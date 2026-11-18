<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Jeton anti-CSRF : chaque formulaire envoie le jeton de la session, comparé en temps constant.
 */
final class Csrf
{
    public const FIELD = '_jeton';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get('_csrf');
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->set('_csrf', $token);
        }

        return $token;
    }

    public function isValid(string $submitted): bool
    {
        $token = $this->session->get('_csrf');

        return is_string($token) && $submitted !== '' && hash_equals($token, $submitted);
    }

    /**
     * Nouveau jeton après une connexion ou une déconnexion.
     */
    public function rotate(): void
    {
        $this->session->remove('_csrf');
    }
}
