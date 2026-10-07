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

// Obtener la base de conocimiento guardada en la base de datos + documentos activos subidos
function get_ai_knowledge_base($db) {
    $kb = get_config($db, 'ai_knowledge_base', '');
    if (empty(trim($kb))) {
        $kb = get_ai_default_knowledge();
        set_config($db, 'ai_knowledge_base', $kb);
    }

    // Consultar todos los documentos de contexto activos subidos en uploads/conocimiento/
    $docs = @$db->query("SELECT * FROM documentos_ia WHERE activo = 1 ORDER BY categoria ASC, id DESC");
    $docs_text = "";
    if ($docs) {
        $categoria_nombres = [
            'precios'    => 'LISTA DE PRECIOS & TARIFAS DE MAQUINARIA',
            'inventario' => 'INVENTARIOS, STOCK & TIEMPOS DE ENTREGA',
            'politicas'  => 'POLÍTICAS COMERCIALES, GARANTÍAS Y ENVÍOS',
            'catalogo'   => 'FICHAS TÉCNICAS Y ESPECIFICACIONES DE PRODUCTO',
            'correos'    => 'MODELOS DE CORREOS Y RESPUESTAS COMERCIALES REALES',
            'general'    => 'INFORMACIÓN Y CONTEXTO EMPRESARIAL ADICIONAL'
        ];

        $docs_por_cat = [];
        while ($d = $docs->fetchArray(SQLITE3_ASSOC)) {
            $cat = $d['categoria'] ?: 'general';
            $docs_por_cat[$cat][] = $d;
        }

        if (!empty($docs_por_cat)) {
            $docs_text .= "\n\n=== ARCHIVOS Y DOCUMENTOS DE CONTEXTO SUBIDOS POR POWER PACK ===";
            foreach ($docs_por_cat as $cat => $items) {
                $nom_cat = $categoria_nombres[$cat] ?? strtoupper($cat);
                $docs_text .= "\n\n--- SECCIÓN: $nom_cat ---";
                foreach ($items as $item) {
                    $docs_text .= "\n[DOCUMENTO: " . $item['titulo'] . " (" . $item['nombre_archivo'] . ")]\n";
                    $contenido = trim($item['texto_extraido']);
                    if (mb_strlen($contenido) > 8000) {
                        $contenido = mb_substr($contenido, 0, 8000) . "\n...(contenido adicional resumido)";
                    }
                    $docs_text .= $contenido . "\n";
                }
            }
        }
    }

    return $kb . $docs_text;
}

// Extractor de texto desde PDFs (pdftotext nativo de Linux con fallback a parser de streams de PHP)
function extraer_texto_pdf($file_path) {
    if (function_exists('shell_exec')) {
        $cmd = 'pdftotext ' . escapeshellarg($file_path) . ' - 2>/dev/null';
        $out = @shell_exec($cmd);
        if ($out !== null && trim($out) !== '') {
            return trim($out);
        }
    }

    $content = @file_get_contents($file_path);
    if (!$content) return '';

    $text = '';
    if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/is', $content, $matches)) {
        foreach ($matches[1] as $stream) {
            $data = $stream;
            if (function_exists('gzuncompress')) {
                $uncompressed = @gzuncompress($stream);
                if ($uncompressed !== false) {
                    $data = $uncompressed;
                }
            }

            if (preg_match_all('/BT[\r\n]+(.*?)[\r\n]+ET/is', $data, $bt_matches)) {
                foreach ($bt_matches[1] as $bt) {
                    if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', $bt, $str_matches)) {
                        foreach ($str_matches[1] as $s) {
                            $text .= $s . " ";
                        }
                    } elseif (preg_match_all('/\[(.*?)\]\s*TJ/s', $bt, $arr_matches)) {
                        foreach ($arr_matches[1] as $arr) {
                            if (preg_match_all('/\((.*?)\)/s', $arr, $sub_str)) {
                                foreach ($sub_str[1] as $s) {
                                    $text .= $s;
                                }
                                $text .= " ";
                            }
                        }
                    }
                    $text .= "\n";
                }
            }
        }
    }

    $text = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $text);
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/(\r?\n){3,}/', "\n\n", $text);

    return trim($text);
}

