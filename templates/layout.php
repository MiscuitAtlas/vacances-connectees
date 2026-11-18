<?php
/**
 * @var \App\View\View $this
 * @var string $content
 * @var string $title
 * @var array<string, mixed>|null $currentUser
 * @var list<array{type: string, message: string}> $flashes
 * @var array{emails: int}|null $demo
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->e(($title ?? '') !== '' ? $title . ' – VacancesConnectées' : 'VacancesConnectées – Locations de vacances en France') ?></title>
    <meta name="description" content="Maisons, chalets et appartements de vacances en France, réservables aux dates qui vous conviennent.">
    <link rel="icon" href="<?= $this->asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preload" href="/assets/fonts/big-shoulders-800.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= $this->asset('css/app.css') ?>">
    <script src="<?= $this->asset('js/app.js') ?>" defer></script>
</head>
<body>
    <a class="skip-link" href="#contenu">Aller au contenu</a>
    <header class="site-header">
        <?php if ($demo !== null) : ?>
            <div class="demo-bar">
                <div class="container demo-bar-inner">
                    <p><strong>Démo en ligne.</strong> Ce que vous faites ici n'est visible que par vous et s'efface au bout de deux heures.</p>
                    <ul class="demo-links">
                        <li><a href="/connexion#demo">Comptes de démonstration</a></li>
                        <li><a href="/demo/emails"<?= $this->isCurrent('/demo/emails') ? ' aria-current="page"' : '' ?>>Emails envoyés<?= $demo['emails'] > 0 ? ' (' . $demo['emails'] . ')' : '' ?></a></li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        <div class="container header-inner">
            <a class="brand" href="/">
                <img src="<?= $this->asset('img/logo.svg') ?>" alt="" width="40" height="40">
                <span>VacancesConnectées</span>
            </a>
            <nav class="main-nav" aria-label="Menu principal">
                <ul>
                    <li><a href="/recherche"<?= $this->isCurrent('/recherche') ? ' aria-current="page"' : '' ?>>Rechercher</a></li>
                    <?php if ($currentUser !== null) : ?>
                        <li><a href="/compte"<?= $this->isCurrent('/compte') && !$this->isCurrent('/compte/profil') ? ' aria-current="page"' : '' ?>>Mes voyages</a></li>
                        <li><a href="/mes-logements"<?= $this->isCurrent('/mes-logements') ? ' aria-current="page"' : '' ?>>Mes logements</a></li>
                        <li><a href="/compte/profil"<?= $this->isCurrent('/compte/profil') ? ' aria-current="page"' : '' ?>><?= $this->e($currentUser['nom']) ?></a></li>
                        <li>
                            <form method="post" action="/deconnexion">
                                <?= $this->csrfField() ?>
                                <button type="submit" class="link-button">Se déconnecter</button>
                            </form>
                        </li>
                    <?php else : ?>
                        <li><a href="/logements/nouveau">Proposer un logement</a></li>
                        <li><a href="/connexion"<?= $this->isCurrent('/connexion') ? ' aria-current="page"' : '' ?>>Connexion</a></li>
                        <li><a class="button small" href="/inscription">Créer un compte</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main id="contenu" tabindex="-1">
        <?php if ($flashes !== []) : ?>
            <div class="container flashes">
                <?php foreach ($flashes as $flash) : ?>
                    <p class="flash flash-<?= $this->e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>"><?= $this->e($flash['message']) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p><strong>VacancesConnectées</strong> : projet de formation, logements et comptes fictifs.</p>
            <p>Aucun paiement en ligne : le séjour se règle directement avec le propriétaire.</p>
        </div>
    </footer>
</body>
</html>
