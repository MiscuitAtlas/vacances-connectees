<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\ValidationException;
use GdImage;

/**
 * Photos des logements. Un fichier envoyé n'est jamais conservé tel quel : l'image est décodée,
 * redimensionnée et réenregistrée en WebP sous un nom aléatoire. Un script déguisé en image
 * est refusé, et les métadonnées (dont la position GPS de l'appareil photo) disparaissent.
 */
final class PhotoStorage
{
    private const MAX_WIDTH = 1600;
    private const MAX_PIXELS = 40_000_000;
    private const ACCEPTED = [
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG => 'imagecreatefrompng',
        IMAGETYPE_WEBP => 'imagecreatefromwebp',
    ];

    public function __construct(private readonly string $dir, private readonly int $maxBytes)
    {
    }

    /**
     * @param array<string, mixed> $file Entrée de $_FILES.
     * @return string Nom du fichier enregistré.
     * @throws ValidationException
     */
    public function store(array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || (int) ($file['size'] ?? 0) > $this->maxBytes) {
            throw ValidationException::field('photo', sprintf('La photo dépasse %d Mo.', intdiv($this->maxBytes, 1024 * 1024)));
        }
        if ($error !== UPLOAD_ERR_OK || !is_file((string) $file['tmp_name'])) {
            throw ValidationException::field('photo', 'La photo n\'a pas pu être envoyée.');
        }

        $path = (string) $file['tmp_name'];
        $info = @getimagesize($path);
        if ($info === false || !isset(self::ACCEPTED[$info[2]])) {
            throw ValidationException::field('photo', 'Envoyez une image JPEG, PNG ou WebP.');
        }
        [$width, $height] = $info;
        if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS) {
            throw ValidationException::field('photo', 'Les dimensions de cette image ne sont pas acceptées.');
        }

        $source = @(self::ACCEPTED[$info[2]])($path);
        if (!$source instanceof GdImage) {
            throw ValidationException::field('photo', 'Cette image est illisible.');
        }
        $image = $width > self::MAX_WIDTH ? imagescale($source, self::MAX_WIDTH) : $source;
        if (!$image instanceof GdImage) {
            throw ValidationException::field('photo', 'Cette image est illisible.');
        }

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
        $name = bin2hex(random_bytes(16)) . '.webp';
        if (!imagewebp($image, $this->dir . '/' . $name, 82)) {
            throw ValidationException::field('photo', 'La photo n\'a pas pu être enregistrée.');
        }

        return $name;
    }

    public function delete(?string $name): void
    {
        // Seuls les noms produits par store() sont acceptés : pas de chemin vers un autre fichier.
        if ($name !== null && preg_match('/^[a-f0-9]{32}\.webp$/', $name) === 1 && is_file($this->dir . '/' . $name)) {
            unlink($this->dir . '/' . $name);
        }
    }
}
