<?php
/**
 * @var \App\View\View $this
 * @var array<string, mixed>|null $listing
 * @var array<string, mixed> $values
 * @var array<string, string> $errors
 * @var bool $photosEnabled
 */
use App\Domain\Catalog;

$action = $listing === null ? '/logements/nouveau' : '/logements/' . $listing['id'] . '/modifier';
$photo = $listing !== null ? $this->photoUrl($listing['photo']) : null;
?>
<div class="container page narrow">
    <p class="back"><a href="/mes-logements">Retour à mes logements</a></p>
    <h1><?= $listing === null ? 'Proposer un logement' : 'Modifier l\'annonce' ?></h1>
    <?php if ($errors !== []) : ?>
        <p class="form-error" role="alert">L'annonce n'est pas enregistrée : corrigez les champs signalés.</p>
    <?php endif; ?>

    <form method="post" action="<?= $this->e($action) ?>" enctype="multipart/form-data" class="panel form" novalidate>
        <?= $this->csrfField() ?>
        <div class="field">
            <label for="titre">Titre de l'annonce</label>
            <input id="titre" name="titre" type="text" maxlength="120" value="<?= $this->e($values['titre']) ?>" required<?= $this->invalid('titre', $errors) ?>>
            <?= $this->error('titre', $errors) ?>
        </div>
        <div class="field-row">
            <div class="field">
                <label for="type">Type</label>
                <select id="type" name="type" required<?= $this->invalid('type', $errors) ?>>
                    <option value="">Choisir…</option>
                    <?php foreach (Catalog::TYPES as $key => $label) : ?>
                        <option value="<?= $this->e($key) ?>"<?= $values['type'] === $key ? ' selected' : '' ?>><?= $this->e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $this->error('type', $errors) ?>
            </div>
            <div class="field">
                <label for="cadre">Cadre</label>
                <select id="cadre" name="cadre" required<?= $this->invalid('cadre', $errors) ?>>
                    <option value="">Choisir…</option>
                    <?php foreach (Catalog::SETTINGS as $key => $label) : ?>
                        <option value="<?= $this->e($key) ?>"<?= $values['cadre'] === $key ? ' selected' : '' ?>><?= $this->e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $this->error('cadre', $errors) ?>
            </div>
        </div>
        <div class="field-row">
            <div class="field">
                <label for="ville">Ville</label>
                <input id="ville" name="ville" type="text" maxlength="100" value="<?= $this->e($values['ville']) ?>" required<?= $this->invalid('ville', $errors) ?>>
                <?= $this->error('ville', $errors) ?>
            </div>
            <div class="field">
                <label for="region">Région</label>
                <input id="region" name="region" type="text" maxlength="100" value="<?= $this->e($values['region']) ?>" required<?= $this->invalid('region', $errors) ?>>
                <?= $this->error('region', $errors) ?>
            </div>
        </div>
        <div class="field-row three">
            <div class="field">
                <label for="prix_nuit">Prix par nuit (€)</label>
                <input id="prix_nuit" name="prix_nuit" type="number" min="10" max="5000" value="<?= $this->e($values['prix_nuit']) ?>" required<?= $this->invalid('prix_nuit', $errors) ?>>
                <?= $this->error('prix_nuit', $errors) ?>
            </div>
            <div class="field">
                <label for="chambres">Chambres</label>
                <input id="chambres" name="chambres" type="number" min="1" max="20" value="<?= $this->e($values['chambres']) ?>" required<?= $this->invalid('chambres', $errors) ?>>
                <?= $this->error('chambres', $errors) ?>
            </div>
            <div class="field">
                <label for="capacite">Voyageurs au plus</label>
                <input id="capacite" name="capacite" type="number" min="1" max="30" value="<?= $this->e($values['capacite']) ?>" required<?= $this->invalid('capacite', $errors) ?>>
                <?= $this->error('capacite', $errors) ?>
            </div>
        </div>
        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="7" minlength="30" maxlength="3000" required<?= $this->invalid('description', $errors) ?>><?= $this->e($values['description']) ?></textarea>
            <?= $this->error('description', $errors) ?>
        </div>
        <fieldset class="checkboxes">
            <legend>Équipements</legend>
            <?php foreach (Catalog::AMENITIES as $key => $label) : ?>
                <label><input type="checkbox" name="equipements[]" value="<?= $this->e($key) ?>"<?= in_array($key, (array) $values['equipements'], true) ? ' checked' : '' ?>> <?= $this->e($label) ?></label>
            <?php endforeach; ?>
        </fieldset>
        <?php if (!$photosEnabled) : ?>
            <p class="help">Dans la démo en ligne, l'envoi de photos est désactivé : l'annonce est illustrée selon le type et le cadre du logement.</p>
        <?php else : ?>
        <div class="field">
            <label for="photo">Photo <span class="muted">(facultative)</span></label>
            <?php if ($photo !== null) : ?>
                <img class="photo-preview" src="<?= $this->e($photo) ?>" alt="Photo actuelle du logement" width="400" height="300">
                <label class="inline"><input type="checkbox" name="retirer_photo" value="1"> Retirer la photo actuelle</label>
            <?php endif; ?>
            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="aide-photo<?= isset($errors['photo']) ? ' erreur-photo' : '' ?>"<?= isset($errors['photo']) ? ' aria-invalid="true"' : '' ?>>
            <p class="help" id="aide-photo">JPEG, PNG ou WebP, 5 Mo au plus. Sans photo, l'annonce est illustrée selon le type et le cadre du logement.</p>
            <?= $this->error('photo', $errors) ?>
        </div>
        <?php endif; ?>
        <button type="submit" class="button"><?= $listing === null ? 'Publier l\'annonce' : 'Enregistrer les modifications' ?></button>
    </form>
</div>
