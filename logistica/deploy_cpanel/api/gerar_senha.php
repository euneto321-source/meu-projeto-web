<?php
header('Content-Type: application/json');
$senha = isset($_GET['s']) ? $_GET['s'] : 'senha123';
$hash = password_hash($senha, PASSWORD_BCRYPT);
echo json_encode([
    'senha' => $senha,
    'hash' => $hash,
    'uso' => "UPDATE users SET password_hash = '$hash' WHERE email = 'EMAIL';"
], JSON_PRETTY_PRINT);
