<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\Stay;
use App\Domain\ValidationException;
use App\Repository\BookingRepository;
use App\Repository\ListingRepository;
use App\Service\BookingService;
use App\Tests\DatabaseTestCase;
use DateTimeImmutable;

final class BookingServiceTest extends DatabaseTestCase
{
    private BookingService $service;
    private BookingRepository $bookings;

    /**
     * @var array<string, mixed>
     */
    private array $listing;
    private int $owner;
    private int $traveller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookings = new BookingRepository($this->pdo);
        $this->service = new BookingService($this->pdo, $this->bookings);
        $this->owner = $this->createUser();
        $this->traveller = $this->createUser('Bob', 'bob@exemple.fr');
        $this->listing = (array) (new ListingRepository($this->pdo))->find($this->createListing($this->owner, ['prix_nuit' => 120, 'capacite' => 4]));
    }

    private function book(string $arrival, string $departure, int $guests = 2, ?int $traveller = null): int
    {
        return $this->service->book($this->listing, $traveller ?? $this->traveller, Stay::fromStrings($arrival, $departure), $guests, $this->now());
    }

    private function assertRefused(string $field, callable $call): void
    {
        try {
            $call();
            $this->fail('La réservation aurait dû être refusée.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey($field, $error->errors);
        }
    }

    public function testBooksAndComputesTheTotalOnTheServer(): void
    {
        $booking = $this->bookings->find($this->book('2026-08-01', '2026-08-06', 3));

        $this->assertSame(600, $booking['prix_total']);
        $this->assertSame('confirmee', $booking['statut']);
        $this->assertSame($this->traveller, $booking['voyageur_id']);
    }

    public function testRefusesOverlappingDatesButAcceptsBackToBackStays(): void
    {
        $this->book('2026-08-01', '2026-08-06');

        $this->assertRefused('arrivee', fn () => $this->book('2026-08-05', '2026-08-09'));
        $this->assertRefused('arrivee', fn () => $this->book('2026-07-28', '2026-08-15'));
        $this->assertGreaterThan(0, $this->book('2026-08-06', '2026-08-09'));
        $this->assertGreaterThan(0, $this->book('2026-07-28', '2026-08-01'));
    }

    public function testACancelledBookingFreesTheDates(): void
    {
        $id = $this->book('2026-08-01', '2026-08-06');
        $this->service->cancel((array) $this->bookings->find($id), new DateTimeImmutable('2026-07-01'));

        $this->assertGreaterThan(0, $this->book('2026-08-02', '2026-08-04'));
    }

    public function testRefusesTheOwnerAndTooManyGuests(): void
    {
        $this->assertRefused('arrivee', fn () => $this->book('2026-08-01', '2026-08-03', 2, $this->owner));
        $this->assertRefused('voyageurs', fn () => $this->book('2026-08-01', '2026-08-03', 5));
        $this->assertRefused('voyageurs', fn () => $this->book('2026-08-01', '2026-08-03', 0));
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn());
    }

    public function testCancellationOnlyBeforeArrival(): void
    {
        $booking = (array) $this->bookings->find($this->book('2026-08-01', '2026-08-06'));

        $this->assertRefused('reservation', fn () => $this->service->cancel($booking, new DateTimeImmutable('2026-08-01')));
        $this->service->cancel($booking, new DateTimeImmutable('2026-07-31'));
        $cancelled = (array) $this->bookings->find($booking['id']);
        $this->assertRefused('reservation', fn () => $this->service->cancel($cancelled, new DateTimeImmutable('2026-07-31')));
    }

    public function testReviewsOnlyAfterDepartureAndOnce(): void
    {
        $booking = (array) $this->bookings->find($this->book('2026-08-01', '2026-08-06'));

        $this->assertFalse(BookingService::canReview($booking, new DateTimeImmutable('2026-08-05')));
        $this->assertTrue(BookingService::canReview($booking, new DateTimeImmutable('2026-08-06')));
        $this->assertFalse(BookingService::canReview(['a_un_avis' => true] + $booking, new DateTimeImmutable('2026-08-10')));
        $this->assertFalse(BookingService::canReview(['statut' => 'annulee'] + $booking, new DateTimeImmutable('2026-08-10')));
    }
}
