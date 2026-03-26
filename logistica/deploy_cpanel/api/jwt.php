<?php
/**
 * NTO LOGISTICS - JWT Functions
 */

require_once __DIR__ . '/config.php';

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function createToken($userId, $email, $role, $name) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'email' => $email,
        'role' => $role,
        'name' => $name,
        'exp' => time() + JWT_EXPIRATION
    ]);
    
    $base64Header = base64UrlEncode($header);
    $base64Payload = base64UrlEncode($payload);
    $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, JWT_SECRET, true);
    
    return $base64Header . '.' . $base64Payload . '.' . base64UrlEncode($signature);
}

function verifyToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    
    list($base64Header, $base64Payload, $base64Signature) = $parts;
    
    $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, JWT_SECRET, true);
    if (!hash_equals(base64UrlEncode($signature), $base64Signature)) return null;
    
    $payload = json_decode(base64UrlDecode($base64Payload), true);
    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) return null;
    
    return $payload;
}

function getCurrentUser() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        return null;
    }
    return verifyToken($matches[1]);
}

function requireAuth() {
    $user = getCurrentUser();
    if (!$user) jsonError('Token inválido ou expirado', 401);
    return $user;
}

function requireRoles(...$roles) {
    $user = requireAuth();
    if (!in_array($user['role'], $roles)) jsonError('Acesso não autorizado', 403);
    return $user;
}
