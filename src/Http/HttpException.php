<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

/**
 * Erreur affichée au visiteur avec son code HTTP : 400, 403, 404, 405.
 */
final class HttpException extends RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message !== '' ? $message : self::defaultMessage($status), $status);
    }

    public static function notFound(): self
    {
        return new self(404);
    }

    public static function forbidden(): self
    {
        return new self(403);
    }

    private static function defaultMessage(int $status): string
    {
        return match ($status) {
            400 => 'La demande n\'a pas pu être traitée.',
            403 => 'Vous n\'avez pas accès à cette page.',
            404 => 'Cette page n\'existe pas.',
            405 => 'Cette action n\'est pas disponible à cette adresse.',
            default => 'Une erreur est survenue.',
        };
    }
}
