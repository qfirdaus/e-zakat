<?php
declare(strict_types=1);

require_once __DIR__ . '/../constants/prestasi_constants.php';

if (!function_exists('prestasi_normalize_group_code')) {
    function prestasi_normalize_group_code(?string $groupKod): string {
        $groupKod = strtoupper(trim((string)$groupKod));
        if ($groupKod === '') return '';
        return preg_replace('/[^A-Z0-9]+/', '', $groupKod) ?? '';
    }
}

if (!function_exists('prestasi_group_code_equals')) {
    function prestasi_group_code_equals(?string $left, ?string $right): bool {
        $a = prestasi_normalize_group_code($left);
        $b = prestasi_normalize_group_code($right);
        return ($a !== '' && $a === $b);
    }
}

if (!function_exists('prestasi_super_admin_code')) {
    function prestasi_super_admin_code(): string {
        if (defined('PRESTASI_ROLE_KOD_ADM_SA')) return (string)PRESTASI_ROLE_KOD_ADM_SA;
        if (defined('PRESTASI_ROLE_ADM_SA')) return (string)PRESTASI_ROLE_ADM_SA;
        return 'ADM-SA';
    }
}

if (!function_exists('prestasi_resolve_active_group')) {
    function prestasi_resolve_active_group(array $profile = [], ?PDO $pdo = null): array {
        $defaultGroupId = (int)($profile['f_groupID'] ?? 0);
        $defaultGroupKod = (string)($profile['f_groupKod'] ?? '');
        $activeGroupId = (int)($_SESSION['group_active_id'] ?? 0);
        if ($activeGroupId <= 0) $activeGroupId = $defaultGroupId;

        $activeGroupKod = '';
        if ($activeGroupId > 0 && $activeGroupId === $defaultGroupId && $defaultGroupKod !== '') {
            $activeGroupKod = $defaultGroupKod;
        } elseif ($activeGroupId > 0 && $pdo instanceof PDO) {
            try {
                static $cacheById = [];
                if (array_key_exists($activeGroupId, $cacheById)) {
                    $activeGroupKod = (string)$cacheById[$activeGroupId];
                } else {
                    $stmt = $pdo->prepare("SELECT f_groupKod FROM tbl_m_group WHERE f_groupID = :gid LIMIT 1");
                    $stmt->execute([':gid' => $activeGroupId]);
                    $activeGroupKod = (string)($stmt->fetchColumn() ?: '');
                    $cacheById[$activeGroupId] = $activeGroupKod;
                }
            } catch (Throwable $e) {
                $activeGroupKod = '';
            }
        }

        if ($activeGroupKod === '') $activeGroupKod = $defaultGroupKod;
        return ['id' => $activeGroupId, 'kod' => $activeGroupKod];
    }
}

if (!function_exists('is_user_super_admin')) {
    function is_user_super_admin(array $profile = [], ?PDO $pdo = null): bool {
        $legacyRoleId = defined('PRESTASI_ROLE_ID_ADM_SA') ? (int)PRESTASI_ROLE_ID_ADM_SA : 0;
        $activeRoleId = (int)($_SESSION['group_active_id'] ?? 0);
        if ($legacyRoleId > 0 && $activeRoleId > 0 && $activeRoleId === $legacyRoleId) return true;

        $resolved = prestasi_resolve_active_group($profile, $pdo);
        if ($legacyRoleId > 0 && (int)$resolved['id'] === $legacyRoleId) return true;

        $superAdminKod = prestasi_super_admin_code();
        if (prestasi_group_code_equals((string)$resolved['kod'], $superAdminKod)) return true;
        return prestasi_group_code_equals((string)($profile['f_groupKod'] ?? ''), $superAdminKod);
    }
}
