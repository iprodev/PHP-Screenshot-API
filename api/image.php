<?php
declare(strict_types=1);

$name = $_GET['name'] ?? '';
if (!preg_match('/^[a-zA-Z0-9_\-]+\.(png|jpg|jpeg)$/', $name)) {
    http_response_code(400);
    echo 'Invalid file name';
    exit;
}
$path = __DIR__ . '/../storage/' . $name;
if (!file_exists($path)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}
$mime = mime_content_type($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
readfile($path);
