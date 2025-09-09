<?php
// Carregar .env
foreach (file(__DIR__ . '/.env') as $line) {
    if (trim($line) === '' || str_starts_with($line, '#')) continue;
    [$k, $v] = explode('=', $line, 2);
    $_ENV[trim($k)] = trim($v);
}

// Definir constantes a partir do .env
$_ENV['DB_HOST'];
$_ENV['DB_NAME'];
$_ENV['DB_USER'];
$_ENV['CORS_ALLOW_ORIGIN'];
$_ENV['API_KEY'];

// Pegar senha do .env
$dbPass = $_ENV['DB_PASS'] ?? '';

// Criar conexão PDO usando o que veio do .env
$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4',
    $_ENV['DB_USER'],
    $dbPass,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// Teste simples (remova em produção!)
$statement = $pdo->query("SELECT 'Conexão OK com MySQL via .env' AS msg");
$row = $statement->fetch();
echo htmlentities($row['msg']);

