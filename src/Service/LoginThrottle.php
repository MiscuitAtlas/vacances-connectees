<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use PDO;

/**
 * Limite les essais de mots de passe : après plusieurs échecs sur un même email,
 * la connexion à ce compte est suspendue quelques minutes.
 */
final class LoginThrottle
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly int $maxFailures,
        private readonly int $lockMinutes,
    ) {
    }

    public function isLocked(string $email, DateTimeImmutable $now): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM echecs_connexion WHERE empreinte_email = ? AND cree_le > ?');
        $statement->execute([self::key($email), $this->windowStart($now)]);

        return (int) $statement->fetchColumn() >= $this->maxFailures;
    }

    public function recordFailure(string $email, DateTimeImmutable $now): void
    {
        $this->pdo->prepare('INSERT INTO echecs_connexion (empreinte_email, cree_le) VALUES (?, ?)')
            ->execute([self::key($email), $now->format('Y-m-d H:i:s')]);
        // Les échecs anciens ne servent plus.
        $this->pdo->prepare('DELETE FROM echecs_connexion WHERE cree_le <= ?')->execute([$this->windowStart($now)]);
    }

    public function clear(string $email): void
    {
        $this->pdo->prepare('DELETE FROM echecs_connexion WHERE empreinte_email = ?')->execute([self::key($email)]);
    }

    public function lockMinutes(): int
    {
        return $this->lockMinutes;
    }

    /**
     * L'adresse saisie n'est pas conservée en clair : seule son empreinte sert de clé.
     */
    private static function key(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }

    private function windowStart(DateTimeImmutable $now): string
    {
        return $now->modify('-' . $this->lockMinutes . ' minutes')->format('Y-m-d H:i:s');
    }
}
