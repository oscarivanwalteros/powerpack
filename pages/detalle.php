<?php
$id = (int)($_GET['id'] ?? $id ?? 0);
if ($id <= 0) {
    echo "<div style='text-align:center;padding:60px 20px'><h2>Contacto no especificado</h2><p><a href='?page=contactos' class='btn btn-secondary' style='margin-top:10px'>Volver a Contactos</a></p></div>";
    return;
}
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

require_once __DIR__ . '/../ai.php';
$skills_wa = get_active_skills($db, 'whatsapp');
$skills_email = get_active_skills($db, 'email');
$todas_skills = get_active_skills($db, 'ambos');
$ai_key = get_config($db, 'ai_api_key', '');
$ai_provider = get_config($db, 'ai_provider', 'gemini');

$clean_tel = limpiar_telefono_whatsapp($c['telefono']);
?>

<!-- Header del Contacto estilo HubSpot -->
<div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;box-shadow:var(--shadow-sm);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px">
    <div style="display:flex;align-items:center;gap:18px">
        <?= avatar_iniciales($c['nombre'], $c['apellido']) ?>
        <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <h1 style="font-size:22px;font-weight:800"><?= h($c['nombre'] . ' ' . $c['apellido']) ?></h1>
                <?= etapa_badge($c['etapa']) ?>
                <span id="badge-prioridad-header"><?= prioridad_badge($c['prioridad'] ?? 'media') ?></span>
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
                    <div style="font-size:11px;color:var(--fg-secondary);font-weight:700;text-transform:uppercase">Prioridad Comercial</div>
                    <div style="margin-top:4px;display:flex;align-items:center;gap:6px">
                        <select onchange="cambiarPrioridadRapidaDetalle(<?= (int)$c['id'] ?>, this.value)" 
                                id="select_prioridad_sidebar"
                                style="padding:4px 8px;font-size:12px;font-weight:700;border-radius:6px;border:1px solid #cbd5e1;background:#fff;cursor:pointer">
                            <option value="alta" <?= ($c['prioridad'] ?? 'media')==='alta'?'selected':'' ?>>🔥 Alta (VIP / Inminente)</option>
                            <option value="media" <?= ($c['prioridad'] ?? 'media')==='media'?'selected':'' ?>>🟡 Media (Estándar)</option>
                            <option value="baja" <?= ($c['prioridad'] ?? 'media')==='baja'?'selected':'' ?>>⚪ Baja (Frío / En Espera)</option>
                        </select>
                        <span id="prio_guardado_pill" style="display:none;font-size:10px;font-weight:700;color:#059669;background:#ecfdf5;padding:2px 6px;border-radius:4px;border:1px solid #a7f3d0">✓ Guardado</span>
                    </div>
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
            <div style="display:flex;border-bottom:1px solid var(--border);background:#f8fafc;padding:0 8px;overflow-x:auto;align-items:center">
                <button type="button" class="tab-btn active" id="btn-wa" onclick="activarTab('tab-wa')">💬 WhatsApp</button>
                <button type="button" class="tab-btn" id="btn-email" onclick="activarTab('tab-email')">✉️ Correo Electrónico</button>
                <button type="button" class="tab-btn" id="btn-llamada" onclick="activarTab('tab-llamada')">📞 Registrar Llamada</button>
                <button type="button" class="tab-btn" id="btn-tarea" onclick="activarTab('tab-tarea')">✅ Programar Tarea</button>
                <button type="button" class="tab-btn" id="btn-nota" onclick="activarTab('tab-nota')">📝 Nota Interna</button>

                <div style="margin-left:auto;display:flex;align-items:center;padding:0 8px;gap:6px">
                    <span id="gemini_status_pill" style="font-size:11px;font-weight:700;color:<?= !empty($ai_key) ? '#059669' : '#d97706' ?>;background:<?= !empty($ai_key) ? '#ecfdf5' : '#fef3c7' ?>;padding:3px 10px;border-radius:12px;border:1px solid <?= !empty($ai_key) ? '#a7f3d0' : '#fde68a' ?>">
                        <?= !empty($ai_key) ? '● Gemini Conectado' : '⚡ Motor Interno' ?>
                    </span>
                    <button type="button" onclick="abrirModalConectarGemini()" class="btn btn-secondary btn-sm" style="font-size:11px;padding:3px 8px;font-weight:700" title="Configurar o conectar API Key de Gemini">
                        🔑 Clave Gemini
                    </button>
                </div>
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

                        <!-- BARRA GENERADOR RÁPIDO CON SKILL B2B (AUTO-PEGADO) -->
                        <div style="background:linear-gradient(135deg, #f0fdf4 0%, #eff6ff 100%);border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="font-size:18px">⚡</span>
                                <div>
                                    <strong style="font-size:12px;color:#1e3a8a">Generar con Skill B2B:</strong>
                                    <span style="font-size:11px;color:#64748b;display:block">Redacta y pega automáticamente en el cuadro de WhatsApp</span>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;flex:1;justify-content:flex-end;min-width:260px">
                                <select id="quick_skill_wa" style="font-size:12px;padding:6px 10px;border-radius:6px;border:1px solid #93c5fd;background:#fff;font-weight:600;max-width:320px">
                                    <?php foreach($skills_wa as $sk): ?>
                                    <option value="<?= h($sk['codigo']) ?>"><?= h($sk['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" onclick="generarYPegar('whatsapp')" id="btn-quick-wa" class="btn btn-sm" style="background:#2c60a4;color:#fff;font-weight:800;padding:7px 14px;white-space:nowrap;box-shadow:0 2px 4px rgba(44,96,164,0.25)">
                                    ⚡ Generar y Pegar
                                </button>
                            </div>
                            <div id="quick_status_wa" style="width:100%;display:none;font-size:11px;padding:5px 8px;border-radius:4px;font-weight:700"></div>
                        </div>

                        <div class="form-group">
                            <label>Mensaje de WhatsApp</label>
                            <textarea name="mensaje" id="wa_mensaje" rows="4" required placeholder="Escribe el mensaje o selecciona una plantilla arriba..."><?= h(reemplazar_variables("¡Hola {nombre}! Un gusto saludarte de parte de {empresa_nombre}. Vimos lo que hacen en {empresa}. ¿Cómo están optimizando actualmente sus procesos?", $c, $config)) ?></textarea>
                        </div>
                        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                            <button type="submit" name="abrir_whatsapp" value="1" class="btn btn-whatsapp">
                                🚀 Abrir WhatsApp Web y Registrar en Historial
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

                        <!-- BARRA GENERADOR RÁPIDO CON SKILL B2B (AUTO-PEGADO EN CORREO) -->
                        <div style="background:linear-gradient(135deg, #f0fdf4 0%, #eff6ff 100%);border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="font-size:18px">⚡</span>
                                <div>
                                    <strong style="font-size:12px;color:#1e3a8a">Generar Correo con Skill B2B:</strong>
                                    <span style="font-size:11px;color:#64748b;display:block">Redacta asunto y propuesta persuasiva y los pega automáticamente</span>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;flex:1;justify-content:flex-end;min-width:260px">
                                <select id="quick_skill_email" style="font-size:12px;padding:6px 10px;border-radius:6px;border:1px solid #93c5fd;background:#fff;font-weight:600;max-width:320px">
                                    <?php foreach($skills_email as $sk): ?>
                                    <option value="<?= h($sk['codigo']) ?>"><?= h($sk['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" onclick="generarYPegar('email')" id="btn-quick-email" class="btn btn-sm" style="background:#2c60a4;color:#fff;font-weight:800;padding:7px 14px;white-space:nowrap;box-shadow:0 2px 4px rgba(44,96,164,0.25)">
                                    ⚡ Generar y Pegar en Correo
                                </button>
                            </div>
                            <div id="quick_status_email" style="width:100%;display:none;font-size:11px;padding:5px 8px;border-radius:4px;font-weight:700"></div>
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
            <div class="form-row-3">
                <div class="form-group"><label style="font-weight:700">Prioridad Comercial</label>
                    <select name="prioridad" style="font-weight:700">
                        <option value="alta" <?= ($c['prioridad'] ?? 'media')==='alta'?'selected':'' ?>>🔥 Alta (VIP / Cierre Inminente)</option>
                        <option value="media" <?= ($c['prioridad'] ?? 'media')==='media'?'selected':'' ?>>🟡 Media (Estándar)</option>
                        <option value="baja" <?= ($c['prioridad'] ?? 'media')==='baja'?'selected':'' ?>>⚪ Baja (Frío / En Espera)</option>
                    </select>
                </div>
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

function cambiarPrioridadRapidaDetalle(id, prioridad) {
    var pill = document.getElementById('prio_guardado_pill');
    var badgeHdr = document.getElementById('badge-prioridad-header');
    
    fetch('api.php?action=cambiar_prioridad', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id, prioridad: prioridad })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            if (pill) {
                pill.style.display = 'inline-block';
                setTimeout(() => { pill.style.display = 'none'; }, 2000);
            }
            if (badgeHdr) {
                if (prioridad === 'alta') {
                    badgeHdr.innerHTML = '<span class="badge" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-weight:800;font-size:11px">🔥 Alta</span>';
                } else if (prioridad === 'baja') {
                    badgeHdr.innerHTML = '<span class="badge" style="background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1;font-weight:700;font-size:11px">⚪ Baja</span>';
                } else {
                    badgeHdr.innerHTML = '<span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-weight:700;font-size:11px">🟡 Media</span>';
                }
            }
        }
    })
    .catch(err => {
        alert('Error al actualizar prioridad: ' + err.message);
    });
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

// FUNCIONES DEL COPILOTO IA POWER PACK & SKILLS B2B
var ultimoTextoIA = '';
var ultimoAsuntoIA = '';

// Función 1-Click: Generar con Skill B2B y Pegar Automáticamente en el Editor
function generarYPegar(canal) {
    var skill = (canal === 'whatsapp') 
        ? document.getElementById('quick_skill_wa').value 
        : document.getElementById('quick_skill_email').value;
    var btn = (canal === 'whatsapp') ? document.getElementById('btn-quick-wa') : document.getElementById('btn-quick-email');
    var statusDiv = (canal === 'whatsapp') ? document.getElementById('quick_status_wa') : document.getElementById('quick_status_email');

    btn.disabled = true;
    var origText = btn.innerHTML;
    btn.innerHTML = '⚡ Generando con Skill...';
    statusDiv.style.display = 'block';
    statusDiv.style.background = '#eff6ff';
    statusDiv.style.color = '#1e40af';
    statusDiv.innerText = 'Consultando base de conocimiento de Power Pack y redactando con la IA...';

    // Determinar objetivo comercial según skill
    var obj = 'seguimiento_feria';
    if (skill === 'pas_problema' || skill === 'bab_transformacion') obj = 'propuesta';
    if (skill === 'reactivacion_fria') obj = 'reactivacion';
    if (skill === 'invitacion_showroom') obj = 'agendar_visita';

    fetch('api.php?action=generar_ia', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            contacto_id: <?= (int)$c['id'] ?>,
            canal: canal,
            skill: skill,
            objetivo: obj
        })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = origText;

        if (data.ok) {
            if (canal === 'whatsapp') {
                var waField = document.getElementById('wa_mensaje');
                waField.value = data.mensaje;
                waField.style.borderColor = '#059669';
                waField.style.boxShadow = '0 0 0 3px rgba(5,150,105,0.25)';
                setTimeout(() => {
                    waField.style.borderColor = '';
                    waField.style.boxShadow = '';
                }, 1800);
            } else {
                if (data.asunto) {
                    var asuntoField = document.getElementById('email_asunto');
                    asuntoField.value = data.asunto;
                    asuntoField.style.borderColor = '#059669';
                    asuntoField.style.boxShadow = '0 0 0 3px rgba(5,150,105,0.25)';
                    setTimeout(() => {
                        asuntoField.style.borderColor = '';
                        asuntoField.style.boxShadow = '';
                    }, 1800);
                }
                var cleanBody = data.mensaje.replace(/<br\s*[\/]?>/gi, "\n").replace(/<p>/gi, '').replace(/<\/p>/gi, "\n\n").replace(/<li>/gi, "• ").replace(/<\/li>/gi, "\n").replace(/<[^>]+>/g, '').trim();
                var emailField = document.getElementById('email_cuerpo');
                emailField.value = cleanBody;
                emailField.style.borderColor = '#059669';
                emailField.style.boxShadow = '0 0 0 3px rgba(5,150,105,0.25)';
                setTimeout(() => {
                    emailField.style.borderColor = '';
                    emailField.style.boxShadow = '';
                }, 1800);
            }

            statusDiv.style.background = '#ecfdf5';
            statusDiv.style.color = '#065f46';
            statusDiv.innerHTML = '✅ <strong>¡Redacción generada y pegada automáticamente!</strong> <span style="font-size:10px;color:#047857">(' + (data.origen || 'Power Pack AI') + ')</span>';
            setTimeout(() => { statusDiv.style.display = 'none'; }, 6500);
        } else {
            statusDiv.style.background = '#fef2f2';
            statusDiv.style.color = '#991b1b';
            statusDiv.innerText = '⚠️ Error al generar: ' + (data.error || 'No se pudo conectar.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origText;
        statusDiv.style.background = '#fef2f2';
        statusDiv.style.color = '#991b1b';
        statusDiv.innerText = '⚠️ Error de conexión: ' + err.message;
    });
}

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
    var skill = document.getElementById('ia_skill').value;
    var inst = document.getElementById('ia_instrucciones').value;
    var box = document.getElementById('ia_resultado_box');
    var tag = document.getElementById('ia_origen_tag');
    var asuntoBox = document.getElementById('ia_asunto_box');
    var msgBox = document.getElementById('ia_mensaje_box');

    btn.disabled = true;
    btn.innerText = '⚡ Consultando Repositorio y Redactando con IA...';

    fetch('api.php?action=generar_ia', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            contacto_id: <?= (int)$c['id'] ?>,
            canal: canal,
            skill: skill,
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

// FUNCIONES PARA CONEXIÓN DE GOOGLE GEMINI CON 1-CLIC
function abrirModalConectarGemini() {
    var modal = document.getElementById('modalConectarGemini');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function pegarApiKeyDesdeClipboard(inputId) {
    var input = document.getElementById(inputId);
    if (!navigator.clipboard || !navigator.clipboard.readText) {
        var manual = prompt('Pega aquí tu API Key de Google Gemini:');
        if (manual && manual.trim()) {
            input.value = manual.trim();
            input.type = 'text';
        }
        return;
    }
    navigator.clipboard.readText()
        .then(function(text) {
            text = (text || '').trim();
            if (text) {
                input.value = text;
                input.type = 'text';
                input.focus();
                input.style.borderColor = '#059669';
                input.style.boxShadow = '0 0 0 3px rgba(5,150,105,0.25)';
                setTimeout(function() {
                    input.style.borderColor = '';
                    input.style.boxShadow = '';
                }, 1800);
            } else {
                alert('El portapapeles está vacío. Por favor copia primero la API Key desde Google AI Studio.');
            }
        })
        .catch(function() {
            var manual = prompt('Por favor pega aquí la API Key copiada de Google AI Studio:');
            if (manual && manual.trim()) {
                input.value = manual.trim();
                input.type = 'text';
            }
        });
}

function toggleVisibilidadPassword(inputId, btnId) {
    var input = document.getElementById(inputId);
    var btn = btnId ? document.getElementById(btnId) : null;
    if (input.type === 'password') {
        input.type = 'text';
        if (btn) btn.innerText = '🙈 Ocultar';
    } else {
        input.type = 'password';
        if (btn) btn.innerText = '👁️ Mostrar';
    }
}

function conectarGeminiAJAX(inputId, statusId, provider, model) {
    var input = document.getElementById(inputId);
    var key = input ? input.value.trim() : '';
    var statusDiv = document.getElementById(statusId);
    
    statusDiv.style.display = 'block';
    statusDiv.style.background = '#eff6ff';
    statusDiv.style.color = '#1e40af';
    statusDiv.style.border = '1px solid #bfdbfe';
    statusDiv.style.padding = '10px 12px';
    statusDiv.style.borderRadius = '6px';
    statusDiv.style.fontSize = '12px';
    statusDiv.innerText = '⏳ Verificando API Key en tiempo real con Google Gemini...';

    fetch('api.php?action=guardar_ai_key', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            api_key: key,
            provider: provider || 'gemini',
            model: (model && model !== 'gemini-2.0-flash') ? model : 'gemini-3.8-flash'
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.ok && !res.warning) {
            statusDiv.style.background = '#ecfdf5';
            statusDiv.style.color = '#065f46';
            statusDiv.style.border = '1px solid #a7f3d0';
            statusDiv.innerHTML = '✅ <strong>¡Conectado exitosamente!</strong> ' + res.mensaje;
            
            var pill = document.getElementById('gemini_status_pill');
            if (pill) {
                if (res.activo) {
                    pill.style.color = '#059669';
                    pill.style.background = '#ecfdf5';
                    pill.style.borderColor = '#a7f3d0';
                    pill.innerText = '● Gemini Conectado';
                } else {
                    pill.style.color = '#d97706';
                    pill.style.background = '#fef3c7';
                    pill.style.borderColor = '#fde68a';
                    pill.innerText = '⚡ Motor Interno';
                }
            }
        } else if (res.warning) {
            statusDiv.style.background = '#fffbeb';
            statusDiv.style.color = '#92400e';
            statusDiv.style.border = '1px solid #fde68a';
            statusDiv.innerHTML = '⚠️ <strong>Aviso:</strong> ' + res.mensaje;
        } else {
            statusDiv.style.background = '#fef2f2';
            statusDiv.style.color = '#991b1b';
            statusDiv.style.border = '1px solid #fecaca';
            statusDiv.innerHTML = '❌ <strong>Error:</strong> ' + (res.error || res.mensaje || 'No se pudo conectar.');
        }
    })
    .catch(function(err) {
        statusDiv.style.background = '#fef2f2';
        statusDiv.style.color = '#991b1b';
        statusDiv.style.border = '1px solid #fecaca';
        statusDiv.innerHTML = '❌ <strong>Error de conexión:</strong> ' + err.message;
    });
}
</script>

