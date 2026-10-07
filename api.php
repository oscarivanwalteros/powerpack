<?php
/**
 * api.php - Webhook & API REST para captura automática de prospectos web
 * Conéctalo a formularios de contacto de WordPress, Elementor, HTML o landing pages en Hostinger.
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? '';

// 1. Webhook de Captura de Prospectos desde la Web
if ($action === 'lead_web' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $nombre   = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $email    = trim($input['email'] ?? '');
    $telefono = trim($input['telefono'] ?? '');
    $empresa  = trim($input['empresa'] ?? '');
    $cargo    = trim($input['cargo'] ?? '');
    $sector   = trim($input['sector'] ?? 'industrial');
    $mensaje  = trim($input['mensaje'] ?? ($input['comentarios'] ?? ''));
    $fuente   = trim($input['fuente'] ?? 'web');

    if (empty($nombre) && empty($email) && empty($telefono)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Se requiere al menos nombre, email o teléfono']);
        exit;
    }

    // Verificar si el contacto ya existe por email
    $existente = null;
    if (!empty($email)) {
        $stmt_check = $db->prepare("SELECT id FROM contactos WHERE email = ?");
        $stmt_check->bindValue(1, $email, SQLITE3_TEXT);
        $res = $stmt_check->execute();
        $existente = $res->fetchArray(SQLITE3_ASSOC);
    }

    if ($existente) {
        $cid = (int)$existente['id'];
        // Registrar actividad en el contacto existente
        $stmt_act = $db->prepare("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado, completada) VALUES (?, 'nota', 'Nueva solicitud web / formulario', ?, 'solicitud', 1)");
        $stmt_act->bindValue(1, $cid, SQLITE3_INTEGER);
        $stmt_act->bindValue(2, "Mensaje recibido desde formulario web:\n\n" . $mensaje, SQLITE3_TEXT);
        $stmt_act->execute();
        $db->exec("UPDATE contactos SET ultima_actividad = datetime('now') WHERE id = $cid");
        $creado = false;
    } else {
        $stmt = $db->prepare("INSERT INTO contactos (nombre, apellido, email, telefono, empresa, cargo, sector, fuente, etapa, interes, notas) VALUES (?,?,?,?,?,?,?,?,'lead',3,?)");
        $stmt->bindValue(1, $nombre, SQLITE3_TEXT);
        $stmt->bindValue(2, $apellido, SQLITE3_TEXT);
        $stmt->bindValue(3, $email, SQLITE3_TEXT);
        $stmt->bindValue(4, $telefono, SQLITE3_TEXT);
        $stmt->bindValue(5, $empresa, SQLITE3_TEXT);
        $stmt->bindValue(6, $cargo, SQLITE3_TEXT);
        $stmt->bindValue(7, $sector, SQLITE3_TEXT);
        $stmt->bindValue(8, $fuente, SQLITE3_TEXT);
        $stmt->bindValue(9, "Mensaje web: " . $mensaje, SQLITE3_TEXT);
        $stmt->execute();
        $cid = $db->lastInsertRowID();
        $creado = true;

        $db->exec("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado, completada) VALUES ($cid, 'nota', 'Lead captado automáticamente desde la Web', 'Registrado mediante Webhook API.', 'nuevo', 1)");
    }

    // Crear tarea prioritaria automática para el vendedor
    $asunto_tarea = "🚨 Llamar a nuevo lead web: " . ($nombre ?: 'Prospecto') . ($empresa ? " ($empresa)" : "");
    $desc_tarea = "El usuario completó el formulario de contacto web.\nTeléfono: $telefono\nEmail: $email\nInterés: $mensaje";
    $vence_hoy = date('Y-m-d H:i:s', strtotime('+2 hours'));

    $stmt_task = $db->prepare("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, fecha_vencimiento, completada, resultado) VALUES (?, 'tarea', ?, ?, ?, 0, 'pendiente')");
    $stmt_task->bindValue(1, $cid, SQLITE3_INTEGER);
    $stmt_task->bindValue(2, $asunto_tarea, SQLITE3_TEXT);
    $stmt_task->bindValue(3, $desc_tarea, SQLITE3_TEXT);
    $stmt_task->bindValue(4, $vence_hoy, SQLITE3_TEXT);
    $stmt_task->execute();

    echo json_encode([
        'ok' => true,
        'creado' => $creado,
        'contacto_id' => $cid,
        'mensaje' => 'Prospecto procesado exitosamente en Power Pack'
    ]);
    exit;
}

// 2. Drag & Drop de Negocios
if ($action === 'mover' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $nid = (int)($input['negocio_id'] ?? 0);
    $etapa = trim($input['etapa'] ?? '');
    if ($nid && $etapa) {
        $stmt = $db->prepare("UPDATE negocios SET etapa = ?, ultima_actividad = datetime('now') WHERE id = ?");
        $stmt->bindValue(1, $etapa, SQLITE3_TEXT);
        $stmt->bindValue(2, $nid, SQLITE3_INTEGER);
        $stmt->execute();
    }
    echo json_encode(['ok' => true]);
    exit;
}

// 3. Generar Redacción Comercial con IA (WhatsApp y Correo)
if ($action === 'generar_ia' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/ai.php';

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $cid = (int)($input['contacto_id'] ?? 0);
    $canal = trim($input['canal'] ?? 'whatsapp'); // 'whatsapp' o 'email'
    $objetivo = trim($input['objetivo'] ?? 'seguimiento_feria');
    $instrucciones = trim($input['instrucciones'] ?? '');
    $skill = trim($input['skill'] ?? '');

    $contacto = [];
    if ($cid > 0) {
        $contacto = $db->querySingle("SELECT * FROM contactos WHERE id = $cid", true);
    }

    if (!$contacto && !empty($input['prospecto_simulado'])) {
        $contacto = $input['prospecto_simulado'];
    }

    if (!$contacto) {
        $contacto = [
            'nombre' => 'Estimado Cliente',
            'empresa' => 'Empresa Industrial',
            'ciudad' => 'Bogotá',
            'fuente' => 'Feria Comercial',
            'notas' => 'Interesado en maquinaria de empaque y sellado'
        ];
    }

    $res = redactar_con_ia($db, $contacto, $canal, $objetivo, $instrucciones, $skill);
    $skill_obj = get_skill_by_code($db, $skill);
    
    // Generar enlace directo de WhatsApp si hay teléfono
    $wa_url = '';
    $clean_tel = limpiar_telefono_whatsapp($contacto['telefono'] ?? '');
    if ($clean_tel && $canal === 'whatsapp') {
        $wa_url = 'https://wa.me/' . $clean_tel . '?text=' . rawurlencode($res['mensaje']);
    }

    echo json_encode([
        'ok' => true,
        'asunto' => $res['asunto'] ?? '',
        'mensaje' => $res['mensaje'] ?? '',
        'origen' => $res['origen'] ?? 'IA Power Pack',
        'skill_nombre' => $skill_obj['nombre'] ?? '',
        'wa_url' => $wa_url,
        'api_warning' => $res['api_warning'] ?? ''
    ]);
    exit;
}

// 4. Listado de Skills B2B
if ($action === 'skills_ia') {
    require_once __DIR__ . '/ai.php';
    $canal = trim($_GET['canal'] ?? 'ambos');
    $skills = get_active_skills($db, $canal);
    echo json_encode([
        'ok' => true,
        'skills' => $skills
    ]);
    exit;
}

// 5. Guardar y Validar API Key de Inteligencia Artificial (Google Gemini / OpenAI)
if ($action === 'guardar_ai_key' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/ai.php';

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $api_key = trim($input['api_key'] ?? '');
    $provider = trim($input['provider'] ?? 'gemini');
    $model = trim($input['model'] ?? ($provider === 'gemini' ? 'gemini-3.8-flash' : 'gpt-4o-mini'));
    if ($provider === 'gemini' && ($model === 'gemini-2.0-flash' || empty($model))) {
        $model = 'gemini-3.8-flash';
    }

    if (empty($api_key)) {
        // Si vacían la API Key, quitarla y volver al motor interno
        set_config($db, 'ai_api_key', '');
        echo json_encode([
            'ok' => true,
            'mensaje' => 'API Key desconectada. El sistema volverá a utilizar el Motor Heurístico Interno de Power Pack.',
            'provider' => $provider,
            'model' => $model,
            'activo' => false
        ]);
        exit;
    }

    // Validar en vivo la clave con Google Gemini o OpenAI
    $test_ok = false;
    $test_err = '';
    if ($provider === 'gemini') {
        $ping = llamar_gemini($api_key, $model, 'Di únicamente: LISTO', 'Eres el asistente comercial de Power Pack');
        $test_ok = $ping['ok'];
        $test_err = $ping['error'] ?? '';
        if ($test_ok && !empty($ping['model'])) {
            $model = $ping['model'];
        }
    } else {
        $ping = llamar_openai($api_key, $model, 'Di únicamente: LISTO', 'Eres el asistente comercial de Power Pack');
        $test_ok = $ping['ok'];
        $test_err = $ping['error'] ?? '';
    }

    // Guardar la configuración
    set_config($db, 'ai_provider', $provider);
    set_config($db, 'ai_api_key', $api_key);
    set_config($db, 'ai_model', $model);

    if ($test_ok) {
        echo json_encode([
            'ok' => true,
            'mensaje' => '¡Conexión exitosa y verificada con ' . ($provider === 'gemini' ? 'Google Gemini' : 'OpenAI') . ' (' . $model . ')!',
            'provider' => $provider,
            'model' => $model,
            'activo' => true
        ]);
    } else {
        echo json_encode([
            'ok' => true,
            'warning' => true,
            'mensaje' => 'API Key guardada, pero la verificación arrojó un aviso: ' . $test_err . '. Puedes verificar tu clave o cuota en Google AI Studio.',
            'error_detalle' => $test_err,
            'provider' => $provider,
            'model' => $model,
            'activo' => true
        ]);
    }
    exit;
}

// 6. Cambiar Prioridad de Contacto (1-Clic en tiempo real)
if ($action === 'cambiar_prioridad' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    $id = (int)($input['id'] ?? 0);
    $prioridad = strtolower(trim($input['prioridad'] ?? 'media'));
    if (!in_array($prioridad, ['alta', 'media', 'baja'])) {
        $prioridad = 'media';
    }

    if ($id > 0) {
        $stmt = $db->prepare("UPDATE contactos SET prioridad = ?, ultima_actividad = datetime('now') WHERE id = ?");
        $stmt->bindValue(1, $prioridad, SQLITE3_TEXT);
        $stmt->bindValue(2, $id, SQLITE3_INTEGER);
        $stmt->execute();

        echo json_encode([
            'ok' => true,
            'id' => $id,
            'prioridad' => $prioridad,
            'mensaje' => 'Prioridad actualizada a ' . ucfirst($prioridad)
        ]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'ID de contacto inválido']);
    exit;
}

// 7. Chat Interactivo Copilot con Subagente Especializado
if ($action === 'subagente_chat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/ai.php';

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $subagente_id = trim($input['subagente_id'] ?? 'email');
    $skill_codigo = trim($input['skill_codigo'] ?? '');
    $contacto_id  = (int)($input['contacto_id'] ?? 0);
    $instrucciones = trim($input['instrucciones'] ?? '');
    $historial    = $input['historial'] ?? [];
    $borrador     = $input['borrador'] ?? [];

    $contacto = null;
    if ($contacto_id > 0) {
        $stmt_c = $db->prepare("SELECT * FROM contactos WHERE id = ?");
        $stmt_c->bindValue(1, $contacto_id, SQLITE3_INTEGER);
        $res_c = $stmt_c->execute();
        $contacto = $res_c->fetchArray(SQLITE3_ASSOC);
    }

    $resultado = copilot_subagente_chat($db, $subagente_id, $skill_codigo, $historial, $contacto, $instrucciones, $borrador);
    echo json_encode($resultado);
    exit;
}

// 8. Envío de Correo desde el Estudio (Individual o Masivo con SMTP Hostinger)
if ($action === 'enviar_correo_estudio' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/mailer.php';
    require_once __DIR__ . '/ai.php';

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $modo            = trim($input['modo'] ?? 'individual'); // 'individual' o 'masivo'
    $asunto_plantilla = trim($input['asunto'] ?? '');
    $cuerpo_plantilla = trim($input['cuerpo_html'] ?? '');
    $skill_codigo    = trim($input['skill_codigo'] ?? '');
    $subagente       = trim($input['subagente'] ?? 'email_copywriter');

    if (empty($asunto_plantilla) || empty($cuerpo_plantilla)) {
        echo json_encode(['ok' => false, 'error' => 'El asunto y el cuerpo del correo no pueden estar vacíos.']);
        exit;
    }

    $smtp_config = [
        'empresa_nombre'   => get_config($db, 'empresa_nombre', 'Power Pack'),
        'smtp_host'        => get_config($db, 'smtp_host', 'smtp.hostinger.com'),
        'smtp_port'        => get_config($db, 'smtp_port', '465'),
        'smtp_secure'      => get_config($db, 'smtp_secure', 'ssl'),
        'smtp_user'        => get_config($db, 'smtp_user', 'administrador@powerpack.site'),
        'smtp_pass'        => get_config($db, 'smtp_pass', 'PowerPack2026*'),
        'smtp_from'        => get_config($db, 'smtp_from', 'administrador@powerpack.site')
    ];

    if ($modo === 'individual') {
        $contacto_id = (int)($input['contacto_id'] ?? 0);
        $dest_email  = trim($input['destinatario_email'] ?? '');
        $dest_nombre = trim($input['destinatario_nombre'] ?? '');
        $empresa     = trim($input['destinatario_empresa'] ?? '');
        $cargo       = trim($input['destinatario_cargo'] ?? '');
        $ciudad      = trim($input['destinatario_ciudad'] ?? 'Bogotá');

        if ($contacto_id > 0) {
            $c_row = $db->querySingle("SELECT * FROM contactos WHERE id = $contacto_id", true);
            if ($c_row) {
                $dest_email  = $c_row['email'] ?: $dest_email;
                $dest_nombre = trim(($c_row['nombre'] ?? '') . ' ' . ($c_row['apellido'] ?? '')) ?: $dest_nombre;
                $empresa     = $c_row['empresa'] ?: $empresa;
                $cargo       = $c_row['cargo'] ?: $cargo;
                $ciudad      = $c_row['ciudad'] ?: $ciudad;
            }
        }

        if (empty($dest_email)) {
            echo json_encode(['ok' => false, 'error' => 'No se especificó un correo electrónico de destino válido.']);
            exit;
        }

        // Reemplazo de variables dinámicas
        $buscar = ['{nombre}', '{empresa}', '{cargo}', '{ciudad}'];
        $reemplazo = [$dest_nombre ?: 'Estimado(a) Cliente', $empresa ?: 'su empresa', $cargo ?: 'Directivo', $ciudad ?: 'Colombia'];

        $asunto_final = str_ireplace($buscar, $reemplazo, $asunto_plantilla);
        $cuerpo_final = str_ireplace($buscar, $reemplazo, $cuerpo_plantilla);

        if (strpos($cuerpo_final, '<p>') === false && strpos($cuerpo_final, '<br') === false) {
            $cuerpo_final = nl2br(htmlspecialchars($cuerpo_final, ENT_NOQUOTES, 'UTF-8'));
        }

        $envio = enviar_correo_smtp($dest_email, $asunto_final, $cuerpo_final, $smtp_config);

        $estado = $envio['ok'] ? 'enviado' : 'fallido';
        $error_txt = $envio['ok'] ? '' : ($envio['error'] ?? 'Error desconocido');

        // Registrar en mensajes_email
        $stmt_log = $db->prepare("INSERT INTO mensajes_email (contacto_id, destinatario_email, destinatario_nombre, asunto, cuerpo_html, subagente, skill_codigo, estado, error_detalle, es_masivo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt_log->bindValue(1, $contacto_id > 0 ? $contacto_id : null, SQLITE3_INTEGER);
        $stmt_log->bindValue(2, $dest_email, SQLITE3_TEXT);
        $stmt_log->bindValue(3, $dest_nombre, SQLITE3_TEXT);
        $stmt_log->bindValue(4, $asunto_final, SQLITE3_TEXT);
        $stmt_log->bindValue(5, $cuerpo_final, SQLITE3_TEXT);
        $stmt_log->bindValue(6, $subagente, SQLITE3_TEXT);
        $stmt_log->bindValue(7, $skill_codigo, SQLITE3_TEXT);
        $stmt_log->bindValue(8, $estado, SQLITE3_TEXT);
        $stmt_log->bindValue(9, $error_txt, SQLITE3_TEXT);
        $stmt_log->execute();

        if ($contacto_id > 0 && $envio['ok']) {
            $stmt_act = $db->prepare("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado, completada) VALUES (?, 'email', ?, ?, 'enviado', 1)");
            $stmt_act->bindValue(1, $contacto_id, SQLITE3_INTEGER);
            $stmt_act->bindValue(2, "Correo enviado: " . mb_substr($asunto_final, 0, 50), SQLITE3_TEXT);
            $desc_act = "Asunto: $asunto_final\nPara: $dest_email\n\n" . (function_exists('html_a_texto_limpio') ? html_a_texto_limpio($cuerpo_final) : strip_tags($cuerpo_final));
            $stmt_act->bindValue(3, $desc_act, SQLITE3_TEXT);
            $stmt_act->execute();
            $db->exec("UPDATE contactos SET ultima_actividad = datetime('now') WHERE id = $contacto_id");
        }

        echo json_encode([
            'ok' => $envio['ok'],
            'destinatario' => $dest_email,
            'contacto_id' => $contacto_id,
            'mensaje' => $envio['ok'] ? "Correo enviado exitosamente a $dest_email" : $envio['error'],
            'error' => $envio['ok'] ? '' : $envio['error']
        ]);
        exit;
    } else {
        // MODO MASIVO
        $contactos_ids = $input['contactos_ids'] ?? [];
        if (empty($contactos_ids) || !is_array($contactos_ids)) {
            // Si no se pasaron IDs explícitos, verificar si se especificó filtro de prioridad o etapa
            $filtro_prioridad = trim($input['filtro_prioridad'] ?? '');
            $filtro_etapa     = trim($input['filtro_etapa'] ?? '');
            $filtro_sector    = trim($input['filtro_sector'] ?? '');

            $where_cl = ["email IS NOT NULL AND email != ''"];
            if (!empty($filtro_prioridad)) $where_cl[] = "prioridad = '" . SQLite3::escapeString($filtro_prioridad) . "'";
            if (!empty($filtro_etapa))     $where_cl[] = "etapa = '" . SQLite3::escapeString($filtro_etapa) . "'";
            if (!empty($filtro_sector))    $where_cl[] = "sector = '" . SQLite3::escapeString($filtro_sector) . "'";

            $sql_ids = "SELECT id FROM contactos WHERE " . implode(" AND ", $where_cl) . " ORDER BY id DESC LIMIT 100";
            $res_ids = $db->query($sql_ids);
            $contactos_ids = [];
            while ($r = $res_ids->fetchArray(SQLITE3_ASSOC)) {
                $contactos_ids[] = (int)$r['id'];
            }
        }

        if (empty($contactos_ids)) {
            echo json_encode(['ok' => false, 'error' => 'No se encontraron contactos con correo electrónico para el envío masivo.']);
            exit;
        }

        $lote_id = 'lote_email_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 4);
        $exitosos = 0;
        $fallidos = 0;
        $detalles = [];

        foreach ($contactos_ids as $cid) {
            $cid = (int)$cid;
            if ($cid <= 0) continue;
            $c_row = $db->querySingle("SELECT * FROM contactos WHERE id = $cid", true);
            if (!$c_row || empty($c_row['email'])) {
                $fallidos++;
                continue;
            }

            $dest_email  = trim($c_row['email']);
            $dest_nombre = trim(($c_row['nombre'] ?? '') . ' ' . ($c_row['apellido'] ?? '')) ?: 'Estimado(a) Cliente';
            $empresa     = trim($c_row['empresa'] ?? 'su empresa');
            $cargo       = trim($c_row['cargo'] ?? 'Directivo');
            $ciudad      = trim($c_row['ciudad'] ?? 'Colombia');

            $buscar = ['{nombre}', '{empresa}', '{cargo}', '{ciudad}'];
            $reemplazo = [$dest_nombre, $empresa, $cargo, $ciudad];

            $asunto_final = str_ireplace($buscar, $reemplazo, $asunto_plantilla);
            $cuerpo_final = str_ireplace($buscar, $reemplazo, $cuerpo_plantilla);

            if (strpos($cuerpo_final, '<p>') === false && strpos($cuerpo_final, '<br') === false) {
                $cuerpo_final = nl2br(htmlspecialchars($cuerpo_final, ENT_NOQUOTES, 'UTF-8'));
            }

            $envio = enviar_correo_smtp($dest_email, $asunto_final, $cuerpo_final, $smtp_config);
            if ($envio['ok']) {
                $exitosos++;
                $estado = 'enviado';
                $error_txt = '';

                // Actividad en contacto
                $stmt_act = $db->prepare("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado, completada) VALUES (?, 'email', ?, ?, 'campaña_masiva', 1)");
                $stmt_act->bindValue(1, $cid, SQLITE3_INTEGER);
                $stmt_act->bindValue(2, "Campaña Masiva: " . mb_substr($asunto_final, 0, 45), SQLITE3_TEXT);
                $stmt_act->bindValue(3, "Lote: $lote_id\nAsunto: $asunto_final\nDestinatario: $dest_email", SQLITE3_TEXT);
                $stmt_act->execute();
                $db->exec("UPDATE contactos SET ultima_actividad = datetime('now') WHERE id = $cid");
            } else {
                $fallidos++;
                $estado = 'fallido';
                $error_txt = $envio['error'] ?? 'Error SMTP';
            }

            // Registrar en historial mensajes_email
            $stmt_log = $db->prepare("INSERT INTO mensajes_email (contacto_id, destinatario_email, destinatario_nombre, asunto, cuerpo_html, subagente, skill_codigo, estado, error_detalle, es_masivo, lote_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
            $stmt_log->bindValue(1, $cid, SQLITE3_INTEGER);
            $stmt_log->bindValue(2, $dest_email, SQLITE3_TEXT);
            $stmt_log->bindValue(3, $dest_nombre, SQLITE3_TEXT);
            $stmt_log->bindValue(4, $asunto_final, SQLITE3_TEXT);
            $stmt_log->bindValue(5, $cuerpo_final, SQLITE3_TEXT);
            $stmt_log->bindValue(6, $subagente, SQLITE3_TEXT);
            $stmt_log->bindValue(7, $skill_codigo, SQLITE3_TEXT);
            $stmt_log->bindValue(8, $estado, SQLITE3_TEXT);
            $stmt_log->bindValue(9, $error_txt, SQLITE3_TEXT);
            $stmt_log->bindValue(10, $lote_id, SQLITE3_TEXT);
            $stmt_log->execute();

            $detalles[] = [
                'contacto_id' => $cid,
                'email' => $dest_email,
                'nombre' => $dest_nombre,
                'ok' => $envio['ok'],
                'error' => $error_txt
            ];
        }

        echo json_encode([
            'ok' => true,
            'lote_id' => $lote_id,
            'total' => count($contactos_ids),
            'exitosos' => $exitosos,
            'fallidos' => $fallidos,
            'detalles' => $detalles,
            'mensaje' => "Campaña procesada: $exitosos correos enviados con éxito ($fallidos errores)."
        ]);
        exit;
    }
}

// 9. Registrar WhatsApp Enviado desde el Estudio (Historial y Trazabilidad)
if ($action === 'registrar_whatsapp_estudio' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $contacto_id = (int)($input['contacto_id'] ?? 0);
    $telefono    = trim($input['telefono'] ?? '');
    $dest_nombre = trim($input['destinatario_nombre'] ?? '');
    $mensaje     = trim($input['mensaje'] ?? '');
    $skill_codigo = trim($input['skill_codigo'] ?? '');
    $subagente   = trim($input['subagente'] ?? 'whatsapp_specialist');
    $es_masivo   = (int)($input['es_masivo'] ?? 0);
    $lote_id     = trim($input['lote_id'] ?? '');

    if (empty($telefono) && $contacto_id > 0) {
        $telefono = (string)$db->querySingle("SELECT telefono FROM contactos WHERE id = $contacto_id");
    }
    if (empty($dest_nombre) && $contacto_id > 0) {
        $c = $db->querySingle("SELECT nombre, apellido, empresa FROM contactos WHERE id = $contacto_id", true);
        if ($c) {
            $dest_nombre = trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? '')) . (!empty($c['empresa']) ? " ({$c['empresa']})" : '');
        }
    }

    $stmt = $db->prepare("INSERT INTO mensajes_whatsapp (contacto_id, telefono, destinatario_nombre, mensaje, subagente, skill_codigo, estado, es_masivo, lote_id) VALUES (?, ?, ?, ?, ?, ?, 'enviado', ?, ?)");
    $stmt->bindValue(1, $contacto_id > 0 ? $contacto_id : null, SQLITE3_INTEGER);
    $stmt->bindValue(2, $telefono, SQLITE3_TEXT);
    $stmt->bindValue(3, $dest_nombre, SQLITE3_TEXT);
    $stmt->bindValue(4, $mensaje, SQLITE3_TEXT);
    $stmt->bindValue(5, $subagente, SQLITE3_TEXT);
    $stmt->bindValue(6, $skill_codigo, SQLITE3_TEXT);
    $stmt->bindValue(7, $es_masivo, SQLITE3_INTEGER);
    $stmt->bindValue(8, $lote_id ?: null, SQLITE3_TEXT);
    $stmt->execute();
    $mid = $db->lastInsertRowID();

    if ($contacto_id > 0) {
        $stmt_act = $db->prepare("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado, completada) VALUES (?, 'whatsapp', 'Mensaje de WhatsApp vía Estudio IA', ?, 'enviado', 1)");
        $stmt_act->bindValue(1, $contacto_id, SQLITE3_INTEGER);
        $stmt_act->bindValue(2, $mensaje . "\n\n(Skill: $skill_codigo)", SQLITE3_TEXT);
        $stmt_act->execute();
        $db->exec("UPDATE contactos SET ultima_actividad = datetime('now') WHERE id = $contacto_id");
    }

    echo json_encode([
        'ok' => true,
        'mensaje_id' => $mid,
        'mensaje' => 'Mensaje de WhatsApp registrado exitosamente en el historial'
    ]);
    exit;
}

// 10. Consultar Historial de Envíos de Subagentes
if ($action === 'obtener_historial_subagente') {
    $canal  = trim($_GET['canal'] ?? 'email');
    $limite = min(100, max(10, (int)($_GET['limite'] ?? 50)));

    if ($canal === 'email') {
        $sql = "SELECT m.*, c.nombre as contacto_nombre, c.apellido as contacto_apellido, c.empresa as contacto_empresa 
                FROM mensajes_email m 
                LEFT JOIN contactos c ON m.contacto_id = c.id 
                ORDER BY m.id DESC LIMIT $limite";
    } else {
        $sql = "SELECT m.*, c.nombre as contacto_nombre, c.apellido as contacto_apellido, c.empresa as contacto_empresa 
                FROM mensajes_whatsapp m 
                LEFT JOIN contactos c ON m.contacto_id = c.id 
                ORDER BY m.id DESC LIMIT $limite";
    }

    $res = $db->query($sql);
    $items = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $items[] = $row;
    }

    echo json_encode(['ok' => true, 'canal' => $canal, 'total' => count($items), 'items' => $items]);
    exit;
}


// Default response
echo json_encode([
    'plataforma' => 'Power Pack API',
    'status' => 'online',
    'version' => '3.0',
    'endpoints' => [
        'POST api.php?action=lead_web'                  => 'Recibir leads desde formularios web externos',
        'POST api.php?action=mover'                     => 'Actualizar etapa de negocio en Kanban',
        'POST api.php?action=generar_ia'                => 'Generar redacción comercial asistida con IA de Power Pack',
        'POST api.php?action=subagente_chat'            => 'Co-redacción iterativa Copilot con subagente B2B',
        'POST api.php?action=enviar_correo_estudio'     => 'Despacho de correos individuales o masivos vía SMTP Hostinger',
        'POST api.php?action=registrar_whatsapp_estudio'=> 'Registro y trazabilidad de WhatsApp en historial comercial',
        'GET  api.php?action=obtener_historial_subagente'=> 'Bandeja de historial de correos y WhatsApps enviados'
    ]
]);
