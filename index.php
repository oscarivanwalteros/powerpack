<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

$page = $_GET['page'] ?? 'dashboard';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Configuración general precargada
$config = [
    'empresa_nombre'   => get_config($db, 'empresa_nombre', 'Powerpack Solutions SAS'),
    'empresa_nit'      => get_config($db, 'empresa_nit', '901.452.889-1'),
    'empresa_telefono' => get_config($db, 'empresa_telefono', '+57 300 467 0474'),
    'empresa_email'    => get_config($db, 'empresa_email', 'ventas@powerpack.com.co'),
    'empresa_direccion'=> get_config($db, 'empresa_direccion', 'Av. El Dorado #98-20, Parque Industrial Bogotá'),
    'smtp_host'        => get_config($db, 'smtp_host', 'smtp.hostinger.com'),
    'smtp_port'        => get_config($db, 'smtp_port', '465'),
    'smtp_secure'      => get_config($db, 'smtp_secure', 'ssl'),
    'smtp_user'        => get_config($db, 'smtp_user', ''),
    'smtp_pass'        => get_config($db, 'smtp_pass', ''),
    'smtp_from'        => get_config($db, 'smtp_from', ''),
    'banco_info'       => get_config($db, 'banco_info', 'Bancolombia Cta Corriente # 104-582910-44'),
];

// Contador de tareas pendientes para el sidebar
$tareas_pendientes_count = (int)$db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'tarea' AND completada = 0");

