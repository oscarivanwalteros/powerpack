<?php
$negocios_por_etapa = [];
$total_monto_pipeline = 0;
$negocios_estancados_count = 0;

foreach ($pipeline_etapas as $e) {
    $negocios_por_etapa[$e['id']] = [];
    $res = $db->query("SELECT n.*, c.nombre as c_nombre, c.apellido as c_apellido, c.empresa, c.telefono, c.email, c.prioridad,
        COALESCE(n.ultima_actividad, n.fecha_creacion) as fecha_ult
        FROM negocios n 
        LEFT JOIN contactos c ON n.contacto_id = c.id 
        WHERE n.etapa = '{$e['id']}' 
        ORDER BY n.fecha_cierre ASC");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $dias_inactivo = calcular_dias_inactividad($row['fecha_ult']);
        $row['dias_inactivo'] = $dias_inactivo;
        if ($dias_inactivo >= 5 && !in_array($e['id'], ['ganado', 'perdido'])) {
            $negocios_estancados_count++;
        }
        $negocios_por_etapa[$e['id']][] = $row;
        if ($e['id'] !== 'perdido') {
            $total_monto_pipeline += (float)$row['monto'];
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1>Embudo de Ventas (Pipeline Kanban con Semáforo)</h1>
        <p>Valor activo: <strong style="color:var(--fg)">$<?= number_format($total_monto_pipeline, 0) ?></strong> • Filosofía Pipedrive: Ningún negocio sin próximo paso.</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="?page=nueva_cotizacion" class="btn btn-secondary btn-sm">📄 + Cotización</a>
        <a href="?page=nuevo_negocio" class="btn btn-primary btn-sm">+ Nueva Oportunidad</a>
    </div>
</div>

<?php if($negocios_estancados_count > 0): ?>
<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between">
    <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:20px">🚨</span>
        <div>
            <strong style="color:#92400e;font-size:13px">Semáforo de Abandono Activo:</strong>
            <span style="color:#b45309;font-size:13px">Hay <strong><?= $negocios_estancados_count ?></strong> oportunidades con más de 5 días sin contacto que se están enfriando.</span>
        </div>
    </div>
    <span style="font-size:11px;font-weight:700;background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:999px">Prioridad Comercial</span>
</div>
<?php endif; ?>

<div class="board-scroll">
    <div class="board">
        <?php foreach ($pipeline_etapas as $e): 
            $deals = $negocios_por_etapa[$e['id']];
            $col_total = array_sum(array_column($deals, 'monto'));
        ?>
        <div class="board-col">
            <div class="board-col-header">
                <span style="display:flex;align-items:center;gap:8px">
                    <span style="width:10px;height:10px;border-radius:50%;background:<?= $e['color'] ?>"></span>
                    <span><?= $e['nombre'] ?></span>
                </span>
                <span style="background:#e2e8f0;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700"><?= count($deals) ?></span>
            </div>
            <div style="font-size:12px;font-weight:700;color:var(--fg-secondary);padding:0 2px">
                $<?= number_format($col_total, 0) ?>
            </div>

            <div class="drop-zone" data-etapa="<?= $e['id'] ?>"
                 ondragover="event.preventDefault()"
                 ondrop="moverNegocio(event,'<?= $e['id'] ?>')"
                 style="display:flex;flex-direction:column;gap:12px;min-height:220px;border-radius:var(--radius-sm)">
                
                <?php foreach ($deals as $n): 
                    $tel_wa = limpiar_telefono_whatsapp($n['telefono']);
                    $dias = $n['dias_inactivo'];
                    $es_estancado = $dias >= 5 && !in_array($e['id'], ['ganado', 'perdido']);
                ?>
                <div class="board-card" draggable="true"
                     ondragstart="event.dataTransfer.setData('negocio_id','<?= $n['id'] ?>')"
                     style="<?= $es_estancado ? 'border-left:4px solid var(--danger);box-shadow:0 2px 6px rgba(239,68,68,0.15)' : '' ?>">
                    
                    <?php if($es_estancado): ?>
                    <div style="display:inline-flex;align-items:center;gap:4px;background:#fef2f2;color:var(--danger);font-size:10px;font-weight:800;padding:2px 6px;border-radius:4px;margin-bottom:6px">
                        ⚠️ <?= $dias ?> días sin actividad
                    </div>
                    <?php endif; ?>

                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
                        <h4 style="font-size:13px;font-weight:700;margin:0">
                            <?php if($n['contacto_id']): ?>
                            <a href="?page=detalle&id=<?= $n['contacto_id'] ?>" style="color:var(--fg)"><?= h($n['nombre']) ?></a>
                            <?php else: ?>
                            <?= h($n['nombre']) ?>
                            <?php endif; ?>
                        </h4>
                        <?= prioridad_badge($n['prioridad'] ?? 'media') ?>
                    </div>

                    <div style="font-size:12px;color:var(--fg-secondary);margin-top:2px">
                        <?= h($n['empresa'] ?: ($n['c_nombre'] ? $n['c_nombre'] . ' ' . $n['c_apellido'] : 'Sin asignar')) ?>
                    </div>

                    <div class="amount">$<?= number_format($n['monto'], 0) ?></div>

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;padding-top:8px;border-top:1px solid #f1f5f9;font-size:11px;color:var(--fg-secondary)">
                        <span>🎯 Prob: <strong><?= (int)$n['probabilidad'] ?>%</strong></span>
                        <span>📅 <?= $n['fecha_cierre'] ?: 'Sin fecha' ?></span>
                    </div>

                    <div style="margin-top:8px;display:flex;justify-content:space-between;align-items:center">
                        <?php if($n['contacto_id']): ?>
                        <a href="?page=nueva_cotizacion&contacto_id=<?= $n['contacto_id'] ?>&negocio_id=<?= $n['id'] ?>" class="btn btn-secondary btn-sm" style="padding:2px 6px;font-size:10px" title="Generar Cotización PDF">
                            📄 Cotizar
                        </a>
                        <?php endif; ?>
                        
                        <?php if($tel_wa): ?>
                        <a href="https://wa.me/<?= $tel_wa ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="padding:2px 8px;font-size:10px">
                            💬 WhatsApp
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.querySelectorAll('.drop-zone').forEach(z => {
    z.addEventListener('dragover', e => { 
        e.preventDefault(); 
        z.style.background = 'rgba(249,115,22,0.08)';
        z.style.outline = '2px dashed var(--accent)';
    });
    z.addEventListener('dragleave', e => { 
        z.style.background = 'transparent';
        z.style.outline = 'none';
    });
});

function moverNegocio(e, etapa) {
    e.preventDefault();
    const id = e.dataTransfer.getData('negocio_id');
    if (!id) return;
    fetch('?page=api&action=mover', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ negocio_id: id, etapa: etapa })
    }).then(r => r.json()).then(() => location.reload());
}
</script>