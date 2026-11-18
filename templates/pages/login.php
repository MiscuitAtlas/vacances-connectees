<?php
/**
 * @var \App\View\View $this
 * @var string $returnTo
 * @var string $email
 * @var string $error
 * @var array{emails: int}|null $demo
 */
?>
<div class="container page narrow">
    <h1>Connexion</h1>
    <?php if ($demo !== null) : ?>
        <section class="panel demo-accounts" id="demo" aria-labelledby="titre-demo">
            <h2 id="titre-demo">Comptes de démonstration</h2>
            <p>Entrez directement avec un compte déjà rempli, ou utilisez le formulaire avec son adresse et le mot de passe <strong>vacances-demo</strong>.</p>
            <div class="demo-account-list">
                <form method="post" action="/demo/connexion">
                    <?= $this->csrfField() ?>
                    <input type="hidden" name="compte" value="voyageuse">
                    <button type="submit" class="button">Entrer comme voyageuse</button>
                    <p class="help">Camille : séjours passés et à venir, avis à donner. demo@exemple.fr</p>
                </form>
                <form method="post" action="/demo/connexion">
                    <?= $this->csrfField() ?>
                    <input type="hidden" name="compte" value="proprietaire">
                    <button type="submit" class="button">Entrer comme propriétaire</button>
                    <p class="help">Hélène : trois logements et des réservations reçues. hote@exemple.fr</p>
                </form>
            </div>
        </section>
    <?php endif; ?>
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
