<?php
/**
 * pages/conocimiento.php - Repositorio de Información & Copiloto IA de Power Pack
 */

require_once __DIR__ . '/../ai.php';

$mensaje_exito = '';
$mensaje_error = '';

// Guardar configuración de IA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_ai_config'])) {
    set_config($db, 'ai_provider', trim($_POST['ai_provider'] ?? 'gemini'));
    set_config($db, 'ai_api_key', trim($_POST['ai_api_key'] ?? ''));
    set_config($db, 'ai_model', trim($_POST['ai_model'] ?? 'gemini-2.0-flash'));
    $mensaje_exito = 'Configuración de Inteligencia Artificial guardada correctamente.';
}

// Guardar base de conocimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_knowledge_base'])) {
    $kb_text = trim($_POST['ai_knowledge_base'] ?? '');
    set_config($db, 'ai_knowledge_base', $kb_text);
    $mensaje_exito = 'Repositorio empresarial y Base de Conocimiento actualizados con éxito.';
}

// Restaurar base de conocimiento predeterminada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restaurar_knowledge_base'])) {
    $default_kb = get_ai_default_knowledge();
    set_config($db, 'ai_knowledge_base', $default_kb);
    $mensaje_exito = 'Base de conocimiento restaurada con el catálogo y políticas oficiales de Power Pack.';
}

// Crear nueva Skill B2B
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_skill'])) {
    $codigo = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $_POST['codigo'] ?? '')));
    $nombre = trim($_POST['nombre'] ?? '');
    $canal = trim($_POST['canal'] ?? 'ambos');
    $desc = trim($_POST['descripcion'] ?? '');
    $framework = trim($_POST['framework_prompt'] ?? '');
    $ejemplo = trim($_POST['ejemplo_salida'] ?? '');
    if ($codigo && $nombre && $framework) {
        $stmt_sk = $db->prepare("INSERT INTO skills_ia (codigo, nombre, canal, categoria, descripcion, framework_prompt, ejemplo_salida, activo) VALUES (?, ?, ?, 'b2b_ventas', ?, ?, ?, 1)");
        $stmt_sk->bindValue(1, $codigo, SQLITE3_TEXT);
        $stmt_sk->bindValue(2, $nombre, SQLITE3_TEXT);
        $stmt_sk->bindValue(3, $canal, SQLITE3_TEXT);
        $stmt_sk->bindValue(4, $desc, SQLITE3_TEXT);
        $stmt_sk->bindValue(5, $framework, SQLITE3_TEXT);
        $stmt_sk->bindValue(6, $ejemplo, SQLITE3_TEXT);
        $stmt_sk->execute();
        $mensaje_exito = "Skill comercial '$nombre' creada e instalada con éxito.";
    } else {
        $mensaje_error = "Por favor completa el código, nombre y directrices de la skill.";
    }
}

// Eliminar Skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_skill'])) {
    $sk_id = (int)($_POST['skill_id'] ?? 0);
    if ($sk_id > 0) {
        $db->exec("DELETE FROM skills_ia WHERE id = $sk_id");
        $mensaje_exito = "Skill comercial eliminada correctamente.";
    }
}

$ai_settings = get_ai_settings($db);
$skills_list = $db->query("SELECT * FROM skills_ia ORDER BY id ASC");
?>

<div class="page-header">
    <div>
        <h1>🧠 Repositorio IA & Base de Conocimiento</h1>
        <p>Entrena al Asistente IA de Power Pack con la información de tu empresa, catálogo de maquinaria y políticas comerciales</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="?page=contactos" class="btn btn-secondary btn-sm">👥 Ir a Contactos</a>
        <a href="https://aistudio.google.com/app/apikey" target="_blank" class="btn btn-secondary btn-sm" title="Obtener API Key gratuita de Google Gemini">
            🔑 Obtener API Key de Gemini Gratis ↗
        </a>
    </div>
</div>

