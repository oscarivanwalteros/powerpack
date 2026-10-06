<?php
$contacto_id_pre = isset($_GET['contacto_id']) ? (int)$_GET['contacto_id'] : 0;
$empresa_id_pre  = isset($_GET['empresa_id'])  ? (int)$_GET['empresa_id']  : 0;
$negocio_id_pre  = isset($_GET['negocio_id'])  ? (int)$_GET['negocio_id']  : 0;

if ($contacto_id_pre === 0 && $empresa_id_pre > 0) {
    $contacto_id_pre = (int)$db->querySingle("SELECT id FROM contactos WHERE empresa_id = $empresa_id_pre ORDER BY id ASC");
}

$contactos = $db->query("SELECT id, nombre, apellido, empresa, email, telefono, empresa_id FROM contactos ORDER BY nombre ASC");
$productos = $db->query("SELECT * FROM productos ORDER BY categoria ASC, nombre ASC");
$productos_arr = [];
while($p = $productos->fetchArray(SQLITE3_ASSOC)) {
    $productos_arr[] = $p;
}

// Generar número correlativo
$anio = date('Y');
$count_ano = (int)$db->querySingle("SELECT COUNT(*) FROM cotizaciones WHERE numero LIKE 'COT-$anio-%'");
$siguiente_numero = sprintf("COT-%s-%03d", $anio, $count_ano + 1);

$banco_info_default = get_config($db, 'banco_info', 'Bancolombia Cta Corriente # 104-582910-44 a nombre de Power Pack');
?>

<div class="page-header">
    <div>
        <h1>Generador de Cotización Comercial Institucional</h1>
        <p>Genera propuestas formales en papelería membretada de Power Pack con carta ejecutiva, especificaciones y términos comerciales</p>
    </div>
    <a href="?page=cotizaciones" class="btn btn-secondary btn-sm">← Volver a Cotizaciones</a>
</div>

