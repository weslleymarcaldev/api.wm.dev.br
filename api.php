<?php
declare(strict_types=1);  // força tipagem estrita (boa prática)

// ===== CORS e cabeçalhos de resposta JSON =====
header('Content-Type: application/json; charset=utf-8');                // toda resposta será JSON
header('Access-Control-Allow-Origin: *');                               // CORS liberado para qualquer origem (troque * em produção)
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');             // métodos aceitos
header('Access-Control-Allow-Headers: Content-Type, Authorization');    // headers permitidos nas requisições

// pré-flight CORS: o navegador manda OPTIONS antes de POST/GET com certos headers
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204); // 204 No Content
    exit;                                   // encerra cedo (sem body)
}

// ===== Helpers reutilizáveis =====
/**
 * Envia JSON e encerra a execução.
 * @param mixed $data   Dados para serializar
 * @param int   $status Código HTTP (default 200)
 * @return never        Indica que a função não retorna (PHP 8.1+)
 */
function json_out($data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Lê o corpo da requisição como JSON e retorna array associativo.
 * Valida JSON e, se inválido, responde 400 com detalhes do erro.
 */
function body_json(): array {
    $raw = file_get_contents('php://input');        // pega o body cru
    if ($raw === '' || $raw === false) return [];             // sem body → array vazio
    $data = json_decode($raw, true);       // decodifica para array
    if (json_last_error() !== JSON_ERROR_NONE) {              // valida sintaxe
        json_out(['error' => 'Invalid JSON', 'details' => json_last_error_msg()], 400);
    }
    return $data ?? []; // garante array
}

// ===== Descoberta da rota atual =====
// Suporta duas formas: querystring ?r=/caminho ou PATH_INFO (/api.php/ping)
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';                 // GET/POST/OPTIONS...
$path   = $_GET['r'] ?? ($_SERVER['PATH_INFO'] ?? '/');        // caminho bruto
$path   = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?? '/', '/'); // normaliza para começar com "/"

// ===== Definição dos endpoints =====
// Documentação rápida:
//
// GET  /ping           -> status rápido
// GET  /time           -> hora do servidor
// GET  /sum?a=1&b=2    -> soma via querystring
// POST /echo           -> devolve o JSON enviado
// POST /sum            -> soma via JSON: { "a": 1, "b": 2 }
$routes = [
    // ping: útil para health-check
    'GET /ping' => fn() => json_out(['ok' => true, 'ts' => date('c')]),

    // time: devolve horário do servidor
    'GET /time' => fn() => json_out(['server_time' => date('Y-m-d H:i:s')]),

    // soma via query (?a=...&b=...)
    'GET /sum' => function () {
        $a = filter_input(INPUT_GET, 'a', FILTER_VALIDATE_FLOAT);
        $b = filter_input(INPUT_GET, 'b', FILTER_VALIDATE_FLOAT);
        // validação numérica
        if ($a === null || $a === false || $b === null || $b === false) {
            json_out(['error' => 'Params a and b must be numbers'], 400);
        }
        json_out(['a' => $a, 'b' => $b, 'sum' => $a + $b]);
    },

    // echo: devolve o JSON recebido no body
    'POST /echo' => function () {
        $body = body_json();
        json_out(['received' => $body, 'method' => 'POST']);
    },

    // soma via body JSON
    'POST /sum' => function () {
        $b = body_json();
        $a = $b['a'] ?? null;
        $c = $b['b'] ?? null;
        // validação numérica
        if (!is_numeric($a) || !is_numeric($c)) {
            json_out(['error' => 'Body must include numeric a and b'], 400);
        }
        json_out(['a' => (float)$a, 'b' => (float)$c, 'sum' => (float)$a + (float)$c]);
    },
];

// ===== Dispatcher (roteador simples) =====
$key = "$method $path";               // ex.: "GET /ping"

// se existir rota exata, executa e encerra (json_out dá exit)
if (isset($routes[$key])) {
    $routes[$key]();
}

// se o caminho existe com outro método, retorna 405 e envia Allow
$allowed = array_keys(array_filter(
    $routes,
    fn($_, $k) => preg_match('#\s' . preg_quote($path, '#') . '$#', $k),
    ARRAY_FILTER_USE_BOTH
));
if ($allowed) {
    // extrai e deduplica os métodos válidos para esse caminho
    $allow = implode(', ', array_unique(array_map(fn($k) => explode(' ', $k)[0], $allowed)));
    header('Allow: '.$allow);
    json_out(['error' => 'Method not allowed', 'allow' => $allow], 405);
}

// se não bateu nenhuma rota: 404 Not Found
json_out(['error' => 'Not found', 'path' => $path], 404);
