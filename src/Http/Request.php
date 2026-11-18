<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Requête HTTP en lecture seule, construite depuis les variables globales de PHP
 * ou à la main dans les tests.
 */
final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     * @param array<string, mixed> $cookies
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $files = [],
        private readonly array $server = [],
        private readonly array $cookies = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            is_string($path) && $path !== '' ? rawurldecode($path) : '/',
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
            $_COOKIE,
        );
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Paramètre de l'adresse, toujours une chaîne : un tableau envoyé à la place est ignoré.
     */
    public function query(string $name, string $default = ''): string
    {
        $value = $this->query[$name] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    /**
     * Champ de formulaire, toujours une chaîne.
     */
    public function input(string $name, string $default = ''): string
    {
        $value = $this->post[$name] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    /**
     * Champ de formulaire envoyé tel quel (mots de passe : les espaces comptent).
     */
    public function raw(string $name): string
    {
        $value = $this->post[$name] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * Cases à cocher multiples (name="x[]").
     *
     * @return list<string>
     */
    public function inputList(string $name): array
    {
        $value = $this->post[$name] ?? [];

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function file(string $name): ?array
    {
        $file = $this->files[$name] ?? null;

        return is_array($file) && is_string($file['tmp_name'] ?? null) ? $file : null;
    }

    public function cookie(string $name): string
    {
        $value = $this->cookies[$name] ?? '';

        return is_string($value) ? $value : '';
    }

    public function isHttps(): bool
    {
        $https = $this->server['HTTPS'] ?? '';

        return $https !== '' && strtolower((string) $https) !== 'off';
    }

    public function clientIp(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '');
    }

    /**
     * Chemin et paramètres, pour revenir à la même page après la connexion.
     */
    public function pathWithQuery(): string
    {
        return $this->query === [] ? $this->path : $this->path . '?' . http_build_query($this->query);
    }
}
