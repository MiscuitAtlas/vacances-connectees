<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Catalog;
use App\Domain\ValidationException;
use App\Http\Request;

/**
 * Validation du formulaire d'annonce (création et modification).
 */
final class ListingForm
{
    /**
     * Valeurs du formulaire, telles que saisies, pour le réafficher.
     *
     * @return array<string, mixed>
     */
    public static function values(Request $request): array
    {
        return [
            'titre' => $request->input('titre'),
            'type' => $request->input('type'),
            'cadre' => $request->input('cadre'),
            'ville' => $request->input('ville'),
            'region' => $request->input('region'),
            'prix_nuit' => $request->input('prix_nuit'),
            'chambres' => $request->input('chambres'),
            'capacite' => $request->input('capacite'),
            'description' => $request->input('description'),
            'equipements' => $request->inputList('equipements'),
        ];
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed> Données prêtes à enregistrer.
     * @throws ValidationException
     */
    public static function validate(array $values): array
    {
        $errors = [];
        $text = static function (string $field, int $min, int $max, string $label) use ($values, &$errors): string {
            $value = preg_replace('/\s+/u', ' ', (string) $values[$field]) ?? '';
            $length = mb_strlen($value);
            if ($length < $min || $length > $max) {
                $errors[$field] = sprintf('%s : entre %d et %d caractères.', $label, $min, $max);
            }

            return $value;
        };
        $number = static function (string $field, int $min, int $max, string $label) use ($values, &$errors): int {
            $value = filter_var($values[$field], FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
            if ($value === false) {
                $errors[$field] = sprintf('%s : un nombre entier de %d à %d.', $label, $min, $max);

                return 0;
            }

            return $value;
        };

        $data = [
            'titre' => $text('titre', 5, 120, 'Titre'),
            'ville' => $text('ville', 2, 100, 'Ville'),
            'region' => $text('region', 2, 100, 'Région'),
            'prix_nuit' => $number('prix_nuit', 10, 5000, 'Prix par nuit'),
            'chambres' => $number('chambres', 1, 20, 'Chambres'),
            'capacite' => $number('capacite', 1, 30, 'Voyageurs'),
            'type' => (string) $values['type'],
            'cadre' => (string) $values['cadre'],
            'equipements' => array_values(array_unique(array_intersect((array) $values['equipements'], array_keys(Catalog::AMENITIES)))),
        ];
        // La description garde ses retours à la ligne.
        $description = trim(str_replace("\r\n", "\n", (string) $values['description']));
        if (mb_strlen($description) < 30 || mb_strlen($description) > 3000) {
            $errors['description'] = 'Description : entre 30 et 3000 caractères.';
        }
        $data['description'] = $description;

        if (!isset(Catalog::TYPES[$data['type']])) {
            $errors['type'] = 'Choisissez le type de logement.';
        }
        if (!isset(Catalog::SETTINGS[$data['cadre']])) {
            $errors['cadre'] = 'Choisissez le cadre du logement.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $data;
    }
}
