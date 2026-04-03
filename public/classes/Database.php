<?php
// ======================================
// ✅ Kelas Database Tunggal (Singleton)
// - Support config MySQL + Sybase domain-based keys
// - Auto suffix _dsn/_dblib ikut OS
// ======================================
// Guarded include for SSO client:
// - Keep SSO client available for normal web requests (needed for login)
// - Avoid executing the client during CLI/test contexts or when explicitly disabled
// if (PHP_SAPI !== 'cli' && !defined('DISABLE_SSO_SP_CLIENT')) {
//     if (!defined('SSO_SP_CLIENT_INCLUDED')) {
//         define('SSO_SP_CLIENT_INCLUDED', true);
//         include_once __DIR__ . '/../sso_sp_client.php';
//     }
// }
class Database
{
    private static array $instances = [];
    private PDO $connection;

    /**
     * 🚪 Private constructor
     */
    private function __construct(array $config)
    {
        try {
            $options = $config['options'] ?? [];
            $driver = $config['driver'] ?? '';
            $defaults = [
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            if (strtolower((string)$driver) === 'mysql' && defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $defaults[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8mb4';
            }
            // Untuk Sybase via ODBC, elakkan server-side prepares (boleh cetus HY010)
            if (strtolower($driver) === 'odbc' && str_contains(strtolower($config['dsn'] ?? ''), 'sybase')) {
                $defaults[PDO::ATTR_EMULATE_PREPARES] = true;
                $defaults[PDO::ATTR_CURSOR] = PDO::CURSOR_FWDONLY;
            }
            $options = $options + $defaults;

            $this->connection = new PDO(
                $config['dsn'],
                $config['user'] ?? null,
                $config['pass'] ?? null,
                $options
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("❌ Gagal sambungan ke DB: " . $e->getMessage());
        }
    }

    /**
     * 🧠 Dapatkan instance tunggal berdasarkan nama konfigurasi
     *
     * @param string $baseName Contoh:
     *   - 'mysql'
      *   - 'sybase_ehrmdb'  (base; suffix auto ditambah)
      *   - 'sybase_ehrmdb_dsn' / 'sybase_ehrmdb_dblib' (explicit)
     */
    public static function getInstance(string $baseName = 'mysql'): self
    {
        $platform = PHP_OS_FAMILY;
        $isSybase = str_starts_with($baseName, 'sybase_');
        $preferStrictDsn = false;

        // 1) Auto-append suffix untuk Sybase kalau caller bagi base key sahaja
        if ($isSybase && !str_ends_with($baseName, '_dsn') && !str_ends_with($baseName, '_dblib')) {
            // Pilih dblib jika tersedia; jika tidak, fallback ikut OS
            $cfgs = require __DIR__ . '/../configuration/db_config.php';
            $core = $baseName;
            $preferDblib = isset($cfgs[$core . '_dblib']);
            $preferDsn   = isset($cfgs[$core . '_dsn']);
            // On Windows we must use DSN (ODBC) only; avoid dblib attempts entirely
            if ($platform === 'Windows' && $preferDsn) {
                $baseName .= '_dsn';
                $preferStrictDsn = true; // disable cross-driver fallback later
            } elseif ($preferDblib) {
                $baseName .= '_dblib';
            } elseif ($preferDsn) {
                $baseName .= '_dsn';
            } else {
                $suffix = ($platform === 'Windows') ? '_dsn' : '_dblib';
                $baseName .= $suffix;
            }
        }

        // 2) Cache instance mengikut $baseName (yang mungkin sudah ditambah suffix)
        if (!isset(self::$instances[$baseName])) {
            $configs = require __DIR__ . '/../configuration/db_config.php';

            if (!isset($configs[$baseName])) {
                // Bantu dev: beritahu nama yang ada kalau miss
                $hint = implode(', ', array_keys($configs));
                throw new Exception("⚠️ Konfigurasi '$baseName' tidak ditemui dalam db_config.php. Pilihan tersedia: {$hint}");
            }

            // Try primary config first; if it fails and this is a Sybase entry,
            // attempt to fall back between '_dsn' and '_dblib' variants to
            // improve resilience across environments (Windows vs Linux).
            try {
                self::$instances[$baseName] = new self($configs[$baseName]);
            } catch (Exception $e) {
                if ($isSybase && !$preferStrictDsn) {
                    $alt = null;
                    if (str_ends_with($baseName, '_dsn')) {
                        $alt = substr($baseName, 0, -4) . '_dblib';
                    } elseif (str_ends_with($baseName, '_dblib')) {
                        $alt = substr($baseName, 0, -6) . '_dsn';
                    }

                    if ($alt && isset($configs[$alt])) {
                        try {
                            error_log("[Database] Primary sybase config '{$baseName}' failed, trying fallback '{$alt}': " . $e->getMessage());
                            self::$instances[$alt] = new self($configs[$alt]);
                            // Return fallback instance so callers get a working PDO
                            return self::$instances[$alt];
                        } catch (Exception $_e) {
                            throw new Exception("⚠️ Gagal sambungan ke DB (dicuba {$baseName} dan {$alt}): " . $e->getMessage() . ' | ' . $_e->getMessage());
                        }
                    }
                }

                // Jika bukan sybase atau tiada fallback, re-throw original exception
                throw $e;
            }
        }

        return self::$instances[$baseName];
    }

    /**
     * 🔌 Dapatkan sambungan PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    // ——————————————————————————————
    // 🔧 Convenience helpers (optional)
    // ——————————————————————————————

    /**
     * 🎯 Terus dapatkan PDO MySQL
     */
    public static function pdoMysql(): PDO
    {
        return self::getInstance('mysql')->getConnection();
    }

    /**
     * 🎯 PDO Sybase domain staff (environment-aware helper)
     */
    public static function pdoSybaseStaff(?string $environment = null): PDO
    {
        if (!function_exists('getSybaseStaffPDO')) {
            $helperPath = __DIR__ . '/../includes/functions-db.php';
            if (is_file($helperPath)) {
                require_once $helperPath;
            }
        }
        if (!function_exists('getSybaseStaffPDO')) {
            throw new Exception("⚠️ Helper getSybaseStaffPDO() belum tersedia.");
        }
        return getSybaseStaffPDO($environment);
    }

    /**
     * 🎯 PDO Sybase domain student (environment-aware helper)
     */
    public static function pdoSybaseStudent(?string $environment = null): ?PDO
    {
        if (!function_exists('getSybaseStudentPDO')) {
            $helperPath = __DIR__ . '/../includes/functions-db.php';
            if (is_file($helperPath)) {
                require_once $helperPath;
            }
        }
        if (!function_exists('getSybaseStudentPDO')) {
            throw new Exception("⚠️ Helper getSybaseStudentPDO() belum tersedia.");
        }
        return getSybaseStudentPDO($environment);
    }

    /**
     * 🔁 Clear a cached instance so callers can force a reconnect.
     *
     * @param string $baseName The base config name (same rules as getInstance)
     */
    public static function clearInstance(string $baseName = 'mysql'): void
    {
        $platform = PHP_OS_FAMILY;
        if (str_starts_with($baseName, 'sybase_') && !str_ends_with($baseName, '_dsn') && !str_ends_with($baseName, '_dblib')) {
            $suffix = ($platform === 'Windows') ? '_dsn' : '_dblib';
            $baseName .= $suffix;
        }

        if (isset(self::$instances[$baseName])) {
            // close PDO connection reference to allow GC
            try {
                self::$instances[$baseName]->connection = null; // release PDO
            } catch (Throwable $e) {
                // ignore — defensive
            }
            unset(self::$instances[$baseName]);
        }
    }

    /**
     * 🔁 Clear all cached instances (useful for full reconnect)
     */
    public static function clearAllInstances(): void
    {
        foreach (array_keys(self::$instances) as $k) {
            try {
                self::$instances[$k]->connection = null;
            } catch (Throwable $e) {
                // ignore
            }
            unset(self::$instances[$k]);
        }
    }
}
