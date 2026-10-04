<?php
$total = $db->querySingle("SELECT COUNT(*) FROM contactos");
$leads = $db->querySingle("SELECT COUNT(*) FROM contactos WHERE etapa='lead'");
$calificados = $db->querySingle("SELECT COUNT(*) FROM contactos WHERE etapa IN ('contacto_inicial','calificado')");
$ganados = $db->querySingle("SELECT COUNT(*) FROM contactos WHERE etapa='ganado'");

// Pipeline
$pipeline = [];
foreach (['lead','contacto_inicial','calificado','cotizacion','negociacion'] as $etapa) {
    $count = $db->querySingle("SELECT COUNT(*) FROM negocios WHERE etapa='$etapa'");
    $suma = $db->querySingle("SELECT COALESCE(SUM(monto),0) FROM negocios WHERE etapa='$etapa'");
    $pipeline[] = ['count' => $count, 'monto' => $suma];
}
$ultimas = $db->query("SELECT * FROM actividades ORDER BY fecha DESC LIMIT 8");
$proximos = $db->query("SELECT n.*, c.empresa FROM negocios n LEFT JOIN contactos c ON n.contacto_id=c.id WHERE n.etapa IN ('cotizacion','negociacion') ORDER BY n.fecha_cierre ASC LIMIT 5");
?>

<div class="page-header">
    <div><h1>Dashboard</h1><p>Resumen de actividad comercial</p></div>
    <a href="?page=nuevo" class="btn btn-primary">+ Agregar contacto</a>
</div>

<div class="cards-grid">
    <div class="metric-card"><div class="label">Total contactos</div><div class="value" style="color:var(--fg)"><?= $total ?></div></div>
    <div class="metric-card"><div class="label">Leads</div><div class="value" style="color:#64748b"><?= $leads ?></div></div>
    <div class="metric-card"><div class="label">Calificados</div><div class="value" style="color:var(--blue)"><?= $calificados ?></div></div>
    <div class="metric-card"><div class="label">Ganados</div><div class="value" style="color:var(--success)"><?= $ganados ?></div></div>
</div>

<div style="margin-top:8px"><h2 style="font-size:15px;font-weight:600;margin-bottom:12px">Pipeline de negocios</h2>
<div class="cards-grid">
    <?php foreach ($pipeline_etapas as $i => $e): if($i>4) break; ?>
    <div class="metric-card" style="border-left:3px solid <?= $e['color'] ?>">
        <div class="label"><?= $e['nombre'] ?></div>
        <div class="value" style="font-size:20px"><?= $pipeline[$i]['count'] ?></div>
        <div class="sub">$<?= number_format($pipeline[$i]['monto'], 0) ?></div>
    </div>
    <?php endforeach; ?>
</div></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:8px">
    <div class="info-card">
        <div class="info-card-header">Actividad reciente</div>
        <div class="info-card-body">
            <div class="timeline">
                <?php while($a = $ultimas->fetchArray(SQLITE3_ASSOC)): ?>
                <div class="timeline-item">
                    <div class="t-date"><?= date('d M Y, H:i', strtotime($a['fecha'])) ?></div>
                    <div class="t-type" style="color:var(--accent)"><?= h($a['tipo']) ?></div>
                    <div class="t-desc"><?= h($a['asunto']) ?></div>
                    <?php if($a['resultado']): ?><div class="t-meta"><?= h($a['resultado']) ?></div><?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <div class="info-card">
        <div class="info-card-header">Próximos cierres</div>
        <div class="info-card-body">
            <?php while($n = $proximos->fetchArray(SQLITE3_ASSOC)): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border)">
                <div><div style="font-weight:600;font-size:13px"><?= h($n['nombre']) ?></div><div style="font-size:12px;color:var(--fg-secondary)"><?= h($n['empresa'] ?? 'Sin empresa') ?></div></div>
                <div style="text-align:right"><div style="font-weight:700">$<?= number_format($n['monto'],0) ?></div><div style="font-size:11px;color:var(--fg-secondary)"><?= $n['fecha_cierre'] ?></div></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>