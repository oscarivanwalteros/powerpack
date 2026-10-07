<?php
/**
 * pages/correos.php - Estudio de Correos Comerciales IA & Subagente B2B
 * Power Pack SAS
 */

require_once __DIR__ . '/../ai.php';
require_once __DIR__ . '/../mailer.php';

$subagent = get_subagent_by_id('email');
$subagent_skills = $subagent['skills'] ?? [];

// Contacto preseleccionado si viene por URL (?page=correos&contacto_id=X)
$contacto_pre = null;
$cid_param = (int)($_GET['contacto_id'] ?? 0);
if ($cid_param > 0) {
    $contacto_pre = $db->querySingle("SELECT * FROM contactos WHERE id = $cid_param", true);
}

// Cargar lista de contactos para selector
$contactos_list = [];
$res_c = $db->query("SELECT id, nombre, apellido, email, empresa, cargo, ciudad, prioridad, etapa FROM contactos WHERE email IS NOT NULL AND email != '' ORDER BY (CASE prioridad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END) ASC, id DESC LIMIT 200");
if ($res_c) {
    while ($r = $res_c->fetchArray(SQLITE3_ASSOC)) {
        $contactos_list[] = $r;
    }
}

// Contadores de estadísticas de correos
$total_enviados = (int)$db->querySingle("SELECT COUNT(*) FROM mensajes_email WHERE estado = 'enviado'");
$total_masivos  = (int)$db->querySingle("SELECT COUNT(*) FROM mensajes_email WHERE es_masivo = 1");
$total_individual = (int)$db->querySingle("SELECT COUNT(*) FROM mensajes_email WHERE es_masivo = 0");
$total_fallidos = (int)$db->querySingle("SELECT COUNT(*) FROM mensajes_email WHERE estado = 'fallido'");

// Cargar historial reciente de correos
$historial_correos = [];
$res_h = $db->query("SELECT m.*, c.nombre as c_nombre, c.apellido as c_apellido, c.empresa as c_empresa FROM mensajes_email m LEFT JOIN contactos c ON m.contacto_id = c.id ORDER BY m.id DESC LIMIT 50");
if ($res_h) {
    while ($r = $res_h->fetchArray(SQLITE3_ASSOC)) {
        $historial_correos[] = $r;
    }
}
?>

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
        <div style="display:flex;align-items:center;gap:10px">
            <h1 style="font-size:22px;font-weight:800;color:var(--fg);margin:0">✉️ Estudio de Correos Comerciales IA</h1>
            <span style="background:rgba(44,96,164,0.12);color:var(--brand-blue);padding:3px 10px;border-radius:20px;font-size:11px;font-weight:800">
                Subagente Activo
            </span>
        </div>
        <p style="color:var(--fg-secondary);font-size:13px;margin-top:4px">
            Co-redacta propuestas con el Subagente de Email Copywriting y realiza envíos 1-a-1 o masivos con SMTP Hostinger.
        </p>
    </div>
    
    <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-secondary btn-sm" onclick="cambiarTabEstudio('tab-editor')" id="btn-tab-editor" style="background:#fff;border-color:var(--brand-blue);color:var(--brand-blue);font-weight:700">
            ✍️ Estudio de Redacción
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="cambiarTabEstudio('tab-historial')" id="btn-tab-historial">
            📬 Historial de Envíos (<?= $total_enviados ?>)
        </button>
    </div>
</div>

