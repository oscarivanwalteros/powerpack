<?php
/**
 * pages/whatsapp.php - Estudio de WhatsApp IA & Despacho de Mensajería B2B
 * Power Pack SAS
 */

require_once __DIR__ . '/../ai.php';

$subagent = get_subagent_by_id('whatsapp');
$subagent_skills = $subagent['skills'] ?? [];

// Contacto preseleccionado si viene por URL (?page=whatsapp&contacto_id=X)
$contacto_pre = null;
$cid_param = (int)($_GET['contacto_id'] ?? 0);
if ($cid_param > 0) {
    $contacto_pre = $db->querySingle("SELECT * FROM contactos WHERE id = $cid_param", true);
}

// Cargar lista de contactos con teléfono
$contactos_list = [];
$res_c = $db->query("SELECT id, nombre, apellido, telefono, empresa, cargo, ciudad, prioridad, etapa FROM contactos WHERE telefono IS NOT NULL AND telefono != '' ORDER BY (CASE prioridad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END) ASC, id DESC LIMIT 200");
if ($res_c) {
    while ($r = $res_c->fetchArray(SQLITE3_ASSOC)) {
        $contactos_list[] = $r;
    }
}

// Contadores de estadísticas de WhatsApp
$total_enviados   = (int)$db->querySingle("SELECT COUNT(*) FROM mensajes_whatsapp WHERE estado = 'enviado'");
$total_individual = (int)$db->querySingle("SELECT COUNT(*) FROM mensajes_whatsapp WHERE es_masivo = 0");
$total_masivos    = (int)$db->querySingle("SELECT COUNT(*) FROM mensajes_whatsapp WHERE es_masivo = 1");

// Cargar historial de mensajes de WhatsApp
$historial_wa = [];
$res_h = $db->query("SELECT m.*, c.nombre as c_nombre, c.apellido as c_apellido, c.empresa as c_empresa FROM mensajes_whatsapp m LEFT JOIN contactos c ON m.contacto_id = c.id ORDER BY m.id DESC LIMIT 50");
if ($res_h) {
    while ($r = $res_h->fetchArray(SQLITE3_ASSOC)) {
        $historial_wa[] = $r;
    }
}
?>

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
        <div style="display:flex;align-items:center;gap:10px">
            <h1 style="font-size:22px;font-weight:800;color:var(--fg);margin:0">💬 Estudio de WhatsApp IA</h1>
            <span style="background:rgba(37,211,102,0.15);color:#059669;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:800">
                Subagente Activo
            </span>
        </div>
        <p style="color:var(--fg-secondary);font-size:13px;margin-top:4px">
            Co-redacta mensajes comerciales de alta conversión para directivos industriales con simulador móvil en vivo y registro histórico.
        </p>
    </div>
    
    <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-secondary btn-sm" onclick="cambiarTabWA('tab-estudio')" id="btn-tab-wa-estudio" style="background:#fff;border-color:var(--whatsapp);color:#059669;font-weight:700">
            💬 Estudio & Simulador
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="cambiarTabWA('tab-cola')" id="btn-tab-wa-cola">
            📱 Cola de Envío Rápido
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="cambiarTabWA('tab-historial')" id="btn-tab-wa-historial">
            📜 Historial (<?= $total_enviados ?>)
        </button>
    </div>
</div>

