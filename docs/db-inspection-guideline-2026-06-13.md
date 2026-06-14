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
