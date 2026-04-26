<?php
declare(strict_types=1);

final class DatabaseConnectionFactory
{
    public function make(array $config): PDO
    {
        $options = $config['options'] ?? [];
        $driver = strtolower((string)($config['driver'] ?? ''));
        $defaults = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($driver === 'mysql' && defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $defaults[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8mb4';
        }

        if ($driver === 'odbc' && str_contains(strtolower((string)($config['dsn'] ?? '')), 'sybase')) {
            $defaults[PDO::ATTR_EMULATE_PREPARES] = true;
            $defaults[PDO::ATTR_CURSOR] = PDO::CURSOR_FWDONLY;
        }

        return new PDO(
            (string)$config['dsn'],
            $config['user'] ?? null,
            $config['pass'] ?? null,
            $options + $defaults
        );
    }
}
