<?php
/**
 * @var \App\View\View $this
 * @var array<string, mixed> $booking
 * @var array<string, mixed>|null $listing
 * @var int $nights
 * @var bool $isTraveller
 * @var bool $canCancel
 * @var bool $canReview
 */
$cancelled = $booking['statut'] !== 'confirmee';
?>
<div class="container page narrow">
    <p class="back"><a href="<?= $isTraveller ? '/compte' : '/mes-logements' ?>"><?= $isTraveller ? 'Retour à mes voyages' : 'Retour à mes logements' ?></a></p>
    <h1>Réservation n° <?= (int) $booking['id'] ?></h1>

    <article class="ticket<?= $cancelled ? ' is-cancelled' : '' ?>">
        <header class="ticket-head">
            <p class="ticket-city"><?= $this->e($booking['ville']) ?></p>
            <p class="badge <?= $cancelled ? 'badge-muted' : 'badge-ok' ?>"><?= $cancelled ? 'Annulée' : 'Confirmée' ?></p>
        </header>
        <h2 class="ticket-title"><a href="/logements/<?= (int) $booking['logement_id'] ?>"><?= $this->e($booking['titre']) ?></a></h2>
        <dl class="ticket-dates">
            <div><dt>Arrivée</dt><dd><?= $this->e($this->date($booking['arrivee'], 'EEEE d MMMM y')) ?></dd></div>
            <div><dt>Départ</dt><dd><?= $this->e($this->date($booking['depart'], 'EEEE d MMMM y')) ?></dd></div>
        </dl>
        <dl class="ticket-facts">
            <div><dt>Durée</dt><dd><?= $this->e($this->plural($nights, 'nuit', 'nuits')) ?></dd></div>
            <div><dt>Voyageurs</dt><dd><?= (int) $booking['voyageurs'] ?></dd></div>
            <div><dt>Total</dt><dd><?= $this->e($this->money($booking['prix_total'])) ?></dd></div>
            <?php if ($listing !== null) : ?>
                <div><dt><?= $isTraveller ? 'Propriétaire' : 'Logement' ?></dt><dd><?= $this->e($isTraveller ? $listing['proprietaire_nom'] : $listing['titre']) ?></dd></div>
            <?php endif; ?>
        </dl>
    </article>

    <div class="actions">
        <?php if ($canReview) : ?>
            <a class="button" href="/reservations/<?= (int) $booking['id'] ?>/avis">Donner mon avis</a>
        <?php endif; ?>
        <?php if ($canCancel) : ?>
            <form method="post" action="/reservations/<?= (int) $booking['id'] ?>/annuler" data-confirm="Annuler cette réservation ? Les dates seront proposées à d'autres voyageurs.">
                <?= $this->csrfField() ?>
                <button type="submit" class="button danger">Annuler la réservation</button>
            </form>
        <?php endif; ?>
    </div>
</div>
