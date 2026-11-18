<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;

/**
 * Utilisateur connecté. L'identité vient toujours de la session, jamais d'un champ de formulaire.
 */
final class Auth
{
    /**
     * @var array<string, mixed>|null|false false : pas encore chargé.
     */
    private array|null|false $user = false;

    public function __construct(
        private readonly Session $session,
        private readonly Csrf $csrf,
        private readonly UserRepository $users,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        if ($this->user === false) {
            $id = $this->session->get('user_id');
            $this->user = is_int($id) ? $this->users->find($id) : null;
            if ($this->user === null && $id !== null) {
                // Compte supprimé entre-temps : la session ne vaut plus rien.
                $this->session->remove('user_id');
            }
        }

        return $this->user;
    }

    public function id(): ?int
    {
        $user = $this->user();

        return $user === null ? null : (int) $user['id'];
    }

    /**
     * @param array<string, mixed> $user
     */
    public function login(array $user): void
    {
        // Nouvel identifiant de session : un identifiant connu d'un attaquant avant la connexion ne sert plus.
        $this->session->regenerate();
        $this->csrf->rotate();
        $this->session->set('user_id', (int) $user['id']);
        $this->user = $user;
    }

    public function logout(): void
    {
        $this->session->clear();
        $this->user = null;
    }

    public function refresh(): void
    {
        $this->user = false;
    }
}
