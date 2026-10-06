<?php
/**
 * pages/conocimiento.php - Repositorio de Información & Copiloto IA de Power Pack
 */

require_once __DIR__ . '/../ai.php';

$mensaje_exito = '';
$mensaje_error = '';

// Guardar configuración de IA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_ai_config'])) {
    set_config($db, 'ai_provider', trim($_POST['ai_provider'] ?? 'gemini'));
    set_config($db, 'ai_api_key', trim($_POST['ai_api_key'] ?? ''));
    set_config($db, 'ai_model', trim($_POST['ai_model'] ?? 'gemini-3.8-flash'));
    $mensaje_exito = 'Configuración de Inteligencia Artificial guardada correctamente.';
}

// Guardar base de conocimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_knowledge_base'])) {
    $kb_text = trim($_POST['ai_knowledge_base'] ?? '');
    set_config($db, 'ai_knowledge_base', $kb_text);
    $mensaje_exito = 'Repositorio empresarial y Base de Conocimiento actualizados con éxito.';
}

// Restaurar base de conocimiento predeterminada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restaurar_knowledge_base'])) {
    $default_kb = get_ai_default_knowledge();
    set_config($db, 'ai_knowledge_base', $default_kb);
    $mensaje_exito = 'Base de conocimiento restaurada con el catálogo y políticas oficiales de Power Pack.';
}

// Crear nueva Skill B2B
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_skill'])) {
    $codigo = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $_POST['codigo'] ?? '')));
    $nombre = trim($_POST['nombre'] ?? '');
    $canal = trim($_POST['canal'] ?? 'ambos');
    $desc = trim($_POST['descripcion'] ?? '');
    $framework = trim($_POST['framework_prompt'] ?? '');
    $ejemplo = trim($_POST['ejemplo_salida'] ?? '');
    if ($codigo && $nombre && $framework) {
        $stmt_sk = $db->prepare("INSERT INTO skills_ia (codigo, nombre, canal, categoria, descripcion, framework_prompt, ejemplo_salida, activo) VALUES (?, ?, ?, 'b2b_ventas', ?, ?, ?, 1)");
        $stmt_sk->bindValue(1, $codigo, SQLITE3_TEXT);
        $stmt_sk->bindValue(2, $nombre, SQLITE3_TEXT);
        $stmt_sk->bindValue(3, $canal, SQLITE3_TEXT);
        $stmt_sk->bindValue(4, $desc, SQLITE3_TEXT);
        $stmt_sk->bindValue(5, $framework, SQLITE3_TEXT);
        $stmt_sk->bindValue(6, $ejemplo, SQLITE3_TEXT);
        $stmt_sk->execute();
        $mensaje_exito = "Skill comercial '$nombre' creada e instalada con éxito.";
    } else {
        $mensaje_error = "Por favor completa el código, nombre y directrices de la skill.";
    }
}

// Eliminar Skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_skill'])) {
    $sk_id = (int)($_POST['skill_id'] ?? 0);
    if ($sk_id > 0) {
        $db->exec("DELETE FROM skills_ia WHERE id = $sk_id");
        $mensaje_exito = "Skill comercial eliminada correctamente.";
    }
}

// 1. Subir Documento de Conocimiento / Archivo de Contexto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_documento_ia'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? 'general');
    $texto_manual = trim($_POST['texto_manual'] ?? '');
    
    $nombre_archivo = '';
    $ruta_archivo = '';
    $tipo_mime = 'text/plain';
    $tamano = 0;
    $texto_extraido = '';

    $upload_dir = __DIR__ . '/../uploads/conocimiento/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['archivo_doc']) && $_FILES['archivo_doc']['error'] === UPLOAD_ERR_OK) {
        $orig_name = basename($_FILES['archivo_doc']['name']);
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        $clean_name = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $orig_name);
        $safe_name = time() . '_' . $clean_name;
        $dest_path = $upload_dir . $safe_name;

        if (move_uploaded_file($_FILES['archivo_doc']['tmp_name'], $dest_path)) {
            $nombre_archivo = $orig_name;
            $ruta_archivo = 'uploads/conocimiento/' . $safe_name;
            $tamano = (int)filesize($dest_path);
            $tipo_mime = mime_content_type($dest_path) ?: 'application/octet-stream';
            $texto_extraido = extraer_texto_archivo($dest_path, $orig_name);
            if (empty($titulo)) {
                $titulo = pathinfo($orig_name, PATHINFO_FILENAME);
            }
        } else {
            $mensaje_error = 'Error al guardar el archivo en el servidor. Verifica los permisos de subida.';
        }
    } elseif (!empty($texto_manual)) {
        $safe_name = 'nota_' . time() . '.txt';
        $dest_path = $upload_dir . $safe_name;
        file_put_contents($dest_path, $texto_manual);
        $nombre_archivo = 'Nota_Texto_' . date('Ymd_His') . '.txt';
        $ruta_archivo = 'uploads/conocimiento/' . $safe_name;
        $tamano = strlen($texto_manual);
        $texto_extraido = $texto_manual;
        if (empty($titulo)) {
            $titulo = 'Nota de Contexto (' . date('d/m/Y') . ')';
        }
    } else {
        $mensaje_error = 'Por favor selecciona un archivo (PDF, TXT, CSV...) o pega el texto directamente.';
    }

    if (!empty($texto_extraido)) {
        $stmt_doc = $db->prepare("INSERT INTO documentos_ia (titulo, categoria, nombre_archivo, ruta_archivo, tipo_mime, tamano, texto_extraido, activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt_doc->bindValue(1, $titulo, SQLITE3_TEXT);
        $stmt_doc->bindValue(2, $categoria, SQLITE3_TEXT);
        $stmt_doc->bindValue(3, $nombre_archivo, SQLITE3_TEXT);
        $stmt_doc->bindValue(4, $ruta_archivo, SQLITE3_TEXT);
        $stmt_doc->bindValue(5, $tipo_mime, SQLITE3_TEXT);
        $stmt_doc->bindValue(6, $tamano, SQLITE3_INTEGER);
        $stmt_doc->bindValue(7, $texto_extraido, SQLITE3_TEXT);
        $stmt_doc->execute();
        $mensaje_exito = "¡Documento '{$titulo}' procesado y guardado con éxito! La IA ahora tiene acceso a esta información para redactar mejores correos y WhatsApp.";
    } elseif (empty($mensaje_error)) {
        $mensaje_error = 'No se pudo extraer texto legible del documento seleccionado.';
    }
}

// 2. Alternar estado activo de documento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_documento_ia'])) {
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    if ($doc_id > 0) {
        $db->exec("UPDATE documentos_ia SET activo = 1 - activo WHERE id = $doc_id");
        $mensaje_exito = 'Estado del documento actualizado para la IA.';
    }
}

