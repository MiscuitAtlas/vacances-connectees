<?php

declare(strict_types=1);

namespace App\View;

use DateTimeImmutable;

/**
 * Calendrier des disponibilités : un tableau par mois, chaque jour libre, réservé ou passé.
 */
final class Calendar
{
    /**
     * @param list<array{arrivee: string, depart: string}> $periods Séjours confirmés.
     * @return list<array{month: string, weeks: list<list<array{day: int, date: string, state: string}|null>>}>
     */
    public static function months(DateTimeImmutable $firstMonth, int $count, array $periods, DateTimeImmutable $today): array
    {
        $months = [];
        for ($i = 0; $i < $count; $i++) {
            $month = $firstMonth->modify("first day of +$i month");
            $cells = array_fill(0, (int) $month->format('N') - 1, null);
            $days = (int) $month->format('t');
            for ($day = 1; $day <= $days; $day++) {
                $date = $month->setDate((int) $month->format('Y'), (int) $month->format('n'), $day)->format('Y-m-d');
                $cells[] = ['day' => $day, 'date' => $date, 'state' => self::state($date, $periods, $today->format('Y-m-d'))];
            }
            while (count($cells) % 7 !== 0) {
                $cells[] = null;
            }
            $months[] = ['month' => $month->format('Y-m-01'), 'weeks' => array_chunk($cells, 7)];
        }

        return $months;
    }

    /**
     * @param list<array{arrivee: string, depart: string}> $periods
     */
    private static function state(string $date, array $periods, string $today): string
    {
        if ($date < $today) {
            return 'past';
        }
        foreach ($periods as $period) {
            // La nuit du jour de départ est libre : un autre séjour peut commencer ce jour-là.
            if ($date >= $period['arrivee'] && $date < $period['depart']) {
                return 'booked';
            }
        }

        return 'free';
    }
}
