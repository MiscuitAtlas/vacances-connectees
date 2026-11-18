<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\App;
use App\Database;
use App\Demo\DemoSite;
use App\Demo\Sandboxes;
use App\Http\Request;
use App\Http\Response;
use App\Security\Csrf;
use App\Security\Session;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Démo en ligne, vue par deux visiteurs : chacun a ses cookies, sa session et sa copie de la base.
 */
final class DemoTest extends TestCase
{
    private string $dir;
    private Sandboxes $sandboxes;
    private DateTimeImmutable $now;

    /**
     * @var array<string, array{cookies: array<string, string>, session: array<string, mixed>}>
     */
    private array $visitors = [];

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/vc-demo-http-' . bin2hex(random_bytes(4));
        $this->sandboxes = new Sandboxes($this->dir, 7200, 50);
        $this->now = new DateTimeImmutable('2026-07-01 10:00');
    }

    protected function tearDown(): void
    {
        self::removeDir($this->dir);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function config(array $overrides = []): array
    {
        return array_replace_recursive([
            'env' => 'test',
            'url' => 'https://demo.example',
            'db' => ['dsn' => 'sqlite::memory:'],
            'mail' => ['transport' => 'log', 'from' => 'demo@vacances.example', 'log_dir' => $this->dir . '/mail-hors-demo'],
            'security' => ['max_login_failures' => 5, 'login_lock_minutes' => 15, 'reset_token_minutes' => 30],
            'uploads' => ['enabled' => true, 'dir' => $this->dir . '/photos', 'url' => '/uploads/logements', 'max_bytes' => 5 * 1024 * 1024],
            'demo' => ['enabled' => true],
        ], $overrides);
    }

    /**
     * Une requête de navigateur, préparée comme dans public/index.php.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $query
     */
    private function request(string $visitor, string $method, string $path, array $data = [], array $query = [], bool $withToken = true): Response
    {
        $this->visitors[$visitor] ??= ['cookies' => [], 'session' => []];
        $state = &$this->visitors[$visitor];
        $session = new Session($state['session']);
        if ($method === 'POST' && $withToken) {
            $data[Csrf::FIELD] = (new Csrf($session))->token();
        }
        $request = new Request($method, $path, $query, $data, [], [], $state['cookies']);

        ['config' => $config, 'newCopy' => $newCopy] = (new DemoSite($this->sandboxes))->prepare($this->config(), $request, $session, $this->now);
        if ($newCopy !== null) {
            $state['cookies'][Sandboxes::COOKIE] = $newCopy;
        }
        $app = new App($config, Database::connect($config['db']), $session, fn (): DateTimeImmutable => $this->now);

        return $app->handle($request);
    }

    private function copies(): int
    {
        return count(glob($this->dir . '/copies/*', GLOB_ONLYDIR) ?: []);
    }

    public function testBrowsingUsesTheSharedTemplateWithoutCreatingACopy(): void
    {
        $home = $this->request('anne', 'GET', '/');

        $this->assertSame(200, $home->status);
        $this->assertStringContainsString('Démo en ligne', $home->body);
        $this->assertSame(200, $this->request('anne', 'GET', '/logements/1')->status);
        $this->assertSame(400, $this->request('robot', 'POST', '/connexion', ['email' => 'demo@exemple.fr'], [], false)->status);
        $this->assertSame(0, $this->copies());
    }

    public function testOneClickLoginCreatesACopyForThisVisitorOnly(): void
    {
        $this->request('anne', 'GET', '/connexion');
        $response = $this->request('anne', 'POST', '/demo/connexion', ['compte' => 'proprietaire']);

        $this->assertSame('/mes-logements', $response->header('Location'));
        $this->assertSame(1, $this->copies());
        $this->assertStringContainsString('Hélène', $this->request('anne', 'GET', '/mes-logements')->body);

        // Anne publie une annonce dans sa copie : Bruno ne la voit pas.
        $this->request('anne', 'POST', '/logements/nouveau', [
            'titre' => 'Cabane créée pendant la démo', 'type' => 'cabane', 'cadre' => 'lac', 'ville' => 'Annecy',
            'region' => 'Auvergne-Rhône-Alpes', 'prix_nuit' => '90', 'chambres' => '1', 'capacite' => '2',
            'description' => 'Une cabane ajoutée par un visiteur de la démo en ligne.',
        ]);
        $this->assertStringContainsString('Cabane créée pendant la démo', $this->request('anne', 'GET', '/mes-logements')->body);
        $search = $this->request('bruno', 'GET', '/recherche', [], ['destination' => 'Annecy']);
        $this->assertStringNotContainsString('Cabane créée pendant la démo', $search->body);
    }

    public function testEmailsAreShownInTheVisitorsMailbox(): void
    {
        $this->assertStringContainsString('Aucun email', $this->request('anne', 'GET', '/demo/emails')->body);

        $this->request('anne', 'POST', '/mot-de-passe-oublie', ['email' => 'demo@exemple.fr']);
        $mailbox = $this->request('anne', 'GET', '/demo/emails')->body;

        $this->assertStringContainsString('Réinitialisation de votre mot de passe', $mailbox);
        $this->assertMatchesRegularExpression('#<a href="/reinitialisation\?jeton=[a-f0-9]{64}">https://demo\.example/reinitialisation#', $mailbox);
        $this->assertStringContainsString('Emails envoyés (1)', $mailbox);
        $this->assertStringContainsString('Aucun email', $this->request('bruno', 'GET', '/demo/emails')->body);
    }

    public function testAnExpiredCopyLogsTheVisitorOut(): void
    {
        $this->request('anne', 'GET', '/connexion');
        $this->request('anne', 'POST', '/demo/connexion', ['compte' => 'voyageuse']);
        $this->assertSame(200, $this->request('anne', 'GET', '/compte')->status);

        $this->now = $this->now->modify('+2 hours');
        $response = $this->request('anne', 'GET', '/compte');

        $this->assertSame(303, $response->status);
        $this->assertArrayNotHasKey('user_id', $this->visitors['anne']['session']);
        $this->assertStringContainsString('a expiré', $this->request('anne', 'GET', '/connexion')->body);
    }

    public function testPhotosAreDisabledInTheDemo(): void
    {
        $this->request('anne', 'GET', '/connexion');
        $this->request('anne', 'POST', '/demo/connexion', ['compte' => 'proprietaire']);

        $form = $this->request('anne', 'GET', '/logements/nouveau')->body;

        $this->assertStringContainsString('l\'envoi de photos est désactivé', $form);
        $this->assertStringNotContainsString('type="file"', $form);
    }

    public function testDemoPagesDoNotExistOnTheRealSite(): void
    {
        $session = [];
        $app = new App(
            $this->config(['demo' => ['enabled' => false]]),
            Database::connect(['dsn' => 'sqlite::memory:']),
            new Session($session),
            fn (): DateTimeImmutable => $this->now,
        );

        $this->assertSame(404, $app->handle(new Request('GET', '/demo/emails'))->status);
    }

    private static function removeDir(string $dir): void
    {
        foreach (glob($dir . '/{,.}*', GLOB_BRACE) ?: [] as $path) {
            if (in_array(basename($path), ['.', '..'], true)) {
                continue;
            }
            is_dir($path) ? self::removeDir($path) : unlink($path);
        }
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }
}