// 3. Eliminar documento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_documento_ia'])) {
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    if ($doc_id > 0) {
        $ruta = $db->querySingle("SELECT ruta_archivo FROM documentos_ia WHERE id = $doc_id");
        if ($ruta && file_exists(__DIR__ . '/../' . $ruta)) {
            @unlink(__DIR__ . '/../' . $ruta);
        }
        $db->exec("DELETE FROM documentos_ia WHERE id = $doc_id");
        $mensaje_exito = 'Documento eliminado del contexto de la IA.';
    }
}

// 4. Editar texto extraído de documento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_documento_ia'])) {
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    $nuevo_titulo = trim($_POST['titulo'] ?? '');
    $nueva_cat = trim($_POST['categoria'] ?? 'general');
    $nuevo_texto = trim($_POST['texto_extraido'] ?? '');
    if ($doc_id > 0 && $nuevo_titulo && $nuevo_texto) {
        $stmt_ed = $db->prepare("UPDATE documentos_ia SET titulo = ?, categoria = ?, texto_extraido = ? WHERE id = ?");
        $stmt_ed->bindValue(1, $nuevo_titulo, SQLITE3_TEXT);
        $stmt_ed->bindValue(2, $nueva_cat, SQLITE3_TEXT);
        $stmt_ed->bindValue(3, $nuevo_texto, SQLITE3_TEXT);
        $stmt_ed->bindValue(4, $doc_id, SQLITE3_INTEGER);
        $stmt_ed->execute();
        $mensaje_exito = 'Texto del documento actualizado correctamente.';
    }
}

$ai_settings = get_ai_settings($db);
$skills_list = $db->query("SELECT * FROM skills_ia ORDER BY id ASC");
$documentos_list = $db->query("SELECT * FROM documentos_ia ORDER BY id DESC");
$total_docs = (int)$db->querySingle("SELECT COUNT(*) FROM documentos_ia");
$activos_docs = (int)$db->querySingle("SELECT COUNT(*) FROM documentos_ia WHERE activo = 1");
?>

<div class="page-header">
    <div>
        <h1>🧠 Repositorio IA & Base de Conocimiento</h1>
        <p>Entrena al Asistente IA de Power Pack con la información de tu empresa, catálogo de maquinaria y políticas comerciales</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="?page=contactos" class="btn btn-secondary btn-sm">👥 Ir a Contactos</a>
        <a href="https://aistudio.google.com/app/apikey" target="_blank" class="btn btn-secondary btn-sm" title="Obtener API Key gratuita de Google Gemini">
            🔑 Obtener API Key de Gemini Gratis ↗
        </a>
    </div>
</div>

<?php if ($mensaje_exito): ?>
<div class="alert alert-success" style="margin-bottom:20px">
    ✅ <?= h($mensaje_exito) ?>
</div>
<?php endif; ?>

