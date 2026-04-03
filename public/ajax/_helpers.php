<?php
// ajax/_helpers.php
// Shared helpers untuk AJAX endpoints: rate limiting, permission checks, caching

/**
 * Extract CSRF token from common request sources.
 */
function getRequestCsrfToken(): string {
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (is_string($headerToken) && $headerToken !== '') {
        return trim($headerToken);
    }

    $postToken = $_POST['csrf_token'] ?? '';
    if (is_string($postToken) && $postToken !== '') {
        return trim($postToken);
    }

    return '';
}

/**
 * Validate request CSRF token against the current session token.
 */
function isValidCsrfToken(?string $token = null): bool {
    $token = $token ?? getRequestCsrfToken();
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
    return $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

/**
 * Standard JSON error response for admin AJAX endpoints.
 */
function jsonErrorResponse(string $message, int $status = 400): never {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => true, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Standard JSON success response for admin AJAX endpoints.
 *
 * @param array<string,mixed> $data
 */
function jsonSuccessResponse(array $data = [], int $status = 200): never {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => true, 'error' => false], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Log unexpected buffered output from includes/bootstrap without exposing it to the client.
 */
function logAjaxUnexpectedOutput(string $context, string $output, int $limit = 200): void {
    $output = trim($output);
    if ($output === '') {
        return;
    }
    error_log(sprintf('[%s] Unexpected output: %s', $context, substr($output, 0, $limit)));
}

/**
 * Simple rate limiting (per session)
 * @param string $key Unique key untuk rate limit
 * @param int $maxRequests Maximum requests dalam window
 * @param int $windowSeconds Time window dalam seconds
 * @return bool True jika allowed, false jika rate limited
 */
function checkRateLimit(string $key, int $maxRequests = 30, int $windowSeconds = 60): bool {
    $now = time();
    $rateKey = 'rate_limit_' . $key;
    
    if (!isset($_SESSION[$rateKey])) {
        $_SESSION[$rateKey] = ['count' => 0, 'reset' => $now + $windowSeconds];
    }
    
    $rate = &$_SESSION[$rateKey];
    
    // Reset if window expired
    if ($now >= $rate['reset']) {
        $rate = ['count' => 0, 'reset' => $now + $windowSeconds];
    }
    
    // Check limit
    if ($rate['count'] >= $maxRequests) {
        return false;
    }
    
    $rate['count']++;
    return true;
}

/**
 * Check if user has permission to manage groups
 * SECURITY CRITICAL – DO NOT MODIFY: UI gating helper for group management
 * @param PDO $pdo Database connection
 * @return bool True jika user ada permission
 */
function hasGroupManagePermission(PDO $pdo): bool {
    require_once __DIR__ . '/../setting/constants/prestasi_constants.php';
    if (empty($_SESSION['f_stafID'])) {
        return false;
    }
    
    try {
        require_once __DIR__ . '/../classes/User.php';
        $userModel = new User($pdo);
        $profile = $userModel->getProfile($_SESSION['f_stafID']);
        
        if (!$profile) {
            return false;
        }
        
        // Super Admin (role aktif-aware + groupKod fallback) boleh manage semua kumpulan
        if (function_exists('is_user_super_admin') && is_user_super_admin($profile, $pdo)) {
            return true;
        }
        
        // Boleh tambah group lain yang ada permission di sini
        // Contoh: Admin HR boleh manage kumpulan HR sahaja
        
        return false;
    } catch (Throwable $e) {
        error_log('[hasGroupManagePermission] Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Enforce admin permission for AJAX endpoints in the group/admin area.
 */
function ensureAjaxGroupManagePermission(PDO $pdo, ?string $message = null): void {
    if (!hasGroupManagePermission($pdo)) {
        jsonErrorResponse($message ?: (string)(__('userList_err_no_permission') ?: 'Anda tidak mempunyai kebenaran untuk melakukan tindakan ini.'), 403);
    }
}

/**
 * Enforce admin permission for HTML pages in the group/admin area.
 */
function ensurePageGroupManagePermission(PDO $pdo, ?string $message = null): void {
    if (hasGroupManagePermission($pdo)) {
        return;
    }

    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="' . htmlspecialchars((string)($_SESSION['lang'] ?? 'ms'), ENT_QUOTES, 'UTF-8') . '"><head><meta charset="utf-8"><title>403</title></head><body>';
    echo htmlspecialchars($message ?: (string)(__('userList_err_no_permission') ?: 'Anda tidak mempunyai kebenaran untuk melakukan tindakan ini.'), ENT_QUOTES, 'UTF-8');
    echo '</body></html>';
    exit;
}

/**
 * Cache helper untuk group/module/menu data (session-based cache dengan TTL)
 */
final class GroupDataCache {
    private static string $namespace = 'groupdata_cache';
    
    public static function get(string $key, int $ttl): mixed {
        $now = time();
        $c = $_SESSION[self::$namespace][$key] ?? null;
        if (!$c) return null;
        if (($c['ts'] + $ttl) < $now) {
            unset($_SESSION[self::$namespace][$key]);
            return null;
        }
        return $c['val'];
    }
    
    public static function set(string $key, mixed $val): void {
        if (!isset($_SESSION[self::$namespace])) {
            $_SESSION[self::$namespace] = [];
        }
        $_SESSION[self::$namespace][$key] = ['ts' => time(), 'val' => $val];
    }
    
    public static function clear(?string $prefix = null): void {
        if (!isset($_SESSION[self::$namespace])) return;
        if ($prefix === null) {
            unset($_SESSION[self::$namespace]);
            return;
        }
        foreach (array_keys($_SESSION[self::$namespace]) as $k) {
            if (str_starts_with($k, $prefix)) {
                unset($_SESSION[self::$namespace][$k]);
            }
        }
    }
}

/**
 * Clear caches that can affect group UI/style resolution.
 * - GroupDataCache (permissions/access)
 * - User list session cache key used by pages/senarai-pengguna.php
 *
 * @param int|null $groupId Optional group ID for targeted invalidation.
 */
function clearGroupUiCaches(?int $groupId = null): void {
    // Invalidate session cache used by senarai-pengguna.php (UserListCache::namespace = userlist_cache)
    if (isset($_SESSION['userlist_cache']) && is_array($_SESSION['userlist_cache'])) {
        foreach (array_keys($_SESSION['userlist_cache']) as $k) {
            if ($k === 'group_list' || str_starts_with($k, 'group_list')) {
                unset($_SESSION['userlist_cache'][$k]);
            }
        }
    }

    // Invalidate group permission/access caches used by AJAX endpoints
    if ($groupId !== null && $groupId > 0) {
        GroupDataCache::clear('group_perms_' . $groupId);
        GroupDataCache::clear('group_access_' . $groupId);
    } else {
        GroupDataCache::clear('group_perms_');
        GroupDataCache::clear('group_access_');
    }
}

/**
 * Clear sidebar navigation caches for the current session and shared APCu cache.
 * This should be called after any change that can affect visible sidebar modules/menus.
 */
function clearSidebarNavigationCaches(): void {
    if (isset($_SESSION['sidebar_cache'])) {
        unset($_SESSION['sidebar_cache']);
    }

    if (function_exists('apcu_delete')) {
        try {
            if (class_exists('APCUIterator')) {
                $iterator = new APCUIterator('/^sidebar:v1:/');
                foreach ($iterator as $key => $unused) {
                    apcu_delete((string)$key);
                }
            }
        } catch (Throwable $e) {
            error_log('[clearSidebarNavigationCaches] APCu clear failed: ' . $e->getMessage());
        }
    }
}