// Extractor universal de archivos de contexto para la IA (PDF, TXT, CSV, MD, JSON, EML, etc.)
function extraer_texto_archivo($file_path, $orig_name = '') {
    $ext = strtolower(pathinfo($orig_name ?: $file_path, PATHINFO_EXTENSION));

    if (in_array($ext, ['txt', 'csv', 'tsv', 'json', 'md', 'eml', 'log', 'xml'])) {
        $raw = @file_get_contents($file_path) ?: '';
        if (!preg_match('//u', $raw)) {
            $raw = @mb_convert_encoding($raw, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');
        }
        return trim($raw);
    }

    if (in_array($ext, ['html', 'htm'])) {
        $raw = @file_get_contents($file_path) ?: '';
        return trim(strip_tags($raw));
    }

    if ($ext === 'pdf') {
        $pdf_txt = extraer_texto_pdf($file_path);
        if (!empty($pdf_txt)) return $pdf_txt;
    }

    // Fallback para otros formatos
    $raw = @file_get_contents($file_path, false, null, 0, 150000) ?: '';
    $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $raw);
    return trim($clean);
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

/**
 * =======================================================================
 * MOTOR DE SUBAGENTES ESPECIALIZADOS Y ESTUDIOS COLABORATIVOS (IA COPILOT)
 * =======================================================================
 */

// Parser liviano para archivos Markdown de skills con metadatos YAML frontmatter
function parse_markdown_with_frontmatter($file_path) {
    if (!file_exists($file_path)) return null;
    $raw = @file_get_contents($file_path);
    if (!$raw) return null;

    $meta = [];
    $body = $raw;

    if (preg_match('/^---\s*\r?\n(.*?)\r?\n---\s*\r?\n(.*)$/s', $raw, $matches)) {
        $frontmatter = $matches[1];
        $body = trim($matches[2]);
        $lines = explode("\n", $frontmatter);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') continue;
            if (strpos($line, ':') !== false) {
                list($key, $val) = explode(':', $line, 2);
                $key = trim($key);
                $val = trim($val);
                $val = trim($val, '"\'');
                $meta[$key] = $val;
            }
        }
    }

    $meta['contenido'] = $body;
    $meta['archivo'] = basename($file_path);
    return $meta;
}

// Escanea y retorna todos los subagentes disponibles en el directorio agents/
function get_available_subagents() {
    $base_dir = __DIR__ . '/agents';
    $subagents = [];
    if (!is_dir($base_dir)) return $subagents;

    $dirs = scandir($base_dir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        $agent_path = $base_dir . '/' . $dir;
        $json_file = $agent_path . '/agent.json';
        if (is_dir($agent_path) && file_exists($json_file)) {
            $json_data = json_decode(@file_get_contents($json_file), true);
            if (!$json_data) $json_data = [];

            $json_data['id'] = $json_data['id'] ?? $dir;
            $json_data['carpeta'] = $dir;
            $json_data['path'] = $agent_path;

            // System prompt
            $system_file = $agent_path . '/system.md';
            $json_data['system_prompt'] = file_exists($system_file) ? @file_get_contents($system_file) : '';

            // Skills en markdown
            $skills = [];
            $skills_dir = $agent_path . '/skills';
            if (is_dir($skills_dir)) {
                $files = scandir($skills_dir);
                foreach ($files as $f) {
                    if (pathinfo($f, PATHINFO_EXTENSION) === 'md') {
                        $parsed = parse_markdown_with_frontmatter($skills_dir . '/' . $f);
                        if ($parsed) {
                            $skills[] = $parsed;
                        }
                    }
                }
            }
            $json_data['skills'] = $skills;
            $subagents[$dir] = $json_data;
        }
    }
    return $subagents;
}

// Obtener subagente por identificador o carpeta
function get_subagent_by_id($subagent_id) {
    $all = get_available_subagents();
    if (isset($all[$subagent_id])) return $all[$subagent_id];
    foreach ($all as $k => $agent) {
        if (($agent['id'] ?? '') === $subagent_id) {
            return $agent;
        }
    }
    return null;
}

// Obtener una habilidad específica de un subagente
function get_subagent_skill($subagent_id, $skill_code) {
    $agent = get_subagent_by_id($subagent_id);
    if (!$agent || empty($agent['skills'])) return null;
    foreach ($agent['skills'] as $s) {
        if (($s['codigo'] ?? '') === $skill_code) {
            return $s;
        }
    }
    return null;
}

