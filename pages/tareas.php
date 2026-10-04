<?php
$filtro_estado = $_GET['estado'] ?? 'pendientes';

$sql = "SELECT a.*, c.nombre, c.apellido, c.empresa, c.telefono, c.email 
        FROM actividades a 
        LEFT JOIN contactos c ON a.contacto_id = c.id 
        WHERE a.tipo = 'tarea'";

if ($filtro_estado === 'pendientes') {
    $sql .= " AND a.completada = 0 ORDER BY a.fecha_vencimiento ASC";
} elseif ($filtro_estado === 'completadas') {
    $sql .= " AND a.completada = 1 ORDER BY a.fecha DESC";
} else {
    $sql .= " ORDER BY a.completada ASC, a.fecha_vencimiento ASC";
}

$tareas = $db->query($sql);
$total_pendientes = (int)$db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'tarea' AND completada = 0");
$total_completadas = (int)$db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'tarea' AND completada = 1");
$contactos_lista = $db->query("SELECT id, nombre, apellido, empresa FROM contactos ORDER BY nombre ASC");
?>

<div class="page-header">
    <div>
        <h1>Gestor de Tareas & Seguimientos Comerciales</h1>
        <p>No pierdas ninguna oportunidad de venta: llamadas pendientes, cotizaciones y reuniones por realizar</p>
    </div>
    <button onclick="document.getElementById('modalTarea').style.display='flex'" class="btn btn-primary">+ Nueva Tarea</button>
</div>

<div class="table-wrap">
    <div class="table-toolbar">
        <div class="tabs">
            <a href="?page=tareas&estado=pendientes" class="tab <?= $filtro_estado==='pendientes'?'active':'' ?>">
                ⭕ Pendientes (<?= $total_pendientes ?>)
            </a>
            <a href="?page=tareas&estado=completadas" class="tab <?= $filtro_estado==='completadas'?'active':'' ?>">
                ✅ Completadas (<?= $total_completadas ?>)
            </a>
            <a href="?page=tareas&estado=todas" class="tab <?= $filtro_estado==='todas'?'active':'' ?>">
                Todas las tareas
            </a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px">Estado</th>
                <th>Tarea / Compromiso</th>
                <th>Contacto & Empresa</th>
                <th>Fecha Límite</th>
                <th>Canal Rápido</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $hay_tareas = false;
            $ahora = time();
            while($t = $tareas->fetchArray(SQLITE3_ASSOC)): 
                $hay_tareas = true;
                $vence_ts = $t['fecha_vencimiento'] ? strtotime($t['fecha_vencimiento']) : null;
                $es_vencida = $vence_ts && $vence_ts < $ahora && !$t['completada'];
                $tel_wa = limpiar_telefono_whatsapp($t['telefono']);
            ?>
            <tr style="<?= $t['completada'] ? 'opacity:0.6;background:#fafafa' : '' ?>">
                <td>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="toggle_tarea" value="1">
                        <input type="hidden" name="tarea_id" value="<?= $t['id'] ?>">
                        <input type="hidden" name="nuevo_estado" value="<?= $t['completada'] ? 0 : 1 ?>">
                        <input type="hidden" name="return_url" value="index.php?page=tareas&estado=<?= $filtro_estado ?>">
                        <button type="submit" title="<?= $t['completada'] ? 'Reabrir tarea' : 'Marcar como completada' ?>" 
                                style="width:22px;height:22px;border-radius:50%;border:2px solid <?= $t['completada'] ? 'var(--success)' : 'var(--border)' ?>;background:<?= $t['completada'] ? 'var(--success)' : '#fff' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;cursor:pointer">
                            <?= $t['completada'] ? '✓' : '' ?>
                        </button>
                    </form>
                </td>
                <td>
                    <div style="font-weight:700;font-size:14px;<?= $t['completada'] ? 'text-decoration:line-through' : '' ?>">
                        <?= h($t['asunto']) ?>
                    </div>
                    <?php if($t['descripcion']): ?>
                    <div style="font-size:12px;color:var(--fg-secondary);margin-top:2px"><?= nl2br(h($t['descripcion'])) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($t['nombre']): ?>
                    <a href="?page=detalle&id=<?= $t['contacto_id'] ?>" style="font-weight:600;color:var(--email)">
                        <?= h($t['nombre'] . ' ' . $t['apellido']) ?>
                    </a>
                    <div style="font-size:12px;color:var(--fg-secondary)"><?= h($t['empresa']) ?></div>
                    <?php else: ?>
                    <span style="color:var(--fg-secondary)">— Sin contacto —</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($vence_ts): ?>
                    <span style="font-weight:600;color:<?= $es_vencida ? 'var(--danger)' : 'var(--fg)' ?>">
                        <?= $es_vencida ? '⚠️ Vencida: ' : '' ?><?= date('d/m/Y H:i', $vence_ts) ?>
                    </span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px">
                        <?php if($tel_wa): ?>
                        <a href="https://wa.me/<?= $tel_wa ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="padding:4px 8px;font-size:11px">💬 WhatsApp</a>
                        <?php endif; ?>
                        <?php if($t['contacto_id']): ?>
                        <a href="?page=detalle&id=<?= $t['contacto_id'] ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11px">Ver Ficha</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>

            <?php if(!$hay_tareas): ?>
            <tr>
                <td colspan="5" style="text-align:center;padding:50px 20px;color:var(--fg-secondary)">
                    No hay tareas en esta sección.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL NUEVA TAREA -->
<div id="modalTarea" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:200;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)">
    <div style="background:#fff;border-radius:var(--radius);max-width:550px;width:100%;padding:24px;box-shadow:var(--shadow-lg)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <h2 style="font-size:18px;font-weight:800">➕ Programar Tarea Comercial</h2>
            <button onclick="document.getElementById('modalTarea').style.display='none'" style="font-size:20px;color:#64748b;background:none">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="nueva_tarea" value="1">
            <div class="form-group">
                <label>Contacto Asociado (Opcional)</label>
                <select name="contacto_id">
                    <option value="">— Ninguno / Tarea general —</option>
                    <?php while($cl = $contactos_lista->fetchArray(SQLITE3_ASSOC)): ?>
                    <option value="<?= $cl['id'] ?>"><?= h($cl['nombre'] . ' ' . $cl['apellido'] . ' (' . $cl['empresa'] . ')') ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Título de la Tarea</label>
                <input type="text" name="asunto" placeholder="Ej: Llamar para validar presupuesto de paletizadora" required>
            </div>
            <div class="form-group">
                <label>Fecha y Hora Límite</label>
                <input type="datetime-local" name="fecha_vencimiento" value="<?= date('Y-m-d\TH:i', strtotime('+1 day 09:00')) ?>" required>
            </div>
            <div class="form-group">
                <label>Descripción / Instrucciones</label>
                <textarea name="descripcion" rows="3" placeholder="Puntos a tratar o información clave..."></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
                <button type="button" onclick="document.getElementById('modalTarea').style.display='none'" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Tarea</button>
            </div>
        </form>
    </div>
</div>
