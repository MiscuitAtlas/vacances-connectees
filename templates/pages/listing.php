<?php
/**
 * @var \App\View\View $this
 * @var array<string, mixed> $listing
 * @var list<array<string, mixed>> $reviews
 * @var array<string, string> $values
 * @var array<string, string> $errors
 * @var array{nights: int, total: int}|null $quote
 * @var bool $isOwner
 * @var list<array{month: string, weeks: list<list<array{day: int, date: string, state: string}|null>>}> $months
 * @var string|null $previousMonth
 * @var string $nextMonth
 * @var string $today
 * @var string $maxDate
 */
use App\Domain\Catalog;

$photo = $this->photoUrl($listing['photo']);
$stayQuery = array_filter(['arrivee' => $values['arrivee'], 'depart' => $values['depart'], 'voyageurs' => $values['voyageurs']]);
$days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
?>
<div class="container page listing-page">
    <p class="back"><a href="/recherche">Retour à la recherche</a></p>
    <header class="listing-head">
        <h1><?= $this->e($listing['titre']) ?></h1>
        <p class="listing-place">
            <?= $this->e($listing['ville']) ?>, <?= $this->e($listing['region']) ?>
            <?php if ($listing['note_moyenne'] !== null) : ?>
                <a class="rating" href="#avis">★ <?= $this->e($this->rating($listing['note_moyenne'])) ?> <span class="muted">(<?= $this->e($this->plural($listing['nb_avis'], 'avis', 'avis')) ?>)</span></a>
            <?php endif; ?>
        </p>
    </header>

    <div class="listing-layout">
        <figure class="listing-art">
            <?php if ($photo !== null) : ?>
                <img src="<?= $this->e($photo) ?>" alt="<?= $this->e('Photo du logement : ' . $listing['titre']) ?>" width="1600" height="1200">
            <?php else : ?>
                <?= $this->poster($listing, true, true) ?>
            <?php endif; ?>
            <figcaption class="card-city"><?= $this->e($listing['ville']) ?></figcaption>
        </figure>
        <aside class="booking-card panel" aria-labelledby="titre-reserver">
            <h2 id="titre-reserver" class="booking-price"><?= $this->e($this->money($listing['prix_nuit'])) ?> <span>la nuit</span></h2>
            <?php if ($isOwner) : ?>
                <p>C'est votre logement : les réservations des voyageurs apparaissent dans votre espace.</p>
                <p><a class="button secondary" href="/logements/<?= $listing['id'] ?>/modifier">Modifier l'annonce</a></p>
                <p><a href="/mes-logements">Voir les réservations</a></p>
            <?php else : ?>
                <form method="post" action="/logements/<?= $listing['id'] ?>/reserver" class="booking-form" data-price="<?= (int) $listing['prix_nuit'] ?>" novalidate>
                    <?= $this->csrfField() ?>
                    <div class="field-row">
                        <div class="field">
                            <label for="arrivee">Arrivée</label>
                            <input id="arrivee" name="arrivee" type="date" min="<?= $this->e($today) ?>" max="<?= $this->e($maxDate) ?>" value="<?= $this->e($values['arrivee']) ?>" required data-pair="depart"<?= $this->invalid('arrivee', $errors) ?>>
                        </div>
                        <div class="field">
                            <label for="depart">Départ</label>
                            <input id="depart" name="depart" type="date" min="<?= $this->e($today) ?>" value="<?= $this->e($values['depart']) ?>" required<?= $this->invalid('depart', $errors) ?>>
                        </div>
                    </div>
                    <?= $this->error('arrivee', $errors) ?>
                    <?= $this->error('depart', $errors) ?>
                    <div class="field">
                        <label for="voyageurs">Voyageurs</label>
                        <input id="voyageurs" name="voyageurs" type="number" min="1" max="<?= (int) $listing['capacite'] ?>" value="<?= $this->e($values['voyageurs']) ?>" required<?= $this->invalid('voyageurs', $errors) ?>>
                        <?= $this->error('voyageurs', $errors) ?>
                    </div>
                    <p class="quote" data-quote aria-live="polite">
                        <?php if ($quote !== null) : ?>
                            <span><?= $this->e($this->money($listing['prix_nuit'])) ?> × <?= $this->e($this->plural($quote['nights'], 'nuit', 'nuits')) ?></span>
                            <strong><?= $this->e($this->money($quote['total'])) ?></strong>
                        <?php endif; ?>
                    </p>
                    <button type="submit" class="button wide">Réserver</button>
                </form>
                <p class="small muted">Annulation gratuite jusqu'à la veille de l'arrivée. Aucun paiement en ligne : le séjour se règle avec le propriétaire.</p>
            <?php endif; ?>
        </aside>
        <div class="listing-info">

            <dl class="facts">
                <div><dt>Type</dt><dd><?= $this->e(Catalog::TYPES[$listing['type']] ?? '') ?></dd></div>
                <div><dt>Cadre</dt><dd><?= $this->e(Catalog::SETTINGS[$listing['cadre']] ?? '') ?></dd></div>
                <div><dt>Chambres</dt><dd><?= (int) $listing['chambres'] ?></dd></div>
                <div><dt>Voyageurs</dt><dd><?= (int) $listing['capacite'] ?> au plus</dd></div>
                <div><dt>Propriétaire</dt><dd><?= $this->e($listing['proprietaire_nom']) ?></dd></div>
            </dl>

            <section class="section-tight" aria-labelledby="titre-description">
                <h2 id="titre-description">Le logement</h2>
                <div class="prose"><?= nl2br($this->e($listing['description'])) ?></div>
            </section>

            <?php if ($listing['equipements'] !== []) : ?>
                <section class="section-tight" aria-labelledby="titre-equipements">
                    <h2 id="titre-equipements">Équipements</h2>
                    <ul class="amenities">
                        <?php foreach ($listing['equipements'] as $key) : ?>
                            <li><?= $this->e(Catalog::AMENITIES[$key]) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <section class="section-tight" aria-labelledby="titre-disponibilites" id="disponibilites">
                <div class="section-head">
                    <h2 id="titre-disponibilites">Disponibilités</h2>
                    <div class="month-nav">
                        <?php if ($previousMonth !== null) : ?>
                            <a href="<?= $this->e($this->url('/logements/' . $listing['id'], $stayQuery + ['mois' => $previousMonth])) ?>#disponibilites">Mois précédent</a>
                        <?php endif; ?>
                        <a href="<?= $this->e($this->url('/logements/' . $listing['id'], $stayQuery + ['mois' => $nextMonth])) ?>#disponibilites">Mois suivant</a>
                    </div>
                </div>
                <div class="calendars">
                    <?php foreach ($months as $month) : ?>
                        <table class="calendar">
                            <caption><?= $this->e($this->date($month['month'], 'MMMM y')) ?></caption>
                            <thead>
                                <tr>
                                    <?php foreach ($days as $day) : ?>
                                        <th scope="col"><abbr title="<?= $this->e($day) ?>"><?= $this->e(mb_strtoupper(mb_substr($day, 0, 1))) ?></abbr></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($month['weeks'] as $week) : ?>
                                    <tr>
                                        <?php foreach ($week as $cell) : ?>
                                            <?php if ($cell === null) : ?>
                                                <td></td>
                                            <?php else : ?>
                                                <td class="day is-<?= $this->e($cell['state']) ?>">
                                                    <?= $cell['day'] ?><?php if ($cell['state'] === 'booked') : ?><span class="sr-only"> (réservé)</span><?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                </div>
                <p class="legend"><span class="swatch is-free"></span> Libre <span class="swatch is-booked"></span> Réservé</p>
            </section>

            <section class="section-tight" aria-labelledby="titre-avis" id="avis">
                <h2 id="titre-avis">Avis des voyageurs</h2>
                <?php if ($reviews === []) : ?>
                    <p class="muted">Pas encore d'avis : seuls les voyageurs qui ont séjourné ici peuvent en laisser un.</p>
                <?php else : ?>
                    <ul class="reviews">
                        <?php foreach ($reviews as $review) : ?>
                            <li class="review">
                                <p class="review-head">
                                    <span class="stars"><span aria-hidden="true"><?= str_repeat('★', $review['note']) . str_repeat('☆', 5 - $review['note']) ?></span><span class="sr-only"><?= $review['note'] ?> sur 5</span></span>
                                    <strong><?= $this->e($review['auteur']) ?></strong>
                                    <span class="muted">séjour de <?= $this->e($this->date($review['arrivee'], 'MMMM y')) ?></span>
                                </p>
                                <p><?= nl2br($this->e($review['commentaire'])) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

    </div>
</div>
