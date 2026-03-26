<?php
/**
 * NTO LOGISTICS - Usuários
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        requireRoles('admin');
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT id, name, email, role, is_active, created_at FROM users ORDER BY created_at DESC");
        jsonResponse($stmt->fetchAll());
        break;
        
    case 'PUT':
        requireRoles('admin');
        if (!$id) jsonError('ID é obrigatório', 400);
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) jsonError('Usuário não encontrado', 404);
        
        $newStatus = $user['is_active'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
        
        jsonResponse(['message' => 'Status atualizado', 'is_active' => (bool) $newStatus]);
        break;
        
    default:
        jsonError('Método não permitido', 405);
}
