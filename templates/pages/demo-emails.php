<?php
/**
 * @var \App\View\View $this
 * @var list<array{to: string, subject: string, date: \DateTimeImmutable|null, parts: list<array{text: string, href: string|null}>}> $emails
 */
?>
<div class="container page narrow">
    <h1>Emails envoyés</h1>
    <p class="lead">Dans la démo, les emails ne partent pas : ceux que le site aurait envoyés depuis votre copie s'affichent ici.</p>
    <?php if ($emails === []) : ?>
        <div class="panel empty">
            <p>Aucun email pour l'instant. Pour en recevoir un, demandez un lien depuis <a href="/mot-de-passe-oublie">Mot de passe oublié</a>, ou réservez un séjour : le propriétaire est prévenu par email.</p>
        </div>
    <?php else : ?>
        <?php foreach ($emails as $email) : ?>
            <article class="panel email">
                <h2><?= $this->e($email['subject']) ?></h2>
                <p class="muted">
                    À <?= $this->e($email['to']) ?><?php if ($email['date'] !== null) : ?>, le <?= $this->e($this->date($email['date']->format('Y-m-d H:i:s'), "d MMMM 'à' HH:mm")) ?><?php endif; ?>
                </p>
                <p class="email-body"><?php foreach ($email['parts'] as $part) : ?><?php if ($part['href'] !== null) : ?><a href="<?= $this->e($part['href']) ?>"><?= $this->e($part['text']) ?></a><?php else : ?><?= $this->e($part['text']) ?><?php endif; ?><?php endforeach; ?></p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