<!-- ============================================== -->
<!-- SECCIÓN 1: ESTUDIO & SIMULADOR DE WHATSAPP     -->
<!-- ============================================== -->
<div id="seccion-wa-estudio" style="display:block">
    <div style="display:grid;grid-template-columns: 390px 1fr;gap:20px;align-items:start" class="studio-grid">
        
        <!-- PANEL IZQUIERDO: SUBAGENTE & COPILOTO -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px;box-shadow:var(--shadow-sm);display:flex;flex-direction:column;gap:14px">
            
            <!-- TARJETA DEL SUBAGENTE -->
            <div style="background:linear-gradient(135deg, #064e3b 0%, #0f172a 100%);color:#fff;padding:14px;border-radius:8px;border-left:4px solid #25D366">
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:22px">💬</span>
                        <div>
                            <div style="font-weight:800;font-size:13px">Senior Mobile Outreach Specialist</div>
                            <div style="font-size:11px;color:#86efac">Subagente de WhatsApp B2B</div>
                        </div>
                    </div>
                    <span style="width:8px;height:8px;border-radius:50%;background:#25D366;box-shadow:0 0 8px #25D366" title="Subagente en línea"></span>
                </div>
                <div style="font-size:11px;color:#d1fae5;margin-top:8px;line-height:1.4">
                    Especializado en mensajes directos, escaneables en pantalla móvil (*negritas*, emojis sobrios) que generan respuestas en menos de 5 minutos.
                </div>
            </div>

            <!-- SELECTOR DE SKILL / HABILIDAD COMERCIAL -->
            <div>
                <label style="display:block;font-size:11px;font-weight:800;color:var(--fg);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px">
                    🧠 Habilidad de WhatsApp B2B (Skill):
                </label>
                <select id="select-wa-skill" class="form-control" style="font-size:12px;font-weight:600" onchange="actualizarInfoSkillWA()">
                    <?php foreach ($subagent_skills as $idx => $sk): ?>
                        <option value="<?= h($sk['codigo'] ?? '') ?>" <?= $idx === 0 ? 'selected' : '' ?> data-desc="<?= h($sk['descripcion'] ?? '') ?>">
                            <?= h($sk['nombre'] ?? 'Skill') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="desc-wa-skill-box" style="font-size:11px;color:var(--fg-secondary);margin-top:6px;background:var(--bg);padding:8px 10px;border-radius:6px;border-left:3px solid #25D366">
                    <?= h($subagent_skills[0]['descripcion'] ?? 'Selecciona un estilo de mensaje de WhatsApp.') ?>
                </div>
            </div>

            <!-- SELECTOR DE CONTACTO -->
            <div style="border-top:1px solid var(--border);padding-top:12px">
                <label style="display:block;font-size:11px;font-weight:800;color:var(--fg);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px">
                    👤 Destinatario Comercial:
                </label>
                <select id="select-wa-contacto" class="form-control" style="font-size:12px" onchange="seleccionarContactoWA()">
                    <option value="">-- Redacción Libre / Sin Contacto Asociado --</option>
                    <?php foreach ($contactos_list as $c): ?>
                        <?php 
                        $c_nombre = trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? ''));
                        $c_desc = $c_nombre . ($c['empresa'] ? " ({$c['empresa']})" : "") . " - " . $c['telefono'];
                        $sel = ($contacto_pre && $contacto_pre['id'] == $c['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $c['id'] ?>" <?= $sel ?>
                                data-nombre="<?= h($c_nombre) ?>"
                                data-empresa="<?= h($c['empresa'] ?? '') ?>"
                                data-cargo="<?= h($c['cargo'] ?? '') ?>"
                                data-ciudad="<?= h($c['ciudad'] ?? '') ?>"
                                data-telefono="<?= h($c['telefono'] ?? '') ?>"
                                data-prioridad="<?= h($c['prioridad'] ?? 'media') ?>">
                            <?= h($c_desc) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px">
                    <div>
                        <label style="font-size:10px;font-weight:700;color:var(--fg-secondary)">Teléfono / WhatsApp:</label>
                        <input type="text" id="dest-wa-telefono" class="form-control" style="font-size:12px;padding:6px" placeholder="3001234567" value="<?= h($contacto_pre['telefono'] ?? '') ?>">
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:700;color:var(--fg-secondary)">Nombre / Empresa:</label>
                        <input type="text" id="dest-wa-nombre" class="form-control" style="font-size:12px;padding:6px" placeholder="Nombre cliente" value="<?= h(trim(($contacto_pre['nombre'] ?? '') . ' ' . ($contacto_pre['apellido'] ?? ''))) ?>">
                    </div>
                </div>
            </div>

            <!-- CHAT INTERACTIVO CON SUBAGENTE DE WHATSAPP -->
            <div style="border-top:1px solid var(--border);padding-top:12px;display:flex;flex-direction:column;flex:1">
                <label style="display:block;font-size:11px;font-weight:800;color:var(--fg);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px">
                    💬 Diálogo con el Subagente:
                </label>
                
                <div id="chat-wa-subagente-timeline" style="background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px;max-height:220px;overflow-y:auto;display:flex;flex-direction:column;gap:10px;font-size:12px;margin-bottom:10px">
                    <div style="display:flex;gap:8px;align-items:flex-start">
                        <span style="font-size:18px">💬</span>
                        <div style="background:#fff;padding:8px 12px;border-radius:8px;border:1px solid var(--border);color:var(--fg);line-height:1.4">
                            ¡Hola! Soy tu <strong>Subagente de WhatsApp B2B</strong>. Selecciona una habilidad arriba o pídeme ajustar el mensaje para que sea ultra-rápido de responder.
                        </div>
                    </div>
                </div>

                <!-- SUGERENCIAS RÁPIDAS -->
                <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px">
                    <button type="button" class="btn-chip" onclick="aplicarPromptRapidoWA('Hazlo más conciso, en solo 2 líneas con emojis industriales')">⚡ Ultra corto</button>
                    <button type="button" class="btn-chip" onclick="aplicarPromptRapido('Menciona que acabo de enviarle la cotización formal por correo')">📄 Aviso cotización</button>
                    <button type="button" class="btn-chip" onclick="aplicarPromptRapidoWA('Invita a traer muestras a nuestro Showroom en Bogotá para pruebas en vivo')">🏢 Showroom Bogotá</button>
                    <button type="button" class="btn-chip" onclick="aplicarPromptRapidoWA('Pregunta amablemente si aún sigue vigente el proyecto o lo dejamos para después')">☕ Reactivación suave</button>
                </div>

                <!-- INPUT DE INSTRUCCIÓN -->
                <div style="display:flex;gap:6px">
                    <input type="text" id="input-wa-subagente-prompt" class="form-control" style="font-size:12px" placeholder="Ej: Redacta un mensaje para agendar llamada..." onkeydown="if(event.key==='Enter') pedirAlSubagenteWA()">
                    <button type="button" id="btn-pedir-wa-subagente" class="btn btn-primary" onclick="pedirAlSubagenteWA()" style="background:#059669;border-color:#059669;padding:6px 14px;font-size:12px;font-weight:700">
                        <span id="txt-pedir-wa-subagente">Co-Redactar</span>
                        <span id="spinner-wa-subagente" style="display:none">⏳</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- PANEL DERECHO: EDITOR Y SIMULADOR MÓVIL EN VIVO -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow-sm);display:flex;flex-direction:column;gap:14px">
            
            <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);padding-bottom:12px">
                <div>
                    <h3 style="font-size:14px;font-weight:800;color:var(--fg);margin:0">📱 Simulador Móvil de WhatsApp en Vivo</h3>
                    <div style="font-size:11px;color:var(--fg-secondary)">Visualiza cómo verá el mensaje el gerente de planta en su celular.</div>
                </div>
                
                <div style="display:flex;align-items:center;gap:4px">
                    <button type="button" class="btn-chip" onclick="insertarWA('*', '*')">*Negrita*</button>
                    <button type="button" class="btn-chip" onclick="insertarWA('_', '_')">_Cursiva_</button>
                    <button type="button" class="btn-chip" onclick="insertarWA('{nombre}', '')">{nombre}</button>
                    <button type="button" class="btn-chip" onclick="insertarWA('{empresa}', '')">{empresa}</button>
                    <button type="button" class="btn-chip" onclick="insertarWA('⚙️', '')">⚙️</button>
                    <button type="button" class="btn-chip" onclick="insertarWA('📦', '')">📦</button>
                    <button type="button" class="btn-chip" onclick="insertarWA('🤝', '')">🤝</button>
                </div>
            </div>

            <!-- SIMULADOR MÓVIL DE WHATSAPP -->
            <div style="background:#efeae2;border-radius:12px;overflow:hidden;border:1px solid #d1d5db;box-shadow:0 4px 14px rgba(0,0,0,0.08);max-width:520px;margin:0 auto;width:100%">
                <!-- CABECERA DE WHATSAPP -->
                <div style="background:#075e54;color:#fff;padding:10px 14px;display:flex;align-items:center;justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="font-size:16px;cursor:pointer">←</span>
                        <div style="width:34px;height:34px;border-radius:50%;background:#128c7e;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:#fff">
                            PP
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:13px;line-height:1.2" id="simulador-contacto-nombre">
                                <?= h($contacto_pre ? trim(($contacto_pre['nombre']??'') . ' ' . ($contacto_pre['apellido']??'')) : 'Cliente Industrial') ?>
                            </div>
                            <div style="font-size:10px;color:#a7f3d0">en línea</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:14px;font-size:16px">
                        <span>📹</span>
                        <span>📞</span>
                        <span>⋮</span>
                    </div>
                </div>

                <!-- FONDO DE MENSAJES DE WHATSAPP -->
                <div style="padding:16px;min-height:200px;max-height:260px;overflow-y:auto;display:flex;flex-direction:column;justify-content:flex-end">
                    <!-- BURBUJA DE MENSAJE ENVIADO (VERDE WHATSAPP) -->
                    <div style="align-self:flex-end;max-width:88%;background:#dcf8c6;border-radius:8px 0px 8px 8px;padding:8px 12px;box-shadow:0 1px 2px rgba(0,0,0,0.15);position:relative">
                        <div id="simulador-burbuja-texto" style="font-size:13px;color:#111827;line-height:1.45;white-space:pre-wrap;word-break:break-word">
                            Escribe o genera un mensaje con el subagente para ver el preview en tiempo real...
                        </div>
                        <div style="display:flex;justify-content:flex-end;align-items:center;gap:3px;margin-top:4px">
                            <span style="font-size:10px;color:#6b7280" id="simulador-hora"><?= date('h:i a') ?></span>
                            <span style="font-size:11px;color:#3b82f6;font-weight:bold">✓✓</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÁREA DE EDICIÓN DEL MENSAJE -->
            <div>
                <label style="display:block;font-size:11px;font-weight:800;color:var(--fg);margin-bottom:4px;text-transform:uppercase;letter-spacing:0.5px">
                    ✍️ Texto del Mensaje (Sintaxis de WhatsApp):
                </label>
                <textarea id="wa-mensaje-texto" class="form-control" style="height:140px;font-size:13px;line-height:1.5" placeholder="Escribe el mensaje o pídele al subagente que lo redacte..." oninput="actualizarSimuladorWA()"></textarea>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid var(--border);flex-wrap:wrap;gap:10px">
                <div style="font-size:12px;color:var(--fg-secondary)" id="wa-status-origen">
                    Modo: Subagente de WhatsApp Listo
                </div>
                
                <div style="display:flex;gap:8px">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="copiarMensajeWA()">
                        📋 Copiar Mensaje
                    </button>
                    <button type="button" class="btn btn-primary" onclick="abrirWhatsAppYRegistrar()" style="background:#25D366;border-color:#25D366;color:#fff;font-weight:800;padding:8px 18px">
                        💬 Abrir WhatsApp Web & Registrar
                    </button>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- ============================================== -->
