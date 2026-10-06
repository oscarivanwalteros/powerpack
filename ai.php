<?php
/**
 * ai.php - Motor de Inteligencia Artificial & Base de Conocimiento de Power Pack
 * Soporta Google Gemini (Gemini 2.0 / 1.5 Flash), OpenAI y Motor Heurístico de Respaldo
 */

if (!function_exists('get_ai_default_knowledge')) {
    function get_ai_default_knowledge() {
        return "=== REPOSITORIO EMPRESARIAL & BASE DE CONOCIMIENTO POWER PACK ===

1. IDENTIDAD DE LA EMPRESA:
- Razón Social: Power Pack
- Especialidad: Soluciones integrales de maquinaria industrial de empaque, sellado, dosificado, fechado y automatización de procesos para la industria en Colombia.
- Dirección Showroom Principal: Calle 161 # 54 - 25, Bogotá, Colombia.
- Teléfono / WhatsApp Comercial: +57 300 467 0474
- Correo Electrónico: ventas@powerpack.com.co
- Sitio Web Oficial: https://powerpack.com.co (Plataforma en https://powerpack.site)
- Misión: Impulsar la productividad y calidad de empaque de pequeñas, medianas y grandes empresas con tecnología confiable, asesoría experta y respaldo posventa inmediato.

2. CATÁLOGO DE LÍNEAS DE MAQUINARIA Y ESPECIFICACIONES:
a) SELLADORAS AL VACÍO INDUSTRIALES:
- Modelos de campana de mesa (compactas) y doble campana de piso (alto volumen).
- Construidas 100% en acero inoxidable grado alimenticio AISI 304.
- Bombas de vacío de alto rendimiento que alcanzan un 99.8% de extracción de aire.
- Aplicaciones: Carnes, quesos, lácteos, café tostado o molido, alimentos procesados, frutos secos, pescados y productos perecederos. Aumenta la vida útil de los alimentos de 3 a 5 veces sin conservantes.

b) DOSIFICADORAS DE LÍQUIDOS Y VISCOSOS:
- Dosificadoras volumétricas de pistón neumático (semiautomáticas y automáticas).
- Rangos de dosificación: 10ml - 100ml, 50ml - 500ml, 100ml - 1000ml, y hasta 5000ml.
- Tolva y componentes de contacto en acero inoxidable 316.
- Aplicaciones: Salsas, mayonesas, arequipes, pulpas de fruta, aceites, miel, geles antibacteriales, champús, cremas cosméticas, desinfectantes y químicos.
- Alta precisión con margen de error inferior al 1%.

c) SELLADORAS DE BANDA CONTINUA:
- Selladoras continuas horizontales (para bolsas acostadas) y verticales (para líquidos o granos en pie).
- Ancho de banda y velocidad regulable. Control digital de temperatura.
- Incluyen fechador / codificador por tinta sólida en caliente (hot roll) para imprimir fecha de vencimiento y lote en el mismo proceso de sellado.
- Sellan polietileno, polipropileno, papel kraft laminado, aluminio y bolsas multicapa.

d) CODIFICADORAS & FECHADORAS (INKJET & TÉRMICAS):
- Impresoras Inkjet TIJ de alta definición (600 DPI) manuales y para líneas continuas.
- Impresión instantánea de fecha de fabricación, fecha de vencimiento, número de lote, códigos de barras y códigos QR en plástico, vidrio, metal, cartón y bolsas flexibles.
- Tintas solventes de secado rápido (1 a 3 segundos).

e) TÚNELES DE TERMOENCOGIDO Y ENVOLVEDORAS:
- Para termoencogido de películas PVC, POF (Poliolefina) y Polietileno.
- Aplicaciones: Packs de bebidas, frascos, canastas, promociones 2x1 y sellado de seguridad en tapas.

