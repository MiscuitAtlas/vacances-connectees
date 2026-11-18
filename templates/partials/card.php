<?php
/**
 * Carte d'un logement dans une liste.
 *
 * @var \App\View\View $this
 * @var array<string, mixed> $listing
 * @var int|null $nights Nuits du séjour recherché, pour afficher le prix total.
 * @var array<string, scalar> $stayQuery Dates et voyageurs transmis à la fiche.
 */
use App\Domain\Catalog;

$nights ??= null;
$stayQuery ??= [];
$photo = $this->photoUrl($listing['photo'] ?? null);
?>
<article class="card">
    <div class="card-art">
        <?php if ($photo !== null) : ?>
            <img src="<?= $this->e($photo) ?>" alt="" loading="lazy" width="400" height="300">
        <?php else : ?>
            <?= $this->poster($listing) ?>
        <?php endif; ?>
        <p class="card-city"><?= $this->e($listing['ville']) ?></p>
    </div>
    <div class="card-body">
        <h3 class="card-title">
            <a href="<?= $this->e($this->url('/logements/' . $listing['id'], $stayQuery)) ?>"><?= $this->e($listing['titre']) ?></a>
        </h3>
        <p class="card-facts">
            <span><?= $this->e(Catalog::TYPES[$listing['type']] ?? '') ?></span>
            <span><?= $this->e($this->plural($listing['capacite'], 'voyageur', 'voyageurs')) ?></span>
            <span><?= $this->e($listing['region']) ?></span>
        </p>
        <div class="card-foot">
            <p class="price">
                <?php if ($nights !== null) : ?>
                    <strong><?= $this->e($this->money($nights * $listing['prix_nuit'])) ?></strong>
                    <span><?= $this->e($this->plural($nights, 'nuit', 'nuits')) ?></span>
                <?php else : ?>
                    <strong><?= $this->e($this->money($listing['prix_nuit'])) ?></strong> <span>la nuit</span>
                <?php endif; ?>
            </p>
            <?php if ($listing['note_moyenne'] !== null) : ?>
                <p class="rating">
                    <span aria-hidden="true">★ <?= $this->e($this->rating($listing['note_moyenne'])) ?></span>
                    <span class="muted" aria-hidden="true">(<?= (int) $listing['nb_avis'] ?>)</span>
                    <span class="sr-only"><?= $this->e(sprintf('Note moyenne %s sur 5, %s', $this->rating($listing['note_moyenne']), $this->plural($listing['nb_avis'], 'avis', 'avis'))) ?></span>
                </p>
            <?php else : ?>
                <p class="rating muted">Nouveau</p>
            <?php endif; ?>
        </div>
    </div>
</article>
