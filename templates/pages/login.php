<?php
/**
 * @var \App\View\View $this
 * @var string $returnTo
 * @var string $email
 * @var string $error
 */
?>
<div class="container page narrow">
    <h1>Connexion</h1>
    <form method="post" action="/connexion" class="panel form" novalidate>
        <?= $this->csrfField() ?>
        <input type="hidden" name="retour" value="<?= $this->e($returnTo) ?>">
        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert" id="erreur-connexion"><?= $this->e($error) ?></p>
        <?php endif; ?>
        <div class="field">
            <label for="email">Adresse email</label>
            <input id="email" name="email" type="email" autocomplete="email" value="<?= $this->e($email) ?>" required<?= $error !== '' ? ' aria-describedby="erreur-connexion"' : '' ?>>
        </div>
        <div class="field">
            <label for="mot_de_passe">Mot de passe</label>
            <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="button wide">Se connecter</button>
        <p class="form-links">
            <a href="/mot-de-passe-oublie">Mot de passe oublié ?</a>
            <a href="<?= $this->e($this->url('/inscription', ['retour' => $returnTo])) ?>">Créer un compte</a>
        </p>
    </form>
</div>
