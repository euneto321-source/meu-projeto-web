<?php
/**
 * NTO LOGISTICS - Configuração do Banco de Dados
 * ALTERE AS CREDENCIAIS ABAIXO
 */

// ========== CONFIGURAÇÕES DO BANCO ==========
define('DB_HOST', 'localhost');
define('DB_NAME', 'NOME_DO_SEU_BANCO');    // Altere aqui
define('DB_USER', 'SEU_USUARIO');          // Altere aqui
define('DB_PASS', 'SUA_SENHA');            // Altere aqui

// ========== CONFIGURAÇÕES JWT ==========
define('JWT_SECRET', 'nto-logistics-chave-secreta-2024');
define('JWT_EXPIRATION', 86400); // 24 horas

// ========== CORS ==========
define('CORS_ORIGIN', '*');

// ========== TIMEZONE ==========
date_default_timezone_set('America/Sao_Paulo');

// ========== CONEXÃO PDO ==========
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        // Forçar fuso horário de Brasília na sessão MySQL
        // Isso garante que NOW() retorne o horário local correto
        $pdo->exec("SET time_zone = '-03:00'");
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['error' => 'Erro de conexão: ' . $e->getMessage()]));
    }
}

// ========== HEADERS ==========
function setHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: ' . CORS_ORIGIN);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// ========== FUNÇÕES AUXILIARES ==========
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function jsonError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['detail' => $message], JSON_UNESCAPED_UNICODE);
    exit();
}
