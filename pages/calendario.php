<?php
/**
 * pages/calendario.php - Calendario Comercial & Checklist Diario de Compromisos
 * Agenda diaria para no olvidar llamadas, cotizaciones, investigaciones y compromisos urgentes con clientes.
 * Power Pack SAS
 */

// Fecha y navegación del calendario
$ahora_ts = time();
$hoy_ymd  = date('Y-m-d');
$fecha_sel = $_GET['fecha'] ?? $hoy_ymd;

// Mes y año del calendario
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('n');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

if ($mes < 1) { $mes = 12; $anio--; }
if ($mes > 12) { $mes = 1; $anio++; }

$mes_siguiente = $mes == 12 ? 1 : $mes + 1;
$anio_siguiente = $mes == 12 ? $anio + 1 : $anio;
$mes_anterior = $mes == 1 ? 12 : $mes - 1;
$anio_anterior = $mes == 1 ? $anio - 1 : $anio;

$meses_nombres = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$dias_semana_nombres = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$nombre_dia_hoy = $dias_semana_nombres[(int)date('w')];
$fecha_hoy_humana = $nombre_dia_hoy . ', ' . date('j') . ' de ' . $meses_nombres[(int)date('n')] . ' de ' . date('Y');

// Filtro de lista
$filtro_modo = $_GET['filtro'] ?? 'hoy_urgentes';
$filtro_dia_especifico = isset($_GET['dia_especifico']) && $_GET['dia_especifico'] == '1';

// Cálculos de métricas de urgencia
$total_urgentes = (int)$db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'tarea' AND completada = 0 AND (prioridad = 'urgente' OR (fecha_vencimiento IS NOT NULL AND date(fecha_vencimiento) < date('now')))");
$total_hoy = (int)$db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'tarea' AND completada = 0 AND date(fecha_vencimiento) = date('now')");
$completadas_hoy = (int)$db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'tarea' AND completada = 1 AND date(fecha) = date('now')");
$total_mes = (int)$db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'tarea' AND completada = 0 AND strftime('%Y-%m', fecha_vencimiento) = '" . sprintf('%04d-%02d', $anio, $mes) . "'");

$total_diario_estimado = $total_hoy + $completadas_hoy;
$pct_progreso = $total_diario_estimado > 0 ? min(100, round(($completadas_hoy / $total_diario_estimado) * 100)) : 0;

// Construir consulta para el Checklist Diario
$where_clauses = ["a.tipo = 'tarea'"];

if ($filtro_dia_especifico) {
    $where_clauses[] = "date(a.fecha_vencimiento) = '" . SQLite3::escapeString($fecha_sel) . "'";
} else {
    switch ($filtro_modo) {
        case 'llamadas':
            $where_clauses[] = "a.completada = 0 AND a.categoria_compromiso = 'llamada'";
            break;
        case 'cotizaciones':
            $where_clauses[] = "a.completada = 0 AND a.categoria_compromiso = 'cotizacion'";
            break;
        case 'investigacion':
            $where_clauses[] = "a.completada = 0 AND a.categoria_compromiso = 'investigacion'";
            break;
        case 'visitas':
            $where_clauses[] = "a.completada = 0 AND a.categoria_compromiso = 'visita'";
            break;
        case 'completadas':
            $where_clauses[] = "a.completada = 1";
            break;
        case 'todas':
            // Todas las pendientes
            $where_clauses[] = "a.completada = 0";
            break;
        case 'hoy_urgentes':
        default:
            // Urgentes vencidas o de hoy
            $where_clauses[] = "a.completada = 0 AND (date(a.fecha_vencimiento) <= date('now') OR a.fecha_vencimiento IS NULL OR a.prioridad = 'urgente')";
            break;
    }
}

$sql_tareas = "SELECT a.*, c.nombre as contacto_nombre, c.apellido as contacto_apellido, c.empresa as contacto_empresa, c.telefono as contacto_telefono, c.email as contacto_email, c.prioridad as contacto_prioridad 
               FROM actividades a 
               LEFT JOIN contactos c ON a.contacto_id = c.id 
               WHERE " . implode(' AND ', $where_clauses) . " 
               ORDER BY (CASE a.prioridad WHEN 'urgente' THEN 1 WHEN 'alta' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END) ASC, a.fecha_vencimiento ASC LIMIT 100";

$res_tareas = $db->query($sql_tareas);
$tareas_lista = [];
while ($t = $res_tareas->fetchArray(SQLITE3_ASSOC)) {
    $tareas_lista[] = $t;
}

// Cargar días del mes con sus compromisos
$primer_dia_mes_ts = strtotime(sprintf('%04d-%02d-01', $anio, $mes));
$total_dias_mes = (int)date('t', $primer_dia_mes_ts);
$dia_semana_inicio = (int)date('N', $primer_dia_mes_ts); // 1 = Lunes, 7 = Domingo

