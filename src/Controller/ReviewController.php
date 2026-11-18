<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Service\BookingService;

final class ReviewController extends Controller
{
    public function form(Request $request, int $id): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        return $this->page($this->reviewableBooking($id, (int) $user['id']), ['note' => '', 'commentaire' => ''], []);
    }

    public function create(Request $request, int $id): Response
    {
        $user = $this->requireUser($request, '/reservations/' . $id . '/avis');
        if ($user instanceof Response) {
            return $user;
        }
        $booking = $this->reviewableBooking($id, (int) $user['id']);
        $values = ['note' => $request->input('note'), 'commentaire' => $request->input('commentaire')];

        $errors = [];
        $rating = filter_var($values['note'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        if ($rating === false) {
            $errors['note'] = 'Choisissez une note de 1 à 5.';
        }
        $comment = trim(str_replace("\r\n", "\n", $values['commentaire']));
        if (mb_strlen($comment) < 10 || mb_strlen($comment) > 1000) {
            $errors['commentaire'] = 'Votre avis : entre 10 et 1000 caractères.';
        }
        if ($errors !== []) {
            return $this->page($booking, $values, $errors, 422);
        }

        $this->app->reviews()->create(
            $booking['id'],
            $booking['logement_id'],
            (int) $user['id'],
            (int) $rating,
            $comment,
            $this->app->now()->format('Y-m-d H:i:s'),
        );
        $this->flash('success', 'Merci, votre avis est publié.');

        return $this->redirect('/logements/' . $booking['logement_id'] . '#avis');
    }

    /**
     * @param array<string, mixed> $booking
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function page(array $booking, array $values, array $errors, int $status = 200): Response
    {
        return $this->render('pages/review', [
            'title' => 'Donner votre avis',
            'booking' => $booking,
            'values' => $values,
            'errors' => $errors,
        ], $status);
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewableBooking(int $id, int $userId): array
    {
        $booking = $this->app->bookings()->find($id);
        if ($booking === null || $booking['voyageur_id'] !== $userId) {
            throw HttpException::notFound();
        }
        if (!BookingService::canReview($booking, $this->app->today())) {
            throw new HttpException(403, $booking['a_un_avis']
                ? 'Vous avez déjà donné votre avis sur ce séjour.'
                : 'Vous pourrez donner votre avis après votre départ.');
        }

        return $booking;
    }
}
