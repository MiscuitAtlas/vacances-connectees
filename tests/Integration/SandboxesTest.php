<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Database;
use App\Demo\Sandboxes;
use DateTimeImmutable;
use PDOException;
use PHPUnit\Framework\TestCase;

final class SandboxesTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/vc-demo-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        self::removeDir($this->dir);
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

    private function sandboxes(int $maxCopies = 10): Sandboxes
    {
        return new Sandboxes($this->dir, 7200, $maxCopies);
    }

    public function testTheTemplateIsBuiltOncePerDayAndOpenedReadOnly(): void
    {
        $sandboxes = $this->sandboxes();
        $monday = $sandboxes->template(new DateTimeImmutable('2026-07-01 09:00'));
        $this->assertSame($monday, $sandboxes->template(new DateTimeImmutable('2026-07-01 18:00')));

        $pdo = Database::connect(['dsn' => 'sqlite:' . $monday, 'readonly' => true]);
        $this->assertSame(1, (int) $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE email = 'demo@exemple.fr'")->fetchColumn());
        try {
            $pdo->exec('DELETE FROM avis');
            $this->fail('La base modèle doit être en lecture seule.');
        } catch (PDOException) {
            $this->assertGreaterThan(0, (int) $pdo->query('SELECT COUNT(*) FROM avis')->fetchColumn());
        }

        $tuesday = $sandboxes->template(new DateTimeImmutable('2026-07-02 08:00'));
        $this->assertNotSame($monday, $tuesday);
        $this->assertFileDoesNotExist($monday);
    }

    public function testEachCopyIsIndependentAndExpiresAfterTwoHours(): void
    {
        $sandboxes = $this->sandboxes();
        $now = new DateTimeImmutable('2026-07-01 10:00');
        $first = $sandboxes->create($now);
        $second = $sandboxes->create($now);

        $this->assertMatchesRegularExpression('/^\d{10}-[a-f0-9]{32}$/', $first);
        $this->assertNotSame($first, $second);
        Database::connect(['dsn' => 'sqlite:' . $sandboxes->database($first)])->exec('DELETE FROM logements');
        $other = Database::connect(['dsn' => 'sqlite:' . $sandboxes->database($second)]);
        $this->assertGreaterThan(0, (int) $other->query('SELECT COUNT(*) FROM logements')->fetchColumn());

        $this->assertSame($first, $sandboxes->find($first, $now->modify('+119 minutes')));
        $this->assertNull($sandboxes->find($first, $now->modify('+2 hours')));
        $this->assertSame('2026-07-01 12:00', $sandboxes->expiresAt($first)->setTimezone($now->getTimezone())->format('Y-m-d H:i'));
    }

    public function testRefusesForgedIdentifiers(): void
    {
        $sandboxes = $this->sandboxes();
        $now = new DateTimeImmutable('2026-07-01 10:00');
        $id = $sandboxes->create($now);

        $this->assertNull($sandboxes->find('', $now));
        $this->assertNull($sandboxes->find('../../modele', $now));
        $this->assertNull($sandboxes->find($now->getTimestamp() . '-' . str_repeat('0', 32), $now));
        $this->assertNull($sandboxes->find(str_replace((string) $now->getTimestamp(), (string) ($now->getTimestamp() + 3600), $id), $now));
    }

    public function testOldCopiesAreRemovedToMakeRoom(): void
    {
        $sandboxes = $this->sandboxes(3);
        $start = new DateTimeImmutable('2026-07-01 10:00');
        $expired = $sandboxes->create($start);
        $ids = [];
        foreach ([150, 160, 170, 180] as $minutes) {
            $ids[] = $sandboxes->create($start->modify("+$minutes minutes"));
        }
        $now = $start->modify('+181 minutes');

        $this->assertDirectoryDoesNotExist(dirname($sandboxes->database($expired)));
        $this->assertNull($sandboxes->find($ids[0], $now), 'La copie la plus ancienne laisse sa place.');
        foreach (array_slice($ids, 1) as $id) {
            $this->assertSame($id, $sandboxes->find($id, $now));
        }
        $this->assertCount(3, glob($this->dir . '/copies/*', GLOB_ONLYDIR) ?: []);
    }
}