// Resumen de tareas por día del mes
$prefijo_mes = sprintf('%04d-%02d', $anio, $mes);
$sql_dias = "SELECT date(fecha_vencimiento) as dia_fecha, 
                    COUNT(*) as total_dia, 
                    SUM(CASE WHEN completada = 0 THEN 1 ELSE 0 END) as pendientes_dia,
                    SUM(CASE WHEN completada = 0 AND (prioridad = 'urgente' OR date(fecha_vencimiento) < date('now')) THEN 1 ELSE 0 END) as urgentes_dia
             FROM actividades 
             WHERE tipo = 'tarea' AND strftime('%Y-%m', fecha_vencimiento) = '$prefijo_mes'
             GROUP BY dia_fecha";

$res_dias = $db->query($sql_dias);
$dias_con_tareas = [];
while ($d = $res_dias->fetchArray(SQLITE3_ASSOC)) {
    $dias_con_tareas[$d['dia_fecha']] = $d;
}

// Lista de contactos para el modal
$contactos_selector = $db->query("SELECT id, nombre, apellido, empresa, telefono FROM contactos ORDER BY nombre ASC");
?>

<div style="max-width:1380px;margin:0 auto">

    <!-- CABECERA PRINCIPAL -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:16px">
        <div>
            <div style="display:flex;align-items:center;gap:10px">
                <h1 style="font-size:24px;font-weight:900;color:var(--fg);margin:0">📅 Calendario & Agenda Comercial de Hoy</h1>
                <span style="background:#fee2e2;color:#dc2626;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:800">
                    ⚡ <?= $total_urgentes ?> Urgentes
                </span>
            </div>
            <p style="color:var(--fg-secondary);font-size:13px;margin-top:4px">
                <strong><?= $fecha_hoy_humana ?></strong> • Checklist diario de llamadas, cotizaciones, investigaciones y compromisos con clientes.
            </p>
        </div>

        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <button type="button" onclick="document.getElementById('modalCompromiso').style.display='flex'" class="btn btn-primary" style="font-weight:800;padding:10px 18px;box-shadow:0 4px 12px rgba(249,115,22,0.25)">
                ➕ Nuevo Compromiso
            </button>
            <?php if (count($tareas_lista) === 0 && $total_urgentes === 0 && $total_hoy === 0): ?>
            <form method="POST" style="margin:0">
                <input type="hidden" name="sembrar_compromisos_demo" value="1">
                <button type="submit" class="btn btn-secondary" style="font-size:12px;font-weight:700">
                    ⚡ Cargar Tareas de Muestra
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- TARJETAS MÉTRICAS DE URGENCIA Y AVANCE DIARIO -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-bottom:24px">
        
        <div class="card" style="padding:18px;border-left:5px solid #dc2626;background:#fff">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:12px;font-weight:800;color:#dc2626;text-transform:uppercase">🔥 Urgentes / Vencidas</span>
                <span style="font-size:20px">⚠️</span>
            </div>
            <div style="font-size:28px;font-weight:900;color:#0f172a;margin-top:6px" id="metric-urgentes"><?= $total_urgentes ?></div>
            <div style="font-size:11px;color:var(--fg-secondary);margin-top:2px">Requieren tu atención inmediata</div>
        </div>

        <div class="card" style="padding:18px;border-left:5px solid #f59e0b;background:#fff">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:12px;font-weight:800;color:#d97706;text-transform:uppercase">🟡 Para Hacer Hoy</span>
                <span style="font-size:20px">📌</span>
            </div>
            <div style="font-size:28px;font-weight:900;color:#0f172a;margin-top:6px" id="metric-hoy"><?= $total_hoy ?></div>
            <div style="font-size:11px;color:var(--fg-secondary);margin-top:2px">Llamadas y tareas del día</div>
        </div>

        <div class="card" style="padding:18px;border-left:5px solid #10b981;background:#fff">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:12px;font-weight:800;color:#059669;text-transform:uppercase">✅ Cumplimiento Hoy</span>
                <span style="font-size:12px;font-weight:800;color:#059669" id="metric-pct"><?= $pct_progreso ?>%</span>
            </div>
            <div style="font-size:28px;font-weight:900;color:#059669;margin-top:6px" id="metric-completadas"><?= $completadas_hoy ?> <span style="font-size:14px;color:var(--fg-secondary);font-weight:600">listas</span></div>
            <div style="background:#e2e8f0;border-radius:10px;height:6px;width:100%;margin-top:8px;overflow:hidden">
                <div id="metric-bar" style="background:#10b981;height:100%;width:<?= $pct_progreso ?>%;transition:width 0.3s ease"></div>
            </div>
        </div>

        <div class="card" style="padding:18px;border-left:5px solid #2c60a4;background:#fff">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:12px;font-weight:800;color:#1e3a8a;text-transform:uppercase">📅 Mes: <?= $meses_nombres[$mes] ?></span>
                <span style="font-size:20px">🗓️</span>
            </div>
            <div style="font-size:28px;font-weight:900;color:#0f172a;margin-top:6px"><?= $total_mes ?></div>
            <div style="font-size:11px;color:var(--fg-secondary);margin-top:2px">Compromisos activos en <?= $anio ?></div>
        </div>

    </div>

    <!-- CONTENEDOR PRINCIPAL EN 2 COLUMNAS (CHECKLIST DIARIO + CALENDARIO VISUAL) -->
    <div style="display:grid;grid-template-columns:1.2fr 0.8fr;gap:24px;align-items:start">

        <!-- ======================================================== -->
        <!-- COLUMNA 1: CHECKLIST DIARIO DE COSAS POR HACER HOY       -->
        <!-- ======================================================== -->
        <div style="display:flex;flex-direction:column;gap:18px">

            <div class="card" style="padding:20px;background:#fff">
                
                <!-- ENCABEZADO DE LA LISTA Y PESTAÑAS DE FILTRO -->
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;border-bottom:1px solid #f1f5f9;padding-bottom:14px">
                    <div>
                        <h2 style="font-size:17px;font-weight:900;margin:0;display:flex;align-items:center;gap:8px">
                            <span>📋 Checklist Diario de Compromisos</span>
                        </h2>
                        <span style="font-size:12px;color:var(--fg-secondary)">
                            <?php if ($filtro_dia_especifico): ?>
                                Filtrando por fecha: <strong><?= date('d/m/Y', strtotime($fecha_sel)) ?></strong> 
                                <a href="?page=calendario" style="color:var(--brand-blue);font-weight:700;margin-left:6px">✕ Ver todo hoy</a>
                            <?php else: ?>
                                Marca la casilla al cumplir cada compromiso para actualizar tu avance en vivo
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- PESTAÑAS DE ACCESO RÁPIDO -->
                    <div style="display:flex;background:#f1f5f9;padding:3px;border-radius:8px;gap:2px;overflow-x:auto">
                        <a href="?page=calendario&filtro=hoy_urgentes" class="btn btn-sm" style="<?= $filtro_modo=='hoy_urgentes'&&!$filtro_dia_especifico?'background:#fff;color:#dc2626;font-weight:800;box-shadow:var(--shadow-sm)':'' ?>padding:4px 10px;font-size:11px">
                            🔥 Hoy & Urgentes
                        </a>
                        <a href="?page=calendario&filtro=llamadas" class="btn btn-sm" style="<?= $filtro_modo=='llamadas'?'background:#fff;color:var(--brand-blue);font-weight:800;box-shadow:var(--shadow-sm)':'' ?>padding:4px 10px;font-size:11px">
                            📞 Llamadas
                        </a>
                        <a href="?page=calendario&filtro=cotizaciones" class="btn btn-sm" style="<?= $filtro_modo=='cotizaciones'?'background:#fff;color:#d97706;font-weight:800;box-shadow:var(--shadow-sm)':'' ?>padding:4px 10px;font-size:11px">
                            📄 Cotizar
                        </a>
                        <a href="?page=calendario&filtro=investigacion" class="btn btn-sm" style="<?= $filtro_modo=='investigacion'?'background:#fff;color:#7c3aed;font-weight:800;box-shadow:var(--shadow-sm)':'' ?>padding:4px 10px;font-size:11px">
                            🔍 Investigar
                        </a>
                        <a href="?page=calendario&filtro=todas" class="btn btn-sm" style="<?= $filtro_modo=='todas'?'background:#fff;color:var(--fg);font-weight:800;box-shadow:var(--shadow-sm)':'' ?>padding:4px 10px;font-size:11px">
                            Todas
                        </a>
                    </div>
                </div>

                <!-- LISTADO INTERACTIVO DE COMPROMISOS -->
                <div id="lista-compromisos-container" style="display:flex;flex-direction:column;gap:12px">
                    <?php if (empty($tareas_lista)): ?>
                        <div style="text-align:center;padding:48px 20px;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1">
                            <span style="font-size:38px;display:block;margin-bottom:8px">🎉</span>
                            <strong style="font-size:15px;color:#0f172a">¡No tienes compromisos pendientes en esta sección!</strong>
                            <p style="font-size:12px;color:var(--fg-secondary);max-width:380px;margin:6px auto 14px auto">
                                Estás al día con tus compromisos comerciales. Añade uno nuevo para registrar una llamada o cotización pendiente.
                            </p>
                            <button type="button" onclick="document.getElementById('modalCompromiso').style.display='flex'" class="btn btn-primary btn-sm">
                                ➕ Programar Nuevo Compromiso
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tareas_lista as $tarea): 
                            $es_comp = (int)$tarea['completada'] === 1;
                            $vence_ts = $tarea['fecha_vencimiento'] ? strtotime($tarea['fecha_vencimiento']) : null;
                            $es_vencida = $vence_ts && $vence_ts < $ahora_ts && !$es_comp;
                            $es_hoy = $vence_ts && date('Y-m-d', $vence_ts) === $hoy_ymd;
                            $cat = $tarea['categoria_compromiso'] ?? 'tarea';
                            $prio = $tarea['prioridad'] ?? 'normal';
                            $tel_wa = limpiar_telefono_whatsapp($tarea['contacto_telefono'] ?? '');
                            $nom_contacto = trim(($tarea['contacto_nombre'] ?? '') . ' ' . ($tarea['contacto_apellido'] ?? ''));

                            // Icono y color por categoría
                            $cat_badge = [
                                'llamada' => ['icono' => '📞', 'txt' => 'Llamar a Cliente', 'bg' => '#eff6ff', 'color' => '#1d4ed8', 'borde' => '#bfdbfe'],
                                'cotizacion' => ['icono' => '📄', 'txt' => 'Pasar Cotización', 'bg' => '#fff7ed', 'color' => '#ea580c', 'borde' => '#fed7aa'],
                                'investigacion' => ['icono' => '🔍', 'txt' => 'Investigación Técnica', 'bg' => '#f5f3ff', 'color' => '#7c3aed', 'borde' => '#ddd6fe'],
                                'visita' => ['icono' => '🎪', 'txt' => 'Visita / Showroom', 'bg' => '#ecfdf5', 'color' => '#059669', 'borde' => '#a7f3d0'],
                                'correo' => ['icono' => '✉️', 'txt' => 'Enviar Correo IA', 'bg' => '#f0f9ff', 'color' => '#0284c7', 'borde' => '#bae6fd'],
                                'tarea' => ['icono' => '⚡', 'txt' => 'Compromiso Comercial', 'bg' => '#f8fafc', 'color' => '#475569', 'borde' => '#e2e8f0']
                            ][$cat] ?? ['icono' => '⚡', 'txt' => 'Tarea', 'bg' => '#f8fafc', 'color' => '#475569', 'borde' => '#e2e8f0'];

                            $borde_izq = $es_vencida || $prio === 'urgente' ? '#ef4444' : ($es_hoy ? '#f59e0b' : '#3b82f6');
                        ?>
                        <div class="tarjeta-compromiso" id="tarjeta-comp-<?= $tarea['id'] ?>" 
                             style="display:flex;gap:14px;padding:14px 16px;background:<?= $es_comp ? '#f8fafc' : '#ffffff' ?>;border:1px solid <?= $es_comp ? '#e2e8f0' : '#cbd5e1' ?>;border-left:5px solid <?= $es_comp ? '#94a3b8' : $borde_izq ?>;border-radius:10px;box-shadow:var(--shadow-sm);align-items:flex-start;transition:all 0.2s ease">
                            
                            <!-- CHECKBOX INTERACTIVO DE 1 CLIC -->
                            <div style="padding-top:2px">
                                <button type="button" class="btn-check-tarea" onclick="toggleTareaAjax(<?= $tarea['id'] ?>, <?= $es_comp ? 0 : 1 ?>)"
                                        style="width:24px;height:24px;border-radius:50%;border:2px solid <?= $es_comp ? '#10b981' : '#94a3b8' ?>;background:<?= $es_comp ? '#10b981' : '#ffffff' ?>;color:#ffffff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;font-weight:900;transition:all 0.15s ease"
                                        title="<?= $es_comp ? 'Clic para reabrir compromiso' : 'Marcar como cumplido' ?>">
                                    <?= $es_comp ? '✓' : '' ?>
                                </button>
                            </div>

                            <!-- CONTENIDO DEL COMPROMISO -->
                            <div style="flex:1">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                                        <!-- BADGE DE CATEGORÍA -->
                                        <span style="font-size:10px;font-weight:800;padding:2px 8px;border-radius:12px;background:<?= $cat_badge['bg'] ?>;color:<?= $cat_badge['color'] ?>;border:1px solid <?= $cat_badge['borde'] ?>">
                                            <?= $cat_badge['icono'] ?> <?= $cat_badge['txt'] ?>
                                        </span>

                                        <!-- BADGE DE URGENCIA -->
                                        <?php if ($es_vencida): ?>
                                            <span style="font-size:10px;font-weight:800;padding:2px 8px;border-radius:12px;background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;animation:pulse 2s infinite">
                                                🚨 VENCIDA
                                            </span>
                                        <?php elseif ($prio === 'urgente'): ?>
                                            <span style="font-size:10px;font-weight:800;padding:2px 8px;border-radius:12px;background:#fee2e2;color:#dc2626;border:1px solid #fca5a5">
                                                🔥 URGENTE
                                            </span>
                                        <?php elseif ($es_hoy): ?>
                                            <span style="font-size:10px;font-weight:800;padding:2px 8px;border-radius:12px;background:#fef3c7;color:#d97706;border:1px solid #fde68a">
                                                🟡 HOY
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- FECHA Y HORA LÍMITE -->
                                    <div style="font-size:11px;font-weight:700;color:<?= $es_vencida ? '#dc2626' : ($es_hoy ? '#d97706' : '#64748b') ?>">
                                        ⏱️ <?= $vence_ts ? date('d/m/Y H:i', $vence_ts) : 'Sin hora límite' ?>
                                    </div>
                                </div>

                                <!-- TÍTULO PRINCIPAL DEL COMPROMISO -->
                                <div style="font-size:14px;font-weight:800;color:#0f172a;margin-top:5px;line-height:1.4;<?= $es_comp ? 'text-decoration:line-through;color:#94a3b8' : '' ?>" id="asunto-comp-<?= $tarea['id'] ?>">
                                    <?= h($tarea['asunto']) ?>
                                </div>

                                <?php if (!empty($tarea['descripcion'])): ?>
                                    <div style="font-size:12px;color:#475569;margin-top:3px;line-height:1.4;white-space:pre-wrap;<?= $es_comp ? 'opacity:0.6' : '' ?>">
                                        <?= nl2br(h($tarea['descripcion'])) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- INFORMACIÓN DEL CLIENTE Y BOTONES DE ACCIÓN RÁPIDA -->
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;flex-wrap:wrap;gap:8px;padding-top:8px;border-top:1px dashed #e2e8f0">
                                    <div style="font-size:12px">
                                        <?php if ($tarea['contacto_id']): ?>
                                            👤 <a href="?page=detalle&id=<?= $tarea['contacto_id'] ?>" style="font-weight:800;color:var(--brand-blue);text-decoration:none">
                                                <?= h($nom_contacto) ?>
                                            </a>
                                            <?php if (!empty($tarea['contacto_empresa'])): ?>
                                                • <strong><?= h($tarea['contacto_empresa']) ?></strong>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:#94a3b8">📌 Compromiso general interno</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- BOTONES DE ACCIÓN DIRECTA -->
                                    <div style="display:flex;gap:6px;align-items:center">
                                        <?php if ($tel_wa): ?>
                                            <a href="https://wa.me/<?= $tel_wa ?>" target="_blank" class="btn btn-sm" style="background:#25d366;color:#fff;font-size:10px;padding:3px 8px;font-weight:800" title="Abrir chat de WhatsApp">
                                                💬 WhatsApp
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($tarea['contacto_id']): ?>
                                            <?php if ($cat === 'cotizacion'): ?>
                                                <a href="?page=nueva_cotizacion&contacto_id=<?= $tarea['contacto_id'] ?>" class="btn btn-sm" style="background:#ea580c;color:#fff;font-size:10px;padding:3px 8px;font-weight:800">
                                                    📄 + Cotizar
                                                </a>
                                            <?php elseif ($cat === 'correo'): ?>
                                                <a href="?page=correos&contacto_id=<?= $tarea['contacto_id'] ?>" class="btn btn-sm" style="background:#2c60a4;color:#fff;font-size:10px;padding:3px 8px;font-weight:800">
                                                    ✉️ Redactar IA
                                                </a>
                                            <?php endif; ?>
                                            <a href="?page=detalle&id=<?= $tarea['contacto_id'] ?>" class="btn btn-secondary btn-sm" style="font-size:10px;padding:3px 8px">
                                                Ficha
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- MINI-FORMULARIO ULTRARRÁPIDO PARA AÑADIR TAREAS DE HOY -->
                <div style="margin-top:20px;padding:14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">
                    <form id="form-tarea-rapida" onsubmit="crearTareaRapidaAjax(event)" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <select id="quick-cat" style="font-size:12px;padding:8px 10px;border-radius:6px;border:1px solid #cbd5e1;background:#fff;font-weight:700">
                            <option value="llamada">📞 Llamar</option>
                            <option value="cotizacion">📄 Cotizar</option>
                            <option value="investigacion">🔍 Investigar</option>
                            <option value="visita">🎪 Visita</option>
                            <option value="correo">✉️ Correo</option>
                            <option value="tarea">⚡ Tarea</option>
                        </select>
                        
                        <input type="text" id="quick-asunto" class="form-control" style="font-size:13px;padding:8px 12px;flex:1;min-width:240px;background:#fff" placeholder="➕ Escribe un compromiso rápido para hoy..." required>

                        <button type="submit" id="btn-quick-add" class="btn btn-primary" style="font-size:12px;font-weight:800;padding:8px 16px;white-space:nowrap">
                            + Agregar a Hoy
                        </button>
                    </form>
                </div>

            </div>

        </div>

        <!-- ======================================================== -->
        <!-- COLUMNA 2: CALENDARIO VISUAL MENSUAL NAVEGABLE            -->
        <!-- ======================================================== -->
        <div style="display:flex;flex-direction:column;gap:18px">

            <div class="card" style="padding:20px;background:#fff">
                
                <!-- NAVEGACIÓN DE MES -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                    <a href="?page=calendario&mes=<?= $mes_anterior ?>&anio=<?= $anio_anterior ?>" class="btn btn-secondary btn-sm" style="padding:5px 10px;font-size:12px">
                        ← <?= $meses_nombres[$mes_anterior] ?>
                    </a>
                    
                    <div style="text-align:center">
                        <strong style="font-size:16px;color:#0f172a;display:block">
                            <?= $meses_nombres[$mes] ?> <?= $anio ?>
                        </strong>
                        <a href="?page=calendario" style="font-size:11px;color:var(--brand-blue);text-decoration:none;font-weight:700">
                            Ir a Hoy
                        </a>
                    </div>

                    <a href="?page=calendario&mes=<?= $mes_siguiente ?>&anio=<?= $anio_siguiente ?>" class="btn btn-secondary btn-sm" style="padding:5px 10px;font-size:12px">
                        <?= $meses_nombres[$mes_siguiente] ?> →
                    </a>
                </div>

                <!-- GRILLA DEL CALENDARIO -->
                <div style="display:grid;grid-template-columns:repeat(7, 1fr);gap:4px;text-align:center">
                    
                    <!-- ENCABEZADOS DE DÍAS DE LA SEMANA (LUN A DOM) -->
                    <?php foreach (['L', 'M', 'M', 'J', 'V', 'S', 'D'] as $d_head): ?>
                        <div style="font-size:11px;font-weight:800;color:#64748b;padding:6px 0;text-transform:uppercase">
                            <?= $d_head ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- CELDAS VACÍAS ANTES DEL PRIMER DÍA DEL MES -->
                    <?php for ($i = 1; $i < $dia_semana_inicio; $i++): ?>
                        <div style="padding:8px 0;background:#f8fafc;border-radius:6px;opacity:0.3"></div>
                    <?php endfor; ?>

                    <!-- DÍAS DEL MES -->
                    <?php 
                    for ($d = 1; $d <= $total_dias_mes; $d++): 
                        $fecha_dia_actual = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
                        $es_celda_hoy = ($fecha_dia_actual === $hoy_ymd);
                        $es_celda_sel = ($fecha_dia_actual === $fecha_sel && $filtro_dia_especifico);
                        $tiene_datos = isset($dias_con_tareas[$fecha_dia_actual]);
                        $datos_dia = $tiene_datos ? $dias_con_tareas[$fecha_dia_actual] : null;
                        $cant_pend = $datos_dia ? (int)$datos_dia['pendientes_dia'] : 0;
                        $cant_urg = $datos_dia ? (int)$datos_dia['urgentes_dia'] : 0;
                    ?>
                        <a href="?page=calendario&mes=<?= $mes ?>&anio=<?= $anio ?>&fecha=<?= $fecha_dia_actual ?>&dia_especifico=1" 
                           style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:48px;padding:4px 2px;border-radius:8px;text-decoration:none;transition:all 0.15s ease;
                                  background:<?= $es_celda_sel ? '#eff6ff' : ($es_celda_hoy ? '#fff7ed' : '#ffffff') ?>;
                                  border:1px solid <?= $es_celda_sel ? 'var(--brand-blue)' : ($es_celda_hoy ? 'var(--accent)' : '#e2e8f0') ?>;
                                  box-shadow:<?= $es_celda_hoy ? '0 0 0 2px rgba(249,115,22,0.2)' : 'none' ?>"
                           title="<?= $fecha_dia_actual ?><?= $cant_pend > 0 ? " ($cant_pend pendientes)" : '' ?>">
                            
                            <span style="font-size:12px;font-weight:<?= $es_celda_hoy || $es_celda_sel ? '900' : '700' ?>;color:<?= $es_celda_hoy ? 'var(--accent)' : ($es_celda_sel ? 'var(--brand-blue)' : '#1e293b') ?>">
                                <?= $d ?>
                            </span>

                            <!-- INDICADOR DE COMPROMISOS DEL DÍA -->
                            <?php if ($cant_pend > 0): ?>
                                <span style="font-size:9px;font-weight:800;padding:1px 5px;border-radius:10px;margin-top:2px;
                                             background:<?= $cant_urg > 0 ? '#fee2e2' : '#fef3c7' ?>;
                                             color:<?= $cant_urg > 0 ? '#dc2626' : '#d97706' ?>;
                                             border:1px solid <?= $cant_urg > 0 ? '#fca5a5' : '#fde68a' ?>">
                                    <?= $cant_pend ?>
                                </span>
                            <?php elseif ($tiene_datos && $datos_dia['total_dia'] > 0): ?>
                                <span style="font-size:8px;color:#10b981;margin-top:2px">✓</span>
                            <?php endif; ?>
                        </a>
                    <?php endfor; ?>

                </div>

                <!-- LEYENDA DEL CALENDARIO -->
                <div style="display:flex;gap:12px;justify-content:center;margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;font-size:11px;color:var(--fg-secondary);flex-wrap:wrap">
                    <span style="display:flex;align-items:center;gap:4px">
                        <span style="width:8px;height:8px;border-radius:50%;background:#ef4444"></span> Urgentes
                    </span>
                    <span style="display:flex;align-items:center;gap:4px">
                        <span style="width:8px;height:8px;border-radius:50%;background:#f59e0b"></span> Pendientes
                    </span>
                    <span style="display:flex;align-items:center;gap:4px">
                        <span style="width:8px;height:8px;border-radius:50%;background:var(--accent)"></span> Día de Hoy
                    </span>
                    <span style="display:flex;align-items:center;gap:4px">
                        <span style="width:8px;height:8px;border-radius:50%;background:#10b981"></span> Completadas
                    </span>
                </div>

            </div>

            <!-- TARJETA DE RECOMENDACIÓN OPERATIVA -->
            <div class="card" style="padding:18px;background:linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);border:1px solid #bfdbfe">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                    <span style="font-size:22px">💡</span>
                    <strong style="font-size:13px;color:#1e3a8a">Consejo Comercial Power Pack:</strong>
                </div>
                <p style="font-size:12px;color:#334155;line-height:1.5;margin:0">
                    Las llamadas a jefes de planta y compras tienen <strong>3 veces más efectividad</strong> si se realizan entre 9:00 AM y 11:30 AM antes de que ingresen a turnos de producción en planta.
                </p>
            </div>

        </div>

    </div>