<!-- ========================================== -->
<!-- SECCIÓN 1: ESTUDIO & REDACCIÓN INTERACTIVA -->
<!-- ========================================== -->
<div id="seccion-estudio" style="display:block">
    <div style="display:grid;grid-template-columns: 390px 1fr;gap:20px;align-items:start" class="studio-grid">
        
        <!-- PANEL IZQUIERDO: SUBAGENTE & COPILOTO -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px;box-shadow:var(--shadow-sm);display:flex;flex-direction:column;gap:14px">
            
            <!-- TARJETA DEL SUBAGENTE -->
            <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);color:#fff;padding:14px;border-radius:8px;border-left:4px solid #2c60a4">
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:22px">✉️</span>
                        <div>
                            <div style="font-weight:800;font-size:13px">Senior B2B Email Copywriter</div>
                            <div style="font-size:11px;color:#94a3b8">Subagente de Redacción Persuasiva</div>
                        </div>
                    </div>
                    <span style="width:8px;height:8px;border-radius:50%;background:#10b981;box-shadow:0 0 8px #10b981" title="Subagente en línea"></span>
                </div>
                <div style="font-size:11px;color:#cbd5e1;margin-top:8px;line-height:1.4">
                    Especializado en prospectos industriales de empaque, sellado y dosificado con garantía y stock en Bogotá.
                </div>
            </div>

            <!-- SELECTOR DE SKILL / HABILIDAD COMERCIAL -->
            <div>
                <label style="display:block;font-size:11px;font-weight:800;color:var(--fg);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px">
                    🧠 Habilidad Comercial B2B (Skill):
                </label>
                <select id="select-skill" class="form-control" style="font-size:12px;font-weight:600" onchange="actualizarInfoSkill()">
                    <?php foreach ($subagent_skills as $idx => $sk): ?>
                        <option value="<?= h($sk['codigo'] ?? '') ?>" <?= $idx === 0 ? 'selected' : '' ?> data-desc="<?= h($sk['descripcion'] ?? '') ?>">
                            <?= h($sk['nombre'] ?? 'Skill') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="desc-skill-box" style="font-size:11px;color:var(--fg-secondary);margin-top:6px;background:var(--bg);padding:8px 10px;border-radius:6px;border-left:3px solid var(--brand-blue)">
                    <?= h($subagent_skills[0]['descripcion'] ?? 'Selecciona una técnica de redacción persuasiva.') ?>
                </div>
            </div>

            <!-- SELECTOR DE DESTINATARIO Y MODO DE ENVÍO -->
            <div style="border-top:1px solid var(--border);padding-top:12px">
                <label style="display:block;font-size:11px;font-weight:800;color:var(--fg);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px">
                    🎯 Modalidad de Envío:
                </label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:10px">
                    <button type="button" class="btn btn-sm" id="btn-modo-individual" onclick="setModoEnvio('individual')" style="background:var(--brand-blue);color:#fff;font-weight:700">
                        👤 1-a-1 Personal
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" id="btn-modo-masivo" onclick="setModoEnvio('masivo')">
                        📢 Masivo / Lote
                    </button>
                </div>

                <!-- SELECTOR INDIVIDUAL -->
                <div id="box-modo-individual">
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--fg-secondary);margin-bottom:4px">
                        Seleccionar Contacto:
                    </label>
                    <select id="select-contacto" class="form-control" style="font-size:12px" onchange="seleccionarContactoIndividual()">
                        <option value="">-- Redacción General / Prospecto Manual --</option>
                        <?php foreach ($contactos_list as $c): ?>
                            <?php 
                            $c_nombre = trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? ''));
                            $c_desc = $c_nombre . ($c['empresa'] ? " ({$c['empresa']})" : "") . " - " . $c['email'];
                            $sel = ($contacto_pre && $contacto_pre['id'] == $c['id']) ? 'selected' : '';
                            ?>
                            <option value="<?= $c['id'] ?>" <?= $sel ?> 
                                    data-nombre="<?= h($c_nombre) ?>" 
                                    data-empresa="<?= h($c['empresa'] ?? '') ?>" 
                                    data-cargo="<?= h($c['cargo'] ?? '') ?>" 
                                    data-ciudad="<?= h($c['ciudad'] ?? '') ?>" 
                                    data-email="<?= h($c['email'] ?? '') ?>"
                                    data-prioridad="<?= h($c['prioridad'] ?? 'media') ?>">
                                <?= h($c_desc) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px">
                        <div>
                            <label style="font-size:10px;font-weight:700;color:var(--fg-secondary)">Email Destino:</label>
                            <input type="email" id="dest-email-manual" class="form-control" style="font-size:12px;padding:6px" placeholder="cliente@empresa.com" value="<?= h($contacto_pre['email'] ?? '') ?>">
                        </div>
                        <div>
                            <label style="font-size:10px;font-weight:700;color:var(--fg-secondary)">Nombre / Empresa:</label>
                            <input type="text" id="dest-nombre-manual" class="form-control" style="font-size:12px;padding:6px" placeholder="Nombre cliente" value="<?= h(trim(($contacto_pre['nombre'] ?? '') . ' ' . ($contacto_pre['apellido'] ?? ''))) ?>">
                        </div>
                    </div>
                </div>

                <!-- SELECTOR MASIVO -->
                <div id="box-modo-masivo" style="display:none">
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--fg-secondary);margin-bottom:4px">
                        Filtrar Segmento de Contactos:
                    </label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div>
                            <label style="font-size:10px;font-weight:700;color:var(--fg-secondary)">Prioridad:</label>
                            <select id="filtro-masivo-prioridad" class="form-control" style="font-size:11px" onchange="calcularDestinatariosMasivos()">
                                <option value="">Todas</option>
                                <option value="alta" selected>🔥 Alta Prioridad</option>
                                <option value="media">⚡ Media Prioridad</option>
                                <option value="baja">🌱 Baja Prioridad</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:10px;font-weight:700;color:var(--fg-secondary)">Etapa:</label>
                            <select id="filtro-masivo-etapa" class="form-control" style="font-size:11px" onchange="calcularDestinatariosMasivos()">
                                <option value="">Todas las Etapas</option>
                                <option value="lead">Lead</option>
                                <option value="contacto">Contactado</option>
                                <option value="cotizacion">Cotización</option>
                                <option value="negociacion">Negociación</option>
                            </select>
                        </div>
                    </div>
                    <div id="badge-conteo-masivo" style="margin-top:8px;font-size:11px;padding:6px 10px;background:#eff6ff;color:#1e40af;border-radius:6px;font-weight:700;border:1px solid #bfdbfe">
                        📊 Calculando destinatarios...
                    </div>
                </div>
            </div>

            <!-- CHAT INTERACTIVO CON EL SUBAGENTE -->
            <div style="border-top:1px solid var(--border);padding-top:12px;display:flex;flex-direction:column;flex:1">
                <label style="display:block;font-size:11px;font-weight:800;color:var(--fg);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px">
                    💬 Diálogo con el Subagente:
                </label>
                
                <div id="chat-subagente-timeline" style="background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px;max-height:220px;overflow-y:auto;display:flex;flex-direction:column;gap:10px;font-size:12px;margin-bottom:10px">
                    <div style="display:flex;gap:8px;align-items:flex-start">
                        <span style="font-size:18px">✉️</span>
                        <div style="background:#fff;padding:8px 12px;border-radius:8px;border:1px solid var(--border);color:var(--fg);line-height:1.4">
                            ¡Hola! Soy tu <strong>Subagente B2B</strong>. Selecciona una habilidad comercial arriba o dime qué mensaje deseas transmitir y redactaré el borrador perfecto para tu cliente.
                        </div>
                    </div>
                </div>

                <!-- SUGERENCIAS RÁPIDAS DE COPILOTO -->
                <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px">
                    <button type="button" class="btn-chip" onclick="aplicarPromptRapido('Hazlo más breve y directo al grano (máximo 2 párrafos)')">⚡ Más breve</button>
                    <button type="button" class="btn-chip" onclick="aplicarPromptRapido('Destaca los 12 meses de garantía y stock para despacho inmediato en Bogotá')">🛡️ Garantía & Stock</button>
                    <button type="button" class="btn-chip" onclick="aplicarPromptRapido('Invita formalmente a traer muestras a nuestro Showroom en Bogotá para pruebas en vivo')">🏢 Showroom</button>
                    <button type="button" class="btn-chip" onclick="aplicarPromptRapido('Enfócate en la reducción de costos y eliminación de mermas de empaque')">💰 ROI & Merma</button>
                </div>

                <!-- INPUT DE INSTRUCCIÓN -->
                <div style="display:flex;gap:6px">
                    <input type="text" id="input-subagente-prompt" class="form-control" style="font-size:12px" placeholder="Ej: Redacta un primer contacto para el gerente..." onkeydown="if(event.key==='Enter') pedirAlSubagente()">
                    <button type="button" id="btn-pedir-subagente" class="btn btn-primary" onclick="pedirAlSubagente()" style="padding:6px 14px;font-size:12px;font-weight:700">
                        <span id="txt-pedir-subagente">Co-Redactar</span>
                        <span id="spinner-subagente" style="display:none">⏳</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- PANEL DERECHO: EDITOR EN VIVO & PREVISUALIZACIÓN -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow-sm);display:flex;flex-direction:column;gap:14px">
            
            <!-- BARRA SUPERIOR DEL EDITOR -->
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;border-bottom:1px solid var(--border);padding-bottom:12px">
                <div style="display:flex;gap:6px">
                    <button type="button" class="btn btn-sm" id="tab-btn-editar" onclick="toggleVistaEditor('editor')" style="background:var(--brand-blue);color:#fff;font-weight:700">
                        📝 Editor
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" id="tab-btn-preview" onclick="toggleVistaEditor('preview')">
                        👁️ Vista Previa Corporativa
                    </button>
                </div>

                <!-- VARIABLES DINÁMICAS -->
                <div style="display:flex;align-items:center;gap:4px">
                    <span style="font-size:10px;font-weight:800;color:var(--fg-secondary);text-transform:uppercase">Insertar:</span>
                    <button type="button" class="btn-chip" onclick="insertarVariable('{nombre}')">{nombre}</button>
                    <button type="button" class="btn-chip" onclick="insertarVariable('{empresa}')">{empresa}</button>
                    <button type="button" class="btn-chip" onclick="insertarVariable('{cargo}')">{cargo}</button>
                    <button type="button" class="btn-chip" onclick="insertarVariable('{ciudad}')">{ciudad}</button>
                </div>
            </div>

            <!-- ASUNTO DEL CORREO -->
            <div>
                <label style="display:block;font-size:11px;font-weight:800;color:var(--fg);margin-bottom:4px;text-transform:uppercase;letter-spacing:0.5px">
                    📌 Línea de Asunto:
                </label>
                <input type="text" id="email-asunto" class="form-control" style="font-size:14px;font-weight:700" placeholder="Ingresa o genera el asunto con el subagente...">
            </div>

            <!-- VISTA EDITOR -->
            <div id="panel-editor-texto">
                <label style="display:block;font-size:11px;font-weight:800;color:var(--fg);margin-bottom:4px;text-transform:uppercase;letter-spacing:0.5px">
                    📄 Cuerpo del Correo (HTML / Formato Profesional):
                </label>
                <textarea id="email-cuerpo" class="form-control" style="height:320px;font-family:monospace;font-size:13px;line-height:1.5" placeholder="Escribe el contenido o pídele al subagente que lo redacte aplicando una de sus habilidades..."></textarea>
                <div style="font-size:11px;color:var(--fg-secondary);margin-top:4px">
                    💡 Tip: Puedes usar etiquetas HTML como &lt;p&gt;, &lt;strong&gt;, &lt;ul&gt;, &lt;li&gt; o texto enriquecido directo.
                </div>
            </div>

            <!-- VISTA PREVIA CORPORATIVA EN VIVO -->
            <div id="panel-editor-preview" style="display:none;background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:20px;min-height:350px">
                <div style="max-width:620px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.06)">
                    <!-- CABECERA POWER PACK -->
                    <div style="background:#0f172a;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid #2c60a4">
                        <img src="assets/logo-blanco.png" alt="Power Pack" style="height:34px;width:auto">
                        <span style="color:#94a3b8;font-size:11px;font-weight:bold;letter-spacing:0.5px">SOLUCIONES INDUSTRIALES</span>
                    </div>
                    <!-- CUERPO PREVISUALIZADO -->
                    <div id="preview-render-body" style="padding:24px;font-size:14px;color:#334155;line-height:1.6">
                        <p style="color:#94a3b8;font-style:italic">Escribe o genera un correo para ver cómo lo verá el cliente en su bandeja de entrada...</p>
                    </div>
                    <!-- PIE DE FIRMA POWER PACK -->
                    <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 20px;font-size:11px;color:#64748b;text-align:center;line-height:1.5">
                        <strong style="color:#0f172a">Power Pack SAS</strong> • Maquinaria y Soluciones de Empaque Industrial<br>
                        Calle 161 # 54 - 25, Bogotá, Colombia • Tel / WhatsApp: +57 300 467 0474<br>
                        <a href="https://powerpack.com.co" style="color:#2c60a4;text-decoration:none;font-weight:bold">www.powerpack.com.co</a>
                    </div>
                </div>
            </div>

            <!-- BARRA DE ACCIÓN Y ENVÍO -->
            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:12px;border-top:1px solid var(--border);flex-wrap:wrap;gap:10px">
                <div style="font-size:12px;color:var(--fg-secondary)" id="status-origen-ia">
                    Modo: Subagente Listo
                </div>
                
                <div style="display:flex;gap:8px">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="copiarContenidoHTML()">
                        📋 Copiar Texto
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-enviar-correo" onclick="ejecutarEnvioCorreo()" style="background:#2c60a4;color:#fff;font-weight:800;padding:8px 18px">
                        <span id="txt-enviar-btn">🚀 Enviar Correo Ahora (SMTP)</span>
                        <span id="spinner-enviar-btn" style="display:none">⏳ Enviando...</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- SECCIÓN 2: HISTORIAL DE CORREOS ENVIADOS   -->