3. POLÍTICAS COMERCIALES, GARANTÍA Y SERVICIO POSVENTA:
- Garantía: 12 meses (1 año) en estructura y componentes mecánicos contra defectos de fabricación.
- Disponibilidad y Entrega: Equipos en stock para entrega inmediata en Bogotá. Equipos sobre pedido entre 10 y 20 días hábiles.
- Cobertura de Envíos: Despachos seguros a toda Colombia (Bogotá, Medellín, Cali, Barranquilla, Bucaramanga, Eje Cafetero, Huila, Nariño, etc.).
- Repuestos y Consumibles: Stock permanente en Bogotá de resistencias, teflones, empaques de silicona, cuchillas, boquillas y tintas.
- Capacitación y Puesta en Marcha: Incluimos inducción y capacitación del operario para asegurar el correcto manejo del equipo.
- Formas de Pago: Transferencia bancaria (Bancolombia Cta Corriente), 50% anticipo al ordenar y 50% contra entrega / despacho.

4. PREGUNTAS FRECUENTES EN FERIAS Y EVENTOS COMERCIALES:
- ¿Puedo probar la máquina con mi producto antes de comprar? Sí, los clientes pueden traer o enviar sus muestras a nuestro showroom en Bogotá para hacer pruebas reales de empaque o dosificado.
- ¿Qué requerimiento eléctrico tienen? La mayoría de equipos compactos funcionan a 110V estándar; los equipos de doble campana o industriales a 220V monofásico o trifásico.
- ¿Hacen visitas técnicas? Sí, brindamos asesoría técnica consultiva para recomendar el equipo exacto según el volumen de producción deseado.

5. TONO DE VOZ DEL ASESOR COMERCIAL DE POWER PACK:
- Tono: Consultivo, profesional, respetuoso, cálido, experto en procesos industriales de empaque y alimentos.
- Enfoque: No vender por vender, sino asesorar la solución exacta que evite mermas, aumente la velocidad de empaque y garantice la inocuidad del producto.
- En WhatsApp: Mensajes concisos, directos, con formato limpio (negritas y emojis bien utilizados) y llamado a la acción claro (ej: agendar llamada, enviar cotización formal o invitar al showroom).
- En Correo: Estructura ejecutiva, saludo formal, beneficios técnicos tangibles, condiciones claras y firma corporativa.";
    }
}

// Obtener la base de conocimiento guardada en la base de datos o la predeterminada
function get_ai_knowledge_base($db) {
    $kb = get_config($db, 'ai_knowledge_base', '');
    if (empty(trim($kb))) {
        $kb = get_ai_default_knowledge();
        set_config($db, 'ai_knowledge_base', $kb);
    }
    return $kb;
}

// Configuración general de IA
function get_ai_settings($db) {
    return [
        'provider'   => get_config($db, 'ai_provider', 'gemini'), // 'gemini', 'openai'
        'api_key'    => get_config($db, 'ai_api_key', ''),
        'model'      => get_config($db, 'ai_model', 'gemini-3.8-flash'), // 'gemini-3.8-flash', 'gemini-2.5-flash', 'gemini-1.5-flash', 'gpt-4o-mini'
        'knowledge'  => get_ai_knowledge_base($db)
    ];
}

// Obtener catálogo de Skills B2B activas
function get_active_skills($db, $canal = 'ambos') {
    if ($canal === 'ambos') {
        $res = $db->query("SELECT * FROM skills_ia WHERE activo = 1 ORDER BY id ASC");
    } else {
        $stmt = $db->prepare("SELECT * FROM skills_ia WHERE activo = 1 AND (canal = ? OR canal = 'ambos') ORDER BY id ASC");
        $stmt->bindValue(1, $canal, SQLITE3_TEXT);
        $res = $stmt->execute();
    }
    $skills = [];
    if ($res) {
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $skills[] = $row;
        }
    }
    return $skills;
}

// Obtener una Skill B2B por código
function get_skill_by_code($db, $codigo) {
    if (empty($codigo)) return null;
    $stmt = $db->prepare("SELECT * FROM skills_ia WHERE codigo = ? LIMIT 1");
    $stmt->bindValue(1, $codigo, SQLITE3_TEXT);
    $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return $res ?: null;
}

// Cliente HTTP universal para Hostinger (soporta curl o stream_context)
function ai_http_post($url, $headers, $payload_json, $timeout = 18) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            return ['ok' => false, 'error' => "cURL error: $err"];
        }
        return ['ok' => $http_code >= 200 && $http_code < 300, 'code' => $http_code, 'body' => $res];
    } else {
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", $headers),
                'content' => $payload_json,
                'timeout' => $timeout,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($opts);
        $res = @file_get_contents($url, false, $context);
        if ($res === false) {
            $err = error_get_last();
            return ['ok' => false, 'error' => $err['message'] ?? 'Error de conexión HTTP'];
        }
        return ['ok' => true, 'code' => 200, 'body' => $res];
    }
}

