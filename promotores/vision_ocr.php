<?php
/**
 * vision_ocr.php
 * Recibe una imagen base64, la envía a Google Cloud Vision (DOCUMENT_TEXT_DETECTION)
 * y devuelve los campos extraídos del formato "INVENTARIO MENSUAL DE LECHE EN POLVO".
 *
 * Campos prioritarios:
 *   - Clave de lecheria (10 dígitos que comienzan con "20")
 *   - Fecha del formato
 *   - Tabla I (Existencia de Leche): filas Cajas y Sobres
 *   - Tabla II (Surtimientos): fecha, cajas, litros, facturas
 *
 * Estrategia: usar las palabras con bounding box que devuelve Vision para
 * reconstruir filas reales del documento, en lugar de confiar en el orden
 * de lectura del texto plano.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// =================================================================
// 1. LLAMADA A GOOGLE VISION
// =================================================================
$apiKey = "AIzaSyACDbtPSIkEjJuckgRR2YacdQreXyzMAYs";
$url = "https://vision.googleapis.com/v1/images:annotate?key=" . $apiKey;

$input = json_decode(file_get_contents('php://input'), true);
$imageB64 = $input['image'] ?? '';
if (!$imageB64) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibió imagen']);
    exit;
}

$payload = [
    "requests" => [[
        "image"    => ["content" => $imageB64],
        "features" => [["type" => "DOCUMENT_TEXT_DETECTION"]],
        "imageContext" => ["languageHints" => ["es"]]
    ]]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$resArr   = json_decode($response, true);
$fullText = $resArr['responses'][0]['fullTextAnnotation']['text'] ?? '';

if ($httpCode !== 200 || empty($fullText)) {
    $errMsg = $resArr['responses'][0]['error']['message']
        ?? ($resArr['error']['message'] ?? 'Google no devolvió texto.');
    echo json_encode([
        'status'  => 'error',
        'message' => 'OCR falló: ' . $errMsg,
        'http'    => $httpCode
    ]);
    exit;
}

// =================================================================
// 2. HELPERS
// =================================================================

/** Convierte letras que la OCR suele confundir con dígitos. */
function normalizarNumero($s) {
    $mapa = [
        'O' => '0', 'o' => '0', 'D' => '0', 'Q' => '0',
        'S' => '5', 's' => '5',
        'Z' => '2', 'z' => '2',
        'I' => '1', 'l' => '1', 'L' => '1', 'J' => '1', 'i' => '1', '|' => '1',
        'G' => '6',
        'B' => '8',
        'T' => '7',
        'A' => '4',
    ];
    return strtr(trim($s), $mapa);
}

/** Devuelve solo dígitos a partir de un token (después de normalizar letras). */
function soloDigitos($s) {
    return preg_replace('/[^0-9]/', '', normalizarNumero($s));
}

