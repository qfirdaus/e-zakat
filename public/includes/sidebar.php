<?php
/**
 * Sidebar Navigation Component
 * 
 * Displays user profile, modules, and menu items based on user access.
 * Uses SidebarController for business logic and implements security validations.
 * 
 * @package e-prestasi
 * @author UPNM, Seksyen Aplikasi Digital, BTMK
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../controllers/SidebarController.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/SystemConfigConstants.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../setting/function.php'; // untuk base_path(), base_url()
require_once __DIR__ . '/../setting/helper/config_helper.php';
require_once __DIR__ . '/../setting/helper/access_helper.php';
require_once __DIR__ . '/../includes/functions-db.php';

/**
 * Validate icon class against whitelist
 * 
 * @param string $icon Icon class name
 * @return string Validated icon class (default: 'ri-folder-fill')
 */
function validate_sidebar_icon(string $icon): string {
    $icon = trim($icon);
    if ($icon !== '' && preg_match('/^ri-[a-z0-9-]+$/', $icon) === 1) {
        return $icon;
    }
    $allowed = SystemConfigConstants::ALLOWED_SIDEBAR_ICONS;
    return in_array($icon, $allowed, true) ? $icon : 'ri-folder-fill';
}

/**
 * Sanitize menu path to prevent path traversal
 * 
 * @param string $path Menu path from database
 * @return string|null Sanitized path or null if invalid
 */
function sanitize_menu_path(string $path): ?string {
    // Remove any path traversal attempts
    if (str_contains($path, '..') || str_contains($path, '//')) {
        return null;
    }
    
    // Remove leading/trailing slashes and whitespace
    $path = trim($path);
    $path = ltrim($path, '/');
    
    // Only allow alphanumeric, dash, underscore, dot, and forward slash
    if (!preg_match('/^[a-zA-Z0-9_\-.\/]+$/', $path)) {
        return null;
    }
    
    // Limit path length
    if (strlen($path) > 255) {
        return null;
    }
    
    return $path;
}

/**
 * Detect if a menu item is active based on current page path
 * 
 * @param string $currentPath Current page relative path (e.g., 'pages/dashboard.php')
 * @param string $menuPath Menu path from database
 * @return bool True if menu is active, false otherwise
 */
function normalize_sidebar_current_path(string $currentPath): string {
    $currentPath = trim(str_replace('\\', '/', $currentPath));
    if ($currentPath === '') {
        return '';
    }

    $currentPath = ltrim($currentPath, '/');
    $publicPos = stripos($currentPath, 'public/');
    if ($publicPos !== false) {
        $currentPath = substr($currentPath, $publicPos + 7);
    }

    return strtolower($currentPath);
}

function sidebar_path_match_variants(string $path, bool $defaultToPages = false): array {
    $normalized = normalize_sidebar_current_path($path);
    if ($normalized === '') {
        return [];
    }

    $variants = [$normalized];
    if ($defaultToPages && !preg_match('#^(pages|ajax|actions)/#', $normalized)) {
        $variants[] = 'pages/' . ltrim($normalized, '/');
    }

    $expanded = [];
    foreach ($variants as $variant) {
        $variant = rtrim($variant, '/');
        if ($variant === '') {
            continue;
        }

        $expanded[] = $variant;

        if (str_ends_with($variant, '/index.php')) {
            $expanded[] = substr($variant, 0, -10);
        } elseif (!str_ends_with($variant, '.php')) {
            $expanded[] = $variant . '/index.php';
        }
    }

    return array_values(array_unique(array_filter($expanded, static fn($item) => $item !== '')));
}

function is_menu_active(string $currentPath, string $menuPath): bool {
    $sanitizedPath = sanitize_menu_path($menuPath);
    if (!$sanitizedPath) {
        return false;
    }

    $currentCandidates = sidebar_path_match_variants($currentPath, false);
    $menuCandidates = sidebar_path_match_variants($sanitizedPath, true);

    foreach ($currentCandidates as $candidate) {
        if (in_array($candidate, $menuCandidates, true)) {
            return true;
        }
    }
    
    return false;
}

