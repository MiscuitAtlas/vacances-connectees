<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Tests\DatabaseTestCase;

/**
 * Pages publiques, réservation, annulation et avis.
 */
final class BookingFlowTest extends DatabaseTestCase
{
    private int $owner;
    private int $traveller;
    private int $listing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->createUser();
        $this->traveller = $this->createUser('Bob Durand', 'bob@exemple.fr');
        $this->listing = $this->createListing($this->owner, ['prix_nuit' => 90]);
    }

    private function reserve(string $arrival, string $departure): \App\Http\Response
    {
        return $this->post('/logements/' . $this->listing . '/reserver', ['arrivee' => $arrival, 'depart' => $departure, 'voyageurs' => '2']);
    }

    public function testPublicPagesAndSecurityHeaders(): void
    {
        foreach (['/', '/recherche', '/logements/' . $this->listing, '/connexion', '/inscription', '/mot-de-passe-oublie'] as $path) {
            $response = $this->get($path);
            $this->assertSame(200, $response->status, $path);
            $this->assertStringContainsString("script-src 'self'", (string) $response->header('Content-Security-Policy'));
            $this->assertSame('DENY', $response->header('X-Frame-Options'));
        }
        $this->assertNull($this->get('/')->header('Strict-Transport-Security'));
        $this->assertSame('max-age=31536000', $this->get('/', [], true)->header('Strict-Transport-Security'));
        $this->assertSame(404, $this->get('/logements/999')->status);
    }

    public function testListingTextIsEscaped(): void
    {
        $this->createListing($this->owner, ['titre' => '<script>alert(1)</script>', 'description' => str_repeat('<img src=x onerror=alert(1)> ', 3)]);

        $search = $this->get('/recherche');
        $this->assertStringNotContainsString('<script>alert(1)</script>', $search->body);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $search->body);
        $this->assertStringNotContainsString('<img src=x', $this->get('/logements/2')->body);
    }

    public function testAVisitorIsSentToLoginAndKeepsTheirDates(): void
    {
        $response = $this->reserve('2026-08-01', '2026-08-04');

        $this->assertSame(303, $response->status);
        parse_str((string) parse_url((string) $response->header('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('/logements/' . $this->listing . '?arrivee=2026-08-01&depart=2026-08-04&voyageurs=2', $query['retour']);
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn());
    }

    private function assertOwnerWasEmailed(string $subject, string $text): void
    {
        $mails = glob($this->tmpDir . '/mail/*.eml') ?: [];
        $this->assertCount(1, $mails);
        $mail = (string) file_get_contents($mails[0]);
        $this->assertStringContainsString('To: alice@exemple.fr', $mail);
        preg_match('/^Subject: (.+?)\n(?=\S)/ms', $mail, $header);
        $this->assertStringContainsString($subject, mb_decode_mimeheader($header[1]));
        $this->assertStringContainsString($text, $mail);
    }

    public function testBookingThenSeeingItAsTravellerAndOwnerOnly(): void
    {
        $this->loginAs($this->traveller);
        $response = $this->reserve('2026-08-01', '2026-08-04');
        $this->assertSame('/reservations/1', $response->header('Location'));
        $this->assertSame(270, (int) $this->pdo->query('SELECT prix_total FROM reservations')->fetchColumn());
        $this->assertOwnerWasEmailed('Nouvelle réservation', 'Bob Durand a réservé');

        $this->assertStringContainsString('Annuler la réservation', $this->get('/reservations/1')->body);
        $this->loginAs($this->owner);
        $page = $this->get('/reservations/1');
        $this->assertSame(200, $page->status);
        $this->assertStringNotContainsString('Annuler la réservation', $page->body);
        $this->assertSame(403, $this->post('/reservations/1/annuler')->status);

        $this->loginAs($this->createUser('Ève', 'eve@exemple.fr'));
        $this->assertSame(404, $this->get('/reservations/1')->status);
        $this->assertSame(404, $this->post('/reservations/1/annuler')->status);
    }

    public function testOverlappingDatesAreRefusedWithAMessage(): void
    {
        $this->createBooking($this->listing, $this->traveller, '2026-08-01', '2026-08-05');
        $this->loginAs($this->createUser('Ève', 'eve@exemple.fr'));

        $response = $this->reserve('2026-08-03', '2026-08-06');

        $this->assertSame(422, $response->status);
        $this->assertStringContainsString('ne sont plus disponibles', $response->body);
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn());
    }

    public function testCancellingFreesTheDates(): void
    {
        $booking = $this->createBooking($this->listing, $this->traveller, '2026-08-01', '2026-08-05');
        $this->loginAs($this->traveller);

        $this->post('/reservations/' . $booking . '/annuler');

        $this->assertSame('annulee', $this->pdo->query('SELECT statut FROM reservations')->fetchColumn());
        $this->assertOwnerWasEmailed('annul', 'Bob Durand a annulé');
        $search = $this->get('/recherche', ['arrivee' => '2026-08-02', 'depart' => '2026-08-04']);
        $this->assertStringContainsString("1\u{00A0}logement disponible", $search->body);
    }

    public function testReviewsAfterTheStayOnly(): void
    {
        $future = $this->createBooking($this->listing, $this->traveller, '2026-08-01', '2026-08-05');
        $past = $this->createBooking($this->listing, $this->traveller, '2026-06-01', '2026-06-05');
        $this->loginAs($this->traveller);

        $this->assertSame(403, $this->get('/reservations/' . $future . '/avis')->status);
        $this->assertSame(422, $this->post('/reservations/' . $past . '/avis', ['note' => '6', 'commentaire' => 'Trop court'])->status);
        $this->assertSame(303, $this->post('/reservations/' . $past . '/avis', ['note' => '4', 'commentaire' => 'Très bon séjour, merci !'])->status);
        $this->assertSame(403, $this->post('/reservations/' . $past . '/avis', ['note' => '5', 'commentaire' => 'Un deuxième avis ?'])->status);

        $this->loginAs($this->owner);
        $this->assertSame(404, $this->get('/reservations/' . $past . '/avis')->status);
        $this->assertStringContainsString('Très bon séjour, merci !', $this->get('/logements/' . $this->listing)->body);
    }
}
