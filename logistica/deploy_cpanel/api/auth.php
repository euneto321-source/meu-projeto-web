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
        if (empty($data['email']) || empty($data['password'])) jsonError('Email e senha são obrigatórios', 400);

        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($data['password'], $user['password_hash'])) jsonError('Credenciais inválidas', 401);

        $token = createToken($user['id'], $user['email'], $user['role'], $user['name']);
        jsonResponse([
            'token' => $token,
            'user'  => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']]
        ]);
        break;

    case 'register':
        if ($method !== 'POST') jsonError('Método não permitido', 405);
        $data = getJsonInput();
        foreach (['name','email','password','role'] as $f) {
            if (empty($data[$f])) jsonError("Campo '$f' é obrigatório", 400);
        }
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) jsonError('Email já cadastrado', 400);

        $userId = generateUUID();
        $hash   = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt   = $pdo->prepare("INSERT INTO users (id, name, email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$userId, $data['name'], $data['email'], $hash, $data['role']]);

        $token = createToken($userId, $data['email'], $data['role'], $data['name']);
        jsonResponse(['token' => $token, 'user' => ['id' => $userId, 'name' => $data['name'], 'email' => $data['email'], 'role' => $data['role']]], 201);
        break;

    case 'me':
        if ($method !== 'GET') jsonError('Método não permitido', 405);
        $user = requireAuth();
        jsonResponse($user);
        break;

    // Solicitar reset: gera código de 6 dígitos, armazena no banco
    case 'request-reset':
        if ($method !== 'POST') jsonError('Método não permitido', 405);
        $data = getJsonInput();
        if (empty($data['email'])) jsonError('Email é obrigatório', 400);

        $pdo  = getConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();

        // Sempre retorna sucesso para não revelar se email existe
        if (!$user) {
            jsonResponse(['message' => 'Se o e-mail existir, um código foi gerado.']);
        }

        $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt    = $pdo->prepare("UPDATE users SET reset_code = ?, reset_code_expires = ? WHERE id = ?");
        $stmt->execute([$code, $expires, $user['id']]);

        jsonResponse(['message' => 'Código gerado. Contate o administrador para obtê-lo.']);
        break;

    // Redefinir senha com código
    case 'do-reset':
        if ($method !== 'POST') jsonError('Método não permitido', 405);
        $data = getJsonInput();
        if (empty($data['email']) || empty($data['code']) || empty($data['new_password'])) {
            jsonError('E-mail, código e nova senha são obrigatórios', 400);
        }
        if (strlen($data['new_password']) < 6) jsonError('A senha deve ter no mínimo 6 caracteres', 400);

        $pdo  = getConnection();
        $stmt = $pdo->prepare("SELECT id, reset_code, reset_code_expires FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();

        if (!$user || $user['reset_code'] !== $data['code']) jsonError('Código inválido', 400);
        if (strtotime($user['reset_code_expires']) < time()) jsonError('Código expirado. Solicite um novo.', 400);

        $hash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_code = NULL, reset_code_expires = NULL WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);

        jsonResponse(['message' => 'Senha redefinida com sucesso!']);
        break;

    // Alterar senha (usuário logado)
    case 'change-password':
        if ($method !== 'POST') jsonError('Método não permitido', 405);
        $auth = requireAuth();
        $data = getJsonInput();
        if (empty($data['current_password']) || empty($data['new_password'])) jsonError('Senhas são obrigatórias', 400);
        if (strlen($data['new_password']) < 6) jsonError('A nova senha deve ter no mínimo 6 caracteres', 400);

        $pdo  = getConnection();
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$auth['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($data['current_password'], $user['password_hash'])) jsonError('Senha atual incorreta', 401);

        $hash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $auth['user_id']]);

        jsonResponse(['message' => 'Senha alterada com sucesso!']);
        break;

    default:
        jsonError('Ação não encontrada', 404);
}
