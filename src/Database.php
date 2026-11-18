<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    /**
     * Connexion PDO : exceptions en cas d'erreur, vraies requêtes préparées côté serveur.
     *
     * @param array{dsn: string, user?: string|null, password?: string|null} $config
     */
    public static function connect(array $config): PDO
    {
        $pdo = new PDO($config['dsn'], $config['user'] ?? null, $config['password'] ?? null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        if (self::isSqlite($pdo)) {
            // SQLite n'applique les clés étrangères que si on le demande.
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        return $pdo;
    }

    public static function isSqlite(PDO $pdo): bool
    {
        return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

    /**
     * Crée les tables à partir du schéma correspondant au moteur (MySQL ou SQLite).
     */
    public static function createSchema(PDO $pdo): void
    {
        $file = dirname(__DIR__) . '/database/' . (self::isSqlite($pdo) ? 'schema.sqlite.sql' : 'schema.mysql.sql');
        $sql = (string) file_get_contents($file);
        foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
            $statement = trim((string) preg_replace('/^--.*$/m', '', $statement));
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }
}