<!-- MODAL COPILOTO IA POWER PACK -->
<div id="modalCopilotoIA" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.65);z-index:9999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px)">
    <div style="background:#fff;border-radius:12px;max-width:660px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);overflow:hidden">
        <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);padding:18px 24px;border-bottom:3px solid #2c60a4;display:flex;justify-content:space-between;align-items:center">
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:22px">✨</span>
                <div>
                    <h3 style="color:#fff;margin:0;font-size:16px;font-weight:800">Copiloto IA Power Pack</h3>
                    <div style="color:#94a3b8;font-size:11px">Redacción comercial asistida con Skills B2B y Base de Conocimiento</div>
                </div>
            </div>
            <button onclick="document.getElementById('modalCopilotoIA').style.display='none'" style="background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer">&times;</button>
        </div>

        <div style="padding:22px;display:flex;flex-direction:column;gap:14px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div>
                    <label style="font-size:12px;font-weight:700">Canal Destino:</label>
                    <select id="ia_canal" style="width:100%;padding:8px 10px;font-size:13px;margin-top:4px;font-weight:600">
                        <option value="whatsapp">📱 Mensaje de WhatsApp</option>
                        <option value="email">✉️ Correo Electrónico Formal</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:700">Skill B2B Especializada:</label>
                    <select id="ia_skill" style="width:100%;padding:8px 10px;font-size:13px;margin-top:4px;font-weight:600">
                        <option value="">— Seleccionar Skill B2B —</option>
                        <?php foreach($todas_skills as $sk): ?>
                        <option value="<?= h($sk['codigo']) ?>"><?= h($sk['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label style="font-size:12px;font-weight:700">Objetivo Comercial Base:</label>
                <select id="ia_objetivo" style="width:100%;padding:8px 10px;font-size:13px;margin-top:4px">
                    <option value="seguimiento_feria">🎪 Seguimiento Post-Feria</option>
                    <option value="primer_contacto">🤝 Presentación Comercial Inicial</option>
                    <option value="propuesta">📄 Presentación de Cotización / Maquinaria</option>
                    <option value="agendar_visita">🏢 Invitar al Showroom en Bogotá</option>
                    <option value="reactivacion">🧊 Reactivar Cliente sin respuesta</option>
                </select>
            </div>

            <div>
                <label style="font-size:12px;font-weight:700">Instrucciones o detalles adicionales del asesor (Opcional):</label>
                <input type="text" id="ia_instrucciones" placeholder="Ej: Destacar entrega inmediata y preguntar por volumen diario..." style="width:100%;margin-top:4px;font-size:13px">
            </div>

            <button type="button" onclick="generarTextoIA()" class="btn btn-primary" id="btn-generar-ia" style="background:#2c60a4;padding:11px 20px;font-size:14px;font-weight:800;width:100%">
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
                        ✅ Pegar en el Editor y Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CONECTAR API KEY DE GOOGLE GEMINI -->
<div id="modalConectarGemini" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.7);z-index:10000;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)">
    <div style="background:#fff;border-radius:14px;max-width:580px;width:100%;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);overflow:hidden">
        <div style="background:linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);padding:18px 24px;display:flex;justify-content:space-between;align-items:center;color:#fff">
            <div style="display:flex;align-items:center;gap:12px">
                <span style="font-size:24px">⚡</span>
                <div>
                    <h3 style="margin:0;font-size:17px;font-weight:800;color:#fff">Conectar Google Gemini AI</h3>
                    <div style="font-size:12px;color:#bfdbfe">Inteligencia artificial para redacción comercial de correos y WhatsApp</div>
                </div>
            </div>
            <button onclick="document.getElementById('modalConectarGemini').style.display='none'" style="background:none;border:none;color:#bfdbfe;font-size:24px;cursor:pointer;line-height:1">&times;</button>
        </div>

        <div style="padding:24px;display:flex;flex-direction:column;gap:18px">
            <!-- PASO 1 -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
                    <div>
                        <div style="font-size:13px;font-weight:800;color:#0f172a">Paso 1: Obtener API Key de Google</div>
                        <div style="font-size:11px;color:#64748b;margin-top:2px">Es 100% gratuita, sin tarjeta de crédito, en Google AI Studio.</div>
                    </div>
                    <a href="https://aistudio.google.com/app/apikey" target="_blank" class="btn btn-primary btn-sm" style="background:#2563eb;white-space:nowrap;font-size:12px;padding:8px 14px;font-weight:700" title="Abre Google AI Studio en nueva pestaña">
                        🔑 Abrir Google AI Studio ↗
                    </a>
                </div>
            </div>

            <!-- PASO 2 -->
            <div>
                <label style="font-size:13px;font-weight:800;color:#0f172a;display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <span>Paso 2: Pegar tu API Key de Gemini:</span>
                    <button type="button" onclick="pegarApiKeyDesdeClipboard('modal_gemini_key')" class="btn btn-secondary btn-sm" style="font-size:11px;padding:3px 10px;font-weight:700;color:#1e40af;background:#eff6ff;border:1px solid #bfdbfe">
                        📋 Pegar desde Portapapeles
                    </button>
                </label>
                <div style="display:flex;gap:6px">
                    <input type="password" id="modal_gemini_key" value="<?= h($ai_key) ?>" placeholder="Pega aquí tu clave (inicia con AIzaSy...)" style="flex:1;padding:10px 12px;font-size:13px;font-family:monospace;border:1px solid #cbd5e1;border-radius:6px">
                    <button type="button" id="btn_toggle_gemini_key" onclick="toggleVisibilidadPassword('modal_gemini_key', 'btn_toggle_gemini_key')" class="btn btn-secondary btn-sm" style="padding:0 12px;font-size:12px">
                        👁️ Mostrar
                    </button>
                </div>
                <div style="font-size:11px;color:#64748b;margin-top:4px">
                    Tu clave se guarda en tu base de datos y se utiliza para redactar correos y WhatsApp.
                </div>
            </div>

            <!-- PASO 3 -->
            <div>
                <button type="button" onclick="conectarGeminiAJAX('modal_gemini_key', 'modal_gemini_status', 'gemini', 'gemini-3.8-flash')" class="btn btn-primary" style="width:100%;padding:12px;font-size:14px;font-weight:800;background:#059669;display:flex;align-items:center;justify-content:center;gap:8px">
                    <span>⚡ Paso 3: Conectar y Validar Clave en Vivo</span>
                </button>
            </div>

            <!-- RESULTADO / STATUS -->
            <div id="modal_gemini_status" style="display:none"></div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid #f1f5f9">
                <button type="button" onclick="document.getElementById('modal_gemini_key').value='';conectarGeminiAJAX('modal_gemini_key', 'modal_gemini_status');" style="background:none;border:none;color:#dc2626;font-size:12px;cursor:pointer;text-decoration:underline">
                    Desconectar clave actual (usar motor interno)
                </button>
                <button type="button" onclick="document.getElementById('modalConectarGemini').style.display='none'" class="btn btn-secondary btn-sm" style="padding:6px 16px">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>