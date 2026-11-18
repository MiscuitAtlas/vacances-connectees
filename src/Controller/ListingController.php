<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Stay;
use App\Domain\ValidationException;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\View\Calendar;

final class ListingController extends Controller
{
    public function show(Request $request, int $id): Response
    {
        $listing = $this->app->listings()->find($id) ?? throw HttpException::notFound();
        $values = [
            'arrivee' => $request->query('arrivee'),
            'depart' => $request->query('depart'),
            'voyageurs' => $request->query('voyageurs', '2'),
        ];

        return $this->page($request, $listing, $values, []);
    }

    /**
     * Fiche du logement, avec le formulaire de réservation pré-rempli (et ses erreurs éventuelles).
     *
     * @param array<string, mixed> $listing
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    public function page(Request $request, array $listing, array $values, array $errors, int $status = 200): Response
    {
        $today = $this->app->today();
        $quote = null;
        if ($errors === [] && $values['arrivee'] !== '' && $values['depart'] !== '') {
            try {
                $stay = Stay::forBooking($values['arrivee'], $values['depart'], $today);
                if ($this->app->bookings()->overlaps($listing['id'], $stay->arrivalString(), $stay->departureString())) {
                    $errors['arrivee'] = 'Ces dates sont déjà réservées : choisissez une autre période.';
                } else {
                    $quote = ['nights' => $stay->nights(), 'total' => $stay->nights() * $listing['prix_nuit']];
                }
            } catch (ValidationException $error) {
                $errors = $error->errors;
            }
        }

        $firstMonth = Stay::parse($request->query('mois') . '-01') ?? $today;
        if ($firstMonth < $today->modify('first day of this month')) {
            $firstMonth = $today;
        }
        $firstMonth = $firstMonth->modify('first day of this month');
        $periods = $this->app->bookings()->bookedPeriods(
            $listing['id'],
            $firstMonth->format('Y-m-d'),
            $firstMonth->modify('+2 months')->format('Y-m-d'),
        );

        return $this->render('pages/listing', [
            'title' => $listing['titre'],
            'listing' => $listing,
            'reviews' => $this->app->reviews()->forListing($listing['id']),
            'values' => $values,
            'errors' => $errors,
            'quote' => $quote,
            'isOwner' => $this->app->auth->id() === $listing['proprietaire_id'],
            'months' => Calendar::months($firstMonth, 2, $periods, $today),
            'previousMonth' => $firstMonth > $today->modify('first day of this month') ? $firstMonth->modify('-1 month')->format('Y-m') : null,
            'nextMonth' => $firstMonth->modify('+1 month')->format('Y-m'),
            'today' => $today->format('Y-m-d'),
            'maxDate' => $today->modify('+' . Stay::MAX_ADVANCE_DAYS . ' days')->format('Y-m-d'),
        ], $status);
    }
}
