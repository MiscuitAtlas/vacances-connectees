<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Service\Passwords;

final class AuthController extends Controller
{
    /**
     * Empreinte utilisée quand l'email est inconnu : la vérification prend le même temps
     * qu'avec un vrai compte, ce qui ne trahit pas l'existence de l'adresse.
     */
    private const DUMMY_HASH = '$2y$10$wLHpY2svbfISlCOOW48/9.s8cFKgQZCbJ7f2HUsB29t7i/2vm3HBK';

    public function loginForm(Request $request): Response
    {
        if ($this->app->auth->user() !== null) {
            return $this->redirect('/compte');
        }

        return $this->loginPage($request->query('retour'), '', '');
    }

    public function login(Request $request): Response
    {
        $email = $request->input('email');
        $password = $request->raw('mot_de_passe');
        $returnTo = $request->input('retour');
        $throttle = $this->app->throttle();
        $now = $this->app->now();

        if ($throttle->isLocked($email, $now)) {
            return $this->loginPage($returnTo, $email, sprintf(
                'Trop de tentatives pour ce compte. Réessayez dans %d minutes ou réinitialisez votre mot de passe.',
                $throttle->lockMinutes(),
            ), 429);
        }

        $user = $this->app->users()->findByEmail($email);
        $valid = password_verify($password, $user['mot_de_passe'] ?? self::DUMMY_HASH);
        if ($user === null || !$valid) {
            $throttle->recordFailure($email, $now);

            return $this->loginPage($returnTo, $email, 'Email ou mot de passe incorrect.', 422);
        }

        $throttle->clear($email);
        if (password_needs_rehash((string) $user['mot_de_passe'], PASSWORD_DEFAULT)) {
            $this->app->users()->updatePassword((int) $user['id'], Passwords::hash($password));
        }
        $this->app->auth->login($user);
        $this->flash('success', 'Bonjour ' . $user['nom'] . ', vous êtes connecté.');

        return $this->redirect(self::safeReturn($returnTo));
    }

    public function registerForm(Request $request): Response
    {
        if ($this->app->auth->user() !== null) {
            return $this->redirect('/compte');
        }

        return $this->registerPage(['nom' => '', 'email' => '', 'retour' => $request->query('retour')], []);
    }

    public function register(Request $request): Response
    {
        $values = [
            'nom' => preg_replace('/\s+/u', ' ', $request->input('nom')) ?? '',
            'email' => $request->input('email'),
            'retour' => $request->input('retour'),
        ];
        $password = $request->raw('mot_de_passe');
        $errors = [];

        if (mb_strlen($values['nom']) < 2 || mb_strlen($values['nom']) > 100) {
            $errors['nom'] = 'Indiquez votre nom (2 à 100 caractères).';
        }
        if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false || mb_strlen($values['email']) > 191) {
            $errors['email'] = 'Indiquez une adresse email valide.';
        } elseif ($this->app->users()->emailTaken($values['email'])) {
            $errors['email'] = 'Un compte existe déjà avec cette adresse. Connectez-vous ou réinitialisez votre mot de passe.';
        }
        $problem = Passwords::problem($password, $request->raw('confirmation'), $values['email']);
        if ($problem !== null) {
            $errors['mot_de_passe'] = $problem;
        }
        if ($errors !== []) {
            return $this->registerPage($values, $errors, 422);
        }

        $id = $this->app->users()->create($values['nom'], $values['email'], Passwords::hash($password), $this->app->now()->format('Y-m-d H:i:s'));
        $this->app->auth->login((array) $this->app->users()->find($id));
        $this->flash('success', 'Bienvenue ' . $values['nom'] . ', votre compte est créé.');

        return $this->redirect($values['retour'] !== '' ? self::safeReturn($values['retour']) : '/');
    }

    public function logout(Request $request): Response
    {
        $this->app->auth->logout();
        $this->flash('success', 'Vous êtes déconnecté. À bientôt !');

        return $this->redirect('/');
    }

    private function loginPage(string $returnTo, string $email, string $error, int $status = 200): Response
    {
        return $this->render('pages/login', [
            'title' => 'Connexion',
            'returnTo' => $returnTo,
            'email' => $email,
            'error' => $error,
        ], $status);
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function registerPage(array $values, array $errors, int $status = 200): Response
    {
        return $this->render('pages/register', [
            'title' => 'Créer un compte',
            'values' => $values,
            'errors' => $errors,
        ], $status);
    }
}
