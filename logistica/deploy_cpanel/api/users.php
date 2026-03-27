<?php
/**
 * NTO LOGISTICS - Usuários
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id']     ?? null;
$action = $_GET['action'] ?? null;

switch ($method) {
    case 'GET':
        requireRoles('admin');
        $pdo  = getConnection();
        $stmt = $pdo->query("SELECT id, name, email, role, is_active, created_at, reset_code, reset_code_expires FROM users ORDER BY created_at DESC");
        jsonResponse($stmt->fetchAll());
        break;

    case 'PUT':
        requireRoles('admin');
        if (!$id) jsonError('ID é obrigatório', 400);
        $pdo  = getConnection();
        $data = getJsonInput();

        if ($action === 'reset-password') {
            // Admin redefine a senha de um usuário diretamente
            if (empty($data['new_password'])) jsonError('Nova senha é obrigatória', 400);
            if (strlen($data['new_password']) < 6) jsonError('A senha deve ter no mínimo 6 caracteres', 400);
            $hash = password_hash($data['new_password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_code = NULL, reset_code_expires = NULL WHERE id = ?");
            $stmt->execute([$hash, $id]);
            if ($stmt->rowCount() === 0) jsonError('Usuário não encontrado', 404);
            jsonResponse(['message' => 'Senha redefinida com sucesso']);
        } else {
            // Toggle ativo/inativo
            $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            if (!$user) jsonError('Usuário não encontrado', 404);
            $newStatus = $user['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);
            jsonResponse(['message' => 'Status atualizado', 'is_active' => (bool) $newStatus]);
        }
        break;

    default:
        jsonError('Método não permitido', 405);
}
