<?php
$total_contactos = $db->querySingle("SELECT COUNT(*) FROM contactos");
$leads = $db->querySingle("SELECT COUNT(*) FROM contactos WHERE etapa='lead'");
$en_negociacion = $db->querySingle("SELECT COUNT(*) FROM contactos WHERE etapa IN ('cotizacion','negociacion')");
$ganados = $db->querySingle("SELECT COUNT(*) FROM contactos WHERE etapa='ganado'");

$total_pipeline_monto = $db->querySingle("SELECT COALESCE(SUM(monto), 0) FROM negocios WHERE etapa NOT IN ('ganado', 'perdido')");
$total_ganado_monto = $db->querySingle("SELECT COALESCE(SUM(monto), 0) FROM negocios WHERE etapa = 'ganado'");

// Tareas pendientes comerciales
$tareas_hoy = $db->query("SELECT a.*, c.nombre, c.apellido, c.empresa, c.telefono 
    FROM actividades a 
    LEFT JOIN contactos c ON a.contacto_id = c.id 
    WHERE a.tipo = 'tarea' AND a.completada = 0 
    ORDER BY a.fecha_vencimiento ASC LIMIT 5");

$total_tareas_pendientes = $db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'tarea' AND completada = 0");

// Actividad reciente multidisciplinaria (WhatsApp, Correo, Llamadas)
$ultimas = $db->query("SELECT a.*, c.nombre, c.apellido, c.empresa 
    FROM actividades a 
    LEFT JOIN contactos c ON a.contacto_id = c.id 
    ORDER BY a.fecha DESC LIMIT 6");

// Próximos cierres
$proximos = $db->query("SELECT n.*, c.nombre as c_nombre, c.apellido as c_apellido, c.empresa 
    FROM negocios n 
    LEFT JOIN contactos c ON n.contacto_id = c.id 
    WHERE n.etapa NOT IN ('ganado', 'perdido') 
    ORDER BY n.fecha_cierre ASC LIMIT 4");

$prioridad_alta_count = (int)$db->querySingle("SELECT COUNT(*) FROM contactos WHERE prioridad = 'alta'");
?>

<div class="page-header">
    <div>
        <h1>Dashboard Comercial</h1>
        <p>Resumen general de prospectos, negocios y seguimientos pendientes</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="?page=nuevo" class="btn btn-primary">+ Nuevo Contacto</a>
        <a href="?page=nuevo_negocio" class="btn btn-secondary">+ Nueva Oportunidad</a>
    </div>
</div>

<!-- TARJETAS KPI ESTILO HUBSPOT -->
<div class="cards-grid">
    <div class="metric-card" style="border-top:4px solid var(--accent)">
        <div class="label">Total Prospectos</div>
        <div class="value"><?= number_format($total_contactos) ?></div>
        <div class="sub">
            <?= $leads ?> leads • <a href="?page=contactos&prioridad=alta" style="color:#dc2626;font-weight:700">🔥 <?= $prioridad_alta_count ?> Alta Prioridad →</a>
        </div>
    </div>
    <div class="metric-card" style="border-top:4px solid var(--blue)">
        <div class="label">Pipeline Activo</div>
        <div class="value">$<?= number_format($total_pipeline_monto, 0) ?></div>
        <div class="sub"><?= $en_negociacion ?> tratos en cotización o negociación</div>
    </div>
    <div class="metric-card" style="border-top:4px solid var(--success)">
        <div class="label">Ventas Ganadas</div>
        <div class="value" style="color:var(--success)">$<?= number_format($total_ganado_monto, 0) ?></div>
        <div class="sub"><?= $ganados ?> clientes cerrados con éxito</div>
    </div>
    <div class="metric-card" style="border-top:4px solid <?= $total_tareas_pendientes > 0 ? '#ef4444' : '#64748b' ?>">
        <div class="label">📅 Agenda & Compromisos Hoy</div>
        <div class="value" style="color:<?= $total_tareas_pendientes > 0 ? '#dc2626' : 'inherit' ?>"><?= $total_tareas_pendientes ?></div>
        <div class="sub"><a href="?page=calendario" style="color:var(--brand-blue);font-weight:700">Ver Agenda & Calendario →</a></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.2fr 0.8fr;gap:24px;margin-bottom:24px">

    <!-- SECCIÓN: Tareas y Seguimientos de Hoy (Prioridad Comercial) -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);padding:22px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div>
                <h3 style="font-size:15px;font-weight:800">📅 Agenda de Hoy & Compromisos Prioritarios</h3>
                <p style="font-size:12px;color:var(--fg-secondary)">Llamadas, cotizaciones e investigaciones programadas para hoy</p>
            </div>
            <a href="?page=calendario" class="btn btn-secondary btn-sm" style="font-weight:700">📅 Ver Calendario Completo</a>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px">
            <?php 
            $hay_tareas = false;
            while($t = $tareas_hoy->fetchArray(SQLITE3_ASSOC)): 
                $hay_tareas = true;
                $tel_wa = limpiar_telefono_whatsapp($t['telefono']);
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fcfcfd">
                <div style="display:flex;align-items:center;gap:12px">
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="toggle_tarea" value="1">
                        <input type="hidden" name="tarea_id" value="<?= $t['id'] ?>">
                        <input type="hidden" name="nuevo_estado" value="1">
                        <input type="hidden" name="return_url" value="index.php?page=dashboard">
                        <button type="submit" title="Marcar como realizada" style="width:20px;height:20px;border-radius:50%;border:2px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer"></button>
                    </form>
                    <div>
                        <div style="font-weight:700;font-size:13px"><?= h($t['asunto']) ?></div>
                        <div style="font-size:12px;color:var(--fg-secondary);margin-top:2px">
                            <?php if($t['nombre']): ?>
                            <a href="?page=detalle&id=<?= $t['contacto_id'] ?>" style="color:var(--email);font-weight:600">
                                <?= h($t['nombre'] . ' ' . $t['apellido']) ?> (<?= h($t['empresa']) ?>)
                            </a>
                            <?php else: ?>
                            Sin contacto asociado
                            <?php endif; ?>
                            <?php if($t['fecha_vencimiento']): ?>
                            • ⏰ <span style="color:var(--warning);font-weight:600"><?= date('d/m/Y H:i', strtotime($t['fecha_vencimiento'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:6px">
                    <?php if($tel_wa): ?>
                    <a href="https://wa.me/<?= $tel_wa ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="padding:4px 8px;font-size:11px" title="Escribir por WhatsApp">💬</a>
                    <?php endif; ?>
                    <a href="?page=detalle&id=<?= $t['contacto_id'] ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11px">Ficha</a>
                </div>
            </div>
            <?php endwhile; ?>

            <?php if(!$hay_tareas): ?>
            <div style="text-align:center;padding:30px 10px;color:var(--fg-secondary);font-size:13px">
                🎉 ¡Estás al día! No tienes tareas comerciales pendientes en este momento.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECCIÓN: Próximos Cierres de Venta -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);padding:22px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="font-size:15px;font-weight:800">🎯 Tratos Próximos a Cerrar</h3>
            <a href="?page=pipeline" class="btn btn-secondary btn-sm">Ver Pipeline</a>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px">
            <?php 
            $hay_cierres = false;
            while($n = $proximos->fetchArray(SQLITE3_ASSOC)): 
                $hay_cierres = true;
            ?>
            <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);display:flex;justify-content:space-between;align-items:center">
                <div>
                    <div style="font-weight:700;font-size:13px"><?= h($n['nombre']) ?></div>
                    <div style="font-size:12px;color:var(--fg-secondary)"><?= h($n['empresa'] ?: 'Sin empresa') ?> • Fecha: <?= $n['fecha_cierre'] ?></div>
                </div>
                <div style="text-align:right">
                    <div style="font-weight:800;font-size:14px;color:var(--fg)">$<?= number_format($n['monto'], 0) ?></div>
                    <div style="font-size:11px"><?= etapa_badge($n['etapa']) ?></div>
                </div>
            </div>
            <?php endwhile; ?>
            <?php if(!$hay_cierres): ?>
            <div style="text-align:center;padding:24px 0;color:var(--fg-secondary);font-size:12px">
                No hay tratos en negociación activa.
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- SECCIÓN: Actividad Comercial Reciente (WhatsApp, Correo, Llamadas) -->
<div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);padding:22px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
        <h3 style="font-size:15px;font-weight:800">⚡ Historial Reciente de Interacciones (WhatsApp, Correo y Llamadas)</h3>
        <span style="font-size:12px;color:var(--fg-secondary)">Últimos movimientos registrados</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:14px">
        <?php while($a = $ultimas->fetchArray(SQLITE3_ASSOC)): ?>
        <div style="border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;display:flex;gap:12px;background:#fcfcfd">
            <div style="font-size:20px"><?= tipo_actividad_icono($a['tipo']) ?></div>
            <div style="flex:1">
                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div style="font-weight:700;font-size:13px"><?= h($a['asunto']) ?></div>
                    <span style="font-size:11px;color:var(--fg-secondary)"><?= date('d M, H:i', strtotime($a['fecha'])) ?></span>
                </div>
                <?php if($a['nombre']): ?>
                <div style="font-size:12px;color:var(--email);font-weight:600;margin-top:2px">
                    <a href="?page=detalle&id=<?= $a['contacto_id'] ?>"><?= h($a['nombre'] . ' ' . $a['apellido']) ?> (<?= h($a['empresa']) ?>)</a>
                </div>
                <?php endif; ?>
                <?php if($a['descripcion']): ?>
                <div style="font-size:12px;color:var(--fg-secondary);margin-top:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                    <?= h($a['descripcion']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>