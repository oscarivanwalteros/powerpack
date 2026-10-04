<?php
$cot_id = (int)($_GET['id'] ?? 0);
$cot = $db->querySingle("SELECT c.*, ct.nombre, ct.apellido, ct.email, ct.telefono, ct.cargo, ct.ciudad, ct.direccion as ct_dir, e.nombre as empresa_nombre, e.nit as empresa_nit, e.direccion as empresa_dir 
    FROM cotizaciones c 
    LEFT JOIN contactos ct ON c.contacto_id = ct.id 
    LEFT JOIN empresas e ON c.empresa_id = e.id 
    WHERE c.id = $cot_id", true);

if (!$cot) {
    echo "<div style='text-align:center;padding:50px'><h2>Cotización no encontrada</h2><a href='?page=cotizaciones' class='btn btn-secondary'>Volver</a></div>";
    return;
}

$items = $db->query("SELECT * FROM cotizacion_items WHERE cotizacion_id = $cot_id ORDER BY id ASC");
$clean_tel = limpiar_telefono_whatsapp($cot['telefono']);
$emp_nombre = get_config($db, 'empresa_nombre', 'Powerpack Solutions SAS');
$emp_nit = get_config($db, 'empresa_nit', '901.452.889-1');
$emp_tel = get_config($db, 'empresa_telefono', '+57 300 467 0474');
$emp_email = get_config($db, 'empresa_email', 'ventas@powerpack.com.co');
$emp_dir = get_config($db, 'empresa_direccion', 'Av. El Dorado #98-20, Parque Industrial Bogotá');
$banco_info = get_config($db, 'banco_info', 'Bancolombia Cta Corriente # 104-582910-44 a nombre de Powerpack Solutions SAS');
?>

<div class="no-print" style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <a href="?page=cotizaciones" class="btn btn-secondary btn-sm">← Volver al listado</a>
    
    <div style="display:flex;gap:10px;align-items:center">
        <!-- Selector rápido de estado -->
        <form method="POST" style="margin:0">
            <input type="hidden" name="cambiar_estado_cotizacion" value="1">
            <input type="hidden" name="cotizacion_id" value="<?= $cot['id'] ?>">
            <select name="nuevo_estado" onchange="this.form.submit()" style="padding:6px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-weight:700;font-size:12px">
                <option value="borrador" <?= $cot['estado']=='borrador'?'selected':'' ?>>📝 Borrador</option>
                <option value="enviada" <?= $cot['estado']=='enviada'?'selected':'' ?>>✉️ Enviada al Cliente</option>
                <option value="aprobada" <?= $cot['estado']=='aprobada'?'selected':'' ?>>🏆 Aprobada / Ganada</option>
                <option value="rechazada" <?= $cot['estado']=='rechazada'?'selected':'' ?>>❌ Rechazada</option>
            </select>
        </form>

        <?php if($clean_tel): ?>
        <a href="https://wa.me/<?= $clean_tel ?>?text=<?= rawurlencode("Hola {$cot['nombre']}, te comparto la propuesta comercial formal {$cot['numero']} por un valor total de $" . number_format($cot['total'], 0) . " para {$cot['empresa_nombre']}. ¿Podemos revisarla juntos?") ?>" target="_blank" class="btn btn-whatsapp btn-sm">
            💬 Enviar por WhatsApp
        </a>
        <?php endif; ?>

        <button onclick="window.print()" class="btn btn-primary btn-sm">
            🖨️ Imprimir / Guardar en PDF
        </button>
    </div>
</div>

<!-- FORMATO DE COTIZACIÓN PROFESIONAL IMPRIMIBLE -->
<div class="print-paper" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:40px;box-shadow:var(--shadow-sm);max-width:900px;margin:0 auto">
    
    <!-- CABECERA CORPORATIVA -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid var(--accent);padding-bottom:24px;margin-bottom:24px">
        <div>
            <div style="display:flex;align-items:center;gap:10px">
                <div style="background:var(--accent);color:#fff;font-weight:900;font-size:20px;padding:6px 12px;border-radius:8px">⚡</div>
                <div style="font-size:24px;font-weight:900;color:var(--fg);letter-spacing:-0.02em"><?= h($emp_nombre) ?></div>
            </div>
            <div style="font-size:12px;color:var(--fg-secondary);margin-top:6px;line-height:1.4">
                <strong>NIT:</strong> <?= h($emp_nit) ?><br>
                <?= h($emp_dir) ?><br>
                Tel: <?= h($emp_tel) ?> • Email: <?= h($emp_email) ?>
            </div>
        </div>

        <div style="text-align:right">
            <div style="font-size:13px;font-weight:800;color:var(--fg-secondary);text-transform:uppercase;letter-spacing:0.06em">Propuesta Comercial</div>
            <div style="font-size:24px;font-weight:900;color:var(--accent);margin:4px 0"><?= h($cot['numero']) ?></div>
            <div style="font-size:12px;color:var(--fg-secondary)">
                <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($cot['fecha'])) ?><br>
                <strong>Vigencia:</strong> <?= (int)$cot['validez_dias'] ?> días calendario
            </div>
        </div>
    </div>

    <!-- DATOS DEL CLIENTE Y EMPRESA -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);padding:18px;margin-bottom:28px;font-size:13px">
        <div>
            <div style="font-size:11px;font-weight:800;color:var(--fg-secondary);text-transform:uppercase;margin-bottom:4px">Cliente / Empresa</div>
            <div style="font-size:16px;font-weight:800;color:var(--fg)"><?= h($cot['empresa_nombre'] ?: 'Particular') ?></div>
            <?php if($cot['empresa_nit']): ?><div style="color:var(--fg-secondary)">NIT: <?= h($cot['empresa_nit']) ?></div><?php endif; ?>
            <?php if($cot['empresa_dir']): ?><div style="color:var(--fg-secondary)"><?= h($cot['empresa_dir']) ?></div><?php endif; ?>
        </div>
        <div>
            <div style="font-size:11px;font-weight:800;color:var(--fg-secondary);text-transform:uppercase;margin-bottom:4px">Atención A:</div>
            <div style="font-weight:700"><?= h($cot['nombre'] . ' ' . $cot['apellido']) ?></div>
            <div style="color:var(--fg-secondary)"><?= h($cot['cargo'] ?: 'Responsable') ?></div>
            <div style="color:var(--fg-secondary)"><?= h($cot['email']) ?> • Tel: <?= h($cot['telefono']) ?></div>
        </div>
    </div>

    <!-- TABLA DE ITEMS -->
    <table style="width:100%;margin-bottom:24px;border-collapse:collapse">
        <thead>
            <tr style="background:#f1f5f9;border-bottom:2px solid var(--border)">
                <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:800;text-transform:uppercase">Ítem / Especificación Técnica</th>
                <th style="padding:10px 14px;text-align:center;font-size:11px;font-weight:800;text-transform:uppercase;width:70px">Cant.</th>
                <th style="padding:10px 14px;text-align:right;font-size:11px;font-weight:800;text-transform:uppercase;width:130px">Precio Unitario</th>
                <th style="padding:10px 14px;text-align:right;font-size:11px;font-weight:800;text-transform:uppercase;width:140px">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php while($item = $items->fetchArray(SQLITE3_ASSOC)): ?>
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:14px;font-size:13px;line-height:1.5;vertical-align:top">
                    <?= nl2br(h($item['descripcion'])) ?>
                </td>
                <td style="padding:14px;text-align:center;font-size:13px;vertical-align:top">
                    <?= (int)$item['cantidad'] ?>
                </td>
                <td style="padding:14px;text-align:right;font-size:13px;vertical-align:top">
                    $<?= number_format($item['precio_unitario'], 0) ?>
                </td>
                <td style="padding:14px;text-align:right;font-size:13px;font-weight:700;vertical-align:top">
                    $<?= number_format($item['subtotal'], 0) ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- TOTALES Y CONDICIONES -->
    <div style="display:grid;grid-template-columns:1.2fr 0.8fr;gap:24px;margin-bottom:32px">
        <div style="font-size:12px;color:var(--fg);background:#fafafa;border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;line-height:1.6">
            <div style="font-weight:800;margin-bottom:6px;text-transform:uppercase;font-size:11px;color:var(--fg-secondary)">Términos & Condiciones Comerciales</div>
            <div><strong>• Forma de pago:</strong> <?= h($cot['condiciones']) ?></div>
            <div><strong>• Tiempo de entrega:</strong> <?= h($cot['tiempo_entrega']) ?></div>
            <div><strong>• Garantía técnica:</strong> <?= h($cot['garantia']) ?></div>
            <div style="margin-top:6px;padding-top:6px;border-top:1px dashed var(--border)">
                <strong>• Información Bancaria:</strong><br><?= h($banco_info) ?>
            </div>
        </div>

        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px;display:flex;flex-direction:column;gap:8px;font-size:13px">
            <div style="display:flex;justify-content:space-between">
                <span>Subtotal:</span>
                <strong>$<?= number_format($cot['subtotal'], 0) ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between">
                <span>IVA (<?= (float)$cot['iva_porcentaje'] ?>%):</span>
                <strong>$<?= number_format($cot['iva_monto'], 0) ?></strong>
            </div>
            <div style="border-top:2px solid var(--border);padding-top:10px;display:flex;justify-content:space-between;font-size:18px;font-weight:900;color:var(--fg)">
                <span>Total a Pagar:</span>
                <span style="color:var(--accent)">$<?= number_format($cot['total'], 0) ?></span>
            </div>
        </div>
    </div>

    <!-- FIRMAS -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:40px;padding-top:20px;border-top:1px solid var(--border)">
        <div>
            <div style="border-bottom:1px solid #94a3b8;width:200px;margin-bottom:8px;height:40px"></div>
            <div style="font-weight:700;font-size:13px"><?= h($emp_nombre) ?></div>
            <div style="font-size:11px;color:var(--fg-secondary)">Dpto. Comercial & Proyectos</div>
        </div>
        <div>
            <div style="border-bottom:1px solid #94a3b8;width:200px;margin-bottom:8px;height:40px"></div>
            <div style="font-weight:700;font-size:13px">Aceptado por el Cliente</div>
            <div style="font-size:11px;color:var(--fg-secondary)"><?= h($cot['empresa_nombre'] ?: 'Firma y Sello') ?></div>
        </div>
    </div>

</div>

<style>
@media print {
    body { background: #fff !important; }
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-wrap { margin-left: 0 !important; }
    .content { padding: 0 !important; max-width: 100% !important; }
    .print-paper { border: none !important; box-shadow: none !important; padding: 0 !important; }
}
</style>
