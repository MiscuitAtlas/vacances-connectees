<?php
/**
 * @var \App\View\View $this
 * @var array<string, string> $values
 * @var array<string, string> $errors
 */
use App\Service\Passwords;

?>
<div class="container page narrow">
    <h1>Créer un compte</h1>
    <p class="lead">Un seul compte pour réserver des séjours et proposer vos logements.</p>
    <form method="post" action="/inscription" class="panel form" novalidate>
        <?= $this->csrfField() ?>
        <input type="hidden" name="retour" value="<?= $this->e($values['retour']) ?>">
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
        <div class="field">
            <label for="mot_de_passe">Mot de passe</label>
            <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="new-password" minlength="<?= Passwords::MIN_LENGTH ?>" required aria-describedby="aide-mdp<?= isset($errors['mot_de_passe']) ? ' erreur-mot_de_passe' : '' ?>"<?= isset($errors['mot_de_passe']) ? ' aria-invalid="true"' : '' ?>>
            <p class="help" id="aide-mdp">Au moins <?= Passwords::MIN_LENGTH ?> caractères. Une phrase facile à retenir fait un bon mot de passe.</p>
            <?= $this->error('mot_de_passe', $errors) ?>
        </div>
        <div class="field">
            <label for="confirmation">Confirmer le mot de passe</label>
            <input id="confirmation" name="confirmation" type="password" autocomplete="new-password" required>
        </div>
        <button type="submit" class="button wide">Créer mon compte</button>
        <p class="form-links"><a href="/connexion">J'ai déjà un compte</a></p>
    </form>
</div>
