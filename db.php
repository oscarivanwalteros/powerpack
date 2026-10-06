<?php
// Base de datos SQLite - se crea y actualiza automáticamente
$db = new SQLite3(__DIR__ . '/powerpack.db');
$db->exec("PRAGMA journal_mode=WAL");

// 1. Tablas principales
$db->exec("CREATE TABLE IF NOT EXISTS empresas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    nit TEXT,
    sector TEXT,
    ciudad TEXT,
    direccion TEXT,
    telefono TEXT,
    email TEXT,
    website TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS contactos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empresa_id INTEGER,
    nombre TEXT NOT NULL,
    apellido TEXT,
    email TEXT UNIQUE,
    telefono TEXT,
    empresa TEXT,
    cargo TEXT,
    ciudad TEXT,
    pais TEXT DEFAULT 'Colombia',
    sector TEXT,
    fuente TEXT DEFAULT 'feria',
    etapa TEXT DEFAULT 'lead',
    interes INTEGER DEFAULT 1,
    notas TEXT,
    website TEXT,
    direccion TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_actividad DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS actividades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contacto_id INTEGER,
    empresa_id INTEGER,
    tipo TEXT, -- 'nota', 'llamada', 'email', 'whatsapp', 'reunion', 'tarea'
    asunto TEXT,
    descripcion TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATETIME,
    completada INTEGER DEFAULT 1,
    resultado TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS negocios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contacto_id INTEGER,
    empresa_id INTEGER,
    nombre TEXT,
    monto REAL DEFAULT 0,
    etapa TEXT DEFAULT 'contacto_inicial',
    fecha_cierre DATE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_actividad DATETIME DEFAULT CURRENT_TIMESTAMP,
    probabilidad INTEGER DEFAULT 20,
    descripcion TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS productos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    codigo TEXT UNIQUE,
    precio REAL DEFAULT 0,
    categoria TEXT,
    descripcion TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS cotizaciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero TEXT UNIQUE,
    contacto_id INTEGER,
    empresa_id INTEGER,
    negocio_id INTEGER,
    fecha DATE DEFAULT (DATE('now')),
    validez_dias INTEGER DEFAULT 15,
    subtotal REAL DEFAULT 0,
    iva_porcentaje REAL DEFAULT 19,
    iva_monto REAL DEFAULT 0,
    total REAL DEFAULT 0,
    condiciones TEXT,
    tiempo_entrega TEXT,
    garantia TEXT,
    estado TEXT DEFAULT 'borrador', -- 'borrador', 'enviada', 'aprobada', 'rechazada'
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS cotizacion_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cotizacion_id INTEGER NOT NULL,
    producto_id INTEGER,
    descripcion TEXT NOT NULL,
    cantidad REAL DEFAULT 1,
    precio_unitario REAL DEFAULT 0,
    subtotal REAL DEFAULT 0
)");