// Llamar a la API de Google Gemini (Soporta gemini-3.8-flash y auto-recuperación de modelo)
function llamar_gemini($api_key, $model, $prompt, $system_instruction) {
    if (empty($model) || $model === 'gemini-2.0-flash') {
        $model = 'gemini-3.8-flash';
    }

    $candidatos = array_values(array_unique([
        $model,
        'gemini-3.8-flash',
        'gemini-2.5-flash',
        'gemini-1.5-flash'
    ]));

    $last_err = '';

    for ($i = 0; $i < count($candidatos); $i++) {
        $m = $candidatos[$i];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode($m) . ":generateContent?key=" . urlencode($api_key);
        
        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $system_instruction]
                ]
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 1200
            ]
        ];

        $headers = [
            'Content-Type: application/json'
        ];

        $resp = ai_http_post($url, $headers, json_encode($payload));
        if ($resp['ok']) {
            $json = json_decode($resp['body'] ?? '', true);
            if (!empty($json['candidates'][0]['content']['parts'][0]['text'])) {
                return [
                    'ok' => true,
                    'text' => trim($json['candidates'][0]['content']['parts'][0]['text']),
                    'model' => $m
                ];
            }
        }

        // Si falló, analizar el error y buscar si Google recomienda un modelo específico
        $body_str = $resp['body'] ?? '';
        $err_json = json_decode($body_str, true);
        $err_msg = $err_json['error']['message'] ?? ($resp['error'] ?? "Error HTTP " . ($resp['code'] ?? ''));
        $last_err = $err_msg;

        // Si Google devuelve: "Please update your code to use models/gemini-3.8-flash"
        if (preg_match('/models\/(gemini-[a-zA-Z0-9\.\-_]+)/i', $err_msg, $match_sug)) {
            $sug = $match_sug[1];
            if (!in_array($sug, $candidatos)) {
                $candidatos[] = $sug;
            }
        }

        // Si el error NO es de modelo faltante / 404 / no longer available (ej. clave inválida 400 o 403), no seguir probando otros modelos
        $err_lower = strtolower($err_msg);
        $es_error_modelo = strpos($err_lower, 'not found') !== false 
            || strpos($err_lower, 'no longer available') !== false 
            || strpos($err_lower, 'not_found') !== false 
            || ($resp['code'] ?? 0) === 404;

        if (!$es_error_modelo) {
            return ['ok' => false, 'error' => $err_msg, 'model' => $m];
        }
    }

    return ['ok' => false, 'error' => $last_err, 'model' => $model];
}

// Llamar a la API de OpenAI
function llamar_openai($api_key, $model, $prompt, $system_instruction) {
    $url = "https://api.openai.com/v1/chat/completions";
    
    $payload = [
        'model' => $model ?: 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $system_instruction],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.4,
        'max_tokens' => 1200
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];

    $resp = ai_http_post($url, $headers, json_encode($payload));
    if (!$resp['ok']) {
        return ['ok' => false, 'error' => $resp['error'] ?? "Error HTTP " . ($resp['code'] ?? '') . ": " . ($resp['body'] ?? '')];
    }

    $json = json_decode($resp['body'], true);
    if (!empty($json['choices'][0]['message']['content'])) {
        return ['ok' => true, 'text' => trim($json['choices'][0]['message']['content'])];
    }

    $error_msg = $json['error']['message'] ?? 'Respuesta inesperada de OpenAI API';
    return ['ok' => false, 'error' => $error_msg];
}