/** Convierte fechas dd/mm/aa, dd-mm-aa, dd.mm.aa → YYYY-MM-DD (vacío si no es válida). */
function convertirFecha($raw) {
    $raw = normalizarNumero($raw);
    if (preg_match('/(\d{1,2})\s*[\/\-\.]\s*(\d{1,2})\s*[\/\-\.]\s*(\d{2,4})/', $raw, $m)) {
        $d  = (int)$m[1];
        $mo = (int)$m[2];
        $y  = $m[3];
        if (strlen($y) == 2) $y = '20' . $y;
        $y  = (int)$y;
        if ($d >= 1 && $d <= 31 && $mo >= 1 && $mo <= 12 && $y >= 2000 && $y <= 2099) {
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
    }
    return '';
}

/** Extrae todos los números (cortos) de un texto, tolerando letras confundidas. */
function extraerNumeros($texto) {
    preg_match_all('/[0-9OoSsZzIiLlBbGgTt|]+/u', $texto, $m);
    $res = [];
    foreach ($m[0] as $tok) {
        if (strlen($tok) > 8) continue; // descarta candidatos demasiado largos
        $n = soloDigitos($tok);
        if ($n !== '' && strlen($n) <= 7) $res[] = $n;
    }
    return $res;
}

// =================================================================
// 3. RECONSTRUIR FILAS USANDO POSICIONES (bounding boxes)
// =================================================================

$palabras = [];
$pages    = $resArr['responses'][0]['fullTextAnnotation']['pages'] ?? [];
foreach ($pages as $page) {
    foreach ($page['blocks'] ?? [] as $block) {
        foreach ($block['paragraphs'] ?? [] as $para) {
            foreach ($para['words'] ?? [] as $word) {
                $txt = '';
                foreach ($word['symbols'] ?? [] as $sym) {
                    $txt .= $sym['text'];
                }
                $verts = $word['boundingBox']['vertices'] ?? [];
                if (count($verts) < 4) continue;
                $xs = array_map(function ($v) { return $v['x'] ?? 0; }, $verts);
                $ys = array_map(function ($v) { return $v['y'] ?? 0; }, $verts);
                $palabras[] = [
                    'text' => $txt,
                    'cx'   => (min($xs) + max($xs)) / 2,
                    'cy'   => (min($ys) + max($ys)) / 2,
                    'h'    => max(1, max($ys) - min($ys))
                ];
            }
        }
    }
}

// Agrupar palabras en filas por coordenada Y
usort($palabras, function ($a, $b) {
    if (abs($a['cy'] - $b['cy']) < 1) return $a['cx'] <=> $b['cx'];
    return $a['cy'] <=> $b['cy'];
});

$alturas    = array_column($palabras, 'h');
$alturaProm = !empty($alturas) ? array_sum($alturas) / count($alturas) : 20;
$umbral     = $alturaProm * 0.6;

$filas = [];
foreach ($palabras as $p) {
    $colocado = false;
    foreach ($filas as $k => &$fila) {
        if (abs($p['cy'] - $fila['cy']) < $umbral) {
            $fila['palabras'][] = $p;
            $n = count($fila['palabras']);
            $fila['cy'] = ($fila['cy'] * ($n - 1) + $p['cy']) / $n;
            $colocado = true;
            break;
        }
    }
    unset($fila);
    if (!$colocado) {
        $filas[] = ['cy' => $p['cy'], 'palabras' => [$p]];
    }
}
usort($filas, function ($a, $b) { return $a['cy'] <=> $b['cy']; });
foreach ($filas as &$f) {
    usort($f['palabras'], function ($a, $b) { return $a['cx'] <=> $b['cx']; });
    $f['texto'] = implode(' ', array_column($f['palabras'], 'text'));
}
unset($f);

// =================================================================
// 4. EXTRACCIÓN DE DATOS
// =================================================================

$datos = [
    'lecher'  => '',
    'fecha'   => '',
    'surt_f'  => '',
    'ini_c'   => '', 'aba_c' => '', 'ven_c' => '', 'reg_c' => '', 'fin_c' => '',
    'ini_s'   => '', 'aba_s' => '', 'ven_s' => '', 'reg_s' => '', 'fin_s' => '',
    'surt_c'  => '', 'surt_l' => '', 'surt_fac' => '', 'surt_cad' => '',
    'cob_h'   => '', 'cob_m'  => '', 'cob_ma'   => '', 'cob_l'    => ''
];

// -------------------------------------------------------
// 4.1 CLAVE DE LECHERIA (10 dígitos comenzando con "20")
// -------------------------------------------------------
$buscarClaveEn = function ($texto) {
    $compact = preg_replace('/\s+/', '', soloDigitos($texto));
    if (preg_match('/(20\d{8,9})/', $compact, $m)) {
        return substr($m[1], 0, 10);
    }
    return '';
};

foreach ($filas as $idx => $f) {
    $low = mb_strtolower($f['texto']);
    if (strpos($low, 'clave del punto') !== false || strpos($low, 'punto de venta') !== false) {
        // Probar la misma fila + las dos siguientes (a veces el número va abajo)
        for ($k = 0; $k <= 2; $k++) {
            $cand = $filas[$idx + $k]['texto'] ?? '';
            $clave = $buscarClaveEn($cand);
            if ($clave !== '') { $datos['lecher'] = $clave; break 2; }
        }
    }
}
// Fallback: buscar en TODO el texto
if ($datos['lecher'] === '') {
    $datos['lecher'] = $buscarClaveEn($fullText);
}

// -------------------------------------------------------
// 4.2 FECHA PRINCIPAL del formato (encabezado "Fecha")
// -------------------------------------------------------
foreach ($filas as $idx => $f) {
    $t = trim($f['texto']);
    // Solo el encabezado superior; la fila de cabecera de Surtimientos
    // tiene "Fecha Cajas Litros Facturas" y la saltamos.
    if (preg_match('/^Fecha\b/i', $t) && stripos($t, 'Cajas') === false) {
        $rest    = preg_replace('/^\s*Fecha\b/i', '', $t);
        $fechaTry = convertirFecha($rest);
        if ($fechaTry === '' && isset($filas[$idx + 1])) {
            $fechaTry = convertirFecha($filas[$idx + 1]['texto']);
        }
        if ($fechaTry !== '') { $datos['fecha'] = $fechaTry; break; }
    }
}
// Fallback: la primera fecha que aparezca en el documento
if ($datos['fecha'] === '') {
    if (preg_match('/(\d{1,2})\s*[\/\-\.]\s*(\d{1,2})\s*[\/\-\.]\s*(\d{2,4})/', $fullText, $m)) {
        $datos['fecha'] = convertirFecha($m[0]);
    }
}

// -------------------------------------------------------
// 4.3 TABLA I — EXISTENCIA DE LECHE (Cajas / Sobres)
// -------------------------------------------------------
$inicioExist = -1;
$finExist    = count($filas);
foreach ($filas as $idx => $f) {
    if ($inicioExist < 0 && stripos($f['texto'], 'EXISTENCIA') !== false) {
        $inicioExist = $idx;
    } elseif ($inicioExist >= 0 && stripos($f['texto'], 'SURTIMIENTOS') !== false) {
        $finExist = $idx;
        break;
    }
}
if ($inicioExist < 0) $inicioExist = 0;

/**
 * Lee los números de una fila etiquetada (Cajas/Sobres). Si en la misma
 * fila no aparecen los 5-6 números esperados, completa con las filas
 * siguientes hasta toparse con la siguiente etiqueta.
 */
$leerFilaTabla = function ($etiqueta, $stopRegex) use ($filas, $inicioExist, $finExist) {
    for ($i = $inicioExist; $i < $finExist; $i++) {
        $f = $filas[$i];
        if (!preg_match('/^\s*' . $etiqueta . '\b/i', $f['texto'])) continue;

        // Recolectar números de la fila después de la etiqueta
        $nums = [];
        $skip = true;
        foreach ($f['palabras'] as $p) {
            if ($skip) {
                if (stripos($p['text'], $etiqueta) !== false) $skip = false;
                continue;
            }
            $n = soloDigitos($p['text']);
            if ($n !== '' && strlen($n) <= 6) $nums[] = $n;
        }

        // Si faltan, completar con filas siguientes
        $j = $i + 1;
        while (count($nums) < 6 && $j < $finExist) {
            $tSig = $filas[$j]['texto'];
            if (preg_match($stopRegex, $tSig)) break;
            foreach ($filas[$j]['palabras'] as $p) {
                $n = soloDigitos($p['text']);
                if ($n !== '' && strlen($n) <= 6) $nums[] = $n;
            }
            $j++;
        }
        return $nums;
    }
    return [];
};

$nC = $leerFilaTabla('Cajas',  '/^\s*(Sobres|Total)\b/i');
$nS = $leerFilaTabla('Sobres', '/^\s*Total\b/i');

// Esperamos 6 columnas: Inv.Inicial | Abasto | Ventas | Litros Reg. | Diferencias | Inv.Final
// Si OCR encuentra los 6, fin_x = índice 5. Si solo encuentra 5 (sin diferencias), fin_x = índice 4.
$datos['ini_c'] = $nC[0] ?? '';
$datos['aba_c'] = $nC[1] ?? '';
$datos['ven_c'] = $nC[2] ?? '';
$datos['reg_c'] = $nC[3] ?? '';
$datos['fin_c'] = $nC[5] ?? ($nC[4] ?? '');

$datos['ini_s'] = $nS[0] ?? '';
$datos['aba_s'] = $nS[1] ?? '';
$datos['ven_s'] = $nS[2] ?? '';
$datos['reg_s'] = $nS[3] ?? '';
$datos['fin_s'] = $nS[5] ?? ($nS[4] ?? '');

// -------------------------------------------------------
// 4.4 TABLA II — SURTIMIENTOS (fecha, cajas, litros, factura)
// -------------------------------------------------------
$inicioSurt = -1;
$finSurt    = count($filas);
foreach ($filas as $idx => $f) {
    if (stripos($f['texto'], 'SURTIMIENTOS') !== false) {
        $inicioSurt = $idx;
        break;
    }
}
if ($inicioSurt >= 0) {
    for ($idx = $inicioSurt + 1; $idx < count($filas); $idx++) {
        $t = $filas[$idx]['texto'];
        if (preg_match('/(DESABASTO|COBERTURA|PROBLEMAS|II\.1)/i', $t)) {
            $finSurt = $idx;
            break;
        }
    }

    for ($i = $inicioSurt + 1; $i < $finSurt; $i++) {
        $f = $filas[$i];
        // Saltar el header "Fecha Cajas Litros Facturas Caducidad"
        if (stripos($f['texto'], 'Cajas') !== false && stripos($f['texto'], 'Litros') !== false) {
            continue;
        }
        if (preg_match('/(\d{1,2})\s*[\/\-\.]\s*(\d{1,2})\s*[\/\-\.]\s*(\d{2,4})/', $f['texto'], $mF)) {
            $datos['surt_f'] = convertirFecha($mF[0]);

            // Recoger números de esta fila (excluyendo la fecha)
            $textoSinFecha = preg_replace(
                '/\d{1,2}\s*[\/\-\.]\s*\d{1,2}\s*[\/\-\.]\s*\d{2,4}/',
                ' ', $f['texto']
            );
            $nums = extraerNumeros($textoSinFecha);

            // Si en la misma fila no hay 3 números, mirar la siguiente
            if (count($nums) < 3 && isset($filas[$i + 1])) {
                $nums = array_merge($nums, extraerNumeros($filas[$i + 1]['texto']));
            }

            $datos['surt_c']   = $nums[0] ?? '';
            $datos['surt_l']   = $nums[1] ?? '';
            $datos['surt_fac'] = $nums[2] ?? '';

            // La factura suele tener 4-7 dígitos. Si el tercer número es muy corto,
            // buscamos otro candidato más largo más adelante.
            if (strlen($datos['surt_fac']) < 4) {
                foreach (array_slice($nums, 2) as $n) {
                    if (strlen($n) >= 4) { $datos['surt_fac'] = $n; break; }
                }
            }
            break;
        }
    }
}

// -------------------------------------------------------
// 4.5 TABLA III — COBERTURA SOCIAL (opcional, por completitud)
// -------------------------------------------------------
$inicioCob = -1;
foreach ($filas as $idx => $f) {
    if (stripos($f['texto'], 'HOGARES') !== false) { $inicioCob = $idx; break; }
    if (stripos($f['texto'], 'COBERTURA') !== false && $inicioCob < 0) { $inicioCob = $idx; }
}
if ($inicioCob >= 0) {
    $combo = '';
    for ($k = 0; $k < 6 && isset($filas[$inicioCob + $k]); $k++) {
        $t = $filas[$inicioCob + $k]['texto'];
        // Eliminamos palabras del encabezado para no contar sus dígitos accidentales
        $tLimpio = preg_replace(
            '/HOGARES|MENORES|PERSONAS\s*ADULTAS|LITROS\s*AL\s*MES|mayores\s*de\s*\d+\s*años|años|COBERTURA\s*SOCIAL.*|PROBLEMAS.*/i',
            ' ', $t
        );
        $combo .= ' ' . $tLimpio;
        if (stripos($t, 'PROBLEMAS') !== false) break;
    }
    $nums = extraerNumeros($combo);
    $datos['cob_h']  = $nums[0] ?? '';
    $datos['cob_m']  = $nums[1] ?? '';
    $datos['cob_ma'] = $nums[2] ?? '';
    $datos['cob_l']  = $nums[3] ?? '';
}

// =================================================================
// 5. RESPUESTA
// =================================================================
echo json_encode([
    'status'         => 'success',
    'datos'          => $datos,
    'texto_completo' => $fullText,
    'debug_filas'    => array_map(function ($f) { return $f['texto']; }, $filas)
], JSON_UNESCAPED_UNICODE);