<!-- SECCIÓN 2: COLA DE ENVÍO SECUENCIAL (RÁPIDO)   -->
<!-- ============================================== -->
<div id="seccion-wa-cola" style="display:none">
    <div class="card" style="margin-bottom:16px;padding:16px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <div>
                <h3 style="font-size:14px;font-weight:800;margin:0">📱 Cola de Envío Secuencial 1-a-1</h3>
                <div style="font-size:12px;color:var(--fg-secondary)">
                    Envía el mensaje redactado a tus contactos prioritarios uno por uno sin riesgo de bloqueo de WhatsApp.
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <label style="font-size:11px;font-weight:700">Filtrar por Prioridad:</label>
                <select id="cola-filtro-prioridad" class="form-control" style="font-size:11px;width:auto" onchange="renderizarColaEnvio()">
                    <option value="">Todas</option>
                    <option value="alta" selected>🔥 Solo Alta Prioridad</option>
                    <option value="media">⚡ Media Prioridad</option>
                    <option value="baja">🌱 Baja Prioridad</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        <div class="table-responsive">
            <table class="table" style="margin:0;font-size:12px">
                <thead>
                    <tr style="background:#f8fafc">
                        <th>Contacto</th>
                        <th>Empresa</th>
                        <th>Teléfono</th>
                        <th>Prioridad</th>
                        <th>Etapa</th>
                        <th style="text-align:right">Acción de Despacho</th>
                    </tr>
                </thead>
                <tbody id="tbody-cola-envio">
                    <!-- RENDERIZADO DINÁMICO JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- SECCIÓN 3: HISTORIAL DE MENSAJES ENVIADOS      -->
