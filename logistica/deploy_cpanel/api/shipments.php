<?php
/**
 * NTO LOGISTICS - Envios/Retiradas
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        $pdo = getConnection();
        
        // Endpoint público para TV
        if ($action === 'pending') {
            $stmt = $pdo->query("SELECT * FROM shipments WHERE status IN ('pending', 'in_progress') ORDER BY FIELD(priority, 'urgent', 'normal'), created_at DESC LIMIT 100");
            jsonResponse($stmt->fetchAll());
        } 
        
        // Demais endpoints requerem auth
        requireAuth();
        
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ?");
            $stmt->execute([$id]);
            $shipment = $stmt->fetch();
            if (!$shipment) jsonError('Envio não encontrado', 404);
            jsonResponse($shipment);
        } else {
            $sql = "SELECT * FROM shipments WHERE 1=1";
            $params = [];
            
            if (!empty($_GET['status'])) { $sql .= " AND status = ?"; $params[] = $_GET['status']; }
            if (!empty($_GET['type'])) { $sql .= " AND type = ?"; $params[] = $_GET['type']; }
            
            $sql .= " ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;
        
    case 'POST':
        $user = requireAuth();
        $data = getJsonInput();
        
        $required = ['origin_sector', 'destination_name', 'description', 'priority', 'type'];
        foreach ($required as $field) {
            if (empty($data[$field])) jsonError("Campo '$field' é obrigatório", 400);
        }
        
        $pdo = getConnection();
        $shipId = generateUUID();
        $stmt = $pdo->prepare("INSERT INTO shipments (id, origin_sector, destination_id, destination_name, description, priority, type, status, created_by_id, created_by_name, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())");
        $stmt->execute([$shipId, $data['origin_sector'], $data['destination_id'] ?? null, $data['destination_name'], $data['description'], $data['priority'], $data['type'], $user['user_id'], $user['name']]);
        
        jsonResponse([
            'id' => $shipId,
            'origin_sector' => $data['origin_sector'],
            'destination_name' => $data['destination_name'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ], 201);
        break;
        
    case 'PUT':
        if (!$id) jsonError('ID é obrigatório', 400);
        $user = requireAuth();
        $pdo = getConnection();
        
        if ($action === 'assign') {
            $stmt = $pdo->prepare("UPDATE shipments SET assigned_driver_id = ?, assigned_driver_name = ?, status = 'in_progress' WHERE id = ?");
            $stmt->execute([$user['user_id'], $user['name'], $id]);
        } elseif ($action === 'complete') {
            $data = getJsonInput();
            $stmt = $pdo->prepare("UPDATE shipments SET status = 'completed', completed_at = NOW(), notes = ? WHERE id = ?");
            $stmt->execute([$data['notes'] ?? null, $id]);
        } else {
            jsonError('Ação não reconhecida', 400);
        }
        
        if ($stmt->rowCount() === 0) jsonError('Envio não encontrado', 404);
        jsonResponse(['message' => 'Operação realizada']);
        break;
        
    default:
        jsonError('Método não permitido', 405);
}
