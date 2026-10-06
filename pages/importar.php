<?php
/**
 * Módulo de Importación Masiva de Prospectos (Excel / CSV / Ferias Comerciales)
 * Power Pack Suite Comercial
 */

$error_import = '';
$paso = 1; // 1: Cargar archivo/datos, 2: Mapeo y vista previa, 3: Resumen final

// Helper para convertir codificación de Excel Windows a UTF-8 limpio
if (!function_exists('normalizar_utf8')) {
    function normalizar_utf8($cadena) {
        if (!preg_match('//u', $cadena)) {
            return iconv('Windows-1252', 'UTF-8//IGNORE', $cadena);
        }
        return $cadena;
    }
}

// Helper para detectar delimitador (Excel en español suele usar ';' y en inglés ',')
if (!function_exists('detectar_delimitador')) {
    function detectar_delimitador($primera_linea) {
        $semicolon_count = substr_count($primera_linea, ';');
        $comma_count = substr_count($primera_linea, ',');
        $tab_count = substr_count($primera_linea, "\t");

        if ($tab_count > $semicolon_count && $tab_count > $comma_count) {
            return "\t";
        }
        if ($semicolon_count >= $comma_count && $semicolon_count > 0) {
            return ';';
        }
        return ',';
    }
}

// --- PASO 1 -> PASO 2: Procesar archivo subido o texto pegado desde Excel ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_origen'])) {
    $raw_content = '';
    
    // Si pegaron texto directamente desde Excel
    if (!empty(trim($_POST['datos_pegados'] ?? ''))) {
        $raw_content = trim($_POST['datos_pegados']);
    } 
    // O si subieron un archivo CSV/TXT
    elseif (isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
        $tmp_path = $_FILES['archivo_csv']['tmp_name'];
        $raw_content = file_get_contents($tmp_path);
    }

    if (empty($raw_content)) {
        $error_import = 'Por favor selecciona un archivo .CSV o pega las filas copiadas desde Excel.';
    } else {
        $raw_content = normalizar_utf8($raw_content);
        
        // Dividir en líneas
        $lineas = preg_split('/\r\n|\r|\n/', trim($raw_content));
        
        if (count($lineas) < 2) {
            $error_import = 'El archivo o texto debe contener al menos la fila de títulos y una fila de datos.';
        } else {
            // Detectar delimitador con la primera línea
            $delimitador = detectar_delimitador($lineas[0]);
            
            // Si el usuario forzó un delimitador específico
            if (!empty($_POST['delimitador_manual']) && in_array($_POST['delimitador_manual'], [',', ';', "\t"])) {
                $delimitador = $_POST['delimitador_manual'];
            }

            // Parsear encabezados y filas
            $headers = str_getcsv($lineas[0], $delimitador);
            $filas_datos = [];

            for ($i = 1; $i < count($lineas); $i++) {
                $linea_actual = trim($lineas[$i]);
                if ($linea_actual === '') continue;
                
                $row = str_getcsv($linea_actual, $delimitador);
                // Si la fila tiene al menos una celda con contenido
                $tiene_datos = false;
                foreach ($row as $val) {
                    if (trim($val) !== '') { $tiene_datos = true; break; }
                }
                if ($tiene_datos) {
                    $filas_datos[] = $row;
                }
            }

            if (empty($filas_datos)) {
                $error_import = 'No se encontraron filas con datos válidos para procesar.';
            } else {
                // Guardar temporalmente en sesión para el paso de mapeo
                $_SESSION['import_filas'] = $filas_datos;
                $_SESSION['import_headers'] = $headers;
                $_SESSION['import_fuente'] = trim($_POST['fuente_nombre'] ?? 'Feria Comercial');
                $paso = 2;
            }
        }
    }
}

