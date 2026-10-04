<?php
// Base de datos SQLite - se crea automáticamente
$db = new SQLite3(__DIR__ . '/powerpack.db');
$db->exec("PRAGMA journal_mode=WAL");

// Crear tablas si no existen
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
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_actividad DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS actividades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contacto_id INTEGER NOT NULL,
    tipo TEXT,
    asunto TEXT,
    descripcion TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
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
    probabilidad INTEGER DEFAULT 20
)");

// Datos de ejemplo (solo si está vacío)
$count = $db->querySingle("SELECT COUNT(*) FROM contactos");
if ($count == 0) {
    $db->exec("INSERT INTO contactos (nombre, apellido, email, telefono, empresa, cargo, ciudad, sector, fuente, etapa, interes, notas) VALUES
        ('Juan', 'Pérez', 'juan@alimentosabc.com', '+57 300 123 4567', 'Alimentos ABC', 'Gerente Producción', 'Bogotá', 'alimentos', 'feria', 'calificado', 4, 'Interesado en paletizadora X4'),
        ('María', 'Gómez', 'maria@industriesxyz.com', '+57 300 987 6543', 'Industrias XYZ', 'Jefe Logística', 'Medellín', 'farmacéutica', 'feria', 'contacto_inicial', 3, 'Cotización pendiente'),
        ('Carlos', 'Rodríguez', 'carlos@logistiddelta.com', '+57 310 555 7777', 'Logística Delta', 'Director Operaciones', 'Bogotá', 'logística', 'llamada', 'negociacion', 5, 'Presupuesto aprobado'),
        ('Ana', 'Martínez', 'ana@companialimentaria.com', '+57 300 444 3333', 'Compañía Alimentaria', 'CEO', 'Cali', 'alimentos', 'feria', 'ganado', 5, 'Cliente recurrente'),
        ('Luis', 'Hernández', 'luis@bebidaslatam.com', '+57 320 111 2222', 'Bebidas Latam', 'Ingeniero Procesos', 'Barranquilla', 'bebidas', 'web', 'lead', 2, 'Visitó web, descargó catálogo')
    ");
    $db->exec("INSERT INTO actividades (contacto_id, tipo, asunto, descripcion, resultado) VALUES
        (1, 'llamada', 'Seguimiento post-feria', 'Alto interés. Quiere demo X4.', 'interesado'),
        (1, 'email', 'Catálogo técnico enviado', 'Catálogo completo de paletizadoras.', 'enviado'),
        (2, 'email', 'Cotización enviada', 'Cotización formal por $45,000 USD.', 'cotizacion'),
        (3, 'reunion', 'Reunión presencial', 'Confirmada compra. Firmando contrato.', 'cita agendada'),
        (4, 'llamada', 'Seguimiento venta recurrente', 'Satisfecha. Próxima compra Q1 2026.', 'venta cerrada')
    ");
    $db->exec("INSERT INTO negocios (contacto_id, nombre, monto, etapa, fecha_cierre, probabilidad) VALUES
        (1, 'Paletizador X4 - Alimentos ABC', 85000, 'cotizacion', '2025-12-15', 60),
        (2, 'Paletizador básico - XYZ', 45000, 'contacto_inicial', '2026-01-15', 20),
        (3, 'Línea completa paletización - Delta', 320000, 'negociacion', '2025-11-30', 80)
    ");
}

// Funciones útiles
function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

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
    $cls = $clases[$etapa] ?? 'badge-lead';
    $nombre = str_replace('_', ' ', $etapa);
    return "<span class='badge $cls'>" . ucwords($nombre) . "</span>";
}

function estrellas($n) {
    $s = '';
    for ($i = 0; $i < 5; $i++) $s .= $i < $n ? '★' : '☆';
    return $s;
}

$pipeline_etapas = [
    ['id' => 'lead', 'nombre' => 'Lead', 'color' => '#6b7280'],
    ['id' => 'contacto_inicial', 'nombre' => 'Contacto Inicial', 'color' => '#3b82f6'],
    ['id' => 'calificado', 'nombre' => 'Calificado', 'color' => '#8b5cf6'],
    ['id' => 'cotizacion', 'nombre' => 'Cotización', 'color' => '#f59e0b'],
    ['id' => 'negociacion', 'nombre' => 'Negociación', 'color' => '#ef4444'],
    ['id' => 'ganado', 'nombre' => 'Ganado', 'color' => '#059669'],
    ['id' => 'perdido', 'nombre' => 'Perdido', 'color' => '#dc2626'],
];