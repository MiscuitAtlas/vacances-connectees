<?php
/**
 * @var \App\View\View $this
 * @var list<array<string, mixed>> $listings
 * @var list<array{region: string, total: int}> $regions
 * @var array<string, mixed> $search
 * @var string $today
 */
use App\View\Poster;

?>
<section class="hero">
    <div class="container">
        <h1>Des maisons de vacances libres aux dates qui vous arrangent</h1>
        <p class="lead">Chalets, villas, cabanes et appartements partout en France. Indiquez vos dates : seuls les logements disponibles s'affichent.</p>
    </div>
    <div class="container">
        <div class="hero-art" aria-hidden="true">
            <?= Poster::render('maison', 'mer', 8) ?>
            <?= Poster::render('chalet', 'montagne', 5) ?>
            <?= Poster::render('villa', 'campagne', 2) ?>
            <?= Poster::render('cabane', 'lac', 12) ?>
        </div>
    </div>
    <div class="container hero-search">
        <?= $this->partial('search-form', ['search' => $search, 'today' => $today]) ?>
    </div>
</section>

<section class="container section" aria-labelledby="titre-notes">
    <div class="section-head">
        <h2 id="titre-notes">Les mieux notés par les voyageurs</h2>
        <a href="/recherche">Voir tous les logements</a>
    </div>
    <div class="grid">
        <?php foreach ($listings as $listing) : ?>
            <?= $this->partial('card', ['listing' => $listing]) ?>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($regions !== []) : ?>
    <section class="container section" aria-labelledby="titre-regions">
        <h2 id="titre-regions">Où partir ?</h2>
        <ul class="chips">
            <?php foreach ($regions as $region) : ?>
                <li>
                    <a class="chip" href="<?= $this->e($this->url('/recherche', ['destination' => $region['region']])) ?>">
                        <?= $this->e($region['region']) ?> <span class="muted"><?= $region['total'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="container section owners" aria-labelledby="titre-proprietaires">
    <h2 id="titre-proprietaires">Vous avez un logement à louer ?</h2>
    <p>Publiez votre annonce en quelques minutes : description, équipements, prix et photo. Les réservations arrivent dans votre espace, sans jamais se chevaucher.</p>
    <a class="button secondary" href="/logements/nouveau">Proposer un logement</a>
</section>