// --- PASO 2 -> PASO 3: Ejecutar la Importación con Mapeo Confirmado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ejecutar_importacion'])) {
    if (empty($_SESSION['import_filas']) || empty($_SESSION['import_headers'])) {
        $error_import = 'La sesión de importación expiró. Por favor vuelve a cargar los datos.';
        $paso = 1;
    } else {
        $filas = $_SESSION['import_filas'];
        $mapping = $_POST['map'] ?? []; // Columna índice => campo destino
        $fuente_evento = trim($_POST['fuente_evento'] ?? 'Feria Comercial');
        $etapa_inicial = trim($_POST['etapa_inicial'] ?? 'lead');
        $crear_empresa_auto = !empty($_POST['crear_empresa_auto']);
        $crear_negocio_auto = !empty($_POST['crear_negocio_auto']);
        $modo_duplicados = $_POST['modo_duplicados'] ?? 'omitir'; // 'omitir', 'actualizar', 'crear_siempre'

        $total_procesados = 0;
        $contactos_creados = 0;
        $contactos_actualizados = 0;
        $duplicados_omitidos = 0;
        $empresas_creadas = 0;
        $negocios_creados = 0;

        // Iniciar transacción segura en SQLite
        $db->exec('BEGIN TRANSACTION');

        try {
            foreach ($filas as $fila) {
                $total_procesados++;
                $datos = [
                    'nombre' => '',
                    'apellido' => '',
                    'empresa' => '',
                    'nit' => '',
                    'telefono' => '',
                    'email' => '',
                    'cargo' => '',
                    'ciudad' => '',
                    'direccion' => '',
                    'sector' => '',
                    'interes' => 2,
                    'notas' => ''
                ];

                foreach ($mapping as $col_idx => $campo) {
                    if ($campo !== 'ignorar' && isset($fila[$col_idx])) {
                        $val = trim($fila[$col_idx]);
                        if ($campo === 'interes') {
                            // Extraer entero de 1 a 3
                            $int_val = (int)preg_replace('/[^0-9]/', '', $val);
                            $datos['interes'] = ($int_val >= 1 && $int_val <= 3) ? $int_val : 2;
                        } else {
                            $datos[$campo] = $val;
                        }
                    }
                }

                // Si no hay nombre pero sí empresa, nombrar como "Contacto - Empresa"
                if (empty($datos['nombre']) && !empty($datos['empresa'])) {
                    $datos['nombre'] = 'Contacto ' . $datos['empresa'];
                }

                // Si no hay ni nombre ni empresa, saltar fila vacía
                if (empty($datos['nombre']) && empty($datos['empresa']) && empty($datos['telefono']) && empty($datos['email'])) {
                    continue;
                }

                // Manejo de Email seguro (NULL si vacío o inválido para no violar UNIQUE)
                $email_limpio = filter_var($datos['email'], FILTER_VALIDATE_EMAIL) ? strtolower($datos['email']) : null;
                $tel_limpio = trim($datos['telefono']);

                // Gestión de Empresa B2B vinculada
                $empresa_id = null;
                if (!empty($datos['empresa']) && $crear_empresa_auto) {
                    $emp_nombre = trim($datos['empresa']);
                    $stmt_emp = $db->prepare("SELECT id FROM empresas WHERE LOWER(nombre) = LOWER(?) LIMIT 1");
                    $stmt_emp->bindValue(1, $emp_nombre, SQLITE3_TEXT);
                    $res_emp = $stmt_emp->execute();
                    $emp_row = $res_emp->fetchArray(SQLITE3_ASSOC);

                    if ($emp_row) {
                        $empresa_id = (int)$emp_row['id'];
                    } else {
                        // Crear nueva Empresa en el catálogo B2B
                        $stmt_new_emp = $db->prepare("INSERT INTO empresas (nombre, nit, ciudad, direccion, telefono, email, sector, fecha_creacion) VALUES (?,?,?,?,?,?,?,datetime('now'))");
                        $stmt_new_emp->bindValue(1, $emp_nombre, SQLITE3_TEXT);
                        $stmt_new_emp->bindValue(2, $datos['nit'] ?: null, SQLITE3_TEXT);
                        $stmt_new_emp->bindValue(3, $datos['ciudad'] ?: null, SQLITE3_TEXT);
                        $stmt_new_emp->bindValue(4, $datos['direccion'] ?: null, SQLITE3_TEXT);
                        $stmt_new_emp->bindValue(5, $tel_limpio ?: null, SQLITE3_TEXT);
                        $stmt_new_emp->bindValue(6, $email_limpio ?: null, SQLITE3_TEXT);
                        $stmt_new_emp->bindValue(7, $datos['sector'] ?: 'Empaques e Industria', SQLITE3_TEXT);
                        $stmt_new_emp->execute();
                        $empresa_id = (int)$db->lastInsertRowID();
                        $empresas_creadas++;
                    }
                }

                // Detección de duplicado existente (por Email o Teléfono)
                $contacto_existente_id = null;
                if ($modo_duplicados !== 'crear_siempre') {
                    if ($email_limpio) {
                        $stmt_chk = $db->prepare("SELECT id FROM contactos WHERE email = ? LIMIT 1");
                        $stmt_chk->bindValue(1, $email_limpio, SQLITE3_TEXT);
                        $chk_res = $stmt_chk->execute()->fetchArray(SQLITE3_ASSOC);
                        if ($chk_res) $contacto_existente_id = (int)$chk_res['id'];
                    }
                    if (!$contacto_existente_id && !empty($tel_limpio) && strlen($tel_limpio) >= 7) {
                        // Buscar coincidencia de teléfono
                        $tel_digits = preg_replace('/[^0-9]/', '', $tel_limpio);
                        $stmt_chk_tel = $db->prepare("SELECT id FROM contactos WHERE replace(replace(replace(replace(telefono,'-',''),' ',''),'+',''),'(','') LIKE ? LIMIT 1");
                        $stmt_chk_tel->bindValue(1, "%$tel_digits%", SQLITE3_TEXT);
                        $chk_tel_res = $stmt_chk_tel->execute()->fetchArray(SQLITE3_ASSOC);
                        if ($chk_tel_res) $contacto_existente_id = (int)$chk_tel_res['id'];
                    }
                }

                // Acción según el modo de duplicados
                if ($contacto_existente_id) {
                    if ($modo_duplicados === 'omitir') {
                        $duplicados_omitidos++;
                        continue;
                    } elseif ($modo_duplicados === 'actualizar') {
                        // Actualizar datos existentes
                        $sql_up = "UPDATE contactos SET ultima_actividad = datetime('now')";
                        if ($empresa_id) $sql_up .= ", empresa_id = $empresa_id";
                        if (!empty($datos['empresa'])) $sql_up .= ", empresa = '" . SQLite3::escapeString($datos['empresa']) . "'";
                        if (!empty($datos['cargo'])) $sql_up .= ", cargo = '" . SQLite3::escapeString($datos['cargo']) . "'";
                        if (!empty($datos['ciudad'])) $sql_up .= ", ciudad = '" . SQLite3::escapeString($datos['ciudad']) . "'";
                        if (!empty($datos['direccion'])) $sql_up .= ", direccion = '" . SQLite3::escapeString($datos['direccion']) . "'";
                        if (!empty($datos['notas'])) $sql_up .= ", notas = coalesce(notas, '') || '\n[Feria " . SQLite3::escapeString($fuente_evento) . "]: " . SQLite3::escapeString($datos['notas']) . "'";
                        $sql_up .= " WHERE id = $contacto_existente_id";
                        $db->exec($sql_up);

                        // Registrar actividad de reactivación en feria
                        $stmt_act = $db->prepare("INSERT INTO actividades (contacto_id, empresa_id, tipo, asunto, descripcion, completada) VALUES (?, ?, 'nota', ?, ?, 1)");
                        $stmt_act->bindValue(1, $contacto_existente_id, SQLITE3_INTEGER);
                        $stmt_act->bindValue(2, $empresa_id ?: null, SQLITE3_INTEGER);
                        $stmt_act->bindValue(3, "Contacto en Feria: $fuente_evento", SQLITE3_TEXT);
                        $stmt_act->bindValue(4, "El contacto fue visto nuevamente en $fuente_evento. " . ($datos['notas'] ? "Notas: " . $datos['notas'] : ''), SQLITE3_TEXT);
                        $stmt_act->execute();

                        $contactos_actualizados++;
                        $contacto_id = $contacto_existente_id;
                    }
                } else {
                    // Determinar prioridad inteligente al importar
                    $prio_auto = 'media';
                    if ((int)$datos['interes'] >= 4) $prio_auto = 'alta';
                    elseif ((int)$datos['interes'] <= 1) $prio_auto = 'baja';

                    // Insertar nuevo contacto
                    $stmt_ins = $db->prepare("INSERT INTO contactos (empresa_id, nombre, apellido, email, telefono, empresa, cargo, ciudad, direccion, sector, fuente, etapa, interes, prioridad, notas, fecha_creacion, ultima_actividad) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,datetime('now'),datetime('now'))");
                    $stmt_ins->bindValue(1, $empresa_id ?: null, SQLITE3_INTEGER);
                    $stmt_ins->bindValue(2, $datos['nombre'], SQLITE3_TEXT);
                    $stmt_ins->bindValue(3, $datos['apellido'] ?: '', SQLITE3_TEXT);
                    $stmt_ins->bindValue(4, $email_limpio, $email_limpio ? SQLITE3_TEXT : SQLITE3_NULL);
                    $stmt_ins->bindValue(5, $tel_limpio ?: '', SQLITE3_TEXT);
                    $stmt_ins->bindValue(6, $datos['empresa'] ?: '', SQLITE3_TEXT);
                    $stmt_ins->bindValue(7, $datos['cargo'] ?: '', SQLITE3_TEXT);
                    $stmt_ins->bindValue(8, $datos['ciudad'] ?: '', SQLITE3_TEXT);
                    $stmt_ins->bindValue(9, $datos['direccion'] ?: '', SQLITE3_TEXT);
                    $stmt_ins->bindValue(10, $datos['sector'] ?: 'General', SQLITE3_TEXT);
                    $stmt_ins->bindValue(11, $fuente_evento, SQLITE3_TEXT);
                    $stmt_ins->bindValue(12, $etapa_inicial, SQLITE3_TEXT);
                    $stmt_ins->bindValue(13, $datos['interes'], SQLITE3_INTEGER);
                    $stmt_ins->bindValue(14, $prio_auto, SQLITE3_TEXT);
                    $stmt_ins->bindValue(15, $datos['notas'] ?: '', SQLITE3_TEXT);
                    $stmt_ins->execute();
                    $contacto_id = (int)$db->lastInsertRowID();
                    $contactos_creados++;

                    // Registrar actividad de origen
                    $stmt_act = $db->prepare("INSERT INTO actividades (contacto_id, empresa_id, tipo, asunto, descripcion, completada) VALUES (?, ?, 'nota', ?, ?, 1)");
                    $stmt_act->bindValue(1, $contacto_id, SQLITE3_INTEGER);
                    $stmt_act->bindValue(2, $empresa_id ?: null, SQLITE3_INTEGER);
                    $stmt_act->bindValue(3, "Ingreso por Evento / Feria", SQLITE3_TEXT);
                    $stmt_act->bindValue(4, "Prospecto captado en el stand de $fuente_evento. " . ($datos['notas'] ? "Requerimiento: " . $datos['notas'] : ''), SQLITE3_TEXT);
                    $stmt_act->execute();
                }

                // Crear Negocio en el Pipeline si se solicitó
                if ($crear_negocio_auto && $contacto_id) {
                    $negocio_nombre = "Oportunidad: " . ($datos['empresa'] ?: $datos['nombre']) . " (" . $fuente_evento . ")";
                    $stmt_neg = $db->prepare("INSERT INTO negocios (contacto_id, empresa_id, nombre, monto, etapa, probabilidad, descripcion, fecha_cierre, ultima_actividad) VALUES (?,?,?,?,?,?,?,date('now', '+30 day'),datetime('now'))");
                    $stmt_neg->bindValue(1, $contacto_id, SQLITE3_INTEGER);
                    $stmt_neg->bindValue(2, $empresa_id ?: null, SQLITE3_INTEGER);
                    $stmt_neg->bindValue(3, $negocio_nombre, SQLITE3_TEXT);
                    $stmt_neg->bindValue(4, 0, SQLITE3_FLOAT);
                    $stmt_neg->bindValue(5, $etapa_inicial === 'lead' ? 'contacto_inicial' : $etapa_inicial, SQLITE3_TEXT);
                    $stmt_neg->bindValue(6, 20, SQLITE3_INTEGER);
                    $stmt_neg->bindValue(7, "Oportunidad creada desde base de datos de $fuente_evento. " . $datos['notas'], SQLITE3_TEXT);
                    $stmt_neg->execute();
                    $negocios_creados++;
                }
            }

            $db->exec('COMMIT');

            // Limpiar datos de sesión
            unset($_SESSION['import_filas'], $_SESSION['import_headers'], $_SESSION['import_fuente']);
            $paso = 3;

        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            $error_import = 'Ocurrió un error al procesar la base de datos: ' . $e->getMessage();
            $paso = 1;
        }
    }
}
?>

