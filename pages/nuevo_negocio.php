<?php
$cid_pre = isset($_GET['contacto_id']) ? (int)$_GET['contacto_id'] : 0;
$todos = $db->query("SELECT id, nombre, apellido, empresa FROM contactos ORDER BY nombre ASC");
?>
<div class="page-header">
    <div>
        <h1>Registrar Nueva Oportunidad Comercial</h1>
        <p>Crea un nuevo trato para tu pipeline y haz seguimiento de la venta</p>
    </div>
    <a href="?page=pipeline" class="btn btn-secondary btn-sm">← Volver al Pipeline</a>
</div>

<div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;max-width:800px;box-shadow:var(--shadow-sm)">
    <form method="POST">
        <input type="hidden" name="guardar_negocio" value="1">
        
        <div class="form-group">
            <label>Nombre del Negocio / Oportunidad *</label>
            <input type="text" name="nombre" required placeholder="Ej: Paletizador Automático X4 - Alimentos ABC">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Prospecto / Contacto Asociado</label>
                <select name="contacto_id">
                    <option value="">— Sin contacto específico —</option>
                    <?php while($ct = $todos->fetchArray(SQLITE3_ASSOC)): ?>
                    <option value="<?= $ct['id'] ?>" <?= $cid_pre == $ct['id'] ? 'selected' : '' ?>>
                        <?= h($ct['nombre'].' '.$ct['apellido']) ?> — <?= h($ct['empresa'] ?: 'Sin empresa') ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Monto Estimado de la Venta ($)</label>
                <input type="number" name="monto" step="100" value="0" required placeholder="Ej: 85000">
            </div>
        </div>

        <div class="form-row-3">
            <div class="form-group">
                <label>Etapa del Pipeline</label>
                <select name="etapa">
                    <?php foreach($pipeline_etapas as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= $e['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Probabilidad de Éxito</label>
                <select name="probabilidad">
                    <option value="10">10% - Lead Inicial</option>
                    <option value="20" selected>20% - Contacto Realizado</option>
                    <option value="40">40% - Requerimiento Validado</option>
                    <option value="60">60% - Cotización Presentada</option>
                    <option value="80">80% - Negociación / Aprobación</option>
                    <option value="100">100% - Ganado / Cerrado</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha Estimada de Cierre</label>
                <input type="date" name="fecha_cierre" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Descripción y Alcance de la Propuesta</label>
            <textarea name="descripcion" rows="3" placeholder="Detalles de los equipos, tiempos de entrega acordados o condiciones comerciales..."></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px">
            <a href="?page=pipeline" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Crear Oportunidad</button>
        </div>
    </form>
</div>