<?php
$cot_id = (int)($_GET['id'] ?? 0);
$cot = $db->querySingle("SELECT c.*, 
    ct.nombre, ct.apellido, ct.email, ct.telefono, ct.cargo, ct.ciudad as ct_ciudad, ct.direccion as ct_dir, 
    e.nombre as empresa_nombre, e.nit as empresa_nit, e.direccion as empresa_dir, e.ciudad as empresa_ciudad, e.email as empresa_email, e.telefono as empresa_tel
    FROM cotizaciones c 
    LEFT JOIN contactos ct ON c.contacto_id = ct.id 
    LEFT JOIN empresas e ON c.empresa_id = e.id 
    WHERE c.id = $cot_id", true);

if (!$cot) {
    echo "<div style='text-align:center;padding:50px'><h2>Cotización no encontrada</h2><a href='?page=cotizaciones' class='btn btn-secondary'>Volver a Cotizaciones</a></div>";
    return;
}

$items = $db->query("SELECT * FROM cotizacion_items WHERE cotizacion_id = $cot_id ORDER BY id ASC");
$clean_tel = limpiar_telefono_whatsapp($cot['telefono'] ?: $cot['empresa_tel']);

$emp_nombre = get_config($db, 'empresa_nombre', 'Power Pack');
$emp_nit = get_config($db, 'empresa_nit', '901.452.889-1');
$emp_tel = get_config($db, 'empresa_telefono', '+57 300 467 0474');
$emp_email = get_config($db, 'empresa_email', 'administrador@powerpack.site');
$emp_dir = get_config($db, 'empresa_direccion', 'Calle 161 # 54 - 25, Bogotá, Colombia');
$emp_website = get_config($db, 'empresa_website', 'https://powerpack.site');
$banco_info = get_config($db, 'banco_info', 'Bancolombia Cta Corriente # 104-582910-44 a nombre de Power Pack');

// Nombres y destinatarios
$cliente_empresa = $cot['empresa_nombre'] ?: ($cot['nombre'] ? $cot['nombre'].' '.$cot['apellido'] : 'Particular');
$cliente_nit = $cot['empresa_nit'] ?: 'No especificado';
$cliente_dir = $cot['empresa_dir'] ?: ($cot['ct_dir'] ?: 'Bogotá D.C., Colombia');
$cliente_ciudad = $cot['empresa_ciudad'] ?: ($cot['ct_ciudad'] ?: 'Bogotá D.C.');
$contacto_nombre = trim(($cot['nombre'] ?? '') . ' ' . ($cot['apellido'] ?? ''));
if (empty($contacto_nombre)) {
    $contacto_nombre = $cliente_empresa;
}
$contacto_cargo = $cot['cargo'] ?: 'Dirección / Compras / Operaciones';
$contacto_email = $cot['email'] ?: ($cot['empresa_email'] ?: 'No registrado');
$contacto_tel = $cot['telefono'] ?: ($cot['empresa_tel'] ?: 'No registrado');
$asunto_cot = !empty($cot['asunto']) ? $cot['asunto'] : 'Propuesta Técnico-Comercial de Maquinaria y Soluciones de Empaque';
$incluye_inst = !empty($cot['incluye_instalacion']) ? $cot['incluye_instalacion'] : 'Incluye servicio de instalación técnica en planta, calibración y capacitación operativa y de mantenimiento para su personal técnico.';

// Helper para fecha en español
function fecha_formal_es($fecha_raw) {
    if (!$fecha_raw) return date('d/m/Y');
    $ts = strtotime($fecha_raw);
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    $dia = date('d', $ts);
    $mes = $meses[(int)date('m', $ts)] ?? date('m', $ts);
    $ano = date('Y', $ts);
    return "Bogotá D.C., $dia de $mes de $ano";
}
$fecha_emision_texto = fecha_formal_es($cot['fecha']);
?>

<!-- ACCIONES SUPERIORES (NO IMPRIMIBLES) -->
<div class="no-print" style="margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;background:#fff;padding:16px 20px;border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow-sm)">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="?page=cotizaciones" class="btn btn-secondary btn-sm" style="display:flex;align-items:center;gap:6px">
            ← Volver a Cotizaciones
        </a>
        <span style="font-size:13px;color:var(--fg-secondary)">|</span>
        <span style="font-size:13px;font-weight:700;color:var(--fg)">Estado actual:</span>
        <form method="POST" style="margin:0;display:inline-block">
            <input type="hidden" name="cambiar_estado_cotizacion" value="1">
            <input type="hidden" name="cotizacion_id" value="<?= $cot['id'] ?>">
            <select name="nuevo_estado" onchange="this.form.submit()" style="padding:6px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);font-weight:800;font-size:12px;background:#f8fafc;cursor:pointer">
                <option value="borrador" <?= $cot['estado']=='borrador'?'selected':'' ?>>📝 Borrador</option>
                <option value="enviada" <?= $cot['estado']=='enviada'?'selected':'' ?>>✉️ Enviada al Cliente</option>
                <option value="aprobada" <?= $cot['estado']=='aprobada'?'selected':'' ?>>🏆 Aprobada / Ganada</option>
                <option value="rechazada" <?= $cot['estado']=='rechazada'?'selected':'' ?>>❌ Rechazada</option>
            </select>
        </form>
    </div>
    
    <div style="display:flex;gap:10px;align-items:center">
        <?php if($clean_tel): ?>
        <a href="https://wa.me/<?= $clean_tel ?>?text=<?= rawurlencode("Estimado(a) {$contacto_nombre}, un cordial saludo de Power Pack. Le comparto nuestra Propuesta Técnico-Comercial formal {$cot['numero']} por valor total de $" . number_format($cot['total'], 0) . " COP para {$cliente_empresa}. Quedamos atentos a coordinar los detalles técnicos y comerciales.") ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="display:flex;align-items:center;gap:6px">
            💬 Compartir por WhatsApp
        </a>
        <?php endif; ?>

        <button type="button" onclick="window.print()" class="btn btn-primary btn-sm" style="display:flex;align-items:center;gap:6px;background:#2c60a4">
            🖨️ Imprimir / Guardar en PDF
        </button>
    </div>
</div>

<!-- FORMATO DE PAPELERÍA INSTITUCIONAL MEMBRETADA POWER PACK -->
<div class="print-paper" style="background:#ffffff;border:1px solid #cbd5e1;border-radius:6px;padding:48px 52px;box-shadow:0 10px 25px -5px rgba(0,0,0,0.08);max-width:920px;margin:0 auto;color:#1e293b;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;line-height:1.5">
    
    <!-- 1. BANDA SUPERIOR BICOLOR INSTITUCIONAL POWER PACK -->
    <div class="bicolor-header-bar" style="display:flex;height:6px;width:100%;margin-bottom:24px;border-radius:4px;overflow:hidden">
        <div style="flex:7;background:#2c60a4"></div>
        <div style="flex:3;background:#ed1c29"></div>
    </div>

    <!-- 2. CABECERA CON LOGOTIPO OFICIAL, DATOS DE POWER PACK Y CONTROL DE PROPUESTA -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:20px;border-bottom:2px solid #e2e8f0;margin-bottom:24px;gap:20px">
        
        <!-- Identidad Institucional Power Pack -->
        <div style="max-width:540px">
            <div style="margin-bottom:10px">
                <img src="assets/logo-power-pack.png" alt="Power Pack" style="height:62px;width:auto;object-fit:contain;display:block">
            </div>
            <div style="font-size:15px;font-weight:900;color:#1e293b;letter-spacing:0.02em;text-transform:uppercase">
                <?= h($emp_nombre) ?>
            </div>
            <div style="font-size:11px;font-weight:700;color:#2c60a4;letter-spacing:0.04em;text-transform:uppercase;margin-bottom:6px">
                Soluciones Industriales de Empaque, Sellado & Encartonado Automático
            </div>
            <div style="font-size:12px;color:#475569;line-height:1.5">
                <strong>NIT:</strong> <?= h($emp_nit) ?><br>
                <strong>Sede & Showroom:</strong> <?= h($emp_dir) ?><br>
                <strong>PBX / WhatsApp:</strong> <?= h($emp_tel) ?> • <strong>Email:</strong> <?= h($emp_email) ?><br>
                <strong>Sitio Web Oficial:</strong> <a href="https://powerpack.com.co" target="_blank" style="color:#2c60a4;text-decoration:none;font-weight:700">www.powerpack.com.co</a>
            </div>
        </div>

        <!-- Consecutivo y Datos de Emisión -->
        <div style="min-width:260px;text-align:right">
            <div style="background:#2c60a4;color:#ffffff;padding:7px 14px;border-radius:6px 6px 0 0;font-size:11px;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;text-align:center">
                Propuesta Técnico-Comercial
            </div>
            <div style="background:#f8fafc;border:1px solid #cbd5e1;border-top:none;border-radius:0 0 6px 6px;padding:12px 14px;text-align:center">
                <div style="font-size:24px;font-weight:900;color:#ed1c29;letter-spacing:0.03em;line-height:1.1;margin-bottom:6px">
                    <?= h($cot['numero']) ?>
                </div>
                <div style="font-size:11.5px;color:#475569;line-height:1.5">
                    <strong>Emisión:</strong> <?= $fecha_emision_texto ?><br>
                    <strong>Vigencia:</strong> <?= (int)$cot['validez_dias'] ?> días calendario<br>
                    <span style="display:inline-block;margin-top:4px;padding:2px 8px;background:#e2e8f0;border-radius:10px;font-size:10.5px;font-weight:800;color:#1e293b">
                        Moneda: Pesos Colombianos (COP)
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. BLOQUE DE DESTINATARIO FORMAL (CARTA EJECUTIVA B2B) -->
    <div class="avoid-page-break" style="background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #2c60a4;border-radius:6px;padding:18px 22px;margin-bottom:24px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;font-size:12.5px;line-height:1.5">
            <div>
                <div style="font-size:10.5px;font-weight:800;color:#2c60a4;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px">
                    Señores Destinatarios:
                </div>
                <div style="font-size:15px;font-weight:900;color:#0f172a;margin-bottom:2px">
                    <?= h($cliente_empresa) ?>
                </div>
                <div style="color:#475569">
                    <strong>NIT / Identificación:</strong> <?= h($cliente_nit) ?>
                </div>
                <div style="color:#475569">
                    <strong>Dirección:</strong> <?= h($cliente_dir) ?>
                </div>
                <div style="color:#475569">
                    <strong>Ciudad:</strong> <?= h($cliente_ciudad) ?>
                </div>
            </div>

            <div>
                <div style="font-size:10.5px;font-weight:800;color:#2c60a4;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px">
                    Atención a:
                </div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;margin-bottom:2px">
                    <?= h($contacto_nombre) ?>
                </div>
                <div style="color:#475569">
                    <strong>Cargo:</strong> <?= h($contacto_cargo) ?>
                </div>
                <div style="color:#475569">
                    <strong>Email:</strong> <?= h($contacto_email) ?>
                </div>
                <div style="color:#475569">
                    <strong>Teléfono:</strong> <?= h($contacto_tel) ?>
                </div>
            </div>
        </div>

        <div style="margin-top:14px;padding-top:10px;border-top:1px dashed #cbd5e1;font-size:12.5px">
            <span style="font-weight:800;color:#0f172a;text-transform:uppercase;font-size:11px">Asunto:</span> 
            <span style="font-weight:700;color:#2c60a4"><?= h($asunto_cot) ?></span>
        </div>
    </div>

    <!-- 4. CARTA DE PRESENTACIÓN EJECUTIVA INSTITUCIONAL -->
    <div class="avoid-page-break" style="margin-bottom:26px;font-size:13px;color:#334155;line-height:1.65;text-align:justify">
        <div style="font-weight:800;color:#0f172a;margin-bottom:8px;font-size:13.5px">
            Apreciados señores:
        </div>

        <?php if (!empty($cot['carta_presentacion'])): ?>
            <div style="white-space:pre-line"><?= h($cot['carta_presentacion']) ?></div>
        <?php else: ?>
            <p style="margin:0 0 10px">
                Reciban un cordial y respetuoso saludo por parte del equipo directivo, técnico y comercial de <strong>Power Pack</strong>. Es para nosotros un gran honor presentar a su consideración nuestra propuesta técnico-comercial para el suministro e integración de maquinaria y soluciones de empaque industrial, concebidas para optimizar la productividad de sus procesos, maximizar la vida útil de sus productos y elevar sus estándares operativos con la más alta confiabilidad.
            </p>
            <p style="margin:0 0 10px">
                En <strong>Power Pack</strong> contamos con un sólido respaldo en ingeniería aplicada, suministrando equipos de alto rendimiento construidos bajo estrictas normas de calidad industrial, materiales de grado alimenticio y componentes electro-neumáticos de marcas líderes a nivel global. Asimismo, garantizamos un acompañamiento posventa integral, disponibilidad permanente de repuestos originales y soporte técnico especializado en Colombia.
            </p>
            <p style="margin:0">
                A continuación, ponemos a su disposición el desglose técnico de los equipos solicitados, junto con la correspondiente valoración económica y términos de suministro diseñados a la medida de sus requerimientos:
            </p>
        <?php endif; ?>
    </div>

    <!-- 5. TABLA DE ESPECIFICACIONES TÉCNICAS Y VALORACIÓN ECONÓMICA -->
    <div class="avoid-page-break" style="margin-bottom:24px">
        <div style="font-size:12px;font-weight:800;color:#2c60a4;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px;display:flex;align-items:center;gap:6px">
            <span>⚙️</span> Memoria Técnica & Desglose de Inversión
        </div>

        <table style="width:100%;border-collapse:collapse;border:1px solid #cbd5e1;border-radius:6px;overflow:hidden;font-size:12.5px">
            <thead>
                <tr style="background:#2c60a4;color:#ffffff">
                    <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;width:45px;color:#fff;border-right:1px solid rgba(255,255,255,0.15)">#</th>
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:#fff">Descripción Técnica & Especificaciones del Equipo</th>
                    <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;width:65px;color:#fff;border-left:1px solid rgba(255,255,255,0.15)">Cant.</th>
                    <th style="padding:10px 14px;text-align:right;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;width:145px;color:#fff;border-left:1px solid rgba(255,255,255,0.15)">Precio Unitario (COP)</th>
                    <th style="padding:10px 14px;text-align:right;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;width:150px;color:#fff;border-left:1px solid rgba(255,255,255,0.15)">Subtotal (COP)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $item_index = 1;
                while($item = $items->fetchArray(SQLITE3_ASSOC)): 
                    $bg_row = ($item_index % 2 === 0) ? '#f8fafc' : '#ffffff';
                ?>
                <tr style="border-bottom:1px solid #e2e8f0;background:<?= $bg_row ?>">
                    <td style="padding:12px;text-align:center;font-weight:800;color:#64748b;vertical-align:top;border-right:1px solid #e2e8f0">
                        <?= $item_index++ ?>
                    </td>
                    <td style="padding:12px 14px;line-height:1.55;vertical-align:top;color:#1e293b">
                        <div style="font-weight:700;color:#0f172a;margin-bottom:3px">
                            <?= h(strtok($item['descripcion'], "\n")) ?>
                        </div>
                        <?php 
                        $resto_desc = substr($item['descripcion'], strlen(strtok($item['descripcion'], "\n")));
                        if(trim($resto_desc)): 
                        ?>
                        <div style="font-size:11.5px;color:#475569;white-space:pre-line"><?= h(trim($resto_desc)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px;text-align:center;font-weight:700;vertical-align:top;border-left:1px solid #e2e8f0;color:#0f172a">
                        <?= (int)$item['cantidad'] ?>
                    </td>
                    <td style="padding:12px 14px;text-align:right;vertical-align:top;border-left:1px solid #e2e8f0;color:#334155">
                        $<?= number_format($item['precio_unitario'], 0, ',', '.') ?>
                    </td>
                    <td style="padding:12px 14px;text-align:right;font-weight:800;vertical-align:top;border-left:1px solid #e2e8f0;color:#0f172a">
                        $<?= number_format($item['subtotal'], 0, ',', '.') ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- 6. TOTALES Y CONDICIONES COMERCIALES B2B -->
    <div class="avoid-page-break" style="display:grid;grid-template-columns:1.25fr 0.85fr;gap:22px;margin-bottom:28px">
        
        <!-- Términos Comerciales & Garantía -->
        <div style="background:#fafafa;border:1px solid #e2e8f0;border-radius:6px;padding:16px 18px;font-size:12px;color:#334155;line-height:1.6">
            <div style="font-weight:900;color:#2c60a4;text-transform:uppercase;font-size:11px;letter-spacing:0.04em;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid #e2e8f0">
                📋 Términos & Condiciones Comerciales
            </div>
            <div style="margin-bottom:4px">
                <strong style="color:#0f172a">• Forma de Pago:</strong> <?= h($cot['condiciones']) ?>
            </div>
            <div style="margin-bottom:4px">
                <strong style="color:#0f172a">• Tiempo de Entrega:</strong> <?= h($cot['tiempo_entrega']) ?>
            </div>
            <div style="margin-bottom:4px">
                <strong style="color:#0f172a">• Garantía Técnica:</strong> <?= h($cot['garantia']) ?>
            </div>
            <div style="margin-bottom:4px">
                <strong style="color:#0f172a">• Puesta en Marcha:</strong> <?= h($incluye_inst) ?>
            </div>
            <div style="margin-top:8px;padding-top:8px;border-top:1px dashed #cbd5e1;font-size:11.5px;color:#1e293b">
                <strong style="color:#2c60a4">🏦 Consignación Bancaria Oficial:</strong><br>
                <?= h($banco_info) ?>
            </div>
        </div>

        <!-- Cuadro de Liquidación Financiera -->
        <div style="background:#f8fafc;border:1px solid #cbd5e1;border-top:4px solid #2c60a4;border-radius:6px;padding:16px 18px;display:flex;flex-direction:column;justify-content:center;gap:10px;font-size:13px">
            <div style="display:flex;justify-content:space-between;color:#475569">
                <span>Subtotal Neto:</span>
                <strong style="color:#0f172a">$<?= number_format($cot['subtotal'], 0, ',', '.') ?> COP</strong>
            </div>
            <div style="display:flex;justify-content:space-between;color:#475569">
                <span>IVA (<?= (float)$cot['iva_porcentaje'] ?>%):</span>
                <strong style="color:#0f172a">$<?= number_format($cot['iva_monto'], 0, ',', '.') ?> COP</strong>
            </div>
            <div style="border-top:2px solid #cbd5e1;padding-top:10px;margin-top:2px">
                <div style="font-size:10.5px;font-weight:800;text-transform:uppercase;color:#64748b;letter-spacing:0.04em">
                    Total Inversión Propuesta:
                </div>
                <div style="font-size:24px;font-weight:900;color:#ed1c29;letter-spacing:0.01em;line-height:1.2;margin-top:2px">
                    $<?= number_format($cot['total'], 0, ',', '.') ?> <span style="font-size:14px;color:#2c60a4">COP</span>
                </div>
                <div style="font-size:10px;color:#64748b;margin-top:4px">
                    Precios en Pesos Colombianos con IVA incluido.
                </div>
            </div>
        </div>
    </div>

    <!-- 7. BLOQUE DE FIRMAS Y ACEPTACIÓN -->
    <div class="avoid-page-break" style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:36px;padding-top:20px;border-top:1px solid #e2e8f0">
        <div>
            <div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:45px">
                Por POWER PACK (Oferente):
            </div>
            <div style="border-top:1.5px solid #0f172a;width:230px;padding-top:6px">
                <div style="font-weight:900;font-size:13px;color:#0f172a"><?= h($emp_nombre) ?></div>
                <div style="font-size:11px;font-weight:700;color:#2c60a4">Dpto. Comercial & Proyectos Industriales</div>
                <div style="font-size:11px;color:#64748b">NIT: <?= h($emp_nit) ?> • Bogotá, Colombia</div>
                <div style="font-size:11px;color:#64748b">Tel: <?= h($emp_tel) ?></div>
            </div>
        </div>

        <div>
            <div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:45px">
                Aceptación de la Propuesta / O.C. (Cliente):
            </div>
            <div style="border-top:1.5px solid #0f172a;width:230px;padding-top:6px">
                <div style="font-weight:900;font-size:13px;color:#0f172a"><?= h($contacto_nombre) ?></div>
                <div style="font-size:11px;color:#64748b"><?= h($cliente_empresa) ?></div>
                <div style="font-size:11px;color:#64748b">C.C. / NIT: <?= h($cliente_nit !== 'No especificado' ? $cliente_nit : '________________________') ?></div>
                <div style="font-size:11px;color:#64748b">Fecha de Aprobación: _____ / _____ / ________</div>
            </div>
        </div>
    </div>

    <!-- 8. PIE DE PÁGINA INSTITUCIONAL DE PAPELERÍA -->
    <div class="avoid-page-break" style="margin-top:36px;padding-top:18px;border-top:1px solid #e2e8f0">
        <div style="display:flex;height:4px;width:100%;margin-bottom:12px;border-radius:2px;overflow:hidden">
            <div style="flex:7;background:#2c60a4"></div>
            <div style="flex:3;background:#ed1c29"></div>
        </div>
        <div style="text-align:center;font-size:10.5px;color:#64748b;line-height:1.5">
            <strong>POWER PACK</strong> • Soluciones Industriales de Empaque, Sellado, Dosificado y Encartonado Automático<br>
            Sede Principal & Showroom: <?= h($emp_dir) ?> • Teléfono: <?= h($emp_tel) ?> • Correo: <?= h($emp_email) ?><br>
            Sitio Web Oficial: <a href="https://powerpack.com.co" target="_blank" style="color:#2c60a4;font-weight:700;text-decoration:none">www.powerpack.com.co</a>
            <div style="margin-top:4px;font-size:9.5px;color:#94a3b8">
                Documento comercial oficial. La información contenida en esta propuesta técnica y económica es de carácter confidencial y para uso exclusivo del destinatario.
            </div>
        </div>
    </div>

</div>

<!-- ESTILOS EXCLUSIVOS DE IMPRESIÓN Y DESCARGA PDF -->
<style>
@media print {
    @page {
        size: letter;
        margin: 10mm 12mm;
    }
    html, body {
        background: #ffffff !important;
        color: #000000 !important;
        font-size: 10.5pt !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .sidebar, .topbar, .mobile-sidebar-overlay, .no-print, button, .btn {
        display: none !important;
    }
    .main-wrap, .content {
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    .print-paper {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .avoid-page-break {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    table {
        page-break-inside: auto;
    }
    tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
