<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\ValidationException;
use App\Repository\UserRepository;
use App\Service\LoginThrottle;
use App\Service\Mailer;
use App\Service\PasswordReset;
use App\Tests\DatabaseTestCase;
use DateTimeImmutable;

/**
 * Réinitialisation du mot de passe et limitation des essais de connexion.
 */
final class AccountSecurityTest extends DatabaseTestCase
{
    private PasswordReset $reset;
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository($this->pdo);
        $mailer = new Mailer('log', 'test@vacances.example', $this->tmpDir . '/mail');
        $this->reset = new PasswordReset($this->pdo, $this->users, $mailer, 'https://vacances.example', 30);
    }

    private function sentLink(): string
    {
        $mails = glob($this->tmpDir . '/mail/*.eml') ?: [];
        $this->assertNotEmpty($mails, 'Aucun email envoyé.');
        $content = (string) file_get_contents(end($mails));
        $this->assertSame(1, preg_match('#https://vacances\.example/reinitialisation\?jeton=([a-f0-9]{64})#', $content, $match));

        return $match[1];
    }

    public function testUnknownEmailSendsNothingAndRaisesNothing(): void
    {
        $this->reset->request('inconnu@exemple.fr', $this->now());

        $this->assertSame([], glob($this->tmpDir . '/mail/*.eml'));
    }

    public function testTheLinkWorksOnceAndOnlyTheHashIsStored(): void
    {
        $id = $this->createUser();
        $this->reset->request('Alice@Exemple.fr', $this->now());
        $token = $this->sentLink();

        $stored = $this->pdo->query('SELECT empreinte FROM jetons_reinitialisation')->fetchColumn();
        $this->assertSame(hash('sha256', $token), $stored);
        $this->assertNotSame($token, $stored);

        $hash = (string) $this->reset->validate($token, $this->now());
        $this->reset->reset($hash, 'nouveau mot de passe', 'nouveau mot de passe', $this->now());
        $this->assertTrue(password_verify('nouveau mot de passe', (string) $this->users->find($id)['mot_de_passe']));

        $this->assertNull($this->reset->validate($token, $this->now()));
        $this->expectException(ValidationException::class);
        $this->reset->reset($hash, 'encore un autre mot', 'encore un autre mot', $this->now());
    }

    public function testTheLinkExpiresAndANewRequestCancelsTheOldOne(): void
    {
        $this->createUser();
        $this->reset->request('alice@exemple.fr', $this->now());
        $first = $this->sentLink();

        $this->assertNull($this->reset->validate($first, $this->now()->modify('+31 minutes')));
        $this->assertNotNull($this->reset->validate($first, $this->now()->modify('+29 minutes')));

        array_map('unlink', glob($this->tmpDir . '/mail/*.eml') ?: []);
        $this->reset->request('alice@exemple.fr', $this->now()->modify('+1 minute'));
        $second = $this->sentLink();
        $this->assertNotSame($first, $second);
        $this->assertNull($this->reset->validate($first, $this->now()->modify('+2 minutes')));
        $this->assertNotNull($this->reset->validate($second, $this->now()->modify('+2 minutes')));
    }

    public function testMalformedTokensAreRejected(): void
    {
        $this->assertNull($this->reset->validate("' OR '1'='1", $this->now()));
        $this->assertNull($this->reset->validate(str_repeat('a', 64), $this->now()));
    }

    public function testLoginIsLockedAfterRepeatedFailures(): void
    {
        $throttle = new LoginThrottle($this->pdo, 3, 15);
        $now = new DateTimeImmutable('2026-07-01 10:00:00');
        foreach ([0, 1, 2] as $minute) {
            $this->assertFalse($throttle->isLocked('alice@exemple.fr', $now));
            $throttle->recordFailure('Alice@exemple.fr', $now->modify("+$minute minutes"));
        }

        $this->assertTrue($throttle->isLocked('ALICE@exemple.fr', $now->modify('+3 minutes')));
        $this->assertFalse($throttle->isLocked('bob@exemple.fr', $now->modify('+3 minutes')));
        $this->assertFalse($throttle->isLocked('alice@exemple.fr', $now->modify('+20 minutes')));

        $throttle->clear('alice@exemple.fr');
        $this->assertFalse($throttle->isLocked('alice@exemple.fr', $now->modify('+3 minutes')));
        $this->assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM echecs_connexion WHERE empreinte_email LIKE '%@%'")->fetchColumn());
    }
}
