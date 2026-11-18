<?php

declare(strict_types=1);

namespace App\Tests;

use App\App;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Security\Csrf;
use App\Security\Session;
use App\Service\Passwords;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base de test : SQLite en mémoire, schéma complet, et une application qu'on interroge comme un navigateur.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected const NOW = '2026-07-01 10:00:00';
    protected const PASSWORD = 'mot-de-passe-de-test';

    protected PDO $pdo;
    protected string $tmpDir;

    /**
     * @var array<string, mixed>
     */
    protected array $sessionData = [];

    protected function setUp(): void
    {
        $this->pdo = Database::connect(['dsn' => 'sqlite::memory:']);
        Database::createSchema($this->pdo);
        $this->tmpDir = sys_get_temp_dir() . '/vc-tests-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir . '/mail', 0775, true);
        mkdir($this->tmpDir . '/uploads', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    protected function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::NOW);
    }

    /**
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        return [
            'env' => 'test',
            'url' => 'https://vacances.example',
            'db' => ['dsn' => 'sqlite::memory:'],
            'mail' => ['transport' => 'log', 'from' => 'test@vacances.example', 'log_dir' => $this->tmpDir . '/mail'],
            'security' => ['max_login_failures' => 5, 'login_lock_minutes' => 15, 'reset_token_minutes' => 30],
            'uploads' => ['dir' => $this->tmpDir . '/uploads', 'url' => '/uploads/logements', 'max_bytes' => 5 * 1024 * 1024],
        ];
    }

    protected function app(): App
    {
        return new App($this->config(), $this->pdo, new Session($this->sessionData), fn (): DateTimeImmutable => $this->now());
    }

    protected function createUser(string $name = 'Alice Martin', string $email = 'alice@exemple.fr', string $password = self::PASSWORD): int
    {
        $statement = $this->pdo->prepare('INSERT INTO utilisateurs (nom, email, mot_de_passe, cree_le) VALUES (?, ?, ?, ?)');
        $statement->execute([$name, $email, Passwords::hash($password), self::NOW]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function createListing(int $ownerId, array $overrides = []): int
    {
        $data = $overrides + [
            'titre' => 'Maison au bord de l\'eau',
            'type' => 'maison',
            'cadre' => 'mer',
            'ville' => 'Quiberon',
            'region' => 'Bretagne',
            'prix_nuit' => 100,
            'chambres' => 2,
            'capacite' => 4,
            'description' => 'Une maison de test assez longue pour passer la validation.',
            'equipements' => 'wifi,jardin',
        ];
        $statement = $this->pdo->prepare(
            'INSERT INTO logements (proprietaire_id, titre, type, cadre, ville, region, prix_nuit, chambres, capacite,'
            . ' description, equipements, cree_le) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        );
        $statement->execute([
            $ownerId, $data['titre'], $data['type'], $data['cadre'], $data['ville'], $data['region'], $data['prix_nuit'],
            $data['chambres'], $data['capacite'], $data['description'], $data['equipements'], self::NOW,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    protected function createBooking(
        int $listingId,
        int $travellerId,
        string $arrival,
        string $departure,
        string $status = 'confirmee',
        int $guests = 2,
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO reservations (logement_id, voyageur_id, arrivee, depart, voyageurs, prix_total, statut, cree_le)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        );
        $statement->execute([$listingId, $travellerId, $arrival, $departure, $guests, 300, $status, self::NOW]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, string> $query
     */
    protected function get(string $path, array $query = [], bool $https = false): Response
    {
        return $this->app()->handle(new Request('GET', $path, $query, [], [], $https ? ['HTTPS' => 'on'] : []));
    }

    /**
     * Envoi d'un formulaire avec le jeton CSRF de la session, comme le ferait le navigateur.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $files
     */
    protected function post(string $path, array $data = [], bool $withToken = true, array $files = []): Response
    {
        if ($withToken) {
            $data[Csrf::FIELD] = (new Csrf(new Session($this->sessionData)))->token();
        }

        return $this->app()->handle(new Request('POST', $path, [], $data, $files));
    }

    protected function loginAs(int $userId): void
    {
        $this->sessionData['user_id'] = $userId;
    }

    /**
     * @return list<array{type: string, message: string}>
     */
    protected function flashes(): array
    {
        $flashes = $this->sessionData['_flash'] ?? [];
        unset($this->sessionData['_flash']);

        return $flashes;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
