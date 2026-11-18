<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Règles communes aux mots de passe : inscription, changement et réinitialisation.
 */
final class Passwords
{
    public const MIN_LENGTH = 10;
    // bcrypt ignore ce qui dépasse 72 octets : un mot de passe plus long serait tronqué sans le dire.
    public const MAX_BYTES = 72;

    /**
     * @return string|null Message d'erreur, ou null si le mot de passe convient.
     */
    public static function problem(string $password, string $confirmation, string $email = ''): ?string
    {
        if (mb_strlen($password) < self::MIN_LENGTH) {
            return sprintf('Le mot de passe doit contenir au moins %d caractères.', self::MIN_LENGTH);
        }
        if (strlen($password) > self::MAX_BYTES) {
            return 'Le mot de passe est trop long.';
        }
        if ($email !== '' && mb_strtolower($password) === mb_strtolower($email)) {
            return 'Le mot de passe ne doit pas être votre adresse email.';
        }
        if (!hash_equals($password, $confirmation)) {
            return 'Les deux mots de passe ne correspondent pas.';
        }

        return null;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
