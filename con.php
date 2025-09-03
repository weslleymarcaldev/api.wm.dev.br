<?php
// mysqli
$mysqli = new mysqli("api.wm.dev.br", "weslley", "W3$ll3y1984M325@zugF", "apiwm");
$result = $mysqli->query("SELECT 'Olá, prezado usuário MySQL!' AS _message FROM DUAL");
$row = $result->fetch_assoc();
echo htmlentities($row['_message']);

// PDO
$pdo = new PDO('mysql:host=api.wm.dev.br;dbname=apiwm', 'weslley', 'W3$ll3y1984M325@zugF');
$statement = $pdo->query("SELECT 'Olá, prezado usuário MySQL!' AS _message FROM DUAL");
$row = $statement->fetch(PDO::FETCH_ASSOC);
echo htmlentities($row['_message']);