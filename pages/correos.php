<?php
/**
 * pages/correos.php - Redactor & Despacho de Correos Comerciales IA
 * Experiencia minimalista, visual y espaciosa con lista de chequeo y auto-llenado desde el contacto.
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

// Cargar lista completa de contactos con email para selector y lista de chequeo
$contactos_list = [];
$res_c = $db->query("SELECT id, nombre, apellido, email, empresa, cargo, ciudad, prioridad, etapa, notas FROM contactos WHERE email IS NOT NULL AND email != '' ORDER BY (CASE prioridad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END) ASC, id DESC LIMIT 300");
if ($res_c) {
    while ($r = $res_c->fetchArray(SQLITE3_ASSOC)) {
        $contactos_list[] = $r;
    }
}

// Contadores de estadísticas de correos
$total_enviados   = (int)$db->querySingle("SELECT COUNT(*) FROM mensajes_email WHERE estado = 'enviado'");
$total_masivos    = (int)$db->querySingle("SELECT COUNT(*) FROM mensajes_email WHERE es_masivo = 1");
$total_individual = (int)$db->querySingle("SELECT COUNT(*) FROM mensajes_email WHERE es_masivo = 0");

// Historial reciente de correos
$historial_correos = [];
$res_h = $db->query("SELECT m.*, c.nombre as c_nombre, c.apellido as c_apellido, c.empresa as c_empresa FROM mensajes_email m LEFT JOIN contactos c ON m.contacto_id = c.id ORDER BY m.id DESC LIMIT 50");
if ($res_h) {
    while ($r = $res_h->fetchArray(SQLITE3_ASSOC)) {
        $historial_correos[] = $r;
    }
}
?>

<div style="max-width:1120px;margin:0 auto">

    <!-- CABECERA PRINCIPAL Y PESTAÑAS -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:14px">
        <div>
            <div style="display:flex;align-items:center;gap:10px">
                <h1 style="font-size:24px;font-weight:900;color:var(--fg);margin:0">✉️ Redactor & Despacho de Correos</h1>
                <span style="background:rgba(44,96,164,0.12);color:var(--brand-blue);padding:4px 12px;border-radius:20px;font-size:11px;font-weight:800">
                    ✨ Asistente IA Activo
                </span>
            </div>
            <p style="color:var(--fg-secondary);font-size:13px;margin-top:3px">
                Dile tu idea a la IA, revisa la carta corporativa lista para enviar y despáchala con 1 clic por Hostinger SMTP.
            </p>
        </div>

        <div style="display:flex;background:#e2e8f0;padding:3px;border-radius:10px;gap:2px">
            <button type="button" class="btn btn-sm" id="tab-btn-individual" onclick="setModoPrincipal('individual')" style="background:#fff;color:var(--brand-blue);font-weight:800;border-radius:8px;box-shadow:var(--shadow-sm)">
                👤 Un Cliente
            </button>
            <button type="button" class="btn btn-sm" id="tab-btn-masivo" onclick="setModoPrincipal('masivo')" style="background:transparent;color:var(--fg-secondary);font-weight:700;border:none">
                📢 Lista de Chequeo (Masivo)
            </button>
            <button type="button" class="btn btn-sm" id="tab-btn-historial" onclick="setModoPrincipal('historial')" style="background:transparent;color:var(--fg-secondary);font-weight:700;border:none">
                📬 Historial (<?= $total_enviados ?>)
            </button>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- VISTA PRINCIPAL: REDACCIÓN & PREVISUALIZACIÓN DE CORREO   -->
    <!-- ======================================================== -->
    <div id="vista-redactor" style="display:block">

        <!-- PASO 1: DESTINATARIO -->
        <div class="card" style="margin-bottom:18px;padding:18px;border-left:5px solid var(--brand-blue)">
            
            <!-- CASO 1A: CLIENTE SELECCIONADO (AUTO-LLENADO 100%) -->
            <div id="box-dest-individual" style="<?= $contacto_pre ? 'display:block' : 'display:block' ?>">
                <?php if ($contacto_pre): ?>
                    <!-- TARJETA DE CLIENTE PRE-LLENADA -->
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
                        <div style="display:flex;align-items:center;gap:14px">
                            <div style="width:48px;height:48px;border-radius:50%;background:#2c60a4;color:#fff;font-size:18px;font-weight:900;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(44,96,164,0.3)">
                                <?= strtoupper(substr($contacto_pre['nombre'], 0, 1) . substr($contacto_pre['apellido'] ?? '', 0, 1)) ?>
                            </div>
                            <div>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <span style="font-size:17px;font-weight:800;color:var(--fg)">
                                        <?= h($contacto_pre['nombre'] . ' ' . $contacto_pre['apellido']) ?>
                                    </span>
                                    <span class="badge" style="background:<?= ($contacto_pre['prioridad']??'media')==='alta'?'#fee2e2':'#fef3c7' ?>;color:<?= ($contacto_pre['prioridad']??'media')==='alta'?'#dc2626':'#d97706' ?>;font-weight:800;font-size:10px">
                                        <?= strtoupper($contacto_pre['prioridad'] ?? 'MEDIA') ?>
                                    </span>
                                    <span class="badge badge-secondary" style="font-size:10px"><?= h($contacto_pre['etapa'] ?? 'lead') ?></span>
                                </div>
                                <div style="font-size:13px;color:var(--fg-secondary);margin-top:2px">
                                    <strong><?= h($contacto_pre['empresa'] ?: 'Empresa no especificada') ?></strong>
                                    <?= $contacto_pre['cargo'] ? ' • ' . h($contacto_pre['cargo']) : '' ?>
                                    <?= $contacto_pre['ciudad'] ? ' • 📍 ' . h($contacto_pre['ciudad']) : '' ?>
                                    • <strong style="color:var(--brand-blue)"><?= h($contacto_pre['email']) ?></strong>
                                </div>
                                <?php if (!empty($contacto_pre['notas'])): ?>
                                    <div style="font-size:11px;color:#047857;background:#ecfdf5;padding:2px 8px;border-radius:4px;display:inline-block;margin-top:4px">
                                        📌 Interés registrado: <?= h($contacto_pre['notas']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="display:flex;gap:8px;align-items:center">
                            <a href="?page=detalle&id=<?= $contacto_pre['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:11px">
                                👤 Ver Ficha Cliente
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="cambiarClienteIndividual()" style="font-size:11px">
                                🔄 Cambiar Cliente
                            </button>
                        </div>
                    </div>

                    <!-- Campos ocultos de soporte -->
                    <input type="hidden" id="dest-id" value="<?= $contacto_pre['id'] ?>">
                    <input type="hidden" id="dest-email" value="<?= h($contacto_pre['email']) ?>">
                    <input type="hidden" id="dest-nombre" value="<?= h($contacto_pre['nombre'] . ' ' . $contacto_pre['apellido']) ?>">
                    <input type="hidden" id="dest-empresa" value="<?= h($contacto_pre['empresa']) ?>">
                    <input type="hidden" id="dest-cargo" value="<?= h($contacto_pre['cargo']) ?>">
                    <input type="hidden" id="dest-ciudad" value="<?= h($contacto_pre['ciudad']) ?>">

                <?php else: ?>
                    <!-- SELECTOR DE CLIENTE SI NO VIENE DE UNA FICHA -->
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                            <label style="font-size:12px;font-weight:800;color:var(--fg);text-transform:uppercase;letter-spacing:0.5px">
                                👤 Seleccionar Cliente Destinatario:
                            </label>
                            <span style="font-size:11px;color:var(--fg-secondary)">Todos sus datos se cargarán automáticamente</span>
                        </div>
                        <select id="select-contacto-manual" class="form-control" style="font-size:14px;font-weight:600;padding:10px" onchange="alSeleccionarContactoManual(this)">
                            <option value="">— Haz clic aquí para elegir un cliente de tu lista comercial —</option>
                            <?php foreach ($contactos_list as $c): ?>
                                <?php 
                                $c_nom = trim($c['nombre'] . ' ' . $c['apellido']);
                                $c_prio = ($c['prioridad'] ?? 'media') === 'alta' ? '🔥' : (($c['prioridad'] ?? 'media') === 'media' ? '🟡' : '🌱');
                                ?>
                                <option value="<?= $c['id'] ?>"
                                        data-nombre="<?= h($c_nom) ?>"
                                        data-email="<?= h($c['email']) ?>"
                                        data-empresa="<?= h($c['empresa'] ?? '') ?>"
                                        data-cargo="<?= h($c['cargo'] ?? '') ?>"
                                        data-ciudad="<?= h($c['ciudad'] ?? '') ?>"
                                        data-prioridad="<?= h($c['prioridad'] ?? 'media') ?>"
                                        data-notas="<?= h($c['notas'] ?? '') ?>">
                                    <?= $c_prio ?> <?= h($c_nom) ?> <?= $c['empresa'] ? '— ' . h($c['empresa']) : '' ?> (<?= h($c['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="info-cliente-seleccionado" style="display:none;margin-top:10px;padding:10px 14px;background:#f8fafc;border-radius:8px;font-size:12px;color:var(--fg)"></div>
                        <input type="hidden" id="dest-id" value="">
                        <input type="hidden" id="dest-email" value="">
                        <input type="hidden" id="dest-nombre" value="">
                        <input type="hidden" id="dest-empresa" value="">
                        <input type="hidden" id="dest-cargo" value="">
                        <input type="hidden" id="dest-ciudad" value="">
                    </div>
                <?php endif; ?>
            </div>

            <!-- CASO 1B: LISTA DE CHEQUEO (MODO MASIVO) -->
            <div id="box-dest-masivo" style="display:none">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px">
                    <div>
                        <div style="font-weight:800;font-size:14px;color:var(--fg)">
                            📢 Lista de Chequeo de Clientes para Envío Masivo
                        </div>
                        <div style="font-size:11px;color:var(--fg-secondary)">
                            Marca las casillas de los clientes que recibirán este correo. Cada uno recibirá su correo 100% personalizado con su nombre y empresa.
                        </div>
                    </div>
                    <div id="badge-contador-chequeo" style="font-size:12px;font-weight:800;background:#ecfdf5;color:#047857;padding:5px 12px;border-radius:20px;border:1px solid #a7f3d0">
                        🟢 0 clientes seleccionados
                    </div>
                </div>

                <!-- CONTROLES RÁPIDOS DE CHEQUEO -->
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;background:#f8fafc;padding:10px 12px;border-radius:8px;margin-bottom:10px">
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="marcarTodos(true)">
                            ☑️ Marcar Todos
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="marcarTodos(false)">
                            ◻️ Desmarcar Todos
                        </button>
                        <button type="button" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;font-weight:700" onclick="marcarPorPrioridad('alta')">
                            🔥 Solo Alta Prioridad
                        </button>
                        <button type="button" class="btn btn-sm" style="background:#fef3c7;color:#d97706;font-weight:700" onclick="marcarPorPrioridad('media')">
                            🟡 Solo Media
                        </button>
                    </div>

                    <div style="min-width:240px">
                        <input type="text" id="buscador-lista-chequeo" class="form-control" style="font-size:12px;padding:6px 10px" placeholder="🔍 Buscar cliente o empresa..." oninput="filtrarListaChequeo(this.value)">
                    </div>
                </div>

                <!-- TABLA DE CHEQUEO CON SCROLL ESPACIOSO -->
                <div style="max-height:260px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;background:#fff">
                    <table class="table" style="margin:0;font-size:12px">
                        <thead style="position:sticky;top:0;background:#f1f5f9;z-index:2">
                            <tr>
                                <th style="width:40px;text-align:center">
                                    <input type="checkbox" id="chk-master" onchange="marcarTodos(this.checked)" style="transform:scale(1.2);cursor:pointer">
                                </th>
                                <th>Cliente / Cargo</th>
                                <th>Empresa</th>
                                <th>Email</th>
                                <th>Prioridad</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-chequeo-clientes">
                            <?php foreach ($contactos_list as $c_item): ?>
                                <?php 
                                $c_full = trim($c_item['nombre'] . ' ' . $c_item['apellido']);
                                $es_alta = ($c_item['prioridad'] ?? '') === 'alta';
                                ?>
                                <tr class="fila-cliente-chequeo" 
                                    data-id="<?= $c_item['id'] ?>"
                                    data-nombre="<?= strtolower(h($c_full)) ?>"
                                    data-empresa="<?= strtolower(h($c_item['empresa'] ?? '')) ?>"
                                    data-prioridad="<?= h($c_item['prioridad'] ?? 'media') ?>"
                                    style="cursor:pointer" onclick="toggleCheckboxFila(event, <?= $c_item['id'] ?>)">
                                    <td style="text-align:center">
                                        <input type="checkbox" class="chk-cliente" value="<?= $c_item['id'] ?>" 
                                               <?= $es_alta ? 'checked' : '' ?> 
                                               style="transform:scale(1.2);cursor:pointer"
                                               onchange="actualizarContadorChequeo()">
                                    </td>
                                    <td>
                                        <strong><?= h($c_full) ?></strong>
                                        <?php if ($c_item['cargo']): ?>
                                            <div style="font-size:10px;color:var(--fg-secondary)"><?= h($c_item['cargo']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-weight:700;color:var(--fg)"><?= h($c_item['empresa'] ?: '—') ?></span>
                                    </td>
                                    <td>
                                        <span style="color:var(--brand-blue)"><?= h($c_item['email']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($es_alta): ?>
                                            <span class="badge" style="background:#fee2e2;color:#dc2626;font-weight:800">🔥 ALTA</span>
                                        <?php elseif (($c_item['prioridad'] ?? '') === 'media'): ?>
                                            <span class="badge" style="background:#fef3c7;color:#d97706;font-weight:800">🟡 MEDIA</span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#f1f5f9;color:#64748b;font-weight:800">🌱 BAJA</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- PASO 2: LA BARRA DE ASISTENTE DE IA (MINIMALISTA Y DIRECTA) -->
        <div class="card" style="margin-bottom:20px;padding:20px;background:linear-gradient(135deg, #ffffff 0%, #f0f7ff 100%);border:1px solid #bfdbfe;box-shadow:var(--shadow-sm)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:22px">✨</span>
                    <div>
                        <strong style="font-size:14px;color:#1e3a8a">Escribe con la Inteligencia Artificial</strong>
                        <span style="font-size:11px;color:#64748b;display:block">Dile tu idea o pulsa una habilidad y la IA redactará el asunto y el mensaje directamente en la carta</span>
                    </div>
                </div>

                <!-- SELECTOR DE SKILL OCULTO O CHIPS -->
                <input type="hidden" id="skill-seleccionada" value="email_prospeccion_fria">
            </div>

            <!-- CHIPS DE HABILIDADES RÁPIDAS (1 CLIC) -->
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px">
                <button type="button" class="chip-skill active" onclick="seleccionarSkillChip(this, 'email_prospeccion_fria', 'Prospección de primer contacto para abrir puertas en planta y conseguir reunión')">
                    ❄️ Prospección en Frío
                </button>
                <button type="button" class="chip-skill" onclick="seleccionarSkillChip(this, 'email_seguimiento_cotizacion', 'Seguimiento cordial a cotización formal para acelerar la decisión')">
                    📄 Seguimiento Cotización
                </button>
                <button type="button" class="chip-skill" onclick="seleccionarSkillChip(this, 'email_post_feria_comercial', 'Nutrición comercial a contacto obtenido en feria industrial Andina Pack')">
                    🎪 Post-Feria Industrial
                </button>
                <button type="button" class="chip-skill" onclick="seleccionarSkillChip(this, 'email_reactivacion_magica', 'Reactivación breve de 9 palabras para prospectos que dejaron de responder')">
                    ☕ Reactivación Rápida
                </button>
                <button type="button" class="chip-skill" onclick="seleccionarSkillChip(this, 'email_retorno_inversion', 'Demostración de retorno de inversión por reducción de merma y velocidad')">
                    💰 Retorno de Inversión (ROI)
                </button>
                <button type="button" class="chip-skill" onclick="seleccionarSkillChip(this, 'email_manejo_objeciones_precio', 'Diferenciación de calidad y garantía de 1 año frente a máquinas baratas')">
                    🛡️ Objeción de Precio
                </button>
            </div>

            <!-- CAMPO DE IDEA DE ENTRADA Y BOTÓN GIGANTE -->
            <div style="display:flex;gap:10px;align-items:stretch;flex-wrap:wrap">
                <input type="text" id="prompt-idea-usuario" class="form-control" 
                       style="font-size:14px;padding:12px 16px;border-radius:8px;border:2px solid #93c5fd;flex:1;min-width:280px" 
                       placeholder="¿Qué deseas proponerle? Ej: Ofrecerle selladora continua con fechador de lote, entrega inmediata en Bogotá y visita al Showroom..."
                       onkeydown="if(event.key==='Enter') ejecutarRedaccionIA()">

                <button type="button" id="btn-generar-ia" class="btn btn-primary" onclick="ejecutarRedaccionIA()" 
                        style="padding:12px 24px;font-size:14px;font-weight:800;background:linear-gradient(135deg, #2c60a4 0%, #1e40af 100%);box-shadow:0 4px 12px rgba(44,96,164,0.3);white-space:nowrap">
                    <span id="txt-btn-ia">✨ Redactar Correo con IA</span>
                    <span id="spin-btn-ia" style="display:none">⏳ Redactando...</span>
                </button>
            </div>
            
            <div id="status-ia-feedback" style="display:none;margin-top:10px;font-size:12px;padding:8px 12px;background:#fff;border-radius:6px;border-left:3px solid #10b981;color:#065f46">
            </div>
        </div>

        <!-- PASO 3: LA CARTA DIGITAL / PREVISUALIZACIÓN EN VIVO (WYSIWYG) -->
        <div style="background:#fff;border:1px solid #cbd5e1;border-radius:12px;box-shadow:var(--shadow-lg);overflow:hidden;margin-bottom:24px">
            
            <!-- MEMBRETE CORPORATIVO OFICIAL DE POWER PACK -->
            <div style="background:#0f172a;padding:16px 24px;border-bottom:3px solid #2c60a4;display:flex;align-items:center;justify-content:space-between">
                <div style="display:flex;align-items:center;gap:12px">
                    <img src="assets/logo-blanco.png" alt="Power Pack" style="height:38px;width:auto">
                    <div style="border-left:1px solid rgba(255,255,255,0.2);padding-left:12px">
                        <div style="color:#fff;font-size:13px;font-weight:800;letter-spacing:0.5px">SOLUCIONES INDUSTRIALES</div>
                        <div style="color:#94a3b8;font-size:11px">Maquinaria de Empaque, Sellado y Dosificado</div>
                    </div>
                </div>
                <div style="text-align:right">
                    <span style="font-size:11px;color:#94a3b8">Bandeja de Salida Hostinger SMTP</span>
                    <div style="font-size:12px;color:#38bdf8;font-weight:700">Garantía 12 Meses • Bodega Bogotá</div>
                </div>
            </div>

            <!-- ENCABEZADO DE LA CARTA (ASUNTO Y DESTINATARIO) -->
            <div style="background:#f8fafc;padding:18px 24px;border-bottom:1px solid #e2e8f0;display:flex;flex-direction:column;gap:12px">
                
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:12px;font-weight:800;color:var(--fg-secondary);width:60px;text-transform:uppercase">Para:</span>
                    <div id="carta-destinatario-label" style="font-size:14px;font-weight:700;color:var(--fg);flex:1">
                        <?= $contacto_pre ? h($contacto_pre['nombre'] . ' ' . $contacto_pre['apellido'] . ' (' . ($contacto_pre['empresa'] ?: 'Empresa') . ') <' . $contacto_pre['email'] . '>') : 'Selecciona un cliente o marca las casillas arriba' ?>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:12px;font-weight:800;color:var(--fg-secondary);width:60px;text-transform:uppercase">Asunto:</span>
                    <input type="text" id="carta-asunto" class="form-control" 
                           style="font-size:16px;font-weight:800;color:var(--fg);border:1px solid #cbd5e1;background:#fff;padding:10px 14px;border-radius:8px;flex:1"
                           placeholder="El asunto del correo generado por la IA aparecerá aquí..."
                           value="<?= $contacto_pre ? 'Propuesta de Maquinaria y Soluciones de Empaque para ' . h($contacto_pre['empresa'] ?: 'su planta') . ' | Power Pack' : 'Propuesta Comercial y Soluciones Industriales | Power Pack' ?>">
                </div>

            </div>

            <!-- CUERPO DE LA CARTA (ESPACIOSO, VISUAL, EDITABLE) -->
            <div style="padding:28px 24px;background:#ffffff">
                <textarea id="carta-cuerpo" 
                          style="width:100%;min-height:380px;font-size:15px;line-height:1.7;color:#1e293b;border:none;background:transparent;resize:vertical;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;outline:none;padding:0" 
                          placeholder="El cuerpo del correo generado por la IA aparecerá aquí redactado de forma impecable. Podrás hacer clic y cambiar cualquier palabra directamente como en Word..."><?php
if ($contacto_pre) {
    echo "Estimado(a) " . h($contacto_pre['nombre']) . ",\n\n";
    echo "Le saluda el equipo comercial de Power Pack SAS. Esperamos que se encuentre muy bien en " . h($contacto_pre['empresa'] ?: 'su empresa') . ".\n\n";
    echo "Nos ponemos en contacto para presentarle nuestras soluciones en maquinaria industrial de empaque, selladoras continuas con fechador de lote integrado y dosificadoras de alta precisión en acero inoxidable 304/316.\n\n";
    echo "Ventajas clave de equipar su planta con Power Pack:\n";
    echo "• 12 meses de garantía directa en estructura y componentes mecánicos.\n";
    echo "• Entrega inmediata y stock permanente de repuestos en Bogotá (sin esperar meses de importación).\n";
    echo "• Acompañamiento técnico, instalación y capacitación para sus operarios.\n\n";
    echo "¿Tendría 10 minutos esta semana para una breve videollamada técnica o le gustaría coordinar una visita a nuestro Showroom en Bogotá (Calle 161 # 54 - 25) para probar los equipos con su producto real?\n\n";
    echo "Quedamos atentos a sus comentarios.";
} else {
    echo "Estimado(a) {nombre},\n\nLe saluda el equipo comercial de Power Pack SAS. Esperamos que se encuentre muy bien en {empresa}.\n\nNos ponemos en contacto para presentarle nuestras soluciones en maquinaria industrial de empaque, selladoras de banda continua y dosificadoras de alta precisión.\n\nTodos nuestros equipos cuentan con 12 meses de garantía directa, entrega inmediata en Bogotá y soporte técnico nacional.\n\n¿Tendría unos minutos esta semana para revisar una propuesta adaptada a su volumen de producción?\n\nCordialmente,";
}
?></textarea>
            </div>

            <!-- PIE DE FIRMA CORPORATIVO -->
            <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 24px;font-size:12px;color:#64748b;line-height:1.6;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
                <div>
                    <strong style="color:#0f172a;font-size:13px">Power Pack SAS</strong> • Maquinaria y Soluciones de Empaque Industrial<br>
                    Showroom Técnico: Calle 161 # 54 - 25, Bogotá, Colombia • Tel / WhatsApp: +57 300 467 0474<br>
                    <a href="https://powerpack.com.co" target="_blank" style="color:#2c60a4;text-decoration:none;font-weight:bold">www.powerpack.com.co</a>
                </div>

                <!-- VARIABLES RÁPIDAS PARA INSERTAR -->
                <div style="display:flex;gap:4px;align-items:center">
                    <span style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Insertar:</span>
                    <button type="button" class="btn-chip" onclick="insertarEnCarta('{nombre}')">{nombre}</button>
                    <button type="button" class="btn-chip" onclick="insertarEnCarta('{empresa}')">{empresa}</button>
                    <button type="button" class="btn-chip" onclick="insertarEnCarta('{cargo}')">{cargo}</button>
                    <button type="button" class="btn-chip" onclick="insertarEnCarta('{ciudad}')">{ciudad}</button>
                </div>
            </div>

        </div>

        <!-- PASO 4: BARRA DE ENVÍO GRANDE Y DESTACADA -->
        <div style="display:flex;justify-content:space-between;align-items:center;padding:18px 24px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow-sm);flex-wrap:wrap;gap:14px">
            <div>
                <div style="font-weight:800;font-size:13px;color:var(--fg)" id="resumen-envio-label">
                    <?= $contacto_pre ? 'Listo para enviar a: ' . h($contacto_pre['nombre'] . ' (' . $contacto_pre['email'] . ')') : 'Selecciona el destinatario o marca las casillas arriba para enviar' ?>
                </div>
                <div style="font-size:11px;color:var(--fg-secondary)">
                    Envío seguro mediante servidor oficial SMTP de Hostinger (smtp.hostinger.com:465 SSL)
                </div>
            </div>

            <div style="display:flex;gap:10px;align-items:center">
                <button type="button" class="btn btn-secondary" onclick="copiarTextoCarta()">
                    📋 Copiar Texto
                </button>
                <button type="button" id="btn-despacho-principal" class="btn btn-primary" onclick="ejecutarDespachoCorreo()" 
                        style="padding:14px 32px;font-size:15px;font-weight:900;background:#059669;border-color:#059669;box-shadow:0 4px 14px rgba(5,150,105,0.3)">
                    <span id="txt-despacho">🚀 Enviar Correo Ahora</span>
                    <span id="spin-despacho" style="display:none">⏳ Enviando por SMTP...</span>
                </button>
            </div>
        </div>

    </div>

    <!-- ======================================================== -->
    <!-- VISTA SECUNDARIA: BANDEJA DE HISTORIAL (OUTBOX)          -->
    <!-- ======================================================== -->
    <div id="vista-historial" style="display:none">
        <div class="card" style="padding:0;overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;background:#f8fafc">
                <div>
                    <h3 style="font-size:15px;font-weight:800;margin:0">📬 Historial de Correos Despachados</h3>
                    <div style="font-size:11px;color:var(--fg-secondary)">Registro auditado de correos enviados vía SMTP</div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setModoPrincipal('individual')">
                    ← Volver al Redactor
                </button>
            </div>

            <div class="table-responsive">
                <table class="table" style="margin:0;font-size:12px">
                    <thead>
                        <tr style="background:#f1f5f9">
                            <th>Fecha / Hora</th>
                            <th>Destinatario</th>
                            <th>Empresa</th>
                            <th>Asunto</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th style="text-align:right">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historial_correos)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center;padding:32px;color:var(--fg-secondary)">
                                    Aún no has enviado correos. Regresa al redactor y envía tu primer correo.
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
                                        <span style="color:var(--brand-blue);font-size:11px"><?= h($h_item['destinatario_email']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= h($h_item['c_empresa'] ?: '—') ?></strong>
                                    </td>
                                    <td style="max-width:280px">
                                        <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= h($h_item['asunto']) ?>">
                                            <strong><?= h($h_item['asunto']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <?= $h_item['es_masivo'] ? '<span class="badge" style="background:#f3e8ff;color:#7e22ce;font-weight:700">📢 Masivo</span>' : '<span class="badge" style="background:#eff6ff;color:#1e40af;font-weight:700">👤 1-a-1</span>' ?>
                                    </td>
                                    <td>
                                        <?= $h_item['estado']==='enviado' ? '<span class="badge badge-success">✓ Enviado</span>' : '<span class="badge badge-danger">✕ Fallido</span>' ?>
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

</div>

<!-- MODAL VISOR DE CORREO -->
<div id="modal-visor-correo" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:12px;max-width:680px;width:100%;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:var(--shadow-lg)">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;background:#0f172a;color:#fff">
            <div>
                <h3 style="font-size:14px;font-weight:800;margin:0" id="modal-correo-asunto">Detalle del Correo</h3>
                <div style="font-size:11px;color:#94a3b8" id="modal-correo-meta">Destinatario</div>
            </div>
            <button type="button" onclick="cerrarModalCorreo()" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer">✕</button>
        </div>
        <div style="padding:24px;overflow-y:auto;flex:1" id="modal-correo-contenido"></div>
        <div style="padding:12px 20px;border-top:1px solid var(--border);background:#f8fafc;display:flex;justify-content:flex-end">
            <button type="button" class="btn btn-secondary btn-sm" onclick="cerrarModalCorreo()">Cerrar</button>
        </div>
    </div>
</div>

<style>
.chip-skill {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 11px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease;
}
.chip-skill:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #0f172a;
}
.chip-skill.active {
    background: #2c60a4;
    border-color: #2c60a4;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(44,96,164,0.25);
}
.btn-chip {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 3px 8px;
    font-size: 10px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
}
.btn-chip:hover {
    background: #e2e8f0;
}
.fila-cliente-chequeo:hover {
    background: #f8fafc;
}
</style>

<script>
let modoActual = '<?= $contacto_pre ? 'individual' : 'individual' ?>';
let contactosLista = <?= json_encode($contactos_list) ?>;
let contactoPreData = <?= json_encode($contacto_pre) ?>;

// Cambiar pestaña principal (Un Cliente / Lista de Chequeo / Historial)
function setModoPrincipal(modo) {
    modoActual = modo;
    
    // Botones de pestañas
    document.getElementById('tab-btn-individual').style.background = modo === 'individual' ? '#fff' : 'transparent';
    document.getElementById('tab-btn-individual').style.color = modo === 'individual' ? 'var(--brand-blue)' : 'var(--fg-secondary)';
    document.getElementById('tab-btn-individual').style.boxShadow = modo === 'individual' ? 'var(--shadow-sm)' : 'none';

    document.getElementById('tab-btn-masivo').style.background = modo === 'masivo' ? '#fff' : 'transparent';
    document.getElementById('tab-btn-masivo').style.color = modo === 'masivo' ? 'var(--brand-blue)' : 'var(--fg-secondary)';
    document.getElementById('tab-btn-masivo').style.boxShadow = modo === 'masivo' ? 'var(--shadow-sm)' : 'none';

    document.getElementById('tab-btn-historial').style.background = modo === 'historial' ? '#fff' : 'transparent';
    document.getElementById('tab-btn-historial').style.color = modo === 'historial' ? 'var(--brand-blue)' : 'var(--fg-secondary)';
    document.getElementById('tab-btn-historial').style.boxShadow = modo === 'historial' ? 'var(--shadow-sm)' : 'none';

    if (modo === 'historial') {
        document.getElementById('vista-redactor').style.display = 'none';
        document.getElementById('vista-historial').style.display = 'block';
    } else {
        document.getElementById('vista-redactor').style.display = 'block';
        document.getElementById('vista-historial').style.display = 'none';

        if (modo === 'individual') {
            document.getElementById('box-dest-individual').style.display = 'block';
            document.getElementById('box-dest-masivo').style.display = 'none';
            actualizarLabelCartaIndividual();
        } else {
            document.getElementById('box-dest-individual').style.display = 'none';
            document.getElementById('box-dest-masivo').style.display = 'block';
            actualizarContadorChequeo();
        }
    }
}

// Selección de Chip de Habilidad
function seleccionarSkillChip(btn, skillCodigo, promptSugerido) {
    document.querySelectorAll('.chip-skill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('skill-seleccionada').value = skillCodigo;

    // Si el usuario no ha escrito nada propio, poner la sugerencia
    const inputPrompt = document.getElementById('prompt-idea-usuario');
    if (!inputPrompt.value.trim() || inputPrompt.value.startsWith('Prospección') || inputPrompt.value.startsWith('Seguimiento') || inputPrompt.value.startsWith('Reactivación') || inputPrompt.value.startsWith('Nutrición')) {
        inputPrompt.value = promptSugerido;
    }
    
    // Auto-ejecutar la redacción para inmediatez
    ejecutarRedaccionIA();
}

// Al seleccionar contacto desde el desplegable manual
function alSeleccionarContactoManual(select) {
    const opt = select.options[select.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('dest-id').value = opt.value;
        document.getElementById('dest-email').value = opt.getAttribute('data-email') || '';
        document.getElementById('dest-nombre').value = opt.getAttribute('data-nombre') || '';
        document.getElementById('dest-empresa').value = opt.getAttribute('data-empresa') || '';
        document.getElementById('dest-cargo').value = opt.getAttribute('data-cargo') || '';
        document.getElementById('dest-ciudad').value = opt.getAttribute('data-ciudad') || '';

        const infoBox = document.getElementById('info-cliente-seleccionado');
        infoBox.style.display = 'block';
        infoBox.innerHTML = `
            <strong>${opt.getAttribute('data-nombre')}</strong> • Empresa: <strong>${opt.getAttribute('data-empresa') || 'No especificada'}</strong> 
            • Cargo: ${opt.getAttribute('data-cargo') || 'Directivo'} • Email: <strong style="color:var(--brand-blue)">${opt.getAttribute('data-email')}</strong>
        `;

        actualizarLabelCartaIndividual();

        // Si ya hay un correo redactado, reemplazar variables visualmente
        reemplazarVariablesEnCartaVisual(opt.getAttribute('data-nombre'), opt.getAttribute('data-empresa'));
    }
}

function cambiarClienteIndividual() {
    window.location.href = 'index.php?page=correos';
}

function actualizarLabelCartaIndividual() {
    const nombre = document.getElementById('dest-nombre').value;
    const email = document.getElementById('dest-email').value;
    const empresa = document.getElementById('dest-empresa').value;

    if (email) {
        document.getElementById('carta-destinatario-label').innerHTML = `
            <strong>${nombre || 'Cliente'}</strong> ${empresa ? '(' + empresa + ')' : ''} &lt;<span style="color:var(--brand-blue)">${email}</span>&gt;
        `;
        document.getElementById('resumen-envio-label').innerText = `Listo para enviar a: ${nombre || 'Cliente'} (${email})`;
        document.getElementById('btn-despacho-principal').style.display = 'inline-flex';
    } else {
        document.getElementById('carta-destinatario-label').innerText = 'Elige un cliente arriba o marca casillas en la lista de chequeo';
        document.getElementById('resumen-envio-label').innerText = 'Selecciona un cliente arriba';
    }
}

// Funciones de la Lista de Chequeo (Checklist)
function toggleCheckboxFila(e, id) {
    if (e.target.tagName === 'INPUT') return;
    const chk = document.querySelector(`.chk-cliente[value="${id}"]`);
    if (chk) {
        chk.checked = !chk.checked;
        actualizarContadorChequeo();
    }
}

function marcarTodos(estado) {
    document.querySelectorAll('.chk-cliente').forEach(chk => {
        // Solo marcar las visibles según el filtro
        const fila = chk.closest('tr');
        if (fila && fila.style.display !== 'none') {
            chk.checked = estado;
        }
    });
    document.getElementById('chk-master').checked = estado;
    actualizarContadorChequeo();
}

function marcarPorPrioridad(prioridad) {
    document.querySelectorAll('.fila-cliente-chequeo').forEach(fila => {
        const chk = fila.querySelector('.chk-cliente');
        if (fila.getAttribute('data-prioridad') === prioridad) {
            chk.checked = true;
            fila.style.display = '';
        } else {
            chk.checked = false;
        }
    });
    actualizarContadorChequeo();
}

function filtrarListaChequeo(query) {
    query = query.toLowerCase().trim();
    document.querySelectorAll('.fila-cliente-chequeo').forEach(fila => {
        const nom = fila.getAttribute('data-nombre') || '';
        const emp = fila.getAttribute('data-empresa') || '';
        if (!query || nom.includes(query) || emp.includes(query)) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
    actualizarContadorChequeo();
}

function actualizarContadorChequeo() {
    const seleccionados = document.querySelectorAll('.chk-cliente:checked').length;
    const badge = document.getElementById('badge-contador-chequeo');
    badge.innerText = `🟢 ${seleccionados} clientes seleccionados`;

    if (modoActual === 'masivo') {
        document.getElementById('carta-destinatario-label').innerHTML = `
            <strong>📢 Envío Masivo a ${seleccionados} clientes seleccionados en la lista de chequeo</strong>
        `;
        document.getElementById('resumen-envio-label').innerText = `Listo para enviar masivamente a ${seleccionados} clientes seleccionados`;
    }
}

// Insertar variables en el textarea de la carta
function insertarEnCarta(token) {
    const textarea = document.getElementById('carta-cuerpo');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + token + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + token.length;
}

function reemplazarVariablesEnCartaVisual(nombre, empresa) {
    let cuerpo = document.getElementById('carta-cuerpo').value;
    if (nombre && cuerpo.includes('{nombre}')) {
        cuerpo = cuerpo.replace(/{nombre}/g, nombre);
    }
    if (empresa && cuerpo.includes('{empresa}')) {
        cuerpo = cuerpo.replace(/{empresa}/g, empresa);
    }
    document.getElementById('carta-cuerpo').value = cuerpo;
}

// EJECUTAR REDACCIÓN CON IA (AUTO-RELLENA ASUNTO Y CARTA)
async function ejecutarRedaccionIA() {
    const promptInput = document.getElementById('prompt-idea-usuario');
    const idea = promptInput.value.trim() || 'Propuesta de maquinaria de empaque y sellado con 12 meses de garantía y stock en Bogotá';
    const skill = document.getElementById('skill-seleccionada').value;

    let contactoId = 0;
    let contactoContexto = null;

    if (modoActual === 'individual') {
        contactoId = parseInt(document.getElementById('dest-id').value) || 0;
        if (contactoId) {
            contactoContexto = {
                nombre: document.getElementById('dest-nombre').value,
                empresa: document.getElementById('dest-empresa').value,
                cargo: document.getElementById('dest-cargo').value,
                ciudad: document.getElementById('dest-ciudad').value,
                email: document.getElementById('dest-email').value
            };
        }
    }

    // Indicador visual en el botón
    document.getElementById('txt-btn-ia').style.display = 'none';
    document.getElementById('spin-btn-ia').style.display = 'inline';
    document.getElementById('btn-generar-ia').disabled = true;

    try {
        const resp = await fetch('api.php?action=subagente_chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                subagente_id: 'email',
                skill_codigo: skill,
                contacto_id: contactoId,
                instrucciones: idea,
                borrador: {
                    asunto: document.getElementById('carta-asunto').value,
                    cuerpo: document.getElementById('carta-cuerpo').value
                }
            })
        });

        const data = await resp.json();
        if (data.ok) {
            // AUTO-RELLENAR ASUNTO Y CARTA EN LA PREVISUALIZACIÓN DIRECTAMENTE
            if (data.asunto) {
                document.getElementById('carta-asunto').value = data.asunto;
            }
            if (data.cuerpo_texto || data.cuerpo_html) {
                document.getElementById('carta-cuerpo').value = data.cuerpo_texto || data.cuerpo_html.replace(/<[^>]*>/g, '');
            }

            // Notificación sutil de éxito
            const feed = document.getElementById('status-ia-feedback');
            feed.style.display = 'block';
            feed.innerHTML = `✅ <strong>Redactado con éxito:</strong> ${data.respuesta_chat || 'Borrador generado y listo para enviar.'} <em>(${data.origen || 'IA Power Pack'})</em>`;
            
            // Scroll suave a la carta para que el usuario vea el resultado inmediatamente
            document.getElementById('carta-asunto').scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            alert('Aviso de la IA: ' + (data.error || 'No se pudo generar el correo'));
        }
    } catch (e) {
        alert('Error conectando con la IA: ' + e.message);
    } finally {
        document.getElementById('txt-btn-ia').style.display = 'inline';
        document.getElementById('spin-btn-ia').style.display = 'none';
        document.getElementById('btn-generar-ia').disabled = false;
    }
}

// EJECUTAR DESPACHO POR SMTP
async function ejecutarDespachoCorreo() {
    const asunto = document.getElementById('carta-asunto').value.trim();
    const cuerpo = document.getElementById('carta-cuerpo').value.trim();
    const skill = document.getElementById('skill-seleccionada').value;

    if (!asunto || !cuerpo) {
        alert('Por favor redacta o genera un asunto y cuerpo de correo antes de enviar.');
        return;
    }

    let payload = {
        asunto: asunto,
        cuerpo_html: cuerpo,
        skill_codigo: skill,
        subagente: 'email_copywriter'
    };

    if (modoActual === 'individual') {
        const destEmail = document.getElementById('dest-email').value.trim();
        const destNombre = document.getElementById('dest-nombre').value.trim();
        const destId = parseInt(document.getElementById('dest-id').value) || 0;

        if (!destEmail) {
            alert('Por favor selecciona un cliente destinatario primero.');
            return;
        }

        if (!confirm(`¿Confirmas el envío inmediato de este correo a ${destNombre || destEmail} mediante el servidor SMTP de Hostinger?`)) {
            return;
        }

        payload.modo = 'individual';
        payload.contacto_id = destId;
        payload.destinatario_email = destEmail;
        payload.destinatario_nombre = destNombre;
    } else {
        // MODO MASIVO POR LISTA DE CHEQUEO
        const seleccionados = Array.from(document.querySelectorAll('.chk-cliente:checked')).map(chk => parseInt(chk.value));
        if (seleccionados.length === 0) {
            alert('Debes marcar al menos una casilla en la lista de chequeo para enviar.');
            return;
        }

        if (!confirm(`¿Confirmas el envío de este correo a los ${seleccionados.length} clientes seleccionados mediante SMTP Hostinger? Cada uno recibirá su correo personalizado con su nombre y empresa.`)) {
            return;
        }

        payload.modo = 'masivo';
        payload.contactos_ids = seleccionados;
    }

    // Botón en carga
    document.getElementById('txt-despacho').style.display = 'none';
    document.getElementById('spin-despacho').style.display = 'inline';
    document.getElementById('btn-despacho-principal').disabled = true;

    try {
        const resp = await fetch('api.php?action=enviar_correo_estudio', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const res = await resp.json();
        if (res.ok) {
            alert('🚀 ' + (res.mensaje || '¡Correo despachado exitosamente vía SMTP!'));
            window.location.href = 'index.php?page=correos&msg=email_enviado';
        } else {
            alert('❌ Error al enviar: ' + (res.error || 'Revisa tu configuración SMTP en Ajustes.'));
        }
    } catch (e) {
        alert('Error de conexión: ' + e.message);
    } finally {
        document.getElementById('txt-despacho').style.display = 'inline';
        document.getElementById('spin-despacho').style.display = 'none';
        document.getElementById('btn-despacho-principal').disabled = false;
    }
}

// Copiar texto de la carta
function copiarTextoCarta() {
    const asunto = document.getElementById('carta-asunto').value;
    const cuerpo = document.getElementById('carta-cuerpo').value;
    const texto = `ASUNTO: ${asunto}\n\n${cuerpo}`;
    navigator.clipboard.writeText(texto).then(() => {
        alert('Asunto y cuerpo del correo copiados al portapapeles');
    });
}

// Modal visor
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
    actualizarContadorChequeo();
    <?php if ($contacto_pre): ?>
        actualizarLabelCartaIndividual();
    <?php endif; ?>
});
</script>
