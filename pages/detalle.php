<?php
$c = $db->querySingle("SELECT * FROM contactos WHERE id = $id", true);
if (!$c) { echo "<div class='empty-state'><h1>Contacto no encontrado</h1></div>"; return; }
$actividades = $db->query("SELECT * FROM actividades WHERE contacto_id = $id ORDER BY fecha DESC");
$negocios = $db->query("SELECT * FROM negocios WHERE contacto_id = $id ORDER BY fecha_creacion DESC");
$msg = $_GET['msg'] ?? '';
?>
<div class="page-header">
    <div>
        <h1><?= h($c['nombre'].' '.$c['apellido']) ?></h1>
        <p><?= h(($c['cargo'] ? $c['cargo'].' en ' : '').$c['empresa']) ?></p>
    </div>
    <?= etapa_badge($c['etapa']) ?>
</div>

<div class="detail-grid">
    <div>
        <div class="info-card">
            <div class="info-card-header">
                <span>Historial de actividad</span>
                <button class="btn btn-secondary btn-sm" onclick="document.getElementById('actForm').style.display='block'">+ Agregar</button>
            </div>
            <div class="info-card-body">
                <div id="actForm" style="display:none;margin-bottom:16px;padding:12px;background:var(--bg);border-radius:var(--radius)">
                    <form method="POST">
                        <input type="hidden" name="nueva_actividad" value="1">
                        <div class="form-row">
                            <div class="form-group"><label>Tipo</label><select name="tipo"><option value="llamada">📞 Llamada</option><option value="email">📧 Email</option><option value="reunion">🤝 Reunión</option><option value="nota">📝 Nota</option></select></div>
                            <div class="form-group"><label>Resultado</label><select name="resultado"><option value="">—</option><option value="interesado">Interesado</option><option value="no_interesado">No interesado</option><option value="cita_agendada">Cita agendada</option><option value="cotizacion_enviada">Cotización enviada</option><option value="venta_cerrada">Venta cerrada</option></select></div>
                        </div>
                        <div class="form-group"><label>Asunto</label><input type="text" name="asunto" required></div>
                        <div class="form-group"><label>Descripción</label><textarea name="descripcion" rows="3"></textarea></div>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                    </form>
                </div>
                <div class="timeline">
                    <?php while($a = $actividades->fetchArray(SQLITE3_ASSOC)): ?>
                    <div class="timeline-item">
                        <div class="t-date"><?= date('d M Y, H:i', strtotime($a['fecha'])) ?></div>
                        <div class="t-type" style="color:var(--accent)"><?= h($a['tipo']) ?></div>
                        <div class="t-desc" style="font-weight:600"><?= h($a['asunto']) ?></div>
                        <?php if($a['descripcion']): ?><div class="t-desc"><?= nl2br(h($a['descripcion'])) ?></div><?php endif; ?>
                        <?php if($a['resultado']): ?><div class="t-meta">Resultado: <?= h($a['resultado']) ?></div><?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div>
        <?php if($msg=='ok'): ?><div class="alert-success">✅ Correo enviado correctamente</div><?php endif; ?>
        <?php if($msg=='error_envio'): ?><div class="alert-error">❌ Error al enviar el correo</div><?php endif; ?>
        
        <div class="info-card">
            <div class="info-card-header"><span>📧 Enviar correo</span>
                <button class="btn btn-secondary btn-sm" onclick="document.getElementById('emailForm').style.display='block'">Redactar</button>
            </div>
            <div class="info-card-body">
                <div id="emailForm" style="display:none">
                    <form method="POST">
                        <input type="hidden" name="enviar_email" value="1">
                        <div class="form-group"><label>Para</label><input type="email" name="destinatario" value="<?= h($c['email']) ?>" required></div>
                        <div class="form-group"><label>Asunto</label><input type="text" name="asunto" value="Optimización de paletización en <?= h($c['empresa'] ?: 'su empresa') ?>" required></div>
                        <div class="form-group"><label>Cuerpo</label>
                            <textarea name="cuerpo" rows="8" required>Estimado <?= h($c['nombre']) ?>,

Trabajando con empresas del sector <?= h($c['sector'] ?: 'industrial') ?> como la suya, hemos ayudado a reducir tiempos de paletización hasta un 40%.

Nuestras paletizadoras ofrecen:
- Operación 24/7 con diagnóstico remoto
- Integración con líneas y software Siemens
- Servicio técnico especializado incluido

¿Coordinamos una breve demostración?

Powerpack Solutions SAS
info@powerpack.com.co
+57 300 467 0474</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar correo</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-card-header">Información del contacto</div>
            <div class="info-card-body">
                <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= h($c['email'] ?: '—') ?></span></div>
                <div class="info-row"><span class="info-label">Teléfono</span><span class="info-value"><?= h($c['telefono'] ?: '—') ?></span></div>
                <div class="info-row"><span class="info-label">Empresa</span><span class="info-value"><?= h($c['empresa'] ?: '—') ?></span></div>
                <div class="info-row"><span class="info-label">Cargo</span><span class="info-value"><?= h($c['cargo'] ?: '—') ?></span></div>
                <div class="info-row"><span class="info-label">Ciudad</span><span class="info-value"><?= h($c['ciudad'] ?: '—') ?></span></div>
                <div class="info-row"><span class="info-label">Sector</span><span class="info-value"><?= h($c['sector'] ?: '—') ?></span></div>
                <div class="info-row"><span class="info-label">Fuente</span><span class="info-value"><?= h($c['fuente']) ?></span></div>
                <div class="info-row"><span class="info-label">Interés</span><span class="info-value"><?= estrellas($c['interes']) ?></span></div>
                <div class="info-row"><span class="info-label">Registrado</span><span class="info-value"><?= date('d/m/Y', strtotime($c['fecha_creacion'])) ?></span></div>
            </div>
        </div>
        <?php if($c['notas']): ?>
        <div class="info-card"><div class="info-card-header">Notas</div><div class="info-card-body"><?= nl2br(h($c['notas'])) ?></div></div>
        <?php endif; ?>
    </div>
</div>