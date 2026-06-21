<?php
/**
 * Auth Helper — RBAC for Koperasi Mitra Dhuafa Risk App
 * 
 * Roles: administrator > manager > staff
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Pastikan user sudah login. Jika belum, redirect ke login.php.
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        $loginUrl = getBaseUrl() . '/login.php';
        header('Location: ' . $loginUrl);
        exit;
    }
}

/**
 * Pastikan user memiliki salah satu role yang diizinkan.
 * Jika tidak, redirect ke halaman 403.
 */
function requireRole(array $allowedRoles): void {
    requireLogin();
    $currentRole = $_SESSION['user_role'] ?? '';
    if (!in_array($currentRole, $allowedRoles, true)) {
        $url403 = getBaseUrl() . '/403.php';
        header('Location: ' . $url403);
        exit;
    }
}

/**
 * Cek apakah user saat ini memiliki role tertentu.
 */
function hasRole(string $role): bool {
    return ($_SESSION['user_role'] ?? '') === $role;
}

/**
 * Cek apakah user saat ini memiliki salah satu role dari array.
 */
function hasAnyRole(array $roles): bool {
    return in_array($_SESSION['user_role'] ?? '', $roles, true);
}

/**
 * Ambil data user yang sedang login dari session.
 */
function getCurrentUser(): array {
    return [
        'id'       => $_SESSION['user_id']       ?? null,
        'nama'     => $_SESSION['user_nama']      ?? 'Tamu',
        'username' => $_SESSION['user_username']  ?? '',
        'role'     => $_SESSION['user_role']      ?? '',
    ];
}

/**
 * Kembalikan label role yang ramah untuk ditampilkan.
 */
function getRoleLabel(string $role): string {
    return match($role) {
        'administrator' => 'Administrator',
        'manager'       => 'Manager',
        'staff'         => 'Staff',
        default         => 'Tidak Diketahui',
    };
}

/**
 * Kembalikan CSS class badge untuk role.
 */
function getRoleBadgeClass(string $role): string {
    return match($role) {
        'administrator' => 'role-badge-admin',
        'manager'       => 'role-badge-manager',
        'staff'         => 'role-badge-staff',
        default         => 'role-badge-staff',
    };
}

/**
 * Hancurkan sesi dan redirect ke login.
 */
function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    $loginUrl = getBaseUrl() . '/login.php';
    header('Location: ' . $loginUrl);
    exit;
}

/**
 * Dapatkan base URL dari aplikasi web ini.
 */
function getBaseUrl(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($scriptName);
    
    // Normalize directory separators
    $dirNormalized = str_replace('\\', '/', $dir);
    
    // If it points to root or is empty, return empty string
    if ($dirNormalized === '/' || $dirNormalized === '.' || empty($dirNormalized)) {
        return '';
    }
    
    return rtrim($dirNormalized, '/');
}
?>
