<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Listes fermées utilisées par les formulaires, la recherche et les illustrations.
 */
final class Catalog
{
    public const TYPES = [
        'appartement' => 'Appartement',
        'maison' => 'Maison',
        'chalet' => 'Chalet',
        'villa' => 'Villa',
        'cabane' => 'Cabane',
    ];

    public const SETTINGS = [
        'mer' => 'Bord de mer',
        'montagne' => 'Montagne',
        'campagne' => 'Campagne',
        'lac' => 'Bord de lac',
        'ville' => 'En ville',
    ];

    public const AMENITIES = [
        'wifi' => 'Wi-Fi',
        'cuisine' => 'Cuisine équipée',
        'parking' => 'Parking',
        'lave_linge' => 'Lave-linge',
        'climatisation' => 'Climatisation',
        'cheminee' => 'Cheminée',
        'jardin' => 'Jardin ou terrasse',
        'piscine' => 'Piscine',
        'animaux' => 'Animaux acceptés',
    ];

    public const SORTS = [
        'note' => 'Les mieux notés',
        'prix' => 'Prix croissant',
        'prix_desc' => 'Prix décroissant',
        'recent' => 'Les plus récents',
    ];

    /**
     * @return list<string>
     */
    public static function amenityKeys(string $stored): array
    {
        $keys = array_filter(explode(',', $stored), static fn (string $key): bool => isset(self::AMENITIES[$key]));

        return array_values($keys);
    }
}