<!-- ENCABEZADO DE PÁGINA -->
<div class="page-header">
    <div>
        <h1>📤 Importador de Contactos y Ferias (Excel / CSV)</h1>
        <p>Carga rápidamente bases de datos de prospectos recolectados en eventos, stands o archivos de Excel</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="?page=descargar_plantilla_csv" class="btn btn-secondary btn-sm" title="Descargar formato modelo para Excel">
            📄 Descargar Plantilla Excel (.CSV)
        </a>
        <a href="?page=contactos" class="btn btn-secondary btn-sm">
            👥 Volver a Contactos
        </a>
    </div>
</div>

<?php if ($error_import): ?>
<div class="alert alert-danger" style="margin-bottom:20px">
    ⚠️ <?= h($error_import) ?>
</div>
<?php endif; ?>

<?php
$count_demo_actual = (int)$db->querySingle("SELECT COUNT(*) FROM contactos WHERE fuente = 'Datos de Prueba (Demo)'");
?>

<!-- ========================================================
     SECCIÓN: HOJA DE PRUEBA Y DATOS DE DEMOSTRACIÓN (1 CLIC)
     ======================================================== -->
<div class="card" style="margin-bottom:24px;border:1px solid #bfdbfe;background:linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%)">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;border-bottom:1px solid #e0f2fe;padding-bottom:14px;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:12px">
            <span style="font-size:28px">🧪</span>
            <div>
                <h3 style="margin:0;font-size:16px;color:#0369a1;font-weight:800">
                    Entorno de Pruebas: Clientes de Demostración para Power Pack
                </h3>
                <p style="margin:2px 0 0 0;font-size:12px;color:#0284c7">
                    Pon a prueba el pipeline, las prioridades, las cotizaciones y la redacción con IA con prospectos del sector de empaque y maquinaria.
                </p>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="?page=descargar_hoja_prueba&delimitador=coma" class="btn btn-secondary btn-sm" title="Descargar archivo CSV delimitado por comas" style="background:#fff;border-color:#bae6fd;color:#0369a1;font-weight:700">
                📥 Descargar Hoja Prueba (Comas ,)
            </a>
            <a href="?page=descargar_hoja_prueba&delimitador=puntocoma" class="btn btn-secondary btn-sm" title="Descargar archivo CSV para Microsoft Excel" style="background:#fff;border-color:#bae6fd;color:#0369a1;font-weight:700">
                📥 Descargar para Excel (Punto y coma ;)
            </a>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px">
        <div style="font-size:12px;color:var(--fg-secondary);max-width:650px;line-height:1.5">
            Incluye 12 industrias colombianas (Lácteos, Snacks, Café, Confitería, Cosmética, Pulpa de Frutas, Flow Pack) con requerimientos reales para evaluar la plataforma antes de cargar tu base de datos definitiva.
        </div>
        <div style="display:flex;gap:10px;align-items:center">
            <form method="POST" style="margin:0">
                <input type="hidden" name="cargar_datos_demo" value="1">
                <button type="submit" class="btn btn-primary btn-sm" style="background:#059669;font-weight:800;padding:8px 16px">
                    ⚡ 1-Clic: Cargar 12 Clientes Demo Ahora
                </button>
            </form>
            <?php if ($count_demo_actual > 0): ?>
            <form method="POST" style="margin:0" onsubmit="return confirm('¿Eliminar todos los <?= $count_demo_actual ?> prospectos de demostración cargados?');">
                <input type="hidden" name="borrar_datos_demo" value="1">
                <button type="submit" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fca5a5;padding:8px 14px;font-weight:700" title="Borrar datos de prueba">
                    🗑️ Borrar los <?= $count_demo_actual ?> Clientes Demo
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ==========================================
     PASO 1: SELECCIONAR ARCHIVO O PEGAR EXCEL
     ========================================== -->
