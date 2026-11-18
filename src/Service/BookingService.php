<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Stay;
use App\Domain\ValidationException;
use App\Repository\BookingRepository;
use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * Règles de réservation et d'annulation.
 */
final class BookingService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly BookingRepository $bookings,
    ) {
    }

    /**
     * Réserve un logement si les dates sont libres. Le logement est verrouillé pendant la vérification :
     * deux voyageurs qui réservent les mêmes dates au même instant ne peuvent pas réussir tous les deux.
     *
     * @param array<string, mixed> $listing
     * @throws ValidationException
     */
    public function book(array $listing, int $travellerId, Stay $stay, int $guests, DateTimeImmutable $now): int
    {
        if ($listing['proprietaire_id'] === $travellerId) {
            throw ValidationException::field('arrivee', 'Vous ne pouvez pas réserver votre propre logement.');
        }
        if ($guests < 1 || $guests > $listing['capacite']) {
            throw ValidationException::field('voyageurs', sprintf('Ce logement accueille de 1 à %d voyageurs.', $listing['capacite']));
        }

        $this->pdo->beginTransaction();
        try {
            // Verrou sur la ligne du logement (MySQL) ou sur la base (SQLite) jusqu'à la fin de la transaction.
            $this->pdo->prepare('UPDATE logements SET id = id WHERE id = ?')->execute([$listing['id']]);
            if ($this->bookings->overlaps($listing['id'], $stay->arrivalString(), $stay->departureString())) {
                throw ValidationException::field(
                    'arrivee',
                    'Ces dates ne sont plus disponibles : une autre réservation occupe une partie du séjour.',
                );
            }
            $id = $this->bookings->create(
                $listing['id'],
                $travellerId,
                $stay->arrivalString(),
                $stay->departureString(),
                $guests,
                $stay->nights() * $listing['prix_nuit'],
                $now->format('Y-m-d H:i:s'),
            );
            $this->pdo->commit();

            return $id;
        } catch (Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }

    /**
     * Le voyageur annule un séjour qui n'a pas encore commencé.
     *
     * @param array<string, mixed> $booking
     * @throws ValidationException
     */
    public function cancel(array $booking, DateTimeImmutable $today): void
    {
        if ($booking['statut'] !== BookingRepository::CONFIRMED) {
            throw ValidationException::field('reservation', 'Cette réservation est déjà annulée.');
        }
        if ($booking['arrivee'] <= $today->format('Y-m-d')) {
            throw ValidationException::field('reservation', 'Un séjour commencé ou terminé ne peut plus être annulé.');
        }
        $this->bookings->cancel($booking['id']);
    }

    /**
     * Un avis se laisse une fois par séjour, après le départ, sur une réservation confirmée.
     *
     * @param array<string, mixed> $booking
     */
    public static function canReview(array $booking, DateTimeImmutable $today): bool
    {
        return $booking['statut'] === BookingRepository::CONFIRMED
            && $booking['depart'] <= $today->format('Y-m-d')
            && !$booking['a_un_avis'];
    }
}