<!-- ============================================== -->
<div id="seccion-wa-historial" style="display:none">
    
    <!-- STATS CARDS -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;margin-bottom:18px">
        <div class="card" style="padding:14px;border-left:4px solid var(--whatsapp)">
            <div style="font-size:11px;color:var(--fg-secondary);font-weight:700">TOTAL MENSAJES WHATSAPP</div>
            <div style="font-size:24px;font-weight:900;color:var(--fg);margin-top:2px"><?= $total_enviados ?></div>
        </div>
        <div class="card" style="padding:14px;border-left:4px solid var(--brand-blue)">
            <div style="font-size:11px;color:var(--fg-secondary);font-weight:700">DESPACHOS INDIVIDUALES</div>
            <div style="font-size:24px;font-weight:900;color:var(--brand-blue);margin-top:2px"><?= $total_individual ?></div>
        </div>
        <div class="card" style="padding:14px;border-left:4px solid #8b5cf6">
            <div style="font-size:11px;color:var(--fg-secondary);font-weight:700">DESPACHOS EN COLA</div>
            <div style="font-size:24px;font-weight:900;color:#8b5cf6;margin-top:2px"><?= $total_masivos ?></div>
        </div>
    </div>

    <!-- TABLA HISTORIAL -->
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
            <h3 style="font-size:14px;font-weight:800;color:var(--fg);margin:0">Registro de Mensajes Enviados</h3>
            <span style="font-size:11px;color:var(--fg-secondary)">Mostrando últimos <?= count($historial_wa) ?> mensajes</span>
        </div>
        
        <div class="table-responsive">
            <table class="table" style="margin:0;font-size:12px">
                <thead>
                    <tr style="background:#f8fafc">
                        <th>Fecha / Hora</th>
                        <th>Destinatario</th>
                        <th>Teléfono</th>
                        <th>Mensaje Registrado</th>
                        <th>Skill Utilizada</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historial_wa)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:30px;color:var(--fg-secondary)">
                                Aún no has registrado mensajes de WhatsApp. Genera y despacha tu primer mensaje arriba.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historial_wa as $wa_item): ?>
                            <tr>
                                <td style="white-space:nowrap;color:var(--fg-secondary)">
                                    <?= date('d/m/Y H:i', strtotime($wa_item['fecha_envio'])) ?>
                                </td>
                                <td>
                                    <strong><?= h($wa_item['destinatario_nombre'] ?: 'Cliente') ?></strong>
                                    <?php if ($wa_item['c_empresa']): ?>
                                        <div style="font-size:11px;color:var(--fg-secondary)"><?= h($wa_item['c_empresa']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-family:monospace;font-weight:700"><?= h($wa_item['telefono']) ?></span>
                                </td>
                                <td style="max-width:320px">
                                    <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= h($wa_item['mensaje']) ?>">
                                        <?= h($wa_item['mensaje']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size:11px;color:var(--fg-secondary)"><?= h($wa_item['skill_codigo'] ?: 'Personalizado') ?></span>
                                </td>
                                <td>
                                    <span class="badge" style="background:#e7f9ee;color:#059669;font-weight:700">✓ Registrado</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let historialChatWA = [];
let contactosWA = <?= json_encode($contactos_list) ?>;

// Cambiar pestaña de WhatsApp
function cambiarTabWA(tab) {
    document.getElementById('seccion-wa-estudio').style.display = tab === 'tab-estudio' ? 'block' : 'none';
    document.getElementById('seccion-wa-cola').style.display = tab === 'tab-cola' ? 'block' : 'none';
    document.getElementById('seccion-wa-historial').style.display = tab === 'tab-historial' ? 'block' : 'none';

    document.getElementById('btn-tab-wa-estudio').style.background = tab === 'tab-estudio' ? '#fff' : '';
    document.getElementById('btn-tab-wa-estudio').style.borderColor = tab === 'tab-estudio' ? 'var(--whatsapp)' : '';
    document.getElementById('btn-tab-wa-estudio').style.color = tab === 'tab-estudio' ? '#059669' : '';

    document.getElementById('btn-tab-wa-cola').style.background = tab === 'tab-cola' ? '#fff' : '';
    document.getElementById('btn-tab-wa-cola').style.borderColor = tab === 'tab-cola' ? 'var(--whatsapp)' : '';
    document.getElementById('btn-tab-wa-cola').style.color = tab === 'tab-cola' ? '#059669' : '';

    document.getElementById('btn-tab-wa-historial').style.background = tab === 'tab-historial' ? '#fff' : '';
    document.getElementById('btn-tab-wa-historial').style.borderColor = tab === 'tab-historial' ? 'var(--whatsapp)' : '';
    document.getElementById('btn-tab-wa-historial').style.color = tab === 'tab-historial' ? '#059669' : '';

    if (tab === 'tab-cola') {
        renderizarColaEnvio();
    }
}

// Actualizar información de la skill de WhatsApp
function actualizarInfoSkillWA() {
    const sel = document.getElementById('select-wa-skill');
    const opt = sel.options[sel.selectedIndex];
    const desc = opt.getAttribute('data-desc') || '';
    document.getElementById('desc-wa-skill-box').innerText = desc;
}

// Seleccionar contacto en el desplegable
function seleccionarContactoWA() {
    const sel = document.getElementById('select-wa-contacto');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('dest-wa-telefono').value = opt.getAttribute('data-telefono') || '';
        document.getElementById('dest-wa-nombre').value = opt.getAttribute('data-nombre') || '';
        document.getElementById('simulador-contacto-nombre').innerText = opt.getAttribute('data-nombre') || 'Cliente Industrial';
        actualizarSimuladorWA();
    }
}

// Aplicar sugerencia rápida
function aplicarPromptRapidoWA(texto) {
    document.getElementById('input-wa-subagente-prompt').value = texto;
    pedirAlSubagenteWA();
}

// Insertar caracteres de formato o variables
function insertarWA(prefix, suffix) {
    const textarea = document.getElementById('wa-mensaje-texto');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.substring(start, end);
    const replacement = prefix + selected + suffix;
    textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
    textarea.focus();
    actualizarSimuladorWA();
}

// Actualizar el simulador móvil
function actualizarSimuladorWA() {
    let raw = document.getElementById('wa-mensaje-texto').value;
    if (!raw.trim()) {
        document.getElementById('simulador-burbuja-texto').innerText = 'Escribe o genera un mensaje con el subagente para ver el preview en tiempo real...';
        return;
    }

    // Reemplazo para la vista previa
    let nombre = document.getElementById('dest-wa-nombre').value || 'Carlos';
    let formatted = raw.replace(/{nombre}/gi, nombre)
                       .replace(/{empresa}/gi, 'Lácteos del Valle')
                       .replace(/{cargo}/gi, 'Jefe de Planta');

    // Parsear *negrita* y _cursiva_ para la burbuja
    let html = formatted.replace(/\*(.*?)\*/g, '<strong>$1</strong>')
                        .replace(/_(.*?)_/g, '<em>$1</em>');

    document.getElementById('simulador-burbuja-texto').innerHTML = html;
}

// Pedir al subagente de WhatsApp
async function pedirAlSubagenteWA() {
    const input = document.getElementById('input-wa-subagente-prompt');
    const prompt = input.value.trim();
    if (!prompt) return;

    const skill = document.getElementById('select-wa-skill').value;
    const contactoId = document.getElementById('select-wa-contacto').value;
    const mensajeActual = document.getElementById('wa-mensaje-texto').value;

    const timeline = document.getElementById('chat-wa-subagente-timeline');
    timeline.innerHTML += `
        <div style="display:flex;justify-content:flex-end">
            <div style="background:#059669;color:#fff;padding:8px 12px;border-radius:8px;max-width:85%;line-height:1.4">
                ${prompt}
            </div>
        </div>
    `;
    input.value = '';
    timeline.scrollTop = timeline.scrollHeight;

    document.getElementById('txt-pedir-wa-subagente').style.display = 'none';
    document.getElementById('spinner-wa-subagente').style.display = 'inline';
    document.getElementById('btn-pedir-wa-subagente').disabled = true;

    try {
        const resp = await fetch('api.php?action=subagente_chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                subagente_id: 'whatsapp',
                skill_codigo: skill,
                contacto_id: contactoId ? parseInt(contactoId) : 0,
                instrucciones: prompt,
                historial: historialChatWA,
                borrador: { mensaje: mensajeActual }
            })
        });

        const data = await resp.json();
        if (data.ok) {
            if (data.mensaje) {
                document.getElementById('wa-mensaje-texto').value = data.mensaje;
            }
            document.getElementById('wa-status-origen').innerText = data.origen || 'Subagente WhatsApp B2B';

            timeline.innerHTML += `
                <div style="display:flex;gap:8px;align-items:flex-start">
                    <span style="font-size:18px">💬</span>
                    <div style="background:#fff;padding:8px 12px;border-radius:8px;border:1px solid var(--border);color:var(--fg);line-height:1.4;max-width:85%">
                        ${data.respuesta_chat}
                    </div>
                </div>
            `;
            timeline.scrollTop = timeline.scrollHeight;

            historialChatWA.push({ rol: 'usuario', texto: prompt });
            historialChatWA.push({ rol: 'asistente', texto: data.respuesta_chat });

            actualizarSimuladorWA();
        } else {
            alert('Aviso del subagente: ' + (data.error || 'No se pudo generar respuesta'));
        }
    } catch (e) {
        alert('Error de conexión con el subagente: ' + e.message);
    } finally {
        document.getElementById('txt-pedir-wa-subagente').style.display = 'inline';
        document.getElementById('spinner-wa-subagente').style.display = 'none';
        document.getElementById('btn-pedir-wa-subagente').disabled = false;
    }
}

