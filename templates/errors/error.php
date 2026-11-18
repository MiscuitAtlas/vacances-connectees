<?php
/**
 * @var \App\View\View $this
 * @var int $status
 * @var string $title
 * @var string $message
 */
?>
<div class="container page narrow error-page">
    <p class="error-code" aria-hidden="true"><?= (int) $status ?></p>
    <h1><?= $this->e($title) ?></h1>
    <p class="lead"><?= $this->e($message) ?></p>
    <p><a class="button" href="/">Retour à l'accueil</a> <a href="/recherche">Rechercher un logement</a></p>
</div>
