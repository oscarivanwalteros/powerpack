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

    // Cabeceras y cuerpo del correo
    $headers  = "Date: " . date('r') . "\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$from_email>\r\n";
    $headers .= "Reply-To: <$from_email>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($asunto) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= "X-Mailer: Powerpack-HubSpot-CRM/1.0\r\n\r\n";

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