function sidebar_link_active_class(string $currentPath, string $menuPath): string {
    return is_menu_active($currentPath, $menuPath) ? ' active' : '';
}

function sanitize_sidebar_user_image(?string $path): string {
    $default = 'assets/images/small/small-5.jpg';
    $path = trim((string)$path);

    if ($path === '') {
        return $default;
    }

    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');
    $path = preg_replace('#^public/#i', '', $path);

    if (!preg_match('#^assets/images/small/[A-Za-z0-9._-]+\.(jpg|jpeg|png|webp|gif)$#i', $path)) {
        return $default;
    }

    $fullPath = realpath(__DIR__ . '/../' . $path);
    $baseDir = realpath(__DIR__ . '/../assets/images/small');

    if (!$fullPath || !$baseDir || strncmp($fullPath, $baseDir, strlen($baseDir)) !== 0 || !is_file($fullPath)) {
        return $default;
    }

    return str_replace('\\', '/', $path);
}

/**
 * Check if profile data is empty or invalid
 * 
 * @param array $profile Profile data array
 * @return bool True if profile is empty/invalid, false otherwise
 */
function is_profile_empty(array $profile): bool {
    return empty($profile) || 
           (empty($profile['f_loginID']) && empty($profile['f_stafID']) && empty($profile['f_nopekerja']) && empty($profile['f_nama']));
}

// Initialize controller and load sidebar data
$currentFile = isset($currentFile) && is_string($currentFile) && $currentFile !== ''
    ? $currentFile
    : (function_exists('prestasi_current_page_relative_path')
        ? prestasi_current_page_relative_path()
        : ($_SERVER['PHP_SELF'] ?? ''));
$sidebarController = new SidebarController();
$sidebarController->loadSidebarData($currentFile);

// Get data from controller
$profile = $sidebarController->getProfile();
$senaraiModul = $sidebarController->getSenaraiModul();
$modulMenus = $sidebarController->getModulMenus();
$modulAktifID = $sidebarController->getModulAktifID();
$lang = $sidebarController->getLang();

// Extract profile data with fallbacks
$isProfileEmpty = is_profile_empty($profile);
$namaPendek = 'Pengguna';
$avatarUrl = base_url('assets/images/no-image.jpg');
$perananLabel = 'Pengguna';
$profileMessage = null;

// Active role (session-based)
$activeGroupId = (int)($_SESSION['group_active_id'] ?? 0);

if (!$isProfileEmpty) {
    // Extract nickname or first name
    $namaPendek = $profile['f_nickname'] ?? '';
    if (empty($namaPendek) && !empty($profile['f_nama'])) {
        $namaPendek = explode(' ', $profile['f_nama'])[0];
    }
    if (empty($namaPendek)) {
        $namaPendek = 'Pengguna';
    }
    
    $avatarUrl = $profile['avatar_url'] ?? $profile['avatar'] ?? base_url('assets/images/no-image.jpg');
    
    $perananLabel = $profile['f_groupName'] ?? 'Pengguna';
    if ($activeGroupId > 0) {
        try {
            $stmtAct = Database::getInstance()->getConnection()->prepare("SELECT f_groupName FROM tbl_m_group WHERE f_groupID = :gid LIMIT 1");
            $stmtAct->execute([':gid' => $activeGroupId]);
            $rowAct = $stmtAct->fetch(PDO::FETCH_ASSOC);
            if (!empty($rowAct['f_groupName'])) {
                $perananLabel = (string)$rowAct['f_groupName'];
            }
        } catch (Throwable $e) {
            // keep default label
        }
    }
} else {
    // Profile is empty - set fallback message
    $profileMessage = __('sidebar_profile_empty') ?: 'Profil tidak ditemui';
}

// Get theme settings
$sidebarColor = $_SESSION['theme.menu']
    ?? $_SESSION['theme.sidebar']
    ?? SystemConfigConstants::DEFAULT_THEME_SIDEBAR;

