<?php
/**
 * api.php - Webhook & API REST para captura automática de prospectos web
 * Conéctalo a formularios de contacto de WordPress, Elementor, HTML o landing pages en Hostinger.
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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
        'mensaje' => 'Prospecto procesado exitosamente en Powerpack CRM'
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

    $res = redactar_con_ia($db, $contacto, $canal, $objetivo, $instrucciones);
    
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
        'wa_url' => $wa_url,
        'api_warning' => $res['api_warning'] ?? ''
    ]);
    exit;
}

// Default response
echo json_encode([
    'crm' => 'Power Pack CRM API',
    'status' => 'online',
    'version' => '2.5',
    'endpoints' => [
        'POST api.php?action=lead_web'   => 'Recibir leads desde formularios web externos',
        'POST api.php?action=mover'      => 'Actualizar etapa de negocio en Kanban',
        'POST api.php?action=generar_ia' => 'Generar redacción comercial asistida con IA de Power Pack'
    ]
]);
