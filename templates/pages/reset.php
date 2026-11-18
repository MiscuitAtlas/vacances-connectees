<?php
/**
 * @var \App\View\View $this
 * @var bool $valid
 * @var string $error
 */
use App\Service\Passwords;

?>
<div class="container page narrow">
    <h1>Nouveau mot de passe</h1>
    <?php if (!$valid) : ?>
        <div class="panel">
            <p>Ce lien n'est plus valable : il a expiré ou a déjà servi.</p>
            <p><a class="button" href="/mot-de-passe-oublie">Demander un nouveau lien</a></p>
        </div>
    <?php else : ?>
        <form method="post" action="/reinitialisation" class="panel form" novalidate>
            <?= $this->csrfField() ?>
            <?php if ($error !== '') : ?>
                <p class="form-error" role="alert"><?= $this->e($error) ?></p>
            <?php endif; ?>
            <div class="field">
                <label for="mot_de_passe">Nouveau mot de passe</label>
                <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="new-password" minlength="<?= Passwords::MIN_LENGTH ?>" required aria-describedby="aide-mdp">
                <p class="help" id="aide-mdp">Au moins <?= Passwords::MIN_LENGTH ?> caractères.</p>
            </div>
            <div class="field">
                <label for="confirmation">Confirmer le mot de passe</label>
                <input id="confirmation" name="confirmation" type="password" autocomplete="new-password" required>
            </div>
            <button type="submit" class="button wide">Enregistrer le mot de passe</button>
        </form>
    <?php endif; ?>
</div>
