<?php

// Carregar .env (simples, mas com validação)
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("Arquivo .env não encontrado!");
}
foreach (file($envFile) as $line) {
    if (trim($line) === '' || str_starts_with($line, '#')) continue;
    [$k, $v] = explode('=', $line, 2);
    $k = trim($k);
    $v = trim($v);
    $v = rtrim($v, ";");                 // remove ; do fim, se houver
    $v = trim($v, " \t\n\r\0\x0B'\"");    // tira aspas e espaços
    $_ENV[$k] = $v; 
}

// Validar variáveis obrigatórias
$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($required as $key) {
    if (empty($_ENV[$key])) {
        die("Erro: Variável {$key} não encontrada no .env");
    }
}

// Conectar PDO
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [
            PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT               => 5,
        ]
    );
// Teste de conexão
    echo "✅ Conexão OK com MySQL via .env";
} catch (PDOException $e) {
    die("❌ Erro de conexão: " . $e->getMessage());
}