// Exportar contactos a CSV compatible con Excel
if ($page === 'exportar_contactos') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="contactos_powerpack_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM para Excel
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Nombre', 'Apellido', 'Email', 'Teléfono', 'Empresa', 'Cargo', 'Ciudad', 'Sector', 'Etapa', 'Interés', 'Última Actividad']);
    $res = $db->query("SELECT id, nombre, apellido, email, telefono, empresa, cargo, ciudad, sector, etapa, interes, ultima_actividad FROM contactos ORDER BY id DESC");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// Procesar formularios POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Guardar o Editar Contacto
    if (isset($_POST['guardar_contacto'])) {
        $cid = isset($_POST['contacto_id']) ? (int)$_POST['contacto_id'] : 0;
        $empresa_id = !empty($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $empresa = trim($_POST['empresa'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $ciudad = trim($_POST['ciudad'] ?? '');
        $sector = trim($_POST['sector'] ?? '');
        $fuente = trim($_POST['fuente'] ?? 'web');
        $etapa = trim($_POST['etapa'] ?? 'lead');
        $interes = (int)($_POST['interes'] ?? 1);
        $notas = trim($_POST['notas'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if ($cid > 0) {
            $stmt = $db->prepare("UPDATE contactos SET empresa_id=?, nombre=?, apellido=?, email=?, telefono=?, empresa=?, cargo=?, ciudad=?, sector=?, fuente=?, etapa=?, interes=?, notas=?, website=?, direccion=?, ultima_actividad=datetime('now') WHERE id=?");
            $stmt->bindValue(1, $empresa_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $nombre, SQLITE3_TEXT);
            $stmt->bindValue(3, $apellido, SQLITE3_TEXT);
            $stmt->bindValue(4, $email, SQLITE3_TEXT);
            $stmt->bindValue(5, $telefono, SQLITE3_TEXT);
            $stmt->bindValue(6, $empresa, SQLITE3_TEXT);
            $stmt->bindValue(7, $cargo, SQLITE3_TEXT);
            $stmt->bindValue(8, $ciudad, SQLITE3_TEXT);
            $stmt->bindValue(9, $sector, SQLITE3_TEXT);
            $stmt->bindValue(10, $fuente, SQLITE3_TEXT);
            $stmt->bindValue(11, $etapa, SQLITE3_TEXT);
            $stmt->bindValue(12, $interes, SQLITE3_INTEGER);
            $stmt->bindValue(13, $notas, SQLITE3_TEXT);
            $stmt->bindValue(14, $website, SQLITE3_TEXT);
            $stmt->bindValue(15, $direccion, SQLITE3_TEXT);
            $stmt->bindValue(16, $cid, SQLITE3_INTEGER);
            $stmt->execute();
            header("Location: index.php?page=detalle&id=$cid&msg=contacto_actualizado");
            exit;
        } else {
            $stmt = $db->prepare("INSERT INTO contactos (empresa_id, nombre, apellido, email, telefono, empresa, cargo, ciudad, sector, fuente, etapa, interes, notas, website, direccion) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bindValue(1, $empresa_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $nombre, SQLITE3_TEXT);
            $stmt->bindValue(3, $apellido, SQLITE3_TEXT);
            $stmt->bindValue(4, $email, SQLITE3_TEXT);
            $stmt->bindValue(5, $telefono, SQLITE3_TEXT);
            $stmt->bindValue(6, $empresa, SQLITE3_TEXT);
            $stmt->bindValue(7, $cargo, SQLITE3_TEXT);
            $stmt->bindValue(8, $ciudad, SQLITE3_TEXT);
            $stmt->bindValue(9, $sector, SQLITE3_TEXT);
            $stmt->bindValue(10, $fuente, SQLITE3_TEXT);
            $stmt->bindValue(11, $etapa, SQLITE3_TEXT);
            $stmt->bindValue(12, $interes, SQLITE3_INTEGER);
            $stmt->bindValue(13, $notas, SQLITE3_TEXT);
            $stmt->bindValue(14, $website, SQLITE3_TEXT);
            $stmt->bindValue(15, $direccion, SQLITE3_TEXT);
            $stmt->execute();
            $new_id = $db->lastInsertRowID();
            $db->exec("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado) VALUES ($new_id, 'nota', 'Contacto registrado en CRM', 'Se añadió el contacto desde el formulario.', 'creado')");
            header("Location: index.php?page=detalle&id=$new_id&msg=contacto_creado");
            exit;
        }
    }

    // 2. Eliminar Contacto
    if (isset($_POST['eliminar_contacto']) && $id) {
        $db->exec("DELETE FROM actividades WHERE contacto_id = $id");
        $db->exec("DELETE FROM negocios WHERE contacto_id = $id");
        $db->exec("DELETE FROM cotizaciones WHERE contacto_id = $id");
        $db->exec("DELETE FROM contactos WHERE id = $id");
        header("Location: index.php?page=contactos&msg=contacto_eliminado");
        exit;
    }

    // 3. Guardar Empresa B2B
    if (isset($_POST['guardar_empresa'])) {
        $nombre = trim($_POST['nombre'] ?? '');
        $nit = trim($_POST['nit'] ?? '');
        $sector = trim($_POST['sector'] ?? 'industrial');
        $ciudad = trim($_POST['ciudad'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');

        if ($nombre) {
            $stmt = $db->prepare("INSERT INTO empresas (nombre, nit, sector, ciudad, direccion, telefono, email, website) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bindValue(1, $nombre, SQLITE3_TEXT);
            $stmt->bindValue(2, $nit, SQLITE3_TEXT);
            $stmt->bindValue(3, $sector, SQLITE3_TEXT);
            $stmt->bindValue(4, $ciudad, SQLITE3_TEXT);
            $stmt->bindValue(5, $direccion, SQLITE3_TEXT);
            $stmt->bindValue(6, $telefono, SQLITE3_TEXT);
            $stmt->bindValue(7, $email, SQLITE3_TEXT);
            $stmt->bindValue(8, $website, SQLITE3_TEXT);
            $stmt->execute();
            $new_emp_id = $db->lastInsertRowID();
            header("Location: index.php?page=empresa_detalle&id=$new_emp_id&msg=empresa_creada");
            exit;
        }
    }

    // 4. Guardar Cotización Formal
    if (isset($_POST['guardar_cotizacion'])) {
        $numero = trim($_POST['numero'] ?? '');
        $contacto_id = (int)($_POST['contacto_id'] ?? 0);
        $fecha = trim($_POST['fecha'] ?? date('Y-m-d'));
        $validez_dias = (int)($_POST['validez_dias'] ?? 15);
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $iva_porcentaje = (float)($_POST['iva_porcentaje'] ?? 19);
        $iva_monto = (float)($_POST['iva_monto'] ?? 0);
        $total = (float)($_POST['total'] ?? 0);
        $condiciones = trim($_POST['condiciones'] ?? '');
        $tiempo_entrega = trim($_POST['tiempo_entrega'] ?? '');
        $garantia = trim($_POST['garantia'] ?? '');
        $items = $_POST['items'] ?? [];

        // Obtener empresa_id del contacto
        $empresa_id = (int)$db->querySingle("SELECT empresa_id FROM contactos WHERE id = $contacto_id");

        $stmt = $db->prepare("INSERT INTO cotizaciones (numero, contacto_id, empresa_id, fecha, validez_dias, subtotal, iva_porcentaje, iva_monto, total, condiciones, tiempo_entrega, garantia, estado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'enviada')");
        $stmt->bindValue(1, $numero, SQLITE3_TEXT);
        $stmt->bindValue(2, $contacto_id ?: null, SQLITE3_INTEGER);
        $stmt->bindValue(3, $empresa_id ?: null, SQLITE3_INTEGER);
        $stmt->bindValue(4, $fecha, SQLITE3_TEXT);
        $stmt->bindValue(5, $validez_dias, SQLITE3_INTEGER);
        $stmt->bindValue(6, $subtotal, SQLITE3_FLOAT);
        $stmt->bindValue(7, $iva_porcentaje, SQLITE3_FLOAT);
        $stmt->bindValue(8, $iva_monto, SQLITE3_FLOAT);
        $stmt->bindValue(9, $total, SQLITE3_FLOAT);
        $stmt->bindValue(10, $condiciones, SQLITE3_TEXT);
        $stmt->bindValue(11, $tiempo_entrega, SQLITE3_TEXT);
        $stmt->bindValue(12, $garantia, SQLITE3_TEXT);
        $stmt->execute();
        $cot_id = $db->lastInsertRowID();

        // Insertar items de la cotización
        foreach ($items as $it) {
            $desc = trim($it['descripcion'] ?? '');
            $cant = (float)($it['cantidad'] ?? 1);
            $precio = (float)($it['precio'] ?? 0);
            $item_sub = $cant * $precio;
            if ($desc) {
                $stmt_it = $db->prepare("INSERT INTO cotizacion_items (cotizacion_id, descripcion, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?)");
                $stmt_it->bindValue(1, $cot_id, SQLITE3_INTEGER);
                $stmt_it->bindValue(2, $desc, SQLITE3_TEXT);
                $stmt_it->bindValue(3, $cant, SQLITE3_FLOAT);
                $stmt_it->bindValue(4, $precio, SQLITE3_FLOAT);
                $stmt_it->bindValue(5, $item_sub, SQLITE3_FLOAT);
                $stmt_it->execute();
            }
        }

        // Registrar en actividades del contacto
        if ($contacto_id > 0) {
            $stmt_act = $db->prepare("INSERT INTO actividades (contacto_id, empresa_id, tipo, asunto, descripcion, resultado, completada) VALUES (?, ?, 'cotizacion', ?, ?, 'emitida', 1)");
            $stmt_act->bindValue(1, $contacto_id, SQLITE3_INTEGER);
            $stmt_act->bindValue(2, $empresa_id ?: null, SQLITE3_INTEGER);
            $stmt_act->bindValue(3, "Cotización formal emitida: $numero", SQLITE3_TEXT);
            $stmt_act->bindValue(4, "Propuesta formal por un valor total de $" . number_format($total, 0) . " con $validez_dias días de validez.", SQLITE3_TEXT);
            $stmt_act->execute();

            $db->exec("UPDATE contactos SET etapa = 'cotizacion', ultima_actividad = datetime('now') WHERE id = $contacto_id");
        }

        header("Location: index.php?page=ver_cotizacion&id=$cot_id&msg=cotizacion_creada");
        exit;
    }

    // 5. Cambiar Estado de Cotización
    if (isset($_POST['cambiar_estado_cotizacion'])) {
        $cot_id = (int)$_POST['cotizacion_id'];
        $nuevo_estado = trim($_POST['nuevo_estado'] ?? 'borrador');
        $stmt = $db->prepare("UPDATE cotizaciones SET estado = ? WHERE id = ?");
        $stmt->bindValue(1, $nuevo_estado, SQLITE3_TEXT);
        $stmt->bindValue(2, $cot_id, SQLITE3_INTEGER);
        $stmt->execute();

        // Si fue aprobada, actualizar contacto a "ganado"
        if ($nuevo_estado === 'aprobada') {
            $cid = (int)$db->querySingle("SELECT contacto_id FROM cotizaciones WHERE id = $cot_id");
            if ($cid > 0) {
                $db->exec("UPDATE contactos SET etapa = 'ganado', ultima_actividad = datetime('now') WHERE id = $cid");
            }
        }
        header("Location: index.php?page=ver_cotizacion&id=$cot_id&msg=estado_actualizado");
        exit;
    }

    // 6. Subir Archivo Adjunto (Fichas técnicas, RUT)
    if (isset($_POST['subir_archivo']) && isset($_FILES['archivo'])) {
        $file = $_FILES['archivo'];
        $emp_id = !empty($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
        $cid = !empty($_POST['contacto_id']) ? (int)$_POST['contacto_id'] : null;

        if ($file['error'] === UPLOAD_ERR_OK) {
            $nombre_orig = basename($file['name']);
            $ext = strtolower(pathinfo($nombre_orig, PATHINFO_EXTENSION));
            $permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'txt', 'csv'];

            if (in_array($ext, $permitidos)) {
                $nombre_seguro = uniqid('doc_') . '.' . $ext;
                $destino = __DIR__ . '/uploads/' . $nombre_seguro;

                if (move_uploaded_file($file['tmp_name'], $destino)) {
                    $stmt = $db->prepare("INSERT INTO archivos (contacto_id, empresa_id, nombre_original, ruta, peso, mime) VALUES (?,?,?,?,?,?)");
                    $stmt->bindValue(1, $cid, SQLITE3_INTEGER);
                    $stmt->bindValue(2, $emp_id, SQLITE3_INTEGER);
                    $stmt->bindValue(3, $nombre_orig, SQLITE3_TEXT);
                    $stmt->bindValue(4, $nombre_seguro, SQLITE3_TEXT);
                    $stmt->bindValue(5, $file['size'], SQLITE3_INTEGER);
                    $stmt->bindValue(6, $file['type'], SQLITE3_TEXT);
                    $stmt->execute();
                }
            }
        }
        if ($emp_id) header("Location: index.php?page=empresa_detalle&id=$emp_id&msg=archivo_subido");
        elseif ($cid) header("Location: index.php?page=detalle&id=$cid&msg=archivo_subido");
        else header("Location: index.php?msg=archivo_subido");
        exit;
    }

    // 7. Registrar y Enviar WhatsApp
    if (isset($_POST['enviar_whatsapp']) && $id) {
        $tel = trim($_POST['telefono'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');
        $abrir = isset($_POST['abrir_whatsapp']);

        $clean_tel = limpiar_telefono_whatsapp($tel);

        $stmt = $db->prepare("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado, completada) VALUES (?, 'whatsapp', ?, ?, 'enviado', 1)");
        $asunto_wa = 'WhatsApp a +' . $clean_tel;
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $asunto_wa, SQLITE3_TEXT);
        $stmt->bindValue(3, $mensaje, SQLITE3_TEXT);
        $stmt->execute();

        $db->exec("UPDATE contactos SET ultima_actividad = datetime('now') WHERE id = $id");

        if ($abrir && !empty($clean_tel)) {
            $wa_url = "https://wa.me/{$clean_tel}?text=" . rawurlencode($mensaje);
            header("Location: index.php?page=detalle&id=$id&wa_open=" . urlencode($wa_url) . "&msg=whatsapp_registrado");
            exit;
        }

        header("Location: index.php?page=detalle&id=$id&msg=whatsapp_registrado");
        exit;
    }

    // 8. Enviar Correo (vía SMTP con fallback)
    if (isset($_POST['enviar_email']) && $id) {
        $destinatario = trim($_POST['destinatario'] ?? '');
        $asunto = trim($_POST['asunto'] ?? 'Información de Powerpack');
        $cuerpo = trim($_POST['cuerpo'] ?? '');

        if ($destinatario && $asunto && $cuerpo) {
            $resultado = enviar_correo_smtp($destinatario, $asunto, nl2br($cuerpo), $config);
            $estado = $resultado['ok'] ? 'enviado' : 'error_smtp';
            $error_detalle = $resultado['ok'] ? '' : ($resultado['error'] ?? '');

            $stmt = $db->prepare("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado, completada) VALUES (?, 'email', ?, ?, ?, 1)");
            $asunto_guardar = "Email: " . $asunto;
            $desc_guardar = "Para: $destinatario\n\n" . $cuerpo . ($error_detalle ? "\n\n[Detalle: $error_detalle]" : "");
            $stmt->bindValue(1, $id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $asunto_guardar, SQLITE3_TEXT);
            $stmt->bindValue(3, $desc_guardar, SQLITE3_TEXT);
            $stmt->bindValue(4, $estado, SQLITE3_TEXT);
            $stmt->execute();

            $db->exec("UPDATE contactos SET ultima_actividad = datetime('now') WHERE id = $id");

            if ($resultado['ok']) {
                header("Location: index.php?page=detalle&id=$id&msg=email_enviado");
            } else {
                header("Location: index.php?page=detalle&id=$id&err=" . urlencode("Email registrado en CRM pero falló el envío SMTP: " . $resultado['error']));
            }
            exit;
        }
    }

    // 9. Crear Tarea / Recordatorio Comercial
    if (isset($_POST['nueva_tarea'])) {
        $cid = (int)($_POST['contacto_id'] ?? 0);
        $asunto = trim($_POST['asunto'] ?? 'Tarea comercial');
        $desc = trim($_POST['descripcion'] ?? '');
        $fecha_venc = trim($_POST['fecha_vencimiento'] ?? date('Y-m-d H:i:s'));

        $stmt = $db->prepare("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, fecha_vencimiento, completada, resultado) VALUES (?, 'tarea', ?, ?, ?, 0, 'pendiente')");
        $stmt->bindValue(1, $cid ?: null, SQLITE3_INTEGER);
        $stmt->bindValue(2, $asunto, SQLITE3_TEXT);
        $stmt->bindValue(3, $desc, SQLITE3_TEXT);
        $stmt->bindValue(4, $fecha_venc, SQLITE3_TEXT);
        $stmt->execute();

        if ($cid > 0) {
            $db->exec("UPDATE contactos SET ultima_actividad = datetime('now') WHERE id = $cid");
            header("Location: index.php?page=detalle&id=$cid&msg=tarea_creada");
        } else {
            header("Location: index.php?page=tareas&msg=tarea_creada");
        }
        exit;
    }

    // 10. Completar / Reabrir Tarea
    if (isset($_POST['toggle_tarea'])) {
        $tid = (int)$_POST['tarea_id'];
        $estado = (int)$_POST['nuevo_estado'];
        $stmt = $db->prepare("UPDATE actividades SET completada = ?, resultado = ? WHERE id = ? AND tipo = 'tarea'");
        $stmt->bindValue(1, $estado, SQLITE3_INTEGER);
        $stmt->bindValue(2, $estado ? 'completada' : 'pendiente', SQLITE3_TEXT);
        $stmt->bindValue(3, $tid, SQLITE3_INTEGER);
        $stmt->execute();

        $return_url = $_POST['return_url'] ?? 'index.php?page=tareas';
        header("Location: " . $return_url);
        exit;
    }

    // 11. Guardar Negocio
    if (isset($_POST['guardar_negocio'])) {
        $nid = (int)($_POST['negocio_id'] ?? 0);
        $contacto_id = (int)($_POST['contacto_id'] ?: 0);
        $empresa_id = (int)($_POST['empresa_id'] ?: 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $monto = (float)($_POST['monto'] ?? 0);
        $etapa = trim($_POST['etapa'] ?? 'contacto_inicial');
        $fecha_cierre = trim($_POST['fecha_cierre'] ?? date('Y-m-d'));
        $probabilidad = (int)($_POST['probabilidad'] ?? 20);
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($nid > 0) {
            $stmt = $db->prepare("UPDATE negocios SET contacto_id=?, empresa_id=?, nombre=?, monto=?, etapa=?, fecha_cierre=?, probabilidad=?, descripcion=?, ultima_actividad=datetime('now') WHERE id=?");
            $stmt->bindValue(1, $contacto_id ?: null, SQLITE3_INTEGER);
            $stmt->bindValue(2, $empresa_id ?: null, SQLITE3_INTEGER);
            $stmt->bindValue(3, $nombre, SQLITE3_TEXT);
            $stmt->bindValue(4, $monto, SQLITE3_FLOAT);
            $stmt->bindValue(5, $etapa, SQLITE3_TEXT);
            $stmt->bindValue(6, $fecha_cierre, SQLITE3_TEXT);
            $stmt->bindValue(7, $probabilidad, SQLITE3_INTEGER);
            $stmt->bindValue(8, $descripcion, SQLITE3_TEXT);
            $stmt->bindValue(9, $nid, SQLITE3_INTEGER);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO negocios (contacto_id, empresa_id, nombre, monto, etapa, fecha_cierre, probabilidad, descripcion, ultima_actividad) VALUES (?,?,?,?,?,?,?,?,datetime('now'))");
            $stmt->bindValue(1, $contacto_id ?: null, SQLITE3_INTEGER);
            $stmt->bindValue(2, $empresa_id ?: null, SQLITE3_INTEGER);
            $stmt->bindValue(3, $nombre, SQLITE3_TEXT);
            $stmt->bindValue(4, $monto, SQLITE3_FLOAT);
            $stmt->bindValue(5, $etapa, SQLITE3_TEXT);
            $stmt->bindValue(6, $fecha_cierre, SQLITE3_TEXT);
            $stmt->bindValue(7, $probabilidad, SQLITE3_INTEGER);
            $stmt->bindValue(8, $descripcion, SQLITE3_TEXT);
            $stmt->execute();
        }
        header("Location: index.php?page=pipeline&msg=negocio_guardado");
        exit;
    }

    // 12. Guardar Configuración
    if (isset($_POST['guardar_configuracion'])) {
        foreach (['empresa_nombre', 'empresa_nit', 'empresa_telefono', 'empresa_email', 'empresa_direccion', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass', 'smtp_from', 'banco_info'] as $key) {
            if (isset($_POST[$key])) {
                set_config($db, $key, trim($_POST[$key]));
            }
        }
        header("Location: index.php?page=configuracion&msg=config_guardada");
        exit;
    }

    // 13. Probar SMTP
    if (isset($_POST['probar_smtp'])) {
        $test_to = trim($_POST['test_email'] ?? '');
        if ($test_to) {
            $res = enviar_correo_smtp($test_to, "Prueba de conexión SMTP - Powerpack CRM", "<h2>¡Conexión SMTP exitosa!</h2><p>Tu CRM Powerpack está listo para enviar correos desde Hostinger con alta entregabilidad.</p>", $config);
            if ($res['ok']) {
                header("Location: index.php?page=configuracion&msg=smtp_ok");
            } else {
                header("Location: index.php?page=configuracion&err=" . urlencode("Error al conectar con SMTP: " . $res['error']));
            }
            exit;
        }
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Powerpack CRM Pro | Suite Comercial</title>
    <meta name="theme-color" content="#f97316">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-w: 240px;
            --topbar-h: 60px;
            --bg: #f8fafc;
            --bg-white: #ffffff;
            --sidebar-bg: #0f172a;
            --sidebar-fg: #94a3b8;
            --accent: #f97316;
            --accent-hover: #ea580c;
            --accent-light: #fff7ed;
            --fg: #0f172a;
            --fg-secondary: #64748b;
            --border: #e2e8f0;
            --card: #ffffff;
            --whatsapp: #25D366;
            --whatsapp-hover: #1ebc59;
            --whatsapp-light: #e7f9ee;
            --email: #2563eb;
            --email-light: #eff6ff;
            --success: #059669;
            --warning: #d97706;
            --danger: #ef4444;
            --radius: 10px;
            --radius-sm: 6px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.06), 0 2px 4px -2px rgba(0,0,0,0.04);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--fg);
            min-height: 100vh;
            display: flex;
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; border: none; }

        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            color: var(--sidebar-fg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
        }
        .sidebar-logo {
            padding: 18px 22px;
            font-weight: 800;
            font-size: 17px;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-logo .brand-badge {
            background: linear-gradient(135deg, var(--accent), #ff9800);
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
        }
        .sidebar-nav {
            padding: 14px 10px;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .sidebar-section {
            padding: 12px 14px 4px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #475569;
            font-weight: 700;
        }
        .sidebar-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
            border-radius: var(--radius-sm);
            transition: all 0.15s ease;
        }
        .sidebar-item:hover {
            background: rgba(255,255,255,0.07);
            color: #fff;
        }
        .sidebar-item.active {
            background: var(--accent);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(249,115,22,0.3);
        }
        .sidebar-badge {
            background: var(--accent);
            color: #fff;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .sidebar-item.active .sidebar-badge { background: #fff; color: var(--accent); }

        .main-wrap {
            flex: 1;
            margin-left: var(--sidebar-w);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .topbar {
            height: var(--topbar-h);
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar-search {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 7px 14px;
            min-width: 320px;
        }
        .topbar-search input { border: none; background: none; outline: none; font-size: 13px; width: 100%; color: var(--fg); }
        .topbar-actions { display: flex; align-items: center; gap: 10px; }

        .content {
            flex: 1;
            padding: 28px;
            max-width: 1440px;
            width: 100%;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .page-header h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.02em; }
        .page-header p { color: var(--fg-secondary); font-size: 13px; margin-top: 2px; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            transition: all 0.15s ease;
        }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-hover); box-shadow: 0 4px 10px rgba(249,115,22,0.25); }
        .btn-secondary { background: #fff; color: var(--fg); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--bg); border-color: #cbd5e1; }
        .btn-whatsapp { background: var(--whatsapp); color: #fff; }
        .btn-whatsapp:hover { background: var(--whatsapp-hover); box-shadow: 0 4px 10px rgba(37,211,102,0.3); }
        .btn-email { background: var(--email); color: #fff; }
        .btn-email:hover { background: #1d4ed8; }
        .btn-danger { background: #fee2e2; color: var(--danger); }
        .btn-danger:hover { background: var(--danger); color: #fff; }
        .btn-sm { padding: 5px 11px; font-size: 12px; }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .metric-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .metric-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
        .metric-card .label { font-size: 12px; color: var(--fg-secondary); font-weight: 600; text-transform: uppercase; }
        .metric-card .value { font-size: 26px; font-weight: 800; margin-top: 6px; }
        .metric-card .sub { font-size: 12px; color: var(--fg-secondary); margin-top: 4px; }

        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-lead { background: #f1f5f9; color: #475569; }
        .badge-contacto_inicial { background: #eff6ff; color: #2563eb; }
        .badge-calificado { background: #f3e8ff; color: #7c3aed; }
        .badge-cotizacion { background: #fffbeb; color: #d97706; }
        .badge-negociacion { background: #fff7ed; color: #ea580c; }
        .badge-ganado { background: #ecfdf5; color: #059669; }
        .badge-perdido { background: #fef2f2; color: #dc2626; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 9px 13px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-family: inherit;
            color: var(--fg);
            background: #fff;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(249,115,22,0.12);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }

        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 13px;
        }
        .alert-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .table-wrap {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
            gap: 14px;
            flex-wrap: wrap;
        }
        .tabs { display: flex; gap: 4px; }
        .tab {
            padding: 7px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            background: transparent;
            color: var(--fg-secondary);
            cursor: pointer;
        }
        .tab:hover { background: var(--bg); color: var(--fg); }
        .tab.active { background: var(--accent-light); color: var(--accent); }

        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 11px 18px;
            font-size: 11px;
            font-weight: 700;
            color: var(--fg-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
        }
        td { padding: 13px 18px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
        tr:hover td { background: #fcfcfd; }
        tr:last-child td { border-bottom: none; }

        .board-scroll { overflow-x: auto; padding-bottom: 14px; }
        .board { display: flex; gap: 16px; min-width: max-content; align-items: flex-start; }
        .board-col {
            background: #f1f5f9;
            border-radius: var(--radius);
            padding: 14px;
            width: 280px;
            min-width: 280px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            border: 1px solid #e2e8f0;
        }
        .board-col-header { display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 13px; }
        .board-card {
            background: #fff;
            border-radius: var(--radius-sm);
            padding: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            cursor: grab;
            transition: all 0.2s;
        }
        .board-card:hover { box-shadow: var(--shadow); border-color: var(--accent); transform: translateY(-2px); }
        .board-card h4 { font-size: 14px; font-weight: 700; margin-bottom: 4px; }
        .board-card .amount { font-size: 16px; font-weight: 800; color: var(--fg); margin: 6px 0; }

        @media(max-width: 960px) {
            .sidebar { display: none; }
            .main-wrap { margin-left: 0; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="brand-badge">⚡</div>
        <div>
            <div>Powerpack</div>
            <div style="font-size:10px;font-weight:500;color:#64748b;letter-spacing:0">CRM Pro Suite</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section">Comercial & Cuentas</div>
        <a href="?page=dashboard" class="sidebar-item <?= $page=='dashboard'?'active':'' ?>">
            <span>📊 Dashboard</span>
        </a>
        <a href="?page=contactos" class="sidebar-item <?= $page=='contactos'?'active':'' ?>">
            <span>👥 Contactos</span>
        </a>
        <a href="?page=empresas" class="sidebar-item <?= in_array($page, ['empresas', 'empresa_detalle'])?'active':'' ?>">
            <span>🏢 Cuentas B2B (Fábricas)</span>
        </a>
        <a href="?page=pipeline" class="sidebar-item <?= $page=='pipeline'?'active':'' ?>">
            <span>📋 Pipeline (Semáforo)</span>
        </a>
        <a href="?page=cotizaciones" class="sidebar-item <?= in_array($page, ['cotizaciones', 'nueva_cotizacion', 'ver_cotizacion'])?'active':'' ?>">
            <span>📄 Cotizaciones (PDF)</span>
        </a>
        <a href="?page=tareas" class="sidebar-item <?= $page=='tareas'?'active':'' ?>">
            <span>✅ Tareas / Seguimientos</span>
            <?php if($tareas_pendientes_count > 0): ?>
            <span class="sidebar-badge"><?= $tareas_pendientes_count ?></span>
            <?php endif; ?>
        </a>

        <div class="sidebar-section" style="margin-top:14px">Inteligencia de Ventas</div>
        <a href="?page=reportes" class="sidebar-item <?= $page=='reportes'?'active':'' ?>">
            <span>📈 Embudo & Reportes</span>
        </a>

        <div class="sidebar-section" style="margin-top:14px">Conexiones & Ajustes</div>
        <a href="?page=configuracion" class="sidebar-item <?= $page=='configuracion'?'active':'' ?>">
            <span>⚙️ WhatsApp, SMTP & Webhook</span>
        </a>
        <a href="?page=exportar_contactos" class="sidebar-item">
            <span>📥 Exportar a Excel</span>
        </a>
    </nav>
</aside>

<div class="main-wrap">
    <header class="topbar">
        <form class="topbar-search" action="index.php" method="get">
            <input type="hidden" name="page" value="contactos">
            <span>🔍</span>
            <input type="text" name="q" placeholder="Buscar por contacto, empresa, email..." value="<?= h($_GET['q'] ?? '') ?>">
        </form>
        <div class="topbar-actions">
            <a href="?page=nueva_cotizacion" class="btn btn-secondary btn-sm">📄 + Cotización</a>
            <a href="?page=nuevo" class="btn btn-primary btn-sm">+ Nuevo Contacto</a>
        </div>
    </header>

    <main class="content">
        <?php if ($msg === 'contacto_creado'): ?><div class="alert alert-success">✅ Contacto creado exitosamente en el CRM.</div><?php endif; ?>
        <?php if ($msg === 'contacto_actualizado'): ?><div class="alert alert-success">✅ Contacto actualizado correctamente.</div><?php endif; ?>
        <?php if ($msg === 'contacto_eliminado'): ?><div class="alert alert-success">🗑️ Contacto eliminado correctamente.</div><?php endif; ?>
        <?php if ($msg === 'empresa_creada'): ?><div class="alert alert-success">🏢 Cuenta / Empresa B2B registrada con éxito.</div><?php endif; ?>
        <?php if ($msg === 'cotizacion_creada'): ?><div class="alert alert-success">📄 Cotización formal generada exitosamente. Lista para imprimir en PDF o compartir por WhatsApp.</div><?php endif; ?>
        <?php if ($msg === 'estado_actualizado'): ?><div class="alert alert-success">✅ Estado de cotización actualizado.</div><?php endif; ?>
        <?php if ($msg === 'archivo_subido'): ?><div class="alert alert-success">📎 Documento adjuntado de forma segura en Hostinger.</div><?php endif; ?>
        <?php if ($msg === 'whatsapp_registrado'): ?><div class="alert alert-success">💬 WhatsApp registrado con éxito en el historial comercial.</div><?php endif; ?>
        <?php if ($msg === 'email_enviado'): ?><div class="alert alert-success">✉️ Correo electrónico enviado vía SMTP y registrado en el historial.</div><?php endif; ?>
        <?php if ($msg === 'tarea_creada'): ?><div class="alert alert-success">✅ Tarea programada en el calendario comercial.</div><?php endif; ?>
        <?php if ($msg === 'config_guardada'): ?><div class="alert alert-success">⚙️ Configuración y credenciales SMTP guardadas.</div><?php endif; ?>
        <?php if ($msg === 'smtp_ok'): ?><div class="alert alert-success">🚀 ¡Conexión SMTP exitosa! El correo de prueba fue enviado.</div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error">❌ <?= h($err) ?></div><?php endif; ?>

        <?php
        // Router de páginas
        switch ($page) {
            case 'dashboard':
                include 'pages/dashboard.php';
                break;
            case 'contactos':
                include 'pages/contactos.php';
                break;
            case 'detalle':
                include 'pages/detalle.php';
                break;
            case 'nuevo':
                include 'pages/nuevo.php';
                break;
            case 'empresas':
                include 'pages/empresas.php';
                break;
            case 'empresa_detalle':
                include 'pages/empresa_detalle.php';
                break;
            case 'pipeline':
                include 'pages/pipeline.php';
                break;
            case 'nuevo_negocio':
                include 'pages/nuevo_negocio.php';
                break;
            case 'cotizaciones':
                include 'pages/cotizaciones.php';
                break;
            case 'nueva_cotizacion':
                include 'pages/nueva_cotizacion.php';
                break;
            case 'ver_cotizacion':
                include 'pages/ver_cotizacion.php';
                break;
            case 'tareas':
                include 'pages/tareas.php';
                break;
            case 'reportes':
                include 'pages/reportes.php';
                break;
            case 'configuracion':
                include 'pages/configuracion.php';
                break;
            default:
                include 'pages/dashboard.php';
        }
        ?>
    </main>
</div>

<?php if (isset($_GET['wa_open'])): ?>
<script>
    window.open(<?= json_encode($_GET['wa_open']) ?>, '_blank');
</script>
<?php endif; ?>

</body>
</html>