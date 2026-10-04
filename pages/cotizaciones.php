<?php
$filtro_estado = $_GET['estado'] ?? '';
$sql = "SELECT c.*, ct.nombre as c_nombre, ct.apellido as c_apellido, ct.telefono, ct.email, e.nombre as empresa_nombre 
        FROM cotizaciones c 
        LEFT JOIN contactos ct ON c.contacto_id = ct.id 
        LEFT JOIN empresas e ON c.empresa_id = e.id 
        WHERE 1=1";

if ($filtro_estado) {
    $sql .= " AND c.estado = '" . SQLite3::escapeString($filtro_estado) . "'";
}
$sql .= " ORDER BY c.id DESC";

$cotizaciones = $db->query($sql);
$total_cotizaciones_monto = $db->querySingle("SELECT COALESCE(SUM(total), 0) FROM cotizaciones WHERE estado != 'rechazada'");
$count_aprobadas = $db->querySingle("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'aprobada'");
?>

<div class="page-header">
    <div>
        <h1>Gestión de Cotizaciones Comerciales</h1>
        <p>Crea, imprime en PDF y envía propuestas formales con términos de pago y garantía. Total activo: <strong style="color:var(--fg)">$<?= number_format($total_cotizaciones_monto, 0) ?></strong></p>
    </div>
    <a href="?page=nueva_cotizacion" class="btn btn-primary">+ Nueva Cotización Formal</a>
</div>

<div class="table-wrap">
    <div class="table-toolbar">
        <div class="tabs">
            <a href="?page=cotizaciones" class="tab <?= !$filtro_estado ? 'active' : '' ?>">Todas</a>
            <a href="?page=cotizaciones&estado=borrador" class="tab <?= $filtro_estado==='borrador' ? 'active' : '' ?>">Borradores</a>
            <a href="?page=cotizaciones&estado=enviada" class="tab <?= $filtro_estado==='enviada' ? 'active' : '' ?>">Enviadas</a>
            <a href="?page=cotizaciones&estado=aprobada" class="tab <?= $filtro_estado==='aprobada' ? 'active' : '' ?>">Aprobadas (<?= $count_aprobadas ?>)</a>
            <a href="?page=cotizaciones&estado=rechazada" class="tab <?= $filtro_estado==='rechazada' ? 'active' : '' ?>">Rechazadas</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Cotización</th>
                <th>Cliente & Empresa</th>
                <th>Fecha Emisión</th>
                <th>Validez</th>
                <th>Subtotal</th>
                <th>Total (con IVA)</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $hay = false;
            while($cot = $cotizaciones->fetchArray(SQLITE3_ASSOC)): 
                $hay = true;
                $tel_wa = limpiar_telefono_whatsapp($cot['telefono']);
                $badge_class = 'badge-lead';
                if ($cot['estado'] === 'aprobada') $badge_class = 'badge-ganado';
                elseif ($cot['estado'] === 'enviada') $badge_class = 'badge-cotizacion';
                elseif ($cot['estado'] === 'rechazada') $badge_class = 'badge-perdido';
            ?>
            <tr>
                <td>
                    <a href="?page=ver_cotizacion&id=<?= $cot['id'] ?>" class="link" style="font-weight:800;font-size:14px">
                        <?= h($cot['numero']) ?>
                    </a>
                </td>
                <td>
                    <div style="font-weight:700"><?= h($cot['empresa_nombre'] ?: ($cot['c_nombre'] . ' ' . $cot['c_apellido'])) ?></div>
                    <?php if($cot['c_nombre']): ?>
                    <div style="font-size:12px;color:var(--fg-secondary)"><?= h($cot['c_nombre'] . ' ' . $cot['c_apellido']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y', strtotime($cot['fecha'])) ?></td>
                <td><?= (int)$cot['validez_dias'] ?> días</td>
                <td>$<?= number_format($cot['subtotal'], 0) ?></td>
                <td><strong style="font-size:14px;color:var(--fg)">$<?= number_format($cot['total'], 0) ?></strong></td>
                <td><span class="badge <?= $badge_class ?>"><?= strtoupper($cot['estado']) ?></span></td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="?page=ver_cotizacion&id=<?= $cot['id'] ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11px" title="Ver e imprimir PDF">
                            📄 Ver / PDF
                        </a>
                        <?php if($tel_wa): ?>
                        <a href="https://wa.me/<?= $tel_wa ?>?text=<?= rawurlencode("Hola {$cot['c_nombre']}, te comparto la cotización formal {$cot['numero']} por un valor total de $" . number_format($cot['total'], 0) . ". ¿Tienes 5 minutos para que la revisemos?") ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="padding:4px 8px;font-size:11px" title="Enviar por WhatsApp">
                            💬
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>

            <?php if(!$hay): ?>
            <tr>
                <td colspan="8" style="text-align:center;padding:50px 20px;color:var(--fg-secondary)">
                    No hay cotizaciones registradas en este estado.
                    <div style="margin-top:10px">
                        <a href="?page=nueva_cotizacion" class="btn btn-primary btn-sm">+ Crear Primera Cotización</a>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