// Abrir WhatsApp Web o App y registrar actividad
async function abrirWhatsAppYRegistrar(cid = 0, tel = '', nom = '') {
    const rawTel = tel || document.getElementById('dest-wa-telefono').value.trim();
    const rawNom = nom || document.getElementById('dest-wa-nombre').value.trim();
    const contactoId = cid || (document.getElementById('select-wa-contacto').value ? parseInt(document.getElementById('select-wa-contacto').value) : 0);
    const mensajePlantilla = document.getElementById('wa-mensaje-texto').value.trim();
    const skill = document.getElementById('select-wa-skill').value;

    if (!rawTel) {
        alert('Por favor ingresa o selecciona un número de WhatsApp.');
        return;
    }
    if (!mensajePlantilla) {
        alert('El mensaje de WhatsApp está vacío. Escribe o genera un mensaje primero.');
        return;
    }

    // Normalizar número telefónico internacional Colombia
    let cleanPhone = rawTel.replace(/\D/g, '');
    if (cleanPhone.length === 10 && cleanPhone.startsWith('3')) {
        cleanPhone = '57' + cleanPhone;
    }

    // Reemplazo de variables
    let mensajeFinal = mensajePlantilla.replace(/{nombre}/gi, rawNom || 'Estimado(a)')
                                      .replace(/{empresa}/gi, 'su empresa');

    // Registrar en base de datos en segundo plano
    try {
        await fetch('api.php?action=registrar_whatsapp_estudio', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                contacto_id: contactoId,
                telefono: cleanPhone,
                destinatario_nombre: rawNom,
                mensaje: mensajeFinal,
                skill_codigo: skill,
                subagente: 'whatsapp_specialist',
                es_masivo: cid ? 1 : 0
            })
        });
    } catch (e) {
        console.error('Error registrando WhatsApp:', e);
    }

    // Abrir ventana oficial de WhatsApp
    const waUrl = `https://api.whatsapp.com/send?phone=${cleanPhone}&text=${encodeURIComponent(mensajeFinal)}`;
    window.open(waUrl, '_blank');
}

