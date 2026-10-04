<?php
$c = $db->querySingle("SELECT * FROM contactos WHERE id = $id", true);
if (!$c) {
    echo "<div style='text-align:center;padding:60px 20px'><h2>Contacto no encontrado</h2><p><a href='?page=contactos' class='btn btn-secondary' style='margin-top:10px'>Volver a Contactos</a></p></div>";
    return;
}

$filtro_act = $_GET['tipo_act'] ?? '';
$sql_act = "SELECT * FROM actividades WHERE contacto_id = $id";
if ($filtro_act) {
    $sql_act .= " AND tipo = '" . SQLite3::escapeString($filtro_act) . "'";
}
$sql_act .= " ORDER BY fecha DESC";
$actividades = $db->query($sql_act);

$negocios = $db->query("SELECT * FROM negocios WHERE contacto_id = $id ORDER BY fecha_creacion DESC");
$cots_contacto = $db->query("SELECT * FROM cotizaciones WHERE contacto_id = $id ORDER BY id DESC");
$archivos_contacto = $db->query("SELECT * FROM archivos WHERE contacto_id = $id ORDER BY id DESC");
$plantillas_wa = $db->query("SELECT * FROM plantillas WHERE tipo = 'whatsapp'");
$plantillas_email = $db->query("SELECT * FROM plantillas WHERE tipo = 'email'");

$clean_tel = limpiar_telefono_whatsapp($c['telefono']);
?>

<!-- Header del Contacto estilo HubSpot -->
<div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;box-shadow:var(--shadow-sm);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px">
    <div style="display:flex;align-items:center;gap:18px">
        <?= avatar_iniciales($c['nombre'], $c['apellido']) ?>
        <div>
            <div style="display:flex;align-items:center;gap:10px">
                <h1 style="font-size:22px;font-weight:800"><?= h($c['nombre'] . ' ' . $c['apellido']) ?></h1>
                <?= etapa_badge($c['etapa']) ?>
            </div>
            <div style="color:var(--fg-secondary);font-size:13px;margin-top:2px">
                <strong><?= h($c['cargo'] ?: 'Contacto') ?></strong> <?= $c['empresa'] ? 'en <strong style="color:var(--fg)">' . h($c['empresa']) . '</strong>' : '' ?>
                <?= $c['ciudad'] ? '• 📍 ' . h($c['ciudad']) : '' ?>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <button onclick="abrirModalIA()" class="btn btn-sm" style="background:linear-gradient(135deg, #2c60a4 0%, #7c3aed 100%);color:#fff;font-weight:800;border:none;box-shadow:0 2px 6px rgba(124,58,237,0.3)">
            ✨ Copiloto IA
        </button>
        <a href="?page=nueva_cotizacion&contacto_id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">📄 + Cotizar</a>
        <?php if($clean_tel): ?>
        <button onclick="activarTab('tab-wa')" class="btn btn-whatsapp btn-sm">💬 Enviar WhatsApp</button>
        <?php endif; ?>
        <?php if($c['email']): ?>
        <button onclick="activarTab('tab-email')" class="btn btn-email btn-sm">✉️ Redactar Email</button>
        <?php endif; ?>
        <button onclick="activarTab('tab-tarea')" class="btn btn-secondary btn-sm">✅ + Tarea</button>
        <button onclick="document.getElementById('modalEditar').style.display='flex'" class="btn btn-secondary btn-sm">✏️ Editar</button>
    </div>
</div>