<?php if ($paso === 1): ?>
<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:24px">
    
    <div class="card">
        <h3 style="margin-top:0;font-size:17px;color:#2c60a4;display:flex;align-items:center;gap:8px">
            <span>📋</span> Paso 1: Selecciona cómo deseas subir tus datos
        </h3>
        <p style="font-size:13px;color:var(--fg-secondary);margin-bottom:20px">
            Puedes subir tu archivo delimitado por comas/punto y coma o simplemente <strong>copiar las filas desde tu Excel y pegarlas aquí directamente</strong>.
        </p>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="procesar_origen" value="1">

            <!-- ORIGEN / NOMBRE DE LA FERIA -->
            <div class="form-group" style="margin-bottom:20px">
                <label style="font-weight:700;font-size:13px">🎪 Nombre de la Feria / Evento / Origen:</label>
                <input type="text" name="fuente_nombre" value="Feria Andina Pack 2026" required
                       placeholder="Ej: Feria Andina Pack 2026, ExpoAgro, Feria Internacional de Bogotá..."
                       style="font-size:14px;font-weight:600;color:#2c60a4">
                <div style="font-size:11px;color:var(--fg-secondary);margin-top:4px">
                    Todos los prospectos importados quedarán etiquetados con este evento para que luego puedas medirlos y filtrarlos fácilmente.
                </div>
            </div>

            <!-- TABS DE MÉTODO -->
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);padding:18px;margin-bottom:20px">
                <div style="display:flex;gap:16px;margin-bottom:14px;border-bottom:1px solid var(--border);padding-bottom:10px">
                    <label style="font-weight:700;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:6px">
                        <input type="radio" name="metodo_carga" value="pegar" checked onchange="document.getElementById('box-pegar').style.display='block';document.getElementById('box-archivo').style.display='none';">
                        <span>📋 Opción A: Pegar directamente desde Excel (¡Más rápido!)</span>
                    </label>
                    <label style="font-weight:700;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:6px">
                        <input type="radio" name="metodo_carga" value="archivo" onchange="document.getElementById('box-pegar').style.display='none';document.getElementById('box-archivo').style.display='block';">
                        <span>📁 Opción B: Subir archivo .CSV / .TXT</span>
                    </label>
                </div>

                <!-- CONTENEDOR PEGAR DESDE EXCEL -->
                <div id="box-pegar">
                    <div style="font-size:12px;color:var(--fg-secondary);margin-bottom:8px">
                        💡 <strong>Instrucciones:</strong> Abre tu archivo en Excel, selecciona las columnas y filas de tus contactos (incluyendo la fila de títulos), presiona <code>Ctrl + C</code> y pégalas aquí con <code>Ctrl + V</code>:
                    </div>
                    <textarea name="datos_pegados" rows="8" placeholder="Nombre&#9;Empresa&#9;Teléfono&#9;Email&#9;Ciudad&#10;Carlos Gómez&#9;Industrias Plásticas&#9;3109876543&#9;carlos@empresa.com&#9;Bogotá&#10;María Fernández&#9;Lácteos del Valle&#9;3205551234&#9;maria@valle.co&#9;Medellín" style="font-family:monospace;font-size:12px;line-height:1.4;white-space:pre"></textarea>
                </div>

                <!-- CONTENEDOR SUBIR ARCHIVO CSV -->
                <div id="box-archivo" style="display:none">
                    <div style="font-size:12px;color:var(--fg-secondary);margin-bottom:8px">
                        Selecciona tu archivo guardado como <code>CSV (delimitado por comas)</code> desde Excel o Google Sheets:
                    </div>
                    <input type="file" name="archivo_csv" accept=".csv, .txt">
                    
                    <div style="margin-top:14px;display:flex;align-items:center;gap:10px">
                        <label style="font-size:12px;font-weight:600">Separador de columnas:</label>
                        <select name="delimitador_manual" style="padding:4px 8px;font-size:12px;border:1px solid var(--border);border-radius:var(--radius-sm)">
                            <option value="">Auto-detectar (Recomendado)</option>
                            <option value=";">Punto y coma (;) - Estándar Excel en español</option>
                            <option value=",">Coma (,) - CSV Internacional</option>
                            <option value="&#9;">Tabulación (Tab)</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding:12px 24px;font-size:14px;width:100%">
                Continuar al Mapeo de Columnas →
            </button>
        </form>
    </div>

    <!-- GUÍA DE AYUDA RÁPIDA -->
    <div style="display:flex;flex-direction:column;gap:18px">
        <div class="card" style="border-top:3px solid #2c60a4">
            <h4 style="margin-top:0;margin-bottom:10px;color:#2c60a4">💡 Consejos para Ferias y Eventos</h4>
            <ul style="font-size:13px;line-height:1.6;padding-left:18px;margin:0;color:var(--fg-secondary)">
                <li><strong>Plantilla Oficial:</strong> Puedes descargar nuestra plantilla modelo preconfigurada haciendo clic en el botón superior derecho.</li>
                <li><strong>Compatibilidad con Excel en Colombia:</strong> Excel en español guarda los archivos CSV usando punto y coma (<code>;</code>). El sistema lo detecta y procesa automáticamente sin errores.</li>
                <li><strong>Tildes y Eñes:</strong> Nombres como <em>"Bogotá"</em>, <em>"Medellín"</em> o <em>"Empaques"</em> se limpian automáticamente para que no aparezcan símbolos raros.</li>
                <li><strong>Cuentas B2B:</strong> Si incluyes la columna de empresa, el sistema creará las cuentas corporativas automáticamente y vinculará a cada persona a su fábrica.</li>
            </ul>
        </div>

        <div class="card" style="background:#eff6ff;border:1px solid #bfdbfe">
            <h4 style="margin-top:0;margin-bottom:8px;color:#1e40af">⚡ ¿Prefieres copiar y pegar?</h4>
            <p style="font-size:12px;color:#1e3a8a;margin:0;line-height:1.5">
                No necesitas guardar tu archivo en formatos complicados. Con la <strong>Opción A</strong> solo marcas las celdas en tu Excel, haces <code>Ctrl + C</code> y las pegas en el cuadro. El sistema detecta todas las columnas en menos de 1 segundo.
            </p>
        </div>
    </div>