<?php if ($mensaje_error): ?>
<div class="alert alert-danger" style="margin-bottom:20px">
    ⚠️ <?= h($mensaje_error) ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1.1fr 1fr;gap:24px">

    <!-- COLUMNA 1: CONFIGURACIÓN DE IA Y SIMULADOR -->
    <div style="display:flex;flex-direction:column;gap:24px">
        
        <!-- TARJETA: MOTOR DE INTELIGENCIA ARTIFICIAL -->
        <div class="card" style="border-top:3px solid #2c60a4">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:24px">⚡</span>
                    <div>
                        <h3 style="margin:0;font-size:16px;color:#2c60a4">Motor de Inteligencia Artificial</h3>
                        <p style="margin:2px 0 0 0;font-size:12px;color:var(--fg-secondary)">Conecta Google Gemini u OpenAI para redacciones comerciales autónomas</p>
                    </div>
                </div>
                <div>
                    <?php if (!empty($ai_settings['api_key'])): ?>
                    <span style="display:inline-block;padding:3px 10px;border-radius:20px;background:#ecfdf5;color:#059669;font-size:11px;font-weight:800">
                        ● Conectado a <?= strtoupper($ai_settings['provider']) ?>
                    </span>
                    <?php else: ?>
                    <span style="display:inline-block;padding:3px 10px;border-radius:20px;background:#eff6ff;color:#2c60a4;font-size:11px;font-weight:800">
                        ⚡ Motor Inteligente Interno Activo
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ASISTENTE DE CONEXIÓN RÁPIDA 1-CLIC -->
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:16px;margin-bottom:18px">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px">
                    <div>
                        <strong style="color:#0369a1;font-size:13px;display:block">⚡ Asistente Rápido: Conectar Google Gemini Gratis</strong>
                        <span style="font-size:11px;color:#0284c7">Obtén tu clave gratuita de Google y conéctala en 3 clics sin salir de la plataforma:</span>
                    </div>
                    <a href="https://aistudio.google.com/app/apikey" target="_blank" class="btn btn-primary btn-sm" style="background:#0284c7;white-space:nowrap;font-size:12px;padding:7px 12px;font-weight:700">
                        🔑 1. Abrir Google AI Studio ↗
                    </a>
                </div>
            </div>

            <form method="POST" id="form-ai-config">
                <input type="hidden" name="guardar_ai_config" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label style="font-weight:700;font-size:12px">Proveedor de IA:</label>
                        <select name="ai_provider" id="ai_provider" onchange="actualizarModelos()" style="font-weight:600">
                            <option value="gemini" <?= $ai_settings['provider']==='gemini'?'selected':'' ?>>Google Gemini (Recomendado - Free Tier)</option>
                            <option value="openai" <?= $ai_settings['provider']==='openai'?'selected':'' ?>>OpenAI (ChatGPT)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:700;font-size:12px">Modelo de IA:</label>
                        <select name="ai_model" id="ai_model" style="font-weight:600">
                            <option value="gemini-3.8-flash" <?= ($ai_settings['model']==='gemini-3.8-flash'||$ai_settings['model']==='gemini-2.0-flash')?'selected':'' ?>>Gemini 3.8 Flash (Recomendado - Nueva Generación)</option>
                            <option value="gemini-2.5-flash" <?= $ai_settings['model']==='gemini-2.5-flash'?'selected':'' ?>>Gemini 2.5 Flash</option>
                            <option value="gemini-1.5-flash" <?= $ai_settings['model']==='gemini-1.5-flash'?'selected':'' ?>>Gemini 1.5 Flash</option>
                            <option value="gpt-4o-mini" <?= $ai_settings['model']==='gpt-4o-mini'?'selected':'' ?>>GPT-4o Mini</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top:10px">
                    <label style="font-weight:700;font-size:12px;display:flex;justify-content:space-between;align-items:center">
                        <span>API Key Secreta:</span>
                        <button type="button" onclick="pegarApiKeyDesdeClipboard('ai_api_key')" class="btn btn-secondary btn-sm" style="font-size:11px;padding:3px 10px;font-weight:700;color:#0284c7;background:#f0f9ff;border:1px solid #bae6fd">
                            📋 2. Pegar desde Portapapeles
                        </button>
                    </label>
                    <div style="display:flex;gap:6px;margin-top:4px">
                        <input type="password" id="ai_api_key" name="ai_api_key" value="<?= h($ai_settings['api_key']) ?>" placeholder="AIzaSy... o sk-proj-..." style="flex:1;font-family:monospace;font-size:13px;padding:9px 12px">
                        <button type="button" id="btn_toggle_kb_key" onclick="toggleVisibilidadPassword('ai_api_key', 'btn_toggle_kb_key')" class="btn btn-secondary btn-sm" style="padding:0 12px;font-size:12px">
                            👁️ Mostrar
                        </button>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
                        <span style="font-size:11px;color:var(--fg-secondary)">
                            ¿No tienes API Key? Google Gemini ofrece un <strong>nivel 100% gratuito</strong> sin tarjeta de crédito.
                        </span>
                        <a href="https://aistudio.google.com/app/apikey" target="_blank" style="font-size:11px;color:#2c60a4;font-weight:700">Crear clave en AI Studio ↗</a>
                    </div>
                </div>

                <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap">
                    <button type="button" onclick="conectarGeminiAJAX('ai_api_key', 'res-test-ia')" class="btn btn-primary" style="padding:10px 18px;font-size:13px;background:#059669;font-weight:700" id="btn-conectar-ia">
                        ⚡ 3. Conectar y Probar en Vivo
                    </button>
                    <button type="submit" class="btn btn-secondary" style="padding:10px 16px;font-size:13px">
                        💾 Guardar Conexión
                    </button>
                    <?php if (!empty($ai_settings['api_key'])): ?>
                    <button type="button" onclick="document.getElementById('ai_api_key').value='';conectarGeminiAJAX('ai_api_key', 'res-test-ia');" class="btn btn-outline" style="padding:10px 12px;font-size:12px;color:#dc2626;border-color:#fca5a5">
                        Desconectar
                    </button>
                    <?php endif; ?>
                </div>
                <div id="res-test-ia" style="margin-top:14px;font-size:12px;display:none"></div>
            </form>
        </div>

        <!-- TARJETA: SIMULADOR INTERACTIVO DE REDACCIÓN -->
        <div class="card" style="background:#f8fafc">
            <h3 style="margin-top:0;font-size:16px;color:#0f172a;display:flex;align-items:center;gap:8px">
                <span>💬</span> Probador / Simulador en Tiempo Real
            </h3>
            <p style="font-size:12px;color:var(--fg-secondary);margin-bottom:14px">
                Prueba cómo redacta la IA de Power Pack consultando el repositorio empresarial:
            </p>

            <div style="display:flex;flex-direction:column;gap:12px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Canal:</label>
                        <select id="sim_canal" style="width:100%;padding:6px 10px;font-size:12px">
                            <option value="whatsapp">📱 WhatsApp</option>
                            <option value="email">✉️ Correo Electrónico</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Skill B2B a Evaluar:</label>
                        <select id="sim_skill" style="width:100%;padding:6px 10px;font-size:12px;font-weight:600">
                            <option value="">— Sin Skill (Modo Estándar) —</option>
                            <?php 
                            $skills_list->reset();
                            while($sk = $skills_list->fetchArray(SQLITE3_ASSOC)): 
                            ?>
                            <option value="<?= h($sk['codigo']) ?>"><?= h($sk['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Objetivo Comercial:</label>
                        <select id="sim_objetivo" style="width:100%;padding:6px 10px;font-size:12px">
                            <option value="seguimiento_feria">🎪 Seguimiento Post-Feria</option>
                            <option value="propuesta">📄 Presentación de Cotización</option>
                            <option value="agendar_visita">🏢 Invitar al Showroom en Bogotá</option>
                            <option value="reactivacion">🧊 Reactivar Cliente Frío</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Instrucción del cliente:</label>
                        <input type="text" id="sim_instrucciones" placeholder="Ej: Cliente interesado en empacar queso al vacío..." value="Interesado en empacadora al vacío de campana para cárnicos" style="width:100%;font-size:12px">
                    </div>
                </div>

                <button type="button" onclick="ejecutarSimulacionIA()" class="btn btn-primary" id="btn-simular-ia" style="background:#2c60a4;padding:8px 16px;font-size:13px;font-weight:700">
                    ⚡ Generar Mensaje Asistido
                </button>

                <!-- RESULTADO DE LA SIMULACIÓN -->
                <div id="sim_resultado_box" style="display:none;background:#fff;border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-top:6px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span id="sim_origen_tag" style="font-size:11px;font-weight:800;color:#2c60a4"></span>
                        <button type="button" onclick="copiarSimulacion()" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:11px">📋 Copiar</button>
                    </div>
                    <div id="sim_asunto_box" style="display:none;font-weight:800;font-size:13px;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #e2e8f0"></div>
                    <div id="sim_mensaje_box" style="font-size:13px;line-height:1.5;white-space:pre-wrap;color:#334155"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- COLUMNA 2: BASE DE CONOCIMIENTO (REPOSITORIO DE LA EMPRESA) -->
    <div class="card" style="display:flex;flex-direction:column">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;border-bottom:1px solid var(--border);padding-bottom:14px">
            <div>
                <h3 style="margin:0;font-size:17px;color:#2c60a4;display:flex;align-items:center;gap:8px">
                    <span>📚</span> Repositorio de la Empresa (Base de Conocimiento)
                </h3>
                <p style="margin:4px 0 0 0;font-size:12px;color:var(--fg-secondary)">
                    Este texto es el "cerebro" que lee la IA para redactar con exactitud técnica sobre la maquinaria y políticas de Power Pack.
                </p>
            </div>
        </div>

        <form method="POST" style="display:flex;flex-direction:column;flex:1">
            <input type="hidden" name="guardar_knowledge_base" value="1">
            
            <div style="margin-bottom:8px;font-size:11px;color:#64748b">
                💡 <em>Puedes agregar nuevos productos, cambiar precios de referencia, actualizar políticas de garantía o incluir nuevas preguntas frecuentes:</em>
            </div>

            <textarea name="ai_knowledge_base" rows="22" style="width:100%;font-family:monospace;font-size:12px;line-height:1.45;padding:14px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fafafa;color:#0f172a;resize:vertical"><?= h(get_config($db, 'ai_knowledge_base', get_ai_default_knowledge())) ?></textarea>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px">
                <button type="submit" class="btn btn-primary" style="padding:10px 24px;font-size:13px;font-weight:800;background:#2c60a4">
                    💾 Guardar Repositorio Empresarial
                </button>
            </div>
        </form>

        <form method="POST" onsubmit="return confirm('¿Seguro que deseas restaurar la base de conocimiento original de Power Pack?');" style="margin-top:10px;text-align:right">
            <input type="hidden" name="restaurar_knowledge_base" value="1">
            <button type="submit" style="background:none;border:none;color:#94a3b8;font-size:11px;text-decoration:underline;cursor:pointer">
                Restaurar texto predeterminado de Power Pack
            </button>
        </form>
    </div>

</div>

<!-- ========================================================
     SECCIÓN: ARCHIVOS & DOCUMENTOS DE CONTEXTO EMPRESARIAL
     ======================================================== -->
<div style="margin-top:32px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px">
        <div>
            <h2 style="margin:0;font-size:20px;color:#0f172a;display:flex;align-items:center;gap:10px">
                <span>📁</span> Archivos de Contexto & Documentos de la Empresa
                <span style="font-size:12px;background:#ecfdf5;color:#059669;padding:3px 10px;border-radius:12px;border:1px solid #a7f3d0;font-weight:700">
                    ● <?= $activos_docs ?> Activos en el Cerebro de la IA
                </span>
            </h2>
            <p style="margin:4px 0 0 0;font-size:13px;color:var(--fg-secondary)">
                Sube listas de precios, inventarios, políticas de garantía, fichas técnicas o correos de referencia para que Google Gemini y las Skills los consulten antes de redactar.
            </p>
        </div>
        <div>
            <button type="button" onclick="abrirModalSubirDoc()" class="btn btn-primary" style="background:#059669;font-weight:800;display:flex;align-items:center;gap:8px;box-shadow:0 2px 6px rgba(5,150,105,0.25)">
                <span>📤 Subir Archivo o Pegar Contenido</span>
            </button>
        </div>
    </div>

    <!-- TARJETA CON TABLA DE DOCUMENTOS SUBIDOS -->
    <div class="card" style="padding:0;overflow:hidden;border:1px solid var(--border)">
        <?php if ($total_docs > 0): ?>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;margin:0">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:1px solid var(--border)">
                        <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:800;color:var(--fg-secondary)">Categoría</th>
                        <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:800;color:var(--fg-secondary)">Documento / Título</th>
                        <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:800;color:var(--fg-secondary)">Tamaño</th>
                        <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:800;color:var(--fg-secondary)">Fecha Subida</th>
                        <th style="padding:12px 16px;text-align:center;font-size:12px;font-weight:800;color:var(--fg-secondary)">Estado para la IA</th>
                        <th style="padding:12px 16px;text-align:right;font-size:12px;font-weight:800;color:var(--fg-secondary)">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $cat_badges = [
                        'precios'    => ['🏷️ Precios & Tarifas', '#fef3c7', '#92400e', '#fde68a'],
                        'inventario' => ['📦 Inventarios & Stock', '#e0f2fe', '#0369a1', '#bae6fd'],
                        'politicas'  => ['📋 Políticas & Envíos', '#f3e8ff', '#6b21a8', '#e9d5ff'],
                        'catalogo'   => ['⚙️ Ficha Técnica', '#ecfdf5', '#065f46', '#a7f3d0'],
                        'correos'    => ['✉️ Correo de Referencia', '#fff1f2', '#9f1239', '#fecdd3'],
                        'general'    => ['🏢 General Empresa', '#f1f5f9', '#475569', '#cbd5e1']
                    ];
                    while ($d = $documentos_list->fetchArray(SQLITE3_ASSOC)): 
                        $cinfo = $cat_badges[$d['categoria']] ?? ['🏢 General', '#f1f5f9', '#475569', '#cbd5e1'];
                        $kb_size = round($d['tamano'] / 1024, 1);
                    ?>
                    <tr style="border-bottom:1px solid #f1f5f9;background:<?= $d['activo'] ? '#fff' : '#fcfcfd' ?>">
                        <td style="padding:14px 16px">
                            <span class="badge" style="background:<?= $cinfo[1] ?>;color:<?= $cinfo[2] ?>;border:1px solid <?= $cinfo[3] ?>;font-size:11px;font-weight:700">
                                <?= $cinfo[0] ?>
                            </span>
                        </td>
                        <td style="padding:14px 16px">
                            <div style="font-weight:700;font-size:13px;color:#0f172a"><?= h($d['titulo']) ?></div>
                            <div style="font-size:11px;color:var(--fg-secondary);display:flex;align-items:center;gap:6px;margin-top:2px">
                                <span>📄 <?= h($d['nombre_archivo']) ?></span>
                                <?php if (file_exists(__DIR__ . '/../' . $d['ruta_archivo'])): ?>
                                <a href="<?= h($d['ruta_archivo']) ?>" target="_blank" style="color:#2563eb;text-decoration:underline">Descargar original ↗</a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="padding:14px 16px;font-size:12px;color:var(--fg-secondary)">
                            <?= $kb_size > 0 ? $kb_size . ' KB' : '—' ?>
                        </td>
                        <td style="padding:14px 16px;font-size:12px;color:var(--fg-secondary)">
                            <?= date('d/m/Y H:i', strtotime($d['fecha_subida'])) ?>
                        </td>
                        <td style="padding:14px 16px;text-align:center">
                            <form method="POST" style="margin:0;display:inline-block">
                                <input type="hidden" name="toggle_documento_ia" value="1">
                                <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                                <button type="submit" class="btn btn-sm" style="font-size:11px;padding:3px 10px;font-weight:700;border-radius:12px;<?= $d['activo'] ? 'background:#ecfdf5;color:#059669;border:1px solid #a7f3d0' : 'background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1' ?>" title="Haz clic para activar o pausar este documento del contexto de la IA">
                                    <?= $d['activo'] ? '● Activo en IA' : '○ Pausado' ?>
                                </button>
                            </form>
                        </td>
                        <td style="padding:14px 16px;text-align:right">
                            <div style="display:flex;justify-content:flex-end;gap:8px">
                                <button type="button" onclick="verDocumentoIA(<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>)" class="btn btn-secondary btn-sm" style="font-size:11px;padding:4px 10px" title="Ver o editar el texto exacto que lee la IA">
                                    👁️ Ver Texto
                                </button>
                                <form method="POST" style="margin:0;display:inline-block" onsubmit="return confirm('¿Seguro que deseas eliminar este documento del contexto de la IA?');">
                                    <input type="hidden" name="eliminar_documento_ia" value="1">
                                    <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" style="font-size:11px;padding:4px 8px;color:#dc2626;border-color:#fca5a5" title="Eliminar documento">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:40px 20px;background:#f8fafc">
            <span style="font-size:42px;display:block;margin-bottom:8px">📂</span>
            <h4 style="font-size:15px;font-weight:800;color:#0f172a;margin:0 0 6px 0">Aún no has subido documentos adicionales</h4>
            <p style="font-size:13px;color:var(--fg-secondary);max-width:550px;margin:0 auto 16px auto">
                Sube listas de precios, inventarios en Excel/CSV, fichas técnicas en PDF o correos reales de la empresa para que Google Gemini y las Skills comerciales redacten con exactitud total.
            </p>
            <button type="button" onclick="abrirModalSubirDoc()" class="btn btn-primary btn-sm" style="background:#059669;font-weight:700">
                📤 Subir tu Primer Documento o Pegar Contenido
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================
     SECCIÓN: CATÁLOGO DE SKILLS COMERCIALES B2B INSTALADAS
     ======================================================== -->
<div style="margin-top:32px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px">
        <div>
            <h2 style="margin:0;font-size:20px;color:#0f172a;display:flex;align-items:center;gap:10px">
                <span>🎯</span> Skills Comerciales B2B Instaladas en Power Pack
            </h2>
            <p style="margin:4px 0 0 0;font-size:13px;color:var(--fg-secondary)">
                Habilidades y marcos metodológicos de venta consultiva (AIDA, PAS, BAB, Magic Email) que dotan a la IA del criterio experto para redactar correos de alta conversión y mensajes ágiles de WhatsApp.
            </p>
        </div>
        <button onclick="document.getElementById('modalNuevaSkill').style.display='flex'" class="btn btn-primary btn-sm" style="background:#2c60a4;font-weight:700">
            ➕ Nueva Skill B2B
        </button>
    </div>

    <!-- GRID DE SKILLS -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:20px">
        <?php 
        $skills_list->reset();
        while($sk = $skills_list->fetchArray(SQLITE3_ASSOC)): 
            $canal_badge_cls = ($sk['canal'] === 'whatsapp') ? 'badge-calificado' : (($sk['canal'] === 'email') ? 'badge-contacto_inicial' : 'badge-lead');
            $canal_label = ($sk['canal'] === 'whatsapp') ? '📱 WhatsApp' : (($sk['canal'] === 'email') ? '✉️ Correo Electrónico' : '🌐 WhatsApp & Correo');
        ?>
        <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,0.04);transition:transform 0.2s ease">
            <div>
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
                    <span class="badge <?= $canal_badge_cls ?>" style="font-size:11px">
                        <?= $canal_label ?>
                    </span>
                    <code style="font-size:11px;background:#f1f5f9;color:#2c60a4;padding:2px 6px;border-radius:4px"><?= h($sk['codigo']) ?></code>
                </div>

                <h3 style="margin:0 0 8px 0;font-size:15px;color:#0f172a;line-height:1.35">
                    <?= h($sk['nombre']) ?>
                </h3>

                <div style="font-size:12px;color:#475569;line-height:1.5;margin-bottom:12px">
                    <strong style="color:#0f172a">Función Comercial:</strong> <?= h($sk['descripcion']) ?>
                </div>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px;font-size:11px;color:#334155;line-height:1.45;margin-bottom:12px">
                    <strong style="color:#2c60a4">🧠 Marco Metodológico (Prompt):</strong><br>
                    <?= nl2br(h($sk['framework_prompt'])) ?>
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #f1f5f9;padding-top:12px;margin-top:6px">
                <button type="button" onclick="probarSkillDirecto('<?= $sk['canal']==='email'?'email':'whatsapp' ?>', '<?= h($sk['codigo']) ?>')" class="btn btn-secondary btn-sm" style="font-size:12px;font-weight:700">
                    ⚡ Probar en Simulador
                </button>
                <form method="POST" style="margin:0" onsubmit="return confirm('¿Eliminar esta skill?');">
                    <input type="hidden" name="eliminar_skill" value="1">
                    <input type="hidden" name="skill_id" value="<?= (int)$sk['id'] ?>">
                    <button type="submit" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:12px" title="Eliminar skill">🗑️</button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- MODAL NUEVA SKILL B2B -->
<div id="modalNuevaSkill" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.65);z-index:9999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px)">
    <div style="background:#fff;border-radius:12px;max-width:580px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);overflow:hidden">
        <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);padding:18px 24px;border-bottom:3px solid #2c60a4;display:flex;justify-content:space-between;align-items:center">
            <h3 style="color:#fff;margin:0;font-size:16px;font-weight:800">➕ Crear Nueva Skill Comercial B2B</h3>
            <button onclick="document.getElementById('modalNuevaSkill').style.display='none'" style="background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer">&times;</button>
        </div>

        <form method="POST" style="padding:22px;display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="crear_skill" value="1">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <label style="font-size:12px;font-weight:700">Código Único (slug):</label>
                    <input type="text" name="codigo" required placeholder="ej: cierre_urgencia" style="width:100%;font-size:13px;margin-top:4px">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:700">Canal:</label>
                    <select name="canal" style="width:100%;font-size:13px;padding:8px;margin-top:4px">
                        <option value="ambos">Ambos (WhatsApp & Correo)</option>
                        <option value="whatsapp">Solo WhatsApp</option>
                        <option value="email">Solo Correo Electrónico</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="font-size:12px;font-weight:700">Nombre de la Skill:</label>
                <input type="text" name="nombre" required placeholder="ej: 🚀 Fórmula Cierre: Descuento por Volumen" style="width:100%;font-size:13px;margin-top:4px">
            </div>

            <div>
                <label style="font-size:12px;font-weight:700">Función Comercial (¿Qué logra y cuándo usarla?):</label>
                <textarea name="descripcion" rows="2" required placeholder="Explica el objetivo de venta de esta habilidad..." style="width:100%;font-size:12px;margin-top:4px"></textarea>
            </div>

            <div>
                <label style="font-size:12px;font-weight:700">Marco Metodológico / Prompt Directivo para la IA:</label>
                <textarea name="framework_prompt" rows="4" required placeholder="Instruye a la IA sobre la estructura persuasiva a seguir..." style="width:100%;font-size:12px;margin-top:4px"></textarea>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px">
                <button type="button" onclick="document.getElementById('modalNuevaSkill').style.display='none'" class="btn btn-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm" style="background:#2c60a4;font-weight:800;padding:8px 20px">Guardar e Instalar Skill</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     MODAL 1: SUBIR DOCUMENTO O PEGAR CONTEXTO IA
     ======================================================== -->