<!-- ========================================== -->
<div id="seccion-historial" style="display:none">
    
    <!-- STATS CARDS -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;margin-bottom:18px">
        <div class="card" style="padding:14px;border-left:4px solid var(--brand-blue)">
            <div style="font-size:11px;color:var(--fg-secondary);font-weight:700">TOTAL CORREOS ENVIADOS</div>
            <div style="font-size:24px;font-weight:900;color:var(--fg);margin-top:2px"><?= $total_enviados ?></div>
        </div>
        <div class="card" style="padding:14px;border-left:4px solid #10b981">
            <div style="font-size:11px;color:var(--fg-secondary);font-weight:700">ENVÍOS INDIVIDUALES (1-A-1)</div>
            <div style="font-size:24px;font-weight:900;color:#10b981;margin-top:2px"><?= $total_individual ?></div>
        </div>
        <div class="card" style="padding:14px;border-left:4px solid #8b5cf6">
            <div style="font-size:11px;color:var(--fg-secondary);font-weight:700">ENVÍOS EN LOTES MASIVOS</div>
            <div style="font-size:24px;font-weight:900;color:#8b5cf6;margin-top:2px"><?= $total_masivos ?></div>
        </div>
        <div class="card" style="padding:14px;border-left:4px solid #ef4444">
            <div style="font-size:11px;color:var(--fg-secondary);font-weight:700">FALLIDOS / REBOTES</div>
            <div style="font-size:24px;font-weight:900;color:#ef4444;margin-top:2px"><?= $total_fallidos ?></div>
        </div>
    </div>

    <!-- TABLA DE HISTORIAL -->
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
            <h3 style="font-size:14px;font-weight:800;color:var(--fg);margin:0">Bandeja de Salida & Registro de Envíos</h3>
            <span style="font-size:11px;color:var(--fg-secondary)">Mostrando últimos <?= count($historial_correos) ?> envíos</span>
        </div>
        
        <div class="table-responsive">
            <table class="table" style="margin:0;font-size:12px">
                <thead>
                    <tr style="background:#f8fafc">
                        <th>Fecha / Hora</th>
                        <th>Destinatario</th>
                        <th>Asunto</th>
                        <th>Modalidad</th>
                        <th>Skill / Origen</th>
                        <th>Estado</th>
                        <th style="text-align:right">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historial_correos)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:30px;color:var(--fg-secondary)">
                                Aún no has enviado correos desde el Estudio. Redacta tu primer correo arriba y envíalo con 1 clic.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historial_correos as $h_item): ?>
                            <tr>
                                <td style="white-space:nowrap;color:var(--fg-secondary)">
                                    <?= date('d/m/Y H:i', strtotime($h_item['fecha_envio'])) ?>
                                </td>
                                <td>
                                    <strong><?= h($h_item['destinatario_nombre'] ?: 'Cliente') ?></strong><br>
                                    <span style="color:var(--fg-secondary);font-size:11px"><?= h($h_item['destinatario_email']) ?></span>
                                </td>
                                <td>
                                    <strong><?= h($h_item['asunto']) ?></strong>
                                </td>
                                <td>
                                    <?php if ($h_item['es_masivo']): ?>
                                        <span class="badge" style="background:#f3e8ff;color:#7e22ce;font-weight:700">📢 Masivo</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#eff6ff;color:#1e40af;font-weight:700">👤 1-a-1</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size:11px;color:var(--fg-secondary)"><?= h($h_item['skill_codigo'] ?: 'Personalizado') ?></span>
                                </td>
                                <td>
                                    <?php if ($h_item['estado'] === 'enviado'): ?>
                                        <span class="badge badge-success">✓ Enviado</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger" title="<?= h($h_item['error_detalle']) ?>">✕ Fallido</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="verModalCorreo(<?= htmlspecialchars(json_encode($h_item), ENT_QUOTES, 'UTF-8') ?>)">
                                        👁️ Ver
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL VISOR DE CORREO -->
<div id="modal-visor-correo" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:10px;max-width:680px;width:100%;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:var(--shadow-lg)">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;background:#0f172a;color:#fff">
            <div>
                <h3 style="font-size:14px;font-weight:800;margin:0" id="modal-correo-asunto">Detalle del Correo</h3>
                <div style="font-size:11px;color:#94a3b8" id="modal-correo-meta">Destinatario</div>
            </div>
            <button type="button" onclick="cerrarModalCorreo()" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer">✕</button>
        </div>
        <div style="padding:20px;overflow-y:auto;flex:1" id="modal-correo-contenido"></div>
        <div style="padding:12px 20px;border-top:1px solid var(--border);background:#f8fafc;display:flex;justify-content:flex-end">
            <button type="button" class="btn btn-secondary btn-sm" onclick="cerrarModalCorreo()">Cerrar</button>
        </div>
    </div>
