<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ReviewRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forListing(int $listingId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.note, a.commentaire, a.cree_le, u.nom AS auteur, r.arrivee FROM avis a'
            . ' JOIN utilisateurs u ON u.id = a.auteur_id JOIN reservations r ON r.id = a.reservation_id'
            . ' WHERE a.logement_id = ? ORDER BY a.cree_le DESC, a.id DESC',
        );
        $statement->execute([$listingId]);

        return array_map(static function (array $row): array {
            $row['note'] = (int) $row['note'];

            return $row;
        }, $statement->fetchAll());
    }

    public function create(int $bookingId, int $listingId, int $authorId, int $rating, string $comment, string $now): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO avis (reservation_id, logement_id, auteur_id, note, commentaire, cree_le) VALUES (?, ?, ?, ?, ?, ?)',
        );
        $statement->execute([$bookingId, $listingId, $authorId, $rating, $comment, $now]);
    }
}
