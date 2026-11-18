<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Repository\ListingRepository;
use App\Tests\DatabaseTestCase;

final class SearchTest extends DatabaseTestCase
{
    private ListingRepository $listings;
    private int $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listings = new ListingRepository($this->pdo);
        $this->owner = $this->createUser();
    }

    /**
     * @param array<string, mixed> $criteria
     * @return list<string>
     */
    private function titles(array $criteria): array
    {
        $criteria += ['destination' => '', 'arrivee' => '', 'depart' => '', 'voyageurs' => 1, 'chambres' => 0, 'prix_max' => null, 'tri' => 'note'];

        return array_column($this->listings->search($criteria, 1, 20)['items'], 'titre');
    }

    public function testDatesHideListingsWithAnOverlappingConfirmedBooking(): void
    {
        $traveller = $this->createUser('Bob', 'bob@exemple.fr');
        $booked = $this->createListing($this->owner, ['titre' => 'Réservée']);
        $cancelled = $this->createListing($this->owner, ['titre' => 'Réservation annulée']);
        $this->createListing($this->owner, ['titre' => 'Libre']);
        $this->createBooking($booked, $traveller, '2026-08-01', '2026-08-08');
        $this->createBooking($cancelled, $traveller, '2026-08-01', '2026-08-08', 'annulee');

        $this->assertEqualsCanonicalizing(['Réservation annulée', 'Libre'], $this->titles(['arrivee' => '2026-08-05', 'depart' => '2026-08-10']));
        // Le jour du départ, le logement est de nouveau libre.
        $this->assertContains('Réservée', $this->titles(['arrivee' => '2026-08-08', 'depart' => '2026-08-10']));
    }

    public function testFiltersOnDestinationCapacityAndPrice(): void
    {
        $this->createListing($this->owner, ['titre' => 'Chalet', 'ville' => 'Chamonix', 'region' => 'Alpes', 'capacite' => 8, 'prix_nuit' => 250]);
        $this->createListing($this->owner, [
            'titre' => 'Studio', 'ville' => 'Lyon', 'region' => 'Auvergne-Rhône-Alpes', 'capacite' => 2, 'prix_nuit' => 70,
        ]);

        $this->assertSame(['Chalet', 'Studio'], $this->titles(['destination' => 'alpes', 'tri' => 'prix_desc']));
        $this->assertSame(['Chalet'], $this->titles(['voyageurs' => 6]));
        $this->assertSame(['Studio'], $this->titles(['prix_max' => 100]));
        $this->assertSame(['Studio', 'Chalet'], $this->titles(['tri' => 'prix']));
    }

    public function testSearchTextIsNeverInterpretedAsSqlOrWildcards(): void
    {
        $this->createListing($this->owner, ['titre' => 'Maison']);

        $this->assertSame([], $this->titles(['destination' => "' OR 1=1 --"]));
        $this->assertSame([], $this->titles(['destination' => '%']));
        $this->assertSame([], $this->titles(['destination' => '_']));
        $this->assertSame(['Maison'], $this->titles(['destination' => 'quib']));
    }

    public function testBestRatedFirstAndListingsWithoutReviewsLast(): void
    {
        $traveller = $this->createUser('Bob', 'bob@exemple.fr');
        $this->createListing($this->owner, ['titre' => 'Sans avis']);
        foreach (['Moyenne' => 3, 'Excellente' => 5] as $title => $note) {
            $listing = $this->createListing($this->owner, ['titre' => $title]);
            $booking = $this->createBooking($listing, $traveller, '2026-05-01', '2026-05-03');
            $this->pdo->prepare('INSERT INTO avis (reservation_id, logement_id, auteur_id, note, commentaire, cree_le) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$booking, $listing, $traveller, $note, 'Commentaire de test.', self::NOW]);
        }

        $this->assertSame(['Excellente', 'Moyenne', 'Sans avis'], $this->titles([]));
        $this->assertSame(5.0, $this->listings->topRated(1)[0]['note_moyenne']);
    }
}
