<?php
/**
 * mailer.php - Cliente SMTP nativo en PHP sin dependencias externas
 * Compatible con Hostinger (smtp.hostinger.com:465), Gmail, Outlook y cPanel.
 */

function enviar_correo_smtp($to, $asunto, $cuerpo_html, $config = []) {
    $host = $config['smtp_host'] ?? '';
    $port = (int)($config['smtp_port'] ?? 465);
    $user = $config['smtp_user'] ?? '';
    $pass = $config['smtp_pass'] ?? '';
    $from_email = $config['smtp_from'] ?? $user;
    $from_name = $config['empresa_nombre'] ?? 'Powerpack CRM';
    $secure = $config['smtp_secure'] ?? ($port == 465 ? 'ssl' : 'tls');

    // Si no hay configuración SMTP, intentar con mail() nativo de PHP
    if (empty($host) || empty($user) || empty($pass)) {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <" . ($from_email ?: 'noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost')) . ">\r\n";
        $headers .= "Reply-To: " . ($from_email ?: 'noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost')) . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $sent = @mail($to, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $cuerpo_html, $headers);
        if ($sent) {
            return ['ok' => true, 'mensaje' => 'Correo enviado mediante el servidor web (mail local).'];
        }
        return ['ok' => false, 'error' => 'No se configuró servidor SMTP y el envío local falló. Configura SMTP en Ajustes.'];
    }

    $socket_address = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client($socket_address, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        return ['ok' => false, 'error' => "No se pudo conectar a $socket_address: $errstr ($errno)"];
    }

    stream_set_timeout($socket, 15);

    $read = function() use ($socket) {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') break;
        }
        return $data;
    };

    $write = function($cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    $res = $read();
    if (substr($res, 0, 3) !== '220') {
        fclose($socket);
        return ['ok' => false, 'error' => "Respuesta inesperada al conectar: $res"];
    }

    $server_name = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $write("EHLO $server_name");
    $res = $read();

    if ($secure === 'tls' || ($port == 587 && strpos($res, 'STARTTLS') !== false)) {
        $write("STARTTLS");
        $res = $read();
        if (substr($res, 0, 3) === '220') {
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write("EHLO $server_name");
            $read();
        }
    }

    $write("AUTH LOGIN");
    $res = $read();
    if (substr($res, 0, 3) !== '334') {
        fclose($socket);
        return ['ok' => false, 'error' => "Error en AUTH LOGIN: $res"];
    }

    $write(base64_encode($user));
    $res = $read();
    if (substr($res, 0, 3) !== '334') {
        fclose($socket);
        return ['ok' => false, 'error' => "Usuario SMTP rechazado: $res"];
    }

    $write(base64_encode($pass));
    $res = $read();
    if (substr($res, 0, 3) !== '235') {
        fclose($socket);
        return ['ok' => false, 'error' => "Contraseña SMTP incorrecta: $res"];
    }

    $write("MAIL FROM: <$from_email>");
    $res = $read();
    if (substr($res, 0, 3) !== '250') {
        fclose($socket);
        return ['ok' => false, 'error' => "MAIL FROM rechazado: $res"];
    }

    $write("RCPT TO: <$to>");
    $res = $read();
    if (substr($res, 0, 3) !== '250' && substr($res, 0, 3) !== '251') {
        fclose($socket);
        return ['ok' => false, 'error' => "Destinatario <$to> rechazado: $res"];
    }

    $write("DATA");
    $res = $read();
    if (substr($res, 0, 3) !== '354') {
        fclose($socket);
        return ['ok' => false, 'error' => "Comando DATA rechazado: $res"];
    }

    // Envolver en plantilla corporativa oficial de Power Pack
    if (strpos($cuerpo_html, '<html') === false) {
        $cuerpo_html = '
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="font-family:Arial,-apple-system,sans-serif;line-height:1.6;color:#1e293b;background:#f8fafc;padding:20px;margin:0">
            <div style="max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.05)">
                <div style="background:#0f172a;padding:18px 24px;border-bottom:3px solid #2c60a4;display:flex;align-items:center;justify-content:space-between">
                    <img src="https://power-pack.com.co/wp-content/uploads/2025/06/logo-blanco.png" alt="Power Pack" style="height:40px;width:auto">
                    <span style="color:#94a3b8;font-size:12px;font-weight:bold">Soluciones Industriales</span>
                </div>
                <div style="padding:28px 24px;font-size:14px;color:#334155;line-height:1.6">
                    ' . $cuerpo_html . '
                </div>
                <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:18px 24px;font-size:11px;color:#64748b;text-align:center;line-height:1.5">
                    <strong style="color:#0f172a">Power Pack</strong> • Maquinaria y Soluciones de Empaque<br>
                    Calle 161 # 54 - 25, Bogotá, Colombia • Tel: +57 300 467 0474<br>
                    <a href="https://powerpack.com.co" style="color:#2c60a4;text-decoration:none;font-weight:bold">www.powerpack.com.co</a>
                </div>
            </div>
        </body>
        </html>';
    }

    $message_data = $headers . $cuerpo_html . "\r\n.";
    $write($message_data);
    $res = $read();

    $write("QUIT");
    fclose($socket);

    if (substr($res, 0, 3) === '250') {
        return ['ok' => true, 'mensaje' => 'Correo enviado exitosamente vía SMTP.'];
    }

    return ['ok' => false, 'error' => "Error al enviar mensaje: $res"];
}
