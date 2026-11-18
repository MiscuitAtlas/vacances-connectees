<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\ValidationException;
use App\Http\Request;
use App\Http\Response;
use App\Service\ListingForm;

/**
 * Espace propriétaire : annonces et réservations reçues.
 */
final class HostController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        return $this->render('pages/host', [
            'title' => 'Mes logements',
            'listings' => $this->app->listings()->byOwner((int) $user['id']),
            'bookings' => $this->app->bookings()->upcomingForOwner((int) $user['id'], $this->app->today()->format('Y-m-d')),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        return $this->formPage(null, [
            'titre' => '', 'type' => '', 'cadre' => '', 'ville' => '', 'region' => '', 'prix_nuit' => '',
            'chambres' => '1', 'capacite' => '2', 'description' => '', 'equipements' => [],
        ], []);
    }

    public function store(Request $request): Response
    {
        $user = $this->requireUser($request, '/logements/nouveau');
        if ($user instanceof Response) {
            return $user;
        }
        $values = ListingForm::values($request);
        try {
            $data = ListingForm::validate($values);
            $photo = $this->storePhoto($request);
        } catch (ValidationException $error) {
            return $this->formPage(null, $values, $error->errors, 422);
        }

        $id = $this->app->listings()->create((int) $user['id'], $data, $this->app->now()->format('Y-m-d H:i:s'));
        if ($photo !== null) {
            $this->app->listings()->setPhoto($id, $photo);
        }
        $this->flash('success', 'Votre logement est en ligne.');

        return $this->redirect('/logements/' . $id);
    }

    public function edit(Request $request, int $id): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }
        $listing = $this->ownedListing($id, (int) $user['id']);

        return $this->formPage($listing, $listing, []);
    }

    public function update(Request $request, int $id): Response
    {
        $user = $this->requireUser($request, '/logements/' . $id . '/modifier');
        if ($user instanceof Response) {
            return $user;
        }
        $listing = $this->ownedListing($id, (int) $user['id']);
        $values = ListingForm::values($request);
        try {
            $data = ListingForm::validate($values);
            $photo = $this->storePhoto($request);
        } catch (ValidationException $error) {
            return $this->formPage($listing, $values + ['id' => $id, 'photo' => $listing['photo']], $error->errors, 422);
        }

        $this->app->listings()->update($id, $data);
        if ($photo !== null || $request->input('retirer_photo') === '1') {
            $this->app->photos()->delete($listing['photo']);
            $this->app->listings()->setPhoto($id, $photo);
        }
        $this->flash('success', 'Votre annonce est mise à jour.');

        return $this->redirect('/logements/' . $id);
    }

    public function delete(Request $request, int $id): Response
    {
        $user = $this->requireUser($request, '/mes-logements');
        if ($user instanceof Response) {
            return $user;
        }
        $listing = $this->ownedListing($id, (int) $user['id']);
        if ($this->app->bookings()->hasUpcoming($id, $this->app->today()->format('Y-m-d'))) {
            $this->flash('error', 'Ce logement a des réservations à venir : il ne peut pas être supprimé avant la fin de ces séjours.');

            return $this->redirect('/mes-logements');
        }
        $this->app->photos()->delete($listing['photo']);
        $this->app->listings()->delete($id);
        $this->flash('success', 'L\'annonce « ' . $listing['titre'] . ' » est supprimée.');

        return $this->redirect('/mes-logements');
    }

    /**
     * @throws ValidationException
     */
    private function storePhoto(Request $request): ?string
    {
        $file = $request->file('photo');
        $enabled = (bool) ($this->app->config['uploads']['enabled'] ?? true);
        if (!$enabled || $file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $this->app->photos()->store($file);
    }

    /**
     * @param array<string, mixed>|null $listing
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    private function formPage(?array $listing, array $values, array $errors, int $status = 200): Response
    {
        return $this->render('pages/listing-form', [
            'title' => $listing === null ? 'Proposer un logement' : 'Modifier l\'annonce',
            'listing' => $listing,
            'values' => $values,
            'errors' => $errors,
        ], $status);
    }
}
