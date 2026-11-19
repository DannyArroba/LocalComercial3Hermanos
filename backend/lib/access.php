<?php

function appPermissionKeys(): array {
    return ['dashboard', 'sales', 'inventory', 'transactions', 'suppliers', 'customers'];
}

function appDefaultPermissions(): array {
    return ['sales', 'inventory'];
}

function appUserPayload(array $row): array {
    $role = ($row['role'] ?? '') === 'admin' ? 'admin' : 'cashier';
    $name = trim((string)($row['name'] ?? ''));
    if ($name === '') $name = explode('@', (string)($row['email'] ?? 'Usuario'))[0];
    $permissions = appDefaultPermissions();
    if (array_key_exists('permissions_json', $row) && $row['permissions_json'] !== null) {
        $decoded = json_decode((string)$row['permissions_json'], true);
        // Invalid stored data must not accidentally grant access.
        $permissions = is_array($decoded) && array_is_list($decoded)
            ? array_values(array_intersect(appPermissionKeys(), array_filter($decoded, 'is_string'))) : [];
    }
    if ($role === 'admin') $permissions = appPermissionKeys();
    $payload = [
        'id' => (int)$row['id'], 'name' => $name,
        'email' => (string)($row['email'] ?? ''), 'role' => $role,
        'permissions' => $permissions,
    ];
    if (isset($row['created_at'])) $payload['created_at'] = $row['created_at'];
    return $payload;
}

function appCurrentUser(mysqli $conn): ?array {
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id <= 0) return null;
    // Cache only within this PHP request/connection, never across session requests.
    static $cache = [];
    $key = spl_object_id($conn) . ':' . $id;
    if (!array_key_exists($key, $cache)) {
        $stmt = $conn->prepare('SELECT id, name, email, role, permissions_json, created_at FROM users WHERE id = ?');
        $stmt->bind_param('i', $id); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $cache[$key] = $row ? appUserPayload($row) : null;
    }
    return $cache[$key];
}

function appHasPermission(?array $user, string|array $keys, bool $adminOnly = false): bool {
    if (!$user) return false;
    if (($user['role'] ?? '') === 'admin') return true;
    if ($adminOnly) return false;
    foreach ((array)$keys as $key) {
        if (in_array($key, appPermissionKeys(), true) && in_array($key, $user['permissions'] ?? [], true)) return true;
    }
    return false;
}

function appRequirePermission(mysqli $conn, string|array $keys, bool $adminOnly = false): array {
    $user = appCurrentUser($conn);
    if (!appHasPermission($user, $keys, $adminOnly)) {
        http_response_code($user ? 403 : 401);
        echo json_encode(['status' => 'error', 'message' => $user ? 'No tienes acceso a esta sección.' : 'Inicia sesión nuevamente.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return $user;
}