// Función Copilot interactiva para co-redacción iterativa con subagente
function copilot_subagente_chat($db, $subagent_id, $skill_code, $mensajes, $contacto, $instrucciones_usuario, $borrador_actual = []) {
    $subagent = get_subagent_by_id($subagent_id);
    if (!$subagent) {
        $subagent = [
            'id' => $subagent_id,
            'nombre' => 'Subagente Comercial Power Pack',
            'rol' => 'Especialista en Ventas B2B',
            'canal' => ($subagent_id === 'whatsapp' ? 'whatsapp' : 'email'),
            'system_prompt' => 'Eres un asesor senior B2B de Power Pack.'
        ];
    }

    $canal = $subagent['canal'] ?? ($subagent_id === 'whatsapp' ? 'whatsapp' : 'email');
    $skill = get_subagent_skill($subagent_id, $skill_code);

    $settings = get_ai_settings($db);
    $api_key = trim($settings['api_key']);
    $provider = $settings['provider'];
    $model = $settings['model'];
    $kb = $settings['knowledge'];

    // Datos del contacto o contexto de campaña
    $nombre = trim(($contacto['nombre'] ?? '') . ' ' . ($contacto['apellido'] ?? ''));
    if (empty($nombre)) $nombre = '{nombre}';
    $empresa = trim($contacto['empresa'] ?? '');
    if (empty($empresa)) $empresa = '{empresa}';
    $cargo = trim($contacto['cargo'] ?? '');
    if (empty($cargo)) $cargo = '{cargo}';
    $ciudad = trim($contacto['ciudad'] ?? '');
    if (empty($ciudad)) $ciudad = '{ciudad}';
    $notas = trim($contacto['notas'] ?? '');

    // Construir instrucción de sistema maestra
    $system_instruction = "Eres el {$subagent['nombre']}, con el rol oficial de '{$subagent['rol']}' en la empresa Power Pack SAS (Colombia).
{$subagent['system_prompt']}

BASE DE CONOCIMIENTO Y REPOSITORIO CORPORATIVO DE POWER PACK:
$kb

REGLAS DE TRABAJO COLABORATIVO:
1. Estás trabajando en una sesión interactiva mano a mano con el asesor comercial humano.
2. Cada vez que el usuario te dé una instrucción (ej: 'hazlo más corto', 'cambia el enfoque a dosificadoras', 'agrega invitación al showroom'), debes procesarla, mejorar el borrador y devolver tanto una breve explicación de lo que cambiaste como el borrador actualizado.
3. SIEMPRE debes responder en formato JSON estricto con la siguiente estructura:
";

    if ($canal === 'email') {
        $system_instruction .= '{
  "respuesta_chat": "Comentario conversacional y profesional para el asesor explicando qué ajustes hiciste...",
  "asunto": "Línea de asunto persuasiva para el correo...",
  "cuerpo_html": "<p>Cuerpo del correo en HTML limpio con formato profesional, párrafos, negritas y firma...</p>"
}';
    } else {
        $system_instruction .= '{
  "respuesta_chat": "Comentario conversacional y profesional para el asesor explicando qué ajustes hiciste...",
  "mensaje": "Texto de WhatsApp formateado con *negritas*, saltos de línea legibles, emojis sobrios y CTA claro..."
}';
    }

    // Contexto de la Skill
    $skill_instruction = "";
    if ($skill) {
        $skill_instruction = "\nHABILIDAD ACTIVA SELECCIONADA: {$skill['nombre']} (Código: {$skill['codigo']})
Descripción de la habilidad: {$skill['descripcion']}
Instrucciones detalladas de la habilidad:
{$skill['contenido']}
Asegúrate de aplicar fielmente los principios de esta habilidad en el borrador.\n";
    }

    // Historial previo
    $chat_history_str = "";
    if (!empty($mensajes) && is_array($mensajes)) {
        $chat_history_str .= "\nHISTORIAL DE LA CONVERSACIÓN PREVIA CON EL ASESOR:\n";
        foreach ($mensajes as $m) {
            $rol_label = ($m['rol'] ?? 'usuario') === 'usuario' ? 'Asesor' : 'Subagente';
            $chat_history_str .= "- $rol_label: " . ($m['texto'] ?? '') . "\n";
        }
    }

    // Estado del borrador actual
    $draft_str = "";
    if (!empty($borrador_actual)) {
        $draft_str .= "\nESTADO ACTUAL DEL BORRADOR:\n";
        if (!empty($borrador_actual['asunto'])) {
            $draft_str .= "Asunto actual: " . $borrador_actual['asunto'] . "\n";
        }
        if (!empty($borrador_actual['cuerpo'])) {
            $draft_str .= "Cuerpo actual: " . $borrador_actual['cuerpo'] . "\n";
        }
        if (!empty($borrador_actual['mensaje'])) {
            $draft_str .= "Mensaje actual: " . $borrador_actual['mensaje'] . "\n";
        }
    }

    $prompt = "DATOS DEL DESTINATARIO / PROSPECTO:
- Nombre: $nombre
- Empresa: $empresa
- Cargo: $cargo
- Ciudad: $ciudad
- Notas / Requerimiento: " . ($notas ?: 'Interés en optimización y maquinaria industrial de empaque') . "
$skill_instruction
$draft_str
$chat_history_str
NUEVA INSTRUCCIÓN DEL ASESOR HUMANO:
\"$instrucciones_usuario\"

Genera la respuesta y el borrador optimizado en el formato JSON requerido.";

    // Ejecución con API (Gemini / OpenAI) o motor offline
    if (!empty($api_key)) {
        if ($provider === 'openai') {
            $resp = llamar_openai($api_key, $model, $prompt, $system_instruction);
        } else {
            $resp = llamar_gemini($api_key, $model ?: 'gemini-3.8-flash', $prompt, $system_instruction);
        }

        if ($resp['ok']) {
            $text = trim($resp['text']);
            // Limpiar bloques de código markdown si la IA respondió con ```json ... ```
            if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $text, $code_match)) {
                $text = trim($code_match[1]);
            }

            $parsed_json = json_decode($text, true);
            if (is_array($parsed_json) && (!empty($parsed_json['cuerpo_html']) || !empty($parsed_json['mensaje']))) {
                return [
                    'ok' => true,
                    'respuesta_chat' => $parsed_json['respuesta_chat'] ?? 'Borrador actualizado con éxito según tus indicaciones.',
                    'asunto' => $parsed_json['asunto'] ?? ($borrador_actual['asunto'] ?? 'Propuesta de Soluciones Industriales | Power Pack'),
                    'cuerpo_html' => $parsed_json['cuerpo_html'] ?? ($parsed_json['mensaje'] ?? ''),
                    'mensaje' => $parsed_json['mensaje'] ?? ($parsed_json['cuerpo_html'] ?? ''),
                    'origen' => "Subagente {$subagent['nombre']} ($provider: " . ($resp['model'] ?? $model) . ")"
                ];
            }
        }
    }

    // Motor Heurístico / Offline de alta calidad para el Subagente
    return copilot_subagente_offline($subagent, $skill, $contacto, $instrucciones_usuario, $borrador_actual);
}

