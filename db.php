<?php
// Polyfills seguros para funciones mb_* si la extensión mbstring no está instalada
if (!function_exists('mb_strlen')) {
    function mb_strlen($str, $encoding = 'UTF-8') {
        return strlen((string)$str);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = null, $encoding = 'UTF-8') {
        if ($length === null) return substr((string)$str, $start);
        return substr((string)$str, $start, $length);
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($str, $encoding = 'UTF-8') {
        return strtoupper((string)$str);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($str, $encoding = 'UTF-8') {
        return strtolower((string)$str);
    }
}
if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding($str, $to_encoding, $from_encoding = '') {
        if (function_exists('iconv')) {
            $from = is_array($from_encoding) ? implode(',', $from_encoding) : $from_encoding;
            $res = @iconv($from ?: 'UTF-8', $to_encoding . '//IGNORE', (string)$str);
            if ($res !== false) return $res;
        }
        return (string)$str;
    }
}

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

$db->exec("CREATE TABLE IF NOT EXISTS documentos_ia (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo TEXT NOT NULL,
    categoria TEXT DEFAULT 'general', -- 'precios', 'inventario', 'politicas', 'catalogo', 'correos', 'general'
    nombre_archivo TEXT NOT NULL,
    ruta_archivo TEXT NOT NULL,
    tipo_mime TEXT,
    tamano INTEGER DEFAULT 0,
    texto_extraido TEXT NOT NULL,
    activo INTEGER DEFAULT 1,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Asegurar que la carpeta de almacenamiento de conocimiento exista con permisos seguros
if (!is_dir(__DIR__ . '/uploads/conocimiento')) {
    @mkdir(__DIR__ . '/uploads/conocimiento', 0777, true);
}

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
agregar_columna_si_falta($db, 'contactos', 'prioridad', "TEXT DEFAULT 'media'");
agregar_columna_si_falta($db, 'negocios', 'empresa_id', 'INTEGER');
agregar_columna_si_falta($db, 'negocios', 'ultima_actividad', 'DATETIME');
agregar_columna_si_falta($db, 'actividades', 'empresa_id', 'INTEGER');
agregar_columna_si_falta($db, 'actividades', 'fecha_vencimiento', 'DATETIME');
agregar_columna_si_falta($db, 'actividades', 'completada', 'INTEGER DEFAULT 1');
agregar_columna_si_falta($db, 'cotizaciones', 'asunto', 'TEXT');
agregar_columna_si_falta($db, 'cotizaciones', 'carta_presentacion', 'TEXT');
agregar_columna_si_falta($db, 'cotizaciones', 'incluye_instalacion', "TEXT DEFAULT 'Incluye servicio de instalación técnica y capacitación operativa en planta'");

// Inicializar prioridades para contactos existentes según su interés comercial si no tienen
@$db->exec("UPDATE contactos SET prioridad = 'alta' WHERE (prioridad IS NULL OR prioridad = '' OR prioridad = 'media') AND interes >= 4");
@$db->exec("UPDATE contactos SET prioridad = 'baja' WHERE (prioridad IS NULL OR prioridad = '') AND interes <= 1");
@$db->exec("UPDATE contactos SET prioridad = 'media' WHERE prioridad IS NULL OR prioridad = ''");

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

function prioridad_badge($p) {
    $p = strtolower(trim($p ?: 'media'));
    switch ($p) {
        case 'alta':
            return '<span class="badge" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-weight:800;font-size:11px" title="Prioridad Alta (VIP / Cierre Inminente)">🔥 Alta</span>';
        case 'baja':
            return '<span class="badge" style="background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1;font-weight:700;font-size:11px" title="Prioridad Baja (Frío / En Espera)">⚪ Baja</span>';
        case 'media':
        default:
            return '<span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-weight:700;font-size:11px" title="Prioridad Media (Estándar)">🟡 Media</span>';
    }
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

// ========================================================
// DATOS DE PRUEBA / DEMOSTRACIÓN (MAQUINARIA & EMPAQUE COLOMBIA)
// ========================================================
function get_demo_contacts_data() {
    return [
        [
            'nombre' => 'Carlos Andrés',
            'apellido' => 'Gómez Restrepo',
            'empresa' => 'Lácteos El Manantial S.A.S.',
            'nit' => '890.334.221-0',
            'cargo' => 'Gerente de Planta',
            'telefono' => '+57 320 555 1234',
            'email' => 'cgomez@lacteosmanantial.co',
            'ciudad' => 'Medellín',
            'direccion' => 'Carrera 45 # 20-10, Zona Industrial',
            'sector' => 'Alimentos & Lácteos',
            'interes' => 3,
            'prioridad' => 'alta',
            'etapa' => 'calificado',
            'notas' => 'Interesado en selladora continua vertical para bolsas de queso de 500g y fechador térmico. Presupuesto disponible para entrega rápida.'
        ],
        [
            'nombre' => 'María Camila',
            'apellido' => 'Restrepo Valencia',
            'empresa' => 'Alimentos & Snacks NutriValle',
            'nit' => '901.888.777-3',
            'cargo' => 'Directora de Compras',
            'telefono' => '+57 315 444 8899',
            'email' => 'compras@nutrivalle.com',
            'ciudad' => 'Cali',
            'direccion' => 'Vía Yumbo Km 4, Parque Industrial',
            'sector' => 'Snacks & Panadería',
            'interes' => 3,
            'prioridad' => 'alta',
            'etapa' => 'cotizacion',
            'notas' => 'Requiere cotización formal de envasadora vertical para papas fritas con dosificador multicabezal de 10 balanzas. Cierre proyectado este mes.'
        ],
        [
            'nombre' => 'Jorge Eduardo',
            'apellido' => 'Morales Silva',
            'empresa' => 'Laboratorios BioDerma Colombia',
            'nit' => '900.567.123-4',
            'cargo' => 'Jefe de Producción',
            'telefono' => '+57 310 987 6543',
            'email' => 'jmorales@bioderma.com.co',
            'ciudad' => 'Bogotá',
            'direccion' => 'Calle 100 # 19-61, Edificio Horizonte',
            'sector' => 'Cosmética & Farmacéutica',
            'interes' => 2,
            'prioridad' => 'media',
            'etapa' => 'contacto_inicial',
            'notas' => 'Busca llenadora de líquidos de 4 boquillas para cremas corporales y tónicos capilares. Pide enviar video de operación y ficha técnica.'
        ],
        [
            'nombre' => 'Ana Sofía',
            'apellido' => 'Cardona Pineda',
            'empresa' => 'Café Selecto San Jerónimo',
            'nit' => '800.222.111-9',
            'cargo' => 'Gerente General & Propietaria',
            'telefono' => '+57 314 333 2211',
            'email' => 'gerencia@cafesanjeronimo.com',
            'ciudad' => 'Pereira',
            'direccion' => 'Avenida Circunvalar # 12-40',
            'sector' => 'Café & Agroindustria',
            'interes' => 3,
            'prioridad' => 'alta',
            'etapa' => 'negociacion',
            'notas' => 'Empacadora al vacío doble campana para café especial en grano y molido en presentaciones de 500g y 1kg. Requiere entrega en Risaralda.'
        ],
        [
            'nombre' => 'Ricardo',
            'apellido' => 'Silva Benítez',
            'empresa' => 'Frigorífico & Embutidos La Sabana',
            'nit' => '901.122.334-5',
            'cargo' => 'Gerente de Operaciones',
            'telefono' => '+57 318 777 6655',
            'email' => 'rsilva@carnicoslasabana.co',
            'ciudad' => 'Chía',
            'direccion' => 'Autopista Norte Km 22, Vereda Bojacá',
            'sector' => 'Cárnicos & Alimentos',
            'interes' => 2,
            'prioridad' => 'alta',
            'etapa' => 'contacto_inicial',
            'notas' => 'Termoformadora para empaque de tocineta y salchichas al vacío. Solicita visita técnica a showroom en Bogotá para pruebas con su producto.'
        ],
        [
            'nombre' => 'Valentina',
            'apellido' => 'Duque Carvajal',
            'empresa' => 'Galletas & Panadería Imperial',
            'nit' => '890.776.543-2',
            'cargo' => 'Gerente Comercial',
            'telefono' => '+57 312 666 9988',
            'email' => 'vduque@panaderiaimperial.com',
            'ciudad' => 'Bucaramanga',
            'direccion' => 'Carrera 27 # 45-18',
            'sector' => 'Panificación & Galletas',
            'interes' => 2,
            'prioridad' => 'media',
            'etapa' => 'lead',
            'notas' => 'Interesada en máquina flow pack horizontal para empaque individual de galletas y ponqués. Pide propuesta económica.'
        ],
        [
            'nombre' => 'Fernando',
            'apellido' => 'Castro Quintero',
            'empresa' => 'Agroquímicos & Fertilizantes del Norte',
            'nit' => '900.445.889-1',
            'cargo' => 'Director Técnico',
            'telefono' => '+57 301 222 4455',
            'email' => 'fcastro@agronorte.com.co',
            'ciudad' => 'Barranquilla',
            'direccion' => 'Vía 40 # 77-120, Zona Franca',
            'sector' => 'Químico & Agropecuario',
            'interes' => 1,
            'prioridad' => 'baja',
            'etapa' => 'lead',
            'notas' => 'Llenadora lineal para garrafas de 4 y 20 litros de fertilizantes agrícolas. Contactar a final de mes para revisión técnica.'
        ],
        [
            'nombre' => 'Diana Marcela',
            'apellido' => 'Hoyos Arango',
            'empresa' => 'Pulpas Naturales del Eje',
            'nit' => '801.334.998-7',
            'cargo' => 'Gerente de Calidad',
            'telefono' => '+57 316 888 1122',
            'email' => 'dhoyos@pulpasnaturaleje.com',
            'ciudad' => 'Armenia',
            'direccion' => 'Calle 21 # 14-25',
            'sector' => 'Frutas & Agroindustria',
            'interes' => 3,
            'prioridad' => 'alta',
            'etapa' => 'calificado',
            'notas' => 'Dosificadora neumática de pistón para pulpa de fruta congelada y selladora de pedal para trabajo pesado. Desea asesoría técnica.'
        ],
        [
            'nombre' => 'Héctor Fabio',
            'apellido' => 'Valencia Osorio',
            'empresa' => 'Dulces & Confitería La Candelaria',
            'nit' => '900.678.901-2',
            'cargo' => 'Jefe de Mantenimiento',
            'telefono' => '+57 311 444 7788',
            'email' => 'hvalencia@dulcescandelaria.com',
            'ciudad' => 'Manizales',
            'direccion' => 'Parque Industrial Juanchito Lote 12',
            'sector' => 'Confitería & Alimentos',
            'interes' => 2,
            'prioridad' => 'media',
            'etapa' => 'contacto_inicial',
            'notas' => 'Túnel de termoencogido y selladora en L para empaques promocionales de caramelos. Necesita dimensiones de cámara.'
        ],
        [
            'nombre' => 'Andrea Patricia',
            'apellido' => 'Pardo Botero',
            'empresa' => 'Cervecería Artesanal Montaña Dorada',
            'nit' => '901.234.567-8',
            'cargo' => 'Socia Fundadora',
            'telefono' => '+57 317 555 3344',
            'email' => 'apardo@montanadorada.co',
            'ciudad' => 'Villa de Leyva',
            'direccion' => 'Km 2 Vía Arcabuco',
            'sector' => 'Bebidas & Licores',
            'interes' => 1,
            'prioridad' => 'baja',
            'etapa' => 'lead',
            'notas' => 'Etiquetadora de botellas de vidrio cilíndricas y taponadora manual/semiautomática. Proyecto a mediano plazo.'
        ],
        [
            'nombre' => 'Mauricio',
            'apellido' => 'Quintero Giraldo',
            'empresa' => 'Plásticos & Empaques Flexibles del Valle',
            'nit' => '890.111.444-6',
            'cargo' => 'Gerente General',
            'telefono' => '+57 313 777 0011',
            'email' => 'mquintero@plasticosvalle.com',
            'ciudad' => 'Yumbo',
            'direccion' => 'Zona Industrial La Herradura',
            'sector' => 'Envases & Plásticos',
            'interes' => 2,
            'prioridad' => 'media',
            'etapa' => 'contacto_inicial',
            'notas' => 'Codificadora inkjet industrial continuo para impresión de lote, fecha y hora en bobinas de polietileno. Solicita demo técnica.'
        ],
        [
            'nombre' => 'Lucía',
            'apellido' => 'Mendoza Cárdenas',
            'empresa' => 'Especias & Condimentos El Condado',
            'nit' => '900.890.123-4',
            'cargo' => 'Coordinadora de Compras',
            'telefono' => '+57 319 999 5566',
            'email' => 'lmendoza@especiascondado.com',
            'ciudad' => 'Ibagué',
            'direccion' => 'Carrera 5 # 38-50',
            'sector' => 'Condimentos & Especias',
            'interes' => 3,
            'prioridad' => 'alta',
            'etapa' => 'calificado',
            'notas' => 'Dosificadora por tornillo sinfín (auger filler) para polvos finos en bolsas doypack. Urgente para ampliación de línea de producción.'
        ]
    ];
}

// Inserción en 1-clic de datos de prueba
function insertar_contactos_demo($db) {
    $demos = get_demo_contacts_data();
    $creados = 0;
    
    $db->exec('BEGIN TRANSACTION');
    try {
        foreach ($demos as $d) {
            $empresa_id = null;
            if (!empty($d['empresa'])) {
                $stmt_e = $db->prepare("SELECT id FROM empresas WHERE LOWER(nombre) = LOWER(?) LIMIT 1");
                $stmt_e->bindValue(1, $d['empresa'], SQLITE3_TEXT);
                $res_e = $stmt_e->execute();
                $row_e = $res_e->fetchArray(SQLITE3_ASSOC);
                if ($row_e) {
                    $empresa_id = (int)$row_e['id'];
                } else {
                    $stmt_ne = $db->prepare("INSERT INTO empresas (nombre, nit, ciudad, direccion, telefono, email, sector, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))");
                    $stmt_ne->bindValue(1, $d['empresa'], SQLITE3_TEXT);
                    $stmt_ne->bindValue(2, $d['nit'], SQLITE3_TEXT);
                    $stmt_ne->bindValue(3, $d['ciudad'], SQLITE3_TEXT);
                    $stmt_ne->bindValue(4, $d['direccion'], SQLITE3_TEXT);
                    $stmt_ne->bindValue(5, $d['telefono'], SQLITE3_TEXT);
                    $stmt_ne->bindValue(6, $d['email'], SQLITE3_TEXT);
                    $stmt_ne->bindValue(7, $d['sector'], SQLITE3_TEXT);
                    $stmt_ne->execute();
                    $empresa_id = (int)$db->lastInsertRowID();
                }
            }

            // Verificar si el contacto ya existe por email
            $stmt_c = $db->prepare("SELECT id FROM contactos WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt_c->bindValue(1, $d['email'], SQLITE3_TEXT);
            $res_c = $stmt_c->execute();
            if ($res_c->fetchArray(SQLITE3_ASSOC)) {
                continue;
            }

            // Insertar contacto con etiqueta de demostración
            $stmt_ins = $db->prepare("INSERT INTO contactos (empresa_id, nombre, apellido, email, telefono, empresa, cargo, ciudad, direccion, sector, fuente, etapa, interes, prioridad, notas, fecha_creacion, ultima_actividad) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Datos de Prueba (Demo)', ?, ?, ?, ?, datetime('now'), datetime('now'))");
            $stmt_ins->bindValue(1, $empresa_id, SQLITE3_INTEGER);
            $stmt_ins->bindValue(2, $d['nombre'], SQLITE3_TEXT);
            $stmt_ins->bindValue(3, $d['apellido'], SQLITE3_TEXT);
            $stmt_ins->bindValue(4, $d['email'], SQLITE3_TEXT);
            $stmt_ins->bindValue(5, $d['telefono'], SQLITE3_TEXT);
            $stmt_ins->bindValue(6, $d['empresa'], SQLITE3_TEXT);
            $stmt_ins->bindValue(7, $d['cargo'], SQLITE3_TEXT);
            $stmt_ins->bindValue(8, $d['ciudad'], SQLITE3_TEXT);
            $stmt_ins->bindValue(9, $d['direccion'], SQLITE3_TEXT);
            $stmt_ins->bindValue(10, $d['sector'], SQLITE3_TEXT);
            $stmt_ins->bindValue(11, $d['etapa'], SQLITE3_TEXT);
            $stmt_ins->bindValue(12, $d['interes'], SQLITE3_INTEGER);
            $stmt_ins->bindValue(13, $d['prioridad'], SQLITE3_TEXT);
            $stmt_ins->bindValue(14, $d['notas'], SQLITE3_TEXT);
            $stmt_ins->execute();
            $cid = (int)$db->lastInsertRowID();
            $creados++;

            // Crear negocio estimado en el pipeline
            $monto_estimado = ($d['interes'] === 3) ? rand(25000000, 75000000) : rand(12000000, 30000000);
            $stmt_neg = $db->prepare("INSERT INTO negocios (contacto_id, empresa_id, nombre, monto, etapa, probabilidad, descripcion, fecha_cierre, ultima_actividad) VALUES (?, ?, ?, ?, ?, 50, ?, date('now', '+25 day'), datetime('now'))");
            $stmt_neg->bindValue(1, $cid, SQLITE3_INTEGER);
            $stmt_neg->bindValue(2, $empresa_id, SQLITE3_INTEGER);
            $stmt_neg->bindValue(3, "Maquinaria para " . $d['empresa'], SQLITE3_TEXT);
            $stmt_neg->bindValue(4, $monto_estimado, SQLITE3_FLOAT);
            $stmt_neg->bindValue(5, $d['etapa'], SQLITE3_TEXT);
            $stmt_neg->bindValue(6, $d['notas'], SQLITE3_TEXT);
            $stmt_neg->execute();

            // Actividad inicial registrada
            $stmt_act = $db->prepare("INSERT INTO actividades (contacto_id, empresa_id, tipo, asunto, descripcion, completada) VALUES (?, ?, 'nota', 'Prospecto Demo Cargado', ?, 1)");
            $stmt_act->bindValue(1, $cid, SQLITE3_INTEGER);
            $stmt_act->bindValue(2, $empresa_id, SQLITE3_INTEGER);
            $stmt_act->bindValue(3, $d['notas'], SQLITE3_TEXT);
            $stmt_act->execute();
        }
        $db->exec('COMMIT');
        return $creados;
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        return false;
    }
}

// Borrado selectivo de datos de demostración
function borrar_contactos_demo($db) {
    $db->exec('BEGIN TRANSACTION');
    try {
        $res = $db->query("SELECT id, empresa_id FROM contactos WHERE fuente = 'Datos de Prueba (Demo)'");
        $contactos_ids = [];
        $empresas_ids = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $contactos_ids[] = (int)$row['id'];
            if (!empty($row['empresa_id'])) {
                $empresas_ids[] = (int)$row['empresa_id'];
            }
        }

        if (!empty($contactos_ids)) {
            $c_list = implode(',', $contactos_ids);
            $db->exec("DELETE FROM actividades WHERE contacto_id IN ($c_list)");
            $db->exec("DELETE FROM cotizaciones WHERE contacto_id IN ($c_list)");
            $db->exec("DELETE FROM negocios WHERE contacto_id IN ($c_list)");
            $db->exec("DELETE FROM contactos WHERE id IN ($c_list)");
        }

        if (!empty($empresas_ids)) {
            $e_list = implode(',', array_unique($empresas_ids));
            $db->exec("DELETE FROM empresas WHERE id IN ($e_list) AND id NOT IN (SELECT DISTINCT empresa_id FROM contactos WHERE empresa_id IS NOT NULL)");
        }

        $db->exec('COMMIT');
        return count($contactos_ids);
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        return false;
    }
}

// Limpieza mensual o reset controlado de la base de datos
function limpiar_base_datos_controlada($db, $opciones = []) {
    $db->exec('BEGIN TRANSACTION');
    try {
        if (!empty($opciones['limpiar_contactos'])) {
            $db->exec("DELETE FROM contactos");
            $db->exec("DELETE FROM actividades");
        }
        if (!empty($opciones['limpiar_negocios'])) {
            $db->exec("DELETE FROM negocios");
        }
        if (!empty($opciones['limpiar_cotizaciones'])) {
            $db->exec("DELETE FROM cotizaciones_items");
            $db->exec("DELETE FROM cotizaciones");
        }
        if (!empty($opciones['limpiar_empresas'])) {
            $db->exec("DELETE FROM empresas");
        }
        $db->exec('COMMIT');
        return true;
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        return false;
    }
}

// Generador de CSV completo de respaldo
function generar_csv_contactos_completo($db, $delimitador = ',') {
    $out = fopen('php://temp', 'r+');
    fwrite($out, "\xEF\xBB\xBF");

    $headers = [
        'ID',
        'Nombre',
        'Apellido',
        'Empresa',
        'NIT',
        'Cargo',
        'Telefono',
        'Email',
        'Ciudad',
        'Direccion',
        'Sector',
        'Fuente_Origen',
        'Etapa_Pipeline',
        'Nivel_Interes',
        'Prioridad',
        'Notas_Requerimiento',
        'Fecha_Creacion',
        'Ultima_Actividad'
    ];
    fputcsv($out, $headers, $delimitador);

    $sql = "SELECT c.id, c.nombre, c.apellido, c.empresa, e.nit, c.cargo, c.telefono, c.email, c.ciudad, c.direccion, c.sector, c.fuente, c.etapa, c.interes, c.prioridad, c.notas, c.fecha_creacion, c.ultima_actividad 
            FROM contactos c 
            LEFT JOIN empresas e ON c.empresa_id = e.id 
            ORDER BY c.id DESC";
    $res = $db->query($sql);
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        fputcsv($out, [
            $row['id'],
            $row['nombre'],
            $row['apellido'],
            $row['empresa'],
            $row['nit'] ?? '',
            $row['cargo'],
            $row['telefono'],
            $row['email'],
            $row['ciudad'],
            $row['direccion'],
            $row['sector'],
            $row['fuente'],
            $row['etapa'],
            $row['interes'],
            $row['prioridad'] ?: 'media',
            $row['notas'],
            $row['fecha_creacion'],
            $row['ultima_actividad']
        ], $delimitador);
    }

    rewind($out);
    $csv = stream_get_contents($out);
    fclose($out);
    return $csv;
}

// Generador de CSV de la hoja de prueba
function generar_csv_hoja_prueba($delimitador = ',') {
    $out = fopen('php://temp', 'r+');
    fwrite($out, "\xEF\xBB\xBF");
    $headers = ['Nombre', 'Apellido', 'Empresa', 'NIT', 'Cargo', 'Telefono', 'Email', 'Ciudad', 'Direccion', 'Sector', 'Interes', 'Prioridad', 'Notas_Requerimiento'];
    fputcsv($out, $headers, $delimitador);
    $demos = get_demo_contacts_data();
    foreach ($demos as $d) {
        fputcsv($out, [
            $d['nombre'],
            $d['apellido'],
            $d['empresa'],
            $d['nit'],
            $d['cargo'],
            $d['telefono'],
            $d['email'],
            $d['ciudad'],
            $d['direccion'],
            $d['sector'],
            $d['interes'],
            $d['prioridad'],
            $d['notas']
        ], $delimitador);
    }
    rewind($out);
    $csv = stream_get_contents($out);
    fclose($out);
    return $csv;
}