</div>

<!-- ======================================================== -->
<!-- MODAL: NUEVO COMPROMISO COMERCIAL CON CLIENTE            -->
<!-- ======================================================== -->
<div id="modalCompromiso" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.65);z-index:200;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)">
    <div style="background:#fff;border-radius:var(--radius);max-width:580px;width:100%;padding:26px;box-shadow:var(--shadow-lg);max-height:90vh;overflow-y:auto">
        
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <h2 style="font-size:18px;font-weight:900;margin:0;color:#0f172a">
                ➕ Agendar Compromiso con Cliente
            </h2>
            <button type="button" onclick="document.getElementById('modalCompromiso').style.display='none'" style="font-size:22px;color:#64748b;background:none;border:none;cursor:pointer">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="nueva_tarea" value="1">
            <input type="hidden" name="return_url" value="index.php?page=calendario">

            <!-- CONTACTO ASOCIADO -->
            <div class="form-group" style="margin-bottom:14px">
                <label style="font-weight:800;font-size:12px;color:#0f172a">👤 Cliente / Fábrica Destino</label>
                <select name="contacto_id" class="form-control" style="font-size:13px;font-weight:600;padding:10px">
                    <option value="">— Ninguno / Compromiso general interno —</option>
                    <?php if ($contactos_selector): ?>
                        <?php while ($c_opt = $contactos_selector->fetchArray(SQLITE3_ASSOC)): ?>
                            <option value="<?= $c_opt['id'] ?>">
                                <?= h($c_opt['nombre'] . ' ' . $c_opt['apellido']) ?> <?= $c_opt['empresa'] ? '— ' . h($c_opt['empresa']) : '' ?> (<?= h($c_opt['telefono']) ?>)
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- TIPO DE COMPROMISO -->
            <div class="form-row" style="margin-bottom:14px">
                <div class="form-group">
                    <label style="font-weight:800;font-size:12px;color:#0f172a">🎯 Tipo de Acción</label>
                    <select name="categoria_compromiso" class="form-control" style="font-size:13px;font-weight:700">
                        <option value="llamada">📞 Llamar a Cliente</option>
                        <option value="cotizacion">📄 Pasar Cotización Formal</option>
                        <option value="investigacion">🔍 Investigación Técnica / Planta</option>
                        <option value="visita">🎪 Visita Técnica / Showroom Bogotá</option>
                        <option value="correo">✉️ Enviar Correo / Propuesta IA</option>
                        <option value="tarea">⚡ Tarea General Urgente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label style="font-weight:800;font-size:12px;color:#0f172a">🔥 Prioridad</label>
                    <select name="prioridad" class="form-control" style="font-size:13px;font-weight:700">
                        <option value="urgente" style="color:#dc2626;font-weight:800">🔴 Urgente (Hacer Primero)</option>
                        <option value="alta" style="color:#ea580c;font-weight:800">🟠 Alta Prioridad</option>
                        <option value="normal" selected>🟡 Normal / Programada</option>
                        <option value="baja">⚪ Baja</option>
                    </select>
                </div>
            </div>

            <!-- TÍTULO DEL COMPROMISO -->
            <div class="form-group" style="margin-bottom:14px">
                <label style="font-weight:800;font-size:12px;color:#0f172a">📝 Asunto / ¿Qué toca hacer?</label>
                <input type="text" name="asunto" class="form-control" required style="font-size:13px;padding:10px" placeholder="Ej: Llamar a validar voltaje trifásico para selladora continua...">
            </div>

            <!-- FECHA Y HORA LÍMITE -->
            <div class="form-group" style="margin-bottom:14px">
                <label style="font-weight:800;font-size:12px;color:#0f172a">⏱️ Fecha y Hora Límite</label>
                <input type="datetime-local" name="fecha_vencimiento" class="form-control" required style="font-size:13px;padding:8px" value="<?= date('Y-m-d\TH:i', strtotime('+3 hours')) ?>">
                
                <!-- BOTONES RÁPIDOS DE FECHA -->
                <div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickFecha(0, 11, 0)" style="font-size:10px;padding:2px 8px">Hoy 11:00 AM</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickFecha(0, 15, 0)" style="font-size:10px;padding:2px 8px">Hoy 3:00 PM</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickFecha(1, 10, 0)" style="font-size:10px;padding:2px 8px">Mañana 10:00 AM</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickFecha(2, 14, 0)" style="font-size:10px;padding:2px 8px">En 2 días</button>
                </div>
            </div>

            <!-- NOTAS O DETALLES ADICIONALES -->
            <div class="form-group" style="margin-bottom:18px">
                <label style="font-weight:800;font-size:12px;color:#0f172a">📌 Notas o Detalles Específicos</label>
                <textarea name="descripcion" rows="3" class="form-control" style="font-size:13px;padding:10px" placeholder="Detalles de lo que se acordó con el cliente, dimensiones de bolsa, tipo de producto, etc..."></textarea>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" onclick="document.getElementById('modalCompromiso').style.display='none'" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary" style="font-weight:800;padding:10px 22px">
                    💾 Guardar Compromiso
                </button>
            </div>
        </form>

    </div>
