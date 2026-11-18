<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\ValidationException;
use App\Repository\UserRepository;
use DateTimeImmutable;
use PDO;

/**
 * Mot de passe oublié : un lien à usage unique, valable 30 minutes, envoyé à l'adresse du compte.
 * La base ne garde que l'empreinte SHA-256 du jeton : une fuite de la base ne donne aucun lien valable.
 */
final class PasswordReset
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly UserRepository $users,
        private readonly Mailer $mailer,
        private readonly string $siteUrl,
        private readonly int $validityMinutes,
    ) {
    }

    /**
     * Envoie le lien si l'adresse correspond à un compte. Rien ne permet de savoir, depuis la page,
     * si un compte existe pour cette adresse.
     */
    public function request(string $email, DateTimeImmutable $now): void
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return;
        }
        $this->invalidateFor((int) $user['id'], $now);

        $token = bin2hex(random_bytes(32));
        $this->pdo->prepare(
            'INSERT INTO jetons_reinitialisation (utilisateur_id, empreinte, expire_le, cree_le) VALUES (?, ?, ?, ?)',
        )->execute([
            $user['id'],
            hash('sha256', $token),
            $now->modify('+' . $this->validityMinutes . ' minutes')->format('Y-m-d H:i:s'),
            $now->format('Y-m-d H:i:s'),
        ]);

        // L'adresse du lien vient de la configuration, jamais de l'en-tête Host de la requête.
        $link = $this->siteUrl . '/reinitialisation?jeton=' . $token;
        $this->mailer->send(
            (string) $user['email'],
            'Réinitialisation de votre mot de passe',
            "Bonjour {$user['nom']},\n\n"
            . "Pour choisir un nouveau mot de passe sur VacancesConnectées, ouvrez ce lien :\n{$link}\n\n"
            . "Il est valable {$this->validityMinutes} minutes et ne sert qu'une fois.\n"
            . "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message : votre mot de passe ne change pas.\n",
        );
    }

    /**
     * Empreinte du jeton si le lien est encore valable, sinon null.
     */
    public function validate(string $token, DateTimeImmutable $now): ?string
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }
        $hash = hash('sha256', $token);

        return $this->findValid($hash, $now) !== null ? $hash : null;
    }

    /**
     * @throws ValidationException
     */
    public function reset(string $tokenHash, string $password, string $confirmation, DateTimeImmutable $now): void
    {
        $token = $this->findValid($tokenHash, $now);
        if ($token === null) {
            throw ValidationException::field('jeton', 'Ce lien a expiré ou a déjà servi. Faites une nouvelle demande.');
        }
        $user = $this->users->find((int) $token['utilisateur_id']);
        $problem = Passwords::problem($password, $confirmation, (string) ($user['email'] ?? ''));
        if ($problem !== null) {
            throw ValidationException::field('mot_de_passe', $problem);
        }
        $this->users->updatePassword((int) $token['utilisateur_id'], Passwords::hash($password));
        $this->invalidateFor((int) $token['utilisateur_id'], $now);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findValid(string $hash, DateTimeImmutable $now): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, utilisateur_id FROM jetons_reinitialisation WHERE empreinte = ? AND utilise_le IS NULL AND expire_le > ?',
        );
        $statement->execute([$hash, $now->format('Y-m-d H:i:s')]);

        return $statement->fetch() ?: null;
    }

    private function invalidateFor(int $userId, DateTimeImmutable $now): void
    {
        $this->pdo->prepare('UPDATE jetons_reinitialisation SET utilise_le = ? WHERE utilisateur_id = ? AND utilise_le IS NULL')
            ->execute([$now->format('Y-m-d H:i:s'), $userId]);
    }
}
