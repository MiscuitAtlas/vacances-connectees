<?php
/**
 * @var \App\View\View $this
 * @var array<string, string> $values
 * @var array<string, string> $errors
 */
use App\Service\Passwords;

?>
<div class="container page narrow">
    <h1>Mon profil</h1>

    <form method="post" action="/compte/profil" class="panel form" novalidate>
        <h2>Informations</h2>
        <?= $this->csrfField() ?>
        <div class="field">
            <label for="nom">Nom</label>
            <input id="nom" name="nom" type="text" autocomplete="name" maxlength="100" value="<?= $this->e($values['nom']) ?>" required<?= $this->invalid('nom', $errors) ?>>
            <?= $this->error('nom', $errors) ?>
        </div>
        <div class="field">
            <label for="email">Adresse email</label>
            <input id="email" name="email" type="email" autocomplete="email" maxlength="191" value="<?= $this->e($values['email']) ?>" required<?= $this->invalid('email', $errors) ?>>
            <?= $this->error('email', $errors) ?>
        </div>
        <button type="submit" class="button">Enregistrer</button>
    </form>

    <form method="post" action="/compte/mot-de-passe" class="panel form" novalidate>
        <h2>Mot de passe</h2>
        <?= $this->csrfField() ?>
        <div class="field">
            <label for="actuel">Mot de passe actuel</label>
            <input id="actuel" name="actuel" type="password" autocomplete="current-password" required<?= $this->invalid('actuel', $errors) ?>>
            <?= $this->error('actuel', $errors) ?>
        </div>
        <div class="field">
            <label for="mot_de_passe">Nouveau mot de passe</label>
            <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="new-password" minlength="<?= Passwords::MIN_LENGTH ?>" required aria-describedby="aide-mdp<?= isset($errors['mot_de_passe']) ? ' erreur-mot_de_passe' : '' ?>"<?= isset($errors['mot_de_passe']) ? ' aria-invalid="true"' : '' ?>>
            <p class="help" id="aide-mdp">Au moins <?= Passwords::MIN_LENGTH ?> caractères.</p>
            <?= $this->error('mot_de_passe', $errors) ?>
        </div>
        <div class="field">
            <label for="confirmation">Confirmer le nouveau mot de passe</label>
            <input id="confirmation" name="confirmation" type="password" autocomplete="new-password" required>
        </div>
        <button type="submit" class="button">Changer le mot de passe</button>
    </form>
</div>
