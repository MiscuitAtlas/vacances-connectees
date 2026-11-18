<?php

declare(strict_types=1);

namespace App\View;

use App\Domain\Catalog;
use App\Security\Csrf;
use IntlDateFormatter;
use NumberFormatter;
use RuntimeException;

/**
 * Rendu des gabarits PHP. Toute donnée affichée passe par e() : le HTML qu'elle contient
 * est affiché comme du texte, jamais interprété.
 */
final class View
{
    /**
     * @param array<string, mixed> $globals Données disponibles dans tous les gabarits.
     */
    public function __construct(
        private readonly string $templatesDir,
        private readonly string $publicDir,
        private readonly Csrf $csrf,
        private array $globals = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $data += $this->globals;
        $content = $this->include($template, $data);

        return $this->include('layout', $data + ['content' => $content]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function partial(string $template, array $data = []): string
    {
        return $this->include('partials/' . $template, $data + $this->globals);
    }

    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function csrfField(): string
    {
        return sprintf('<input type="hidden" name="%s" value="%s">', Csrf::FIELD, $this->e($this->csrf->token()));
    }

    /**
     * Adresse d'un fichier statique, suivie de sa date de modification pour que le navigateur
     * recharge les fichiers modifiés.
     */
    public function asset(string $path): string
    {
        $file = $this->publicDir . '/assets/' . $path;

        return '/assets/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
    }

    /**
     * @param array<string, scalar> $query
     */
    public function url(string $path, array $query = []): string
    {
        $query = array_filter($query, static fn ($value): bool => $value !== '' && $value !== null);

        return $path . ($query === [] ? '' : '?' . http_build_query($query));
    }

    public function money(int $euros): string
    {
        static $formatter = null;
        $formatter ??= new NumberFormatter('fr_FR', NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);

        return (string) $formatter->formatCurrency($euros, 'EUR');
    }

    /**
     * Date en toutes lettres : « 12 juillet 2026 », ou « samedi 12 juillet » avec un autre motif.
     */
    public function date(string $value, string $pattern = 'd MMMM y'): string
    {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Europe/Paris', null, $pattern);

        return (string) $formatter->format($timestamp);
    }

    /**
     * Attributs d'un champ en erreur : signalé aux lecteurs d'écran et relié à son message.
     *
     * @param array<string, string> $errors
     */
    public function invalid(string $field, array $errors): string
    {
        return isset($errors[$field]) ? sprintf(' aria-invalid="true" aria-describedby="erreur-%s"', $this->e($field)) : '';
    }

    /**
     * @param array<string, string> $errors
     */
    public function error(string $field, array $errors): string
    {
        return isset($errors[$field])
            ? sprintf('<p class="field-error" id="erreur-%s">%s</p>', $this->e($field), $this->e($errors[$field]))
            : '';
    }

    public function rating(?float $rating): string
    {
        return $rating === null ? '' : number_format($rating, 1, ',', '');
    }

    public function plural(int $count, string $singular, string $plural): string
    {
        return $count . "\u{00A0}" . ($count > 1 ? $plural : $singular);
    }

    /**
     * Illustration d'un logement.
     *
     * @param array<string, mixed> $listing
     */
    public function poster(array $listing, bool $described = false, bool $wide = false): string
    {
        $label = $described
            ? sprintf(
                'Illustration : %s, %s',
                mb_strtolower(Catalog::TYPES[$listing['type']] ?? 'logement'),
                mb_strtolower(Catalog::SETTINGS[$listing['cadre']] ?? ''),
            )
            : '';

        return Poster::render((string) $listing['type'], (string) $listing['cadre'], (int) $listing['id'], $label, $wide);
    }

    public function photoUrl(?string $photo): ?string
    {
        return $photo !== null && $photo !== '' ? '/uploads/logements/' . rawurlencode($photo) : null;
    }

    /**
     * La page affichée fait-elle partie de cette rubrique du menu ?
     */
    public function isCurrent(string $prefix): bool
    {
        $path = (string) ($this->globals['path'] ?? '');

        return $prefix === '/' ? $path === '/' : str_starts_with($path, $prefix);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function include(string $template, array $data): string
    {
        $file = $this->templatesDir . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Gabarit introuvable : $template");
        }
        $render = function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            try {
                include $__file;
            } catch (\Throwable $error) {
                ob_end_clean();
                throw $error;
            }

            return (string) ob_get_clean();
        };

        return $render($file, $data);
    }
}
