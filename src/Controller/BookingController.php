<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Stay;
use App\Domain\ValidationException;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Service\BookingService;

final class BookingController extends Controller
{
    public function create(Request $request, int $id): Response
    {
        $listing = $this->app->listings()->find($id) ?? throw HttpException::notFound();
        $values = [
            'arrivee' => $request->input('arrivee'),
            'depart' => $request->input('depart'),
            'voyageurs' => $request->input('voyageurs'),
        ];
        // Un visiteur non connecté retrouve ses dates sur la fiche après s'être connecté.
        $user = $this->requireUser($request, '/logements/' . $id . '?' . http_build_query($values));
        if ($user instanceof Response) {
            return $user;
        }

        try {
            $stay = Stay::forBooking($values['arrivee'], $values['depart'], $this->app->today());
            $guests = filter_var($values['voyageurs'], FILTER_VALIDATE_INT);
            $bookingId = $this->app->bookingService()->book($listing, (int) $user['id'], $stay, $guests === false ? 0 : $guests, $this->app->now());
        } catch (ValidationException $error) {
            return (new ListingController($this->app))->page($request, $listing, $values, $error->errors, 422);
        }
        $this->app->hostNotifier()->booked((array) $this->app->bookings()->find($bookingId));
        $this->flash('success', 'Réservation confirmée. Bon séjour !');

        return $this->redirect('/reservations/' . $bookingId);
    }

    public function show(Request $request, int $id): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }
        $booking = $this->visibleBooking($id, (int) $user['id']);
        $today = $this->app->today();
        $isTraveller = $booking['voyageur_id'] === (int) $user['id'];

        return $this->render('pages/booking', [
            'title' => 'Réservation : ' . $booking['titre'],
            'booking' => $booking,
            'listing' => $this->app->listings()->find($booking['logement_id']),
            'nights' => Stay::fromStrings($booking['arrivee'], $booking['depart'])->nights(),
            'isTraveller' => $isTraveller,
            'canCancel' => $isTraveller && $booking['statut'] === 'confirmee' && $booking['arrivee'] > $today->format('Y-m-d'),
            'canReview' => $isTraveller && BookingService::canReview($booking, $today),
        ]);
    }

    public function cancel(Request $request, int $id): Response
    {
        $user = $this->requireUser($request, '/reservations/' . $id);
        if ($user instanceof Response) {
            return $user;
        }
        $booking = $this->visibleBooking($id, (int) $user['id']);
        if ($booking['voyageur_id'] !== (int) $user['id']) {
            throw HttpException::forbidden();
        }
        try {
            $this->app->bookingService()->cancel($booking, $this->app->today());
            $this->app->hostNotifier()->cancelled($booking);
            $this->flash('success', 'Réservation annulée. Les dates sont de nouveau libres.');
        } catch (ValidationException $error) {
            $this->flash('error', implode(' ', $error->errors));
        }

        return $this->redirect('/reservations/' . $id);
    }

    /**
     * Réservation visible par le voyageur et par le propriétaire du logement, par personne d'autre.
     *
     * @return array<string, mixed>
     */
    private function visibleBooking(int $id, int $userId): array
    {
        $booking = $this->app->bookings()->find($id);
        if ($booking === null || ($booking['voyageur_id'] !== $userId && $booking['proprietaire_id'] !== $userId)) {
            throw HttpException::notFound();
        }

        return $booking;
    }
}
