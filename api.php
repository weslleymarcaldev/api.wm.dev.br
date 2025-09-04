<?php
// api.php — API de estudo simples

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');              // CORS simples
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = $_GET['r'] ?? '';        // ex.: /ping -> api.php?r=/ping
$method = $_SERVER['REQUEST_METHOD'];

function json($data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

if ($path === '/ping' && $method === 'GET') {
    json(['ok' => true, 'ts' => date('c')]);
}

if ($path === '/echo' && $method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    json(['received' => $body, 'method' => 'POST']);
}

json(['error' => 'Not found', 'path' => $path], 404);
