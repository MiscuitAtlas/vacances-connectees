<?php
/**
 * @var \App\View\View $this
 * @var array<string, mixed> $search
 * @var array<string, string> $errors
 * @var int|null $nights
 * @var list<array<string, mixed>> $listings
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var string $today
 */
use App\Domain\Catalog;

$stayQuery = $nights !== null ? ['arrivee' => $search['arrivee'], 'depart' => $search['depart'], 'voyageurs' => $search['voyageurs']] : [];
$baseQuery = array_filter([
    'destination' => $search['destination'], 'arrivee' => $search['arrivee'], 'depart' => $search['depart'],
    'voyageurs' => $search['voyageurs'] > 1 ? $search['voyageurs'] : '', 'chambres' => $search['chambres'] ?: '',
    'prix_max' => $search['prix_max'] ?? '', 'tri' => $search['tri'] !== 'note' ? $search['tri'] : '',
], static fn ($value): bool => $value !== '' && $value !== null);

// Résumé de la recherche, affiché sur téléphone quand les filtres sont repliés.
$summary = array_filter([
    $search['destination'],
    $nights !== null ? $this->date($search['arrivee'], 'd MMM') . ' → ' . $this->date($search['depart'], 'd MMM') : '',
    $this->plural((int) $search['voyageurs'], 'voyageur', 'voyageurs'),
]);
$collapsed = $errors === [] && ($nights !== null || $search['destination'] !== '');
?>
<div class="container page search-page">
    <h1>Rechercher un logement</h1>
    <div class="search-layout">
        <details class="filters" open data-collapsible<?= $collapsed ? ' data-collapsed' : '' ?>>
        <summary class="filters-summary">
            <span>Modifier la recherche</span>
            <span class="muted"><?= $this->e(implode(', ', $summary)) ?></span>
        </summary>
        <form class="filters-form panel" method="get" action="/recherche" role="search" aria-label="Filtres de recherche">
            <div class="field">
                <label for="destination">Destination</label>
                <input id="destination" name="destination" type="search" placeholder="Ville ou région" value="<?= $this->e($search['destination']) ?>">
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="arrivee">Arrivée</label>
                    <input id="arrivee" name="arrivee" type="date" min="<?= $this->e($today) ?>" value="<?= $this->e($search['arrivee']) ?>" data-pair="depart"<?= $this->invalid('arrivee', $errors) ?>>
                    <?= $this->error('arrivee', $errors) ?>
                </div>
                <div class="field">
                    <label for="depart">Départ</label>
                    <input id="depart" name="depart" type="date" min="<?= $this->e($today) ?>" value="<?= $this->e($search['depart']) ?>"<?= $this->invalid('depart', $errors) ?>>
                    <?= $this->error('depart', $errors) ?>
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="voyageurs">Voyageurs</label>
                    <input id="voyageurs" name="voyageurs" type="number" min="1" max="30" value="<?= (int) $search['voyageurs'] ?>">
                </div>
                <div class="field">
                    <label for="chambres">Chambres</label>
                    <select id="chambres" name="chambres">
                        <option value="0">Indifférent</option>
                        <?php foreach ([1, 2, 3, 4] as $rooms) : ?>
                            <option value="<?= $rooms ?>"<?= $search['chambres'] === $rooms ? ' selected' : '' ?>><?= $rooms ?> ou plus</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="prix_max">Prix maximum par nuit (€)</label>
                <input id="prix_max" name="prix_max" type="number" min="10" max="5000" step="5" value="<?= $this->e($search['prix_max'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="tri">Trier par</label>
                <select id="tri" name="tri">
                    <?php foreach (Catalog::SORTS as $key => $label) : ?>
                        <option value="<?= $this->e($key) ?>"<?= $search['tri'] === $key ? ' selected' : '' ?>><?= $this->e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="button">Rechercher</button>
        </form>
        </details>

        <section class="results" aria-labelledby="titre-resultats">
            <h2 id="titre-resultats" class="results-summary" aria-live="polite">
                <?= $this->e($this->plural($total, $nights !== null ? 'logement disponible' : 'logement', $nights !== null ? 'logements disponibles' : 'logements')) ?>
                <?php if ($nights !== null) : ?>
                    <span class="muted">du <?= $this->e($this->date($search['arrivee'], 'd MMMM')) ?> au <?= $this->e($this->date($search['depart'], 'd MMMM y')) ?></span>
                <?php endif; ?>
            </h2>
            <?php if ($listings === []) : ?>
                <div class="empty panel">
                    <p>Aucun logement ne correspond à ces critères.</p>
                    <p class="muted">Essayez d'autres dates, une destination plus large ou un prix plus élevé.</p>
                </div>
            <?php else : ?>
                <div class="grid grid-results">
                    <?php foreach ($listings as $listing) : ?>
                        <?= $this->partial('card', ['listing' => $listing, 'nights' => $nights, 'stayQuery' => $stayQuery]) ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($pages > 1) : ?>
                    <nav class="pagination" aria-label="Pages de résultats">
                        <?php if ($page > 1) : ?>
                            <a href="<?= $this->e($this->url('/recherche', $baseQuery + ['page' => $page - 1])) ?>" rel="prev">Page précédente</a>
                        <?php endif; ?>
                        <span>Page <?= $page ?> sur <?= $pages ?></span>
                        <?php if ($page < $pages) : ?>
                            <a href="<?= $this->e($this->url('/recherche', $baseQuery + ['page' => $page + 1])) ?>" rel="next">Page suivante</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</div>