<div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:28px;box-shadow:var(--shadow-sm);max-width:980px">
    <form method="POST">
        <input type="hidden" name="guardar_cotizacion" value="1">
        
        <!-- DATOS BÁSICOS DE LA PROPUESTA -->
        <div class="form-row">
            <div class="form-group">
                <label>Número de Cotización Oficial</label>
                <input type="text" name="numero" value="<?= $siguiente_numero ?>" required readonly style="background:#f8fafc;font-weight:900;color:#ed1c29;font-size:15px;letter-spacing:0.02em">
            </div>
            <div class="form-group">
                <label>Fecha de Emisión</label>
                <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Cliente / Contacto Destinatario *</label>
                <select name="contacto_id" id="select_contacto" required onchange="actualizarDestinatarioCarta()">
                    <option value="">— Seleccionar cliente destinatario —</option>
                    <?php while($ct = $contactos->fetchArray(SQLITE3_ASSOC)): ?>
                    <option value="<?= $ct['id'] ?>" data-empresa-id="<?= (int)$ct['empresa_id'] ?>" data-empresa="<?= h($ct['empresa']) ?>" data-nombre="<?= h($ct['nombre'].' '.$ct['apellido']) ?>" <?= $contacto_id_pre == $ct['id'] ? 'selected' : '' ?>>
                        <?= h($ct['nombre'].' '.$ct['apellido']) ?> — <?= h($ct['empresa'] ?: 'Sin empresa') ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Validez de la Oferta</label>
                <select name="validez_dias">
                    <option value="15" selected>15 días calendario</option>
                    <option value="30">30 días calendario</option>
                    <option value="45">45 días calendario</option>
                    <option value="60">60 días calendario</option>
                </select>
            </div>
        </div>

        <!-- ASUNTO FORMAL -->
        <div class="form-group" style="margin-bottom:20px">
            <label>Asunto de la Propuesta Técnico-Comercial *</label>
            <input type="text" name="asunto" id="input_asunto" value="Propuesta Técnico-Comercial de Maquinaria y Soluciones de Empaque" required style="font-weight:700">
        </div>

        <!-- CARTA DE PRESENTACIÓN EJECUTIVA INSTITUCIONAL -->
        <div style="margin:20px 0 24px;background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #2c60a4;border-radius:var(--radius-sm);padding:18px 20px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:10px">
                <div>
                    <label style="font-weight:800;font-size:13.5px;color:var(--fg);margin:0;display:flex;align-items:center;gap:6px">
                        <span>📄</span> Carta de Presentación Ejecutiva (Papelería Propia Power Pack)
                    </label>
                    <div style="font-size:12px;color:var(--fg-secondary);margin-top:2px">
                        Aparecerá en el encabezado de la cotización formal con el saludo oficial de Power Pack. Puedes editarla o personalizarla libremente.
                    </div>
                </div>
                <button type="button" onclick="cargarCartaEstandar()" class="btn btn-secondary btn-sm" style="font-size:11.5px;padding:4px 12px">
                    🔄 Restablecer Texto Estándar
                </button>
            </div>
            <textarea name="carta_presentacion" id="textarea_carta" rows="6" style="width:100%;font-size:13px;line-height:1.6;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fff;font-family:inherit"><?= h("Reciban un cordial y respetuoso saludo por parte del equipo directivo, técnico y comercial de Power Pack. Es para nosotros un gran honor presentar a su consideración nuestra propuesta técnico-comercial para el suministro e integración de maquinaria y soluciones de empaque industrial, concebidas para optimizar la productividad de sus procesos, maximizar la vida útil de sus productos y elevar sus estándares operativos con la más alta confiabilidad.\n\nEn Power Pack contamos con un sólido respaldo en ingeniería aplicada, suministrando equipos de alto rendimiento construidos bajo estrictas normas de calidad industrial, materiales de grado alimenticio y componentes electro-neumáticos de marcas líderes a nivel global. Asimismo, garantizamos un acompañamiento posventa integral, disponibilidad permanente de repuestos originales y soporte técnico especializado en Colombia.\n\nA continuación, ponemos a su disposición el desglose técnico de los equipos solicitados, junto con la correspondiente valoración económica y términos de suministro diseñados a la medida de sus requerimientos:") ?></textarea>
        </div>

        <!-- ITEMS / PRODUCTOS DE LA COTIZACIÓN -->
        <div style="margin:24px 0 16px;border-top:1px solid var(--border);padding-top:20px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:10px">
                <h3 style="font-size:15px;font-weight:800;margin:0;display:flex;align-items:center;gap:6px">
                    <span>⚙️</span> Equipos, Maquinaria & Servicios a Cotizar
                </h3>
                <div style="display:flex;gap:8px">
                    <select id="select_catalogo" style="padding:6px 12px;font-size:12px;border:1px solid var(--border);border-radius:var(--radius-sm)">
                        <option value="">+ Añadir desde catálogo de maquinaria...</option>
                        <?php foreach($productos_arr as $prod): ?>
                        <option value="<?= $prod['id'] ?>" data-nombre="<?= h($prod['nombre']) ?>" data-precio="<?= (float)$prod['precio'] ?>" data-desc="<?= h($prod['descripcion']) ?>">
                            <?= h($prod['nombre']) ?> - $<?= number_format($prod['precio'], 0, ',', '.') ?> COP
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="agregarItemCatalogo()" class="btn btn-secondary btn-sm">+ Agregar</button>
                    <button type="button" onclick="agregarFilaVacia()" class="btn btn-secondary btn-sm">+ Línea Manual</button>
                </div>
            </div>

            <table style="width:100%;margin-bottom:14px;border-collapse:collapse">
                <thead>
                    <tr style="background:#2c60a4;color:#fff">
                        <th style="padding:10px 12px;text-align:left;font-size:11.5px;font-weight:800;color:#fff">Descripción Técnica del Equipo o Servicio</th>
                        <th style="padding:10px 12px;text-align:center;font-size:11.5px;font-weight:800;width:90px;color:#fff">Cant.</th>
                        <th style="padding:10px 12px;text-align:right;font-size:11.5px;font-weight:800;width:150px;color:#fff">Precio Unitario ($)</th>
                        <th style="padding:10px 12px;text-align:right;font-size:11.5px;font-weight:800;width:150px;color:#fff">Subtotal ($)</th>
                        <th style="padding:10px 12px;text-align:center;width:45px;color:#fff"></th>
                    </tr>
                </thead>
                <tbody id="items_body">
                    <!-- Filas dinámicas -->
                </tbody>
            </table>

            <!-- RESUMEN DE TOTALES -->
            <div style="display:flex;justify-content:flex-end">
                <div style="width:320px;background:#f8fafc;border:1px solid var(--border);border-top:3px solid #2c60a4;border-radius:var(--radius-sm);padding:16px;font-size:13px;display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;justify-content:space-between">
                        <span>Subtotal Neto:</span>
                        <strong id="label_subtotal">$0 COP</strong>
                        <input type="hidden" name="subtotal" id="input_subtotal" value="0">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span>IVA (19%):</span>
                        <strong id="label_iva">$0 COP</strong>
                        <input type="hidden" name="iva_monto" id="input_iva" value="0">
                        <input type="hidden" name="iva_porcentaje" value="19">
                    </div>
                    <div style="border-top:2px solid var(--border);padding-top:10px;display:flex;justify-content:space-between;font-size:17px;font-weight:900">
                        <span>Total Inversión:</span>
                        <strong id="label_total" style="color:#ed1c29">$0 COP</strong>
                        <input type="hidden" name="total" id="input_total" value="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- CONDICIONES COMERCIALES B2B -->
        <div style="margin:24px 0 16px;border-top:1px solid var(--border);padding-top:20px">
            <h3 style="font-size:15px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:6px">
                <span>📋</span> Condiciones Comerciales & Garantía de Fábrica
            </h3>
            <div class="form-row-3">
                <div class="form-group">
                    <label>Forma de Pago</label>
                    <input type="text" name="condiciones" value="50% Anticipo con orden de compra / 50% contra entrega y prueba" required>
                </div>
                <div class="form-group">
                    <label>Tiempo de Entrega</label>
                    <input type="text" name="tiempo_entrega" value="4 a 6 semanas calendario" required>
                </div>
                <div class="form-group">
                    <label>Garantía de Fábrica</label>
                    <input type="text" name="garantia" value="12 meses en partes mecánicas y electrónicas" required>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px">
                <div class="form-group">
                    <label>Instalación & Capacitación Técnica en Planta</label>
                    <input type="text" name="incluye_instalacion" value="Incluye servicio de instalación técnica en planta, calibración y capacitación operativa y de mantenimiento para su personal técnico." required>
                </div>
                <div class="form-group">
                    <label>Cuenta Bancaria Oficial</label>
                    <input type="text" value="<?= h($banco_info_default) ?>" readonly style="background:#f8fafc;color:var(--fg-secondary)">
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:28px;padding-top:16px;border-top:1px solid var(--border)">
            <a href="?page=cotizaciones" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="background:#2c60a4;font-weight:800">
                📄 Generar y Ver Cotización en Papelería Oficial
            </button>
        </div>
    </form>
