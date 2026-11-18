<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use DateTimeImmutable;
use DateTimeZone;
use IntlDateFormatter;
use NumberFormatter;
use RuntimeException;

/**
 * Prévient le propriétaire par email quand un voyageur réserve ou annule un séjour dans son logement.
 */
final class HostNotifier
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Mailer $mailer,
        private readonly string $siteUrl,
    ) {
    }

    /**
     * @param array<string, mixed> $booking Réservation telle que BookingRepository::find la renvoie.
     */
    public function booked(array $booking): void
    {
        $this->notify($booking, 'Nouvelle réservation', sprintf(
            "%s a réservé « %s » du %s au %s, pour %s. Montant du séjour : %s.",
            $this->travellerName($booking),
            $booking['titre'],
            self::date((string) $booking['arrivee']),
            self::date((string) $booking['depart']),
            $booking['voyageurs'] > 1 ? $booking['voyageurs'] . ' voyageurs' : '1 voyageur',
            self::money((int) $booking['prix_total']),
        ));
    }

    /**
     * @param array<string, mixed> $booking
     */
    public function cancelled(array $booking): void
    {
        $this->notify($booking, 'Réservation annulée', sprintf(
            "%s a annulé son séjour dans « %s » du %s au %s. Ces dates sont de nouveau libres.",
            $this->travellerName($booking),
            $booking['titre'],
            self::date((string) $booking['arrivee']),
            self::date((string) $booking['depart']),
        ));
    }

    /**
     * @param array<string, mixed> $booking
     */
    private function notify(array $booking, string $subject, string $message): void
    {
        $owner = $this->users->find((int) $booking['proprietaire_id']);
        if ($owner === null) {
            return;
        }

        try {
            $this->mailer->send(
                (string) $owner['email'],
                $subject . ' : ' . $booking['titre'],
                "Bonjour {$owner['nom']},\n\n{$message}\n\n"
                . "Détail de la réservation : {$this->siteUrl}/reservations/{$booking['id']}\n",
            );
        } catch (RuntimeException $error) {
            // La réservation est enregistrée : un email qui ne part pas ne doit pas l'annuler.
            error_log('Email au propriétaire non envoyé : ' . $error->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $booking
     */
    private function travellerName(array $booking): string
    {
        return (string) ($this->users->find((int) $booking['voyageur_id'])['nom'] ?? 'Un voyageur');
    }

    private static function date(string $value): string
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Europe/Paris', null, 'EEEE d MMMM y');

        return (string) $formatter->format(new DateTimeImmutable($value, new DateTimeZone('Europe/Paris')));
    }

    private static function money(int $euros): string
    {
        $formatter = new NumberFormatter('fr_FR', NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);

        return (string) $formatter->formatCurrency($euros, 'EUR');
    }
}
