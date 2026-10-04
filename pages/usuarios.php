<?php
/**
 * pages/usuarios.php - Gestión de Usuarios y Accesos
 * Solo accesible para usuarios con rol 'admin'
 */

if (($_SESSION['user_rol'] ?? '') !== 'admin') {
    echo "<div class='alert alert-danger' style='margin:40px auto;max-width:600px;text-align:center'>
        <h3>⛔ Acceso Denegado</h3>
        <p>Solo los usuarios con rol de <strong>Administrador</strong> pueden gestionar usuarios y permisos del sistema.</p>
        <a href='?page=dashboard' class='btn btn-secondary' style='margin-top:14px'>Volver al Panel</a>
    </div>";
    return;
}

$msg_user = '';
$err_user = '';

// 1. Crear Nuevo Usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $rol = in_array($_POST['rol'] ?? '', ['admin', 'asesor']) ? $_POST['rol'] : 'asesor';

    if (empty($nombre) || empty($email) || empty($password)) {
        $err_user = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err_user = 'El correo electrónico ingresado no es válido.';
    } else {
        // Verificar si el correo ya existe
        $existe = $db->querySingle("SELECT COUNT(*) FROM usuarios WHERE LOWER(email) = '" . SQLite3::escapeString($email) . "'");
        if ($existe > 0) {
            $err_user = "Ya existe un usuario registrado con el correo $email.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol, activo, fecha_creacion) VALUES (?, ?, ?, ?, 1, datetime('now'))");
            $stmt->bindValue(1, $nombre, SQLITE3_TEXT);
            $stmt->bindValue(2, $email, SQLITE3_TEXT);
            $stmt->bindValue(3, $hash, SQLITE3_TEXT);
            $stmt->bindValue(4, $rol, SQLITE3_TEXT);
            $stmt->execute();
            $msg_user = "Usuario $nombre creado exitosamente con rol " . ($rol === 'admin' ? 'Administrador' : 'Asesor Comercial') . ".";
        }
    }
}

