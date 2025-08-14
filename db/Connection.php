<?php

declare(strict_types=1);

final class Connection
{
    private static ?\PDO $shared = null;

    public static function get(): \PDO
    {
        if (self::$shared instanceof \PDO) {
            return self::$shared;
        }

        $cfg = require __DIR__ . '/config.php';

        try {
            $pdo = self::connect(self::dsnWithDb($cfg), $cfg);
        } catch (\PDOException $e) {
            if (!self::isUnknownDb($cfg['driver'], $e)) {
                throw $e;
            }
            self::createDatabaseIfMissing($cfg);
            $pdo = self::connect(self::dsnWithDb($cfg), $cfg);
        }

        return self::$shared = $pdo;
    }

    private static function connect(string $dsn, array $cfg): \PDO
    {
        return new \PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
    }

    private static function dsnWithDb(array $cfg): string
    {
        if ($cfg['driver'] === 'mysql') {
            return sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['database'],
                $cfg['charset']
            );
        }
        if ($cfg['driver'] === 'pgsql') {
            return sprintf('pgsql:host=%s;port=%d;dbname=%s', $cfg['host'], $cfg['port'], $cfg['database']);
        }
        throw new \InvalidArgumentException('Unsupported driver');
    }

    private static function dsnNoDb(array $cfg): string
    {
        if ($cfg['driver'] === 'mysql') {
            return sprintf('mysql:host=%s;port=%d;charset=%s', $cfg['host'], $cfg['port'], $cfg['charset']);
        }
        if ($cfg['driver'] === 'pgsql') {
            return sprintf('pgsql:host=%s;port=%d', $cfg['host'], $cfg['port']);
        }
        throw new \InvalidArgumentException('Unsupported driver');
    }

    private static function isUnknownDb(string $driver, \PDOException $e): bool
    {
        if ($driver === 'mysql') {
            return (int)$e->getCode() === 1049
                || strpos($e->getMessage(), 'Unknown database') !== false;
        }
        if ($driver === 'pgsql') {
            return strpos($e->getMessage(), 'SQLSTATE[3D000]') !== false
                || strpos($e->getMessage(), 'does not exist') !== false;
        }
        return false;
    }

    private static function sanitizeIdentifier(string $name): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException('Invalid identifier');
        }
        return $name;
    }

    private static function createDatabaseIfMissing(array $cfg): void
    {
        $db  = self::sanitizeIdentifier($cfg['database']);
        $pdo = self::connect(self::dsnNoDb($cfg), $cfg);

        if ($cfg['driver'] === 'mysql') {
            $charset   = $cfg['charset']   ?? 'utf8mb4';
            $collation = $cfg['collation'] ?? 'utf8mb4_unicode_ci';
            $sql = sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s',
                $db,
                $charset,
                $collation
            );
            $pdo->exec($sql);
            return;
        }

        if ($cfg['driver'] === 'pgsql') {
            $sql = sprintf("CREATE DATABASE \"%s\" WITH ENCODING 'UTF8' TEMPLATE template0", $db);
            $pdo->exec($sql);
            return;
        }

        throw new \InvalidArgumentException('Unsupported driver');
    }
}
