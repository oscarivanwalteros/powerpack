<?php
$plantillas_wa = $db->query("SELECT * FROM plantillas WHERE tipo = 'whatsapp' ORDER BY id ASC");
$plantillas_email = $db->query("SELECT * FROM plantillas WHERE tipo = 'email' ORDER BY id ASC");
?>

<div class="page-header">
    <div>
        <h1>Configuración & Conexiones</h1>
        <p>Configura tus credenciales de correo SMTP (Hostinger), WhatsApp y plantillas de mensajes rápidos</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

    <!-- COLUMNA 1: CONFIGURACIÓN DE CORREO SMTP -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:22px;box-shadow:var(--shadow-sm)">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
            <span style="font-size:22px">✉️</span>
            <div>
                <h3 style="font-size:16px;font-weight:800">Servidor de Correo SMTP (Hostinger)</h3>
                <p style="font-size:12px;color:var(--fg-secondary)">Permite enviar correos y cotizaciones directamente desde la plataforma</p>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="guardar_configuracion" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label>Servidor SMTP (Host)</label>
                    <input type="text" name="smtp_host" value="<?= h($config['smtp_host']) ?>" required placeholder="smtp.hostinger.com">
                </div>
                <div class="form-group">
                    <label>Puerto SMTP</label>
                    <input type="number" name="smtp_port" value="<?= h($config['smtp_port']) ?>" required placeholder="465 o 587">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Seguridad / Cifrado</label>
                    <select name="smtp_secure">
                        <option value="ssl" <?= $config['smtp_secure']==='ssl'?'selected':'' ?>>SSL (Recomendado para puerto 465)</option>
                        <option value="tls" <?= $config['smtp_secure']==='tls'?'selected':'' ?>>TLS (Recomendado para puerto 587)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Email Remitente (From)</label>
                    <input type="email" name="smtp_from" value="<?= h($config['smtp_from'] ?: $config['smtp_user']) ?>" placeholder="ventas@tudominio.com">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Usuario SMTP / Correo Hostinger</label>
                    <input type="email" name="smtp_user" value="<?= h($config['smtp_user']) ?>" placeholder="usuario@tudominio.com">
                </div>
                <div class="form-group">
                    <label>Contraseña de Correo Hostinger</label>
                    <input type="password" name="smtp_pass" value="<?= h($config['smtp_pass']) ?>" placeholder="••••••••••••">
                </div>
            </div>

            <div style="border-top:1px solid #f1f5f9;margin:18px 0;padding-top:14px">
                <h4 style="font-size:13px;font-weight:700;margin-bottom:10px">Datos de la Empresa en la Firma</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre de la Empresa</label>
                        <input type="text" name="empresa_nombre" value="<?= h($config['empresa_nombre']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono Comercial / WhatsApp</label>
                        <input type="text" name="empresa_telefono" value="<?= h($config['empresa_telefono']) ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Credenciales</button>
        </form>

        <!-- PRUEBA DE CONEXIÓN SMTP -->
        <div style="margin-top:24px;padding:16px;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm)">
            <h4 style="font-size:13px;font-weight:700;margin-bottom:6px">🧪 Probar Envío SMTP en Vivo</h4>
            <p style="font-size:12px;color:var(--fg-secondary);margin-bottom:12px">Escribe un correo receptor para verificar que tu servidor Hostinger envía sin problemas.</p>
            <form method="POST" style="display:flex;gap:8px">
                <input type="hidden" name="probar_smtp" value="1">
                <input type="email" name="test_email" placeholder="tu-correo@gmail.com" required style="flex:1;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px">
                <button type="submit" class="btn btn-secondary btn-sm">Enviar Prueba</button>
            </form>
        </div>

        <!-- GUÍA OFICIAL HOSTINGER DNS & REGISTROS MX -->
        <div style="margin-top:20px;padding:16px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:var(--radius-sm)">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                <span style="font-size:18px">📮</span>
                <h4 style="font-size:13px;font-weight:800;color:#1e40af;margin:0">Guía de Registros DNS & MX en Hostinger</h4>
            </div>
            <p style="font-size:11px;color:#1e3a8a;line-height:1.5;margin-bottom:10px">
                Para que tu correo institucional (ej: <code>administrador@powerpack.site</code>) reciba y envíe correos oficiales con alta entregabilidad y sin caer en spam, tu dominio en Hostinger debe tener activos los siguientes registros:
            </p>
            <div style="background:#fff;border:1px solid #bfdbfe;border-radius:6px;padding:10px;font-size:11px;font-family:monospace;color:#0f172a;line-height:1.6;margin-bottom:10px">
                • <strong>MX 1:</strong> Host: <code>@</code> | Apunta a: <code>mx1.hostinger.com</code> | Prioridad: <code>5</code><br>
                • <strong>MX 2:</strong> Host: <code>@</code> | Apunta a: <code>mx2.hostinger.com</code> | Prioridad: <code>10</code><br>
                • <strong>SPF (TXT):</strong> Host: <code>@</code> | <code>v=spf1 include:_netblocks.spamlaws.org include:relay.mailchannels.net include:hostinger.com ~all</code>
            </div>
            <div style="font-size:11px;color:#1e40af;line-height:1.4">
                💡 <em>En Hostinger hPanel &gt; Correos &gt; Tu Dominio, estos registros se configuran de forma automática al crear el buzón. Puedes revisar tus respuestas y correos entrantes en Webmail oficial: <a href="https://mail.hostinger.com" target="_blank" style="color:#1d4ed8;font-weight:700">mail.hostinger.com ↗</a></em>
            </div>
        </div>
    </div>

    <!-- COLUMNA 2: CONEXIÓN WHATSAPP & PLANTILLAS -->
    <div style="display:flex;flex-direction:column;gap:24px">

        <!-- TARJETA WHATSAPP -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:22px;box-shadow:var(--shadow-sm)">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                <span style="font-size:24px">💬</span>
                <div>
                    <h3 style="font-size:16px;font-weight:800">Conexión con WhatsApp</h3>
                    <p style="font-size:12px;color:var(--fg-secondary)">Integración directa y registro de conversaciones</p>
                </div>
            </div>

            <div style="font-size:13px;color:var(--fg);line-height:1.6">
                <p>La plataforma utiliza el protocolo oficial de enlace directo <strong>WhatsApp Universal Click-to-Chat</strong>.</p>
                <div style="background:var(--whatsapp-light);border:1px solid #bbf7d0;border-radius:var(--radius-sm);padding:12px;margin:12px 0;font-size:12px;color:#166534">
                    ✓ <strong>Sin costo de API:</strong> Funciona directamente en WhatsApp Web en tu PC y en la app móvil en tu celular.<br>
                    ✓ <strong>Historial Comercial:</strong> Cada mensaje enviado mediante el botón de WhatsApp queda registrado automáticamente en el timeline del prospecto.
                </div>
            </div>
        </div>

        <!-- PLANTILLAS RÁPIDAS (SNIPPETS ESTILO HUBSPOT) -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:22px;box-shadow:var(--shadow-sm)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                <div>
                    <h3 style="font-size:16px;font-weight:800">📝 Plantillas Rápidas (Snippets)</h3>
                    <p style="font-size:12px;color:var(--fg-secondary)">Plantillas predefinidas para no redactar lo mismo en cada contacto</p>
                </div>
                <button onclick="document.getElementById('modalPlantilla').style.display='flex'" class="btn btn-secondary btn-sm">+ Nueva Plantilla</button>
            </div>

            <div style="display:flex;flex-direction:column;gap:10px;max-height:350px;overflow-y:auto">
                <div style="font-size:11px;font-weight:700;color:var(--fg-secondary);text-transform:uppercase">Plantillas de WhatsApp</div>
                <?php while($pw = $plantillas_wa->fetchArray(SQLITE3_ASSOC)): ?>
                <div style="padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);display:flex;justify-content:space-between;align-items:center;background:#fafafa">
                    <div>
                        <div style="font-weight:700;font-size:13px">💬 <?= h($pw['titulo']) ?></div>
                        <div style="font-size:11px;color:var(--fg-secondary);margin-top:2px;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            <?= h($pw['cuerpo']) ?>
                        </div>
                    </div>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="eliminar_plantilla" value="1">
                        <input type="hidden" name="plantilla_id" value="<?= $pw['id'] ?>">
                        <button type="submit" onclick="return confirm('¿Eliminar plantilla?')" style="background:none;color:var(--danger);font-size:12px;cursor:pointer">🗑️</button>
                    </form>
                </div>
                <?php endwhile; ?>

                <div style="font-size:11px;font-weight:700;color:var(--fg-secondary);text-transform:uppercase;margin-top:8px">Plantillas de Correo Electrónico</div>
                <?php while($pe = $plantillas_email->fetchArray(SQLITE3_ASSOC)): ?>
                <div style="padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);display:flex;justify-content:space-between;align-items:center;background:#fafafa">
                    <div>
                        <div style="font-weight:700;font-size:13px">✉️ <?= h($pe['titulo']) ?></div>
                        <div style="font-size:11px;color:var(--fg-secondary);margin-top:2px">Asunto: <?= h($pe['asunto']) ?></div>
                    </div>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="eliminar_plantilla" value="1">
                        <input type="hidden" name="plantilla_id" value="<?= $pe['id'] ?>">
                        <button type="submit" onclick="return confirm('¿Eliminar plantilla?')" style="background:none;color:var(--danger);font-size:12px;cursor:pointer">🗑️</button>
                    </form>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

    </div>

