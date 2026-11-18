<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

/**
 * Envoi des emails. En développement, ils sont écrits dans var/mail au lieu d'être envoyés.
 */
final class Mailer
{
    public function __construct(
        private readonly string $transport,
        private readonly string $from,
        private readonly string $logDir,
    ) {
    }

    public function send(string $to, string $subject, string $body): void
    {
        // Pas de retour à la ligne dans les en-têtes : impossible d'y glisser un destinataire caché.
        if (preg_match('/[\r\n]/', $to . $subject) === 1) {
            throw new RuntimeException('En-tête d\'email invalide.');
        }
        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');
        $headers = [
            'From: ' . self::encodeAddress($this->from),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        if ($this->transport === 'php') {
            if (!mail($to, $encodedSubject, $body, implode("\r\n", $headers))) {
                throw new RuntimeException('L\'email n\'a pas pu être envoyé.');
            }

            return;
        }

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0775, true);
        }
        $message = implode("\n", ['To: ' . $to, 'Subject: ' . $encodedSubject, ...$headers]) . "\n\n" . $body;
        file_put_contents(sprintf('%s/%s-%s.eml', $this->logDir, date('Ymd-His'), bin2hex(random_bytes(3))), $message);
    }

    /**
     * « Nom <adresse> » : le nom peut contenir des accents, il est encodé pour l'en-tête.
     */
    private static function encodeAddress(string $address): string
    {
        if (preg_match('/^(.+?)\s*<([^>]+)>$/', $address, $match) !== 1) {
            return $address;
        }

        return mb_encode_mimeheader($match[1], 'UTF-8') . ' <' . $match[2] . '>';
    }
}
