<?php
$q = trim($_GET['q'] ?? '');
$etapa_filtro = trim($_GET['etapa'] ?? '');
$sector_filtro = trim($_GET['sector'] ?? '');
$fuente_filtro = trim($_GET['fuente'] ?? '');
$prioridad_filtro = trim($_GET['prioridad'] ?? '');
$orden = trim($_GET['orden'] ?? 'prioridad_desc');

$sql = "SELECT * FROM contactos WHERE 1=1";
if ($q !== '') {
    $q_escaped = SQLite3::escapeString($q);
    $sql .= " AND (nombre LIKE '%$q_escaped%' OR apellido LIKE '%$q_escaped%' OR empresa LIKE '%$q_escaped%' OR email LIKE '%$q_escaped%' OR telefono LIKE '%$q_escaped%')";
}
if ($etapa_filtro !== '') {
    $sql .= " AND etapa = '" . SQLite3::escapeString($etapa_filtro) . "'";
}
if ($sector_filtro !== '') {
    $sql .= " AND sector = '" . SQLite3::escapeString($sector_filtro) . "'";
}
if ($fuente_filtro !== '') {
    $sql .= " AND fuente LIKE '%" . SQLite3::escapeString($fuente_filtro) . "%'";
}
if ($prioridad_filtro !== '') {
    if ($prioridad_filtro === 'media') {
        $sql .= " AND (prioridad = 'media' OR prioridad IS NULL OR prioridad = '')";
    } else {
        $sql .= " AND prioridad = '" . SQLite3::escapeString($prioridad_filtro) . "'";
    }
}

// Ordenación inteligente: Por defecto los clientes de alta prioridad van primero
switch ($orden) {
    case 'prioridad_asc':
        $sql .= " ORDER BY CASE WHEN prioridad = 'baja' THEN 1 WHEN prioridad = 'media' OR prioridad IS NULL OR prioridad = '' THEN 2 WHEN prioridad = 'alta' THEN 3 ELSE 4 END ASC, ultima_actividad DESC";
        break;
    case 'recientes':
        $sql .= " ORDER BY ultima_actividad DESC";
        break;
    case 'interes':
        $sql .= " ORDER BY interes DESC, ultima_actividad DESC";
        break;
    case 'nombre':
        $sql .= " ORDER BY nombre ASC";
        break;
    case 'prioridad_desc':
    default:
        $sql .= " ORDER BY CASE WHEN prioridad = 'alta' THEN 1 WHEN prioridad = 'media' OR prioridad IS NULL OR prioridad = '' THEN 2 WHEN prioridad = 'baja' THEN 3 ELSE 4 END ASC, ultima_actividad DESC";
        break;
}

$contactos = $db->query($sql);

$total_count = (int)$db->querySingle("SELECT COUNT(*) FROM contactos");
$count_alta = (int)$db->querySingle("SELECT COUNT(*) FROM contactos WHERE prioridad = 'alta'");
$count_media = (int)$db->querySingle("SELECT COUNT(*) FROM contactos WHERE prioridad = 'media' OR prioridad IS NULL OR prioridad = ''");
$count_baja = (int)$db->querySingle("SELECT COUNT(*) FROM contactos WHERE prioridad = 'baja'");
$count_demos_contactos = (int)$db->querySingle("SELECT COUNT(*) FROM contactos WHERE fuente = 'Datos de Prueba (Demo)'");
$sectores = $db->query("SELECT DISTINCT sector FROM contactos WHERE sector IS NOT NULL AND sector != '' ORDER BY sector ASC");
?>

<div class="page-header">
    <div>
        <h1>Directorio de Contactos</h1>
        <p>Administra tus prospectos, jerarquía de prioridad comercial y seguimiento</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="?page=importar" class="btn btn-primary btn-sm" style="background:#2c60a4" title="Subir base de datos de feria o archivo Excel">📤 Subir Excel / CSV (Feria)</a>
        <a href="?page=exportar_contactos&delimitador=coma" class="btn btn-secondary btn-sm" title="Descargar archivo CSV estándar delimitado por comas">📥 CSV (Comas ,)</a>
        <a href="?page=exportar_contactos&delimitador=puntocoma" class="btn btn-secondary btn-sm" title="Descargar archivo CSV optimizado para Microsoft Excel">📥 Excel (Punto y coma ;)</a>
        <a href="?page=nuevo" class="btn btn-secondary btn-sm">+ Nuevo Contacto</a>
    </div>
