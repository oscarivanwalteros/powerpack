<div class="page-header"><div><h1>Agregar negocio</h1><p>Registrar una oportunidad de venta</p></div></div>
<div class="info-card" style="max-width:700px"><div class="info-card-body">
<form method="POST">
    <input type="hidden" name="nuevo_negocio" value="1">
    <div class="form-group"><label>Nombre del negocio *</label><input type="text" name="nombre" required placeholder="Ej: Paletizador X4 - Alimentos ABC"></div>
    <div class="form-row">
        <div class="form-group"><label>Contacto</label>
            <select name="contacto_id">
                <option value="">Sin contacto</option>
                <?php
                $todos = $db->query("SELECT id, nombre, apellido, empresa FROM contactos ORDER BY nombre");
                while($ct = $todos->fetchArray(SQLITE3_ASSOC)):
                ?>
                <option value="<?=$ct['id']?>"><?=h($ct['nombre'].' '.$ct['apellido'])?> — <?=h($ct['empresa'] ?: 'Sin empresa')?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group"><label>Monto</label><input type="number" name="monto" step="1000" value="0"></div>
    </div>
    <div class="form-row form-row-3">
        <div class="form-group"><label>Etapa</label>
            <select name="etapa">
                <?php foreach($pipeline_etapas as $e): if($e['id']=='perdido') break; ?>
                <option value="<?=$e['id']?>"><?=$e['nombre']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Probabilidad (%)</label>
            <select name="probabilidad">
                <option value="10">10%</option><option value="20" selected>20%</option>
                <option value="40">40%</option><option value="60">60%</option>
                <option value="80">80%</option><option value="100">100%</option>
            </select>
        </div>
        <div class="form-group"><label>Fecha estimada de cierre</label><input type="date" name="fecha_cierre" value="<?=date('Y-m-d')?>"></div>
    </div>
    <button type="submit" class="btn btn-primary">Guardar negocio</button>
</form>
</div></div>