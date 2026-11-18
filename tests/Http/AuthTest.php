<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Tests\DatabaseTestCase;

/**
 * Connexion, inscription, déconnexion et mot de passe oublié, vus du navigateur.
 */
final class AuthTest extends DatabaseTestCase
{
    public function testEveryFormRequiresTheCsrfToken(): void
    {
        $this->createUser();
        $response = $this->post('/connexion', ['email' => 'alice@exemple.fr', 'mot_de_passe' => self::PASSWORD], false);

        $this->assertSame(400, $response->status);
        $this->assertArrayNotHasKey('user_id', $this->sessionData);
    }

    public function testLoginWithTheSameMessageForAWrongPasswordAndAnUnknownEmail(): void
    {
        $this->createUser();

        $wrong = $this->post('/connexion', ['email' => 'alice@exemple.fr', 'mot_de_passe' => 'mauvais mot de passe']);
        $unknown = $this->post('/connexion', ['email' => 'personne@exemple.fr', 'mot_de_passe' => 'mauvais mot de passe']);

        $this->assertSame(422, $wrong->status);
        $this->assertSame(422, $unknown->status);
        $this->assertStringContainsString('Email ou mot de passe incorrect.', $wrong->body);
        $this->assertStringContainsString('Email ou mot de passe incorrect.', $unknown->body);
    }

    public function testLoginRenewsTheSessionAndOnlyRedirectsInsideTheSite(): void
    {
        $id = $this->createUser();

        $response = $this->post('/connexion', [
            'email' => ' Alice@Exemple.fr ',
            'mot_de_passe' => self::PASSWORD,
            'retour' => '//pirate.example/page',
        ]);

        $this->assertSame(303, $response->status);
        $this->assertSame('/compte', $response->header('Location'));
        $this->assertSame($id, $this->sessionData['user_id']);
        $this->assertSame(1, $this->sessionData['_regenerated']);

        $this->sessionData = [];
        $response = $this->post('/connexion', [
            'email' => 'alice@exemple.fr',
            'mot_de_passe' => self::PASSWORD,
            'retour' => '/logements/3?arrivee=2026-08-01',
        ]);
        $this->assertSame('/logements/3?arrivee=2026-08-01', $response->header('Location'));
    }

    public function testTooManyFailuresLockTheAccountForAWhile(): void
    {
        $this->createUser();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/connexion', ['email' => 'alice@exemple.fr', 'mot_de_passe' => 'essai ' . $i]);
        }

        $response = $this->post('/connexion', ['email' => 'alice@exemple.fr', 'mot_de_passe' => self::PASSWORD]);

        $this->assertSame(429, $response->status);
        $this->assertArrayNotHasKey('user_id', $this->sessionData);
    }

    public function testRegistration(): void
    {
        $this->createUser();

        $password = ['mot_de_passe' => 'un long mot de passe', 'confirmation' => 'un long mot de passe'];
        $taken = $this->post('/inscription', ['nom' => 'Autre', 'email' => 'ALICE@exemple.fr'] + $password);
        $this->assertSame(422, $taken->status);
        $this->assertStringContainsString('Un compte existe déjà', $taken->body);

        $weak = $this->post('/inscription', ['nom' => 'Bob', 'email' => 'bob@exemple.fr', 'mot_de_passe' => 'court', 'confirmation' => 'court']);
        $this->assertSame(422, $weak->status);

        $ok = $this->post('/inscription', ['nom' => 'Bob  Durand', 'email' => 'Bob@Exemple.fr'] + $password);
        $this->assertSame(303, $ok->status);
        $row = $this->pdo->query("SELECT id, nom, mot_de_passe FROM utilisateurs WHERE email = 'bob@exemple.fr'")->fetch();
        $this->assertSame('Bob Durand', $row['nom']);
        $this->assertStringStartsWith('$2y$', $row['mot_de_passe']);
        $this->assertSame((int) $row['id'], $this->sessionData['user_id']);
    }

    public function testLogoutOnlyByPost(): void
    {
        $this->loginAs($this->createUser());

        $this->assertSame(405, $this->get('/deconnexion')->status);
        $this->assertSame(303, $this->post('/deconnexion')->status);
        $this->assertArrayNotHasKey('user_id', $this->sessionData);
    }

    public function testForgottenPasswordFlow(): void
    {
        $this->createUser();

        $this->post('/mot-de-passe-oublie', ['email' => 'inconnu@exemple.fr']);
        $unknownMessage = $this->flashes();
        $this->post('/mot-de-passe-oublie', ['email' => 'alice@exemple.fr']);
        $this->assertSame($unknownMessage, $this->flashes(), 'Le message ne doit pas révéler si le compte existe.');

        $mails = glob($this->tmpDir . '/mail/*.eml') ?: [];
        $this->assertCount(1, $mails);
        preg_match('/jeton=([a-f0-9]{64})/', (string) file_get_contents($mails[0]), $match);

        // Le lien de l'email mène à une adresse sans le jeton.
        $response = $this->get('/reinitialisation', ['jeton' => $match[1]]);
        $this->assertSame('/reinitialisation', $response->header('Location'));
        $this->assertStringContainsString('Nouveau mot de passe', $this->get('/reinitialisation')->body);

        $response = $this->post('/reinitialisation', ['mot_de_passe' => 'mon nouveau secret', 'confirmation' => 'mon nouveau secret']);
        $this->assertSame('/connexion', $response->header('Location'));

        $this->assertSame(422, $this->post('/connexion', ['email' => 'alice@exemple.fr', 'mot_de_passe' => self::PASSWORD])->status);
        $this->assertSame(303, $this->post('/connexion', ['email' => 'alice@exemple.fr', 'mot_de_passe' => 'mon nouveau secret'])->status);

        // Le même lien ne sert pas deux fois.
        $this->sessionData = [];
        $this->get('/reinitialisation', ['jeton' => $match[1]]);
        $this->assertStringContainsString('n\'est plus valable', $this->get('/reinitialisation')->body);
    }

    public function testChangingThePasswordRequiresTheCurrentOne(): void
    {
        $this->loginAs($this->createUser());

        $new = ['mot_de_passe' => 'un autre mot de passe', 'confirmation' => 'un autre mot de passe'];
        $refused = $this->post('/compte/mot-de-passe', ['actuel' => 'pas le bon'] + $new);
        $this->assertSame(422, $refused->status);

        $accepted = $this->post('/compte/mot-de-passe', ['actuel' => self::PASSWORD] + $new);
        $this->assertSame(303, $accepted->status);
        $hash = (string) $this->pdo->query('SELECT mot_de_passe FROM utilisateurs')->fetchColumn();
        $this->assertTrue(password_verify('un autre mot de passe', $hash));
    }
}