<div id="modalSubirDocIA" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.65);z-index:9999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px)">
    <div style="background:#fff;border-radius:12px;max-width:620px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);overflow:hidden;max-height:92vh;display:flex;flex-direction:column">
        <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);padding:18px 24px;border-bottom:3px solid #059669;display:flex;justify-content:space-between;align-items:center">
            <h3 style="color:#fff;margin:0;font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px">
                <span>📁</span> Subir Documento o Información para la IA
            </h3>
            <button type="button" onclick="document.getElementById('modalSubirDocIA').style.display='none'" style="background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer">&times;</button>
        </div>

        <form method="POST" enctype="multipart/form-data" style="padding:22px;overflow-y:auto;display:flex;flex-direction:column;gap:16px">
            <input type="hidden" name="subir_documento_ia" value="1">
            <input type="hidden" name="modo_subida" id="modo_subida_input" value="archivo">

            <!-- SELECTOR DE CATEGORÍA Y TÍTULO -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px">Categoría del Contenido:</label>
                    <select name="categoria" id="doc_categoria" required style="width:100%;font-size:13px;padding:8px;border-radius:6px;border:1px solid #cbd5e1">
                        <option value="precios">🏷️ Lista de Precios & Tarifas</option>
                        <option value="inventario">📦 Inventarios & Disponibilidad Stock</option>
                        <option value="politicas">📋 Políticas Comerciales, Envíos & Garantías</option>
                        <option value="catalogo">⚙️ Fichas Técnicas & Especificaciones</option>
                        <option value="correos">✉️ Correos Reales & Modelos de Éxito</option>
                        <option value="general" selected>🏢 Información General de la Empresa</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px">Título Descriptivo:</label>
                    <input type="text" name="titulo" id="doc_titulo" required placeholder="ej: Precios Envasadoras 2026 o Stock Medellín" style="width:100%;font-size:13px;padding:8px;border-radius:6px;border:1px solid #cbd5e1">
                </div>
            </div>

            <!-- TABS MODO: SUBIR ARCHIVO VS PEGAR TEXTO -->
            <div>
                <label style="font-size:12px;font-weight:700;display:block;margin-bottom:6px">¿Cómo deseas agregar la información?</label>
                <div style="display:flex;gap:8px;background:#f1f5f9;padding:4px;border-radius:8px">
                    <button type="button" id="tab-btn-archivo" onclick="cambiarModoSubida('archivo')" style="flex:1;padding:8px 12px;font-size:12px;font-weight:700;border:none;border-radius:6px;cursor:pointer;background:#fff;color:#0f172a;box-shadow:0 1px 3px rgba(0,0,0,0.1)">
                        📎 1. Subir Archivo (PDF, CSV, Excel, TXT, EML)
                    </button>
                    <button type="button" id="tab-btn-texto" onclick="cambiarModoSubida('texto')" style="flex:1;padding:8px 12px;font-size:12px;font-weight:600;border:none;border-radius:6px;cursor:pointer;background:transparent;color:#64748b">
                        📋 2. Pegar Texto o Correo Directo
                    </button>
                </div>
            </div>

            <!-- PANEL 1: SUBIDA DE ARCHIVO -->
            <div id="panel-modo-archivo" style="background:#f8fafc;border:2px dashed #cbd5e1;border-radius:10px;padding:18px;text-align:center">
                <input type="file" name="archivo" id="archivo_input" accept=".pdf,.txt,.csv,.tsv,.json,.md,.html,.htm,.eml" style="display:block;margin:0 auto 10px auto;max-width:100%;font-size:13px" onchange="autoCompletarTitulo(this)">
                <div style="font-size:12px;color:var(--fg-secondary);line-height:1.5">
                    <strong>Formatos compatibles:</strong> PDF (fichas y catálogos), CSV / TXT / TSV (inventarios y listas de Excel exportadas como CSV), EML / HTML (correos anteriores), Markdown.<br>
                    <span style="font-size:11px;color:#0369a1">💡 El sistema extraerá el texto automáticamente para que Gemini pueda leerlo sin necesidad de abrir el archivo.</span>
                </div>
            </div>

            <!-- PANEL 2: PEGAR TEXTO DIRECTO -->
            <div id="panel-modo-texto" style="display:none">
                <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px">Texto o Correo Electrónico:</label>
                <textarea name="texto_directo" id="texto_directo_input" rows="8" placeholder="Pega aquí el contenido del correo, lista rápida de precios, especificaciones de una máquina o notas comerciales..." style="width:100%;font-size:12px;padding:10px;border-radius:6px;border:1px solid #cbd5e1;font-family:inherit"></textarea>
                <span style="font-size:11px;color:var(--fg-secondary)">Ideal para copiar y pegar respuestas frecuentes que das a los clientes o respuestas de proveedores.</span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e2e8f0;padding-top:14px">
                <div style="font-size:11px;color:var(--fg-secondary)">
                    📁 Se guardará en <code>uploads/conocimiento/</code>
                </div>
                <div style="display:flex;gap:10px">
                    <button type="button" onclick="document.getElementById('modalSubirDocIA').style.display='none'" class="btn btn-secondary btn-sm">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="background:#059669;font-weight:800;padding:8px 20px">
                        📥 Subir e Integrar al Cerebro IA
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     MODAL 2: VER Y EDITAR TEXTO EXTRAÍDO
     ======================================================== -->
