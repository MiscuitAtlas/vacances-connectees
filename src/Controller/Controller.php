<?php

declare(strict_types=1);

namespace App\Controller;

use App\App;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

abstract class Controller
{
    public function __construct(protected readonly App $app)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $template, array $data = [], int $status = 200): Response
    {
        return new Response($this->app->view()->render($template, $data), $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect($path);
    }

    protected function flash(string $type, string $message): void
    {
        $this->app->session->flash($type, $message);
    }

    /**
     * Utilisateur connecté, ou redirection vers la connexion puis retour à la page demandée.
     *
     * @return array<string, mixed>|Response
     */
    protected function requireUser(Request $request, string $returnTo = ''): array|Response
    {
        $user = $this->app->auth->user();
        if ($user !== null) {
            return $user;
        }
        $this->flash('info', 'Connectez-vous pour continuer.');

        return $this->redirect('/connexion?' . http_build_query(['retour' => $returnTo !== '' ? $returnTo : $request->pathWithQuery()]));
    }

    /**
     * Logement appartenant à l'utilisateur connecté. Pour un autre compte, la page n'existe pas.
     *
     * @return array<string, mixed>
     */
    protected function ownedListing(int $id, int $userId): array
    {
        $listing = $this->app->listings()->find($id);
        if ($listing === null || $listing['proprietaire_id'] !== $userId) {
            throw HttpException::notFound();
        }

        return $listing;
    }

    /**
     * Adresse de retour après la connexion : un chemin du site uniquement, pour qu'un lien piégé
     * ne puisse pas renvoyer le visiteur vers un autre site.
     */
    protected static function safeReturn(string $target): string
    {
        if (
            $target === '' || $target[0] !== '/' || str_starts_with($target, '//') || str_contains($target, '\\')
            || preg_match('/[\x00-\x1F]/', $target) === 1
        ) {
            return '/compte';
        }

        return $target;
    }
}