// 2. Editar Usuario Existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_usuario'])) {
    $uid = (int)($_POST['usuario_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $rol = in_array($_POST['rol'] ?? '', ['admin', 'asesor']) ? $_POST['rol'] : 'asesor';
    $activo = isset($_POST['activo']) ? 1 : 0;
    $nueva_pass = trim($_POST['nueva_password'] ?? '');

    if ($uid > 0 && !empty($nombre) && !empty($email)) {
        // Validar que el correo no esté ocupado por otro usuario
        $stmt_chk = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE LOWER(email) = ? AND id != ?");
        $stmt_chk->bindValue(1, $email, SQLITE3_TEXT);
        $stmt_chk->bindValue(2, $uid, SQLITE3_INTEGER);
        $chk = $stmt_chk->execute()->fetchArray(SQLITE3_NUM)[0];

        if ($chk > 0) {
            $err_user = "El correo $email ya pertenece a otro usuario.";
        } else {
            // Evitar que el administrador actual se desactive o se quite el rol admin a sí mismo
            if ($uid === (int)$_SESSION['user_id']) {
                $activo = 1;
                $rol = 'admin';
            }

            if (!empty($nueva_pass)) {
                $new_hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
                $stmt_up = $db->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ?, activo = ?, password_hash = ? WHERE id = ?");
                $stmt_up->bindValue(1, $nombre, SQLITE3_TEXT);
                $stmt_up->bindValue(2, $email, SQLITE3_TEXT);
                $stmt_up->bindValue(3, $rol, SQLITE3_TEXT);
                $stmt_up->bindValue(4, $activo, SQLITE3_INTEGER);
                $stmt_up->bindValue(5, $new_hash, SQLITE3_TEXT);
                $stmt_up->bindValue(6, $uid, SQLITE3_INTEGER);
                $stmt_up->execute();
            } else {
                $stmt_up = $db->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ?, activo = ? WHERE id = ?");
                $stmt_up->bindValue(1, $nombre, SQLITE3_TEXT);
                $stmt_up->bindValue(2, $email, SQLITE3_TEXT);
                $stmt_up->bindValue(3, $rol, SQLITE3_TEXT);
                $stmt_up->bindValue(4, $activo, SQLITE3_INTEGER);
                $stmt_up->bindValue(5, $uid, SQLITE3_INTEGER);
                $stmt_up->execute();
            }

            // Si se editó el propio usuario logueado, actualizar sesión
            if ($uid === (int)$_SESSION['user_id']) {
                $_SESSION['user_nombre'] = $nombre;
                $_SESSION['user_email'] = $email;
            }

            $msg_user = "Datos del usuario $nombre actualizados correctamente.";
        }
    }
}

// 3. Alternar Estado (Activar / Desactivar)
if (isset($_GET['toggle_activo']) && isset($_GET['uid'])) {
    $uid = (int)$_GET['uid'];
    if ($uid === (int)$_SESSION['user_id']) {
        $err_user = "No puedes desactivar tu propia cuenta de administrador en sesión.";
    } else {
        $estado_actual = (int)$db->querySingle("SELECT activo FROM usuarios WHERE id = $uid");
        $nuevo_estado = $estado_actual ? 0 : 1;
        $db->exec("UPDATE usuarios SET activo = $nuevo_estado WHERE id = $uid");
        $msg_user = "Estado del usuario actualizado.";
    }
}

// 4. Eliminar Usuario
if (isset($_GET['eliminar_uid'])) {
    $uid = (int)$_GET['eliminar_uid'];
    if ($uid === (int)$_SESSION['user_id']) {
        $err_user = "No puedes eliminar tu propia cuenta de administrador en sesión.";
    } else {
        $u_nom = $db->querySingle("SELECT nombre FROM usuarios WHERE id = $uid");
        $db->exec("DELETE FROM usuarios WHERE id = $uid");
        $msg_user = "El usuario $u_nom ha sido eliminado del sistema.";
    }
}

// Consultar lista de usuarios
$usuarios = $db->query("SELECT * FROM usuarios ORDER BY rol ASC, id ASC");
?>

<div class="page-header">
    <div>
        <h1>👥 Gestión de Usuarios & Accesos</h1>
        <p>Crea y administra las cuentas de acceso para el equipo comercial y administradores de Power Pack</p>
    </div>
    <div>
        <button type="button" onclick="document.getElementById('modalNuevoUsuario').style.display='flex'" class="btn btn-primary btn-sm" style="background:#2c60a4">
            + Nuevo Usuario
        </button>
    </div>
</div>

<?php if ($msg_user): ?>
<div class="alert alert-success" style="margin-bottom:20px">
    ✅ <?= h($msg_user) ?>
</div>
<?php endif; ?>

<?php if ($err_user): ?>
<div class="alert alert-danger" style="margin-bottom:20px">
    ⚠️ <?= h($err_user) ?>
</div>
<?php endif; ?>

<div class="table-wrap">
    <table>
        <thead>
            <tr style="background:#f8fafc">
                <th style="padding:12px 16px">Usuario / Nombre</th>
                <th style="padding:12px 16px">Correo Electrónico</th>
                <th style="padding:12px 16px">Rol / Permisos</th>
                <th style="padding:12px 16px">Estado</th>
                <th style="padding:12px 16px">Último Ingreso</th>
                <th style="padding:12px 16px;text-align:right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($u = $usuarios->fetchArray(SQLITE3_ASSOC)): 
                $es_yo = ($u['id'] === (int)$_SESSION['user_id']);
            ?>
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:12px 16px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <?= avatar_iniciales($u['nombre'], '') ?>
                        <div>
                            <strong style="color:var(--fg);font-size:14px"><?= h($u['nombre']) ?></strong>
                            <?php if ($es_yo): ?>
                            <span style="font-size:10px;background:#eff6ff;color:#2c60a4;font-weight:800;padding:2px 6px;border-radius:4px;margin-left:6px">Tú</span>
                            <?php endif; ?>
                            <div style="font-size:11px;color:var(--fg-secondary)">Creado: <?= date('d/m/Y', strtotime($u['fecha_creacion'])) ?></div>
                        </div>
                    </div>
                </td>
                <td style="padding:12px 16px;font-size:13px;color:var(--fg)">
                    <?= h($u['email']) ?>
                </td>
                <td style="padding:12px 16px">
                    <?php if ($u['rol'] === 'admin'): ?>
                    <span style="display:inline-block;padding:3px 10px;border-radius:20px;background:#fef2f2;color:#ed1c29;font-size:11px;font-weight:800;border:1px solid #fecaca">
                        👑 Administrador (Acceso Total)
                    </span>
                    <?php else: ?>
                    <span style="display:inline-block;padding:3px 10px;border-radius:20px;background:#eff6ff;color:#2c60a4;font-size:11px;font-weight:800;border:1px solid #bfdbfe">
                        💼 Asesor Comercial
                    </span>
                    <?php endif; ?>
                </td>
                <td style="padding:12px 16px">
                    <?php if ($u['activo']): ?>
                    <span style="color:#059669;font-weight:700;font-size:12px;display:flex;align-items:center;gap:4px">
                        ● Activo
                    </span>
                    <?php else: ?>
                    <span style="color:#94a3b8;font-weight:700;font-size:12px;display:flex;align-items:center;gap:4px">
                        ○ Inactivo
                    </span>
                    <?php endif; ?>
                </td>
                <td style="padding:12px 16px;font-size:12px;color:var(--fg-secondary)">
                    <?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Sin ingresos aún' ?>
                </td>
                <td style="padding:12px 16px;text-align:right">
                    <div style="display:flex;gap:8px;justify-content:flex-end">
                        <button type="button" 
                                onclick='abrirModalEditar(<?= json_encode($u) ?>)'
                                class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:11px">
                            ✏️ Editar
                        </button>
                        
                        <?php if (!$es_yo): ?>
                        <a href="?page=usuarios&toggle_activo=1&uid=<?= $u['id'] ?>" 
                           class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:11px;color:<?= $u['activo'] ? '#d97706' : '#059669' ?>">
                            <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                        </a>
                        <a href="?page=usuarios&eliminar_uid=<?= $u['id'] ?>" 
                           onclick="return confirm('¿Seguro que deseas eliminar definitivamente a <?= h($u['nombre']) ?>?');"
                           class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11px;color:#ef4444" title="Eliminar">
                            🗑️
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- ==============================================
     MODAL 1: CREAR NUEVO USUARIO
     ============================================== -->
<div id="modalNuevoUsuario" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.65);z-index:9999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px)">
    <div style="background:#fff;border-radius:12px;max-width:500px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);overflow:hidden">
        <div style="background:#0f172a;padding:18px 24px;border-bottom:3px solid #2c60a4;display:flex;justify-content:space-between;align-items:center">
            <h3 style="color:#fff;margin:0;font-size:16px;font-weight:800">👤 Registrar Nuevo Usuario</h3>
            <button onclick="document.getElementById('modalNuevoUsuario').style.display='none'" style="background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer">&times;</button>
        </div>

        <form method="POST" style="padding:22px;display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="crear_usuario" value="1">

            <div class="form-group">
                <label style="font-size:12px;font-weight:700">Nombre Completo:</label>
                <input type="text" name="nombre" required placeholder="Ej: Juan Camilo Pérez" style="width:100%">
            </div>

            <div class="form-group">
                <label style="font-size:12px;font-weight:700">Correo Electrónico (Acceso):</label>
                <input type="email" name="email" required placeholder="juan.perez@powerpack.com.co" style="width:100%">
            </div>

            <div class="form-group">
                <label style="font-size:12px;font-weight:700">Contraseña Inicial:</label>
                <input type="password" name="password" required placeholder="••••••••••••" style="width:100%">
            </div>

            <div class="form-group">
                <label style="font-size:12px;font-weight:700">Rol de Acceso:</label>
                <select name="rol" style="width:100%;padding:8px 10px;font-size:13px">
                    <option value="asesor">💼 Asesor Comercial (Gestión de prospectos, cotizaciones y WhatsApp)</option>
                    <option value="admin">👑 Administrador (Acceso total, configuración y usuarios)</option>
                </select>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:14px">
                <button type="button" onclick="document.getElementById('modalNuevoUsuario').style.display='none'" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="background:#2c60a4">Crear Usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- ==============================================
     MODAL 2: EDITAR USUARIO / RESTABLECER CONTRASEÑA
     ============================================== -->
