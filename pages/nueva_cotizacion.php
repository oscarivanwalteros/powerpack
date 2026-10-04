<?php
$contacto_id_pre = isset($_GET['contacto_id']) ? (int)$_GET['contacto_id'] : 0;
$negocio_id_pre  = isset($_GET['negocio_id'])  ? (int)$_GET['negocio_id']  : 0;

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
?>

<div class="page-header">
    <div>
        <h1>Generador de Cotización Comercial</h1>
        <p>Crea una propuesta técnica y económica formal con validez, garantía y desglose de IVA</p>
    </div>
    <a href="?page=cotizaciones" class="btn btn-secondary btn-sm">← Volver a Cotizaciones</a>
</div>

<div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;box-shadow:var(--shadow-sm);max-width:960px">
    <form method="POST">
        <input type="hidden" name="guardar_cotizacion" value="1">
        
        <div class="form-row">
            <div class="form-group">
                <label>Número de Cotización</label>
                <input type="text" name="numero" value="<?= $siguiente_numero ?>" required readonly style="background:#f8fafc;font-weight:800;color:var(--accent)">
            </div>
            <div class="form-group">
                <label>Fecha de Emisión</label>
                <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Cliente / Contacto Destinatario *</label>
                <select name="contacto_id" id="select_contacto" required>
                    <option value="">— Seleccionar cliente —</option>
                    <?php while($ct = $contactos->fetchArray(SQLITE3_ASSOC)): ?>
                    <option value="<?= $ct['id'] ?>" data-empresa-id="<?= (int)$ct['empresa_id'] ?>" <?= $contacto_id_pre == $ct['id'] ? 'selected' : '' ?>>
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
                    <option value="60">60 días calendario</option>
                </select>
            </div>
        </div>

        <!-- ITEMS / PRODUCTOS DE LA COTIZACIÓN -->
        <div style="margin:24px 0 16px;border-top:1px solid var(--border);padding-top:18px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <h3 style="font-size:15px;font-weight:800">📦 Equipos & Servicios a Cotizar</h3>
                <div style="display:flex;gap:8px">
                    <select id="select_catalogo" style="padding:6px 10px;font-size:12px;border:1px solid var(--border);border-radius:var(--radius-sm)">
                        <option value="">+ Añadir desde catálogo de productos...</option>
                        <?php foreach($productos_arr as $prod): ?>
                        <option value="<?= $prod['id'] ?>" data-nombre="<?= h($prod['nombre']) ?>" data-precio="<?= (float)$prod['precio'] ?>" data-desc="<?= h($prod['descripcion']) ?>">
                            <?= h($prod['nombre']) ?> - $<?= number_format($prod['precio'], 0) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="agregarItemCatalogo()" class="btn btn-secondary btn-sm">+ Agregar</button>
                    <button type="button" onclick="agregarFilaVacia()" class="btn btn-secondary btn-sm">+ Línea Manual</button>
                </div>
            </div>

            <table style="width:100%;margin-bottom:12px">
                <thead>
                    <tr>
                        <th>Descripción del Equipo o Servicio</th>
                        <th style="width:90px">Cant.</th>
                        <th style="width:140px">Precio Unitario ($)</th>
                        <th style="width:140px">Subtotal ($)</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="items_body">
                    <!-- Filas dinámicas -->
                </tbody>
            </table>

            <!-- RESUMEN DE TOTALES -->
            <div style="display:flex;justify-content:flex-end">
                <div style="width:280px;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;font-size:13px;display:flex;flex-direction:column;gap:8px">
                    <div style="display:flex;justify-content:space-between">
                        <span>Subtotal:</span>
                        <strong id="label_subtotal">$0</strong>
                        <input type="hidden" name="subtotal" id="input_subtotal" value="0">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span>IVA (19%):</span>
                        <strong id="label_iva">$0</strong>
                        <input type="hidden" name="iva_monto" id="input_iva" value="0">
                        <input type="hidden" name="iva_porcentaje" value="19">
                    </div>
                    <div style="border-top:2px solid var(--border);padding-top:8px;display:flex;justify-content:space-between;font-size:16px;font-weight:800">
                        <span>Total:</span>
                        <strong id="label_total" style="color:var(--accent)">$0</strong>
                        <input type="hidden" name="total" id="input_total" value="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- CONDICIONES COMERCIALES B2B -->
        <div style="margin:24px 0 16px;border-top:1px solid var(--border);padding-top:18px">
            <h3 style="font-size:15px;font-weight:800;margin-bottom:12px">🤝 Condiciones Comerciales & Garantía</h3>
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
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px">
            <a href="?page=cotizaciones" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Generar y Ver Cotización</button>
        </div>
    </form>
</div>

<script>
let filaIndex = 0;

function agregarFila(desc = '', cant = 1, precio = 0) {
    const tbody = document.getElementById('items_body');
    const tr = document.createElement('tr');
    tr.id = 'fila_' + filaIndex;
    tr.innerHTML = `
        <td>
            <textarea name="items[${filaIndex}][descripcion]" rows="2" required placeholder="Nombre técnico y especificaciones..." style="width:100%;font-size:13px;padding:6px;border:1px solid var(--border);border-radius:var(--radius-sm)">${desc}</textarea>
        </td>
        <td>
            <input type="number" step="1" min="1" name="items[${filaIndex}][cantidad]" value="${cant}" oninput="calcularTotales()" style="width:100%;padding:6px;border:1px solid var(--border);border-radius:var(--radius-sm);text-align:center">
        </td>
        <td>
            <input type="number" step="100" min="0" name="items[${filaIndex}][precio]" value="${precio}" oninput="calcularTotales()" style="width:100%;padding:6px;border:1px solid var(--border);border-radius:var(--radius-sm);text-align:right">
        </td>
        <td style="text-align:right;font-weight:700">
            <span class="row-subtotal">$0</span>
        </td>
        <td style="text-align:center">
            <button type="button" onclick="eliminarFila('fila_${filaIndex}')" style="background:none;color:var(--danger);cursor:pointer;font-size:14px">✕</button>
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

    document.getElementById('label_subtotal').innerText = '$' + subtotal.toLocaleString('es-CO');
    document.getElementById('label_iva').innerText = '$' + iva.toLocaleString('es-CO');
    document.getElementById('label_total').innerText = '$' + total.toLocaleString('es-CO');

    document.getElementById('input_subtotal').value = subtotal;
    document.getElementById('input_iva').value = iva;
    document.getElementById('input_total').value = total;
}

// Iniciar con al menos 1 fila de ejemplo
window.addEventListener('DOMContentLoaded', () => {
    agregarFila('Paletizador Automático Industrial X4\nLínea de alta velocidad con capacidad de hasta 40 paquetes/minuto. Diagnóstico remoto Siemens.', 1, 85000);
});
</script>
