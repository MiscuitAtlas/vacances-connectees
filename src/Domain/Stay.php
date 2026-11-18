<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

/**
 * Séjour : une date d'arrivée et une date de départ, le jour du départ n'étant pas compté.
 * Un séjour qui se termine le 10 et un autre qui commence le 10 ne se chevauchent pas.
 */
final class Stay
{
    public const MAX_NIGHTS = 28;
    public const MAX_ADVANCE_DAYS = 365;

    private function __construct(
        public readonly DateTimeImmutable $arrival,
        public readonly DateTimeImmutable $departure,
    ) {
    }

    /**
     * Séjour à réserver : dates valides, dans le futur, de 1 à 28 nuits, au plus un an à l'avance.
     *
     * @throws ValidationException
     */
    public static function forBooking(string $arrival, string $departure, DateTimeImmutable $today): self
    {
        $stay = self::fromStrings($arrival, $departure);
        if ($stay->arrival < $today) {
            throw ValidationException::field('arrivee', 'La date d\'arrivée est déjà passée.');
        }
        if ($stay->arrival > $today->modify('+' . self::MAX_ADVANCE_DAYS . ' days')) {
            throw ValidationException::field('arrivee', 'Les réservations sont ouvertes un an à l\'avance au plus.');
        }
        if ($stay->nights() > self::MAX_NIGHTS) {
            throw ValidationException::field('depart', 'Un séjour dure ' . self::MAX_NIGHTS . ' nuits au plus.');
        }

        return $stay;
    }

    /**
     * @throws ValidationException
     */
    public static function fromStrings(string $arrival, string $departure): self
    {
        $start = self::parse($arrival);
        if ($start === null) {
            throw ValidationException::field('arrivee', 'Indiquez une date d\'arrivée valide.');
        }
        $end = self::parse($departure);
        if ($end === null) {
            throw ValidationException::field('depart', 'Indiquez une date de départ valide.');
        }
        if ($end <= $start) {
            throw ValidationException::field('depart', 'La date de départ doit suivre la date d\'arrivée.');
        }

        return new self($start, $end);
    }

    public function nights(): int
    {
        return (int) $this->arrival->diff($this->departure)->days;
    }

    public function overlaps(self $other): bool
    {
        return $this->arrival < $other->departure && $this->departure > $other->arrival;
    }

    public function arrivalString(): string
    {
        return $this->arrival->format('Y-m-d');
    }

    public function departureString(): string
    {
        return $this->departure->format('Y-m-d');
    }

    /**
     * Date au format AAAA-MM-JJ, refusée si le jour n'existe pas (31 février).
     */
    public static function parse(string $value): ?DateTimeImmutable
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) !== 1 || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date === false ? null : $date;
    }
}