// Get notification count (optional - hide badge if null)
$notificationCount = $sidebarController->getNotificationCount();
$defaultHome = app_config('site.default_home', 'pages/dashboard.php');
$sidebarLogo = app_config('branding.sidebar_logo', 'assets/images/new-logo.png');
$sidebarUserImage = sanitize_sidebar_user_image(app_config('branding.sidebar_user_image', 'assets/images/small/small-5.jpg'));
$pdo = Database::getInstance()->getConnection();
$isSidebarSuperAdmin = function_exists('is_user_super_admin') ? is_user_super_admin($profile, $pdo) : false;
$manualMenuUrl = null;
if ($activeGroupId > 0) {
    try {
        $manualStmt = $pdo->prepare(
            "SELECT f_file_path FROM tbl_m_usermanual WHERE f_groupID = :gid LIMIT 1"
        );
        $manualStmt->execute([':gid' => $activeGroupId]);
        $manualRow = $manualStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $manualPath = trim((string)($manualRow['f_file_path'] ?? ''));
        $manualFullPath = $manualPath !== '' ? realpath(__DIR__ . '/../' . ltrim($manualPath, '/\\')) : false;
        $manualBaseDir = realpath(__DIR__ . '/../uploads/manuals');
        if ($manualPath !== '' && $manualFullPath && $manualBaseDir && strncmp($manualFullPath, $manualBaseDir, strlen($manualBaseDir)) === 0 && is_file($manualFullPath)) {
            $manualMenuUrl = base_url('ajax/manual-view.php?group_id=' . $activeGroupId);
        }
    } catch (Throwable $e) {
        $manualMenuUrl = null;
    }
}
?>

<!-- ========== Left Sidebar Start ========== -->
<div class="leftside-menu" id="leftside-menu" data-menu-color="<?= $sidebarColor ?>" data-sidebar-loaded="true">
<style>
/* Sidebar Loading State */
#leftside-menu[data-sidebar-loaded="false"] .sidebar-loading-overlay {
    display: flex;
}
#leftside-menu[data-sidebar-loaded="true"] .sidebar-loading-overlay {
    display: none;
}
.sidebar-loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(2px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10;
    flex-direction: column;
    gap: 12px;
}
html[data-bs-theme="dark"] .sidebar-loading-overlay {
    background: rgba(0, 0, 0, 0.75);
}
.sidebar-loading-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(0, 0, 0, 0.1);
    border-top-color: #0d6efd;
    border-radius: 50%;
    animation: sidebar-spin 0.8s linear infinite;
}
html[data-bs-theme="dark"] .sidebar-loading-spinner {
    border-color: rgba(255, 255, 255, 0.1);
    border-top-color: #0d6efd;
}
.sidebar-loading-text {
    font-size: 0.875rem;
    color: #6c757d;
    opacity: 0.8;
}
html[data-bs-theme="dark"] .sidebar-loading-text {
    color: #adb5bd;
}
@keyframes sidebar-spin {
    to { transform: rotate(360deg); }
}
/* Sidebar Chart Icon (Dashboard) */
.side-nav .sidebar-chart-icon {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: calc(var(--ct-menu-item-font-size) * 1.1);
    color: var(--ct-menu-item-color);
    opacity: 0.6;
    transition: opacity 0.2s ease;
}
.side-nav .sidebar-chart-icon:hover {
    opacity: 1;
}
.side-nav .sidebar-manual-icon {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: calc(var(--ct-menu-item-font-size) * 1.1);
    color: var(--ct-menu-item-color);
    opacity: 0.6;
    transition: opacity 0.2s ease;
}
.side-nav .sidebar-manual-icon:hover {
    opacity: 1;
}
/* Sidebar Logout Icon */
.side-nav .sidebar-logout-icon {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: calc(var(--ct-menu-item-font-size) * 1.1);
    color: var(--bs-danger);
    opacity: 0.7;
    transition: opacity 0.2s ease;
}
.side-nav .sidebar-logout-icon:hover {
    opacity: 1;
}
.side-nav .side-nav-primary-link {
    position: relative;
    display: flex;
    align-items: center;
    width: calc(100% - 12px);
    margin: 0 6px;
    padding: var(--ct-menu-item-padding-y) calc(var(--ct-menu-item-padding-x) * 3) var(--ct-menu-item-padding-y) var(--ct-menu-item-padding-x);
    border-radius: 10px;
    transition: background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}