<div id="modalVerDocIA" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.65);z-index:9999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px)">
    <div style="background:#fff;border-radius:12px;max-width:760px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);overflow:hidden;max-height:92vh;display:flex;flex-direction:column">
        <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);padding:18px 24px;border-bottom:3px solid #2c60a4;display:flex;justify-content:space-between;align-items:center">
            <div>
                <h3 style="color:#fff;margin:0;font-size:16px;font-weight:800" id="ver_doc_header_title">
                    👁️ Contexto del Documento en la IA
                </h3>
                <span style="font-size:11px;color:#94a3b8" id="ver_doc_header_sub"></span>
            </div>
            <button type="button" onclick="document.getElementById('modalVerDocIA').style.display='none'" style="background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer">&times;</button>
        </div>

        <form method="POST" style="padding:22px;overflow-y:auto;display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="editar_documento_ia" value="1">
            <input type="hidden" name="doc_id" id="edit_doc_id" value="">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px">Título:</label>
                    <input type="text" name="titulo" id="edit_doc_titulo" required style="width:100%;font-size:13px;padding:8px;border-radius:6px;border:1px solid #cbd5e1">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px">Categoría:</label>
                    <select name="categoria" id="edit_doc_categoria" required style="width:100%;font-size:13px;padding:8px;border-radius:6px;border:1px solid #cbd5e1">
                        <option value="precios">🏷️ Lista de Precios & Tarifas</option>
                        <option value="inventario">📦 Inventarios & Disponibilidad Stock</option>
                        <option value="politicas">📋 Políticas Comerciales, Envíos & Garantías</option>
                        <option value="catalogo">⚙️ Fichas Técnicas & Especificaciones</option>
                        <option value="correos">✉️ Correos Reales & Modelos de Éxito</option>
                        <option value="general">🏢 Información General de la Empresa</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="font-size:12px;font-weight:700;display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                    <span>Texto que lee Google Gemini & las Skills Comerciales:</span>
                    <span style="font-size:11px;color:var(--fg-secondary)" id="edit_doc_chars"></span>
                </label>
                <textarea name="texto_extraido" id="edit_doc_texto" rows="14" required style="width:100%;font-size:12px;padding:12px;border-radius:6px;border:1px solid #cbd5e1;font-family:monospace;line-height:1.45;background:#f8fafc"></textarea>
                <p style="font-size:11px;color:var(--fg-secondary);margin:4px 0 0 0">
                    Puedes corregir, agregar o pulir datos directamente aquí. Este es el texto exacto que se le suministra a la IA cuando genera correos y mensajes de WhatsApp.
                </p>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e2e8f0;padding-top:12px">
                <span id="ver_doc_enlace_descarga" style="font-size:12px"></span>
                <div style="display:flex;gap:10px">
                    <button type="button" onclick="document.getElementById('modalVerDocIA').style.display='none'" class="btn btn-secondary btn-sm">Cerrar</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="background:#2c60a4;font-weight:800;padding:8px 20px">
                        💾 Guardar Cambios en el Texto
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function actualizarModelos() {
    var prov = document.getElementById('ai_provider').value;
    var modelSelect = document.getElementById('ai_model');
    modelSelect.innerHTML = '';
    
    if (prov === 'gemini') {
        modelSelect.innerHTML += '<option value="gemini-3.8-flash">Gemini 3.8 Flash (Recomendado - Nueva Generación)</option>';
        modelSelect.innerHTML += '<option value="gemini-2.5-flash">Gemini 2.5 Flash</option>';
        modelSelect.innerHTML += '<option value="gemini-1.5-flash">Gemini 1.5 Flash</option>';
    } else {
        modelSelect.innerHTML += '<option value="gpt-4o-mini">GPT-4o Mini (Recomendado por precio/velocidad)</option>';
        modelSelect.innerHTML += '<option value="gpt-4o">GPT-4o</option>';
    }
}

