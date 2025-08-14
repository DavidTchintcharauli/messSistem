<?php
declare(strict_types=1);

function ensure_users_table(PDO $pdo): void
{
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        return;
    }

    if ($driver === 'pgsql') {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS users (
  id BIGSERIAL PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
)
SQL);
        return;
    }

    throw new InvalidArgumentException('Unsupported driver: ' . $driver);
}

function mysql_drop_table_if_exists(PDO $pdo, string $table): void
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

function mysql_fk_exists(PDO $pdo, string $table, string $constraint): bool
{
    $sql = "
      SELECT 1
        FROM information_schema.REFERENTIAL_CONSTRAINTS
       WHERE CONSTRAINT_SCHEMA = DATABASE()
         AND TABLE_NAME = :t
         AND CONSTRAINT_NAME = :c
      LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':t' => $table, ':c' => $constraint]);
    return (bool)$st->fetchColumn();
}

function ensure_messages_table(PDO $pdo): void
{
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $createCore = <<<SQL
CREATE TABLE IF NOT EXISTS `messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_user_id` BIGINT UNSIGNED NOT NULL,
  `to_user_id`   BIGINT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_conv` (`from_user_id`,`to_user_id`,`id`),
  KEY `ix_conv_rev` (`to_user_id`,`from_user_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

        try {
            $pdo->exec($createCore);
        } catch (PDOException $e) {
            $code = (int)($e->errorInfo[1] ?? 0);
            if ($code === 1932 || strpos($e->getMessage(), "doesn't exist in engine") !== false) {
                mysql_drop_table_if_exists($pdo, 'messages');
                $pdo->exec($createCore);
            } else {
                throw $e;
            }
        }

        if (!mysql_fk_exists($pdo, 'messages', 'fk_msg_from')) {
            $pdo->exec("
                ALTER TABLE `messages`
                ADD CONSTRAINT `fk_msg_from`
                FOREIGN KEY (`from_user_id`) REFERENCES `users`(`id`)
                ON DELETE CASCADE
            ");
        }
        if (!mysql_fk_exists($pdo, 'messages', 'fk_msg_to')) {
            $pdo->exec("
                ALTER TABLE `messages`
                ADD CONSTRAINT `fk_msg_to`
                FOREIGN KEY (`to_user_id`) REFERENCES `users`(`id`)
                ON DELETE CASCADE
            ");
        }
        return;
    }

    if ($driver === 'pgsql') {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS messages (
  id BIGSERIAL PRIMARY KEY,
  from_user_id BIGINT NOT NULL,
  to_user_id   BIGINT NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS ix_conv ON messages (from_user_id, to_user_id, id);
CREATE INDEX IF NOT EXISTS ix_conv_rev ON messages (to_user_id, from_user_id, id);
SQL);

        try {
            $pdo->exec("ALTER TABLE messages
                        ADD CONSTRAINT fk_msg_from
                        FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE");
        } catch (PDOException $e) {
            if (($e->errorInfo[0] ?? '') !== '42710') throw $e;
        }
        try {
            $pdo->exec("ALTER TABLE messages
                        ADD CONSTRAINT fk_msg_to
                        FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE");
        } catch (PDOException $e) {
            if (($e->errorInfo[0] ?? '') !== '42710') throw $e;
        }
        return;
    }

    throw new InvalidArgumentException('Unsupported driver');
}
