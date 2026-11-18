<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\ValidationException;
use App\Http\Request;
use App\Http\Response;

final class PasswordController extends Controller
{
    private const SESSION_KEY = 'reinitialisation';

    public function forgotForm(Request $request): Response
    {
        return $this->render('pages/forgot', ['title' => 'Mot de passe oublié', 'email' => '', 'error' => '']);
    }

    public function forgot(Request $request): Response
    {
        $email = $request->input('email');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->render('pages/forgot', [
                'title' => 'Mot de passe oublié',
                'email' => $email,
                'error' => 'Indiquez une adresse email valide.',
            ], 422);
        }
        $this->app->passwordReset()->request($email, $this->app->now());
        // Même message que le compte existe ou non.
        $this->flash('info', 'Si un compte existe pour cette adresse, un email vient de lui être envoyé avec un lien valable 30 minutes.');

        return $this->redirect('/connexion');
    }

    public function resetForm(Request $request): Response
    {
        $token = $request->query('jeton');
        if ($token !== '') {
            // Le jeton quitte l'adresse aussitôt : il ne reste ni dans l'historique ni dans les journaux.
            $hash = $this->app->passwordReset()->validate($token, $this->app->now());
            $hash === null ? $this->app->session->remove(self::SESSION_KEY) : $this->app->session->set(self::SESSION_KEY, $hash);

            return $this->redirect('/reinitialisation');
        }

        return $this->resetPage('');
    }

    public function reset(Request $request): Response
    {
        $hash = $this->app->session->get(self::SESSION_KEY);
        if (!is_string($hash)) {
            return $this->resetPage('');
        }
        try {
            $this->app->passwordReset()->reset($hash, $request->raw('mot_de_passe'), $request->raw('confirmation'), $this->app->now());
        } catch (ValidationException $error) {
            return $this->resetPage(implode(' ', $error->errors), 422);
        }
        $this->app->session->remove(self::SESSION_KEY);
        $this->flash('success', 'Votre mot de passe est changé. Vous pouvez vous connecter.');

        return $this->redirect('/connexion');
    }

    private function resetPage(string $error, int $status = 200): Response
    {
        return $this->render('pages/reset', [
            'title' => 'Nouveau mot de passe',
            'valid' => is_string($this->app->session->get(self::SESSION_KEY)),
            'error' => $error,
        ], $status);
    }
}
