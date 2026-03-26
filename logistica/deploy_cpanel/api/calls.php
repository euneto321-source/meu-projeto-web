<?php
/**
 * NTO LOGÍSTICA - Chamados (v2)
 * Alterações: descrição não obrigatória, tipo fixo = sample_collection (Retirada de Amostras)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$id     = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        $pdo = getConnection();

        // Endpoint público para painel TV / motoristas
        if ($action === 'pending') {
            $stmt = $pdo->query("
                SELECT * FROM calls
                WHERE status IN ('pending', 'in_progress')
                ORDER BY FIELD(priority, 'emergency', 'urgent', 'normal'), created_at DESC
                LIMIT 100
            ");
            jsonResponse($stmt->fetchAll());
        }

        $user = getCurrentUser();

        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM calls WHERE id = ?");
            $stmt->execute([$id]);
            $call = $stmt->fetch();
            if (!$call) jsonError('Chamado não encontrado', 404);
            jsonResponse($call);
        }

        $sql    = "SELECT * FROM calls WHERE 1=1";
        $params = [];

        if (!empty($_GET['status']))      { $sql .= " AND status = ?";              $params[] = $_GET['status']; }
        if (!empty($_GET['priority']))    { $sql .= " AND priority = ?";            $params[] = $_GET['priority']; }
        if (!empty($_GET['start_date']))  { $sql .= " AND DATE(created_at) >= ?";   $params[] = $_GET['start_date']; }
        if (!empty($_GET['end_date']))    { $sql .= " AND DATE(created_at) <= ?";   $params[] = $_GET['end_date']; }
        if (!empty($_GET['origin_name'])) { $sql .= " AND origin_name LIKE ?";     $params[] = '%' . $_GET['origin_name'] . '%'; }
        if (!empty($_GET['driver_id']))   { $sql .= " AND assigned_driver_id = ?"; $params[] = $_GET['driver_id']; }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse($stmt->fetchAll());
        break;

    case 'POST':
        $user = getCurrentUser();
        if (!$user) jsonError('Autenticação necessária', 401);

        $data = getJsonInput();

        // Tipo fixo: apenas Retirada de Amostras
        $data['type']     = 'sample_collection';
        $data['priority'] = $data['priority'] ?? 'normal';

        // Descrição não obrigatória
        $description = $data['description'] ?? '';

        $originName = !empty($data['origin_name']) ? $data['origin_name'] : $user['name'];

        try {
            $pdo    = getConnection();
            $callId = generateUUID();

            $stmt = $pdo->prepare("
                INSERT INTO calls
                    (id, origin_id, origin_name, description, priority, type, status, created_by_id, created_by_name, created_at)
                VALUES
                    (?, ?, ?, ?, ?, 'sample_collection', 'pending', ?, ?, NOW())
            ");

            $result = $stmt->execute([
                $callId,
                $data['origin_id'] ?? null,
                $originName,
                $description,
                $data['priority'],
                $user['user_id'],
                $user['name']
            ]);

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                jsonError('Erro ao criar chamado: ' . ($errorInfo[2] ?? 'Erro desconhecido'), 500);
            }

            jsonResponse([
                'id'          => $callId,
                'origin_name' => $originName,
                'description' => $description,
                'priority'    => $data['priority'],
                'type'        => 'sample_collection',
                'status'      => 'pending',
                'created_at'  => date('Y-m-d H:i:s')
            ], 201);

        } catch (PDOException $e) {
            jsonError('Erro de banco: ' . $e->getMessage(), 500);
        }
        break;

    case 'PUT':
        if (!$id) jsonError('ID é obrigatório', 400);
        $user = getCurrentUser();
        if (!$user) jsonError('Autenticação necessária', 401);

        $pdo = getConnection();

        if ($action === 'assign') {
            $stmt = $pdo->prepare("
                UPDATE calls
                SET assigned_driver_id   = ?,
                    assigned_driver_name = ?,
                    status               = 'in_progress'
                WHERE id = ?
            ");
            $stmt->execute([$user['user_id'], $user['name'], $id]);

        } elseif ($action === 'complete') {
            $data = getJsonInput();
            $stmt = $pdo->prepare("
                UPDATE calls
                SET status       = 'completed',
                    completed_at = NOW(),
                    notes        = ?
                WHERE id = ?
            ");
            $stmt->execute([$data['notes'] ?? null, $id]);

        } elseif ($action === 'arrival') {
            $stmt = $pdo->prepare("UPDATE calls SET arrival_at_nto = NOW() WHERE id = ?");
            $stmt->execute([$id]);

        } else {
            jsonError('Ação não reconhecida', 400);
        }

        if ($stmt->rowCount() === 0) jsonError('Chamado não encontrado', 404);
        jsonResponse(['message' => 'Operação realizada']);
        break;

    default:
        jsonError('Método não permitido', 405);
}
