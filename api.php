<?php
declare(strict_types=1);

// CORS e JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // ⚠️ Em produção, troque "*" pelo seu domínio
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---- helpers ---------------------------------------------------------------
function json_out($data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

function body_json(): array {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) return [];
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_out(['error' => 'Invalid JSON', 'details' => json_last_error_msg()], 400);
    }
    return $data ?? [];
}

// ---- rota atual (via r=/caminho ou PATH_INFO) ------------------------------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = $_GET['r'] ?? ($_SERVER['PATH_INFO'] ?? '/');
$path   = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?? '/', '/');

// ---- endpoints --------------------------------------------------------------
// GET  /ping      -> status rápido
// GET  /time      -> hora do servidor
// GET  /sum?a=1&b=2      -> soma via query
// POST /echo      -> devolve o JSON enviado
// POST /sum       -> soma via JSON: { "a": 1, "b": 2 }

$routes = [
    'GET /ping' => fn() => json_out(['ok' => true, 'ts' => date('c')]),

    'GET /time' => fn() => json_out(['server_time' => date('Y-m-d H:i:s')]),

    'GET /sum' => function () {
        $a = filter_input(INPUT_GET, 'a', FILTER_VALIDATE_FLOAT);
        $b = filter_input(INPUT_GET, 'b', FILTER_VALIDATE_FLOAT);
        if ($a === null || $a === false || $b === null || $b === false) {
            json_out(['error' => 'Params a and b must be numbers'], 400);
        }
        json_out(['a' => $a, 'b' => $b, 'sum' => $a + $b]);
    },

    'POST /echo' => function () {
        $body = body_json();
        json_out(['received' => $body, 'method' => 'POST']);
    },

    'POST /sum' => function () {
        $b = body_json();
        $a = $b['a'] ?? null;
        $c = $b['b'] ?? null;
        if (!is_numeric($a) || !is_numeric($c)) {
            json_out(['error' => 'Body must include numeric a and b'], 400);
        }
        json_out(['a' => (float)$a, 'b' => (float)$c, 'sum' => (float)$a + (float)$c]);
    },
];

// ---- dispatcher -------------------------------------------------------------
$key = "$method $path";

if (isset($routes[$key])) {
    $routes[$key](); // executa e encerra via json_out
}

// Se a rota existe com outro método, retorna 405
$allowed = array_keys(array_filter(
    $routes,
    fn($_, $k) => preg_match('#\s' . preg_quote($path, '#') . '$#', $k),
    ARRAY_FILTER_USE_BOTH
));
if ($allowed) {
    $allow = implode(', ', array_unique(array_map(fn($k) => explode(' ', $k)[0], $allowed)));
    header('Allow: '.$allow);
    json_out(['error' => 'Method not allowed', 'allow' => $allow], 405);
}

// 404
json_out(['error' => 'Not found', 'path' => $path], 404);