</div>

<?php if ($count_demos_contactos > 0): ?>
<div class="alert alert-info" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;flex-wrap:wrap;gap:10px">
    <span>🧪 <strong>Modo Demostración Activo:</strong> Hay <?= $count_demos_contactos ?> prospectos de prueba cargados. Puedes usarlos para simular cotizaciones y redacción con IA.</span>
    <form method="POST" style="margin:0" onsubmit="return confirm('¿Deseas eliminar los <?= $count_demos_contactos ?> prospectos de demostración para dejar la base de datos limpia?');">
        <input type="hidden" name="borrar_datos_demo" value="1">
        <button type="submit" class="btn btn-sm" style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;font-weight:700;padding:5px 12px;font-size:11px">
            🗑️ Borrar los <?= $count_demos_contactos ?> Clientes Demo
        </button>
    </form>
</div>
<?php endif; ?>

<?php if ($fuente_filtro): ?>
<div class="alert alert-info" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af">
    <span>🎪 Mostrando prospectos del evento: <strong><?= h($fuente_filtro) ?></strong></span>
    <a href="?page=contactos" style="color:#1d4ed8;font-weight:700;font-size:12px;text-decoration:underline">Ver todos los contactos ✕</a>
</div>
<?php endif; ?>

<!-- BOTONES RÁPIDOS DE PRIORIDAD COMERCIAL -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;background:#fff;padding:12px 18px;border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow-sm)">
    <span style="font-size:12px;font-weight:800;color:var(--fg-secondary);text-transform:uppercase;letter-spacing:0.5px">🎯 Atacar por Prioridad:</span>
    <a href="?page=contactos<?= $q ? '&q='.urlencode($q) : '' ?><?= $etapa_filtro ? '&etapa='.urlencode($etapa_filtro) : '' ?><?= $sector_filtro ? '&sector='.urlencode($sector_filtro) : '' ?>&orden=<?= urlencode($orden) ?>" 
       class="btn btn-sm" 
       style="<?= empty($prioridad_filtro) ? 'background:#0f172a;color:#fff;font-weight:800' : 'background:#fff;border:1px solid #cbd5e1;color:#475569' ?>">
        🔘 Todos (<?= $total_count ?>)
    </a>
    <a href="?page=contactos&prioridad=alta<?= $q ? '&q='.urlencode($q) : '' ?><?= $etapa_filtro ? '&etapa='.urlencode($etapa_filtro) : '' ?><?= $sector_filtro ? '&sector='.urlencode($sector_filtro) : '' ?>&orden=<?= urlencode($orden) ?>" 
       class="btn btn-sm" 
       style="<?= $prioridad_filtro === 'alta' ? 'background:#dc2626;color:#fff;font-weight:800;border:1px solid #b91c1c;box-shadow:0 2px 4px rgba(220,38,38,0.25)' : 'background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;font-weight:700' ?>"
       title="Prospectos más importantes, con presupuesto asignado o cierre inminente">
        🔥 Alta Prioridad (<?= $count_alta ?>)
    </a>
    <a href="?page=contactos&prioridad=media<?= $q ? '&q='.urlencode($q) : '' ?><?= $etapa_filtro ? '&etapa='.urlencode($etapa_filtro) : '' ?><?= $sector_filtro ? '&sector='.urlencode($sector_filtro) : '' ?>&orden=<?= urlencode($orden) ?>" 
       class="btn btn-sm" 
       style="<?= $prioridad_filtro === 'media' ? 'background:#d97706;color:#fff;font-weight:800;border:1px solid #b45309' : 'background:#fef3c7;border:1px solid #fde68a;color:#92400e;font-weight:700' ?>"
       title="Prospectos en seguimiento regular y evaluación de maquinaria">
        🟡 Media (<?= $count_media ?>)
    </a>
    <a href="?page=contactos&prioridad=baja<?= $q ? '&q='.urlencode($q) : '' ?><?= $etapa_filtro ? '&etapa='.urlencode($etapa_filtro) : '' ?><?= $sector_filtro ? '&sector='.urlencode($sector_filtro) : '' ?>&orden=<?= urlencode($orden) ?>" 
       class="btn btn-sm" 
       style="<?= $prioridad_filtro === 'baja' ? 'background:#475569;color:#fff;font-weight:800;border:1px solid #334155' : 'background:#f1f5f9;border:1px solid #cbd5e1;color:#475569;font-weight:700' ?>"
       title="Prospectos fríos para futuras campañas">
        ⚪ Baja / En Espera (<?= $count_baja ?>)
    </a>
