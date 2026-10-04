<?php
// Base de datos SQLite - se crea automáticamente
$db = new SQLite3(__DIR__ . '/powerpack.db');
$db->exec("PRAGMA journal_mode=WAL");

// 1. Tablas principales
$db->exec("CREATE TABLE IF NOT EXISTS contactos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    contacto_id INTEGER NOT NULL,
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
    nombre TEXT,
    monto REAL DEFAULT 0,
    etapa TEXT DEFAULT 'contacto_inicial',
    fecha_cierre DATE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    probabilidad INTEGER DEFAULT 20,
    descripcion TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS plantillas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo TEXT NOT NULL, -- 'whatsapp' o 'email'
    titulo TEXT NOT NULL,
    asunto TEXT,
    cuerpo TEXT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS configuracion (
    clave TEXT PRIMARY KEY,
    valor TEXT
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

agregar_columna_si_falta($db, 'contactos', 'website', 'TEXT');
agregar_columna_si_falta($db, 'contactos', 'direccion', 'TEXT');
agregar_columna_si_falta($db, 'actividades', 'fecha_vencimiento', 'DATETIME');
agregar_columna_si_falta($db, 'actividades', 'completada', 'INTEGER DEFAULT 1');
agregar_columna_si_falta($db, 'negocios', 'descripcion', 'TEXT');

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
    set_config($db, 'empresa_nombre', 'Powerpack Solutions');
    set_config($db, 'empresa_telefono', '+57 300 467 0474');
    set_config($db, 'empresa_email', 'ventas@powerpack.com.co');
    set_config($db, 'smtp_host', 'smtp.hostinger.com');
    set_config($db, 'smtp_port', '465');
    set_config($db, 'smtp_secure', 'ssl');
    set_config($db, 'smtp_user', '');
    set_config($db, 'smtp_pass', '');
    set_config($db, 'smtp_from', '');
}

// Plantillas por defecto para WhatsApp y Correo
if ($db->querySingle("SELECT COUNT(*) FROM plantillas") == 0) {
    $db->exec("INSERT INTO plantillas (tipo, titulo, asunto, cuerpo) VALUES
        ('whatsapp', '1. Saludo inicial y presentación', '', '¡Hola {nombre}! Un gusto saludarte. Te escribe {empresa_nombre}. Vimos el excelente trabajo que hacen en {empresa}. ¿Cómo tienen organizado actualmente su proceso de empaque y logística?'),
        ('whatsapp', '2. Compartir catálogo técnico', '', 'Hola {nombre}, como te comenté, te comparto el catálogo con nuestras soluciones de maquinaria y paletización pensadas para optimizar tiempos en {empresa}. ¿Te gustaría que coordinemos una breve demostración?'),
        ('whatsapp', '3. Propuesta / Cotización lista', '', 'Hola {nombre}, ¡buenas noticias! Ya tenemos lista la propuesta comercial a la medida para {empresa}. ¿Tienes 5 minutos hoy para que la revisemos juntos?'),
        ('whatsapp', '4. Seguimiento comercial', '', 'Hola {nombre}, ¿cómo estás? Quería hacer un breve seguimiento para saber si lograste revisar la propuesta para {empresa} o si tienes alguna inquietud que podamos resolver.'),
        ('email', 'Propuesta Comercial y Ficha Técnica', 'Propuesta de optimización para {empresa}', '<p>Estimado/a <strong>{nombre}</strong>,</p><p>Es un placer saludarte de parte de <strong>{empresa_nombre}</strong>.</p><p>Analizando las necesidades de <strong>{empresa}</strong> en el sector {sector}, hemos preparado una propuesta especializada que permite automatizar y optimizar los tiempos de producción y despacho hasta en un 35%.</p><p>Quedamos atentos a tus comentarios para coordinar una breve reunión técnica o llamada de seguimiento.</p><p>Cordialmente,<br><strong>Equipo Comercial</strong><br>{empresa_nombre}<br>{empresa_telefono}</p>'),
        ('email', 'Seguimiento post-reunión', 'Próximos pasos - {empresa} & {empresa_nombre}', '<p>Hola <strong>{nombre}</strong>,</p><p>Muchas gracias por el tiempo en nuestra reciente conversación sobre los requerimientos de <strong>{empresa}</strong>.</p><p>Tal como acordamos, quedamos atentos a su confirmación para proceder con el siguiente paso en la implementación.</p><p>Saludos cordiales,<br>{empresa_nombre}</p>')
    ");
}

// Datos de ejemplo iniciales (solo si está vacío)
$count = $db->querySingle("SELECT COUNT(*) FROM contactos");
if ($count == 0) {
    $db->exec("INSERT INTO contactos (nombre, apellido, email, telefono, empresa, cargo, ciudad, sector, fuente, etapa, interes, notas) VALUES
        ('Juan', 'Pérez', 'juan@alimentosabc.com', '+57 300 123 4567', 'Alimentos ABC', 'Gerente Producción', 'Bogotá', 'alimentos', 'feria', 'calificado', 4, 'Interesado en paletizadora X4 de alta velocidad'),
        ('María', 'Gómez', 'maria@industriesxyz.com', '+57 300 987 6543', 'Industrias XYZ', 'Jefe Logística', 'Medellín', 'farmacéutica', 'feria', 'contacto_inicial', 3, 'Cotización pendiente de validación técnica'),
        ('Carlos', 'Rodríguez', 'carlos@logistiddelta.com', '+57 310 555 7777', 'Logística Delta', 'Director Operaciones', 'Bogotá', 'logística', 'llamada', 'negociacion', 5, 'Presupuesto aprobado para cierre en fin de mes'),
        ('Ana', 'Martínez', 'ana@companialimentaria.com', '+57 300 444 3333', 'Compañía Alimentaria', 'CEO', 'Cali', 'alimentos', 'feria', 'ganado', 5, 'Cliente recurrente con línea de empaque activa'),
        ('Luis', 'Hernández', 'luis@bebidaslatam.com', '+57 320 111 2222', 'Bebidas Latam', 'Ingeniero Procesos', 'Barranquilla', 'bebidas', 'web', 'lead', 2, 'Visitó el catálogo web y solicitó especificaciones')
    ");

    $db->exec("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, fecha, resultado) VALUES
        (1, 'whatsapp', 'Mensaje de presentación', 'Enviado catálogo técnico de paletizadora X4 vía WhatsApp.', datetime('now', '-2 days'), 'entregado'),
        (1, 'llamada', 'Seguimiento telefónico', 'Llamada de 12 minutos. Alto interés en demostración presencial.', datetime('now', '-1 day'), 'interesado'),
        (2, 'email', 'Cotización formal enviada', 'Propuesta económica enviada por $45,000 USD.', datetime('now', '-3 days'), 'enviado'),
        (3, 'tarea', 'Revisar firma de contrato', 'Llamar al departamento legal para confirmar pólizas.', datetime('now'), 'pendiente'),
        (4, 'reunion', 'Reunión presencial en planta', 'Revisión final de línea en Cali. Venta concretada.', datetime('now', '-5 days'), 'venta cerrada')
    ");

    $db->exec("INSERT INTO negocios (contacto_id, nombre, monto, etapa, fecha_cierre, probabilidad, descripcion) VALUES
        (1, 'Paletizador Automático X4 - Alimentos ABC', 85000, 'cotizacion', '2026-11-15', 60, 'Incluye instalación y entrenamiento del personal'),
        (2, 'Línea de Encartonado - XYZ', 45000, 'contacto_inicial', '2026-12-05', 20, 'Evaluando requerimiento eléctrico'),
        (3, 'Automatización Fin de Línea - Delta', 320000, 'negociacion', '2026-10-30', 80, 'Esperando orden de compra formal')
    ");
}

// Funciones de formato y utilidad
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
        case 'nota':     return '<span class="act-icon act-nota">📝</span>';
        default:         return '<span class="act-icon act-default">📌</span>';
    }
}

function limpiar_telefono_whatsapp($tel, $codigo_pais = '57') {
    $limpio = preg_replace('/[^0-9]/', '', $tel ?? '');
    if (empty($limpio)) return '';
    // Si empieza con 0, removerlo
    if (strpos($limpio, '0') === 0) {
        $limpio = substr($limpio, 1);
    }
    // Si tiene 10 dígitos (formato estándar móvil ej. Colombia 3001234567), anteponer código país
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
        '{empresa_nombre}' => $config['empresa_nombre'] ?? 'Powerpack Solutions',
        '{empresa_telefono}'=> $config['empresa_telefono'] ?? '+57 300 467 0474',
        '{empresa_email}'  => $config['empresa_email'] ?? 'ventas@powerpack.com.co',
    ];
    return str_replace(array_keys($vars), array_values($vars), $plantilla);
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