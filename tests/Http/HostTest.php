<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Tests\DatabaseTestCase;

/**
 * Espace propriétaire : publication, modification et suppression des annonces.
 */
final class HostTest extends DatabaseTestCase
{
    /**
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return $overrides + [
            'titre' => 'Chalet au calme',
            'type' => 'chalet',
            'cadre' => 'montagne',
            'ville' => 'Megève',
            'region' => 'Auvergne-Rhône-Alpes',
            'prix_nuit' => '180',
            'chambres' => '3',
            'capacite' => '6',
            'description' => "Grand chalet familial.\nVue sur le Mont-Blanc depuis la terrasse.",
            'equipements' => ['wifi', 'cheminee', 'inconnu'],
        ];
    }

    public function testPagesRequireLogin(): void
    {
        foreach (['/mes-logements', '/logements/nouveau', '/compte', '/compte/profil'] as $path) {
            $response = $this->get($path);
            $this->assertSame(303, $response->status, $path);
            $this->assertStringStartsWith('/connexion?retour=', (string) $response->header('Location'));
        }
    }

    public function testPublishingAListing(): void
    {
        $owner = $this->createUser();
        $this->loginAs($owner);

        $invalid = $this->post('/logements/nouveau', $this->form(['type' => 'chateau', 'prix_nuit' => '-5']));
        $this->assertSame(422, $invalid->status);
        $this->assertStringContainsString('Choisissez le type de logement.', $invalid->body);

        $response = $this->post('/logements/nouveau', $this->form(['proprietaire_id' => '999']));
        $this->assertSame('/logements/1', $response->header('Location'));
        $listing = $this->pdo->query('SELECT * FROM logements')->fetch();
        $this->assertSame($owner, (int) $listing['proprietaire_id']);
        $this->assertSame('wifi,cheminee', $listing['equipements']);
    }

    public function testOnlyTheOwnerCanEditOrDelete(): void
    {
        $owner = $this->createUser();
        $listing = $this->createListing($owner);
        $this->loginAs($this->createUser('Ève', 'eve@exemple.fr'));

        $this->assertSame(404, $this->get('/logements/' . $listing . '/modifier')->status);
        $this->assertSame(404, $this->post('/logements/' . $listing . '/modifier', $this->form())->status);
        $this->assertSame(404, $this->post('/logements/' . $listing . '/supprimer')->status);
        $this->assertSame('Maison au bord de l\'eau', $this->pdo->query('SELECT titre FROM logements')->fetchColumn());

        $this->loginAs($owner);
        $this->assertSame(303, $this->post('/logements/' . $listing . '/modifier', $this->form())->status);
        $this->assertSame('Chalet au calme', $this->pdo->query('SELECT titre FROM logements')->fetchColumn());
    }

    public function testAListingWithUpcomingStaysCannotBeDeleted(): void
    {
        $owner = $this->createUser();
        $listing = $this->createListing($owner);
        $this->createBooking($listing, $this->createUser('Bob', 'bob@exemple.fr'), '2026-08-01', '2026-08-05');
        $this->loginAs($owner);

        $this->post('/logements/' . $listing . '/supprimer');
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM logements')->fetchColumn());
        $this->assertSame('error', $this->flashes()[0]['type']);

        $this->pdo->exec("UPDATE reservations SET statut = 'annulee'");
        $this->post('/logements/' . $listing . '/supprimer');
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM logements')->fetchColumn());
    }

    public function testTheOwnerSeesBookingsOnTheirListings(): void
    {
        $owner = $this->createUser();
        $listing = $this->createListing($owner, ['titre' => 'Ma maison']);
        $this->createBooking($listing, $this->createUser('Bob Durand', 'bob@exemple.fr'), '2026-08-01', '2026-08-05');
        $this->createBooking($listing, $this->createUser('Ancien', 'ancien@exemple.fr'), '2026-05-01', '2026-05-05');
        $this->loginAs($owner);

        $page = $this->get('/mes-logements')->body;
        $this->assertStringContainsString('Bob Durand', $page);
        $this->assertStringNotContainsString('Ancien', $page);
    }
}
