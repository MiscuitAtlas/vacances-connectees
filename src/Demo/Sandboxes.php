<?php

declare(strict_types=1);

namespace App\Demo;

use App\Database;
use DateTimeImmutable;
use RuntimeException;

/**
 * Démo en ligne : chaque visiteur travaille sur sa propre copie de la base SQLite.
 *
 * Tant qu'il ne fait que consulter le site, le visiteur lit une base modèle commune, ouverte
 * en lecture seule. Au premier formulaire envoyé, il reçoit une copie de ce modèle, désignée
 * par un cookie et supprimée au bout de deux heures. Ce qu'un visiteur fait n'est jamais
 * visible des autres.
 */
final class Sandboxes
{
    public const COOKIE = 'vc_demo';

    public function __construct(
        private readonly string $dir,
        private readonly int $lifetime,
        private readonly int $maxCopies,
    ) {
    }

    /**
     * Base modèle du jour. Elle est refaite chaque jour pour que les séjours de démonstration
     * restent placés par rapport à la date du jour : à venir, passés, avis possibles.
     */
    public function template(DateTimeImmutable $now): string
    {
        $file = sprintf('%s/modele-%s.sqlite', $this->dir, $now->format('Y-m-d'));
        if (!is_file($file)) {
            $this->buildTemplate($file, $now);
        }

        return $file;
    }

    /**
     * Identifiant de la copie si elle existe encore, sinon null.
     */
    public function find(string $id, DateTimeImmutable $now): ?string
    {
        $created = self::createdAt($id);
        if ($created === null || $created + $this->lifetime <= $now->getTimestamp()) {
            return null;
        }

        return is_file($this->database($id)) ? $id : null;
    }

    /**
     * Nouvelle copie du modèle. Les copies expirées sont supprimées au passage, et les plus
     * anciennes aussi quand le nombre maximum est atteint.
     */
    public function create(DateTimeImmutable $now): string
    {
        $this->purge($now);
        $template = $this->template($now);
        $id = $now->getTimestamp() . '-' . bin2hex(random_bytes(16));
        if (!mkdir($this->mailDir($id), 0775, true) || !copy($template, $this->database($id))) {
            throw new RuntimeException('Impossible de créer la copie de démonstration.');
        }

        return $id;
    }

    public function database(string $id): string
    {
        return $this->copyDir($id) . '/base.sqlite';
    }

    public function mailDir(string $id): string
    {
        return $this->copyDir($id) . '/mail';
    }

    public function expiresAt(string $id): DateTimeImmutable
    {
        return (new DateTimeImmutable())->setTimestamp((int) self::createdAt($id) + $this->lifetime);
    }

    private function copyDir(string $id): string
    {
        return $this->dir . '/copies/' . $id;
    }

    /**
     * L'identifiant commence par la date de création, suivie de 128 bits aléatoires.
     */
    private static function createdAt(string $id): ?int
    {
        return preg_match('/^(\d{10})-[a-f0-9]{32}$/', $id, $match) === 1 ? (int) $match[1] : null;
    }

    private function purge(DateTimeImmutable $now): void
    {
        $copies = glob($this->dir . '/copies/*', GLOB_ONLYDIR) ?: [];
        // Les dates de création ont toutes 10 chiffres : l'ordre alphabétique est l'ordre de création.
        sort($copies);
        $kept = [];
        foreach ($copies as $dir) {
            $created = self::createdAt(basename($dir));
            if ($created === null || $created + $this->lifetime <= $now->getTimestamp()) {
                self::remove($dir);
            } else {
                $kept[] = $dir;
            }
        }
        while (count($kept) >= $this->maxCopies) {
            self::remove((string) array_shift($kept));
        }
    }

    private function buildTemplate(string $file, DateTimeImmutable $now): void
    {
        if (!is_dir($this->dir) && !mkdir($this->dir, 0775, true) && !is_dir($this->dir)) {
            throw new RuntimeException('Dossier de la démo impossible à créer.');
        }
        // Plusieurs requêtes peuvent arriver en même temps : une seule construit le modèle.
        $lock = fopen($this->dir . '/.verrou', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('Verrou de la démo indisponible.');
        }
        try {
            if (is_file($file)) {
                return;
            }
            $temporary = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
            $pdo = Database::connect(['dsn' => 'sqlite:' . $temporary]);
            Database::createSchema($pdo);
            DemoData::load($pdo, $now->setTime(0, 0));
            unset($pdo);
            rename($temporary, $file);

            foreach (glob($this->dir . '/modele-*.sqlite') ?: [] as $old) {
                if ($old !== $file) {
                    unlink($old);
                }
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Supprime une copie : sa base (avec un éventuel journal SQLite) et ses emails.
     */
    private static function remove(string $dir): void
    {
        foreach ([...(glob($dir . '/mail/*') ?: []), ...(glob($dir . '/*.sqlite*') ?: [])] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        foreach ([$dir . '/mail', $dir] as $path) {
            if (is_dir($path)) {
                rmdir($path);
            }
        }
    }
}