.side-nav .side-nav-primary-link > i:first-child {
    flex: 0 0 auto;
    opacity: 0.9;
    transition: color 0.18s ease, opacity 0.18s ease, transform 0.18s ease;
}
.side-nav .side-nav-primary-link > span {
    flex: 1 1 auto;
    padding-right: calc(var(--ct-menu-item-padding-x) * 0.6);
}
.side-nav .side-nav-toggle-btn {
    position: relative;
    display: flex;
    align-items: center;
    width: calc(100% - 12px);
    margin: 0 6px;
    padding: var(--ct-menu-item-padding-y) calc(var(--ct-menu-item-padding-x) * 3) var(--ct-menu-item-padding-y) var(--ct-menu-item-padding-x);
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: var(--ct-menu-item-color);
    text-align: left;
    box-shadow: none;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    transition: background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}
.side-nav .side-nav-toggle-btn > i:first-child {
    flex: 0 0 auto;
    opacity: 0.9;
    transition: color 0.18s ease, opacity 0.18s ease, transform 0.18s ease;
}
.side-nav .side-nav-toggle-btn > span {
    flex: 1 1 auto;
    padding-right: calc(var(--ct-menu-item-padding-x) * 0.6);
}
.side-nav .sidebar-parent-arrow {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    text-align: center;
    font-size: calc(var(--ct-menu-item-font-size) * 1.1);
    color: inherit;
    opacity: 0.72;
    transition: transform 0.15s ease, opacity 0.15s ease;
}
.side-nav .side-nav-toggle-btn:hover .sidebar-parent-arrow,
.side-nav .side-nav-toggle-btn:focus-visible .sidebar-parent-arrow {
    opacity: 1;
}
.side-nav .side-nav-item > .side-nav-toggle-btn[aria-expanded="true"] > .sidebar-parent-arrow {
    transform: translateY(-50%) rotate(90deg);
    opacity: 1;
}
.side-nav .side-nav-toggle-btn:hover,
.side-nav .side-nav-toggle-btn:focus-visible {
    color: var(--ct-menu-item-hover-color);
    background-color: rgba(255, 255, 255, 0.055);
    transform: translateX(1px);
}
.side-nav .side-nav-toggle-btn:focus {
    outline: none;
    box-shadow: none;
}
.side-nav .side-nav-item > .side-nav-toggle-btn[aria-expanded="true"] {
    color: var(--ct-menu-item-active-color);
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.075), rgba(255, 255, 255, 0.025));
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.03);
}
.side-nav .side-nav-item.menuitem-active > .side-nav-toggle-btn {
    color: var(--ct-menu-item-active-color);
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.07), rgba(255, 255, 255, 0.018));
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.028);
    font-weight: 400;
}
.side-nav .side-nav-item > .side-nav-primary-link.active {
    color: var(--ct-menu-item-active-color);
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.07), rgba(255, 255, 255, 0.018));
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.028);
    font-weight: 400;
}
.side-nav .side-nav-item.menuitem-active > .side-nav-primary-link {
    color: var(--ct-menu-item-active-color);
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.07), rgba(255, 255, 255, 0.018));
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.028);
    font-weight: 400;
}
.side-nav .side-nav-primary-link:hover,
.side-nav .side-nav-primary-link:focus-visible {
    color: var(--ct-menu-item-hover-color);
    background-color: rgba(255, 255, 255, 0.055);
    transform: translateX(1px);
}
.side-nav .side-nav-primary-link:hover > i:first-child,
.side-nav .side-nav-primary-link:focus-visible > i:first-child,
.side-nav .side-nav-toggle-btn:hover > i:first-child,
.side-nav .side-nav-toggle-btn:focus-visible > i:first-child,
.side-nav .side-nav-item.menuitem-active > .side-nav-primary-link > i:first-child,
.side-nav .side-nav-item.menuitem-active > .side-nav-toggle-btn > i:first-child {
    opacity: 1;
    transform: translateX(1px);
}
.side-nav .side-nav-item.menuitem-active > .side-nav-toggle-btn .sidebar-parent-arrow {
    opacity: 1;
}
.side-nav .side-nav-second-level {
    position: relative;
    padding-left: calc(var(--ct-menu-item-icon-width) - 12px);
    margin-top: 0.28rem;
    padding-top: 0.18rem;
    padding-bottom: 0.22rem;
}
.side-nav .side-nav-second-level::before {
    content: "";
    position: absolute;
    left: 0.55rem;
    top: 0.15rem;
    bottom: 0.25rem;
    width: 1px;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.08));
    opacity: 0.55;
}
.side-nav .side-nav-second-level li > a {
    display: flex;
    align-items: center;
    margin: 0.08rem 0.55rem 0.08rem 0.2rem;
    min-height: 36px;
    padding-top: 0.34rem;
    padding-bottom: 0.34rem;
    padding-left: calc(var(--ct-menu-item-padding-x) * 0.9);
    line-height: 1;
    border-radius: 8px;
    transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
}
.side-nav .side-nav-second-level li > a:hover {
    color: var(--ct-menu-item-hover-color);
    background-color: rgba(255, 255, 255, 0.045);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.02);
    transform: translateX(2px);
}
.side-nav .side-nav-second-level li.menuitem-active > a,
.side-nav .side-nav-second-level li > a.active {
    color: var(--ct-menu-item-active-color);
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.09), rgba(255, 255, 255, 0.028));
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.03);
    padding-left: calc(var(--ct-menu-item-padding-x) * 1.45);
    font-weight: 400;
}
.side-nav .side-nav-second-level li.menuitem-active > a::before,
.side-nav .side-nav-second-level li > a.active::before {
    left: 0.55rem;
    top: 0.5rem;
    bottom: 0.5rem;
}
</style>
<div class="sidebar-loading-overlay">
    <div class="sidebar-loading-spinner"></div>
    <div class="sidebar-loading-text"><?= __('sidebar_loading') ?: 'Memuatkan...' ?></div>
