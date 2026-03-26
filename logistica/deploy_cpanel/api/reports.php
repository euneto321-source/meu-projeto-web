<?php
/**
 * NTO LOGÍSTICA - Relatórios Completos
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonError('Método não permitido', 405);

$user = getCurrentUser();
if (!$user) jsonError('Autenticação necessária', 401);

$action = $_GET['action'] ?? 'summary';
$pdo = getConnection();

// Parâmetros de filtro
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$period = $_GET['period'] ?? 'month';

if ($action === 'summary') {
    // === CHAMADOS ===
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM calls");
    $totalCalls = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM calls WHERE status = 'pending'");
    $pendingCalls = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM calls WHERE status = 'in_progress'");
    $inProgressCalls = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM calls WHERE status = 'completed'");
    $completedCalls = $stmt->fetch()['total'];
    
    // === CHAMADOS POR PRIORIDADE ===
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM calls WHERE priority = 'emergency'");
    $emergencyPriority = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM calls WHERE priority = 'urgent'");
    $urgentPriority = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM calls WHERE priority = 'normal'");
    $normalPriority = $stmt->fetch()['total'];
    
    // === ENVIOS/RETIRADAS ===
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shipments");
    $totalShipments = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shipments WHERE type = 'send'");
    $sends = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shipments WHERE type = 'pickup'");
    $pickups = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shipments WHERE status = 'pending'");
    $pendingShipments = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shipments WHERE status = 'completed'");
    $completedShipments = $stmt->fetch()['total'];
    
    // === POR MOTORISTA ===
    $stmt = $pdo->query("
        SELECT 
            assigned_driver_name as driver_name,
            assigned_driver_id as driver_id,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM calls 
        WHERE assigned_driver_id IS NOT NULL
        GROUP BY assigned_driver_id, assigned_driver_name
        ORDER BY completed DESC
    ");
    $callsByDriver = $stmt->fetchAll();
    
    $stmt = $pdo->query("
        SELECT 
            assigned_driver_name as driver_name,
            assigned_driver_id as driver_id,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN type = 'send' THEN 1 ELSE 0 END) as sends,
            SUM(CASE WHEN type = 'pickup' THEN 1 ELSE 0 END) as pickups
        FROM shipments 
        WHERE assigned_driver_id IS NOT NULL
        GROUP BY assigned_driver_id, assigned_driver_name
        ORDER BY completed DESC
    ");
    $shipmentsByDriver = $stmt->fetchAll();
    
    // === POR UNIDADE DE EMERGÊNCIA ===
    $stmt = $pdo->query("
        SELECT 
            origin_name,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM calls
        GROUP BY origin_name
        ORDER BY total DESC
    ");
    $callsByUnit = $stmt->fetchAll();
    
    // === DESPESAS ===
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM expenses WHERE status = 'pending'");
    $pendingExpenses = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM expenses WHERE status = 'approved'");
    $approvedExpenses = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE status = 'released'");
    $releasedAmount = $stmt->fetch()['total'];
    
    // Despesas por setor
    $stmt = $pdo->query("
        SELECT 
            created_by_sector as sector,
            COUNT(*) as total,
            COALESCE(SUM(amount), 0) as total_amount,
            SUM(CASE WHEN status = 'released' THEN amount ELSE 0 END) as released_amount
        FROM expenses
        GROUP BY created_by_sector
        ORDER BY total_amount DESC
    ");
    $expensesBySector = $stmt->fetchAll();
    
    // Despesas por categoria
    $stmt = $pdo->query("
        SELECT 
            category,
            COUNT(*) as total,
            COALESCE(SUM(amount), 0) as total_amount
        FROM expenses
        GROUP BY category
        ORDER BY total_amount DESC
    ");
    $expensesByCategory = $stmt->fetchAll();
    
    // Despesas por setor E categoria
    $stmt = $pdo->query("
        SELECT 
            created_by_sector as sector,
            category,
            COUNT(*) as total,
            COALESCE(SUM(amount), 0) as total_amount
        FROM expenses
        GROUP BY created_by_sector, category
        ORDER BY created_by_sector, total_amount DESC
    ");
    $expensesBySectorCategory = $stmt->fetchAll();
    
    // === TEMPO REAL ===
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM calls WHERE status = 'pending'");
    $realTimePendingCalls = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM calls WHERE status = 'in_progress'");
    $realTimeInProgressCalls = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shipments WHERE status = 'pending'");
    $realTimePendingShipments = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shipments WHERE status = 'in_progress'");
    $realTimeInProgressShipments = $stmt->fetch()['total'];
    
    // Motoristas ativos
    $stmt = $pdo->query("
        SELECT DISTINCT u.id as driver_id, u.name as driver_name,
            (SELECT COUNT(*) FROM calls WHERE assigned_driver_id = u.id AND status = 'in_progress') as calls_in_progress,
            (SELECT COUNT(*) FROM shipments WHERE assigned_driver_id = u.id AND status = 'in_progress') as shipments_in_progress
        FROM users u
        WHERE u.role = 'driver' AND u.is_active = 1
    ");
    $driversStatus = $stmt->fetchAll();
    
    jsonResponse([
        'calls' => [
            'total' => (int) $totalCalls,
            'pending' => (int) $pendingCalls,
            'in_progress' => (int) $inProgressCalls,
            'completed' => (int) $completedCalls,
            'by_priority' => [
                'emergency' => (int) $emergencyPriority,
                'urgent' => (int) $urgentPriority,
                'normal' => (int) $normalPriority
            ],
            'by_driver' => $callsByDriver,
            'by_unit' => $callsByUnit
        ],
        'shipments' => [
            'total' => (int) $totalShipments,
            'sends' => (int) $sends,
            'pickups' => (int) $pickups,
            'pending' => (int) $pendingShipments,
            'completed' => (int) $completedShipments,
            'by_driver' => $shipmentsByDriver
        ],
        'expenses' => [
            'pending' => (int) $pendingExpenses,
            'approved' => (int) $approvedExpenses,
            'released_amount' => (float) $releasedAmount,
            'by_sector' => $expensesBySector,
            'by_category' => $expensesByCategory,
            'by_sector_category' => $expensesBySectorCategory
        ],
        'real_time' => [
            'pending_calls' => (int) $realTimePendingCalls,
            'in_progress_calls' => (int) $realTimeInProgressCalls,
            'pending_shipments' => (int) $realTimePendingShipments,
            'in_progress_shipments' => (int) $realTimeInProgressShipments,
            'drivers' => $driversStatus
        ]
    ]);
    
} elseif ($action === 'daily') {
    $days = (int) ($_GET['days'] ?? 30);
    
    $result = [];
    for ($i = 0; $i < $days; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM calls WHERE DATE(created_at) = ?");
        $stmt->execute([$date]);
        $calls = $stmt->fetch()['c'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM calls WHERE DATE(completed_at) = ?");
        $stmt->execute([$date]);
        $completedCalls = $stmt->fetch()['c'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM shipments WHERE DATE(created_at) = ? AND type = 'send'");
        $stmt->execute([$date]);
        $sends = $stmt->fetch()['c'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM shipments WHERE DATE(created_at) = ? AND type = 'pickup'");
        $stmt->execute([$date]);
        $pickups = $stmt->fetch()['c'];
        
        $result[] = [
            'date' => $date, 
            'calls' => (int)$calls, 
            'completed_calls' => (int)$completedCalls,
            'sends' => (int)$sends, 
            'pickups' => (int)$pickups
        ];
    }
    
    jsonResponse($result);
    
} elseif ($action === 'by-period') {
    // Relatório por período específico
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM calls
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $callsByDate = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            type,
            COUNT(*) as total
        FROM shipments
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at), type
        ORDER BY date DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $shipmentsByDate = $stmt->fetchAll();
    
    jsonResponse([
        'period' => ['start' => $startDate, 'end' => $endDate],
        'calls' => $callsByDate,
        'shipments' => $shipmentsByDate
    ]);
    
} elseif ($action === 'drivers') {
    // Relatório detalhado por motorista
    $driverId = $_GET['driver_id'] ?? null;
    
    $sql = "
        SELECT 
            u.id,
            u.name,
            (SELECT COUNT(*) FROM calls WHERE assigned_driver_id = u.id) as total_calls,
            (SELECT COUNT(*) FROM calls WHERE assigned_driver_id = u.id AND status = 'completed') as completed_calls,
            (SELECT COUNT(*) FROM calls WHERE assigned_driver_id = u.id AND status = 'in_progress') as in_progress_calls,
            (SELECT COUNT(*) FROM shipments WHERE assigned_driver_id = u.id) as total_shipments,
            (SELECT COUNT(*) FROM shipments WHERE assigned_driver_id = u.id AND status = 'completed') as completed_shipments,
            (SELECT COUNT(*) FROM shipments WHERE assigned_driver_id = u.id AND type = 'send') as sends,
            (SELECT COUNT(*) FROM shipments WHERE assigned_driver_id = u.id AND type = 'pickup') as pickups
        FROM users u
        WHERE u.role = 'driver'
    ";
    
    if ($driverId) {
        $sql .= " AND u.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$driverId]);
    } else {
        $stmt = $pdo->query($sql);
    }
    
    jsonResponse($stmt->fetchAll());
    
} else {
    jsonError('Ação não reconhecida', 400);
}
