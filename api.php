<?php
// api.php — API de estudo simples

header(header: 'Content-Type: application/json; charset=utf-8');
header(header: 'Access-Control-Allow-Origin: *');              // CORS simples
header(header: 'Access-Control-Allow-Methods: GET, POST, OPTIONS');
header(header: 'Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(response_code: 204);
    exit;
}

$path = $_GET['r'] ?? '';        // ex.: /ping -> api.php?r=/ping $method = $_SERVER['REQUEST_METHOD'];

function json($data, int $status = 200): never {
    http_response_code(response_code: $status);
    echo json_encode(value: $data, flags: JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

if ($path === '/ping' && $method === 'GET') {
    json(data: ['ok' => true, 'ts' => date(format: 'c')]);
}

if ($path === '/echo' && $method === 'POST') {
    $body = json_decode(json: file_get_contents(filename: 'php://input'), associative: true);
    json(data: ['received' => $body, 'method' => 'POST']);
}

json(data: ['error' => 'Not found', 'path' => $path], status: 404);