// Copiar mensaje
function copiarMensajeWA() {
    const text = document.getElementById('wa-mensaje-texto').value;
    navigator.clipboard.writeText(text).then(() => {
        alert('Mensaje de WhatsApp copiado al portapapeles');
    });
}

// Renderizar la cola de envío rápido
function renderizarColaEnvio() {
    const prio = document.getElementById('cola-filtro-prioridad').value;
    const tbody = document.getElementById('tbody-cola-envio');

    let filtrados = contactosWA.filter(c => {
        if (!c.telefono) return false;
        if (prio && c.prioridad !== prio) return false;
        return true;
    });

    if (filtrados.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--fg-secondary)">No hay contactos con teléfono bajo este filtro.</td></tr>`;
        return;
    }

    let html = '';
    filtrados.forEach(c => {
        let nom = (c.nombre || '') + ' ' + (c.apellido || '');
        let badgeColor = c.prioridad === 'alta' ? '#ef4444' : (c.prioridad === 'media' ? '#f59e0b' : '#6b7280');
        html += `
            <tr>
                <td><strong>${nom}</strong></td>
                <td>${c.empresa || '-'}</td>
                <td style="font-family:monospace">${c.telefono}</td>
                <td><span style="font-weight:700;color:${badgeColor}">● ${c.prioridad ? c.prioridad.toUpperCase() : 'MEDIA'}</span></td>
                <td><span class="badge badge-secondary">${c.etapa || 'lead'}</span></td>
                <td style="text-align:right">
                    <button type="button" class="btn btn-sm" style="background:#25D366;color:#fff;font-weight:700" onclick="abrirWhatsAppYRegistrar(${c.id}, '${c.telefono}', '${nom}')">
                        💬 Enviar por WhatsApp
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    actualizarInfoSkillWA();
    if (document.getElementById('wa-mensaje-texto').value) {
        actualizarSimuladorWA();
    }
});
</script>
