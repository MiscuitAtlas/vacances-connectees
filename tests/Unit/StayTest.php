<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Stay;
use App\Domain\ValidationException;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StayTest extends TestCase
{
    private DateTimeImmutable $today;

    protected function setUp(): void
    {
        $this->today = new DateTimeImmutable('2026-07-01');
    }

    public function testCountsNightsWithoutTheDepartureDay(): void
    {
        $this->assertSame(5, Stay::fromStrings('2026-07-10', '2026-07-15')->nights());
        $this->assertSame(3, Stay::fromStrings('2026-12-30', '2027-01-02')->nights());
    }

    public function testStaysThatShareOnlyADepartureDayDoNotOverlap(): void
    {
        $first = Stay::fromStrings('2026-07-10', '2026-07-15');

        $this->assertFalse($first->overlaps(Stay::fromStrings('2026-07-15', '2026-07-20')));
        $this->assertFalse($first->overlaps(Stay::fromStrings('2026-07-05', '2026-07-10')));
        $this->assertTrue($first->overlaps(Stay::fromStrings('2026-07-14', '2026-07-16')));
        $this->assertTrue($first->overlaps(Stay::fromStrings('2026-07-01', '2026-07-31')));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function refusedStays(): iterable
    {
        yield 'date inexistante' => ['2026-02-30', '2026-03-02', 'arrivee'];
        yield 'format libre' => ['10/07/2026', '2026-07-12', 'arrivee'];
        yield 'départ avant arrivée' => ['2026-07-12', '2026-07-10', 'depart'];
        yield 'zéro nuit' => ['2026-07-12', '2026-07-12', 'depart'];
        yield 'arrivée passée' => ['2026-06-30', '2026-07-03', 'arrivee'];
        yield 'plus de 28 nuits' => ['2026-07-02', '2026-07-31', 'depart'];
        yield 'plus d\'un an à l\'avance' => ['2027-07-02', '2027-07-05', 'arrivee'];
    }

    #[DataProvider('refusedStays')]
    public function testRefusesInvalidBookings(string $arrival, string $departure, string $field): void
    {
        try {
            Stay::forBooking($arrival, $departure, $this->today);
            $this->fail('Le séjour aurait dû être refusé.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey($field, $error->errors);
        }
    }

    public function testAcceptsAStayStartingToday(): void
    {
        $stay = Stay::forBooking('2026-07-01', '2026-07-29', $this->today);

        $this->assertSame(Stay::MAX_NIGHTS, $stay->nights());
    }
}
