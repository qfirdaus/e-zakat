# Database Inspection Guideline

Date: 2026-06-13

Purpose: standardize how Codex should inspect the project database for this Docker-based IQS Framework workspace.

## Scope

The application PHP runtime runs inside Docker, not on the Windows host.

Use the Docker container PHP runtime for database checks:

```bash
docker exec -i iqs-framework-web php
```

The project path inside the container is:

```text
/var/www/html
```

The application database connection should be loaded through:

```php
require '/var/www/html/classes/Database.php';
$pdo = Database::getInstance('mysql')->getConnection();
```

## Read-Only Rule

Codex is allowed to inspect the database only.

Allowed examples:

```sql
SELECT ...
SHOW COLUMNS ...
DESCRIBE ...
EXPLAIN SELECT ...
```

For table existence checks, prefer `INFORMATION_SCHEMA` instead of `SHOW TABLES LIKE ?`:

```sql
SELECT COUNT(*)
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = ?
```

Do not run database-changing statements without explicit user approval.

Prohibited without approval:

```sql
ALTER ...
CREATE ...
DROP ...
TRUNCATE ...
INSERT ...
UPDATE ...
DELETE ...
REPLACE ...
RENAME ...
GRANT ...
REVOKE ...
```

## Standard Table Check Command

Use this pattern from the host/WSL terminal when checking table availability:

```bash
docker exec -i iqs-framework-web php <<'PHP'
<?php
require '/var/www/html/classes/Database.php';
$pdo = Database::getInstance('mysql')->getConnection();

$tables = [
    'tbl_ai_chat_knowledge',
    'tbl_ai_chat_knowledge_source',
    'tbl_ai_chat_knowledge_chunk',
];

foreach ($tables as $table) {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    echo $table . ': ' . ((int)$stmt->fetchColumn() > 0 ? 'EXISTS' : 'MISSING') . PHP_EOL;
}
PHP
```

## Standard Column Check Command

Use this pattern to inspect columns without changing schema:

```bash
docker exec -i iqs-framework-web php <<'PHP'
<?php
require '/var/www/html/classes/Database.php';
$pdo = Database::getInstance('mysql')->getConnection();

$table = 'tbl_ai_chat_knowledge_source';
$stmt = $pdo->prepare(
    'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
     FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = ?
     ORDER BY ORDINAL_POSITION'
);
$stmt->execute([$table]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
    echo $column['COLUMN_NAME'] . ' | ' . $column['COLUMN_TYPE'] . ' | nullable=' . $column['IS_NULLABLE'] . PHP_EOL;
}
PHP
```

## Operational Notes

- The Windows host `php` command may not have the same extensions as the Docker PHP runtime.
- Use Docker PHP for application-accurate database checks.
- If a schema change is needed, stop and ask the user for explicit approval before running any migration or DDL.

## AI Chatbot Project Provider Checks

AI Chatbot project-aware providers must be checked using the same read-only rule.

For the core `iqs-framework` project, Codex may inspect only the current IQS Framework database unless the user explicitly grants a project-specific exception.

For downstream project databases such as e-PMS, access must be approved separately each time unless the user gives a new explicit read-only scope. The previous e-PMS schema inspection was limited to schema metadata and must not be treated as ongoing permission.

Allowed project provider inspection examples:

```sql
SELECT COUNT(*)
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (...)
```

```sql
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = ?
ORDER BY ORDINAL_POSITION
```

Avoid reading live business rows unless the user explicitly approves that specific read-only data check.

Project-aware chatbot code may use provider-owned read-only prepared queries at application runtime, but Codex shell inspection remains limited by the approval scope above.

Do not run AI Chatbot provider verification against a downstream project database without confirming:

- project path or container name,
- database target,
- read-only scope,
- whether row-level checks are permitted or schema-only checks are permitted.

## Phase 12/13 — e-PMS Verification Notes (2026-06-14)

- Approved test runtime: use the `upnm30` project/runtime when verifying e-PMS provider queries. Do not query other downstream project databases unless explicitly authorized.

- CLI PHP extension note: the Windows host `php` binary may lack `pdo_mysql`. For quick one-off CLI tests you can enable it with:

```powershell
php -d extension=php_pdo_mysql.dll your_test_script.php
```

Use the Docker PHP runtime where possible to avoid extension mismatches.

- Example e-PMS table presence and row counts observed during verification (informational snapshot):

  - `tbl_monitoring_project`: 61
  - `tbl_monitoring_teras`: 6
  - `tbl_monitoring_project_owner`: 88
  - `tbl_monitoring_project_pic`: 93
  - `tbl_monitoring_aktiviti`: 363
  - `tbl_monitoring_laporan`: 2563
  - `tbl_announcements`: 4
  - `tbl_monitoring_project_feedback`: 0

  These numbers are an operational snapshot from `upnm30` and should not be treated as authoritative or permanently stored in docs.

- Safety and runtime behavior changes implemented in Phase 13:

  - The question classifier may mark prompts as sensitive with `blocked_detail` (examples: requests for SQL/table structure, requests for other users' private records, raw feedback comments).
  - Runtime fix: when `blocked_detail` is true, the chatbot runtime now skips building or attaching project-aware context — this prevents exposing provider rows for sensitive queries.
  - The runtime exposes audit-only metadata via `ai_chatbot_project_context_meta()` which contains safe fields only (no raw rows): `status`, `provider`, `provider_label`, `match_score`, `intent`, `scope`, `provider_context_status`, `row_count`, `has_records`, and `denied_reason`.

Follow the Read-Only Rule and the Safety constraints above when performing further verification or troubleshooting.
