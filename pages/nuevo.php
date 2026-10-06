<div class="page-header">
    <div>
        <h1>Registrar Nuevo Contacto</h1>
        <p>Añade un prospecto o cliente a tu base de datos comercial</p>
    </div>
    <a href="?page=contactos" class="btn btn-secondary btn-sm">← Volver al Directorio</a>
</div>

<div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;max-width:800px;box-shadow:var(--shadow-sm)">
    <form method="POST">
        <input type="hidden" name="guardar_contacto" value="1">
        
        <div class="form-row">
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" placeholder="Ej: Carlos" required>
            </div>
            <div class="form-group">
                <label>Apellido</label>
                <input type="text" name="apellido" placeholder="Ej: Rodríguez">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email Corporativo</label>
                <input type="email" name="email" placeholder="carlos@empresa.com">
            </div>
            <div class="form-group">
                <label>Teléfono Celular / WhatsApp</label>
                <input type="tel" name="telefono" placeholder="+57 300 123 4567">
                <div style="font-size:11px;color:var(--fg-secondary);margin-top:2px">Compatible con enlace directo a WhatsApp.</div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Empresa</label>
                <input type="text" name="empresa" placeholder="Ej: Alimentos & Bebidas SAS">
            </div>
            <div class="form-group">
                <label>Cargo / Rol</label>
                <input type="text" name="cargo" placeholder="Ej: Gerente de Planta / Jefe de Operaciones">
            </div>
        </div>

        <div class="form-row-3">
            <div class="form-group">
                <label>Ciudad</label>
                <input type="text" name="ciudad" placeholder="Bogotá, Medellín, Cali...">
            </div>
            <div class="form-group">
                <label>Sector / Industria</label>
                <select name="sector">
                    <option value="">Seleccionar...</option>
                    <option value="alimentos">Alimentos</option>
                    <option value="bebidas">Bebidas</option>
                    <option value="farmacéutica">Farmacéutica</option>
                    <option value="cosmética">Cosmética</option>
                    <option value="logística">Logística & Envíos</option>
                    <option value="industrial">Manufactura / Industrial</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="form-group">
                <label>Etapa Comercial Inicial</label>
                <select name="etapa">
                    <?php foreach($pipeline_etapas as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= $e['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row-3">
            <div class="form-group">
                <label style="font-weight:700">Prioridad Comercial *</label>
                <select name="prioridad" style="font-weight:700">
                    <option value="alta">🔥 Alta (VIP / Cierre Inminente)</option>
                    <option value="media" selected>🟡 Media (Estándar / Seguimiento)</option>
                    <option value="baja">⚪ Baja (Frío / En Espera)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fuente de Captación</label>
                <select name="fuente">
                    <option value="feria">Feria / Evento Industrial</option>
                    <option value="web">Página Web / Catálogo</option>
                    <option value="whatsapp">Mensaje Directo de WhatsApp</option>
                    <option value="llamada">Llamada en Frío</option>
                    <option value="referido">Referido / Recomendación</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nivel de Interés Comercial</label>
                <select name="interes">
                    <option value="1">★☆☆☆☆ - Curioso / Bajo</option>
                    <option value="2">★★☆☆☆ - Interés Inicial</option>
                    <option value="3" selected>★★★☆☆ - Calificado con Necesidad</option>
                    <option value="4">★★★★☆ - Presupuesto Asignado</option>
                    <option value="5">★★★★★ - Urgente / Cierre Inmediato</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Sitio Web de la Empresa</label>
                <input type="url" name="website" placeholder="https://empresa.com">
            </div>
            <div class="form-group">
                <label>Dirección / Planta</label>
                <input type="text" name="direccion" placeholder="Zona Franca, Parque Industrial...">
            </div>
        </div>

        <div class="form-group">
            <label>Notas Iniciales & Requerimientos Técnicos</label>
            <textarea name="notas" rows="3" placeholder="Información de maquinaria que busca, volumen de producción diario, etc."></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px">
            <a href="?page=contactos" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar y Abrir Ficha</button>
        </div>
    </form>
</div>