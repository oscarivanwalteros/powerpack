<?php
$negocios_por_etapa = [];
foreach ($pipeline_etapas as $e) {
    $negocios_por_etapa[$e['id']] = [];
    $res = $db->query("SELECT n.*, c.empresa FROM negocios n LEFT JOIN contactos c ON n.contacto_id=c.id WHERE n.etapa='{$e['id']}' ORDER BY n.fecha_cierre ASC");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) $negocios_por_etapa[$e['id']][] = $row;
}
$total_monto = $db->querySingle("SELECT COALESCE(SUM(monto),0) FROM negocios WHERE etapa != 'perdido'");
?>
<div class="page-header">
    <div><h1>Pipeline de negocios</h1><p>Total: $<?= number_format($total_monto, 0) ?></p></div>
    <a href="?page=nuevo_negocio" class="btn btn-primary">+ Agregar negocio</a>
</div>

<div class="board-scroll">
    <div class="board">
        <?php foreach ($pipeline_etapas as $e): if($e['id']=='perdido') break; ?>
        <div class="board-col">
            <div class="board-col-header">
                <span style="display:flex;align-items:center;gap:6px">
                    <span style="width:8px;height:8px;border-radius:50%;background:<?=$e['color']?>"></span>
                    <?=$e['nombre']?>
                </span>
                <span class="board-col-count"><?= count($negocios_por_etapa[$e['id']]) ?></span>
            </div>
            <?php foreach ($negocios_por_etapa[$e['id']] as $n): ?>
            <div class="board-card" draggable="true"
                 ondragstart="event.dataTransfer.setData('negocio_id','<?=$n['id']?>')"
                 onclick="location.href='?page=detalle&id=<?=$n['contacto_id']?>'">
                <h4><?= h($n['nombre']) ?></h4>
                <div class="company"><?= h($n['empresa'] ?: 'Sin empresa') ?></div>
                <div class="amount">$<?= number_format($n['monto'], 0) ?></div>
                <div class="meta"><span><?=$n['probabilidad']?>% prob.</span><span><?=$n['fecha_cierre']?></span></div>
            </div>
            <?php endforeach; ?>
            <div class="drop-zone" data-etapa="<?=$e['id']?>"
                 ondragover="event.preventDefault()"
                 ondrop="moverNegocio(event,'<?=$e['id']?>')"
                 style="min-height:40px;border:2px dashed transparent;border-radius:var(--radius);transition:.2s"></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.querySelectorAll('.drop-zone').forEach(z => {
    z.addEventListener('dragover', e => { e.preventDefault(); z.style.borderColor='var(--accent)'; z.style.background='rgba(249,115,22,.05)'; });
    z.addEventListener('dragleave', e => { z.style.borderColor='transparent'; z.style.background='transparent'; });
});
function moverNegocio(e, etapa) {
    e.preventDefault();
    const id = e.dataTransfer.getData('negocio_id');
    fetch('?page=api&action=mover',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({negocio_id:id,etapa:etapa})})
        .then(r=>r.json()).then(()=>location.reload());
    e.target.style.borderColor='transparent'; e.target.style.background='transparent';
}
</script>