<?php
/**
 * NTO LOGISTICS - Autenticação
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        if ($method !== 'POST') jsonError('Método não permitido', 405);
        
        $data = getJsonInput();
        if (empty($data['email']) || empty($data['password'])) {
            jsonError('Email e senha são obrigatórios', 400);
        }
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            jsonError('Credenciais inválidas', 401);
        }
        
        $token = createToken($user['id'], $user['email'], $user['role'], $user['name']);
        jsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
        break;
        
    case 'register':
        if ($method !== 'POST') jsonError('Método não permitido', 405);
        
        $data = getJsonInput();
        $required = ['name', 'email', 'password', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) jsonError("Campo '$field' é obrigatório", 400);
        }
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) jsonError('Email já cadastrado', 400);
        
        $userId = generateUUID();
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$userId, $data['name'], $data['email'], $passwordHash, $data['role']]);
        
        $token = createToken($userId, $data['email'], $data['role'], $data['name']);
        jsonResponse([
            'token' => $token,
            'user' => ['id' => $userId, 'name' => $data['name'], 'email' => $data['email'], 'role' => $data['role']]
        ], 201);
        break;
        
    case 'me':
        if ($method !== 'GET') jsonError('Método não permitido', 405);
        $user = requireAuth();
        jsonResponse($user);
        break;
        
    default:
        jsonError('Ação não encontrada', 404);
}
