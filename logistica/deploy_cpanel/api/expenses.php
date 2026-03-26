<?php
/**
 * NTO LOGISTICS - Despesas (v2 - Novos campos + fluxo simultâneo)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$id     = $_GET['id'] ?? null;

switch ($method) {

    case 'GET':
        $user = requireAuth();
        $pdo  = getConnection();

        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            $expense = $stmt->fetch();
            if (!$expense) jsonError('Despesa não encontrada', 404);
            jsonResponse($expense);
        }

        if ($action === 'pending-approval') {
            requireRoles('admin', 'approval');
            $stmt = $pdo->query("SELECT * FROM expenses WHERE status = 'pending' ORDER BY created_at DESC");
            jsonResponse($stmt->fetchAll());
        }

        // Lista completa por role
        if (in_array($user['role'], ['admin', 'financial', 'approval'])) {
            $status = $_GET['status'] ?? null;
            $sql    = "SELECT * FROM expenses WHERE 1=1";
            $params = [];
            if ($status) { $sql .= " AND status = ?"; $params[] = $status; }
            $sql .= " ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }

        // Sector: só as próprias
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE created_by_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user['user_id']]);
        jsonResponse($stmt->fetchAll());
        break;

    case 'POST':
        $user = requireAuth();
        $data = getJsonInput();

        // Nenhum campo obrigatório — apenas salva o que foi enviado
        $pdo       = getConnection();
        $expenseId = generateUUID();

        $stmt = $pdo->prepare("
            INSERT INTO expenses (
                id, description, amount, category, status,
                created_by_id, created_by_name, created_by_sector, created_at,
                solicitante, data_solicitacao, centro_custo, processo,
                classificacao_fin, competencia, beneficiario, cpf_cnpj,
                forma_pagamento, emite_nota_fiscal,
                titular, banco, agencia, conta, pix_chave,
                data_limite_pag, obs
            ) VALUES (
                ?, ?, ?, 'solicitacao', 'pending',
                ?, ?, ?, NOW(),
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?
            )
        ");

        $stmt->execute([
            $expenseId,
            $data['pagamento_ref']      ?? null,
            $data['valor']              ?? 0,
            $user['user_id'],
            $user['name'],
            $data['setor']              ?? $user['name'],
            $data['solicitante']        ?? null,
            $data['data_solicitacao']   ?? null,
            $data['centro_custo']       ?? null,
            $data['processo']           ?? null,
            $data['classificacao_fin']  ?? null,
            $data['competencia']        ?? null,
            $data['beneficiario']       ?? null,
            $data['cpf_cnpj']           ?? null,
            $data['forma_pagamento']    ?? null,
            $data['emite_nota_fiscal']  ?? null,
            $data['titular']            ?? null,
            $data['banco']              ?? null,
            $data['agencia']            ?? null,
            $data['conta']              ?? null,
            $data['pix_chave']          ?? null,
            $data['data_limite_pag']    ?? null,
            $data['obs']                ?? null,
        ]);

        jsonResponse([
            'id'         => $expenseId,
            'status'     => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ], 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID é obrigatório', 400);
        $user = requireAuth();
        $pdo  = getConnection();
        $data = getJsonInput();

        if ($action === 'approve') {
            requireRoles('admin', 'approval');
            $stmt = $pdo->prepare("UPDATE expenses SET status='approved', approved_by_id=?, approved_by_name=?, approved_at=NOW() WHERE id=? AND status='pending'");
            $stmt->execute([$user['user_id'], $user['name'], $id]);
        } elseif ($action === 'reject') {
            requireRoles('admin', 'approval');
            $stmt = $pdo->prepare("UPDATE expenses SET status='rejected', approved_by_id=?, approved_by_name=?, approved_at=NOW(), rejection_reason=? WHERE id=? AND status='pending'");
            $stmt->execute([$user['user_id'], $user['name'], $data['reason'] ?? null, $id]);
        } elseif ($action === 'release') {
            requireRoles('admin', 'financial');
            $stmt = $pdo->prepare("UPDATE expenses SET status='released', released_by_id=?, released_by_name=?, released_at=NOW() WHERE id=? AND status='approved'");
            $stmt->execute([$user['user_id'], $user['name'], $id]);
        } else {
            jsonError('Ação não reconhecida', 400);
        }

        if ($stmt->rowCount() === 0) jsonError('Despesa não encontrada ou status inválido', 404);
        jsonResponse(['message' => 'Operação realizada com sucesso']);
        break;

    default:
        jsonError('Método não permitido', 405);
}
