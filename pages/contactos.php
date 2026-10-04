<?php
$q = trim($_GET['q'] ?? '');
$etapa_filtro = trim($_GET['etapa'] ?? '');
$sector_filtro = trim($_GET['sector'] ?? '');

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
$sql .= " ORDER BY ultima_actividad DESC";
$contactos = $db->query($sql);

$total_count = $db->querySingle("SELECT COUNT(*) FROM contactos");
$sectores = $db->query("SELECT DISTINCT sector FROM contactos WHERE sector IS NOT NULL AND sector != '' ORDER BY sector ASC");
?>

<div class="page-header">
    <div>
        <h1>Directorio de Contactos</h1>
        <p>Administra tus prospectos, historial y canales de comunicación directa</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="?page=exportar_contactos" class="btn btn-secondary btn-sm" title="Descargar en Excel">📥 Exportar CSV</a>
        <a href="?page=nuevo" class="btn btn-primary btn-sm">+ Nuevo Contacto</a>
    </div>
</div>

<div class="table-wrap">
    <div class="table-toolbar">
        <div class="tabs" style="overflow-x:auto;max-width:100%">
            <a href="?page=contactos<?= $q ? '&q='.urlencode($q) : '' ?>" class="tab <?= !$etapa_filtro ? 'active' : '' ?>">Todos (<?= $total_count ?>)</a>
            <?php foreach($pipeline_etapas as $e): ?>
            <a href="?page=contactos&etapa=<?= $e['id'] ?><?= $q ? '&q='.urlencode($q) : '' ?>" class="tab <?= $etapa_filtro==$e['id'] ? 'active' : '' ?>">
                <?= $e['nombre'] ?>
            </a>
            <?php endforeach; ?>
        </div>

        <form style="display:flex;gap:8px" method="GET">
            <input type="hidden" name="page" value="contactos">
            <?php if($etapa_filtro): ?><input type="hidden" name="etapa" value="<?= h($etapa_filtro) ?>"><?php endif; ?>
            <select name="sector" onchange="this.form.submit()" style="padding:7px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:12px;font-weight:600">
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
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:12px">
                        <?= avatar_iniciales($c['nombre'], $c['apellido']) ?>
                        <div>
                            <a href="?page=detalle&id=<?= $c['id'] ?>" class="link" style="font-weight:700;font-size:14px;color:var(--fg)">
                                <?= h($c['nombre'] . ' ' . $c['apellido']) ?>
                            </a>
                            <div style="font-size:12px;color:var(--fg-secondary)">
                                <?= h($c['cargo'] ?: 'Contacto') ?> <?= $c['empresa'] ? 'en <strong>' . h($c['empresa']) . '</strong>' : '' ?>
                            </div>
                        </div>
                    </div>
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
                <td colspan="8" style="text-align:center;padding:50px 20px;color:var(--fg-secondary)">
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