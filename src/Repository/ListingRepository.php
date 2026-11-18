<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Catalog;
use PDO;

final class ListingRepository
{
    /**
     * Note moyenne et nombre d'avis, calculés pour chaque logement.
     */
    private const RATING_COLUMNS = '(SELECT AVG(a.note) FROM avis a WHERE a.logement_id = l.id) AS note_moyenne, '
        . '(SELECT COUNT(*) FROM avis a WHERE a.logement_id = l.id) AS nb_avis';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT l.*, u.nom AS proprietaire_nom, ' . self::RATING_COLUMNS
            . ' FROM logements l JOIN utilisateurs u ON u.id = l.proprietaire_id WHERE l.id = ?',
        );
        $statement->execute([$id]);
        $listing = $statement->fetch();

        return $listing ? self::hydrate($listing) : null;
    }

    /**
     * Recherche des logements disponibles. Toutes les valeurs passent par des paramètres liés.
     *
     * @param array{destination: string, arrivee: string, depart: string, voyageurs: int,
     *              chambres: int, prix_max: int|null, tri: string} $criteria
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function search(array $criteria, int $page, int $perPage): array
    {
        $where = ['l.capacite >= :voyageurs', 'l.chambres >= :chambres'];
        $params = [':voyageurs' => $criteria['voyageurs'], ':chambres' => $criteria['chambres']];

        if ($criteria['destination'] !== '') {
            // Les jokers de LIKE tapés par le visiteur sont neutralisés.
            $pattern = '%' . strtr($criteria['destination'], ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $where[] = "(l.ville LIKE :q1 ESCAPE '!' OR l.region LIKE :q2 ESCAPE '!' OR l.titre LIKE :q3 ESCAPE '!')";
            $params += [':q1' => $pattern, ':q2' => $pattern, ':q3' => $pattern];
        }
        if ($criteria['prix_max'] !== null) {
            $where[] = 'l.prix_nuit <= :prix_max';
            $params[':prix_max'] = $criteria['prix_max'];
        }
        if ($criteria['arrivee'] !== '' && $criteria['depart'] !== '') {
            $where[] = "NOT EXISTS (SELECT 1 FROM reservations r WHERE r.logement_id = l.id AND r.statut = 'confirmee'"
                . ' AND r.arrivee < :depart AND r.depart > :arrivee)';
            $params += [':arrivee' => $criteria['arrivee'], ':depart' => $criteria['depart']];
        }

        $conditions = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM logements l WHERE $conditions");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $order = match ($criteria['tri']) {
            'prix' => 'l.prix_nuit ASC, l.id ASC',
            'prix_desc' => 'l.prix_nuit DESC, l.id ASC',
            'recent' => 'l.id DESC',
            default => 'note_moyenne IS NULL, note_moyenne DESC, nb_avis DESC, l.id ASC',
        };
        $statement = $this->pdo->prepare(
            'SELECT l.*, ' . self::RATING_COLUMNS . " FROM logements l WHERE $conditions ORDER BY $order"
            . ' LIMIT :limite OFFSET :decalage',
        );
        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->bindValue(':limite', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':decalage', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();

        return ['items' => array_map(self::hydrate(...), $statement->fetchAll()), 'total' => $total];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function topRated(int $limit): array
    {
        $statement = $this->pdo->prepare(
            'SELECT l.*, ' . self::RATING_COLUMNS
            . ' FROM logements l ORDER BY note_moyenne IS NULL, note_moyenne DESC, nb_avis DESC, l.id ASC LIMIT :limite',
        );
        $statement->bindValue(':limite', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(self::hydrate(...), $statement->fetchAll());
    }

    /**
     * Régions proposées, avec le nombre de logements.
     *
     * @return list<array{region: string, total: int}>
     */
    public function regions(): array
    {
        $rows = $this->pdo->query(
            'SELECT region, COUNT(*) AS total FROM logements GROUP BY region ORDER BY total DESC, region ASC',
        )->fetchAll();

        return array_map(static fn (array $row): array => ['region' => (string) $row['region'], 'total' => (int) $row['total']], $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function byOwner(int $ownerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT l.*, ' . self::RATING_COLUMNS . ' FROM logements l WHERE l.proprietaire_id = ? ORDER BY l.id DESC',
        );
        $statement->execute([$ownerId]);

        return array_map(self::hydrate(...), $statement->fetchAll());
    }

    /**
     * @param array<string, mixed> $data Champs validés par ListingForm.
     */
    public function create(int $ownerId, array $data, string $now): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO logements (proprietaire_id, titre, type, cadre, ville, region, prix_nuit, chambres, capacite,'
            . ' description, equipements, cree_le) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        );
        $statement->execute([
            $ownerId, $data['titre'], $data['type'], $data['cadre'], $data['ville'], $data['region'],
            $data['prix_nuit'], $data['chambres'], $data['capacite'], $data['description'],
            implode(',', $data['equipements']), $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data Champs validés par ListingForm.
     */
    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE logements SET titre = ?, type = ?, cadre = ?, ville = ?, region = ?, prix_nuit = ?, chambres = ?,'
            . ' capacite = ?, description = ?, equipements = ? WHERE id = ?',
        );
        $statement->execute([
            $data['titre'], $data['type'], $data['cadre'], $data['ville'], $data['region'], $data['prix_nuit'],
            $data['chambres'], $data['capacite'], $data['description'], implode(',', $data['equipements']), $id,
        ]);
    }

    public function setPhoto(int $id, ?string $filename): void
    {
        $this->pdo->prepare('UPDATE logements SET photo = ? WHERE id = ?')->execute([$filename, $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM logements WHERE id = ?')->execute([$id]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrate(array $row): array
    {
        foreach (['id', 'proprietaire_id', 'prix_nuit', 'chambres', 'capacite', 'nb_avis'] as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = (int) $row[$key];
            }
        }
        $row['note_moyenne'] = isset($row['note_moyenne']) ? round((float) $row['note_moyenne'], 1) : null;
        $row['equipements'] = Catalog::amenityKeys((string) ($row['equipements'] ?? ''));

        return $row;
    }
}
