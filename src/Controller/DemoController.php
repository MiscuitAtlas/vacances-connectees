<?php

declare(strict_types=1);

namespace App\Controller;

use App\Demo\Mailbox;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Pages propres à la démo en ligne : connexion en un clic et emails envoyés.
 */
final class DemoController extends Controller
{
    /**
     * Comptes de démonstration et page affichée après la connexion.
     */
    private const ACCOUNTS = [
        'voyageuse' => ['email' => 'demo@exemple.fr', 'page' => '/compte'],
        'proprietaire' => ['email' => 'hote@exemple.fr', 'page' => '/mes-logements'],
    ];

    public function login(Request $request): Response
    {
        $this->requireDemo();
        $account = self::ACCOUNTS[$request->input('compte')] ?? throw HttpException::notFound();

        $user = $this->app->users()->findByEmail($account['email']);
        if ($user === null) {
            $this->flash('error', 'Ce compte de démonstration a changé d\'adresse email : connectez-vous avec le formulaire.');

            return $this->redirect('/connexion');
        }
        $this->app->auth->login($user);
        $this->flash('success', 'Bonjour ' . $user['nom'] . ', vous êtes connecté.');

        return $this->redirect($account['page']);
    }

    public function emails(Request $request): Response
    {
        $this->requireDemo();
        $dir = $this->app->demoMailDir();

        return $this->render('pages/demo-emails', [
            'title' => 'Emails envoyés',
            'emails' => $dir === null ? [] : Mailbox::read($dir, (string) $this->app->config['url']),
        ]);
    }

    private function requireDemo(): void
    {
        if (!$this->app->isDemo()) {
            throw HttpException::notFound();
        }
    }
}