<div id="modalEditarUsuario" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.65);z-index:9999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px)">
    <div style="background:#fff;border-radius:12px;max-width:500px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);overflow:hidden">
        <div style="background:#0f172a;padding:18px 24px;border-bottom:3px solid #2c60a4;display:flex;justify-content:space-between;align-items:center">
            <h3 style="color:#fff;margin:0;font-size:16px;font-weight:800">✏️ Editar Usuario</h3>
            <button onclick="document.getElementById('modalEditarUsuario').style.display='none'" style="background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer">&times;</button>
        </div>

        <form method="POST" style="padding:22px;display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="editar_usuario" value="1">
            <input type="hidden" name="usuario_id" id="edit_id">

            <div class="form-group">
                <label style="font-size:12px;font-weight:700">Nombre Completo:</label>
                <input type="text" name="nombre" id="edit_nombre" required style="width:100%">
            </div>

            <div class="form-group">
                <label style="font-size:12px;font-weight:700">Correo Electrónico:</label>
                <input type="email" name="email" id="edit_email" required style="width:100%">
            </div>

            <div class="form-group">
                <label style="font-size:12px;font-weight:700">Cambiar Contraseña (dejar en blanco para no cambiar):</label>
                <input type="password" name="nueva_password" placeholder="Nueva contraseña opcional..." style="width:100%">
            </div>

            <div class="form-group">
                <label style="font-size:12px;font-weight:700">Rol de Acceso:</label>
                <select name="rol" id="edit_rol" style="width:100%;padding:8px 10px;font-size:13px">
                    <option value="asesor">💼 Asesor Comercial</option>
                    <option value="admin">👑 Administrador</option>
                </select>
            </div>

            <div class="form-group" style="margin-top:6px">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="activo" id="edit_activo" value="1">
                    <span>Usuario activo (permite iniciar sesión)</span>
                </label>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:14px">
                <button type="button" onclick="document.getElementById('modalEditarUsuario').style.display='none'" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="background:#2c60a4">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalEditar(u) {
    document.getElementById('edit_id').value = u.id;
    document.getElementById('edit_nombre').value = u.nombre;
    document.getElementById('edit_email').value = u.email;
    document.getElementById('edit_rol').value = u.rol;
    document.getElementById('edit_activo').checked = (u.activo == 1);
    document.getElementById('modalEditarUsuario').style.display = 'flex';
}
</script>