// Respaldo heurístico e interactivo offline para subagentes
function copilot_subagente_offline($subagent, $skill, $contacto, $instrucciones_usuario, $borrador_actual = []) {
    $canal = $subagent['canal'] ?? 'email';
    $nombre = trim(($contacto['nombre'] ?? '') . ' ' . ($contacto['apellido'] ?? '')) ?: '{nombre}';
    $empresa = trim($contacto['empresa'] ?? '') ?: '{empresa}';
    $skill_nombre = $skill['nombre'] ?? 'Redacción Estratégica B2B';
    $skill_codigo = $skill['codigo'] ?? '';

    if ($canal === 'email') {
        $asunto = "Eficiencia y Continuidad Operativa en Línea de Empaque para $empresa | Power Pack";
        $cuerpo = "<p>Estimado(a) <strong>$nombre</strong>,</p>
<p>Le escribe el equipo comercial de <strong>Power Pack SAS</strong>. Esperamos que se encuentre muy bien en <strong>$empresa</strong>.</p>
<p>Sabemos que mantener la eficiencia en el empaque y sellado sin paradas de planta no programadas es un reto constante. Por ello, queremos presentarle nuestras soluciones en <strong>maquinaria industrial de empaque, selladoras continuas con fechador de lote integrado y dosificadoras de alta precisión en acero inoxidable 304/316</strong>.</p>
<p><strong>Lo que distingue a Power Pack en la industria:</strong></p>
<ul>
    <li><strong>12 Meses de Garantía</strong> directa en estructura y componentes mecánicos.</li>
    <li><strong>Disponibilidad Inmediata</strong> de equipos y repuestos en bodega Bogotá (evitando meses de importación marítima).</li>
    <li><strong>Soporte Técnico Especializado</strong> y puesta en marcha con capacitación a sus operarios.</li>
</ul>
<p>¿Tendría 10 minutos esta semana para una breve llamada técnica o le gustaría coordinar una visita a nuestro Showroom en Bogotá (Calle 161 # 54 - 25) para probar las máquinas con su producto?</p>
<p>Atentamente,<br><strong>Power Pack SAS</strong><br>Soluciones Industriales de Empaque<br>Calle 161 # 54 - 25, Bogotá • Tel: +57 300 467 0474<br><a href=\"https://powerpack.com.co\">www.powerpack.com.co</a></p>";

        if (strpos($skill_codigo, 'reactivacion') !== false) {
            $asunto = "¿Continuamos con la propuesta de empaque para $empresa? | Power Pack";
            $cuerpo = "<p>Hola <strong>$nombre</strong>,</p>
<p>Te escribo brevemente porque sé lo ocupadas que son las semanas operativas en <strong>$empresa</strong>.</p>
<p>¿Sigue siendo prioridad para ustedes la adquisición de la maquinaria de empaque este trimestre, o prefieres que pausemos el seguimiento por ahora para no saturar tu correo?</p>
<p>Por cortesía comercial podemos reservar la disponibilidad inmediata hasta fin de mes.</p>
<p>Cordialmente,<br><strong>Power Pack SAS</strong><br>Tel: +57 300 467 0474</p>";
        }

        return [
            'ok' => true,
            'respuesta_chat' => "He estructurado el correo aplicando la habilidad de '{$skill_nombre}'. El mensaje incluye la propuesta de valor de Power Pack (garantía de 1 año, stock en Bogotá y respaldo técnico) con llamado a la acción claro.",
            'asunto' => $asunto,
            'cuerpo_html' => $cuerpo,
            'mensaje' => strip_tags($cuerpo),
            'origen' => "Subagente {$subagent['nombre']} (Motor Heurístico Power Pack)"
        ];
    } else {
        // WhatsApp
        $msg = "Hola *{$nombre}*, un gusto saludarte. Soy Oscar Walteros de *Power Pack SAS* ⚙️\n\nTe escribo porque apoyamos a plantas como *{$empresa}* en optimizar sus líneas de empaque y sellado con maquinaria industrial de alta velocidad.\n\nContamos con equipos para *entrega inmediata en Bogotá*, repuestos locales y *12 meses de garantía directa*.\n\n¿En qué tipo de producto o máquina de empaque están enfocando sus mejoras actualmente para compartirte un video corto en operación? 🤝";

        if (strpos($skill_codigo, 'aviso_cotizacion') !== false) {
            $msg = "Hola *{$nombre}*, un saludo cordial 🤝\n\nAcabo de enviarte a tu correo la cotización formal con las especificaciones técnicas completas y disponibilidad de entrega inmediata de *Power Pack*.\n\n¿Pudiste recibirlo bien en tu bandeja de entrada o prefieres que te adjunte el documento en PDF también por aquí?";
        } elseif (strpos($skill_codigo, 'showroom') !== false) {
            $msg = "¡Hola *{$nombre}*! 👋 Desde *Power Pack* queremos invitarte a nuestro Showroom técnico en Bogotá (Calle 161 # 54 - 25).\n\nPuedes traer muestras de tu producto en *{$empresa}* y realizamos pruebas de sellado y velocidad en vivo sin compromiso. ¿Qué día de esta semana te quedaría cómodo visitarnos? 🏢";
        } elseif (strpos($skill_codigo, 'reactivacion') !== false) {
            $msg = "Hola *{$nombre}*, ¿cómo va todo en *{$empresa}*? ☕\n\n¿Pudieron evaluar la propuesta del equipo de empaque o prefieres que lo retomemos el próximo mes? Un saludo.";
        }

        return [
            'ok' => true,
            'respuesta_chat' => "He preparado el mensaje para WhatsApp con formato móvil (*negritas*, viñetas y emojis sobrios) adaptado a la habilidad '{$skill_nombre}'.",
            'asunto' => '',
            'cuerpo_html' => nl2br($msg),
            'mensaje' => $msg,
            'origen' => "Subagente {$subagent['nombre']} (Motor Heurístico Power Pack)"
        ];
    }
}