</div>

<script>
let filaIndex = 0;

function agregarFila(desc = '', cant = 1, precio = 0) {
    const tbody = document.getElementById('items_body');
    const tr = document.createElement('tr');
    tr.id = 'fila_' + filaIndex;
    tr.style.borderBottom = '1px solid var(--border)';
    tr.innerHTML = `
        <td style="padding:10px 12px;vertical-align:top">
            <textarea name="items[${filaIndex}][descripcion]" rows="2" required placeholder="Nombre técnico y especificaciones..." style="width:100%;font-size:13px;padding:8px;border:1px solid var(--border);border-radius:var(--radius-sm);line-height:1.4">${desc}</textarea>
        </td>
        <td style="padding:10px 12px;vertical-align:top">
            <input type="number" step="1" min="1" name="items[${filaIndex}][cantidad]" value="${cant}" oninput="calcularTotales()" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:var(--radius-sm);text-align:center">
        </td>
        <td style="padding:10px 12px;vertical-align:top">
            <input type="number" step="100" min="0" name="items[${filaIndex}][precio]" value="${precio}" oninput="calcularTotales()" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:var(--radius-sm);text-align:right">
        </td>
        <td style="padding:10px 12px;text-align:right;font-weight:800;vertical-align:top;padding-top:16px">
            <span class="row-subtotal">$0</span>
        </td>
        <td style="padding:10px 12px;text-align:center;vertical-align:top;padding-top:14px">
            <button type="button" onclick="eliminarFila('fila_${filaIndex}')" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:16px" title="Eliminar fila">✕</button>
        </td>
    `;
    tbody.appendChild(tr);
    filaIndex++;
    calcularTotales();
}