</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
.tarjeta-compromiso:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}
</style>

<script>
// Toggle de tarea con actualización instantánea vía AJAX
async function toggleTareaAjax(tareaId, nuevoEstado) {
    const tarjeta = document.getElementById(`tarjeta-comp-${tareaId}`);
    const asunto = document.getElementById(`asunto-comp-${tareaId}`);
    const btnCheck = tarjeta.querySelector('.btn-check-tarea');

    // Efecto visual inmediato optimista
    if (nuevoEstado === 1) {
        btnCheck.style.background = '#10b981';
        btnCheck.style.borderColor = '#10b981';
        btnCheck.innerHTML = '✓';
        asunto.style.textDecoration = 'line-through';
        asunto.style.color = '#94a3b8';
        tarjeta.style.opacity = '0.6';
    } else {
        btnCheck.style.background = '#ffffff';
        btnCheck.style.borderColor = '#94a3b8';
        btnCheck.innerHTML = '';
        asunto.style.textDecoration = 'none';
        asunto.style.color = '#0f172a';
        tarjeta.style.opacity = '1';
    }

    try {
        const resp = await fetch('api.php?action=toggle_tarea_ajax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tarea_id: tareaId, nuevo_estado: nuevoEstado })
        });
        const data = await resp.json();
        if (data.ok) {
            // Actualizar contadores si están presentes
            if (data.pendientes_hoy !== undefined) {
                const metHoy = document.getElementById('metric-hoy');
                if (metHoy) metHoy.innerText = data.pendientes_hoy;
            }
            if (data.completadas_hoy !== undefined) {
                const metComp = document.getElementById('metric-completadas');
                if (metComp) metComp.innerHTML = `${data.completadas_hoy} <span style="font-size:14px;color:var(--fg-secondary);font-weight:600">listas</span>`;
            }
        }
    } catch (e) {
        console.error('Error al actualizar tarea:', e);
    }
}