</div>

<style>
.btn-chip {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 3px 8px;
    font-size: 10px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-chip:hover {
    background: #e2e8f0;
    border-color: #94a3b8;
    color: #0f172a;
}
@media(max-width: 900px) {
    .studio-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
// Estado de la sesión del estudio
let modoEnvioActual = 'individual';
let historialChat = [];
let contactosLista = <?= json_encode($contactos_list) ?>;

// Cambiar pestaña principal (Estudio vs Historial)
function cambiarTabEstudio(tab) {
    if (tab === 'tab-editor') {
        document.getElementById('seccion-estudio').style.display = 'block';
        document.getElementById('seccion-historial').style.display = 'none';
        document.getElementById('btn-tab-editor').style.background = '#fff';
        document.getElementById('btn-tab-editor').style.borderColor = 'var(--brand-blue)';
        document.getElementById('btn-tab-editor').style.color = 'var(--brand-blue)';
        document.getElementById('btn-tab-historial').style.background = '';
        document.getElementById('btn-tab-historial').style.borderColor = '';
        document.getElementById('btn-tab-historial').style.color = '';
    } else {
        document.getElementById('seccion-estudio').style.display = 'none';
        document.getElementById('seccion-historial').style.display = 'block';
        document.getElementById('btn-tab-historial').style.background = '#fff';
        document.getElementById('btn-tab-historial').style.borderColor = 'var(--brand-blue)';
        document.getElementById('btn-tab-historial').style.color = 'var(--brand-blue)';
        document.getElementById('btn-tab-editor').style.background = '';
        document.getElementById('btn-tab-editor').style.borderColor = '';
        document.getElementById('btn-tab-editor').style.color = '';
    }
}

// Cambiar modo de envío (individual vs masivo)
function setModoEnvio(modo) {
    modoEnvioActual = modo;
    if (modo === 'individual') {
        document.getElementById('box-modo-individual').style.display = 'block';
        document.getElementById('box-modo-masivo').style.display = 'none';
        document.getElementById('btn-modo-individual').style.background = 'var(--brand-blue)';
        document.getElementById('btn-modo-individual').style.color = '#fff';
        document.getElementById('btn-modo-masivo').style.background = '';
        document.getElementById('btn-modo-masivo').style.color = '';
        document.getElementById('txt-enviar-btn').innerText = '🚀 Enviar Correo Ahora (SMTP)';
    } else {
        document.getElementById('box-modo-individual').style.display = 'none';
        document.getElementById('box-modo-masivo').style.display = 'block';
        document.getElementById('btn-modo-masivo').style.background = 'var(--brand-blue)';
        document.getElementById('btn-modo-masivo').style.color = '#fff';
        document.getElementById('btn-modo-individual').style.background = '';
        document.getElementById('btn-modo-individual').style.color = '';
        calcularDestinatariosMasivos();
    }
}

// Actualizar información de la skill seleccionada
function actualizarInfoSkill() {
    const sel = document.getElementById('select-skill');
    const opt = sel.options[sel.selectedIndex];
    const desc = opt.getAttribute('data-desc') || '';
    document.getElementById('desc-skill-box').innerText = desc;
}

// Selección de contacto en modo individual
function seleccionarContactoIndividual() {
    const sel = document.getElementById('select-contacto');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('dest-email-manual').value = opt.getAttribute('data-email') || '';
        document.getElementById('dest-nombre-manual').value = opt.getAttribute('data-nombre') || '';
    }
}

// Conteo dinámico de destinatarios masivos
function calcularDestinatariosMasivos() {
    const prio = document.getElementById('filtro-masivo-prioridad').value;
    const etapa = document.getElementById('filtro-masivo-etapa').value;

    let filtrados = contactosLista.filter(c => {
        if (!c.email) return false;
        if (prio && c.prioridad !== prio) return false;
        if (etapa && c.etapa !== etapa) return false;
        return true;
    });

    const badge = document.getElementById('badge-conteo-masivo');
    badge.innerText = `📦 ${filtrados.length} contactos recibirán este correo personalizado ({nombre}, {empresa}).`;
    document.getElementById('txt-enviar-btn').innerText = `📢 Enviar Masivo a ${filtrados.length} Contactos`;
}

// Aplicar sugerencia rápida en el chat
function aplicarPromptRapido(texto) {
    document.getElementById('input-subagente-prompt').value = texto;
    pedirAlSubagente();
}

// Insertar variable en el cursor del cuerpo
function insertarVariable(token) {
    const textarea = document.getElementById('email-cuerpo');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + token + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + token.length;
    actualizarVistaPreviaHTML();
}

// Alternar entre editor y vista previa
function toggleVistaEditor(vista) {
    if (vista === 'editor') {
        document.getElementById('panel-editor-texto').style.display = 'block';
        document.getElementById('panel-editor-preview').style.display = 'none';
        document.getElementById('tab-btn-editar').style.background = 'var(--brand-blue)';
        document.getElementById('tab-btn-editar').style.color = '#fff';
        document.getElementById('tab-btn-preview').style.background = '';
        document.getElementById('tab-btn-preview').style.color = '';
    } else {
        actualizarVistaPreviaHTML();
        document.getElementById('panel-editor-texto').style.display = 'none';
        document.getElementById('panel-editor-preview').style.display = 'block';
        document.getElementById('tab-btn-preview').style.background = 'var(--brand-blue)';
        document.getElementById('tab-btn-preview').style.color = '#fff';
        document.getElementById('tab-btn-editar').style.background = '';
        document.getElementById('tab-btn-editar').style.color = '';
    }
}

// Actualizar renderizado HTML en la vista previa
function actualizarVistaPreviaHTML() {
    let html = document.getElementById('email-cuerpo').value;
    if (!html.trim()) {
        html = '<p style="color:#94a3b8;font-style:italic">El cuerpo del correo está vacío actualmente...</p>';
    }

    // Reemplazo visual de variables para la vista previa
    let nombreEjemplo = document.getElementById('dest-nombre-manual').value || 'Ing. Carlos Mendoza';
    html = html.replace(/{nombre}/gi, nombreEjemplo)
               .replace(/{empresa}/gi, 'Lácteos del Valle SAS')
               .replace(/{cargo}/gi, 'Director de Planta')
               .replace(/{ciudad}/gi, 'Bogotá');

    document.getElementById('preview-render-body').innerHTML = html;
}

// Diálogo interactivo con el subagente (Co-Redactar)
async function pedirAlSubagente() {
    const input = document.getElementById('input-subagente-prompt');
    const prompt = input.value.trim();
    if (!prompt) return;

    const skill = document.getElementById('select-skill').value;
    const contactoId = document.getElementById('select-contacto').value;
    const asuntoActual = document.getElementById('email-asunto').value;
    const cuerpoActual = document.getElementById('email-cuerpo').value;

    // Agregar mensaje del usuario a la línea de tiempo
    const timeline = document.getElementById('chat-subagente-timeline');
    timeline.innerHTML += `
        <div style="display:flex;justify-content:flex-end">
            <div style="background:#2c60a4;color:#fff;padding:8px 12px;border-radius:8px;max-width:85%;line-height:1.4">
                ${prompt}
            </div>
        </div>
    `;
    input.value = '';
    timeline.scrollTop = timeline.scrollHeight;

    // Indicador de carga
    document.getElementById('txt-pedir-subagente').style.display = 'none';
    document.getElementById('spinner-subagente').style.display = 'inline';
    document.getElementById('btn-pedir-subagente').disabled = true;

    try {
        const resp = await fetch('api.php?action=subagente_chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                subagente_id: 'email',
                skill_codigo: skill,
                contacto_id: contactoId ? parseInt(contactoId) : 0,
                instrucciones: prompt,
                historial: historialChat,
                borrador: { asunto: asuntoActual, cuerpo: cuerpoActual }
            })
        });

        const data = await resp.json();
        if (data.ok) {
            // Actualizar campos del editor
            if (data.asunto) document.getElementById('email-asunto').value = data.asunto;
            if (data.cuerpo_html) document.getElementById('email-cuerpo').value = data.cuerpo_html;
            
            document.getElementById('status-origen-ia').innerText = data.origen || 'Subagente B2B';

            // Mensaje del subagente en el chat
            timeline.innerHTML += `
                <div style="display:flex;gap:8px;align-items:flex-start">
                    <span style="font-size:18px">✉️</span>
                    <div style="background:#fff;padding:8px 12px;border-radius:8px;border:1px solid var(--border);color:var(--fg);line-height:1.4;max-width:85%">
                        ${data.respuesta_chat}
                    </div>
                </div>
            `;
            timeline.scrollTop = timeline.scrollHeight;

            // Guardar en historial de sesión
            historialChat.push({ rol: 'usuario', texto: prompt });
            historialChat.push({ rol: 'asistente', texto: data.respuesta_chat });

            actualizarVistaPreviaHTML();
        } else {
            alert('Aviso del subagente: ' + (data.error || 'No se pudo generar respuesta'));
        }
    } catch (e) {
        alert('Error de conexión con el subagente: ' + e.message);
    } finally {
        document.getElementById('txt-pedir-subagente').style.display = 'inline';
        document.getElementById('spinner-subagente').style.display = 'none';
        document.getElementById('btn-pedir-subagente').disabled = false;
    }
}