</div>

<!-- INTEGRACIÓN WEB & WEBHOOK PARA FORMULARIOS EXTERNOS -->
<div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;box-shadow:var(--shadow-sm);margin-top:24px">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
        <span style="font-size:24px">🌐</span>
        <div>
            <h3 style="font-size:16px;font-weight:800">Captura Automática de Prospectos Web (Webhook / API)</h3>
            <p style="font-size:12px;color:var(--fg-secondary)">Copia este código y pégalo en tu página web de Hostinger o WordPress para que cada contacto que escriba entre directo a tu plataforma</p>
        </div>
    </div>

    <div style="background:#0f172a;color:#e2e8f0;padding:16px;border-radius:var(--radius-sm);font-family:monospace;font-size:12px;overflow-x:auto;margin:14px 0">
        &lt;!-- Formulario HTML para tu sitio web --&gt;<br>
        &lt;form action="https://tudominio.com/api.php?action=lead_web" method="POST"&gt;<br>
        &nbsp;&nbsp;&lt;input type="text" name="nombre" placeholder="Nombre completo" required&gt;<br>
        &nbsp;&nbsp;&lt;input type="email" name="email" placeholder="Correo electrónico" required&gt;<br>
        &nbsp;&nbsp;&lt;input type="tel" name="telefono" placeholder="WhatsApp / Celular" required&gt;<br>
        &nbsp;&nbsp;&lt;input type="text" name="empresa" placeholder="Nombre de tu empresa"&gt;<br>
        &nbsp;&nbsp;&lt;textarea name="mensaje" placeholder="¿Qué maquinaria o solución necesitas?"&gt;&lt;/textarea&gt;<br>
        &nbsp;&nbsp;&lt;button type="submit"&gt;Solicitar Cotización&lt;/button&gt;<br>
        &lt;/form&gt;
    </div>

    <div style="font-size:12px;color:var(--fg-secondary);line-height:1.5">
        ✓ <strong>Respuesta Inmediata:</strong> Al recibir los datos, el sistema crea el prospecto en etapa <em>Lead Nuevo</em> y genera una tarea prioritaria: <em>"🚨 Llamar a nuevo lead web"</em> para no dejar enfriar al cliente.
    </div>