// Motor Heurístico de Respaldo (Funciona 100% offline sin API Key)
function motor_redaccion_offline($contacto, $canal, $objetivo, $instrucciones_extra, $knowledge, $skill_codigo = '') {
    $nombre = trim(($contacto['nombre'] ?? '') . ' ' . ($contacto['apellido'] ?? '')) ?: 'Estimado Cliente';
    $empresa = trim($contacto['empresa'] ?? '');
    $fuente = trim($contacto['fuente'] ?? 'Feria Comercial');
    $notas = trim($contacto['notas'] ?? '');
    $ciudad = trim($contacto['ciudad'] ?? 'Bogotá');
    $emp_str = $empresa ? " de $empresa" : "";

    // 1. Manejo específico por Skill B2B
    if ($skill_codigo === 'aida_feria' || $objetivo === 'seguimiento_feria') {
        if ($canal === 'whatsapp') {
            $msg = "¡Hola, $nombre! 👋 Te saluda el equipo de *Power Pack*.\n\nFue un gusto conocerte en nuestro stand de *$fuente* 🎪. Estuvimos revisando tus requerimientos de empaque" . ($notas ? " (*$notas*)" : "") . " para $empresa y queremos brindarte la asesoría técnica que necesitas para optimizar tu producción.\n\nContamos con equipos para entrega inmediata y 1 año de garantía técnica. ¿Te parecería si te envío la ficha técnica y cotización formal por este medio o prefieres que conversemos 5 minutos por llamada? Quedo muy atento. ¡Feliz día! ⚡";
            return ['ok' => true, 'asunto' => '', 'mensaje' => $msg . ($instrucciones_extra ? "\n\n📌 *Nota:* $instrucciones_extra" : ''), 'origen' => 'Skill B2B: Fórmula AIDA Feria (Motor Power Pack)'];
        } else {
            $asunto = "Seguimiento $fuente | Asesoría en Maquinaria de Empaque para $empresa - Power Pack";
            $cuerpo = "<p>Estimado(a) <strong>$nombre</strong>$emp_str,</p>
            <p>Es un placer saludarte. En nombre de <strong>Power Pack</strong>, queremos agradecerte por haber visitado nuestro stand durante la <strong>$fuente</strong>.</p>
            <p>De acuerdo con la conversación que sostuvimos en el evento, estuvimos analizando los requerimientos de tu empresa" . ($notas ? " (<em>$notas</em>)" : "") . ". Nuestro objetivo es brindarte una solución integral que permita incrementar la velocidad de tu proceso de empaque, reducir mermas y garantizar la máxima vida útil de tu producto.</p>
            <p><strong>Beneficios clave que te ofrecemos en Power Pack:</strong></p>
            <ul>
                <li><strong>Garantía de 12 meses:</strong> En estructura y componentes mecánicos contra todo defecto de fabricación.</li>
                <li><strong>Entrega y Despacho:</strong> Equipos disponibles en stock para despacho inmediato a $ciudad y todo el país.</li>
                <li><strong>Soporte y Repuestos:</strong> Centro de servicio técnico especializado y disponibilidad de repuestos originales en Bogotá.</li>
                <li><strong>Capacitación incluida:</strong> Inducción operativa y acompañamiento en la puesta en marcha.</li>
            </ul>" . (!empty($instrucciones_extra) ? "<p><strong>Observación técnica:</strong> $instrucciones_extra</p>" : "") . "
            <p>Nos gustaría coordinar una breve llamada de 10 minutos o invitarte a nuestro showroom en Bogotá (Calle 161 # 54 - 25) para que puedas ver los equipos operando con tus muestras reales.</p>
            <p>Quedamos atentos a tus comentarios para enviarte la propuesta formal.</p>
            <p>Cordialmente,<br><strong>Departamento Comercial & Soluciones Industriales</strong><br>Power Pack • www.powerpack.com.co<br>Tel / WhatsApp: +57 300 467 0474</p>";
            return ['ok' => true, 'asunto' => $asunto, 'mensaje' => $cuerpo, 'origen' => 'Skill B2B: Fórmula AIDA Feria (Motor Power Pack)'];
        }
    }

    if ($skill_codigo === 'pas_problema') {
        if ($canal === 'whatsapp') {
            $msg = "Hola, $nombre. Te saludamos desde *Power Pack* ⚡.\n\nSabemos que en la industria los cuellos de botella en empaque manual o fugas de sellado generan mermas invisibles y retrasos costosos en bodega.\n\nNuestras selladoras y dosificadoras industriales eliminan ese fallo con precisión garantizada, repuestos en Bogotá y 12 meses de garantía. ¿Te gustaría que evaluemos el equipo ideal para la línea de $empresa? 📄";
            return ['ok' => true, 'asunto' => '', 'mensaje' => $msg . ($instrucciones_extra ? "\n\n📌 *Nota:* $instrucciones_extra" : ''), 'origen' => 'Skill B2B: Fórmula PAS Problema (Motor Power Pack)'];
        } else {
            $asunto = "Optimización y Eliminación de Mermas de Empaque en $empresa | Power Pack";
            $cuerpo = "<p>Estimado(a) <strong>$nombre</strong>$emp_str,</p>
            <p>Un cordial saludo de parte de <strong>Power Pack</strong>.</p>
            <p>En el sector productivo de $empresa, procesos como el sellado manual o equipos descalibrados suelen causar <strong>mermas de producto, paradas inesperadas y reclamos por pérdida de vacío</strong>, lo que encarece directamente el costo por unidad empacada.</p>
            <p>En Power Pack diseñamos e importamos maquinaria con <strong>bombas de alto vacío grado industrial (99.8%) y dosificadoras neumáticas de pistón</strong> que blindan la calidad de tus empaques, reduciendo el desperdicio prácticamente a cero.</p>
            " . ($notas ? "<p>En atención a tus necesidades especificadas: <em>$notas</em>, podemos enviarte una simulación técnica del retorno de inversión.</p>" : "") . "
            <p>Todos nuestros equipos cuentan con 1 año de garantía y despacho a $ciudad con inducción completa a tus operarios.</p>
            <p>¿Tienes 10 minutos esta semana para presentarte la solución puntual para tu fábrica?</p>
            <p>Atentamente,<br><strong>Equipo Comercial Power Pack</strong><br>Calle 161 # 54 - 25, Bogotá, Colombia • Tel: +57 300 467 0474</p>";
            return ['ok' => true, 'asunto' => $asunto, 'mensaje' => $cuerpo, 'origen' => 'Skill B2B: Fórmula PAS Problema (Motor Power Pack)'];
        }
    }

    if ($skill_codigo === 'bab_transformacion') {
        if ($canal === 'whatsapp') {
            $msg = "¡Hola, $nombre! 👋 Imagina tu planta en $empresa empacando al triple de velocidad, con sellado hermético al 99.8% y presentación impecable en punto de venta, sin horas extra de nómina.\n\nEn *Power Pack* hacemos realidad esa transformación con maquinaria industrial lista para entrega inmediata en Colombia, 12 meses de garantía y facilidades de pago. ¿Podemos revisar juntos la ficha técnica de la solución? 🚀";
            return ['ok' => true, 'asunto' => '', 'mensaje' => $msg . ($instrucciones_extra ? "\n\n📌 *Nota:* $instrucciones_extra" : ''), 'origen' => 'Skill B2B: Transformación BAB (Motor Power Pack)'];
        } else {
            $asunto = "Transformación y Eficiencia en Línea de Empaque para $empresa | Power Pack";
            $cuerpo = "<p>Estimado(a) <strong>$nombre</strong>$emp_str,</p>
            <p>Imagina tu línea de producción operando de forma continua, rápida y sin depender de sellados manuales fatigantes, garantizando una presentación impecable que destaque en cualquier supermercado o distribuidor.</p>
            <p>En <strong>Power Pack</strong> somos el puente tecnológico para que plantas como <strong>$empresa</strong> den ese salto productivo. Ofrecemos selladoras continuas con fechador de lote integrado, selladoras al vacío de campana y dosificadoras de alta precisión en acero inoxidable 304/316.</p>
            <p><strong>Lo que ganas al equipar tu planta con Power Pack:</strong></p>
            <ul>
                <li>Hasta 3x mayor velocidad en empaque y despacho.</li>
                <li>12 meses de garantía estructural y mecánica.</li>
                <li>Stock permanente de consumibles y repuestos en Bogotá.</li>
            </ul>
            <p>¿Te gustaría coordinar una videollamada de 10 minutos para evaluar la configuración recomendada para tu volumen?</p>
            <p>Cordialmente,<br><strong>Power Pack Soluciones Industriales</strong><br>www.powerpack.com.co • Tel: +57 300 467 0474</p>";
            return ['ok' => true, 'asunto' => $asunto, 'mensaje' => $cuerpo, 'origen' => 'Skill B2B: Transformación BAB (Motor Power Pack)'];
        }
    }

    if ($skill_codigo === 'reactivacion_fria' || $objetivo === 'reactivacion') {
        if ($canal === 'whatsapp') {
            $msg = "Hola, $nombre, ¿cómo estás? Te saluda nuevamente el equipo de *Power Pack*.\n\nImagino que están con mucha carga en planta en $empresa. Quería consultarte con total sinceridad: ¿aún sigue vigente el proyecto de maquinaria de empaque o prefieres que archivemos la propuesta por ahora para no insistir más? Saludos cordiales. 🤝";
            return ['ok' => true, 'asunto' => '', 'mensaje' => $msg . ($instrucciones_extra ? "\n\n📌 *Nota:* $instrucciones_extra" : ''), 'origen' => 'Skill B2B: Magic Email Reactivación (Motor Power Pack)'];
        } else {
            $asunto = "¿Continuamos con la propuesta de maquinaria para $empresa? | Power Pack";
            $cuerpo = "<p>Estimado(a) <strong>$nombre</strong>$emp_str,</p>
            <p>Espero que te encuentres muy bien.</p>
            <p>Te escribo de manera muy breve. Imagino que han estado con bastantes compromisos operativos en <strong>$empresa</strong> durante estas semanas.</p>
            <p>Quería consultarte con total sinceridad: ¿el proyecto para modernizar la línea de empaque y maquinaria sigue entre las prioridades de la empresa, o prefieres que archivemos la cotización por el momento para no saturar tu bandeja de entrada?</p>
            <p>Por cortesía comercial, podemos mantener reservados los precios especiales y la disponibilidad de entrega inmediata hasta final de mes si aún les interesa evaluar el equipo.</p>
            <p>Quedo atento a tus comentarios cuando dispongas de un momento.</p>
            <p>Un cordial saludo,<br><strong>Equipo Comercial Power Pack</strong><br>Tel / WhatsApp: +57 300 467 0474</p>";
            return ['ok' => true, 'asunto' => $asunto, 'mensaje' => $cuerpo, 'origen' => 'Skill B2B: Magic Email Reactivación (Motor Power Pack)'];
        }
    }

    if ($skill_codigo === 'invitacion_showroom' || $objetivo === 'agendar_visita') {
        if ($canal === 'whatsapp') {
            $msg = "¡Hola, $nombre! 👋 Desde *Power Pack* queremos extenderte una invitación especial a nuestro Showroom en Bogotá (Calle 161 # 54 - 25).\n\nPuedes traer muestras reales de tu producto para realizar pruebas de empaque, sellado y dosificado en vivo sin ningún compromiso. Así compruebas velocidad y acabado antes de comprar. ¿Qué día de esta semana te quedaría cómodo visitarnos? 🏢";
            return ['ok' => true, 'asunto' => '', 'mensaje' => $msg . ($instrucciones_extra ? "\n\n📌 *Nota:* $instrucciones_extra" : ''), 'origen' => 'Skill B2B: Invitación Showroom (Motor Power Pack)'];
        } else {
            $asunto = "Invitación Especial a Pruebas en Vivo en Showroom Bogotá | Power Pack";
            $cuerpo = "<p>Estimado(a) <strong>$nombre</strong>$emp_str,</p>
            <p>Esperamos que tengas un excelente día.</p>
            <p>Sabemos que al adquirir maquinaria industrial para <strong>$empresa</strong>, la mayor seguridad es comprobar el resultado con el producto real. Por eso, queremos extenderte una invitación exclusiva a nuestro <strong>Showroom y Centro de Pruebas en Bogotá (Calle 161 # 54 - 25)</strong>.</p>
            <p>Puedes traer o enviarnos muestras de tu producto para realizar pruebas reales en vivo en nuestras selladoras al vacío, dosificadoras de pistón o selladoras de banda continua. Nuestros ingenieros calibrarán la máquina contigo y validarán la velocidad y hermeticidad exacta.</p>
            <p>Esta sesión técnica es 100% gratuita y sin compromiso de compra.</p>
            <p>¿Qué día de esta semana o la próxima te quedaría conveniente agendar tu visita?</p>
            <p>Cordialmente,<br><strong>Power Pack Soluciones Industriales</strong><br>Calle 161 # 54 - 25, Bogotá, Colombia • Tel: +57 300 467 0474</p>";
            return ['ok' => true, 'asunto' => $asunto, 'mensaje' => $cuerpo, 'origen' => 'Skill B2B: Invitación Showroom (Motor Power Pack)'];
        }
    }

    if ($skill_codigo === 'flash_whatsapp') {
        $msg = "¡Hola, $nombre! 👋 Te saluda Power Pack. Vimos su producción en $empresa y tenemos selladoras al vacío y dosificadoras en stock en Bogotá con 1 año de garantía. ¿Te queda bien que te comparta la ficha técnica en PDF por aquí?";
        return ['ok' => true, 'asunto' => '', 'mensaje' => $msg, 'origen' => 'Skill B2B: WhatsApp Flash (Motor Power Pack)'];
    }

    // Default genérico
    if ($canal === 'whatsapp') {
        $msg = "¡Hola, $nombre! 👋 Te saludamos de *Power Pack*, especialistas en maquinaria de empaque y dosificado en Colombia.\n\nNos ponemos en contacto" . ($empresa ? " con $empresa" : "") . " para apoyarte en la automatización de su línea de producción" . ($notas ? " respecto a: $notas" : "") . ".\n\nContamos con stock en Bogotá, 1 año de garantía y entrega inmediata. ¿En qué podemos colaborarte hoy? Quedamos a tu completa disposición.";
        return ['ok' => true, 'asunto' => '', 'mensaje' => $msg, 'origen' => 'Motor Inteligente Power Pack'];
    } else {
        $asunto = "Propuesta Comercial & Soluciones de Empaque | Power Pack";
        $cuerpo = "<p>Estimado(a) <strong>$nombre</strong>$emp_str,</p>
        <p>Esperamos que te encuentres muy bien. Te escribimos desde <strong>Power Pack</strong> con el propósito de dar respuesta a tu solicitud de maquinaria y tecnología de empaque industrial.</p>
        <p>Contamos con una amplia trayectoria asesorando plantas de alimentos, agroindustria y manufactura en Colombia, equipándolas con selladoras al vacío, dosificadoras de alta precisión, selladoras continuas y sistemas de codificación inkjet.</p>
        " . ($notas ? "<p>En atención a tus necesidades especificadas: <em>$notas</em>, hemos preparado la configuración técnica más adecuada para tu volumen de producción.</p>" : "") . "
        <p>Todos nuestros equipos cuentan con 1 año de garantía, soporte técnico directo y despacho a $ciudad.</p>
        <p>Agradecemos nos indiques tu disponibilidad para contactarte y enviarte los detalles técnicos y económicos.</p>
        <p>Atentamente,<br><strong>Equipo Comercial Power Pack</strong><br>Calle 161 # 54 - 25, Bogotá, Colombia • Tel: +57 300 467 0474</p>";
        return ['ok' => true, 'asunto' => $asunto, 'mensaje' => $cuerpo, 'origen' => 'Motor Inteligente Power Pack'];
    }
}

// Función principal para redactar con IA
function redactar_con_ia($db, $contacto, $canal, $objetivo, $instrucciones_extra = '', $skill_codigo = '') {
    $settings = get_ai_settings($db);
    $api_key = trim($settings['api_key']);
    $provider = $settings['provider'];
    $model = $settings['model'];
    $kb = $settings['knowledge'];

    // Si no hay API key configurada, usar el motor heurístico de respaldo que funciona al instante con las skills
    if (empty($api_key)) {
        return motor_redaccion_offline($contacto, $canal, $objetivo, $instrucciones_extra, $kb, $skill_codigo);
    }

    $nombre_completo = trim(($contacto['nombre'] ?? '') . ' ' . ($contacto['apellido'] ?? '')) ?: 'Cliente';
    $empresa = trim($contacto['empresa'] ?? '');
    $cargo = trim($contacto['cargo'] ?? '');
    $ciudad = trim($contacto['ciudad'] ?? 'Colombia');
    $sector = trim($contacto['sector'] ?? 'General');
    $fuente = trim($contacto['fuente'] ?? 'Feria / Comercial');
    $interes = (int)($contacto['interes'] ?? 2);
    $notas = trim($contacto['notas'] ?? '');

    // Cargar Skill B2B seleccionada si aplica
    $skill = get_skill_by_code($db, $skill_codigo);
    $skill_prompt_part = "";
    if ($skill) {
        $skill_prompt_part = "\n\nHABILIDAD COMERCIAL B2B APLICADA: '{$skill['nombre']}'
DIRECTRICES ESPECÍFICAS DE ESTA SKILL:
{$skill['framework_prompt']}
IMPORTANTE: Debes seguir rigurosamente la estructura y psicología persuasiva de esta skill en tu redacción.";
    }

    $system_instruction = "Eres el Asistente Comercial Senior de Inteligencia Artificial de la empresa 'Power Pack', líder en maquinaria industrial de empaque, sellado, dosificado y codificación en Colombia.
Tu misión es redactar mensajes comerciales persuasivos, profesionales, directos y adaptados al mercado industrial colombiano.
Debes consultar y respetar fielmente la Base de Conocimiento oficial de Power Pack que se te suministra a continuación. No inventes precios ni características técnicas que contradigan el catálogo.
$skill_prompt_part

BASE DE CONOCIMIENTO OFICIAL DE POWER PACK:
$kb

REGLAS DE FORMATO:
- Si el canal es WhatsApp: Escribe texto plano optimizado para WhatsApp móvil. Usa negritas con asteriscos (*texto*), viñetas con guiones o emojis industriales elegantes. NO uses HTML ni encabezados markdown #. Máximo 3 o 4 párrafos cortos y un llamado a la acción concreto.
- Si el canal es Correo: Devuelve primero la línea de asunto con el formato exacto: 'ASUNTO: [Tu Asunto Aquí]' seguido por una línea en blanco y luego el cuerpo del mensaje en formato HTML limpio (usando <p>, <ul>, <li>, <strong>) listo para enviar.
- Saluda siempre por el nombre del contacto de forma cortés y respetuosa.";

    $prompt = "Genera un mensaje de comunicación comercial para el siguiente prospecto:

DATOS DEL PROSPECTO:
- Nombre: $nombre_completo
- Empresa: " . ($empresa ?: 'No especificada') . "
- Cargo: " . ($cargo ?: 'Contacto Comercial') . "
- Ciudad: $ciudad
- Sector industrial: $sector
- Fuente de captación: $fuente
- Nivel de interés: $interes / 3
- Notas y requerimientos registrados: " . ($notas ?: 'Interés general en soluciones de empaque') . "

CANAL DESTINO: " . strtoupper($canal) . "
OBJETIVO DEL MENSAJE: $objetivo
SKILL B2B: " . ($skill ? $skill['nombre'] : 'Estándar') . "
INSTRUCCIONES ADICIONALES DEL ASESOR: " . ($instrucciones_extra ?: 'Ninguna') . "

Genera la redacción comercial perfecta ahora.";

    $resultado = null;
    if ($provider === 'openai') {
        $resultado = llamar_openai($api_key, $model, $prompt, $system_instruction);
    } else {
        // Por defecto Google Gemini
        $resultado = llamar_gemini($api_key, $model ?: 'gemini-3.8-flash', $prompt, $system_instruction);
    }

    if ($resultado['ok']) {
        $actual_model = $resultado['model'] ?? $model;
        $texto = $resultado['text'];
        $asunto = '';
        if ($canal === 'email') {
            if (preg_match('/^ASUNTO:\s*(.+)$/mi', $texto, $matches)) {
                $asunto = trim($matches[1]);
                $texto = trim(preg_replace('/^ASUNTO:\s*.+$/mi', '', $texto));
            } else {
                $asunto = "Soluciones de Empaque Industrial | Power Pack";
            }
        }

        $origen_str = $skill ? "IA ($provider: $actual_model) + Skill: " . $skill['nombre'] : "IA ($provider: $actual_model)";

        return [
            'ok' => true,
            'asunto' => $asunto,
            'mensaje' => $texto,
            'origen' => $origen_str
        ];
    } else {
        // En caso de error de API, usar el respaldo offline y avisar
        $backup = motor_redaccion_offline($contacto, $canal, $objetivo, $instrucciones_extra, $kb, $skill_codigo);
        $backup['api_warning'] = "Aviso: La API de $provider arrojó un error (" . $resultado['error'] . "). Se usó el motor inteligente interno.";
        return $backup;
    }
}