function pegarApiKeyDesdeClipboard(inputId) {
    var input = document.getElementById(inputId);
    if (!navigator.clipboard || !navigator.clipboard.readText) {
        var manual = prompt('Pega aquí tu API Key de Google Gemini:');
        if (manual && manual.trim()) {
            input.value = manual.trim();
            input.type = 'text';
        }
        return;
    }
    navigator.clipboard.readText()
        .then(function(text) {
            text = (text || '').trim();
            if (text) {
                input.value = text;
                input.type = 'text';
                input.focus();
                input.style.borderColor = '#059669';
                input.style.boxShadow = '0 0 0 3px rgba(5,150,105,0.25)';
                setTimeout(function() {
                    input.style.borderColor = '';
                    input.style.boxShadow = '';
                }, 1800);
            } else {
                alert('El portapapeles está vacío. Por favor copia primero la API Key desde Google AI Studio.');
            }
        })
        .catch(function() {
            var manual = prompt('Por favor pega aquí la API Key copiada de Google AI Studio:');
            if (manual && manual.trim()) {
                input.value = manual.trim();
                input.type = 'text';
            }
        });
}

function toggleVisibilidadPassword(inputId, btnId) {
    var input = document.getElementById(inputId);
    var btn = btnId ? document.getElementById(btnId) : null;
    if (input.type === 'password') {
        input.type = 'text';
        if (btn) btn.innerText = '🙈 Ocultar';
    } else {
        input.type = 'password';
        if (btn) btn.innerText = '👁️ Mostrar';
    }
}

