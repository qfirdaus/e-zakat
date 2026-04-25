# Notification Module Blueprint

Date: 2026-04-25

Scope:
- Final implementation blueprint for topbar notification module
- Workspace only: `D:\WWW\iqs-framework`
- Identity standard locked to `tbl_m_user.f_loginID`
- Target users include `STAF`, `PELAJAR`, and `UMUM`

Identity baseline:
- Primary recipient identity: `tbl_m_user.f_loginID`
- Recipient category: `tbl_m_user.f_categoryUser`
- Group targeting context: active session group from `$_SESSION['group_active_id']`, fallback `tbl_m_user.f_groupID`
- Optional metadata only: `f_userID`, `f_stafID`, `f_nopekerja`

Repo alignment references:
- Dummy topbar notification dropdown: [topbar.php](D:/WWW/iqs-framework/public/includes/topbar.php:265)
- AJAX JSON + CSRF pattern: [role-switch.php](D:/WWW/iqs-framework/public/ajax/role-switch.php:1)
- Central audit writer: [AuditLogger.php](D:/WWW/iqs-framework/public/classes/AuditLogger.php:5)
- Dashboard announcement payload exists but is not a notification source of truth: [dashboard.php](D:/WWW/iqs-framework/public/pages/dashboard.php:97)

## Locked Decisions

1. Notification recipient identity is `f_loginID`, not `f_userID`.
2. Notification audience targeting supports only these values in phase 1:
   - `ALL`
   - `LOGIN_ID`
   - `GROUP_ID`
   - `CATEGORY_USER`
3. Read state is stored per `f_notificationID + f_loginID`.
4. Topbar dropdown is a live inbox, not static content.
5. `Clear All` in current dummy UI is replaced by `Mark All Read`.
6. Audit log and notification are separate domains. Audit can feed notification later, but notification is not a direct projection of audit rows.

## Implementation Goals

Phase 1 goals:
- show unread badge in topbar bell
- fetch latest topbar notifications from DB
- mark a notification as read
- mark all visible notifications as read
- provide `View All` page
- support `ALL`, `LOGIN_ID`, `GROUP_ID`, and `CATEGORY_USER`
- support multilingual title/body fallback
- support active windows using start and expiry timestamps

Phase 1 non-goals:
- websocket or push notification
- email digest
- converting all audit events into notifications
- advanced admin composer UI
- snooze, pin, archive, or rule engine

## File List

New files to add:
- [NotificationService.php](D:/WWW/iqs-framework/public/classes/NotificationService.php)
- [notification-list.php](D:/WWW/iqs-framework/public/ajax/notification-list.php)
- [notification-read.php](D:/WWW/iqs-framework/public/ajax/notification-read.php)
- [notification-read-all.php](D:/WWW/iqs-framework/public/ajax/notification-read-all.php)
- [notifications.php](D:/WWW/iqs-framework/public/pages/notifications.php)
- [topbar-notifications.js](D:/WWW/iqs-framework/public/assets/js/topbar-notifications.js)

Existing files to update:
- [topbar.php](D:/WWW/iqs-framework/public/includes/topbar.php)
- `public/lang/ms.php`
- `public/lang/en.php`
- `CHANGELOG.md`

Optional future files, not required for phase 1:
- `public/pages/notification-center.php`
- `public/ajax/notification-save.php`
- `public/ajax/notification-dismiss.php`

## SQL Migration Final

Recommended migration file:
- `docs/notification-module-migration-2026-04-25.sql`

Final SQL:

