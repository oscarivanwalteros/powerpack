<?php
$emp_id = (int)($_GET['id'] ?? 0);
$emp = $db->querySingle("SELECT * FROM empresas WHERE id = $emp_id", true);

if (!$emp) {
    echo "<div style='text-align:center;padding:50px'><h2>Empresa no encontrada</h2><a href='?page=empresas' class='btn btn-secondary'>Volver a Empresas</a></div>";
    return;
}

$contactos = $db->query("SELECT * FROM contactos WHERE empresa_id = $emp_id ORDER BY nombre ASC");
$negocios = $db->query("SELECT * FROM negocios WHERE empresa_id = $emp_id ORDER BY fecha_creacion DESC");
$cotizaciones = $db->query("SELECT * FROM cotizaciones WHERE empresa_id = $emp_id ORDER BY id DESC");
$archivos = $db->query("SELECT * FROM archivos WHERE empresa_id = $emp_id ORDER BY id DESC");
?>

<div class="page-header">
    <div style="display:flex;align-items:center;gap:14px">
        <?= avatar_empresa($emp['nombre']) ?>
        <div>
            <h1><?= h($emp['nombre']) ?></h1>
            <p><?= $emp['nit'] ? 'NIT: ' . h($emp['nit']) . ' • ' : '' ?>📍 <?= h($emp['ciudad'] ?: 'Colombia') ?> • Sector: <?= h(ucwords($emp['sector'] ?: 'Industrial')) ?></p>
        </div>
    </div>
    <div style="display:flex;gap:10px">
        <a href="?page=empresas" class="btn btn-secondary btn-sm">← Todas las empresas</a>
        <button onclick="document.getElementById('modalNuevoContactoEmpresa').style.display='flex'" class="btn btn-primary btn-sm">+ Agregar Contacto a esta Empresa</button>
    </div>
</div>

<div style="display:grid;grid-template-columns:340px 1fr;gap:24px">

    <!-- COLUMNA IZQUIERDA: INFORMACIÓN DE LA EMPRESA & ARCHIVOS -->
    <div style="display:flex;flex-direction:column;gap:20px">
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow-sm)">
            <h3 style="font-size:14px;font-weight:800;margin-bottom:12px;border-bottom:1px solid var(--border);padding-bottom:8px">📋 Datos Corporativos</h3>
            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px">
                <div><div style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Teléfono Planta</div><div><?= h($emp['telefono'] ?: '—') ?></div></div>
                <div><div style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Email Corporativo</div><div><?= h($emp['email'] ?: '—') ?></div></div>
                <div><div style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Dirección de Planta</div><div><?= h($emp['direccion'] ?: '—') ?></div></div>
                <?php if($emp['website']): ?>
                <div><div style="font-size:11px;font-weight:700;color:var(--fg-secondary)">Sitio Web</div><div><a href="<?= h($emp['website']) ?>" target="_blank" style="color:var(--email)"><?= h($emp['website']) ?></a></div></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- MÓDULO DE ARCHIVOS Y DOCUMENTOS ADJUNTOS -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow-sm)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <h3 style="font-size:14px;font-weight:800">📎 Documentos & Fichas</h3>
                <button onclick="document.getElementById('modalSubirArchivo').style.display='flex'" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:11px">+ Subir</button>
            </div>

            <div style="display:flex;flex-direction:column;gap:8px">
                <?php 
                $hay_archivos = false;
                while($arc = $archivos->fetchArray(SQLITE3_ASSOC)): 
                    $hay_archivos = true;
                ?>
                <div style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);display:flex;justify-content:space-between;align-items:center;font-size:12px;background:#fafafa">
                    <div style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= h($arc['nombre_original']) ?>">
                        📄 <?= h($arc['nombre_original']) ?>
                    </div>
                    <a href="uploads/<?= h($arc['ruta']) ?>" target="_blank" download style="color:var(--email);font-weight:700">Descargar</a>
                </div>
                <?php endwhile; ?>
                <?php if(!$hay_archivos): ?>
                <div style="font-size:12px;color:var(--fg-secondary);text-align:center;padding:12px 0">
                    No hay archivos adjuntos aún (RUT, fichas técnicas, contratos).
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- COLUMNA DERECHA: CONTACTOS Y OPORTUNIDADES ASOCIADAS -->
    <div style="display:flex;flex-direction:column;gap:24px">

        <!-- CONTACTOS EN ESTA EMPRESA (Múltiples Roles) -->
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow-sm)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                <h3 style="font-size:15px;font-weight:800">👥 Equipo & Contactos en esta Fábrica</h3>
                <span style="font-size:12px;color:var(--fg-secondary)">Personas de compras, planta y gerencia</span>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:12px">
                <?php 
                $hay_contactos = false;
                while($ct = $contactos->fetchArray(SQLITE3_ASSOC)): 
                    $hay_contactos = true;
                    $tel_wa = limpiar_telefono_whatsapp($ct['telefono']);
                ?>
                <div style="border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;display:flex;justify-content:space-between;align-items:flex-start;background:#fcfcfd">
                    <div style="display:flex;align-items:center;gap:10px">
                        <?= avatar_iniciales($ct['nombre'], $ct['apellido']) ?>
                        <div>
                            <a href="?page=detalle&id=<?= $ct['id'] ?>" style="font-weight:800;color:var(--fg);font-size:14px"><?= h($ct['nombre'] . ' ' . $ct['apellido']) ?></a>
                            <div style="font-size:12px;color:var(--fg-secondary)"><?= h($ct['cargo'] ?: 'Contacto') ?></div>
                            <div style="font-size:11px;color:var(--fg-secondary);margin-top:2px"><?= h($ct['email']) ?></div>
                        </div>
                    </div>
                    <?php if($tel_wa): ?>
                    <a href="https://wa.me/<?= $tel_wa ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="padding:4px 8px;font-size:11px" title="Abrir WhatsApp">💬</a>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
                <?php if(!$hay_contactos): ?>
                <div style="font-size:13px;color:var(--fg-secondary);padding:14px 0">
                    No hay personas registradas bajo esta cuenta aún.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- OPORTUNIDADES Y COTIZACIONES DE LA EMPRESA -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            
            <!-- Negocios -->
            <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow-sm)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <h3 style="font-size:14px;font-weight:800">💼 Negocios en Pipeline</h3>
                    <a href="?page=nuevo_negocio&empresa_id=<?= $emp['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:11px;padding:2px 8px">+ Negocio</a>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <?php while($n = $negocios->fetchArray(SQLITE3_ASSOC)): ?>
                    <div style="padding:10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fafafa">
                        <div style="display:flex;justify-content:space-between">
                            <strong style="font-size:13px"><?= h($n['nombre']) ?></strong>
                            <strong style="color:var(--fg)">$<?= number_format($n['monto'], 0) ?></strong>
                        </div>
                        <div style="margin-top:4px"><?= etapa_badge($n['etapa']) ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Cotizaciones -->
            <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow-sm)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <h3 style="font-size:14px;font-weight:800">📄 Cotizaciones Formales</h3>
                    <a href="?page=nueva_cotizacion&empresa_id=<?= $emp['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:11px;padding:2px 8px">+ Cotizar</a>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <?php while($cot = $cotizaciones->fetchArray(SQLITE3_ASSOC)): ?>
                    <div style="padding:10px;border:1px solid var(--border);border-radius:var(--radius-sm);display:flex;justify-content:space-between;align-items:center;background:#fafafa">
                        <div>
                            <a href="?page=ver_cotizacion&id=<?= $cot['id'] ?>" style="font-weight:800;color:var(--accent)"><?= h($cot['numero']) ?></a>
                            <div style="font-size:11px;color:var(--fg-secondary)"><?= $cot['fecha'] ?></div>
                        </div>
                        <div style="text-align:right">
                            <strong>$<?= number_format($cot['total'], 0) ?></strong>
                            <div><span style="font-size:10px;font-weight:700;text-transform:uppercase"><?= $cot['estado'] ?></span></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- MODAL SUBIR ARCHIVO -->