</div>
<?php endif; ?>

<!-- ==========================================
     PASO 2: MAPEO DE COLUMNAS Y VISTA PREVIA
     ========================================== -->
<?php if ($paso === 2): 
    $headers = $_SESSION['import_headers'] ?? [];
    $filas = $_SESSION['import_filas'] ?? [];
    $fuente_evento = $_SESSION['import_fuente'] ?? 'Feria Comercial';
    $total_filas = count($filas);
    $preview_filas = array_slice($filas, 0, 4);

    // Opciones disponibles de campos de destino
    $campos_disponibles = [
        'ignorar'   => '🚫 (Ignorar esta columna)',
        'nombre'    => '👤 Nombre (o Nombre Completo)',
        'apellido'  => '👤 Apellido',
        'empresa'   => '🏢 Empresa / Razón Social B2B',
        'nit'       => '🆔 NIT / RUT de la Empresa',
        'telefono'  => '📱 Teléfono / WhatsApp / Celular',
        'email'     => '✉️ Correo Electrónico',
        'cargo'     => '💼 Cargo / Puesto',
        'ciudad'    => '📍 Ciudad',
        'direccion' => '🏠 Dirección',
        'sector'    => '🏭 Sector Industrial',
        'interes'   => '⭐ Nivel de Interés (1 a 3)',
        'notas'     => '📝 Notas / Requerimiento en Stand'
    ];

    // Helper para auto-detectar campo según el nombre del encabezado
    if (!function_exists('adivinar_campo')) {
        function adivinar_campo($header_name) {
            $h = strtolower(trim($header_name));
            $h = str_replace(['_', '-', '.', 'á', 'é', 'í', 'ó', 'ú'], ['', '', '', 'a', 'e', 'i', 'o', 'u'], $h);

            if (strpos($h, 'apellido') !== false) return 'apellido';
            if (strpos($h, 'nombre') !== false || strpos($h, 'contacto') !== false || strpos($h, 'prospecto') !== false) return 'nombre';
            if (strpos($h, 'empresa') !== false || strpos($h, 'compania') !== false || strpos($h, 'cliente') !== false || strpos($h, 'razon') !== false) return 'empresa';
            if (strpos($h, 'nit') !== false || strpos($h, 'rut') !== false || strpos($h, 'identifica') !== false) return 'nit';
            if (strpos($h, 'tel') !== false || strpos($h, 'cel') !== false || strpos($h, 'movil') !== false || strpos($h, 'what') !== false || strpos($h, 'phone') !== false) return 'telefono';
            if (strpos($h, 'email') !== false || strpos($h, 'correo') !== false || strpos($h, 'mail') !== false) return 'email';
            if (strpos($h, 'cargo') !== false || strpos($h, 'puesto') !== false || strpos($h, 'rol') !== false) return 'cargo';
            if (strpos($h, 'ciudad') !== false || strpos($h, 'municipio') !== false || strpos($h, 'city') !== false) return 'ciudad';
            if (strpos($h, 'dir') !== false || strpos($h, 'address') !== false || strpos($h, 'ubicacion') !== false) return 'direccion';
            if (strpos($h, 'sector') !== false || strpos($h, 'industria') !== false || strpos($h, 'rubro') !== false) return 'sector';
            if (strpos($h, 'interes') !== false || strpos($h, 'prioridad') !== false || strpos($h, 'califica') !== false) return 'interes';
            if (strpos($h, 'nota') !== false || strpos($h, 'comentario') !== false || strpos($h, 'requerimiento') !== false || strpos($h, 'obs') !== false) return 'notas';

            return 'ignorar';
        }
    }
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:20px">
        <div>
            <h3 style="margin:0;font-size:18px;color:#2c60a4">
                🎯 Paso 2: Revisa la correspondencia de columnas (<?= $total_filas ?> filas detectadas)
            </h3>
            <div style="font-size:13px;color:var(--fg-secondary);margin-top:4px">
                El sistema reconoció las columnas automáticamente. Revisa que correspondan a los datos correctos antes de importar.
            </div>
        </div>
        <a href="?page=importar" class="btn btn-secondary btn-sm">← Cargar otro archivo</a>
    </div>

    <form method="POST">
        <input type="hidden" name="ejecutar_importacion" value="1">

        <!-- PARÁMETROS DEL EVENTO -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px;margin-bottom:24px">
            <div>
                <label style="font-weight:700;font-size:12px;text-transform:uppercase;color:var(--fg-secondary)">🎪 Etiqueta de la Feria / Evento:</label>
                <input type="text" name="fuente_evento" value="<?= h($fuente_evento) ?>" required style="margin-top:6px;font-weight:700">
            </div>

            <div>
                <label style="font-weight:700;font-size:12px;text-transform:uppercase;color:var(--fg-secondary)">📊 Etapa Inicial en el Pipeline:</label>
                <select name="etapa_inicial" style="margin-top:6px">
                    <?php foreach ($pipeline_etapas as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= $e['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="font-weight:700;font-size:12px;text-transform:uppercase;color:var(--fg-secondary)">🛡️ Gestión de Duplicados:</label>
                <select name="modo_duplicados" style="margin-top:6px">
                    <option value="omitir">Omitir si el Email o Teléfono ya existe (Recomendado)</option>
                    <option value="actualizar">Actualizar el contacto existente con los nuevos datos</option>
                    <option value="crear_siempre">Crear siempre como nuevo (Permitir duplicados)</option>
                </select>
            </div>
        </div>

        <div style="display:flex;gap:24px;margin-bottom:24px;padding:0 8px">
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;cursor:pointer">
                <input type="checkbox" name="crear_empresa_auto" value="1" checked>
                <span>🏢 Crear automáticamente la Cuenta B2B (Fábrica) si no existe en el sistema</span>
            </label>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;cursor:pointer">
                <input type="checkbox" name="crear_negocio_auto" value="1" checked>
                <span>📋 Crear automáticamente una oportunidad/negocio en el Pipeline comercial</span>
            </label>
        </div>

        <!-- TABLA DE MAPEO DE COLUMNAS -->
        <div style="overflow-x:auto;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:24px">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="background:#2c60a4;color:#fff">
                        <th style="padding:10px 14px;text-align:left;font-size:12px">Columna de tu Excel</th>
                        <th style="padding:10px 14px;text-align:left;font-size:12px;width:280px">Asignar al campo:</th>
                        <th style="padding:10px 14px;text-align:left;font-size:12px">Muestra de datos (primeras filas)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($headers as $idx => $header_name): 
                        $header_clean = trim($header_name);
                        $campo_sugerido = adivinar_campo($header_clean);
                    ?>
                    <tr style="border-bottom:1px solid var(--border);background:<?= $idx % 2 === 0 ? '#ffffff' : '#f8fafc' ?>">
                        <td style="padding:10px 14px;font-weight:700;color:var(--fg)">
                            <div style="font-size:13px"><?= h($header_clean ?: "Columna " . ($idx + 1)) ?></div>
                            <div style="font-size:10px;color:var(--fg-secondary)">Columna #<?= $idx + 1 ?></div>
                        </td>
                        <td style="padding:10px 14px">
                            <select name="map[<?= $idx ?>]" style="width:100%;padding:8px 10px;border:1px solid <?= $campo_sugerido !== 'ignorar' ? '#2c60a4' : '#cbd5e1' ?>;border-radius:var(--radius-sm);font-size:13px;font-weight:<?= $campo_sugerido !== 'ignorar' ? '700' : '400' ?>;color:<?= $campo_sugerido !== 'ignorar' ? '#2c60a4' : '#64748b' ?>">
                                <?php foreach ($campos_disponibles as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $campo_sugerido === $key ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="padding:10px 14px;font-size:12px;color:var(--fg-secondary)">
                            <div style="display:flex;flex-direction:column;gap:3px">
                                <?php 
                                foreach ($preview_filas as $p_row): 
                                    $sample_val = isset($p_row[$idx]) ? trim($p_row[$idx]) : '';
                                    if ($sample_val !== ''):
                                ?>
                                <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:400px">
                                    • <?= h($sample_val) ?>
                                </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px">
            <a href="?page=importar" class="btn btn-secondary" style="padding:12px 20px">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding:12px 28px;font-size:14px;font-weight:800;background:#2c60a4">
                🚀 Importar <?= $total_filas ?> Prospectos al Sistema Ahora
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ==========================================
     PASO 3: RESUMEN Y RESULTADOS DE IMPORTACIÓN
     ========================================== -->
<?php if ($paso === 3): ?>
<div class="card" style="text-align:center;padding:40px 20px;max-width:750px;margin:0 auto">
    <div style="width:68px;height:68px;border-radius:50%;background:#ecfdf5;color:#059669;display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 16px auto">
        ✓
    </div>
    
    <h2 style="margin:0 0 8px 0;color:#0f172a">¡Importación completada con éxito!</h2>
    <p style="color:var(--fg-secondary);font-size:14px;margin-bottom:30px">
        La base de datos de la feria ha sido procesada e ingresada a Power Pack sin errores.
    </p>

    <!-- MÉTRICAS EN TARJETAS -->
    <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;margin-bottom:32px">
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px">
            <div style="font-size:26px;font-weight:900;color:#2c60a4"><?= $contactos_creados ?></div>
            <div style="font-size:11px;font-weight:700;color:var(--fg-secondary);text-transform:uppercase;margin-top:4px">Nuevos Contactos</div>
        </div>
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px">
            <div style="font-size:26px;font-weight:900;color:#059669"><?= $empresas_creadas ?></div>
            <div style="font-size:11px;font-weight:700;color:var(--fg-secondary);text-transform:uppercase;margin-top:4px">Cuentas B2B Creadas</div>
        </div>
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px">
            <div style="font-size:26px;font-weight:900;color:#d97706"><?= $contactos_actualizados ?></div>
            <div style="font-size:11px;font-weight:700;color:var(--fg-secondary);text-transform:uppercase;margin-top:4px">Actualizados</div>
        </div>
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px">
            <div style="font-size:26px;font-weight:900;color:#64748b"><?= $duplicados_omitidos ?></div>
            <div style="font-size:11px;font-weight:700;color:var(--fg-secondary);text-transform:uppercase;margin-top:4px">Duplicados Omitidos</div>
        </div>
    </div>

    <!-- BOTONES DE ACCIÓN POST IMPORTACIÓN -->
    <div style="display:flex;justify-content:center;gap:14px;flex-wrap:wrap">
        <a href="?page=contactos&fuente=<?= urlencode($fuente_evento) ?>" class="btn btn-primary" style="padding:12px 24px;font-weight:800;background:#2c60a4">
            🎪 Ver Contactos de "<?= h($fuente_evento) ?>" →
        </a>
        <a href="?page=pipeline" class="btn btn-secondary" style="padding:12px 20px">
            📋 Ir al Pipeline Comercial
        </a>
        <a href="?page=importar" class="btn btn-secondary" style="padding:12px 20px">
            📤 Importar otra base de datos
        </a>
    </div>
</div>
<?php endif; ?>
