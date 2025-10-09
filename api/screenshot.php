<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Screenshot.php';
require_once __DIR__ . '/../config.php';

use Screenshot\Screenshot;

// CORS & preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . ALLOW_ORIGIN);
    header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    http_response_code(204);
    exit;
}
header('Access-Control-Allow-Origin: ' . ALLOW_ORIGIN);

// API Key
$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($providedKey !== API_KEY) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Parse JSON
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
    exit;
}

// Params
$url = $body['url'] ?? null;
$width = isset($body['width']) ? (int)$body['width'] : 1280;
$height = isset($body['height']) ? (int)$body['height'] : 800;
$fullPage = (bool)($body['full_page'] ?? false);
$delaySec = isset($body['delay']) ? max(0, (int)$body['delay']) : 0;
$format = strtolower($body['format'] ?? 'png'); // png|jpg
$responseType = strtolower($body['response'] ?? 'json'); // json|binary

if ($format === 'jpg') $format = 'jpeg';
if (!in_array($format, ['png','jpeg'], true)) $format = 'png';

if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid or missing url']);
    exit;
}
$parts = parse_url($url);
if (!in_array(strtolower($parts['scheme'] ?? ''), ['http','https'], true)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Only http/https are allowed']);
    exit;
}

// Storage & cleanup
$storage = realpath(__DIR__ . '/../storage') ?: (__DIR__ . '/../storage');
if (!is_dir($storage)) { mkdir($storage, 0775, true); }
$now = time();
$deleted = 0;
foreach (glob($storage . '/*') as $f) {
    if (is_file($f) && $now - filemtime($f) > CLEANUP_TTL) { @unlink($f); $deleted++; }
}

// Target file
$ext = $format === 'jpeg' ? 'jpg' : 'png';
$filename = sprintf('screenshot_%d_%s.%s', time(), bin2hex(random_bytes(8)), $ext);
$outPath = $storage . DIRECTORY_SEPARATOR . $filename;

// Capture
try {
    $cap = new Screenshot();
    $cap->setViewport($width, $height);
    $cap->setFormat($format);
    $cap->setTimeout(20000);
    $cap->setDelay($delaySec);
    $cap->setFullPage($fullPage);

    $ok = $cap->capture($url, $outPath);
    if (!$ok || !file_exists($outPath)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Capture failed']);
        exit;
    }

    if ($responseType === 'binary') {
        $mime = mime_content_type($outPath) ?: ($format === 'jpeg' ? 'image/jpeg' : 'image/png');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($outPath) . '"');
        readfile($outPath);
        exit;
    } else {
        header('Content-Type: application/json; charset=utf-8');
        $b64 = base64_encode(file_get_contents($outPath));
        echo json_encode([
            'success' => true,
            'filename' => basename($outPath),
            'deleted_old_files' => $deleted,
            'meta' => [
                'width' => $width,
                'height' => $height,
                'full_page' => $fullPage,
                'delay' => $delaySec,
                'format' => $ext
            ],
            'base64' => $b64
        ]);
    }
} catch (\Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