<div style="display:grid;grid-template-columns:360px 1fr;gap:24px;align-items:start">

    <!-- COLUMNA IZQUIERDA: Ficha y Oportunidades del Cliente -->
    <div style="display:flex;flex-direction:column;gap:20px">
        
        <!-- Tarjeta de Propiedades -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);overflow:hidden">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);font-weight:700;font-size:13px;display:flex;justify-content:space-between;align-items:center;background:#f8fafc">
                <span>📋 Datos del Contacto</span>
                <span style="font-size:11px;color:var(--fg-secondary)">ID #<?= $c['id'] ?></span>
            </div>
            <div style="padding:18px;display:flex;flex-direction:column;gap:12px;font-size:13px">
                <div>
                    <div style="font-size:11px;color:var(--fg-secondary);font-weight:700;text-transform:uppercase">Email</div>
                    <div style="font-weight:600;margin-top:2px">
                        <?php if($c['email']): ?>
                        <a href="mailto:<?= h($c['email']) ?>" style="color:var(--email)"><?= h($c['email']) ?></a>
                        <?php else: ?>—<?php endif; ?>
                    </div>
                </div>

                <div>
                    <div style="font-size:11px;color:var(--fg-secondary);font-weight:700;text-transform:uppercase">Teléfono / WhatsApp</div>
                    <div style="font-weight:600;margin-top:2px;display:flex;align-items:center;gap:8px">
                        <span><?= h($c['telefono'] ?: '—') ?></span>
                        <?php if($clean_tel): ?>
                        <a href="https://wa.me/<?= $clean_tel ?>" target="_blank" title="Abrir WhatsApp directo" style="color:var(--whatsapp);font-weight:700">💬 Abrir</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div style="font-size:11px;color:var(--fg-secondary);font-weight:700;text-transform:uppercase">Empresa</div>
                    <div style="font-weight:600;margin-top:2px"><?= h($c['empresa'] ?: '—') ?></div>
                </div>

                <div>
                    <div style="font-size:11px;color:var(--fg-secondary);font-weight:700;text-transform:uppercase">Sector / Industria</div>
                    <div style="font-weight:600;margin-top:2px"><?= h(ucwords($c['sector'] ?: 'General')) ?></div>
                </div>

                <div>
                    <div style="font-size:11px;color:var(--fg-secondary);font-weight:700;text-transform:uppercase">Nivel de Interés</div>
                    <div style="margin-top:2px"><?= estrellas($c['interes']) ?></div>
                </div>

                <div>
                    <div style="font-size:11px;color:var(--fg-secondary);font-weight:700;text-transform:uppercase">Fuente de Captación</div>
                    <div style="font-weight:600;margin-top:2px"><?= h(ucwords($c['fuente'])) ?></div>
                </div>

                <?php if($c['website']): ?>
                <div>
                    <div style="font-size:11px;color:var(--fg-secondary);font-weight:700;text-transform:uppercase">Sitio Web</div>
                    <div style="margin-top:2px"><a href="<?= h($c['website']) ?>" target="_blank" style="color:var(--email)"><?= h($c['website']) ?></a></div>
                </div>
                <?php endif; ?>

                <div style="border-top:1px solid #f1f5f9;padding-top:10px;font-size:11px;color:var(--fg-secondary)">
                    Registrado el <?= date('d/m/Y H:i', strtotime($c['fecha_creacion'])) ?>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Negocios Asociados -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);overflow:hidden">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);font-weight:700;font-size:13px;display:flex;justify-content:space-between;align-items:center;background:#f8fafc">
                <span>💼 Negocios Asociados</span>
                <a href="?page=nuevo_negocio&contacto_id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm" style="padding:3px 8px;font-size:11px">+ Nuevo</a>
            </div>
            <div style="padding:16px;display:flex;flex-direction:column;gap:12px">
                <?php 
                $hay_negocios = false;
                while($n = $negocios->fetchArray(SQLITE3_ASSOC)): 
                    $hay_negocios = true;
                ?>
                <div style="padding:10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fafafa">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div style="font-weight:700;font-size:13px"><?= h($n['nombre']) ?></div>
                        <div style="font-weight:800;color:var(--fg);font-size:14px">$<?= number_format($n['monto'], 0) ?></div>
                    </div>
                    <div style="margin-top:6px;display:flex;justify-content:space-between;align-items:center">
                        <?= etapa_badge($n['etapa']) ?>
                        <span style="font-size:11px;color:var(--fg-secondary)">Cierre: <?= $n['fecha_cierre'] ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if(!$hay_negocios): ?>
                <div style="text-align:center;padding:14px 0;color:var(--fg-secondary);font-size:12px">
                    No hay oportunidades abiertas aún.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tarjeta de Cotizaciones Formales -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);overflow:hidden">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);font-weight:700;font-size:13px;display:flex;justify-content:space-between;align-items:center;background:#f8fafc">
                <span>📄 Cotizaciones Formales</span>
                <a href="?page=nueva_cotizacion&contacto_id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm" style="padding:3px 8px;font-size:11px">+ Nueva</a>
            </div>
            <div style="padding:14px;display:flex;flex-direction:column;gap:8px">
                <?php 
                $hay_cots = false;
                while($cot = $cots_contacto->fetchArray(SQLITE3_ASSOC)): 
                    $hay_cots = true;
                ?>
                <div style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);display:flex;justify-content:space-between;align-items:center;background:#fafafa;font-size:12px">
                    <div>
                        <a href="?page=ver_cotizacion&id=<?= $cot['id'] ?>" style="font-weight:800;color:var(--accent)"><?= h($cot['numero']) ?></a>
                        <div style="font-size:11px;color:var(--fg-secondary)"><?= $cot['fecha'] ?></div>
                    </div>
                    <div style="text-align:right">
                        <strong>$<?= number_format($cot['total'], 0) ?></strong>
                        <div><span style="font-size:10px;font-weight:700;text-transform:uppercase"><?= $cot['estado'] ?></span></div>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if(!$hay_cots): ?>
                <div style="text-align:center;padding:10px 0;color:var(--fg-secondary);font-size:12px">
                    Sin cotizaciones emitidas aún.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tarjeta de Archivos y Documentos Adjuntos -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);overflow:hidden">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);font-weight:700;font-size:13px;display:flex;justify-content:space-between;align-items:center;background:#f8fafc">
                <span>📎 Archivos & Fichas Técnicas</span>
                <button onclick="document.getElementById('modalSubirArchivoContacto').style.display='flex'" class="btn btn-secondary btn-sm" style="padding:3px 8px;font-size:11px">+ Subir</button>
            </div>
            <div style="padding:14px;display:flex;flex-direction:column;gap:8px">
                <?php 
                $hay_arch = false;
                while($arc = $archivos_contacto->fetchArray(SQLITE3_ASSOC)): 
                    $hay_arch = true;
                ?>
                <div style="padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);display:flex;justify-content:space-between;align-items:center;font-size:12px;background:#fafafa">
                    <span style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= h($arc['nombre_original']) ?>">📄 <?= h($arc['nombre_original']) ?></span>
                    <a href="uploads/<?= h($arc['ruta']) ?>" target="_blank" download style="color:var(--email);font-weight:700">Ver</a>
                </div>
                <?php endwhile; ?>
                <?php if(!$hay_arch): ?>
                <div style="text-align:center;padding:10px 0;color:var(--fg-secondary);font-size:12px">
                    Sin archivos adjuntos.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if($c['notas']): ?>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px;box-shadow:var(--shadow-sm)">
            <div style="font-weight:700;font-size:13px;margin-bottom:8px">📝 Notas de Perfil</div>
            <div style="font-size:13px;color:var(--fg);white-space:pre-wrap"><?= h($c['notas']) ?></div>
        </div>
        <?php endif; ?>

    </div>

    <!-- COLUMNA DERECHA: Editor de Acciones tipo HubSpot & Feed de Actividad -->
    <div style="display:flex;flex-direction:column;gap:20px">

        <!-- COMPOSER DE ACCIONES RÁPIDAS (Tabs estilo HubSpot) -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);overflow:hidden">
            <div style="display:flex;border-bottom:1px solid var(--border);background:#f8fafc;padding:0 8px;overflow-x:auto">
                <button type="button" class="tab-btn active" id="btn-wa" onclick="activarTab('tab-wa')">💬 WhatsApp</button>
                <button type="button" class="tab-btn" id="btn-email" onclick="activarTab('tab-email')">✉️ Correo Electrónico</button>
                <button type="button" class="tab-btn" id="btn-llamada" onclick="activarTab('tab-llamada')">📞 Registrar Llamada</button>
                <button type="button" class="tab-btn" id="btn-tarea" onclick="activarTab('tab-tarea')">✅ Programar Tarea</button>
                <button type="button" class="tab-btn" id="btn-nota" onclick="activarTab('tab-nota')">📝 Nota Interna</button>
            </div>

            <div style="padding:20px">
                <!-- TAB 1: WHATSAPP -->
                <div id="tab-wa" class="composer-pane" style="display:block">
                    <form method="POST">
                        <input type="hidden" name="enviar_whatsapp" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Número Destinatario</label>
                                <input type="text" name="telefono" id="wa_tel" value="<?= h($c['telefono']) ?>" required placeholder="Ej: 300 123 4567">
                                <div style="font-size:11px;color:var(--fg-secondary);margin-top:3px">Se formateará automáticamente con prefijo internacional (+57).</div>
                            </div>
                            <div class="form-group">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                                    <label style="margin:0">Plantilla Rápida</label>
                                    <button type="button" onclick="abrirModalIA('whatsapp')" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:11px;color:#2c60a4;font-weight:700">
                                        ✨ Redactar con IA
                                    </button>
                                </div>
                                <select id="select_plantilla_wa" onchange="cargarPlantillaWA(this)">
                                    <option value="">— Seleccionar plantilla predefinida —</option>
                                    <?php while($p = $plantillas_wa->fetchArray(SQLITE3_ASSOC)): ?>
                                    <option value="<?= h(reemplazar_variables($p['cuerpo'], $c, $config)) ?>"><?= h($p['titulo']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mensaje de WhatsApp</label>
                            <textarea name="mensaje" id="wa_mensaje" rows="4" required placeholder="Escribe el mensaje o selecciona una plantilla arriba..."><?= h(reemplazar_variables("¡Hola {nombre}! Un gusto saludarte de parte de {empresa_nombre}. Vimos lo que hacen en {empresa}. ¿Cómo están optimizando actualmente sus procesos?", $c, $config)) ?></textarea>
                        </div>
                        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                            <button type="submit" name="abrir_whatsapp" value="1" class="btn btn-whatsapp">
                                🚀 Abrir WhatsApp Web y Registrar en CRM
                            </button>
                            <button type="submit" class="btn btn-secondary">
                                📝 Solo Registrar en Historial
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 2: CORREO ELECTRÓNICO (SMTP) -->
                <div id="tab-email" class="composer-pane" style="display:none">
                    <form method="POST">
                        <input type="hidden" name="enviar_email" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Para (Email Destinatario)</label>
                                <input type="email" name="destinatario" value="<?= h($c['email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                                    <label style="margin:0">Plantilla Rápida de Email</label>
                                    <button type="button" onclick="abrirModalIA('email')" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:11px;color:#2c60a4;font-weight:700">
                                        ✨ Redactar con IA
                                    </button>
                                </div>
                                <select id="select_plantilla_email" onchange="cargarPlantillaEmail(this)">
                                    <option value="">— Seleccionar plantilla —</option>
                                    <?php while($pe = $plantillas_email->fetchArray(SQLITE3_ASSOC)): ?>
                                    <option data-asunto="<?= h(reemplazar_variables($pe['asunto'], $c, $config)) ?>" value="<?= h(reemplazar_variables($pe['cuerpo'], $c, $config)) ?>">
                                        <?= h($pe['titulo']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Asunto del Correo</label>
                            <input type="text" name="asunto" id="email_asunto" value="<?= h(reemplazar_variables("Propuesta y Soluciones de Automatización para {empresa}", $c, $config)) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Cuerpo del Correo</label>
                            <textarea name="cuerpo" id="email_cuerpo" rows="7" required>Estimado/a <?= h($c['nombre']) ?>,

Es un gusto saludarte. Te comparto la información solicitada sobre nuestras soluciones de maquinaria y paletización para <?= h($c['empresa'] ?: 'su empresa') ?>.

¿Podríamos coordinar una breve reunión o llamada de 10 minutos para revisar los detalles técnicos?

Quedo atento a tus comentarios.

Cordialmente,
<?= h($config['empresa_nombre']) ?>
<?= h($config['empresa_telefono']) ?></textarea>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <button type="submit" class="btn btn-email">✉️ Enviar Correo vía Hostinger SMTP</button>
                            <span style="font-size:11px;color:var(--fg-secondary)">Servidor: <?= h($config['smtp_host']) ?></span>
                        </div>
                    </form>
                </div>

                <!-- TAB 3: REGISTRAR LLAMADA -->
                <div id="tab-llamada" class="composer-pane" style="display:none">
                    <form method="POST">
                        <input type="hidden" name="nueva_actividad" value="1">
                        <input type="hidden" name="tipo" value="llamada">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Asunto de la Llamada</label>
                                <input type="text" name="asunto" value="Llamada de seguimiento" required>
                            </div>
                            <div class="form-group">
                                <label>Resultado de la Conversación</label>
                                <select name="resultado">
                                    <option value="interesado">Interesado / Enviar cotización</option>
                                    <option value="cita_agendada">Cita / Demostración agendada</option>
                                    <option value="no_contesta">No contesta / Dejó mensaje</option>
                                    <option value="numero_equivocado">Número equivocado</option>
                                    <option value="no_interesado">No tiene presupuesto / No interesado</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Notas de lo conversado</label>
                            <textarea name="descripcion" rows="3" placeholder="Detalles de la conversación, objeciones, presupuestos mencionados..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Registro de Llamada</button>
                    </form>
                </div>

                <!-- TAB 4: PROGRAMAR TAREA -->
                <div id="tab-tarea" class="composer-pane" style="display:none">
                    <form method="POST">
                        <input type="hidden" name="nueva_tarea" value="1">
                        <input type="hidden" name="contacto_id" value="<?= $c['id'] ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Título de la Tarea / Recordatorio</label>
                                <input type="text" name="asunto" placeholder="Ej: Llamar para confirmar recepción de cotización" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha y Hora Límite</label>
                                <input type="datetime-local" name="fecha_vencimiento" value="<?= date('Y-m-d\TH:i', strtotime('+1 day 09:00')) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Detalles adicionales</label>
                            <textarea name="descripcion" rows="2" placeholder="Recordar preguntar por disponibilidad de la gerencia técnica..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">✅ Programar Tarea Comercial</button>
                    </form>
                </div>

                <!-- TAB 5: NOTA INTERNA -->
                <div id="tab-nota" class="composer-pane" style="display:none">
                    <form method="POST">
                        <input type="hidden" name="nueva_actividad" value="1">
                        <input type="hidden" name="tipo" value="nota">
                        <div class="form-group">
                            <label>Título de la Nota</label>
                            <input type="text" name="asunto" value="Nota interna" required>
                        </div>
                        <div class="form-group">
                            <label>Contenido de la Nota</label>
                            <textarea name="descripcion" rows="4" placeholder="Apuntes privados sobre este prospecto..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Nota</button>
                    </form>
                </div>

            </div>
        </div>

        <!-- FEED DE ACTIVIDAD & TIMELINE -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);padding:22px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px">
                <h3 style="font-size:16px;font-weight:800">Timeline de Actividades</h3>
                <div style="display:flex;gap:4px">
                    <a href="?page=detalle&id=<?= $c['id'] ?>" class="tab <?= !$filtro_act?'active':'' ?>" style="font-size:12px;padding:4px 10px">Todo</a>
                    <a href="?page=detalle&id=<?= $c['id'] ?>&tipo_act=whatsapp" class="tab <?= $filtro_act=='whatsapp'?'active':'' ?>" style="font-size:12px;padding:4px 10px">💬 WhatsApp</a>
                    <a href="?page=detalle&id=<?= $c['id'] ?>&tipo_act=email" class="tab <?= $filtro_act=='email'?'active':'' ?>" style="font-size:12px;padding:4px 10px">✉️ Emails</a>
                    <a href="?page=detalle&id=<?= $c['id'] ?>&tipo_act=llamada" class="tab <?= $filtro_act=='llamada'?'active':'' ?>" style="font-size:12px;padding:4px 10px">📞 Llamadas</a>
                    <a href="?page=detalle&id=<?= $c['id'] ?>&tipo_act=tarea" class="tab <?= $filtro_act=='tarea'?'active':'' ?>" style="font-size:12px;padding:4px 10px">✅ Tareas</a>
                </div>
            </div>

            <div class="timeline">
                <?php 
                $hay_actividades = false;
                while($a = $actividades->fetchArray(SQLITE3_ASSOC)): 
                    $hay_actividades = true;
                    $tipo = $a['tipo'];
                ?>
                <div class="timeline-item">
                    <div class="timeline-item-icon">
                        <?= tipo_actividad_icono($tipo) ?>
                    </div>
                    <div class="t-header">
                        <span class="t-type" style="color:<?= $tipo=='whatsapp'?'var(--whatsapp)':($tipo=='email'?'var(--email)':'var(--accent)') ?>">
                            <?= strtoupper($tipo) ?>: <?= h($a['asunto']) ?>
                        </span>
                        <span class="t-date"><?= date('d M Y, H:i', strtotime($a['fecha'])) ?></span>
                    </div>

                    <?php if($a['descripcion']): ?>
                    <div class="t-desc"><?= nl2br(h($a['descripcion'])) ?></div>
                    <?php endif; ?>

                    <?php if($tipo === 'tarea'): ?>
                    <div style="margin-top:10px;display:flex;align-items:center;gap:10px">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="toggle_tarea" value="1">
                            <input type="hidden" name="tarea_id" value="<?= $a['id'] ?>">
                            <input type="hidden" name="nuevo_estado" value="<?= $a['completada'] ? 0 : 1 ?>">
                            <input type="hidden" name="return_url" value="index.php?page=detalle&id=<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $a['completada'] ? 'btn-secondary' : 'btn-primary' ?>" style="font-size:11px">
                                <?= $a['completada'] ? '✓ Tarea Completada (Clic para reabrir)' : '⭕ Marcar como Realizada' ?>
                            </button>
                        </form>
                        <?php if($a['fecha_vencimiento']): ?>
                        <span style="font-size:11px;color:var(--fg-secondary)">Vence: <?= date('d/m/Y H:i', strtotime($a['fecha_vencimiento'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if($a['resultado']): ?>
                    <div class="t-meta">
                        <span>Estado / Resultado: <strong><?= h($a['resultado']) ?></strong></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>

                <?php if(!$hay_actividades): ?>
                <div style="text-align:center;padding:40px 10px;color:var(--fg-secondary)">
                    No hay actividades registradas en este filtro. Usa las pestañas de arriba para enviar un WhatsApp o correo.
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- MODAL PARA EDITAR CONTACTO -->
<div id="modalEditar" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:200;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)">
    <div style="background:#fff;border-radius:var(--radius);max-width:650px;width:100%;max-height:90vh;overflow-y:auto;padding:24px;box-shadow:var(--shadow-lg)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <h2 style="font-size:18px;font-weight:800">Editar Contacto #<?= $c['id'] ?></h2>
            <button onclick="document.getElementById('modalEditar').style.display='none'" style="font-size:20px;color:#64748b;background:none">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="guardar_contacto" value="1">
            <input type="hidden" name="contacto_id" value="<?= $c['id'] ?>">
            <div class="form-row">
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" value="<?= h($c['nombre']) ?>" required></div>
                <div class="form-group"><label>Apellido</label><input type="text" name="apellido" value="<?= h($c['apellido']) ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= h($c['email']) ?>"></div>
                <div class="form-group"><label>Teléfono</label><input type="text" name="telefono" value="<?= h($c['telefono']) ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Empresa</label><input type="text" name="empresa" value="<?= h($c['empresa']) ?>"></div>
                <div class="form-group"><label>Cargo</label><input type="text" name="cargo" value="<?= h($c['cargo']) ?>"></div>
            </div>
            <div class="form-row-3">
                <div class="form-group"><label>Ciudad</label><input type="text" name="ciudad" value="<?= h($c['ciudad']) ?>"></div>
                <div class="form-group"><label>Sector</label><input type="text" name="sector" value="<?= h($c['sector']) ?>"></div>
                <div class="form-group"><label>Etapa</label>
                    <select name="etapa">
                        <?php foreach($pipeline_etapas as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= $c['etapa']==$e['id']?'selected':'' ?>><?= $e['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Nivel de Interés (1 a 5)</label>
                    <select name="interes">
                        <option value="1" <?= $c['interes']==1?'selected':'' ?>>★☆☆☆☆ (Bajo)</option>
                        <option value="2" <?= $c['interes']==2?'selected':'' ?>>★★☆☆☆ (Medio Bajo)</option>
                        <option value="3" <?= $c['interes']==3?'selected':'' ?>>★★★☆☆ (Medio)</option>
                        <option value="4" <?= $c['interes']==4?'selected':'' ?>>★★★★☆ (Alto)</option>
                        <option value="5" <?= $c['interes']==5?'selected':'' ?>>★★★★★ (Listo para compra)</option>
                    </select>
                </div>
                <div class="form-group"><label>Sitio Web</label><input type="text" name="website" value="<?= h($c['website']) ?>"></div>
            </div>
            <div class="form-group"><label>Notas</label><textarea name="notas" rows="3"><?= h($c['notas']) ?></textarea></div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <button type="button" onclick="confirmarEliminar()" class="btn btn-danger btn-sm">Eliminar Contacto</button>
            </div>
        </form>
        <form id="formEliminar" method="POST" style="display:none">
            <input type="hidden" name="eliminar_contacto" value="1">
        </form>
    </div>
</div>

<!-- MODAL SUBIR ARCHIVO AL CONTACTO -->
<div id="modalSubirArchivoContacto" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:200;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)">
    <div style="background:#fff;border-radius:var(--radius);max-width:450px;width:100%;padding:24px;box-shadow:var(--shadow-lg)">
        <h3 style="font-size:16px;font-weight:800;margin-bottom:14px">📎 Adjuntar Documento a <?= h($c['nombre']) ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="subir_archivo" value="1">
            <input type="hidden" name="contacto_id" value="<?= $c['id'] ?>">
            <div class="form-group">
                <label>Seleccionar Archivo (PDF, RUT, Ficha Técnica, Imagen)</label>
                <input type="file" name="archivo" required style="padding:8px">
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
                <button type="button" onclick="document.getElementById('modalSubirArchivoContacto').style.display='none'" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Subir Documento</button>
            </div>
        </form>
    </div>
</div>

<style>
.tab-btn {
    padding: 12px 18px;
    font-size: 13px;
    font-weight: 700;
    color: var(--fg-secondary);
    background: transparent;
    border-bottom: 2px solid transparent;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.tab-btn:hover { color: var(--fg); }
.tab-btn.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
    background: #fff;
}
</style>

<script>
function activarTab(tabId) {
    document.querySelectorAll('.composer-pane').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById(tabId).style.display = 'block';
    
    const btnMap = {
        'tab-wa': 'btn-wa',
        'tab-email': 'btn-email',
        'tab-llamada': 'btn-llamada',
        'tab-tarea': 'btn-tarea',
        'tab-nota': 'btn-nota'
    };
    if (btnMap[tabId]) {
        document.getElementById(btnMap[tabId]).classList.add('active');
    }
}

function cargarPlantillaWA(select) {
    if (select.value) {
        document.getElementById('wa_mensaje').value = select.value;
    }
}

function cargarPlantillaEmail(select) {
    const selectedOption = select.options[select.selectedIndex];
    if (select.value) {
        document.getElementById('email_cuerpo').value = select.value.replace(/<br\s*[\/]?>/gi, "\n").replace(/<[^>]+>/g, '');
        const asunto = selectedOption.getAttribute('data-asunto');
        if (asunto) {
            document.getElementById('email_asunto').value = asunto;
        }
    }
}

function confirmarEliminar() {
    if (confirm('¿Estás seguro de que deseas eliminar este contacto y todo su historial de actividades y negocios?')) {
        document.getElementById('formEliminar').submit();
    }
}

// FUNCIONES DEL COPILOTO IA POWER PACK
var ultimoTextoIA = '';
var ultimoAsuntoIA = '';

function abrirModalIA(canal) {
    if (canal) {
        document.getElementById('ia_canal').value = canal;
    }
    document.getElementById('modalCopilotoIA').style.display = 'flex';
}

function generarTextoIA() {
    var btn = document.getElementById('btn-generar-ia');
    var canal = document.getElementById('ia_canal').value;
    var objetivo = document.getElementById('ia_objetivo').value;
    var inst = document.getElementById('ia_instrucciones').value;
    var box = document.getElementById('ia_resultado_box');
    var tag = document.getElementById('ia_origen_tag');
    var asuntoBox = document.getElementById('ia_asunto_box');
    var msgBox = document.getElementById('ia_mensaje_box');

    btn.disabled = true;
    btn.innerText = '⚡ Consultando Repositorio y Redactando...';

    fetch('api.php?action=generar_ia', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            contacto_id: <?= (int)$c['id'] ?>,
            canal: canal,
            objetivo: objetivo,
            instrucciones: inst
        })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = '⚡ Generar Redacción Asistida con IA';
        
        if (data.ok) {
            box.style.display = 'block';
            tag.innerText = 'Generado con: ' + (data.origen || 'Power Pack AI');
            ultimoTextoIA = data.mensaje;
            ultimoAsuntoIA = data.asunto || '';

            if (canal === 'email' && data.asunto) {
                asuntoBox.style.display = 'block';
                asuntoBox.innerHTML = '<strong>Asunto:</strong> ' + data.asunto;
                msgBox.innerHTML = data.mensaje;
            } else {
                asuntoBox.style.display = 'none';
                msgBox.innerText = data.mensaje;
            }
        } else {
            alert('Error de IA: ' + (data.error || 'No se pudo generar'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = '⚡ Generar Redacción Asistida con IA';
        alert('Error de conexión con la IA: ' + err.message);
    });
}

function aplicarAlEditor() {
    var canal = document.getElementById('ia_canal').value;
    if (canal === 'whatsapp') {
        activarTab('tab-wa');
        document.getElementById('wa_mensaje').value = ultimoTextoIA;
    } else {
        activarTab('tab-email');
        if (ultimoAsuntoIA) {
            document.getElementById('email_asunto').value = ultimoAsuntoIA;
        }
        document.getElementById('email_cuerpo').value = ultimoTextoIA.replace(/<br\s*[\/]?>/gi, "\n").replace(/<[^>]+>/g, '');
    }
    document.getElementById('modalCopilotoIA').style.display = 'none';
}

function copiarTextoIA() {
    var txt = document.getElementById('ia_mensaje_box').innerText;
    navigator.clipboard.writeText(txt).then(() => {
        alert('¡Texto copiado al portapapeles!');
    });
}
</script>

<!-- MODAL COPILOTO IA POWER PACK -->
<div id="modalCopilotoIA" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.65);z-index:9999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px)">
    <div style="background:#fff;border-radius:12px;max-width:640px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);overflow:hidden">
        <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);padding:18px 24px;border-bottom:3px solid #2c60a4;display:flex;justify-content:space-between;align-items:center">
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:22px">✨</span>
                <div>
                    <h3 style="color:#fff;margin:0;font-size:16px;font-weight:800">Copiloto IA Power Pack</h3>
                    <div style="color:#94a3b8;font-size:11px">Redacción comercial asistida con la Base de Conocimiento de la empresa</div>
                </div>
            </div>
            <button onclick="document.getElementById('modalCopilotoIA').style.display='none'" style="background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer">&times;</button>
        </div>

        <div style="padding:22px;display:flex;flex-direction:column;gap:14px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div>
                    <label style="font-size:12px;font-weight:700">Canal:</label>
                    <select id="ia_canal" style="width:100%;padding:8px 10px;font-size:13px;margin-top:4px">
                        <option value="whatsapp">📱 Mensaje de WhatsApp</option>
                        <option value="email">✉️ Correo Electrónico Formal</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:700">Objetivo Comercial:</label>
                    <select id="ia_objetivo" style="width:100%;padding:8px 10px;font-size:13px;margin-top:4px">
                        <option value="seguimiento_feria">🎪 Seguimiento Post-Feria</option>
                        <option value="primer_contacto">🤝 Presentación Comercial Inicial</option>
                        <option value="propuesta">📄 Presentación de Cotización / Maquinaria</option>
                        <option value="agendar_visita">🏢 Invitar al Showroom en Bogotá</option>
                        <option value="reactivacion">🧊 Reactivar Cliente sin respuesta</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="font-size:12px;font-weight:700">Instrucciones o requerimiento del cliente (Opcional):</label>
                <input type="text" id="ia_instrucciones" placeholder="Ej: Mencionar que tenemos entrega inmediata y 1 año de garantía..." style="width:100%;margin-top:4px;font-size:13px">
            </div>

            <button type="button" onclick="generarTextoIA()" class="btn btn-primary" id="btn-generar-ia" style="background:#2c60a4;padding:10px 20px;font-size:13px;font-weight:800;width:100%">
                ⚡ Generar Redacción Asistida con IA
            </button>

            <!-- CONTENEDOR DEL RESULTADO -->
            <div id="ia_resultado_box" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <span id="ia_origen_tag" style="font-size:11px;font-weight:800;color:#2c60a4"></span>
                    <button type="button" onclick="copiarTextoIA()" class="btn btn-secondary btn-sm" style="padding:3px 8px;font-size:11px">📋 Copiar</button>
                </div>
                <div id="ia_asunto_box" style="display:none;font-weight:800;font-size:13px;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #cbd5e1;color:#0f172a"></div>
                <div id="ia_mensaje_box" style="font-size:13px;line-height:1.5;max-height:220px;overflow-y:auto;white-space:pre-wrap;color:#334155;background:#fff;padding:10px;border-radius:6px;border:1px solid #e2e8f0"></div>

                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px">
                    <button type="button" onclick="aplicarAlEditor()" class="btn btn-primary btn-sm" style="background:#059669;font-weight:800;padding:8px 18px">
                        ✅ Cargar en el Editor y Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>