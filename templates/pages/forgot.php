<?php
/**
 * @var \App\View\View $this
 * @var string $email
 * @var string $error
 */
?>
<div class="container page narrow">
    <h1>Mot de passe oublié</h1>
    <p class="lead">Indiquez l'adresse de votre compte : vous recevrez un lien pour choisir un nouveau mot de passe.</p>
    <form method="post" action="/mot-de-passe-oublie" class="panel form" novalidate>
        <?= $this->csrfField() ?>
        <div class="field">
            <label for="email">Adresse email</label>
            <input id="email" name="email" type="email" autocomplete="email" value="<?= $this->e($email) ?>" required<?= $this->invalid('email', $error !== '' ? ['email' => $error] : []) ?>>
            <?= $this->error('email', $error !== '' ? ['email' => $error] : []) ?>
        </div>
        <button type="submit" class="button wide">Envoyer le lien</button>
        <p class="form-links"><a href="/connexion">Retour à la connexion</a></p>
    </form>
</div>
