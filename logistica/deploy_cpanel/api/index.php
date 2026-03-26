<?php
/**
 * NTO LOGISTICS - Router Principal
 */

require_once __DIR__ . '/config.php';

setHeaders();

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = preg_replace('#^/api/?#', '', $path);
$path = preg_replace('#^index\.php/?#', '', $path);
$path = trim($path, '/');

if (empty($path)) {
    jsonResponse(['message' => 'NTO Logistics Command API']);
}

$segments = explode('/', $path);
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;
$action = $segments[2] ?? null;

$_GET['id'] = $id;
$_GET['action'] = $action;

switch ($resource) {
    case 'auth':
        $_GET['action'] = $id;
        require __DIR__ . '/auth.php';
        break;
        
    case 'locations':
        require __DIR__ . '/locations.php';
        break;
        
    case 'calls':
        if ($id === 'pending') {
            $_GET['action'] = 'pending';
            $_GET['id'] = null;
        }
        require __DIR__ . '/calls.php';
        break;
        
    case 'shipments':
        if ($id === 'pending') {
            $_GET['action'] = 'pending';
            $_GET['id'] = null;
        }
        require __DIR__ . '/shipments.php';
        break;
        
    case 'users':
        require __DIR__ . '/users.php';
        break;
        
    case 'reports':
        $_GET['action'] = $id ?? 'summary';
        require __DIR__ . '/reports.php';
        break;
        
    case 'expenses':
        if ($id === 'pending-approval' || $id === 'pending-release') {
            $_GET['action'] = $id;
            $_GET['id'] = null;
        }
        require __DIR__ . '/expenses.php';
        break;
        
    case 'seed':
        require __DIR__ . '/seed.php';
        break;
        
    default:
        jsonError('Endpoint não encontrado', 404);
}
