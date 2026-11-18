<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, nom, email, mot_de_passe FROM utilisateurs WHERE id = ?');
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, nom, email, mot_de_passe FROM utilisateurs WHERE email = ?');
        $statement->execute([self::normalizeEmail($email)]);

        return $statement->fetch() ?: null;
    }

    public function emailTaken(string $email, ?int $exceptId = null): bool
    {
        $statement = $this->pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id <> ?');
        $statement->execute([self::normalizeEmail($email), $exceptId ?? 0]);

        return $statement->fetchColumn() !== false;
    }

    public function create(string $name, string $email, string $passwordHash, string $now): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO utilisateurs (nom, email, mot_de_passe, cree_le) VALUES (?, ?, ?, ?)',
        );
        $statement->execute([$name, self::normalizeEmail($email), $passwordHash, $now]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateProfile(int $id, string $name, string $email): void
    {
        $statement = $this->pdo->prepare('UPDATE utilisateurs SET nom = ?, email = ? WHERE id = ?');
        $statement->execute([$name, self::normalizeEmail($email), $id]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $statement = $this->pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
        $statement->execute([$passwordHash, $id]);
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