<?php if ($mensaje_exito): ?>
<div class="alert alert-success" style="margin-bottom:20px">
    ✅ <?= h($mensaje_exito) ?>
</div>
<?php endif; ?>

<?php if ($mensaje_error): ?>
<div class="alert alert-danger" style="margin-bottom:20px">
    ⚠️ <?= h($mensaje_error) ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1.1fr 1fr;gap:24px">

    <!-- COLUMNA 1: CONFIGURACIÓN DE IA Y SIMULADOR -->
    <div style="display:flex;flex-direction:column;gap:24px">
        
        <!-- TARJETA: MOTOR DE INTELIGENCIA ARTIFICIAL -->
        <div class="card" style="border-top:3px solid #2c60a4">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:24px">⚡</span>
                    <div>
                        <h3 style="margin:0;font-size:16px;color:#2c60a4">Motor de Inteligencia Artificial</h3>
                        <p style="margin:2px 0 0 0;font-size:12px;color:var(--fg-secondary)">Conecta Google Gemini u OpenAI para redacciones comerciales autónomas</p>
                    </div>
                </div>
                <div>
                    <?php if (!empty($ai_settings['api_key'])): ?>
                    <span style="display:inline-block;padding:3px 10px;border-radius:20px;background:#ecfdf5;color:#059669;font-size:11px;font-weight:800">
                        ● Conectado a <?= strtoupper($ai_settings['provider']) ?>
                    </span>
                    <?php else: ?>
                    <span style="display:inline-block;padding:3px 10px;border-radius:20px;background:#eff6ff;color:#2c60a4;font-size:11px;font-weight:800">
                        ⚡ Motor Inteligente Interno Activo
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="guardar_ai_config" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label style="font-weight:700;font-size:12px">Proveedor de IA:</label>
                        <select name="ai_provider" id="ai_provider" onchange="actualizarModelos()" style="font-weight:600">
                            <option value="gemini" <?= $ai_settings['provider']==='gemini'?'selected':'' ?>>Google Gemini (Recomendado - Free Tier)</option>
                            <option value="openai" <?= $ai_settings['provider']==='openai'?'selected':'' ?>>OpenAI (ChatGPT)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:700;font-size:12px">Modelo de IA:</label>
                        <select name="ai_model" id="ai_model" style="font-weight:600">
                            <!-- Opciones actualizadas por JS -->
                            <option value="gemini-2.0-flash" <?= $ai_settings['model']==='gemini-2.0-flash'?'selected':'' ?>>Gemini 2.0 Flash (Ultrarrápido y potente)</option>
                            <option value="gemini-1.5-flash" <?= $ai_settings['model']==='gemini-1.5-flash'?'selected':'' ?>>Gemini 1.5 Flash</option>
                            <option value="gpt-4o-mini" <?= $ai_settings['model']==='gpt-4o-mini'?'selected':'' ?>>GPT-4o Mini</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top:10px">
                    <label style="font-weight:700;font-size:12px">API Key secreta:</label>
                    <input type="password" name="ai_api_key" value="<?= h($ai_settings['api_key']) ?>" placeholder="AIzaSy... o sk-proj-..." style="font-family:monospace;font-size:13px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
                        <span style="font-size:11px;color:var(--fg-secondary)">
                            ¿No tienes API Key? Google Gemini ofrece un <strong>nivel 100% gratuito</strong> sin tarjeta de crédito.
                        </span>
                        <a href="https://aistudio.google.com/app/apikey" target="_blank" style="font-size:11px;color:#2c60a4;font-weight:700">Crear clave gratis ↗</a>
                    </div>
                </div>

                <div style="margin-top:18px;display:flex;gap:12px">
                    <button type="submit" class="btn btn-primary" style="padding:10px 20px;font-size:13px;background:#2c60a4">
                        Guardar Conexión de IA
                    </button>
                    <button type="button" onclick="probarConexionIA()" class="btn btn-secondary" style="padding:10px 16px;font-size:13px" id="btn-probar-ia">
                        ⚡ Probar Conexión
                    </button>
                </div>
                <div id="res-test-ia" style="margin-top:12px;font-size:12px;display:none"></div>
            </form>
        </div>

        <!-- TARJETA: SIMULADOR INTERACTIVO DE REDACCIÓN -->
        <div class="card" style="background:#f8fafc">
            <h3 style="margin-top:0;font-size:16px;color:#0f172a;display:flex;align-items:center;gap:8px">
                <span>💬</span> Probador / Simulador en Tiempo Real
            </h3>
            <p style="font-size:12px;color:var(--fg-secondary);margin-bottom:14px">
                Prueba cómo redacta la IA de Power Pack consultando el repositorio empresarial:
            </p>

            <div style="display:flex;flex-direction:column;gap:12px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Canal:</label>
                        <select id="sim_canal" style="width:100%;padding:6px 10px;font-size:12px">
                            <option value="whatsapp">📱 WhatsApp</option>
                            <option value="email">✉️ Correo Electrónico</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Skill B2B a Evaluar:</label>
                        <select id="sim_skill" style="width:100%;padding:6px 10px;font-size:12px;font-weight:600">
                            <option value="">— Sin Skill (Modo Estándar) —</option>
                            <?php 
                            $skills_list->reset();
                            while($sk = $skills_list->fetchArray(SQLITE3_ASSOC)): 
                            ?>
                            <option value="<?= h($sk['codigo']) ?>"><?= h($sk['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Objetivo Comercial:</label>
                        <select id="sim_objetivo" style="width:100%;padding:6px 10px;font-size:12px">
                            <option value="seguimiento_feria">🎪 Seguimiento Post-Feria</option>
                            <option value="propuesta">📄 Presentación de Cotización</option>
                            <option value="agendar_visita">🏢 Invitar al Showroom en Bogotá</option>
                            <option value="reactivacion">🧊 Reactivar Cliente Frío</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Instrucción del cliente:</label>
                        <input type="text" id="sim_instrucciones" placeholder="Ej: Cliente interesado en empacar queso al vacío..." value="Interesado en empacadora al vacío de campana para cárnicos" style="width:100%;font-size:12px">
                    </div>
                </div>

                <button type="button" onclick="ejecutarSimulacionIA()" class="btn btn-primary" id="btn-simular-ia" style="background:#2c60a4;padding:8px 16px;font-size:13px;font-weight:700">
                    ⚡ Generar Mensaje Asistido
                </button>

                <!-- RESULTADO DE LA SIMULACIÓN -->
                <div id="sim_resultado_box" style="display:none;background:#fff;border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-top:6px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span id="sim_origen_tag" style="font-size:11px;font-weight:800;color:#2c60a4"></span>
                        <button type="button" onclick="copiarSimulacion()" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:11px">📋 Copiar</button>
                    </div>
                    <div id="sim_asunto_box" style="display:none;font-weight:800;font-size:13px;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #e2e8f0"></div>
                    <div id="sim_mensaje_box" style="font-size:13px;line-height:1.5;white-space:pre-wrap;color:#334155"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- COLUMNA 2: BASE DE CONOCIMIENTO (REPOSITORIO DE LA EMPRESA) -->
    <div class="card" style="display:flex;flex-direction:column">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;border-bottom:1px solid var(--border);padding-bottom:14px">
            <div>
                <h3 style="margin:0;font-size:17px;color:#2c60a4;display:flex;align-items:center;gap:8px">
                    <span>📚</span> Repositorio de la Empresa (Base de Conocimiento)
                </h3>
                <p style="margin:4px 0 0 0;font-size:12px;color:var(--fg-secondary)">
                    Este texto es el "cerebro" que lee la IA para redactar con exactitud técnica sobre la maquinaria y políticas de Power Pack.
                </p>
            </div>
        </div>

        <form method="POST" style="display:flex;flex-direction:column;flex:1">
            <input type="hidden" name="guardar_knowledge_base" value="1">
            
            <div style="margin-bottom:8px;font-size:11px;color:#64748b">
                💡 <em>Puedes agregar nuevos productos, cambiar precios de referencia, actualizar políticas de garantía o incluir nuevas preguntas frecuentes:</em>
            </div>

            <textarea name="ai_knowledge_base" rows="22" style="width:100%;font-family:monospace;font-size:12px;line-height:1.45;padding:14px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fafafa;color:#0f172a;resize:vertical"><?= h($ai_settings['knowledge']) ?></textarea>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px">
                <button type="submit" class="btn btn-primary" style="padding:10px 24px;font-size:13px;font-weight:800;background:#2c60a4">
                    💾 Guardar Repositorio Empresarial
                </button>
            </div>
        </form>

        <form method="POST" onsubmit="return confirm('¿Seguro que deseas restaurar la base de conocimiento original de Power Pack?');" style="margin-top:10px;text-align:right">
            <input type="hidden" name="restaurar_knowledge_base" value="1">
            <button type="submit" style="background:none;border:none;color:#94a3b8;font-size:11px;text-decoration:underline;cursor:pointer">
                Restaurar texto predeterminado de Power Pack
            </button>
        </form>
    </div>

</div>

<!-- ========================================================
     SECCIÓN: CATÁLOGO DE SKILLS COMERCIALES B2B INSTALADAS
     ======================================================== -->
<div style="margin-top:32px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px">
        <div>
            <h2 style="margin:0;font-size:20px;color:#0f172a;display:flex;align-items:center;gap:10px">
                <span>🎯</span> Skills Comerciales B2B Instaladas en Power Pack
            </h2>
            <p style="margin:4px 0 0 0;font-size:13px;color:var(--fg-secondary)">
                Habilidades y marcos metodológicos de venta consultiva (AIDA, PAS, BAB, Magic Email) que dotan a la IA del criterio experto para redactar correos de alta conversión y mensajes ágiles de WhatsApp.
            </p>
        </div>
        <button onclick="document.getElementById('modalNuevaSkill').style.display='flex'" class="btn btn-primary btn-sm" style="background:#2c60a4;font-weight:700">
            ➕ Nueva Skill B2B
        </button>
    </div>

    <!-- GRID DE SKILLS -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:20px">
        <?php 
        $skills_list->reset();
        while($sk = $skills_list->fetchArray(SQLITE3_ASSOC)): 
            $canal_badge_cls = ($sk['canal'] === 'whatsapp') ? 'badge-calificado' : (($sk['canal'] === 'email') ? 'badge-contacto_inicial' : 'badge-lead');
            $canal_label = ($sk['canal'] === 'whatsapp') ? '📱 WhatsApp' : (($sk['canal'] === 'email') ? '✉️ Correo Electrónico' : '🌐 WhatsApp & Correo');
        ?>
        <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,0.04);transition:transform 0.2s ease">
            <div>
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
                    <span class="badge <?= $canal_badge_cls ?>" style="font-size:11px">
                        <?= $canal_label ?>
                    </span>
                    <code style="font-size:11px;background:#f1f5f9;color:#2c60a4;padding:2px 6px;border-radius:4px"><?= h($sk['codigo']) ?></code>
                </div>

                <h3 style="margin:0 0 8px 0;font-size:15px;color:#0f172a;line-height:1.35">
                    <?= h($sk['nombre']) ?>
                </h3>

                <div style="font-size:12px;color:#475569;line-height:1.5;margin-bottom:12px">
                    <strong style="color:#0f172a">Función Comercial:</strong> <?= h($sk['descripcion']) ?>
                </div>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px;font-size:11px;color:#334155;line-height:1.45;margin-bottom:12px">
                    <strong style="color:#2c60a4">🧠 Marco Metodológico (Prompt):</strong><br>
                    <?= nl2br(h($sk['framework_prompt'])) ?>
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #f1f5f9;padding-top:12px;margin-top:6px">
                <button type="button" onclick="probarSkillDirecto('<?= $sk['canal']==='email'?'email':'whatsapp' ?>', '<?= h($sk['codigo']) ?>')" class="btn btn-secondary btn-sm" style="font-size:12px;font-weight:700">
                    ⚡ Probar en Simulador
                </button>
                <form method="POST" style="margin:0" onsubmit="return confirm('¿Eliminar esta skill?');">
                    <input type="hidden" name="eliminar_skill" value="1">
                    <input type="hidden" name="skill_id" value="<?= (int)$sk['id'] ?>">
                    <button type="submit" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:12px" title="Eliminar skill">🗑️</button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- MODAL NUEVA SKILL B2B -->
<div id="modalNuevaSkill" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.65);z-index:9999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px)">
    <div style="background:#fff;border-radius:12px;max-width:580px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);overflow:hidden">
        <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);padding:18px 24px;border-bottom:3px solid #2c60a4;display:flex;justify-content:space-between;align-items:center">
            <h3 style="color:#fff;margin:0;font-size:16px;font-weight:800">➕ Crear Nueva Skill Comercial B2B</h3>
            <button onclick="document.getElementById('modalNuevaSkill').style.display='none'" style="background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer">&times;</button>
        </div>

        <form method="POST" style="padding:22px;display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="crear_skill" value="1">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <label style="font-size:12px;font-weight:700">Código Único (slug):</label>
                    <input type="text" name="codigo" required placeholder="ej: cierre_urgencia" style="width:100%;font-size:13px;margin-top:4px">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:700">Canal:</label>
                    <select name="canal" style="width:100%;font-size:13px;padding:8px;margin-top:4px">
                        <option value="ambos">Ambos (WhatsApp & Correo)</option>
                        <option value="whatsapp">Solo WhatsApp</option>
                        <option value="email">Solo Correo Electrónico</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="font-size:12px;font-weight:700">Nombre de la Skill:</label>
                <input type="text" name="nombre" required placeholder="ej: 🚀 Fórmula Cierre: Descuento por Volumen" style="width:100%;font-size:13px;margin-top:4px">
            </div>

            <div>
                <label style="font-size:12px;font-weight:700">Función Comercial (¿Qué logra y cuándo usarla?):</label>
                <textarea name="descripcion" rows="2" required placeholder="Explica el objetivo de venta de esta habilidad..." style="width:100%;font-size:12px;margin-top:4px"></textarea>
            </div>

            <div>
                <label style="font-size:12px;font-weight:700">Marco Metodológico / Prompt Directivo para la IA:</label>
                <textarea name="framework_prompt" rows="4" required placeholder="Instruye a la IA sobre la estructura persuasiva a seguir..." style="width:100%;font-size:12px;margin-top:4px"></textarea>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px">
                <button type="button" onclick="document.getElementById('modalNuevaSkill').style.display='none'" class="btn btn-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm" style="background:#2c60a4;font-weight:800;padding:8px 20px">Guardar e Instalar Skill</button>
            </div>
        </form>
    </div>