// Crear tarea rápida desde el mini-formulario al pie del checklist
async function crearTareaRapidaAjax(e) {
    e.preventDefault();
    const asuntoInput = document.getElementById('quick-asunto');
    const catInput = document.getElementById('quick-cat');
    const btn = document.getElementById('btn-quick-add');
    const asunto = asuntoInput.value.trim();

    if (!asunto) return;

    btn.disabled = true;
    btn.innerText = 'Guardando...';

    try {
        const resp = await fetch('api.php?action=crear_tarea_rapida_ajax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                asunto: asunto,
                categoria_compromiso: catInput.value,
                prioridad: 'alta',
                fecha_vencimiento: '<?= date('Y-m-d') ?> 17:00:00'
            })
        });
        const data = await resp.json();
        if (data.ok) {
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo guardar'));
            btn.disabled = false;
            btn.innerText = '+ Agregar a Hoy';
        }
    } catch (err) {
        alert('Error: ' + err.message);
        btn.disabled = false;
        btn.innerText = '+ Agregar a Hoy';
    }
}

// Helper para rellenar fecha rápida en el modal
function setQuickFecha(diasOffset, hora, minutos) {
    const d = new Date();
    d.setDate(d.getDate() + diasOffset);
    d.setHours(hora);
    d.setMinutes(minutos);
    d.setSeconds(0);

    const pad = (n) => String(n).padStart(2, '0');
    const fechaStr = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    
    const input = document.querySelector('input[name="fecha_vencimiento"]');
    if (input) input.value = fechaStr;
}
</script>