</div>

<!-- MODAL NUEVA PLANTILLA -->
<div id="modalPlantilla" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:200;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)">
    <div style="background:#fff;border-radius:var(--radius);max-width:550px;width:100%;padding:24px;box-shadow:var(--shadow-lg)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <h2 style="font-size:18px;font-weight:800">➕ Crear Nueva Plantilla</h2>
            <button onclick="document.getElementById('modalPlantilla').style.display='none'" style="font-size:20px;color:#64748b;background:none">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="guardar_plantilla" value="1">
            <div class="form-group">
                <label>Canal</label>
                <select name="tipo" onchange="document.getElementById('campo_asunto_plantilla').style.display = this.value==='email'?'block':'none'">
                    <option value="whatsapp">💬 WhatsApp</option>
                    <option value="email">✉️ Correo Electrónico</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nombre / Título de la Plantilla</label>
                <input type="text" name="titulo" placeholder="Ej: Seguimiento a cotización enviada" required>
            </div>
            <div class="form-group" id="campo_asunto_plantilla" style="display:none">
                <label>Asunto (Solo para Email)</label>
                <input type="text" name="asunto" placeholder="Ej: Cotización y Ficha Técnica para {empresa}">
            </div>
            <div class="form-group">
                <label>Contenido del Mensaje</label>
                <textarea name="cuerpo" rows="5" required placeholder="Hola {nombre}, te saludo de {empresa_nombre}..."></textarea>
                <div style="font-size:11px;color:var(--fg-secondary);margin-top:4px">
                    Variables disponibles: <code>{nombre}</code>, <code>{apellido}</code>, <code>{empresa}</code>, <code>{cargo}</code>, <code>{ciudad}</code>, <code>{empresa_nombre}</code>, <code>{empresa_telefono}</code>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
                <button type="button" onclick="document.getElementById('modalPlantilla').style.display='none'" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Plantilla</button>
            </div>
        </form>
    </div>
</div>
