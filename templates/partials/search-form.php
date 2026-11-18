<?php
/**
 * Recherche rapide : destination, dates, voyageurs.
 *
 * @var \App\View\View $this
 * @var array<string, mixed> $search
 * @var string $today
 */
?>
<form class="quick-search" method="get" action="/recherche" role="search" aria-label="Rechercher un logement">
    <div class="field">
        <label for="qs-destination">Destination</label>
        <input id="qs-destination" name="destination" type="search" placeholder="Ville ou région" value="<?= $this->e($search['destination']) ?>" autocomplete="off">
    </div>
    <div class="field">
        <label for="qs-arrivee">Arrivée</label>
        <input id="qs-arrivee" name="arrivee" type="date" min="<?= $this->e($today) ?>" value="<?= $this->e($search['arrivee']) ?>" data-pair="qs-depart">
    </div>
    <div class="field">
        <label for="qs-depart">Départ</label>
        <input id="qs-depart" name="depart" type="date" min="<?= $this->e($today) ?>" value="<?= $this->e($search['depart']) ?>">
    </div>
    <div class="field field-small">
        <label for="qs-voyageurs">Voyageurs</label>
        <input id="qs-voyageurs" name="voyageurs" type="number" min="1" max="30" value="<?= (int) $search['voyageurs'] ?>">
    </div>
    <button type="submit" class="button">Rechercher</button>
</form>
