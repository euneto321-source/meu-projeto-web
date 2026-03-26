<?php
/**
 * NTO LOGISTICS - Criar Dados Iniciais
 */

require_once __DIR__ . '/config.php';

setHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método não permitido', 405);

$pdo = getConnection();

// Verificar se já existe
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute(['admin@nto.com']);
if ($stmt->fetch()) {
    jsonResponse(['message' => 'Dados já existem']);
}

try {
    $pdo->beginTransaction();
    
    // Admin (senha: admin123)
    $adminId = generateUUID();
    $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password_hash, role, is_active, created_at) VALUES (?, 'Administrador', 'admin@nto.com', ?, 'admin', 1, NOW())");
    $stmt->execute([$adminId, password_hash('admin123', PASSWORD_BCRYPT)]);
    
    // Motorista (senha: driver123)
    $driverId = generateUUID();
    $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password_hash, role, is_active, created_at) VALUES (?, 'João Motorista', 'motorista@nto.com', ?, 'driver', 1, NOW())");
    $stmt->execute([$driverId, password_hash('driver123', PASSWORD_BCRYPT)]);
    
    // Locais
    $locations = [
        ['Hospital de Emergência Oswaldo Cruz', 'emergency_unit'],
        ['Hospital Universitário (HU) – UNIFAP', 'emergency_unit'],
        ['Maternidade Zona Norte Bem Nascer', 'emergency_unit'],
        ['Setor de TI', 'internal_sector'],
        ['Unidade Infraero', 'delivery_point'],
        ['NTO Central', 'internal_sector'],
    ];
    
    $locIds = [];
    foreach ($locations as $loc) {
        $locId = generateUUID();
        $locIds[] = $locId;
        $stmt = $pdo->prepare("INSERT INTO locations (id, name, type, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
        $stmt->execute([$locId, $loc[0], $loc[1]]);
    }
    
    // Chamados exemplo
    $callId1 = generateUUID();
    $stmt = $pdo->prepare("INSERT INTO calls (id, origin_id, origin_name, description, priority, type, status, created_at) VALUES (?, ?, 'Hospital de Emergência Oswaldo Cruz', 'Amostras disponíveis para coleta', 'urgent', 'sample_collection', 'pending', NOW())");
    $stmt->execute([$callId1, $locIds[0]]);
    
    $callId2 = generateUUID();
    $stmt = $pdo->prepare("INSERT INTO calls (id, origin_id, origin_name, description, priority, type, status, created_at) VALUES (?, ?, 'Hospital Universitário (HU) – UNIFAP', 'Material biológico para análise', 'emergency', 'sample_collection', 'pending', NOW())");
    $stmt->execute([$callId2, $locIds[1]]);
    
    $pdo->commit();
    
    jsonResponse([
        'message' => 'Dados iniciais criados',
        'admin_email' => 'admin@nto.com',
        'admin_password' => 'admin123'
    ], 201);
    
} catch (Exception $e) {
    $pdo->rollBack();
    jsonError('Erro: ' . $e->getMessage(), 500);
}
