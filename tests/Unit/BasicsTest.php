<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Catalog;
use App\Http\HttpException;
use App\Http\Router;
use App\Service\Passwords;
use App\View\Calendar;
use App\View\Poster;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Règles simples : mots de passe, routes, calendrier, illustrations.
 */
final class BasicsTest extends TestCase
{
    public function testPasswordRules(): void
    {
        $this->assertNotNull(Passwords::problem('court', 'court'));
        $this->assertNotNull(Passwords::problem(str_repeat('é', 40), str_repeat('é', 40)), 'Plus de 72 octets');
        $this->assertNotNull(Passwords::problem('alice@exemple.fr', 'alice@exemple.fr', 'Alice@exemple.fr'));
        $this->assertNotNull(Passwords::problem('un long mot de passe', 'un autre mot de passe'));
        $this->assertNull(Passwords::problem('un long mot de passe', 'un long mot de passe', 'alice@exemple.fr'));
    }

    public function testRouterExtractsIdsAndDistinguishesMethods(): void
    {
        $router = new Router();
        $router->get('/logements/{id}', [self::class, 'show']);
        $router->post('/logements/{id}/reserver', [self::class, 'book']);

        $this->assertSame(['id' => 42], $router->match('GET', '/logements/42')['params']);
        $this->assertStatus(405, fn () => $router->match('GET', '/logements/42/reserver'));
        $this->assertStatus(404, fn () => $router->match('GET', '/logements/0'));
        $this->assertStatus(404, fn () => $router->match('GET', '/logements/007'));
        $this->assertStatus(404, fn () => $router->match('GET', '/logements/1 OR 1=1'));
    }

    public function testCalendarMarksBookedNightsAndFreesTheDepartureDay(): void
    {
        $months = Calendar::months(
            new DateTimeImmutable('2026-07-01'),
            1,
            [['arrivee' => '2026-07-10', 'depart' => '2026-07-12']],
            new DateTimeImmutable('2026-07-05'),
        );
        $days = [];
        foreach ($months[0]['weeks'] as $week) {
            foreach (array_filter($week) as $cell) {
                $days[$cell['day']] = $cell['state'];
            }
        }

        $this->assertSame('past', $days[4]);
        $this->assertSame('free', $days[9]);
        $this->assertSame('booked', $days[10]);
        $this->assertSame('booked', $days[11]);
        $this->assertSame('free', $days[12]);
        // Le 1er juillet 2026 est un mercredi : deux cases vides avant lui.
        $this->assertSame([null, null], array_slice($months[0]['weeks'][0], 0, 2));
    }

    public function testEveryIllustrationIsValidSvg(): void
    {
        foreach (array_keys(Catalog::TYPES) as $type) {
            foreach (array_keys(Catalog::SETTINGS) as $setting) {
                $svg = Poster::render($type, $setting, 3, 'Chalet « test » & <b>');
                $this->assertNotFalse(simplexml_load_string($svg), "$type / $setting");
                $this->assertStringNotContainsString('<b>', $svg);
            }
        }
    }

    private function assertStatus(int $status, callable $call): void
    {
        try {
            $call();
            $this->fail("Code $status attendu.");
        } catch (HttpException $error) {
            $this->assertSame($status, $error->status);
        }
    }
}