</div>
<script>
// Hide sidebar loading overlay once sidebar is loaded
(function() {
    const sidebar = document.getElementById('leftside-menu');
    if (sidebar && sidebar.getAttribute('data-sidebar-loaded') === 'true') {
        // Sidebar already loaded, hide overlay immediately
        const overlay = sidebar.querySelector('.sidebar-loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
})();
</script>

    <!-- ✅ Logo Sidebar -->
    <a href="<?= base_path($defaultHome) ?>" class="logo logo-dark">
        <span class="logo-lg"><img src="<?= base_url($sidebarLogo) ?>" alt="logo" style="width: 200px; height: auto;"></span>
        <span class="logo-sm"><img src="<?= base_url($sidebarLogo) ?>" alt="small logo" style="height: 30px; width: auto;"></span>
    </a>

    <a href="<?= base_path($defaultHome) ?>" class="logo logo-light">
        <span class="logo-lg"><img src="<?= base_url($sidebarLogo) ?>" alt="logo" style="width: 200px; height: auto;"></span>
        <span class="logo-sm"><img src="<?= base_url($sidebarLogo) ?>" alt="small logo" style="height: 30px; width: auto;"></span>
    </a>

    <div class="h-100" id="leftside-menu-container" data-simplebar>

        <!-- ✅ Paparan Pengguna -->
        <div class="leftbar-user p-3 text-white" style="background-image:url('<?= htmlspecialchars(base_url($sidebarUserImage), ENT_QUOTES, 'UTF-8') ?>');">
            <?php if ($isProfileEmpty): ?>
                <!-- Fallback: Profile tidak ditemui -->
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle shadow d-flex align-items-center justify-content-center bg-white bg-opacity-10" style="width: 42px; height: 42px;">
                            <i class="ri-user-line fs-18"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <span class="fw-semibold fs-15 d-block"><?= htmlspecialchars($namaPendek) ?></span>
                        <span class="fs-12 text-white-50"><?= htmlspecialchars($profileMessage) ?></span>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= base_path('pages/profile.php') ?>" class="d-flex align-items-center text-reset">
                    <div class="flex-shrink-0">
                        <img src="<?= $avatarUrl ?>" onerror="this.onerror=null;this.src='<?= base_url('assets/images/no-image.jpg') ?>';" alt="user-image" height="42" class="rounded-circle shadow">
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <span class="fw-semibold fs-15 d-block"><?= htmlspecialchars($namaPendek) ?></span>
                        <span class="fs-13"><?= htmlspecialchars($perananLabel) ?></span>
                    </div>
                    <div class="ms-auto">
                        <i class="ri-arrow-right-s-fill fs-20"></i>
                    </div>
                </a>
            <?php endif; ?>
        </div>

        <!-- ✅ Menu Sidebar -->
        <ul class="side-nav" style="padding-bottom: 70px;">

            <!-- Dashboard -->
            <li class="side-nav-title mt-1"><?= __('sidebar_main') ?></li>
            <?php $dashboardActive = is_menu_active($currentFile, $defaultHome); ?>
            <li class="side-nav-item<?= $dashboardActive ? ' menuitem-active' : '' ?>">
                <a href="<?= base_path($defaultHome) ?>" class="side-nav-link side-nav-primary-link<?= $dashboardActive ? ' active' : '' ?>">
                    <i class="ri-dashboard-fill"></i>
                    <span><?= __('sidebar_dashboard') ?></span>
                    <i class="ri-bar-chart-line sidebar-chart-icon" title="<?= __('sidebar_dashboard_stats') ?: 'Statistik' ?>"></i>
                </a>
            </li>
            <?php if (!empty($manualMenuUrl)): ?>
            <li class="side-nav-item">
                <a href="<?= htmlspecialchars($manualMenuUrl, ENT_QUOTES, 'UTF-8') ?>" class="side-nav-link side-nav-primary-link" target="_blank" rel="noopener">
                    <i class="ri-book-open-line"></i>
                    <span><?= __('sidebar_user_manual') ?: 'Manual Pengguna' ?></span>
                    <i class="ri-external-link-line sidebar-manual-icon" title="<?= __('sidebar_user_manual') ?: 'Manual Pengguna' ?>"></i>
                </a>
            </li>
            <?php endif; ?>
            <!-- Modul Sistem -->
            <li class="side-nav-title mt-2"><?= __('sidebar_modul') ?></li>
            <?php foreach ($senaraiModul as $modul): 
                $modulID = (int)$modul['f_modulID'];
                $modulId = 'sidebarModul' . $modulID;
                
                // ✅ VALIDATE ICON CLASS
                $icon = validate_sidebar_icon($modul['f_icon'] ?? 'ri-folder-fill');
                
                $nama = htmlspecialchars($modul['modulName'] ?? '', ENT_QUOTES, 'UTF-8');
                
                // ✅ USE BATCH LOADED MENUS (no N+1 query)
                $childs = $modulMenus[$modulID] ?? [];
                if (empty($childs)) continue;

                $isActive     = ($modulID === $modulAktifID);
                $collapseCls  = $isActive ? 'collapse show' : 'collapse';
                $linkCls      = 'side-nav-link' . ($isActive ? '' : ' collapsed');
                $ariaExpanded = $isActive ? 'true' : 'false';
            ?>
                <li class="side-nav-item<?= $isActive ? ' menuitem-active' : '' ?>">
                    <button type="button"
                       data-sidebar-toggle="true"
                       data-sidebar-target="#<?= $modulId ?>"
                       class="<?= $linkCls ?> side-nav-toggle-btn<?= $isActive ? ' active' : '' ?>"
                       aria-expanded="<?= $ariaExpanded ?>"
                       aria-controls="<?= $modulId ?>">
                        <i class="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i>
                        <span><?= $nama ?></span>
                        <i class="ri-arrow-right-s-line sidebar-parent-arrow" aria-hidden="true"></i>
                    </button>
                    <div class="<?= $collapseCls ?>" id="<?= $modulId ?>">
                        <ul class="side-nav-second-level">
                            <?php foreach ($childs as $menu): 
                                // ✅ SANITIZE MENU PATH
                                $menuPath = sanitize_menu_path($menu['f_path'] ?? '');
                                if (!$menuPath) continue; // Skip invalid paths
                                
                                // ✅ USE HELPER FUNCTION FOR ACTIVE DETECTION
                                $menuActive = is_menu_active($currentFile, $menuPath);
                                $menuHref = base_path('pages/' . $menuPath);
                                $menuName = htmlspecialchars($menu['menuName'] ?? '-', ENT_QUOTES, 'UTF-8');
                            ?>
                                <li class="<?= $menuActive ? 'menuitem-active' : '' ?>">
                                    <a class="<?= $menuActive ? 'active' : '' ?>"
                                       href="<?= htmlspecialchars($menuHref, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= $menuName ?>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </li>
            <?php endforeach ?>

            <!-- Kawalan Sistem -->
            <li class="side-nav-title mt-2"><?= __('sidebar_kawalan') ?></li>
            <li class="side-nav-item">
                <a href="javascript:void(0);" onclick="return confirmLogout(event);" data-no-loader class="side-nav-link side-nav-primary-link text-danger">
                    <i class="ri-logout-box-r-fill"></i>
                    <span><?= __('sidebar_keluar') ?></span>
                    <i class="ri-logout-box-r-line sidebar-logout-icon" title="<?= __('sidebar_keluar') ?>"></i>
                </a>
            </li>

        </ul>
        <div class="clearfix"></div>
    </div>
</div>
<!-- ========== Left Sidebar End ========== -->
<script>
(function () {
    if (window.__SidebarCollapseFixInitialized) {
        return;
    }
    window.__SidebarCollapseFixInitialized = true;

    function syncSidebarToggleState(toggleEl, panelEl, isExpanded) {
        if (!toggleEl || !panelEl) {
            return;
        }

        toggleEl.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        toggleEl.classList.toggle('collapsed', !isExpanded);
        panelEl.classList.toggle('show', !!isExpanded);
    }

    function bindSidebarCollapseFix() {
        var sidebar = document.getElementById('leftside-menu');
        if (!sidebar) {
            return;
        }

        sidebar.addEventListener('click', function (event) {
            var toggleEl = event.target.closest(".side-nav li [data-sidebar-toggle='true']");
            if (!toggleEl || !sidebar.contains(toggleEl)) {
                return;
            }

            var targetSelector = toggleEl.getAttribute('data-sidebar-target') || '';
            if (!targetSelector || targetSelector.charAt(0) !== '#') {
                return;
            }

            var panelEl = document.querySelector(targetSelector);
            if (!panelEl) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            var isExpanded = panelEl.classList.contains('show');

            if (isExpanded) {
                syncSidebarToggleState(toggleEl, panelEl, false);
                return;
            }

            var openPanels = sidebar.querySelectorAll('.side-nav .collapse.show');
            openPanels.forEach(function (openPanelEl) {
                if (openPanelEl === panelEl) {
                    return;
                }

                var openToggleEl = sidebar.querySelector(".side-nav li [data-sidebar-toggle='true'][data-sidebar-target='#" + openPanelEl.id + "']");
                syncSidebarToggleState(openToggleEl, openPanelEl, false);
            });

            syncSidebarToggleState(toggleEl, panelEl, true);
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindSidebarCollapseFix, { once: true });
    } else {
        bindSidebarCollapseFix();
    }
})();
</script>