$db->exec("CREATE TABLE IF NOT EXISTS archivos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contacto_id INTEGER,
    empresa_id INTEGER,
    negocio_id INTEGER,
    nombre_original TEXT NOT NULL,
    ruta TEXT NOT NULL,
    peso INTEGER,
    mime TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS plantillas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo TEXT NOT NULL,
    titulo TEXT NOT NULL,
    asunto TEXT,
    cuerpo TEXT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS configuracion (
    clave TEXT PRIMARY KEY,
    valor TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    rol TEXT DEFAULT 'asesor', -- 'admin', 'asesor'
    activo INTEGER DEFAULT 1,
    ultimo_acceso DATETIME,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS skills_ia (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo TEXT UNIQUE NOT NULL,
    nombre TEXT NOT NULL,
    canal TEXT DEFAULT 'ambos', -- 'email', 'whatsapp', 'ambos'
    categoria TEXT DEFAULT 'b2b_ventas',
    descripcion TEXT NOT NULL,
    framework_prompt TEXT NOT NULL,
    ejemplo_salida TEXT,
    activo INTEGER DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Migraciones seguras para bases de datos existentes
function agregar_columna_si_falta($db, $tabla, $columna, $tipo) {
    $res = $db->query("PRAGMA table_info($tabla)");
    $existe = false;
    while ($col = $res->fetchArray(SQLITE3_ASSOC)) {
        if ($col['name'] === $columna) {
            $existe = true;
            break;
        }
    }
    if (!$existe) {
        @$db->exec("ALTER TABLE $tabla ADD COLUMN $columna $tipo");
    }
}

agregar_columna_si_falta($db, 'contactos', 'empresa_id', 'INTEGER');
agregar_columna_si_falta($db, 'contactos', 'website', 'TEXT');
agregar_columna_si_falta($db, 'contactos', 'direccion', 'TEXT');
agregar_columna_si_falta($db, 'negocios', 'empresa_id', 'INTEGER');
agregar_columna_si_falta($db, 'negocios', 'ultima_actividad', 'DATETIME');
agregar_columna_si_falta($db, 'actividades', 'empresa_id', 'INTEGER');
agregar_columna_si_falta($db, 'actividades', 'fecha_vencimiento', 'DATETIME');
agregar_columna_si_falta($db, 'actividades', 'completada', 'INTEGER DEFAULT 1');

// Helpers de Configuración
function get_config($db, $clave, $default = '') {
    $stmt = $db->prepare("SELECT valor FROM configuracion WHERE clave = ?");
    $stmt->bindValue(1, $clave, SQLITE3_TEXT);
    $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return $res ? $res['valor'] : $default;
}

function set_config($db, $clave, $valor) {
    $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON CONFLICT(clave) DO UPDATE SET valor = excluded.valor");
    $stmt->bindValue(1, $clave, SQLITE3_TEXT);
    $stmt->bindValue(2, $valor, SQLITE3_TEXT);
    $stmt->execute();
}

// Inicializar configuración por defecto
if ($db->querySingle("SELECT COUNT(*) FROM configuracion") == 0) {
    set_config($db, 'empresa_nombre', 'Power Pack');
    set_config($db, 'empresa_nit', '901.452.889-1');
    set_config($db, 'empresa_email', 'administrador@powerpack.site');
    set_config($db, 'empresa_website', 'https://powerpack.site');
    set_config($db, 'empresa_direccion', 'Calle 161 # 54 - 25, Bogotá, Colombia');
    set_config($db, 'smtp_host', 'smtp.hostinger.com');
    set_config($db, 'smtp_port', '465');
    set_config($db, 'smtp_secure', 'ssl');
    set_config($db, 'smtp_user', 'administrador@powerpack.site');
    set_config($db, 'smtp_pass', 'PowerPack2026*');
    set_config($db, 'smtp_from', 'administrador@powerpack.site');
    set_config($db, 'banco_info', 'Bancolombia Cta Corriente # 104-582910-44 a nombre de Power Pack');
}

// Asegurar que el usuario administrador oficial administrador@powerpack.site exista siempre y esté activo
$admin_existe = $db->querySingle("SELECT id FROM usuarios WHERE LOWER(email) = 'administrador@powerpack.site' LIMIT 1");
if (!$admin_existe) {
    // Si existe el antiguo admin@powerpack.com.co o cualquier otro admin previo, actualizarlo
    $old_admin = $db->querySingle("SELECT id FROM usuarios WHERE LOWER(email) = 'admin@powerpack.com.co' OR rol = 'admin' LIMIT 1");
    if ($old_admin) {
        $stmt_up = $db->prepare("UPDATE usuarios SET nombre = 'Administrador Power Pack', email = 'administrador@powerpack.site', password_hash = ?, rol = 'admin', activo = 1 WHERE id = ?");
        $stmt_up->bindValue(1, password_hash('PowerPack2026*', PASSWORD_DEFAULT), SQLITE3_TEXT);
        $stmt_up->bindValue(2, $old_admin, SQLITE3_INTEGER);
        $stmt_up->execute();
    } else {
        $stmt_admin = $db->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol, activo, fecha_creacion) VALUES ('Administrador Power Pack', 'administrador@powerpack.site', ?, 'admin', 1, datetime('now'))");
        $stmt_admin->bindValue(1, password_hash('PowerPack2026*', PASSWORD_DEFAULT), SQLITE3_TEXT);
        $stmt_admin->execute();
    }
} else {
    // Asegurar que esté activo
    $db->exec("UPDATE usuarios SET activo = 1 WHERE id = $admin_existe");
}

// Auto-migración: actualizar modelo de Google Gemini si tiene el anterior gemini-2.0-flash deprecado
$current_ai_model = get_config($db, 'ai_model', '');
if ($current_ai_model === 'gemini-2.0-flash' || empty($current_ai_model)) {
    set_config($db, 'ai_model', 'gemini-3.8-flash');
}

// Catálogo de Productos inicial (Maquinaria y Soluciones de Empaque)
if ($db->querySingle("SELECT COUNT(*) FROM productos") == 0) {
    $db->exec("INSERT INTO productos (nombre, codigo, precio, categoria, descripcion) VALUES
        ('Paletizador Automático Industrial X4', 'PAL-X4', 85000, 'Paletizado', 'Línea de alta velocidad con capacidad de hasta 40 paquetes/minuto. Diagnóstico remoto Siemens.'),
        ('Línea Automática de Encartonado EC-200', 'ENC-200', 45000, 'Encartonado', 'Formadora y cerradora de cajas de cartón corrugado con adhesivo hot-melt.'),
        ('Transportador Modular de Rodillos Motorizados (10m)', 'TR-ROD-10', 12500, 'Transportadores', 'Estructura en acero inoxidable 304, variadores de frecuencia y sensores fotocélula.'),
        ('Envolvedora Automática de Palets con Film Estirable', 'ENV-PAL-50', 28000, 'Embalaje', 'Mesa giratoria con pre-estirado al 300%. Cortado y pegado automático de film.'),
        ('Póliza Anual de Mantenimiento Preventivo & Repuestos', 'SER-MANT-ANUAL', 8500, 'Servicios', '4 visitas técnicas anuales, soporte remoto 24/7 y 15% de descuento en piezas de desgaste.')
    ");
}

// Empresas de ejemplo iniciales (Cuentas B2B)
if ($db->querySingle("SELECT COUNT(*) FROM empresas") == 0) {
    $db->exec("INSERT INTO empresas (nombre, nit, sector, ciudad, direccion, telefono, email, website) VALUES
        ('Alimentos ABC SAS', '860.123.456-7', 'alimentos', 'Bogotá', 'Zona Industrial Montevideo', '+57 1 450 7800', 'compras@alimentosabc.com', 'https://alimentosabc.com'),
        ('Industrias Farmacéuticas XYZ', '900.876.543-2', 'farmacéutica', 'Medellín', 'Parque Industrial Guayabal', '+57 4 320 9000', 'planta@industriesxyz.com', 'https://industriesxyz.com'),
        ('Logística & Distribución Delta', '901.333.222-1', 'logística', 'Bogotá', 'Parque Logístico Celta, Funza', '+57 1 820 4400', 'contacto@logisticadelta.com', 'https://logisticadelta.com')
    ");
    $db->exec("UPDATE contactos SET empresa_id = 1 WHERE empresa LIKE '%Alimentos ABC%'");
    $db->exec("UPDATE contactos SET empresa_id = 2 WHERE empresa LIKE '%XYZ%'");
    $db->exec("UPDATE contactos SET empresa_id = 3 WHERE empresa LIKE '%Delta%'");
    $db->exec("UPDATE negocios SET empresa_id = 1 WHERE contacto_id = 1");
    $db->exec("UPDATE negocios SET empresa_id = 2 WHERE contacto_id = 2");
    $db->exec("UPDATE negocios SET empresa_id = 3 WHERE contacto_id = 3");
}

// Plantillas por defecto
if ($db->querySingle("SELECT COUNT(*) FROM plantillas") == 0) {
    $db->exec("INSERT INTO plantillas (tipo, titulo, asunto, cuerpo) VALUES
        ('whatsapp', '1. Saludo inicial y presentación', '', '¡Hola {nombre}! Un gusto saludarte. Te escribe {empresa_nombre}. Vimos el excelente trabajo que hacen en {empresa}. ¿Cómo tienen organizado actualmente su proceso de empaque y logística?'),
        ('whatsapp', '2. Compartir catálogo técnico', '', 'Hola {nombre}, como te comenté, te comparto el catálogo con nuestras soluciones de maquinaria y paletización pensadas para optimizar tiempos en {empresa}. ¿Te gustaría que coordinemos una breve demostración?'),
        ('whatsapp', '3. Cotización formal lista', '', 'Hola {nombre}, te comparto que ya tenemos lista tu cotización formal para {empresa}. Puedes revisarla en el enlace o descargar el PDF formal. ¿Tienes 5 minutos hoy para resolver dudas?'),
        ('whatsapp', '4. Seguimiento comercial', '', 'Hola {nombre}, ¿cómo estás? Quería hacer un breve seguimiento para saber si lograste revisar la propuesta para {empresa} o si tienes alguna inquietud que podamos resolver.'),
        ('email', 'Propuesta Comercial y Ficha Técnica', 'Propuesta de optimización para {empresa}', '<p>Estimado/a <strong>{nombre}</strong>,</p><p>Es un placer saludarte de parte de <strong>{empresa_nombre}</strong>.</p><p>Analizando las necesidades de <strong>{empresa}</strong> en el sector {sector}, hemos preparado una propuesta especializada que permite automatizar y optimizar los tiempos de producción y despacho hasta en un 35%.</p><p>Quedamos atentos a tus comentarios para coordinar una breve reunión técnica o llamada de seguimiento.</p><p>Cordialmente,<br><strong>Equipo Comercial</strong><br>{empresa_nombre}<br>{empresa_telefono}</p>')
    ");
}

// Skills de IA Especializadas en Ventas B2B
if ($db->querySingle("SELECT COUNT(*) FROM skills_ia") == 0) {
    $stmt_skill = $db->prepare("INSERT INTO skills_ia (codigo, nombre, canal, categoria, descripcion, framework_prompt, ejemplo_salida, activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    
    $skills_seed = [
        [
            'aida_feria',
            '🎯 Fórmula AIDA: Seguimiento Post-Feria y Eventos B2B',
            'ambos',
            'b2b_ventas',
            'Convierte contactos de ferias industriales (Andina Pack, Alimentec, etc.) en cotizaciones. Capta Atención recordando el stand, despierta Interés en reducir mermas, genera Deseo con garantía de 12 meses y stock en Bogotá, y mueve a la Acción con una llamada de 10 min.',
            'Aplica la metodología AIDA para venta consultiva industrial B2B. 1) Atención: Menciona con calidez que se conocieron en la feria comercial o evento. 2) Interés: Demuestra entendimiento de sus procesos de empaque o dosificado y la necesidad de aumentar rendimiento. 3) Deseo: Presenta a Power Pack como aliado con máquinas en acero inoxidable, bombas de vacío de alto rendimiento y stock de entrega inmediata con garantía de 1 año. 4) Acción: Invita a coordinar una videollamada de 10 min o solicita confirmación para enviar la cotización formal.',
            "Hola {nombre}, un gusto saludarte. Recordando la conversación en la feria sobre la línea de producción de {empresa}..."
        ],
        [
            'pas_problema',
            '⚡ Fórmula PAS: Problema, Agitación y Solución Industrial',
            'ambos',
            'b2b_ventas',
            'Para prospectos con procesos manuales lentos o fallas en empaque. Identifica el Problema (mermas, sellos defectuosos, lentitud), Agita el costo oculto de paradas o quejas, y ofrece la Solución exacta de Power Pack con rápido retorno de inversión.',
            'Aplica el marco PAS (Problem - Agitate - Solution) para directores de planta y compras. 1) Problema: Señala los cuellos de botella habituales en empaque manual o maquinaria descalibrada. 2) Agitación: Expone las mermas económicas y retrasos de entrega que esto causa. 3) Solución: Presenta el equipo de Power Pack como la solución definitiva con precisión milimétrica, repuestos inmediatos y soporte técnico en Colombia.',
            "Estimado {nombre}, muchas empresas del sector alimenticio pierden hasta un 12% en mermas por empaques defectuosos..."
        ],
        [
            'bab_transformacion',
            '🏭 Fórmula BAB: Transformación y Eficiencia (Before - After - Bridge)',
            'ambos',
            'b2b_ventas',
            'Contrasta el Antes (planta operando con fricción y lentitud) con el Después (planta moderna empacando al triple de velocidad con acabado hermético), posicionando a Power Pack como el Puente tecnológico de confianza.',
            'Aplica el marco BAB (Before - After - Bridge). 1) Before: Describe la fricción de procesos semi-manuales con altos tiempos por lote. 2) After: Proyecta la planta trabajando al triple de velocidad con acabado hermético y fecha de vencimiento clara. 3) Bridge: Muestra la maquinaria Power Pack con inducción a operarios y facilidades de pago como el puente directo a esa transformación.',
            "¿Cómo sería triplicar la velocidad de sellado en {empresa} sin incrementar horas extra operativas?..."
        ],
        [
            'reactivacion_fria',
            '🧊 Magic Email: Reactivación de Prospectos Inactivos (No Responde)',
            'ambos',
            'b2b_ventas',
            'Para cotizaciones congeladas o clientes que dejaron de responder. Usa psicología de cortesía profesional para reabrir la conversación o confirmar con elegancia si se archiva el proyecto.',
            'Aplica la técnica de reactivación cordial de ciclo comercial. El tono debe ser 100% respetuoso y profesional, sin sonar insistente. Pregunta de forma directa y sincera si sus prioridades en la línea de empaque cambiaron, o si prefieren que archivemos la propuesta por el momento para no saturar su bandeja. Incluye una breve nota de que los precios o cupos de entrega inmediata se mantienen reservados por cortesía hasta fin de mes.',
            "Hola {nombre}, te escribo brevemente. Imagino que están con mucha carga en planta. ¿Aún sigue en pie el proyecto de empaque para {empresa} o prefieres que pausemos la propuesta?..."
        ],
        [
            'invitacion_showroom',
            '🏢 Invitación VIP a Showroom Bogotá: Pruebas con Muestras Reales',
            'ambos',
            'b2b_ventas',
            'Estrategia de alta conversión para clientes exigentes. Los invita al Showroom de Power Pack en Bogotá (Calle 161 # 54 - 25) a probar la máquina en vivo con su producto real sin costo alguno.',
            'El objetivo es cerrar una cita presencial o envío de muestras al Showroom de Power Pack en Bogotá (Calle 161 # 54 - 25). Explica que la mejor garantía es ver la máquina sellando, empacando al vacío o dosificando con su producto real. Es una prueba técnica sin compromiso guiada por nuestros ingenieros para validar velocidad y presentación.',
            "Estimado {nombre}, queremos invitarte a nuestro Showroom en Bogotá para hacer pruebas reales con tus muestras..."
        ],
        [
            'flash_whatsapp',
            '📱 WhatsApp Flash B2B: Mensaje Ejecutivo Ultraligero (Directo al Grano)',
            'whatsapp',
            'b2b_ventas',
            'Mensaje quirúrgico de máximo 3 o 4 párrafos cortos para WhatsApp. Diseñado para gerentes ocupados que revisan el celular entre reuniones. Con formato nítido y 1 sola pregunta concreta.',
            'Genera un mensaje de WhatsApp móvil ultra-directo. No exceder 75 palabras. Saludo formal con nombre de pila, 2 frases de valor comercial sobre la máquina ideal para su empresa, mención rápida de stock para entrega inmediata en Colombia con garantía de 1 año, y un llamado a la acción simple que se responda con Sí o No. Usa asteriscos para negritas.',
            "¡Hola {nombre}! 👋 Te saluda Power Pack. Vimos su producción en {empresa} y tenemos selladoras al vacío en stock en Bogotá con 1 año de garantía. ¿Te queda bien que te comparta la ficha técnica en PDF por aquí?"
        ]
    ];

    foreach ($skills_seed as $s) {
        $stmt_skill->bindValue(1, $s[0], SQLITE3_TEXT);
        $stmt_skill->bindValue(2, $s[1], SQLITE3_TEXT);
        $stmt_skill->bindValue(3, $s[2], SQLITE3_TEXT);
        $stmt_skill->bindValue(4, $s[3], SQLITE3_TEXT);
        $stmt_skill->bindValue(5, $s[4], SQLITE3_TEXT);
        $stmt_skill->bindValue(6, $s[5], SQLITE3_TEXT);
        $stmt_skill->bindValue(7, $s[6], SQLITE3_TEXT);
        $stmt_skill->execute();
    }
}

// Helpers de formato y utilidad
function h($str) { 
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); 
}

function etapa_badge($etapa) {
    $clases = [
        'lead' => 'badge-lead',
        'contacto_inicial' => 'badge-contacto_inicial',
        'calificado' => 'badge-calificado',
        'cotizacion' => 'badge-cotizacion',
        'negociacion' => 'badge-negociacion',
        'ganado' => 'badge-ganado',
        'perdido' => 'badge-perdido',
    ];
    $iconos = [
        'lead' => '🎯',
        'contacto_inicial' => '📞',
        'calificado' => '⭐',
        'cotizacion' => '📄',
        'negociacion' => '🤝',
        'ganado' => '🏆',
        'perdido' => '❌',
    ];
    $cls = $clases[$etapa] ?? 'badge-lead';
    $ico = $iconos[$etapa] ?? '•';
    $nombre = str_replace('_', ' ', $etapa);
    return "<span class='badge $cls'>$ico " . ucwords($nombre) . "</span>";
}

function estrellas($n) {
    $s = '<span class="stars" title="' . (int)$n . ' de 5 estrellas">';
    for ($i = 0; $i < 5; $i++) {
        $s .= $i < $n ? '★' : '☆';
    }
    $s .= '</span>';
    return $s;
}

function tipo_actividad_icono($tipo) {
    switch ($tipo) {
        case 'whatsapp': return '<span class="act-icon act-whatsapp">💬</span>';
        case 'email':    return '<span class="act-icon act-email">✉️</span>';
        case 'llamada':  return '<span class="act-icon act-llamada">📞</span>';
        case 'reunion':  return '<span class="act-icon act-reunion">🤝</span>';
        case 'tarea':    return '<span class="act-icon act-tarea">✅</span>';
        case 'cotizacion':return '<span class="act-icon act-cotizacion">📄</span>';
        case 'nota':     return '<span class="act-icon act-nota">📝</span>';
        default:         return '<span class="act-icon act-default">📌</span>';
    }
}

function limpiar_telefono_whatsapp($tel, $codigo_pais = '57') {
    $limpio = preg_replace('/[^0-9]/', '', $tel ?? '');
    if (empty($limpio)) return '';
    if (strpos($limpio, '0') === 0) {
        $limpio = substr($limpio, 1);
    }
    if (strlen($limpio) === 10) {
        $limpio = $codigo_pais . $limpio;
    }
    return $limpio;
}

function avatar_iniciales($nombre, $apellido = '') {
    $i1 = mb_strtoupper(mb_substr(trim($nombre), 0, 1, 'UTF-8'));
    $i2 = mb_strtoupper(mb_substr(trim($apellido), 0, 1, 'UTF-8'));
    $colores = ['#f97316', '#2563eb', '#7c3aed', '#059669', '#0891b2', '#ea580c', '#4f46e5'];
    $idx = (ord($i1) + ord($i2 ?: 'A')) % count($colores);
    $bg = $colores[$idx];
    return "<div class='avatar-circle' style='background:$bg'>" . $i1 . $i2 . "</div>";
}

function avatar_empresa($nombre) {
    $i1 = mb_strtoupper(mb_substr(trim($nombre), 0, 2, 'UTF-8'));
    return "<div class='avatar-circle' style='background:#334155;border-radius:8px'>" . $i1 . "</div>";
}

function reemplazar_variables($plantilla, $contacto, $config = []) {
    $vars = [
        '{nombre}'         => $contacto['nombre'] ?? '',
        '{apellido}'       => $contacto['apellido'] ?? '',
        '{nombre_completo}'=> trim(($contacto['nombre'] ?? '') . ' ' . ($contacto['apellido'] ?? '')),
        '{empresa}'        => $contacto['empresa'] ?? 'su empresa',
        '{cargo}'          => $contacto['cargo'] ?? 'responsable',
        '{sector}'         => $contacto['sector'] ?? 'industrial',
        '{ciudad}'         => $contacto['ciudad'] ?? '',
        '{email}'          => $contacto['email'] ?? '',
        '{telefono}'       => $contacto['telefono'] ?? '',
        '{empresa_nombre}' => $config['empresa_nombre'] ?? 'Powerpack Solutions SAS',
        '{empresa_telefono}'=> $config['empresa_telefono'] ?? '+57 300 467 0474',
        '{empresa_email}'  => $config['empresa_email'] ?? 'ventas@powerpack.com.co',
    ];
    return str_replace(array_keys($vars), array_values($vars), $plantilla);
}

// Calcular días de inactividad de un negocio (Semáforo de Abandono estilo Pipedrive)
function calcular_dias_inactividad($fecha_ultima) {
    if (!$fecha_ultima) return 999;
    $diff = time() - strtotime($fecha_ultima);
    return max(0, (int)floor($diff / 86400));
}

$pipeline_etapas = [
    ['id' => 'lead', 'nombre' => 'Lead Nuevo', 'color' => '#64748b', 'prob' => 10],
    ['id' => 'contacto_inicial', 'nombre' => 'Contacto Inicial', 'color' => '#3b82f6', 'prob' => 20],
    ['id' => 'calificado', 'nombre' => 'Calificado / Interesado', 'color' => '#8b5cf6', 'prob' => 40],
    ['id' => 'cotizacion', 'nombre' => 'Cotización Presentada', 'color' => '#f59e0b', 'prob' => 60],
    ['id' => 'negociacion', 'nombre' => 'En Negociación', 'color' => '#f97316', 'prob' => 80],
    ['id' => 'ganado', 'nombre' => 'Cerrado Ganado', 'color' => '#059669', 'prob' => 100],
    ['id' => 'perdido', 'nombre' => 'Cerrado Perdido', 'color' => '#ef4444', 'prob' => 0],
];