```sql
CREATE TABLE IF NOT EXISTS `tbl_notification` (
  `f_notificationID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `f_code` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_type` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'announcement',
  `f_category` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `f_severity` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `f_title_ms` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `f_title_en` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_body_ms` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `f_body_en` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `f_actionURL` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_actionLabel_ms` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_actionLabel_en` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_icon` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_sourceType` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_sourceID` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_isBroadcast` TINYINT(1) NOT NULL DEFAULT '0',
  `f_status` TINYINT(1) NOT NULL DEFAULT '1',
  `f_startsAt` DATETIME DEFAULT NULL,
  `f_expiresAt` DATETIME DEFAULT NULL,
  `f_insertBy` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_insertdt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `f_updatedt` DATETIME DEFAULT NULL,
  PRIMARY KEY (`f_notificationID`),
  KEY `idx_notification_status_window` (`f_status`, `f_startsAt`, `f_expiresAt`),
  KEY `idx_notification_type` (`f_type`),
  KEY `idx_notification_category` (`f_category`),
  KEY `idx_notification_source` (`f_sourceType`, `f_sourceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_notification_audience` (
  `f_audienceID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `f_notificationID` BIGINT UNSIGNED NOT NULL,
  `f_targetType` ENUM('ALL','LOGIN_ID','GROUP_ID','CATEGORY_USER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `f_targetValue` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_insertdt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`f_audienceID`),
  KEY `idx_notification_audience_lookup` (`f_targetType`, `f_targetValue`),
  KEY `idx_notification_audience_notification` (`f_notificationID`),
  CONSTRAINT `fk_notification_audience_notification`
    FOREIGN KEY (`f_notificationID`) REFERENCES `tbl_notification` (`f_notificationID`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_notification_user_state` (
  `f_stateID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `f_notificationID` BIGINT UNSIGNED NOT NULL,
  `f_loginID` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `f_categoryUser` ENUM('STAF','PELAJAR','UMUM') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_isRead` TINYINT(1) NOT NULL DEFAULT '0',
  `f_readAt` DATETIME DEFAULT NULL,
  `f_isDismissed` TINYINT(1) NOT NULL DEFAULT '0',
  `f_dismissedAt` DATETIME DEFAULT NULL,
  `f_clickedAt` DATETIME DEFAULT NULL,
  `f_insertdt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `f_updatedt` DATETIME DEFAULT NULL,
  PRIMARY KEY (`f_stateID`),
  UNIQUE KEY `uq_notification_login` (`f_notificationID`, `f_loginID`),
  KEY `idx_notification_login_unread` (`f_loginID`, `f_isRead`, `f_isDismissed`),
  CONSTRAINT `fk_notification_user_state_notification`
    FOREIGN KEY (`f_notificationID`) REFERENCES `tbl_notification` (`f_notificationID`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Seed examples for validation:

```sql
INSERT INTO `tbl_notification`
(`f_type`, `f_category`, `f_severity`, `f_title_ms`, `f_title_en`, `f_body_ms`, `f_body_en`, `f_actionURL`, `f_icon`, `f_isBroadcast`, `f_status`, `f_insertBy`)
VALUES
('announcement', 'system', 'info', 'Pengumuman Sistem', 'System Announcement', 'Selamat datang ke modul notifikasi.', 'Welcome to the notification module.', 'pages/notifications.php', 'ri-notification-3-line', 1, 1, 'system');

INSERT INTO `tbl_notification_audience`
(`f_notificationID`, `f_targetType`, `f_targetValue`)
VALUES
(1, 'ALL', NULL);
```

## Data Rules

`tbl_notification`:
- stores canonical notification content
- `f_isBroadcast = 1` is metadata only and does not replace audience rows
- visibility still depends on `tbl_notification_audience`

`tbl_notification_audience`:
- `ALL`: `f_targetValue = NULL`
- `LOGIN_ID`: exact value of `tbl_m_user.f_loginID`
- `GROUP_ID`: string value of active group ID
- `CATEGORY_USER`: one of `STAF`, `PELAJAR`, `UMUM`

`tbl_notification_user_state`:
- one row per `f_notificationID + f_loginID`
- no row means notification is still unread and not dismissed
- `f_categoryUser` is snapshot/debug context, not identity key

## Visibility Resolution Rules

A notification is visible only if all of these are true:
- `n.f_status = 1`
- `n.f_startsAt IS NULL OR n.f_startsAt <= NOW()`
- `n.f_expiresAt IS NULL OR n.f_expiresAt >= NOW()`
- user state is not dismissed
- at least one audience row matches current actor

Audience match is true when one of these matches:
- `ALL`
- `LOGIN_ID = current f_loginID`
- `GROUP_ID = current active group ID`
- `CATEGORY_USER = current f_categoryUser`

Identity resolution order:
1. read `$_SESSION['f_loginID']`
2. load `tbl_m_user` by `f_loginID`
3. derive:
   - `f_loginID`
   - `f_categoryUser`
   - `$_SESSION['group_active_id']` if present
   - fallback `tbl_m_user.f_groupID`
   - optional `f_userID`, `f_stafID`, `f_nopekerja`

## NotificationService Class Skeleton

File:
- [NotificationService.php](D:/WWW/iqs-framework/public/classes/NotificationService.php)

Recommended skeleton:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class NotificationService
{
    public function __construct(private PDO $pdo) {}

    public function resolveCurrentActor(): array
    {
        $loginId = trim((string)($_SESSION['f_loginID'] ?? ''));
        if ($loginId === '') {
            throw new RuntimeException('Missing session login ID.');
        }

        $stmt = $this->pdo->prepare("
            SELECT f_userID, f_loginID, f_stafID, f_nopekerja, f_categoryUser, f_groupID
            FROM tbl_m_user
            WHERE f_loginID = :loginID
            LIMIT 1
        ");
        $stmt->execute([':loginID' => $loginId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row) {
            throw new RuntimeException('Authenticated user record not found.');
        }

        $activeGroupId = (int)($_SESSION['group_active_id'] ?? 0);
        if ($activeGroupId <= 0) {
            $activeGroupId = (int)($row['f_groupID'] ?? 0);
        }

        return [
            'user_id' => (int)($row['f_userID'] ?? 0),
            'login_id' => (string)($row['f_loginID'] ?? ''),
            'staf_id' => (string)($row['f_stafID'] ?? ''),
            'nopekerja' => (string)($row['f_nopekerja'] ?? ''),
            'category_user' => (string)($row['f_categoryUser'] ?? ''),
            'group_id' => $activeGroupId,
        ];
    }

    public function getTopbarPayload(string $lang = 'ms', int $limit = 10): array
    {
        $actor = $this->resolveCurrentActor();

        return [
            'unread' => $this->countUnread($actor, $lang),
            'items' => $this->listNotifications($actor, [
                'lang' => $lang,
                'limit' => $limit,
                'mode' => 'topbar',
            ]),
        ];
    }

    public function countUnread(array $actor, string $lang = 'ms'): int
    {
        // Count visible notifications with no read row or unread state.
        return 0;
    }

    public function listNotifications(array $actor, array $options = []): array
    {
        // Resolve visible notifications for topbar or page list mode.
        return [];
    }

    public function markAsRead(int $notificationId, array $actor): bool
    {
        // Upsert tbl_notification_user_state by f_notificationID + f_loginID.
        return true;
    }

    public function markAllAsRead(array $actor): int
    {
        // Bulk mark all visible notifications as read for current f_loginID.
        return 0;
    }

    public function touchClicked(int $notificationId, array $actor): bool
    {
        // Optional future usage when item click tracking is required.
        return true;
    }

    public function createNotification(array $payload): int
    {
        // Future admin/broadcast use.
        return 0;
    }
}
```

## Core Query Contract

Recommended visibility query shape:

```sql
SELECT
  n.f_notificationID,
  n.f_type,
  n.f_category,
  n.f_severity,
  CASE
    WHEN :lang = 'en' AND COALESCE(NULLIF(n.f_title_en, ''), '') <> '' THEN n.f_title_en
    ELSE n.f_title_ms
  END AS title,
  CASE
    WHEN :lang = 'en' AND COALESCE(NULLIF(n.f_body_en, ''), '') <> '' THEN n.f_body_en
    ELSE n.f_body_ms
  END AS body,
  n.f_actionURL,
  CASE
    WHEN :lang = 'en' AND COALESCE(NULLIF(n.f_actionLabel_en, ''), '') <> '' THEN n.f_actionLabel_en
    ELSE n.f_actionLabel_ms
  END AS action_label,
  n.f_icon,
  n.f_insertdt,
  COALESCE(s.f_isRead, 0) AS is_read
FROM tbl_notification n
JOIN tbl_notification_audience a
  ON a.f_notificationID = n.f_notificationID
LEFT JOIN tbl_notification_user_state s
  ON s.f_notificationID = n.f_notificationID
 AND s.f_loginID = :login_id
WHERE n.f_status = 1
  AND (n.f_startsAt IS NULL OR n.f_startsAt <= NOW())
  AND (n.f_expiresAt IS NULL OR n.f_expiresAt >= NOW())
  AND COALESCE(s.f_isDismissed, 0) = 0
  AND (
    a.f_targetType = 'ALL'
    OR (a.f_targetType = 'LOGIN_ID' AND a.f_targetValue = :login_id)
    OR (a.f_targetType = 'GROUP_ID' AND a.f_targetValue = :group_id)
    OR (a.f_targetType = 'CATEGORY_USER' AND a.f_targetValue = :category_user)
  )
GROUP BY n.f_notificationID
ORDER BY COALESCE(s.f_isRead, 0) ASC, n.f_insertdt DESC
LIMIT :limit;
```

## AJAX Endpoint Request and Response

All endpoints must follow these repo conventions:
- `require_once __DIR__ . '/../includes/init.php';`
- `require_login();`
- `header('Content-Type: application/json; charset=utf-8');`
- verify CSRF via `isValidCsrfToken()`
- use `POST`
- read JSON body where appropriate

### 1. `notification-list.php`

Purpose:
- fetch topbar notification payload
- fetch page list payload later with the same service

Request:

```json
{
  "mode": "topbar",
  "limit": 10
}
```

Success response:

```json
{
  "error": false,
  "unread": 3,
  "items": [
    {
      "id": 17,
      "type": "announcement",
      "category": "system",
      "severity": "info",
      "title": "Pengumuman Sistem",
      "body": "Selamat datang ke modul notifikasi.",
      "action_url": "pages/notifications.php",
      "action_label": "Buka",
      "icon": "ri-notification-3-line",
      "is_read": false,
      "created_at": "2026-04-25 15:30:00",
      "time_ago": "1 min ago"
    }
  ]
}
```

Error response:

```json
{
  "error": true,
  "message": "Gagal memuatkan notifikasi."
}
```

### 2. `notification-read.php`

Purpose:
- mark one notification as read for current `f_loginID`

Request:

```json
{
  "notification_id": 17
}
```

Success response:

```json
{
  "error": false,
  "message": "Notification marked as read.",
  "notification_id": 17
}
```

Error response:

```json
{
  "error": true,
  "message": "Notification tidak sah."
}
```

### 3. `notification-read-all.php`

Purpose:
- mark all visible notifications as read for current `f_loginID`

Request:

```json
{
  "scope": "topbar"
}
```

Success response:

```json
{
  "error": false,
  "updated": 8,
  "message": "All notifications marked as read."
}
```

### 4. `notifications.php`

Purpose:
- full-page list view
- initial phase can render server shell and fetch rows via `notification-list.php` using mode `page`

Suggested future request for page mode:

```json
{
  "mode": "page",
  "limit": 25,
  "page": 1,
  "filter": "all"
}
```

## AJAX Skeleton

Recommended endpoint skeleton for `notification-list.php`:

```php
<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../includes/init.php';
    require_login();
    require_once __DIR__ . '/../classes/Database.php';
    require_once __DIR__ . '/../classes/NotificationService.php';

    if (!isValidCsrfToken()) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => (string)__('userGroup_csrf_invalid')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = [];
    }

    $limit = max(1, min(25, (int)($data['limit'] ?? 10)));
    $lang = (string)($_SESSION['lang'] ?? 'ms');

    $service = new NotificationService(Database::getInstance('mysql')->getConnection());
    $payload = $service->getTopbarPayload($lang, $limit);

    echo json_encode([
        'error' => false,
        'unread' => (int)($payload['unread'] ?? 0),
        'items' => $payload['items'] ?? [],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Gagal memuatkan notifikasi.'
    ], JSON_UNESCAPED_UNICODE);
}
```

## Topbar UI Structure

Existing dummy block in [topbar.php](D:/WWW/iqs-framework/public/includes/topbar.php:265) should be replaced with a render shell:

```html
<li class="dropdown notification-list" id="topbarNotificationRoot">
  <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false" id="topbarNotificationToggle">
    <i class="ri-notification-3-fill fs-22"></i>
    <span class="noti-icon-badge d-none" id="topbarNotificationBadge"></span>
  </a>
  <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated dropdown-lg py-0" data-bs-auto-close="outside">
    <div class="p-2 border-dashed border-top-0 d-flex justify-content-between align-items-center">
      <h6 class="fs-16 fw-medium m-0"><?= h(__('topbar_notification_title') ?: 'Notification') ?></h6>
      <a href="#" class="text-dark text-decoration-underline d-none" id="topbarNotificationReadAll">
        <small><?= h(__('topbar_notification_mark_all_read') ?: 'Mark All Read') ?></small>
      </a>
    </div>
    <div style="max-height: 300px;" data-simplebar id="topbarNotificationList">
      <div class="p-3 text-center text-muted small"><?= h(__('topbar_notification_loading') ?: 'Loading...') ?></div>
    </div>
    <a href="<?= h(base_url('pages/notifications.php')) ?>" class="dropdown-item text-center text-primary fw-bold border-top py-2">
      <?= h(__('topbar_notification_view_all') ?: 'View All') ?>
    </a>
  </div>
</li>
```

## Pseudo-UI Topbar Render Flow

Topbar notification lifecycle:

1. Page load
- read `csrfToken` from existing topbar/init context
- initialize `window.TopbarNotifications`
- bind dropdown open event
- do one silent initial fetch to populate badge

2. Initial silent fetch
- call `POST ajax/notification-list.php`
- payload: `{ "mode": "topbar", "limit": 10 }`
- if success:
  - set unread badge
  - render latest items
  - show `Mark All Read` only when unread > 0
- if fail:
  - keep bell visible
  - render soft empty/error state without blocking other topbar actions

3. Dropdown open
- refresh list if cache older than 20-30 seconds
- rerender items

4. Click single notification item
- call `POST ajax/notification-read.php`
- optimistically mark item read in UI
- update unread badge
- if item has `action_url`, redirect there

5. Click `Mark All Read`
- call `POST ajax/notification-read-all.php`
- rerender all visible items as read
- clear unread badge

6. Click `View All`
- open `pages/notifications.php`

Recommended JS structure:

```js
window.TopbarNotifications = {
  state: {
    loadedAt: 0,
    unread: 0,
    items: [],
    loading: false
  },

  init() {},
  bindEvents() {},
  load(options = {}) {},
  render(payload) {},
  renderEmpty(message) {},
  renderItems(items) {},
  updateBadge(unread) {},
  markRead(id) {},
  markAllRead() {}
};
```

## Pseudo-Render Rules

Unread badge:
- hidden when unread = 0
- visible when unread > 0
- for counts > 99, show `99+`

Topbar item render:
- icon on left
- title on first line
- short body on second line
- relative time on right or below
- unread items use stronger emphasis
- read items use muted text

Empty state:
- text only
- no fake sample cards
- suggested string: `Tiada notifikasi untuk masa ini.`

Error state:
- text only
- suggested string: `Tidak dapat memuatkan notifikasi.`

## Translation Keys

Add these keys to `ms.php` and `en.php`:

- `topbar_notification_title`
- `topbar_notification_loading`
- `topbar_notification_empty`
- `topbar_notification_load_failed`
- `topbar_notification_mark_all_read`
- `topbar_notification_view_all`
- `topbar_notification_read_success`
- `topbar_notification_read_failed`
- `topbar_notification_read_all_success`
- `topbar_notification_read_all_failed`
- `notification_page_title`
- `notification_filter_all`
- `notification_filter_unread`
- `notification_filter_read`

## Audit Requirements

These user actions should be auditable:
- notification item marked as read
- all visible notifications marked as read
- future admin-created notification published

Recommended audit payload shape:

```php
audit_event([
    'event_type' => 'UPDATE',
    'severity' => 'INFO',
    'outcome' => 'SUCCESS',
    'target_type' => 'notification',
    'target_id' => (string)$notificationId,
    'target_label' => 'Notification read state',
    'message' => 'Notification marked as read',
    'request_id' => $GLOBALS['__AUDIT_REQUEST_ID'] ?? null,
    'session_id' => session_id() ?: null,
    'meta' => [
        'login_id' => $actor['login_id'] ?? null,
        'category_user' => $actor['category_user'] ?? null,
        'group_id' => $actor['group_id'] ?? null,
    ],
]);
```

## Build Order

Implementation order locked for delivery:

1. add SQL migration
2. add `NotificationService.php`
3. add `notification-list.php`
4. replace dummy topbar shell in `topbar.php`
5. add `topbar-notifications.js`
6. add `notification-read.php`
7. add `notification-read-all.php`
8. add `notifications.php`
9. add language keys
10. add audit hooks
11. update `CHANGELOG.md`

## Acceptance Checklist

The feature is considered ready for phase 1 when:
- topbar no longer renders dummy sample notification
- unread badge matches DB state for current `f_loginID`
- current actor only sees notifications matching `ALL`, `LOGIN_ID`, `GROUP_ID`, or `CATEGORY_USER`
- `STAF`, `PELAJAR`, and `UMUM` identities all resolve correctly via `tbl_m_user.f_loginID`
- clicking an unread notification marks it read
- `Mark All Read` updates all visible notifications for current `f_loginID`
- `View All` opens a real page
- expired notifications do not display
- all notification AJAX endpoints enforce login and CSRF

## Final Note

This blueprint is locked to the current identity model in `tbl_m_user`:
- `f_loginID` is the canonical notification recipient key
- `f_categoryUser` is the category targeting key
- `group_active_id` is the runtime group targeting key

That keeps the module aligned with the way authentication and multi-category user access already work in this repository.