</div>

<script>
function actualizarModelos() {
    var prov = document.getElementById('ai_provider').value;
    var modelSelect = document.getElementById('ai_model');
    modelSelect.innerHTML = '';
    
    if (prov === 'gemini') {
        modelSelect.innerHTML += '<option value="gemini-2.0-flash">Gemini 2.0 Flash (Recomendado - Rápido y moderno)</option>';
        modelSelect.innerHTML += '<option value="gemini-1.5-flash">Gemini 1.5 Flash</option>';
        modelSelect.innerHTML += '<option value="gemini-1.5-pro">Gemini 1.5 Pro</option>';
    } else {
        modelSelect.innerHTML += '<option value="gpt-4o-mini">GPT-4o Mini (Recomendado por precio/velocidad)</option>';
        modelSelect.innerHTML += '<option value="gpt-4o">GPT-4o</option>';
    }
}

function probarConexionIA() {
    var btn = document.getElementById('btn-probar-ia');
    var resDiv = document.getElementById('res-test-ia');
    btn.disabled = true;
    btn.innerText = 'Probando...';
    resDiv.style.display = 'block';
    resDiv.className = 'alert alert-info';
    resDiv.innerText = 'Enviando prueba de conexión con la IA...';

    fetch('api.php?action=generar_ia', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            canal: 'whatsapp',
            objetivo: 'seguimiento_feria',
            instrucciones: 'Prueba de diagnóstico de conexión',
            prospecto_simulado: {
                nombre: 'Cliente de Prueba',
                empresa: 'Industria Piloto',
                fuente: 'Diagnóstico'
            }
        })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = '⚡ Probar Conexión';
        if (data.ok) {
            resDiv.className = 'alert alert-success';
            resDiv.innerHTML = '<strong>✅ ¡Conexión exitosa!</strong> Respondido por: <em>' + (data.origen || 'IA Power Pack') + '</em>';
        } else {
            resDiv.className = 'alert alert-danger';
            resDiv.innerText = '⚠️ Error: ' + (data.error || 'No se pudo conectar.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = '⚡ Probar Conexión';
        resDiv.className = 'alert alert-danger';
        resDiv.innerText = 'Error de red al consultar la API: ' + err.message;
    });
}

