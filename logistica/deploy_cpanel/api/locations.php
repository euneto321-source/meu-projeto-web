<?php
/**
 * NTO LOGISTICS - Locais
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        $pdo = getConnection();
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ? AND is_active = 1");
            $stmt->execute([$id]);
            $location = $stmt->fetch();
            if (!$location) jsonError('Local não encontrado', 404);
            jsonResponse($location);
        } else {
            $stmt = $pdo->query("SELECT * FROM locations WHERE is_active = 1 ORDER BY name");
            jsonResponse($stmt->fetchAll());
        }
        break;
        
    case 'POST':
        requireRoles('admin');
        $data = getJsonInput();
        if (empty($data['name']) || empty($data['type'])) jsonError('Nome e tipo são obrigatórios', 400);
        
        $pdo = getConnection();
        $locId = generateUUID();
        $stmt = $pdo->prepare("INSERT INTO locations (id, name, type, address, contact, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$locId, $data['name'], $data['type'], $data['address'] ?? null, $data['contact'] ?? null]);
        
        jsonResponse(['id' => $locId, 'name' => $data['name'], 'type' => $data['type'], 'is_active' => true], 201);
        break;
        
    case 'PUT':
        requireRoles('admin');
        if (!$id) jsonError('ID é obrigatório', 400);
        
        $data = getJsonInput();
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE locations SET name = ?, type = ?, address = ?, contact = ? WHERE id = ?");
        $stmt->execute([$data['name'], $data['type'], $data['address'] ?? null, $data['contact'] ?? null, $id]);
        
        if ($stmt->rowCount() === 0) jsonError('Local não encontrado', 404);
        jsonResponse(['message' => 'Local atualizado']);
        break;
        
    case 'DELETE':
        requireRoles('admin');
        if (!$id) jsonError('ID é obrigatório', 400);
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE locations SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) jsonError('Local não encontrado', 404);
        jsonResponse(['message' => 'Local removido']);
        break;
        
    default:
        jsonError('Método não permitido', 405);
}
