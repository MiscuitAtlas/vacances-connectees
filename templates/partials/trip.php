<?php
/**
 * Séjour dans la liste « Mes voyages ».
 *
 * @var \App\View\View $this
 * @var array<string, mixed> $booking
 * @var string $action Lien d'action mis en avant (avis à donner), ou vide.
 */
$action ??= '';
?>
<li class="trip">
    <div class="trip-art"><?= $this->poster(['id' => $booking['logement_id'], 'type' => $booking['type'], 'cadre' => $booking['cadre']]) ?></div>
    <div class="trip-body">
        <p class="trip-city"><?= $this->e($booking['ville']) ?></p>
        <h3><a href="/reservations/<?= (int) $booking['id'] ?>"><?= $this->e($booking['titre']) ?></a></h3>
        <p class="muted">
            Du <?= $this->e($this->date($booking['arrivee'], 'd MMMM')) ?> au <?= $this->e($this->date($booking['depart'], 'd MMMM y')) ?>,
            <?= $this->e($this->plural($booking['voyageurs'], 'voyageur', 'voyageurs')) ?>, <?= $this->e($this->money($booking['prix_total'])) ?>
        </p>
        <?php if ($action !== '') : ?>
            <p><a class="button small" href="<?= $this->e($action) ?>">Donner mon avis</a></p>
        <?php endif; ?>
    </div>
</li>
