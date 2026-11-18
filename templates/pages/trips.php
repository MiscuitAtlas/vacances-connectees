<?php
/**
 * @var \App\View\View $this
 * @var array{upcoming: list<array<string, mixed>>, past: list<array<string, mixed>>, cancelled: list<array<string, mixed>>} $groups
 */
?>
<div class="container page">
    <h1>Mes voyages</h1>

    <section class="section-tight" aria-labelledby="titre-a-venir">
        <h2 id="titre-a-venir">À venir</h2>
        <?php if ($groups['upcoming'] === []) : ?>
            <div class="empty panel">
                <p>Aucun séjour prévu pour l'instant.</p>
                <p><a class="button" href="/recherche">Trouver un logement</a></p>
            </div>
        <?php else : ?>
            <ul class="trips">
                <?php foreach ($groups['upcoming'] as $booking) : ?>
                    <?= $this->partial('trip', ['booking' => $booking]) ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if ($groups['past'] !== []) : ?>
        <section class="section-tight" aria-labelledby="titre-passes">
            <h2 id="titre-passes">Séjours passés</h2>
            <ul class="trips">
                <?php foreach ($groups['past'] as $booking) : ?>
                    <?= $this->partial('trip', ['booking' => $booking, 'action' => $booking['a_un_avis'] ? '' : '/reservations/' . $booking['id'] . '/avis']) ?>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($groups['cancelled'] !== []) : ?>
        <section class="section-tight" aria-labelledby="titre-annules">
            <h2 id="titre-annules">Annulés</h2>
            <ul class="trips is-cancelled">
                <?php foreach ($groups['cancelled'] as $booking) : ?>
                    <?= $this->partial('trip', ['booking' => $booking]) ?>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</div>