<div id="modalSubirArchivo" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:200;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)">
    <div style="background:#fff;border-radius:var(--radius);max-width:450px;width:100%;padding:24px;box-shadow:var(--shadow-lg)">
        <h3 style="font-size:16px;font-weight:800;margin-bottom:14px">📎 Adjuntar Documento a esta Empresa</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="subir_archivo" value="1">
            <input type="hidden" name="empresa_id" value="<?= $emp['id'] ?>">
            <div class="form-group">
                <label>Seleccionar Archivo (PDF, Word, Excel, Imagen)</label>
                <input type="file" name="archivo" required style="padding:8px">
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
                <button type="button" onclick="document.getElementById('modalSubirArchivo').style.display='none'" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Subir Documento</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL NUEVO CONTACTO EN ESTA EMPRESA -->
<div id="modalNuevoContactoEmpresa" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:200;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)">
    <div style="background:#fff;border-radius:var(--radius);max-width:550px;width:100%;padding:24px;box-shadow:var(--shadow-lg)">
        <h3 style="font-size:16px;font-weight:800;margin-bottom:14px">👥 Agregar Contacto a <?= h($emp['nombre']) ?></h3>
        <form method="POST">
            <input type="hidden" name="guardar_contacto" value="1">
            <input type="hidden" name="empresa_id" value="<?= $emp['id'] ?>">
            <input type="hidden" name="empresa" value="<?= h($emp['nombre']) ?>">
            <input type="hidden" name="sector" value="<?= h($emp['sector']) ?>">
            <input type="hidden" name="ciudad" value="<?= h($emp['ciudad']) ?>">
            
            <div class="form-row">
                <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" required></div>
                <div class="form-group"><label>Apellido</label><input type="text" name="apellido"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Cargo / Rol</label><input type="text" name="cargo" placeholder="Ej: Gerente de Compras"></div>
                <div class="form-group"><label>Teléfono / WhatsApp</label><input type="text" name="telefono" placeholder="+57 300 000 0000"></div>
            </div>
            <div class="form-group"><label>Email Corporativo</label><input type="email" name="email"></div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
                <button type="button" onclick="document.getElementById('modalNuevoContactoEmpresa').style.display='none'" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Vincular Contacto</button>
            </div>
        </form>
    </div>
</div>
