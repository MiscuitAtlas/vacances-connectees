<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class BookingRepository
{
    public const CONFIRMED = 'confirmee';
    public const CANCELLED = 'annulee';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Une réservation confirmée occupe-t-elle déjà une partie de la période ?
     */
    public function overlaps(int $listingId, string $arrival, string $departure): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM reservations WHERE logement_id = ? AND statut = ? AND arrivee < ? AND depart > ? LIMIT 1',
        );
        $statement->execute([$listingId, self::CONFIRMED, $departure, $arrival]);

        return $statement->fetchColumn() !== false;
    }

    public function create(
        int $listingId,
        int $travellerId,
        string $arrival,
        string $departure,
        int $guests,
        int $total,
        string $now,
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO reservations (logement_id, voyageur_id, arrivee, depart, voyageurs, prix_total, statut, cree_le)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        );
        $statement->execute([$listingId, $travellerId, $arrival, $departure, $guests, $total, self::CONFIRMED, $now]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.*, l.titre, l.ville, l.proprietaire_id, (SELECT COUNT(*) FROM avis a WHERE a.reservation_id = r.id) AS a_un_avis'
            . ' FROM reservations r JOIN logements l ON l.id = r.logement_id WHERE r.id = ?',
        );
        $statement->execute([$id]);
        $booking = $statement->fetch();

        return $booking ? self::hydrate($booking) : null;
    }

    /**
     * Séjours du voyageur, du plus proche au plus lointain.
     *
     * @return list<array<string, mixed>>
     */
    public function forTraveller(int $travellerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.*, l.titre, l.ville, l.type, l.cadre, (SELECT COUNT(*) FROM avis a WHERE a.reservation_id = r.id) AS a_un_avis'
            . ' FROM reservations r JOIN logements l ON l.id = r.logement_id WHERE r.voyageur_id = ? ORDER BY r.arrivee DESC, r.id DESC',
        );
        $statement->execute([$travellerId]);

        return array_map(self::hydrate(...), $statement->fetchAll());
    }

    /**
     * Réservations confirmées à venir sur les logements d'un propriétaire.
     *
     * @return list<array<string, mixed>>
     */
    public function upcomingForOwner(int $ownerId, string $today): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.*, l.titre, l.ville, u.nom AS voyageur_nom FROM reservations r'
            . ' JOIN logements l ON l.id = r.logement_id JOIN utilisateurs u ON u.id = r.voyageur_id'
            . ' WHERE l.proprietaire_id = ? AND r.statut = ? AND r.depart > ? ORDER BY r.arrivee ASC, r.id ASC',
        );
        $statement->execute([$ownerId, self::CONFIRMED, $today]);

        return array_map(self::hydrate(...), $statement->fetchAll());
    }

    /**
     * Périodes réservées qui touchent l'intervalle [début, fin[ (pour le calendrier).
     *
     * @return list<array{arrivee: string, depart: string}>
     */
    public function bookedPeriods(int $listingId, string $from, string $to): array
    {
        $statement = $this->pdo->prepare(
            'SELECT arrivee, depart FROM reservations WHERE logement_id = ? AND statut = ? AND arrivee < ? AND depart > ?'
            . ' ORDER BY arrivee',
        );
        $statement->execute([$listingId, self::CONFIRMED, $to, $from]);

        return $statement->fetchAll();
    }

    public function hasUpcoming(int $listingId, string $today): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM reservations WHERE logement_id = ? AND statut = ? AND depart > ? LIMIT 1',
        );
        $statement->execute([$listingId, self::CONFIRMED, $today]);

        return $statement->fetchColumn() !== false;
    }

    public function cancel(int $id): void
    {
        $this->pdo->prepare('UPDATE reservations SET statut = ? WHERE id = ?')->execute([self::CANCELLED, $id]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrate(array $row): array
    {
        foreach (['id', 'logement_id', 'voyageur_id', 'voyageurs', 'prix_total', 'proprietaire_id'] as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = (int) $row[$key];
            }
        }
        if (array_key_exists('a_un_avis', $row)) {
            $row['a_un_avis'] = (int) $row['a_un_avis'] > 0;
        }

        return $row;
    }
}
