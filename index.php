<?php
require_once __DIR__ . '/db.php';

$page = $_GET['page'] ?? 'dashboard';
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nuevo_contacto'])) {
        $stmt = $db->prepare("INSERT INTO contactos (nombre, apellido, email, telefono, empresa, cargo, ciudad, sector, fuente, etapa, interes, notas) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bindValue(1, $_POST['nombre'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(2, $_POST['apellido'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(3, $_POST['email'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(4, $_POST['telefono'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(5, $_POST['empresa'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(6, $_POST['cargo'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(7, $_POST['ciudad'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(8, $_POST['sector'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(9, $_POST['fuente'] ?? 'feria', SQLITE3_TEXT);
        $stmt->bindValue(10, $_POST['etapa'] ?? 'lead', SQLITE3_TEXT);
        $stmt->bindValue(11, (int)($_POST['interes'] ?? 1), SQLITE3_INTEGER);
        $stmt->bindValue(12, $_POST['notas'] ?? '', SQLITE3_TEXT);
        $stmt->execute();
        $cid = $db->lastInsertRowID();
        $db->exec("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado) VALUES ($cid, 'creacion', 'Contacto creado', 'Registrado en el CRM', 'nuevo')");
        header("Location: index.php?page=detalle&id=$cid");
        exit;
    }
    
    if (isset($_POST['nueva_actividad']) && $id) {
        $stmt = $db->prepare("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado) VALUES (?,?,?,?,?)");
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $_POST['tipo'] ?? 'nota', SQLITE3_TEXT);
        $stmt->bindValue(3, $_POST['asunto'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(4, $_POST['descripcion'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(5, $_POST['resultado'] ?? '', SQLITE3_TEXT);
        $stmt->execute();
        $db->exec("UPDATE contactos SET ultima_actividad = datetime('now') WHERE id = $id");
        header("Location: index.php?page=detalle&id=$id");
        exit;
    }
    
    if (isset($_POST['nuevo_negocio'])) {
        $stmt = $db->prepare("INSERT INTO negocios (contacto_id, nombre, monto, etapa, fecha_cierre, probabilidad) VALUES (?,?,?,?,?,?)");
        $stmt->bindValue(1, $_POST['contacto_id'] ?: null, SQLITE3_INTEGER);
        $stmt->bindValue(2, $_POST['nombre'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(3, (float)($_POST['monto'] ?? 0), SQLITE3_FLOAT);
        $stmt->bindValue(4, $_POST['etapa'] ?? 'contacto_inicial', SQLITE3_TEXT);
        $stmt->bindValue(5, $_POST['fecha_cierre'] ?? date('Y-m-d'), SQLITE3_TEXT);
        $stmt->bindValue(6, (int)($_POST['probabilidad'] ?? 20), SQLITE3_INTEGER);
        $stmt->execute();
        header("Location: index.php?page=pipeline");
        exit;
    }
    
    if (isset($_POST['enviar_email']) && $id) {
        $destinatario = $_POST['destinatario'] ?? '';
        $asunto = $_POST['asunto'] ?? '';
        $cuerpo = $_POST['cuerpo'] ?? '';
        $password = $_POST['smtp_password'] ?? '';
        
        if ($asunto && $cuerpo && $destinatario) {
            $headers = "From: oivancho1967@gmail.com\r\nContent-Type: text/plain; charset=UTF-8";
            $sent = @mail($destinatario, $asunto, $cuerpo, $headers);
            
            $db->exec("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado) VALUES ($id, 'email', 'Email enviado: " . SQLite3::escapeString($asunto) . "', 'Para: " . SQLite3::escapeString($destinatario) . "\n\n" . SQLite3::escapeString(substr($cuerpo, 0, 300)) . "', '" . ($sent ? 'enviado' : 'error') . "')");
            $db->exec("UPDATE contactos SET ultima_actividad = datetime('now') WHERE id = $id");
            $msg = $sent ? 'ok' : 'error_envio';
        }
        header("Location: index.php?page=detalle&id=$id&msg=$msg");
        exit;
    }
}

// API: mover negocio
if ($page === 'api' && $action === 'mover' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $nid = $input['negocio_id'] ?? 0;
    $etapa = $input['etapa'] ?? '';
    if ($nid && $etapa) {
        $db->exec("UPDATE negocios SET etapa = '$etapa' WHERE id = " . (int)$nid);
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// Headers comunes
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Powerpack CRM</title>
    <meta name="theme-color" content="#f97316">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Powerpack">
    <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%23f97316' width='100' height='100' rx='20'/></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-w: 220px; --topbar-h: 56px;
            --bg: #f5f6f8; --bg-white: #ffffff; --sidebar-bg: #1e293b;
            --sidebar-fg: #cbd5e1; --accent: #f97316; --accent-hover: #ea580c;
            --fg: #1e293b; --fg-secondary: #64748b; --border: #e2e8f0; --card: #ffffff;
            --success: #059669; --warning: #d97706; --danger: #dc2626; --blue: #2563eb;
            --radius: 8px;
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',-apple-system,sans-serif;background:var(--bg);color:var(--fg);min-height:100vh;display:flex;font-size:14px;line-height:1.5}
        a{color:inherit;text-decoration:none}button{font-family:inherit;cursor:pointer}
        .sidebar{width:var(--sidebar-w);background:var(--sidebar-bg);color:var(--sidebar-fg);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;overflow-y:auto}
        .sidebar-logo{padding:16px 20px;font-weight:700;font-size:18px;color:#fff;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:8px}
        .sidebar-logo .dot{width:10px;height:10px;background:var(--accent);border-radius:50%}
        .sidebar-section{padding:8px 20px 4px;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b}
        .sidebar-item{display:flex;align-items:center;gap:10px;padding:10px 20px;font-size:13px;font-weight:500;color:var(--sidebar-fg);margin:1px 8px;border-radius:var(--radius);transition:.15s}
        .sidebar-item:hover{background:rgba(255,255,255,.06);color:#fff}
        .sidebar-item.active{background:var(--accent);color:#fff;font-weight:600}
        .main-wrap{flex:1;margin-left:var(--sidebar-w);display:flex;flex-direction:column;min-height:100vh}
        .topbar{height:var(--topbar-h);background:var(--bg-white);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:50}
        .topbar-search{display:flex;align-items:center;gap:8px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:6px 12px;min-width:300px}
        .topbar-search input{border:none;background:none;outline:none;font-size:13px;width:100%;color:var(--fg)}
        .content{flex:1;padding:24px;max-width:1400px;width:100%;margin:0 auto}
        .page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px}
        .page-header h1{font-size:22px;font-weight:700}
        .page-header p{color:var(--fg-secondary);font-size:13px;margin-top:2px}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--radius);font-size:13px;font-weight:500;border:none;transition:.15s}
        .btn-primary{background:var(--accent);color:#fff}.btn-primary:hover{background:var(--accent-hover)}
        .btn-secondary{background:var(--bg-white);color:var(--fg);border:1px solid var(--border)}.btn-secondary:hover{border-color:#cbd5e1;background:var(--bg)}
        .btn-ghost{background:transparent;color:var(--fg-secondary)}.btn-ghost:hover{background:var(--bg)}
        .btn-sm{padding:5px 10px;font-size:12px}
        .cards-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px}
        .metric-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;transition:.2s}
        .metric-card:hover{box-shadow:0 1px 3px rgba(0,0,0,.08)}
        .metric-card .label{font-size:12px;color:var(--fg-secondary);font-weight:500;text-transform:uppercase;letter-spacing:.03em}
        .metric-card .value{font-size:28px;font-weight:700;margin-top:4px}
        .metric-card .sub{font-size:12px;color:var(--fg-secondary);margin-top:2px}
        .info-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:16px}
        .info-card-header{padding:14px 16px;border-bottom:1px solid var(--border);font-weight:600;font-size:13px;display:flex;justify-content:space-between;align-items:center}
        .info-card-body{padding:16px}
        .info-row{display:flex;justify-content:space-between;padding:6px 0;font-size:13px;border-bottom:1px solid #f8fafc}
        .info-row:last-child{border-bottom:none}
        .info-row .info-label{color:var(--fg-secondary)}
        .info-row .info-value{font-weight:500;text-align:right}
        .table-wrap{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
        .table-toolbar{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border);gap:12px}
        .tabs{display:flex;gap:2px}
        .tab{padding:6px 14px;border-radius:var(--radius);font-size:13px;font-weight:500;border:none;background:transparent;color:var(--fg-secondary);transition:.15s;cursor:pointer}
        .tab:hover{background:var(--bg)}.tab.active{background:var(--bg);color:var(--fg);font-weight:600}
        table{width:100%;border-collapse:collapse}
        th{text-align:left;padding:10px 16px;font-size:11px;font-weight:600;color:var(--fg-secondary);text-transform:uppercase;letter-spacing:.04em;background:var(--bg);border-bottom:1px solid var(--border)}
        td{padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle}
        tr:hover td{background:#f8fafc}tr:last-child td{border-bottom:none}
        .link{color:var(--blue);font-weight:500}.link:hover{text-decoration:underline}
        .badge{display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;white-space:nowrap}
        .badge-lead{background:#f1f5f9;color:#475569}
        .badge-contacto_inicial{background:#eff6ff;color:var(--blue)}
        .badge-calificado{background:#f3e8ff;color:#7c3aed}
        .badge-cotizacion{background:#fffbeb;color:var(--warning)}
        .badge-negociacion{background:#fef2f2;color:var(--danger)}
        .badge-ganado{background:#ecfdf5;color:var(--success)}
        .badge-perdido{background:#fee2e2;color:#991b1b}
        .board-scroll{overflow-x:auto;padding-bottom:8px}
        .board{display:flex;gap:16px;min-height:400px;min-width:max-content}
        .board-col{background:#f0f2f5;border-radius:var(--radius);padding:12px;width:260px;min-width:260px;display:flex;flex-direction:column;gap:10px}
        .board-col-header{display:flex;justify-content:space-between;align-items:center;font-weight:600;font-size:13px;padding:0 4px}
        .board-col-count{background:#e2e8f0;padding:2px 8px;border-radius:999px;font-size:12px}
        .board-card{background:var(--card);border-radius:var(--radius);padding:14px;box-shadow:0 1px 2px rgba(0,0,0,.05);cursor:pointer;transition:.2s;border:1px solid transparent}
        .board-card:hover{box-shadow:0 1px 3px rgba(0,0,0,.08);border-color:var(--accent)}
        .board-card h4{font-size:14px;font-weight:600;margin-bottom:4px}
        .board-card .company{font-size:12px;color:var(--fg-secondary)}
        .board-card .amount{font-size:15px;font-weight:700;margin-top:8px}
        .board-card .meta{font-size:11px;color:var(--fg-secondary);margin-top:4px;display:flex;justify-content:space-between}
        .timeline{position:relative;padding-left:28px}
        .timeline::before{content:'';position:absolute;left:8px;top:8px;bottom:8px;width:2px;background:var(--border)}
        .timeline-item{position:relative;margin-bottom:20px;padding-left:12px}
        .timeline-item::before{content:'';position:absolute;left:-24px;top:6px;width:10px;height:10px;border-radius:50%;background:var(--accent);border:2px solid #fff;box-shadow:0 0 0 2px var(--accent)}
        .t-date{font-size:11px;color:var(--fg-secondary)}.t-type{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.03em}
        .t-desc{font-size:13px;margin-top:4px}.t-meta{font-size:11px;color:var(--fg-secondary);margin-top:2px}
        .form-group{margin-bottom:14px}.form-group label{display:block;margin-bottom:4px;font-size:13px;font-weight:500}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:inherit;color:var(--fg);background:#fff;transition:border-color .15s}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(249,115,22,.12)}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}.form-row-3{grid-template-columns:1fr 1fr 1fr}
        .detail-grid{display:grid;grid-template-columns:1fr 350px;gap:24px}
        .empty-state{text-align:center;padding:60px 20px;color:var(--fg-secondary)}
        .alert-success{background:#ecfdf5;color:#059669;padding:12px;border-radius:8px;margin-bottom:12px;font-weight:500}
        .alert-error{background:#fef2f2;color:#dc2626;padding:12px;border-radius:8px;margin-bottom:12px}
        @media(max-width:900px){.sidebar{display:none}.main-wrap{margin-left:0}.detail-grid{grid-template-columns:1fr}.form-row,.form-row-3{grid-template-columns:1fr}}
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo"><span class="dot"></span>Powerpack CRM</div>
    <nav style="padding:12px 0;flex:1;">
        <div class="sidebar-section">Principal</div>
        <a href="?page=dashboard" class="sidebar-item <?= $page=='dashboard'?'active':'' ?>">📊 Dashboard</a>
        <a href="?page=contactos" class="sidebar-item <?= $page=='contactos'?'active':'' ?>">👥 Contactos</a>
        <a href="?page=pipeline" class="sidebar-item <?= $page=='pipeline'?'active':'' ?>">📋 Negocios</a>
        <div class="sidebar-section" style="margin-top:12px">Acciones</div>
        <a href="?page=nuevo" class="sidebar-item">➕ Nuevo contacto</a>
        <a href="?page=nuevo_negocio" class="sidebar-item">💼 Nuevo negocio</a>
    </nav>
</aside>
<div class="main-wrap">
    <header class="topbar">
        <form class="topbar-search" action="?page=contactos" method="get">
            <input type="hidden" name="page" value="contactos">
            <span style="color:var(--fg-secondary)">🔍</span>
            <input type="text" name="q" placeholder="Buscar contactos, empresas..." value="<?= h($_GET['q'] ?? '') ?>">
        </form>
        <a href="?page=nuevo" class="btn btn-primary btn-sm">+ Agregar</a>
    </header>
    <main class="content">
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
    case 'pipeline':
        include 'pages/pipeline.php';
        break;
    case 'nuevo_negocio':
        include 'pages/nuevo_negocio.php';
        break;
    default:
        include 'pages/dashboard.php';
}
?>
    </main>
</div>
</body>
</html>