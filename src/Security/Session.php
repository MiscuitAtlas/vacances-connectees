<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Session du visiteur : données, messages flash et protection contre la fixation de session.
 *
 * En production, les données sont celles de $_SESSION. Dans les tests, un simple tableau.
 */
final class Session
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @param array<string, mixed> $storage
     */
    public function __construct(array &$storage, private readonly bool $native = false)
    {
        $this->data = &$storage;
    }

    /**
     * Démarre la session PHP avec un cookie durci.
     */
    public static function start(bool $https): self
    {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('vc_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        return new self($_SESSION, true);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Nouvel identifiant de session, à chaque changement de droits (connexion, déconnexion).
     */
    public function regenerate(): void
    {
        if ($this->native && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $this->data['_regenerated'] = ($this->data['_regenerated'] ?? 0) + 1;
    }

    /**
     * Vide la session, en gardant les messages flash à afficher sur la page suivante.
     */
    public function clear(): void
    {
        $flashes = $this->data['_flash'] ?? [];
        foreach (array_keys($this->data) as $key) {
            unset($this->data[$key]);
        }
        $this->regenerate();
        if ($flashes !== []) {
            $this->data['_flash'] = $flashes;
        }
    }

    public function flash(string $type, string $message): void
    {
        $this->data['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * @return list<array{type: string, message: string}>
     */
    public function takeFlashes(): array
    {
        $flashes = $this->data['_flash'] ?? [];
        unset($this->data['_flash']);

        return $flashes;
    }
}
