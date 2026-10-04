<?php
$q = trim($_GET['q'] ?? '');
$sql = "SELECT e.*, 
        (SELECT COUNT(*) FROM contactos WHERE empresa_id = e.id) as total_contactos,
        (SELECT COUNT(*) FROM negocios WHERE empresa_id = e.id) as total_negocios,
        (SELECT COALESCE(SUM(monto), 0) FROM negocios WHERE empresa_id = e.id AND etapa NOT IN ('perdido')) as total_pipeline
        FROM empresas e WHERE 1=1";

if ($q !== '') {
    $q_esc = SQLite3::escapeString($q);
    $sql .= " AND (e.nombre LIKE '%$q_esc%' OR e.nit LIKE '%$q_esc%' OR e.ciudad LIKE '%$q_esc%' OR e.sector LIKE '%$q_esc%')";
}
$sql .= " ORDER BY e.nombre ASC";

$empresas = $db->query($sql);
$total_empresas = $db->querySingle("SELECT COUNT(*) FROM empresas");
?>

<div class="page-header">
    <div>
        <h1>Cuentas & Empresas B2B</h1>
        <p>Administra las fábricas, plantas industriales y clientes corporativos con múltiples contactos</p>
    </div>
    <button onclick="document.getElementById('modalEmpresa').style.display='flex'" class="btn btn-primary">+ Nueva Empresa</button>
</div>

<div class="table-wrap">
    <div class="table-toolbar">
        <form style="display:flex;gap:8px;flex:1;max-width:400px" method="GET">
            <input type="hidden" name="page" value="empresas">
            <input type="text" name="q" placeholder="Buscar por nombre, NIT, ciudad..." value="<?= h($q) ?>" style="padding:7px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;width:100%">
        </form>
        <span style="font-size:12px;color:var(--fg-secondary)"><?= $total_empresas ?> empresas registradas</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Empresa / Fábrica</th>
                <th>NIT</th>
                <th>Ciudad</th>
                <th>Sector</th>
                <th>Contactos</th>
                <th>Oportunidades</th>
                <th>Valor en Pipeline</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $hay = false;
            while($emp = $empresas->fetchArray(SQLITE3_ASSOC)): 
                $hay = true;
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:12px">
                        <?= avatar_empresa($emp['nombre']) ?>
                        <div>
                            <a href="?page=empresa_detalle&id=<?= $emp['id'] ?>" class="link" style="font-weight:800;font-size:14px;color:var(--fg)">
                                <?= h($emp['nombre']) ?>
                            </a>
                            <?php if($emp['website']): ?>
                            <div style="font-size:11px;color:var(--fg-secondary)">
                                <a href="<?= h($emp['website']) ?>" target="_blank" style="color:var(--email)"><?= h($emp['website']) ?></a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td><?= h($emp['nit'] ?: '—') ?></td>
                <td><?= h($emp['ciudad'] ?: '—') ?></td>
                <td><span style="font-size:12px;color:var(--fg-secondary)"><?= h(ucwords($emp['sector'] ?: 'General')) ?></span></td>
                <td><strong><?= (int)$emp['total_contactos'] ?></strong> contactos</td>
                <td><strong><?= (int)$emp['total_negocios'] ?></strong> negocios</td>
                <td><strong style="color:var(--fg);font-size:14px">$<?= number_format($emp['total_pipeline'], 0) ?></strong></td>
                <td>
                    <a href="?page=empresa_detalle&id=<?= $emp['id'] ?>" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:12px">
                        Ver Cuenta B2B →
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>

            <?php if(!$hay): ?>
            <tr>
                <td colspan="8" style="text-align:center;padding:50px 20px;color:var(--fg-secondary)">
                    No hay empresas registradas con ese criterio.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL NUEVA EMPRESA -->
<div id="modalEmpresa" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:200;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)">
    <div style="background:#fff;border-radius:var(--radius);max-width:600px;width:100%;padding:24px;box-shadow:var(--shadow-lg)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <h2 style="font-size:18px;font-weight:800">🏢 Registrar Cuenta / Empresa B2B</h2>
            <button onclick="document.getElementById('modalEmpresa').style.display='none'" style="font-size:20px;color:#64748b;background:none">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="guardar_empresa" value="1">
            <div class="form-row">
                <div class="form-group"><label>Nombre de la Empresa *</label><input type="text" name="nombre" placeholder="Ej: Compañía Colombiana de Alimentos SAS" required></div>
                <div class="form-group"><label>NIT / Identificación Fiscal</label><input type="text" name="nit" placeholder="860.000.000-1"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Sector / Industria</label>
                    <select name="sector">
                        <option value="alimentos">Alimentos</option>
                        <option value="bebidas">Bebidas</option>
                        <option value="farmacéutica">Farmacéutica</option>
                        <option value="cosmética">Cosmética</option>
                        <option value="logística">Logística & Empaque</option>
                        <option value="industrial">Manufactura / Industrial</option>
                    </select>
                </div>
                <div class="form-group"><label>Ciudad</label><input type="text" name="ciudad" placeholder="Bogotá, Medellín, Barranquilla..."></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Teléfono Conmutador</label><input type="text" name="telefono" placeholder="+57 1 600 0000"></div>
                <div class="form-group"><label>Email Corporativo</label><input type="email" name="email" placeholder="contacto@empresa.com"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Sitio Web</label><input type="url" name="website" placeholder="https://empresa.com"></div>
                <div class="form-group"><label>Dirección de Planta / Oficinas</label><input type="text" name="direccion" placeholder="Calle 13 # 68-50, Zona Industrial"></div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
                <button type="button" onclick="document.getElementById('modalEmpresa').style.display='none'" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Empresa</button>
            </div>
        </form>
    </div>
</div>
