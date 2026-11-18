<?php

declare(strict_types=1);

namespace App\Demo;

use DateTimeImmutable;

/**
 * Emails de la démo : au lieu de partir, ils sont écrits dans le dossier de la copie du visiteur,
 * et cette classe les relit pour les afficher.
 */
final class Mailbox
{
    /**
     * @return list<array{to: string, subject: string, date: DateTimeImmutable|null, parts: list<array{text: string, href: string|null}>}>
     */
    public static function read(string $dir, string $siteUrl): array
    {
        $files = glob($dir . '/*.eml') ?: [];
        rsort($files);

        return array_map(static fn (string $file): array => self::parse($file, $siteUrl), $files);
    }

    public static function count(string $dir): int
    {
        return count(glob($dir . '/*.eml') ?: []);
    }

    /**
     * @return array{to: string, subject: string, date: DateTimeImmutable|null, parts: list<array{text: string, href: string|null}>}
     */
    private static function parse(string $file, string $siteUrl): array
    {
        $content = str_replace("\r\n", "\n", (string) file_get_contents($file));
        [$head, $body] = array_pad(explode("\n\n", $content, 2), 2, '');
        // En-têtes repliés sur plusieurs lignes : une ligne qui commence par un espace continue la précédente.
        $head = (string) preg_replace('/\n[ \t]+/', ' ', $head);

        $headers = [];
        foreach (explode("\n", $head) as $line) {
            [$name, $value] = array_pad(explode(':', $line, 2), 2, '');
            $headers[strtolower(trim($name))] = trim($value);
        }
        $date = preg_match('/^(\d{8}-\d{6})/', basename($file), $match) === 1
            ? DateTimeImmutable::createFromFormat('Ymd-His', $match[1]) ?: null
            : null;

        return [
            'to' => $headers['to'] ?? '',
            'subject' => mb_decode_mimeheader($headers['subject'] ?? ''),
            'date' => $date,
            'parts' => self::links($body, $siteUrl),
        ];
    }

    /**
     * Découpe le texte pour rendre cliquables les liens vers le site. Le lien pointe vers le chemin
     * seul, pour fonctionner quelle que soit l'adresse sous laquelle la démo est publiée.
     *
     * @return list<array{text: string, href: string|null}>
     */
    private static function links(string $body, string $siteUrl): array
    {
        $parts = [];
        foreach (preg_split('#(https?://\S+)#', $body, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [] as $chunk) {
            $href = null;
            if ($siteUrl !== '' && str_starts_with($chunk, $siteUrl . '/')) {
                $href = substr($chunk, strlen($siteUrl));
            }
            $parts[] = ['text' => $chunk, 'href' => $href];
        }

        return $parts;
    }
}