function ejecutarSimulacionIA() {
    var btn = document.getElementById('btn-simular-ia');
    var canal = document.getElementById('sim_canal').value;
    var objetivo = document.getElementById('sim_objetivo').value;
    var skill = document.getElementById('sim_skill') ? document.getElementById('sim_skill').value : '';
    var inst = document.getElementById('sim_instrucciones').value;
    var box = document.getElementById('sim_resultado_box');
    var tag = document.getElementById('sim_origen_tag');
    var asuntoBox = document.getElementById('sim_asunto_box');
    var msgBox = document.getElementById('sim_mensaje_box');

    btn.disabled = true;
    btn.innerText = '⚡ Generando con Skill B2B...';

    fetch('api.php?action=generar_ia', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            canal: canal,
            objetivo: objetivo,
            skill: skill,
            instrucciones: inst,
            prospecto_simulado: {
                nombre: 'María Camila Ruiz',
                empresa: 'Lácteos & Quesos Andinos',
                ciudad: 'Medellín',
                fuente: 'Feria Andina Pack',
                notas: inst
            }
        })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = '⚡ Generar Mensaje Asistido';
        box.style.display = 'block';
        tag.innerText = 'Generado con: ' + (data.origen || 'Power Pack AI');

        if (canal === 'email' && data.asunto) {
            asuntoBox.style.display = 'block';
            asuntoBox.innerHTML = '<strong>Asunto:</strong> ' + data.asunto;
            msgBox.innerHTML = data.mensaje;
        } else {
            asuntoBox.style.display = 'none';
            msgBox.innerText = data.mensaje;
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = '⚡ Generar Mensaje Asistido';
        alert('Error al simular: ' + err.message);
    });
}

function probarSkillDirecto(canal, codigo) {
    if (document.getElementById('sim_canal')) {
        document.getElementById('sim_canal').value = canal;
    }
    if (document.getElementById('sim_skill')) {
        document.getElementById('sim_skill').value = codigo;
    }
    // Auto-ajustar objetivo según skill
    if (document.getElementById('sim_objetivo')) {
        if (codigo === 'aida_feria') document.getElementById('sim_objetivo').value = 'seguimiento_feria';
        if (codigo === 'reactivacion_fria') document.getElementById('sim_objetivo').value = 'reactivacion';
        if (codigo === 'invitacion_showroom') document.getElementById('sim_objetivo').value = 'agendar_visita';
    }
    window.scrollTo({ top: 350, behavior: 'smooth' });
    ejecutarSimulacionIA();
}

function copiarSimulacion() {
    var msg = document.getElementById('sim_mensaje_box').innerText;
    navigator.clipboard.writeText(msg).then(() => {
        alert('¡Mensaje copiado al portapapeles!');
    });
}
</script>