// Ejecutar envío directo vía SMTP Hostinger
async function ejecutarEnvioCorreo() {
    const asunto = document.getElementById('email-asunto').value.trim();
    const cuerpo = document.getElementById('email-cuerpo').value.trim();
    const skill = document.getElementById('select-skill').value;

    if (!asunto || !cuerpo) {
        alert('Por favor redacta un asunto y cuerpo para el correo antes de enviar.');
        return;
    }

    let payload = {
        modo: modoEnvioActual,
        asunto: asunto,
        cuerpo_html: cuerpo,
        skill_codigo: skill,
        subagente: 'email_copywriter'
    };

    if (modoEnvioActual === 'individual') {
        const destEmail = document.getElementById('dest-email-manual').value.trim();
        const destNombre = document.getElementById('dest-nombre-manual').value.trim();
        const contactoId = document.getElementById('select-contacto').value;

        if (!destEmail) {
            alert('Por favor especifica el correo del destinatario.');
            return;
        }

        if (!confirm(`¿Confirmas el envío de este correo a ${destEmail} mediante el servidor SMTP de Hostinger?`)) {
            return;
        }

        payload.contacto_id = contactoId ? parseInt(contactoId) : 0;
        payload.destinatario_email = destEmail;
        payload.destinatario_nombre = destNombre;
    } else {
        const prio = document.getElementById('filtro-masivo-prioridad').value;
        const etapa = document.getElementById('filtro-masivo-etapa').value;

        let filtrados = contactosLista.filter(c => {
            if (!c.email) return false;
            if (prio && c.prioridad !== prio) return false;
            if (etapa && c.etapa !== etapa) return false;
            return true;
        });

        if (filtrados.length === 0) {
            alert('No hay contactos que coincidan con los filtros seleccionados.');
            return;
        }

        if (!confirm(`¿Confirmas el envío MASIVO a ${filtrados.length} contactos mediante SMTP Hostinger? Cada uno recibirá su correo personalizado con su nombre y empresa.`)) {
            return;
        }

        payload.contactos_ids = filtrados.map(c => c.id);
    }

    // Botón en carga
    document.getElementById('txt-enviar-btn').style.display = 'none';
    document.getElementById('spinner-enviar-btn').style.display = 'inline';
    document.getElementById('btn-enviar-correo').disabled = true;

    try {
        const resp = await fetch('api.php?action=enviar_correo_estudio', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const res = await resp.json();
        if (res.ok) {
            alert('🚀 ' + (res.mensaje || 'Correo enviado exitosamente'));
            // Recargar para refrescar historial
            window.location.href = 'index.php?page=correos&msg=email_enviado';
        } else {
            alert('❌ Error al enviar correo: ' + (res.error || 'Revisa la configuración SMTP en Ajustes.'));
        }
    } catch (e) {
        alert('Error de conexión: ' + e.message);
    } finally {
        document.getElementById('txt-enviar-btn').style.display = 'inline';
        document.getElementById('spinner-enviar-btn').style.display = 'none';
        document.getElementById('btn-enviar-correo').disabled = false;
    }
}

// Copiar contenido al portapapeles
function copiarContenidoHTML() {
    const text = document.getElementById('email-cuerpo').value;
    navigator.clipboard.writeText(text).then(() => {
        alert('Cuerpo del correo copiado al portapapeles');
    });
}

// Modal visor de correos históricos
function verModalCorreo(item) {
    document.getElementById('modal-correo-asunto').innerText = item.asunto || 'Sin asunto';
    document.getElementById('modal-correo-meta').innerText = `Para: ${item.destinatario_nombre || ''} <${item.destinatario_email}> • Fecha: ${item.fecha_envio} • Estado: ${item.estado}`;
    document.getElementById('modal-correo-contenido').innerHTML = item.cuerpo_html;
    document.getElementById('modal-visor-correo').style.display = 'flex';
}

function cerrarModalCorreo() {
    document.getElementById('modal-visor-correo').style.display = 'none';
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    actualizarInfoSkill();
    if (document.getElementById('email-cuerpo').value) {
        actualizarVistaPreviaHTML();
    }
});
</script>
