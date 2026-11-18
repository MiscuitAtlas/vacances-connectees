<?php
/**
 * @var \App\View\View $this
 * @var list<array<string, mixed>> $listings
 * @var list<array<string, mixed>> $bookings
 */
?>
<div class="container page">
    <div class="page-head">
        <h1>Mes logements</h1>
        <a class="button" href="/logements/nouveau">Proposer un logement</a>
    </div>

    <section class="section-tight" aria-labelledby="titre-reservations-recues">
        <h2 id="titre-reservations-recues">Réservations à venir</h2>
        <?php if ($bookings === []) : ?>
            <p class="muted">Aucune réservation à venir sur vos logements.</p>
        <?php else : ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Logement</th>
                            <th scope="col">Voyageur</th>
                            <th scope="col">Séjour</th>
                            <th scope="col" class="num">Voyageurs</th>
                            <th scope="col" class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking) : ?>
                            <tr>
                                <th scope="row"><a href="/reservations/<?= (int) $booking['id'] ?>"><?= $this->e($booking['titre']) ?></a></th>
                                <td><?= $this->e($booking['voyageur_nom']) ?></td>
                                <td class="nowrap"><?= $this->e($this->date($booking['arrivee'], 'd MMM')) ?> → <?= $this->e($this->date($booking['depart'], 'd MMM y')) ?></td>
                                <td class="num"><?= (int) $booking['voyageurs'] ?></td>
                                <td class="num nowrap"><?= $this->e($this->money($booking['prix_total'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-tight" aria-labelledby="titre-annonces">
        <h2 id="titre-annonces">Mes annonces</h2>
        <?php if ($listings === []) : ?>
            <div class="empty panel">
                <p>Vous n'avez pas encore d'annonce.</p>
                <p><a class="button" href="/logements/nouveau">Publier mon premier logement</a></p>
            </div>
        <?php else : ?>
            <ul class="host-listings">
                <?php foreach ($listings as $listing) : ?>
                    <li class="host-listing panel">
                        <div class="trip-art">
                            <?php $photo = $this->photoUrl($listing['photo']); ?>
                            <?php if ($photo !== null) : ?>
                                <img src="<?= $this->e($photo) ?>" alt="" width="400" height="300">
                            <?php else : ?>
                                <?= $this->poster($listing) ?>
                            <?php endif; ?>
                        </div>
                        <div class="host-listing-body">
                            <h3><a href="/logements/<?= $listing['id'] ?>"><?= $this->e($listing['titre']) ?></a></h3>
                            <p class="muted"><?= $this->e($listing['ville']) ?>, <?= $this->e($this->money($listing['prix_nuit'])) ?> la nuit<?= $listing['note_moyenne'] !== null ? ', note ' . $this->e($this->rating($listing['note_moyenne'])) . ' sur 5' : '' ?></p>
                            <div class="actions">
                                <a class="button small secondary" href="/logements/<?= $listing['id'] ?>/modifier">Modifier<span class="sr-only"> « <?= $this->e($listing['titre']) ?> »</span></a>
                                <form method="post" action="/logements/<?= $listing['id'] ?>/supprimer" data-confirm="<?= $this->e('Supprimer l\'annonce « ' . $listing['titre'] . ' » ? Ses avis seront supprimés aussi.') ?>">
                                    <?= $this->csrfField() ?>
                                    <button type="submit" class="button small danger">Supprimer<span class="sr-only"> « <?= $this->e($listing['titre']) ?> »</span></button>
                                </form>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
