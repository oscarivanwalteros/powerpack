<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Seguro | Power Pack</title>
    <meta name="theme-color" content="#2c60a4">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Power Pack">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="assets/logo-power-pack.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0b1120 0%, #0f172a 50%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #0f172a;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .login-header {
            background: #0f172a;
            padding: 32px 30px 24px 30px;
            text-align: center;
            border-bottom: 3px solid #2c60a4;
        }

        .login-logo {
            height: 58px;
            width: auto;
            object-fit: contain;
            margin-bottom: 12px;
        }

        .login-title {
            color: #ffffff;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .login-subtitle {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 4px;
        }

        .login-body {
            padding: 32px 30px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #0f172a;
            background: #f8fafc;
            transition: all 0.2s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2c60a4;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(44, 96, 164, 0.15);
        }

        .btn-login {
            width: 100%;
            padding: 13px 20px;
            background: #2c60a4;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 12px rgba(44, 96, 164, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            background: #1f4b85;
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            padding: 11px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-danger {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .login-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 16px 20px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }

        .seed-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 11px;
            color: #1e40af;
            margin-top: 18px;
            line-height: 1.5;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <img src="assets/logo-blanco.png" alt="Power Pack" class="login-logo">
        <h1 class="login-title">Power Pack</h1>
        <div class="login-subtitle">Acceso Seguro a la Plataforma Comercial</div>
    </div>

    <div class="login-body">
        <?php if (!empty($login_error)): ?>
        <div class="alert alert-danger">
            <span>⚠️</span>
            <div><?= h($login_error) ?></div>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'logout'): ?>
        <div class="alert alert-success">
            <span>✓</span>
            <div>Has cerrado sesión de forma segura.</div>
        </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=login">
            <input type="hidden" name="iniciar_sesion" value="1">

            <div class="form-group">
                <label for="email">Correo Corporativo</label>
                <input type="email" id="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required autofocus placeholder="administrador@powerpack.site">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="••••••••••••">
            </div>

            <button type="submit" class="btn-login">
                <span>Ingresar a la Plataforma</span>
                <span>→</span>
            </button>
        </form>

        <div class="seed-box">
            <strong>🔑 Acceso Inicial de Administrador:</strong><br>
            • Correo: <code>administrador@powerpack.site</code><br>
            • Contraseña: <code>PowerPack2026*</code><br>
            <span style="font-size:10px;color:#3b82f6">(Podrás cambiarla y crear tus propios asesores en el panel de administración).</span>
        </div>
    </div>

    <div class="login-footer">
        <strong>Power Pack</strong> • Soluciones Industriales de Empaque<br>
        Calle 161 # 54 - 25, Bogotá, Colombia • www.powerpack.com.co
    </div>
</div>

<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('sw.js').catch(function(err) {
            console.log('SW registration error:', err);
        });
    });
}
</script>
</body>
</html>