function agregarFilaVacia() {
    agregarFila('', 1, 0);
}

function agregarItemCatalogo() {
    const select = document.getElementById('select_catalogo');
    const opt = select.options[select.selectedIndex];
    if (!opt.value) return;
    const nombre = opt.getAttribute('data-nombre');
    const precio = parseFloat(opt.getAttribute('data-precio')) || 0;
    const desc = opt.getAttribute('data-desc');
    agregarFila(nombre + (desc ? "\n" + desc : ''), 1, precio);
    select.value = '';
}

function eliminarFila(id) {
    const fila = document.getElementById(id);
    if (fila) fila.remove();
    calcularTotales();
}

function calcularTotales() {
    let subtotal = 0;
    const rows = document.querySelectorAll('#items_body tr');
    rows.forEach(r => {
        const cant = parseFloat(r.querySelector('input[name*="[cantidad]"]').value) || 0;
        const precio = parseFloat(r.querySelector('input[name*="[precio]"]').value) || 0;
        const filaSubtotal = cant * precio;
        r.querySelector('.row-subtotal').innerText = '$' + filaSubtotal.toLocaleString('es-CO');
        subtotal += filaSubtotal;
    });

    const iva = subtotal * 0.19;
    const total = subtotal + iva;

    document.getElementById('label_subtotal').innerText = '$' + subtotal.toLocaleString('es-CO') + ' COP';
    document.getElementById('label_iva').innerText = '$' + iva.toLocaleString('es-CO') + ' COP';
    document.getElementById('label_total').innerText = '$' + total.toLocaleString('es-CO') + ' COP';

    document.getElementById('input_subtotal').value = subtotal;
    document.getElementById('input_iva').value = iva;
    document.getElementById('input_total').value = total;
}

function cargarCartaEstandar() {
    document.getElementById('textarea_carta').value = `Reciban un cordial y respetuoso saludo por parte del equipo directivo, técnico y comercial de Power Pack. Es para nosotros un gran honor presentar a su consideración nuestra propuesta técnico-comercial para el suministro e integración de maquinaria y soluciones de empaque industrial, concebidas para optimizar la productividad de sus procesos, maximizar la vida útil de sus productos y elevar sus estándares operativos con la más alta confiabilidad.\n\nEn Power Pack contamos con un sólido respaldo en ingeniería aplicada, suministrando equipos de alto rendimiento construidos bajo estrictas normas de calidad industrial, materiales de grado alimenticio y componentes electro-neumáticos de marcas líderes a nivel global. Asimismo, garantizamos un acompañamiento posventa integral, disponibilidad permanente de repuestos originales y soporte técnico especializado en Colombia.\n\nA continuación, ponemos a su disposición el desglose técnico de los equipos solicitados, junto con la correspondiente valoración económica y términos de suministro diseñados a la medida de sus requerimientos:`;
}

function actualizarDestinatarioCarta() {
    // Si la carta está vacía, cargar el estándar
    const current = document.getElementById('textarea_carta').value.trim();
    if (!current) {
        cargarCartaEstandar();
    }
}

// Iniciar con al menos 1 fila de ejemplo si no hay filas
window.addEventListener('DOMContentLoaded', () => {
    agregarFila('Paletizador Automático Industrial X4\nLínea de alta velocidad con capacidad de hasta 40 paquetes/minuto. Diagnóstico remoto Siemens.', 1, 85000);
});
</script>
