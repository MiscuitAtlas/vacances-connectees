<?php
/**
 * @var \App\View\View $this
 * @var array<string, mixed> $booking
 * @var array<string, string> $values
 * @var array<string, string> $errors
 */
?>
<div class="container page narrow">
    <p class="back"><a href="/reservations/<?= (int) $booking['id'] ?>">Retour à la réservation</a></p>
    <h1>Votre avis sur « <?= $this->e($booking['titre']) ?> »</h1>
    <p class="lead">Séjour du <?= $this->e($this->date($booking['arrivee'], 'd MMMM')) ?> au <?= $this->e($this->date($booking['depart'], 'd MMMM y')) ?>.</p>

    <form method="post" action="/reservations/<?= (int) $booking['id'] ?>/avis" class="panel form" novalidate>
        <?= $this->csrfField() ?>
        <fieldset class="rating-input"<?= $this->invalid('note', $errors) ?>>
            <legend>Note</legend>
            <?php for ($note = 5; $note >= 1; $note--) : ?>
                <label>
                    <input type="radio" name="note" value="<?= $note ?>"<?= $values['note'] === (string) $note ? ' checked' : '' ?> required>
                    <span><?= $note ?> <span aria-hidden="true"><?= str_repeat('★', $note) ?></span></span>
                </label>
            <?php endfor; ?>
        </fieldset>
        <?= $this->error('note', $errors) ?>
        <div class="field">
            <label for="commentaire">Votre avis</label>
            <textarea id="commentaire" name="commentaire" rows="6" minlength="10" maxlength="1000" required<?= $this->invalid('commentaire', $errors) ?>><?= $this->e($values['commentaire']) ?></textarea>
            <?= $this->error('commentaire', $errors) ?>
        </div>
        <button type="submit" class="button">Publier mon avis</button>
    </form>
</div>