function conectarGeminiAJAX(inputId, statusId) {
    var input = document.getElementById(inputId);
    var key = input ? input.value.trim() : '';
    var provSelect = document.getElementById('ai_provider');
    var modelSelect = document.getElementById('ai_model');
    var provider = provSelect ? provSelect.value : 'gemini';
    var model = modelSelect ? modelSelect.value : 'gemini-3.8-flash';
    if (provider === 'gemini' && (model === 'gemini-2.0-flash' || !model)) {
        model = 'gemini-3.8-flash';
    }
    var statusDiv = document.getElementById(statusId);
    var btn = document.getElementById('btn-conectar-ia');

    if (btn) btn.disabled = true;
    statusDiv.style.display = 'block';
    statusDiv.className = 'alert alert-info';
    statusDiv.style.background = '#eff6ff';
    statusDiv.style.color = '#1e40af';
    statusDiv.style.border = '1px solid #bfdbfe';
    statusDiv.style.padding = '12px 14px';
    statusDiv.style.borderRadius = '6px';
    statusDiv.innerText = '⏳ Verificando API Key en tiempo real con los servidores de Google Gemini...';

    fetch('api.php?action=guardar_ai_key', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            api_key: key,
            provider: provider,
            model: model
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (btn) btn.disabled = false;
        if (res.ok && !res.warning) {
            statusDiv.className = 'alert alert-success';
            statusDiv.style.background = '#ecfdf5';
            statusDiv.style.color = '#065f46';
            statusDiv.style.border = '1px solid #a7f3d0';
            statusDiv.innerHTML = '✅ <strong>¡Conectado exitosamente!</strong> ' + res.mensaje;
            setTimeout(function() {
                location.reload();
            }, 1800);
        } else if (res.warning) {
            statusDiv.className = 'alert alert-warning';
            statusDiv.style.background = '#fffbeb';
            statusDiv.style.color = '#92400e';
            statusDiv.style.border = '1px solid #fde68a';
            statusDiv.innerHTML = '⚠️ <strong>Aviso:</strong> ' + res.mensaje;
        } else {
            statusDiv.className = 'alert alert-danger';
            statusDiv.style.background = '#fef2f2';
            statusDiv.style.color = '#991b1b';
            statusDiv.style.border = '1px solid #fecaca';
            statusDiv.innerHTML = '❌ <strong>Error:</strong> ' + (res.error || res.mensaje || 'No se pudo conectar.');
        }
    })
    .catch(function(err) {
        if (btn) btn.disabled = false;
        statusDiv.className = 'alert alert-danger';
        statusDiv.style.background = '#fef2f2';
        statusDiv.style.color = '#991b1b';
        statusDiv.style.border = '1px solid #fecaca';
        statusDiv.innerHTML = '❌ <strong>Error de conexión:</strong> ' + err.message;
    });
}

function probarConexionIA() {
    var btn = document.getElementById('btn-probar-ia');
    var resDiv = document.getElementById('res-test-ia');
    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Probando...';
    }
    resDiv.style.display = 'block';
    resDiv.className = 'alert alert-info';
    resDiv.innerText = 'Enviando prueba de conexión con la IA...';

    fetch('api.php?action=generar_ia', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            canal: 'whatsapp',
            objetivo: 'seguimiento_feria',
            instrucciones: 'Prueba de diagnóstico de conexión',
            prospecto_simulado: {
                nombre: 'Cliente de Prueba',
                empresa: 'Industria Piloto',
                fuente: 'Diagnóstico'
            }
        })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = '⚡ Probar Conexión';
        if (data.ok) {
            resDiv.className = 'alert alert-success';
            resDiv.innerHTML = '<strong>✅ ¡Conexión exitosa!</strong> Respondido por: <em>' + (data.origen || 'IA Power Pack') + '</em>';
        } else {
            resDiv.className = 'alert alert-danger';
            resDiv.innerText = '⚠️ Error: ' + (data.error || 'No se pudo conectar.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = '⚡ Probar Conexión';
        resDiv.className = 'alert alert-danger';
        resDiv.innerText = 'Error de red al consultar la API: ' + err.message;
    });
}

