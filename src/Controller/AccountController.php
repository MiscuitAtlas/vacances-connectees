<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Repository\BookingRepository;
use App\Service\Passwords;

final class AccountController extends Controller
{
    public function trips(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }
        $today = $this->app->today()->format('Y-m-d');
        $groups = ['upcoming' => [], 'past' => [], 'cancelled' => []];
        foreach ($this->app->bookings()->forTraveller((int) $user['id']) as $booking) {
            $group = match (true) {
                $booking['statut'] !== BookingRepository::CONFIRMED => 'cancelled',
                $booking['depart'] > $today => 'upcoming',
                default => 'past',
            };
            $groups[$group][] = $booking;
        }
        // Les prochains séjours d'abord, dans l'ordre chronologique.
        $groups['upcoming'] = array_reverse($groups['upcoming']);

        return $this->render('pages/trips', ['title' => 'Mes voyages', 'groups' => $groups, 'today' => $today]);
    }

    public function profile(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        return $this->profilePage(['nom' => (string) $user['nom'], 'email' => (string) $user['email']], []);
    }

    public function updateProfile(Request $request): Response
    {
        $user = $this->requireUser($request, '/compte/profil');
        if ($user instanceof Response) {
            return $user;
        }
        $values = ['nom' => preg_replace('/\s+/u', ' ', $request->input('nom')) ?? '', 'email' => $request->input('email')];
        $errors = [];
        if (mb_strlen($values['nom']) < 2 || mb_strlen($values['nom']) > 100) {
            $errors['nom'] = 'Indiquez votre nom (2 à 100 caractères).';
        }
        if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false || mb_strlen($values['email']) > 191) {
            $errors['email'] = 'Indiquez une adresse email valide.';
        } elseif ($this->app->users()->emailTaken($values['email'], (int) $user['id'])) {
            $errors['email'] = 'Cette adresse est déjà utilisée par un autre compte.';
        }
        if ($errors !== []) {
            return $this->profilePage($values, $errors, 422);
        }
        $this->app->users()->updateProfile((int) $user['id'], $values['nom'], $values['email']);
        $this->app->auth->refresh();
        $this->flash('success', 'Votre profil est enregistré.');

        return $this->redirect('/compte/profil');
    }

    public function changePassword(Request $request): Response
    {
        $user = $this->requireUser($request, '/compte/profil');
        if ($user instanceof Response) {
            return $user;
        }
        $values = ['nom' => (string) $user['nom'], 'email' => (string) $user['email']];
        if (!password_verify($request->raw('actuel'), (string) $user['mot_de_passe'])) {
            return $this->profilePage($values, ['actuel' => 'Le mot de passe actuel est incorrect.'], 422);
        }
        $problem = Passwords::problem($request->raw('mot_de_passe'), $request->raw('confirmation'), (string) $user['email']);
        if ($problem !== null) {
            return $this->profilePage($values, ['mot_de_passe' => $problem], 422);
        }
        $this->app->users()->updatePassword((int) $user['id'], Passwords::hash($request->raw('mot_de_passe')));
        $this->app->session->regenerate();
        $this->flash('success', 'Votre mot de passe est changé.');

        return $this->redirect('/compte/profil');
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function profilePage(array $values, array $errors, int $status = 200): Response
    {
        return $this->render('pages/profile', ['title' => 'Mon profil', 'values' => $values, 'errors' => $errors], $status);
    }
}
