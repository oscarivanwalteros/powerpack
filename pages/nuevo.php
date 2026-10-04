<div class="page-header"><div><h1>Agregar contacto</h1><p>Registrar un nuevo lead o cliente</p></div></div>
<div class="info-card" style="max-width:700px"><div class="info-card-body">
<form method="POST">
    <input type="hidden" name="nuevo_contacto" value="1">
    <div class="form-row">
        <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" required></div>
        <div class="form-group"><label>Apellido</label><input type="text" name="apellido"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Email</label><input type="email" name="email"></div>
        <div class="form-group"><label>Teléfono</label><input type="tel" name="telefono"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Empresa</label><input type="text" name="empresa"></div>
        <div class="form-group"><label>Cargo</label><input type="text" name="cargo" placeholder="Ej: Gerente de producción"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Ciudad</label><input type="text" name="ciudad" placeholder="Bogotá"></div>
        <div class="form-group"><label>Sector</label>
            <select name="sector"><option value="">Seleccionar...</option>
                <option value="alimentos">Alimentos</option><option value="bebidas">Bebidas</option>
                <option value="farmacéutica">Farmacéutica</option><option value="cosmética">Cosmética</option>
                <option value="logística">Logística</option><option value="industrial">Industrial</option>
            </select>
        </div>
    </div>
    <div class="form-row form-row-3">
        <div class="form-group"><label>Etapa</label>
            <select name="etapa">
                <?php foreach($pipeline_etapas as $e): ?>
                <option value="<?=$e['id']?>"><?=$e['nombre']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Fuente</label>
            <select name="fuente">
                <option value="feria">Feria</option><option value="web">Web</option>
                <option value="llamada">Llamada</option><option value="recomendacion">Recomendación</option>
            </select>
        </div>
        <div class="form-group"><label>Interés (1-5)</label>
            <select name="interes">
                <?php for($i=1;$i<=5;$i++): ?>
                <option value="<?=$i?>" <?=$i==3?'selected':''?>><?=$i?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
    <div class="form-group"><label>Notas</label><textarea name="notas" rows="3"></textarea></div>
    <button type="submit" class="btn btn-primary">Guardar contacto</button>
</form>
</div></div>