</div>

<div class="table-wrap">
    <div class="table-toolbar">
        <div class="tabs" style="overflow-x:auto;max-width:100%">
            <a href="?page=contactos<?= $q ? '&q='.urlencode($q) : '' ?><?= $prioridad_filtro ? '&prioridad='.urlencode($prioridad_filtro) : '' ?>&orden=<?= urlencode($orden) ?>" class="tab <?= !$etapa_filtro ? 'active' : '' ?>">Todos (<?= $total_count ?>)</a>
            <?php foreach($pipeline_etapas as $e): ?>
            <a href="?page=contactos&etapa=<?= $e['id'] ?><?= $q ? '&q='.urlencode($q) : '' ?><?= $prioridad_filtro ? '&prioridad='.urlencode($prioridad_filtro) : '' ?>&orden=<?= urlencode($orden) ?>" class="tab <?= $etapa_filtro==$e['id'] ? 'active' : '' ?>">
                <?= $e['nombre'] ?>
            </a>
            <?php endforeach; ?>
        </div>

        <form style="display:flex;gap:8px;flex-wrap:wrap" method="GET">
            <input type="hidden" name="page" value="contactos">
            <?php if($q): ?><input type="hidden" name="q" value="<?= h($q) ?>"><?php endif; ?>
            <?php if($etapa_filtro): ?><input type="hidden" name="etapa" value="<?= h($etapa_filtro) ?>"><?php endif; ?>
            <?php if($prioridad_filtro): ?><input type="hidden" name="prioridad" value="<?= h($prioridad_filtro) ?>"><?php endif; ?>

            <select name="orden" onchange="this.form.submit()" style="padding:7px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:12px;font-weight:700;background:#fff" title="Cambiar orden de visualización">
                <option value="prioridad_desc" <?= $orden==='prioridad_desc'?'selected':'' ?>>⚡ Alta Prioridad Primero</option>
                <option value="prioridad_asc" <?= $orden==='prioridad_asc'?'selected':'' ?>>⚪ Baja Prioridad Primero</option>
                <option value="recientes" <?= $orden==='recientes'?'selected':'' ?>>🕒 Más Recientes</option>
                <option value="interes" <?= $orden==='interes'?'selected':'' ?>>⭐ Mayor Interés</option>
                <option value="nombre" <?= $orden==='nombre'?'selected':'' ?>>🔤 Nombre (A-Z)</option>
            </select>

            <select name="sector" onchange="this.form.submit()" style="padding:7px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:12px;font-weight:600;background:#fff">
                <option value="">Todos los sectores</option>
                <?php while($s = $sectores->fetchArray(SQLITE3_ASSOC)): ?>
                <option value="<?= h($s['sector']) ?>" <?= $sector_filtro==$s['sector']?'selected':'' ?>><?= h(ucwords($s['sector'])) ?></option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Contacto & Empresa</th>
                <th style="min-width:110px">Prioridad</th>
                <th>Canales Rápidos</th>
                <th>Ciudad</th>
                <th>Sector</th>
                <th>Etapa Comercial</th>
                <th>Interés</th>
                <th>Última Actividad</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $hay_contactos = false;
            while($c = $contactos->fetchArray(SQLITE3_ASSOC)): 
                $hay_contactos = true;
                $tel_wa = limpiar_telefono_whatsapp($c['telefono']);
                $prio = strtolower(trim($c['prioridad'] ?? 'media'));
                if (!$prio) $prio = 'media';
            ?>
            <tr id="fila-contacto-<?= $c['id'] ?>">
                <td>
                    <div style="display:flex;align-items:center;gap:12px">
                        <?= avatar_iniciales($c['nombre'], $c['apellido']) ?>
                        <div>
                            <a href="?page=detalle&id=<?= $c['id'] ?>" class="link" style="font-weight:700;font-size:14px;color:var(--fg)">
                                <?= h($c['nombre'] . ' ' . $c['apellido']) ?>
                            </a>
                            <div style="font-size:12px;color:var(--fg-secondary)">
                                <?= h($c['cargo'] ?: 'Contacto') ?> <?= $c['empresa'] ? 'en <strong>' . h($c['empresa']) . '</strong>' : '' ?>
                                <?php if (!empty($c['fuente'])): ?>
                                <span style="display:inline-block;padding:1px 6px;border-radius:4px;background:#eff6ff;color:#2c60a4;font-size:10px;font-weight:700;margin-left:4px">🎪 <?= h($c['fuente']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <select onchange="cambiarPrioridadRapida(<?= $c['id'] ?>, this.value, this)" 
                            style="padding:4px 8px;font-size:11px;font-weight:800;border-radius:6px;cursor:pointer;outline:none;transition:all 0.2s ease;
                                   border:1px solid <?= $prio==='alta'?'#fca5a5':($prio==='baja'?'#cbd5e1':'#fde68a') ?>;
                                   background:<?= $prio==='alta'?'#fee2e2':($prio==='baja'?'#f1f5f9':'#fef3c7') ?>;
                                   color:<?= $prio==='alta'?'#991b1b':($prio==='baja'?'#475569':'#92400e') ?>"
                            title="Haz clic para cambiar la prioridad al instante">
                        <option value="alta" <?= $prio==='alta'?'selected':'' ?>>🔥 Alta</option>
                        <option value="media" <?= $prio==='media'?'selected':'' ?>>🟡 Media</option>
                        <option value="baja" <?= $prio==='baja'?'selected':'' ?>>⚪ Baja</option>
                    </select>
                </td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center">
                        <?php if($tel_wa): ?>
                        <a href="?page=detalle&id=<?= $c['id'] ?>&accion=whatsapp" class="btn btn-whatsapp btn-sm" style="padding:4px 8px;font-size:11px" title="Abrir y enviar WhatsApp">
                            💬 WhatsApp
                        </a>
                        <?php endif; ?>
                        <?php if($c['email']): ?>
                        <a href="?page=detalle&id=<?= $c['id'] ?>&accion=email" class="btn btn-email btn-sm" style="padding:4px 8px;font-size:11px" title="Redactar correo">
                            ✉️
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
                <td><?= h($c['ciudad'] ?: '—') ?></td>
                <td><span style="font-size:12px;color:var(--fg-secondary)"><?= h(ucwords($c['sector'] ?: 'General')) ?></span></td>
                <td><?= etapa_badge($c['etapa']) ?></td>
                <td><?= estrellas($c['interes']) ?></td>
                <td>
                    <span style="font-size:12px;color:var(--fg-secondary)">
                        <?= $c['ultima_actividad'] ? date('d/m/Y H:i', strtotime($c['ultima_actividad'])) : '—' ?>
                    </span>
                </td>
                <td>
                    <a href="?page=detalle&id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:12px">
                        Ver Ficha →
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>

            <?php if(!$hay_contactos): ?>
            <tr>
                <td colspan="9" style="text-align:center;padding:50px 20px;color:var(--fg-secondary)">
                    No se encontraron contactos con los filtros seleccionados.
                    <div style="margin-top:10px">
                        <a href="?page=nuevo" class="btn btn-primary btn-sm">+ Agregar Nuevo Contacto</a>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function cambiarPrioridadRapida(id, prioridad, el) {
    if (el) el.style.opacity = '0.5';

    fetch('api.php?action=cambiar_prioridad', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id, prioridad: prioridad })
    })
    .then(r => r.json())
    .then(data => {
        if (el) {
            el.style.opacity = '1';
            if (data.ok) {
                if (prioridad === 'alta') {
                    el.style.background = '#fee2e2';
                    el.style.color = '#991b1b';
                    el.style.borderColor = '#fca5a5';
                } else if (prioridad === 'baja') {
                    el.style.background = '#f1f5f9';
                    el.style.color = '#475569';
                    el.style.borderColor = '#cbd5e1';
                } else {
                    el.style.background = '#fef3c7';
                    el.style.color = '#92400e';
                    el.style.borderColor = '#fde68a';
                }
            } else {
                alert('No se pudo actualizar: ' + (data.error || 'Error desconocido'));
            }
        }
    })
    .catch(err => {
        if (el) el.style.opacity = '1';
        alert('Error de conexión: ' + err.message);
    });
}
</script>