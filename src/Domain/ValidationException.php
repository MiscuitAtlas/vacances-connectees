<?php

declare(strict_types=1);

namespace App\Domain;

use RuntimeException;

/**
 * Données refusées : les messages sont classés par champ du formulaire.
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }

    public static function field(string $field, string $message): self
    {
        return new self([$field => $message]);
    }
}
