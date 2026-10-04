<?php
$q = $_GET['q'] ?? '';
$etapa_filtro = $_GET['etapa'] ?? '';
$sector_filtro = $_GET['sector'] ?? '';

$sql = "SELECT * FROM contactos WHERE 1=1";
if ($q) $sql .= " AND (nombre LIKE '%$q%' OR empresa LIKE '%$q%' OR email LIKE '%$q%')";
if ($etapa_filtro) $sql .= " AND etapa='$etapa_filtro'";
if ($sector_filtro) $sql .= " AND sector='$sector_filtro'";
$sql .= " ORDER BY ultima_actividad DESC";
$contactos = $db->query($sql);

$total_count = $db->querySingle("SELECT COUNT(*) FROM contactos");
$sectores = $db->query("SELECT DISTINCT sector FROM contactos WHERE sector != ''");
?>
<div class="page-header">
    <div><h1>Contactos</h1><p><?= $total_count ?> contactos</p></div>
    <a href="?page=nuevo" class="btn btn-primary">+ Agregar contacto</a>
</div>
<div class="table-wrap">
    <div class="table-toolbar">
        <div class="tabs">
            <a href="?page=contactos" class="tab <?= !$etapa_filtro?'active':'' ?>">Todos</a>
            <?php foreach($pipeline_etapas as $e): ?>
            <a href="?page=contactos&etapa=<?= $e['id'] ?>" class="tab <?= $etapa_filtro==$e['id']?'active':'' ?>"><?= $e['nombre'] ?></a>
            <?php endforeach; ?>
        </div>
        <form style="display:flex;gap:8px" method="GET">
            <input type="hidden" name="page" value="contactos">
            <select name="sector" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px">
                <option value="">Todos los sectores</option>
                <?php while($s = $sectores->fetchArray(SQLITE3_ASSOC)): ?>
                <option value="<?= h($s['sector']) ?>" <?= $sector_filtro==$s['sector']?'selected':'' ?>><?= h($s['sector']) ?></option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>
    <table>
        <thead><tr><th>Nombre</th><th>Email</th><th>Empresa</th><th>Teléfono</th><th>Ciudad</th><th>Sector</th><th>Etapa</th><th>Interés</th><th>Última actividad</th></tr></thead>
        <tbody>
            <?php while($c = $contactos->fetchArray(SQLITE3_ASSOC)): ?>
            <tr>
                <td><a href="?page=detalle&id=<?= $c['id'] ?>" class="link"><?= h($c['nombre'].' '.$c['apellido']) ?></a>
                    <?php if($c['cargo']): ?><div style="font-size:11px;color:var(--fg-secondary)"><?= h($c['cargo']) ?></div><?php endif; ?></td>
                <td><?= h($c['email'] ?: '—') ?></td>
                <td><?= h($c['empresa'] ?: '—') ?></td>
                <td><?= h($c['telefono'] ?: '—') ?></td>
                <td><?= h($c['ciudad'] ?: '—') ?></td>
                <td><?= h($c['sector'] ?: '—') ?></td>
                <td><?= etapa_badge($c['etapa']) ?></td>
                <td><?= estrellas($c['interes']) ?></td>
                <td><?= $c['ultima_actividad'] ? date('d/m/Y', strtotime($c['ultima_actividad'])) : '—' ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>