function ejecutarSimulacionIA() {
    var btn = document.getElementById('btn-simular-ia');
    var canal = document.getElementById('sim_canal').value;
    var objetivo = document.getElementById('sim_objetivo').value;
    var skill = document.getElementById('sim_skill') ? document.getElementById('sim_skill').value : '';
    var inst = document.getElementById('sim_instrucciones').value;
    var box = document.getElementById('sim_resultado_box');
    var tag = document.getElementById('sim_origen_tag');
    var asuntoBox = document.getElementById('sim_asunto_box');
    var msgBox = document.getElementById('sim_mensaje_box');

    btn.disabled = true;
    btn.innerText = '⚡ Generando con Skill B2B...';

    fetch('api.php?action=generar_ia', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            canal: canal,
            objetivo: objetivo,
            skill: skill,
            instrucciones: inst,
            prospecto_simulado: {
                nombre: 'María Camila Ruiz',
                empresa: 'Lácteos & Quesos Andinos',
                ciudad: 'Medellín',
                fuente: 'Feria Andina Pack',
                notas: inst
            }
        })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = '⚡ Generar Mensaje Asistido';
        box.style.display = 'block';
        tag.innerText = 'Generado con: ' + (data.origen || 'Power Pack AI');

        if (canal === 'email' && data.asunto) {
            asuntoBox.style.display = 'block';
            asuntoBox.innerHTML = '<strong>Asunto:</strong> ' + data.asunto;
            msgBox.innerHTML = data.mensaje;
        } else {
            asuntoBox.style.display = 'none';
            msgBox.innerText = data.mensaje;
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = '⚡ Generar Mensaje Asistido';
        alert('Error al simular: ' + err.message);
    });
}

function probarSkillDirecto(canal, codigo) {
    if (document.getElementById('sim_canal')) {
        document.getElementById('sim_canal').value = canal;
    }
    if (document.getElementById('sim_skill')) {
        document.getElementById('sim_skill').value = codigo;
    }
    // Auto-ajustar objetivo según skill
    if (document.getElementById('sim_objetivo')) {
        if (codigo === 'aida_feria') document.getElementById('sim_objetivo').value = 'seguimiento_feria';
        if (codigo === 'reactivacion_fria') document.getElementById('sim_objetivo').value = 'reactivacion';
        if (codigo === 'invitacion_showroom') document.getElementById('sim_objetivo').value = 'agendar_visita';
    }
    window.scrollTo({ top: 350, behavior: 'smooth' });
    ejecutarSimulacionIA();
}

function copiarSimulacion() {
    var msg = document.getElementById('sim_mensaje_box').innerText;
    navigator.clipboard.writeText(msg).then(() => {
        alert('¡Mensaje copiado al portapapeles!');
    });
}

// FUNCIONES PARA GESTIÓN DE DOCUMENTOS Y ARCHIVOS DE CONTEXTO IA
function abrirModalSubirDoc() {
    cambiarModoSubida('archivo');
    var tit = document.getElementById('doc_titulo');
    var arch = document.getElementById('archivo_input');
    var txt = document.getElementById('texto_directo_input');
    if (tit) tit.value = '';
    if (arch) arch.value = '';
    if (txt) txt.value = '';
    document.getElementById('modalSubirDocIA').style.display = 'flex';
}

function cambiarModoSubida(modo) {
    var inputModo = document.getElementById('modo_subida_input');
    var btnArch = document.getElementById('tab-btn-archivo');
    var btnText = document.getElementById('tab-btn-texto');
    var panArch = document.getElementById('panel-modo-archivo');
    var panText = document.getElementById('panel-modo-texto');
    var fileInput = document.getElementById('archivo_input');
    var textInput = document.getElementById('texto_directo_input');

    if (!inputModo || !btnArch || !btnText) return;

    inputModo.value = modo;

    if (modo === 'archivo') {
        btnArch.style.background = '#fff';
        btnArch.style.color = '#0f172a';
        btnArch.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
        btnArch.style.fontWeight = '700';

        btnText.style.background = 'transparent';
        btnText.style.color = '#64748b';
        btnText.style.boxShadow = 'none';
        btnText.style.fontWeight = '600';

        panArch.style.display = 'block';
        panText.style.display = 'none';
        if (fileInput) fileInput.required = true;
        if (textInput) textInput.required = false;
    } else {
        btnText.style.background = '#fff';
        btnText.style.color = '#0f172a';
        btnText.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
        btnText.style.fontWeight = '700';

        btnArch.style.background = 'transparent';
        btnArch.style.color = '#64748b';
        btnArch.style.boxShadow = 'none';
        btnArch.style.fontWeight = '600';

        panArch.style.display = 'none';
        panText.style.display = 'block';
        if (fileInput) fileInput.required = false;
        if (textInput) textInput.required = true;
    }
}

function autoCompletarTitulo(input) {
    if (input.files && input.files[0]) {
        var tituloInput = document.getElementById('doc_titulo');
        if (tituloInput && !tituloInput.value.trim()) {
            var fileName = input.files[0].name.replace(/\.[^/.]+$/, "");
            tituloInput.value = fileName;
        }
    }
}

function verDocumentoIA(doc) {
    if (!doc) return;
    document.getElementById('edit_doc_id').value = doc.id;
    document.getElementById('edit_doc_titulo').value = doc.titulo || '';
    document.getElementById('edit_doc_categoria').value = doc.categoria || 'general';
    document.getElementById('edit_doc_texto').value = doc.texto_extraido || '';
    
    var chars = (doc.texto_extraido || '').length;
    var charsSpan = document.getElementById('edit_doc_chars');
    if (charsSpan) {
        charsSpan.innerText = chars.toLocaleString() + ' caracteres extraídos';
    }
    
    var titleH = document.getElementById('ver_doc_header_title');
    if (titleH) titleH.innerText = '👁️ ' + (doc.titulo || 'Documento');
    
    var subH = document.getElementById('ver_doc_header_sub');
    if (subH) {
        subH.innerText = 'Archivo original: ' + (doc.nombre_archivo || 'Texto directo') + ' • Subido: ' + (doc.fecha_subida || '');
    }
    
    var downloadSpan = document.getElementById('ver_doc_enlace_descarga');
    if (downloadSpan) {
        if (doc.ruta_archivo) {
            downloadSpan.innerHTML = '<a href="' + encodeURI(doc.ruta_archivo) + '" target="_blank" style="color:#2563eb;text-decoration:underline;font-weight:600">📥 Descargar archivo original (' + (doc.nombre_archivo || 'archivo') + ') ↗</a>';
        } else {
            downloadSpan.innerHTML = '<span style="color:#64748b">Texto incorporado directamente</span>';
        }
    }

    document.getElementById('modalVerDocIA').style.display = 'flex';
}
</script>
