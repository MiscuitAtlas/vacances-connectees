<?php

declare(strict_types=1);

namespace App;

use App\Controller\AccountController;
use App\Controller\AuthController;
use App\Controller\BookingController;
use App\Controller\DemoController;
use App\Controller\HomeController;
use App\Controller\HostController;
use App\Controller\ListingController;
use App\Controller\PasswordController;
use App\Controller\ReviewController;
use App\Controller\SearchController;
use App\Demo\Mailbox;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repository\BookingRepository;
use App\Repository\ListingRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Security\Auth;
use App\Security\Csrf;
use App\Security\Session;
use App\Service\BookingService;
use App\Service\LoginThrottle;
use App\Service\HostNotifier;
use App\Service\Mailer;
use App\Service\PasswordReset;
use App\Service\PhotoStorage;
use App\View\View;
use Closure;
use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * Point d'entrée de l'application : reçoit une requête, renvoie une réponse.
 * Les services sont créés à la demande et partagés le temps de la requête.
 */
final class App
{
    public readonly Csrf $csrf;
    public readonly Auth $auth;
    private Router $router;
    private ?Request $request = null;

    /**
     * @var array<string, object>
     */
    private array $services = [];

    /**
     * @param array<string, mixed> $config
     * @param Closure(): DateTimeImmutable $clock
     */
    public function __construct(
        public readonly array $config,
        public readonly PDO $pdo,
        public readonly Session $session,
        private readonly Closure $clock,
    ) {
        $this->csrf = new Csrf($session);
        $this->auth = new Auth($session, $this->csrf, $this->users());
        $this->router = self::routes();
    }

    public function handle(Request $request): Response
    {
        $this->request = $request;
        try {
            if ($request->isPost() && !$this->csrf->isValid($request->input(Csrf::FIELD))) {
                throw new HttpException(400, 'Le formulaire a expiré. Rechargez la page et recommencez.');
            }
            $route = $this->router->match($request->method, $request->path);
            [$class, $method] = $route['handler'];
            $response = (new $class($this))->$method($request, ...array_values($route['params']));
        } catch (HttpException $error) {
            $response = $this->errorPage($error->status, $error->getMessage());
        } catch (Throwable $error) {
            error_log(sprintf('[%s] %s in %s:%d', $error::class, $error->getMessage(), $error->getFile(), $error->getLine()));
            $message = $this->config['env'] === 'dev' ? $error->getMessage() : 'Une erreur est survenue. Réessayez dans un instant.';
            $response = $this->errorPage(500, $message);
        }

        return $this->withSecurityHeaders($response, $request);
    }

    public function now(): DateTimeImmutable
    {
        return ($this->clock)();
    }

    public function today(): DateTimeImmutable
    {
        return $this->now()->setTime(0, 0);
    }

    public function view(): View
    {
        return new View(dirname(__DIR__) . '/templates', dirname(__DIR__) . '/public', $this->csrf, [
            'currentUser' => $this->auth->user(),
            'path' => $this->request?->path ?? '/',
            'flashes' => $this->session->takeFlashes(),
            'photosEnabled' => (bool) ($this->config['uploads']['enabled'] ?? true),
            'demo' => $this->isDemo() ? ['emails' => $this->demoMailCount()] : null,
        ]);
    }

    public function isDemo(): bool
    {
        return (bool) ($this->config['demo']['enabled'] ?? false);
    }

    /**
     * Dossier des emails de la copie du visiteur, ou null s'il n'a pas encore de copie.
     */
    public function demoMailDir(): ?string
    {
        return $this->isDemo() && ($this->config['demo']['copy'] ?? null) !== null ? (string) $this->config['mail']['log_dir'] : null;
    }

    private function demoMailCount(): int
    {
        $dir = $this->demoMailDir();

        return $dir === null ? 0 : Mailbox::count($dir);
    }

    public function users(): UserRepository
    {
        return $this->service(UserRepository::class, fn () => new UserRepository($this->pdo));
    }

    public function listings(): ListingRepository
    {
        return $this->service(ListingRepository::class, fn () => new ListingRepository($this->pdo));
    }

    public function bookings(): BookingRepository
    {
        return $this->service(BookingRepository::class, fn () => new BookingRepository($this->pdo));
    }

    public function reviews(): ReviewRepository
    {
        return $this->service(ReviewRepository::class, fn () => new ReviewRepository($this->pdo));
    }

    public function bookingService(): BookingService
    {
        return $this->service(BookingService::class, fn () => new BookingService($this->pdo, $this->bookings()));
    }

    public function throttle(): LoginThrottle
    {
        return $this->service(LoginThrottle::class, fn () => new LoginThrottle(
            $this->pdo,
            (int) $this->config['security']['max_login_failures'],
            (int) $this->config['security']['login_lock_minutes'],
        ));
    }

    public function passwordReset(): PasswordReset
    {
        return $this->service(PasswordReset::class, fn () => new PasswordReset(
            $this->pdo,
            $this->users(),
            $this->mailer(),
            $this->config['url'],
            (int) $this->config['security']['reset_token_minutes'],
        ));
    }

    public function hostNotifier(): HostNotifier
    {
        return $this->service(HostNotifier::class, fn () => new HostNotifier($this->users(), $this->mailer(), $this->config['url']));
    }

    public function mailer(): Mailer
    {
        return $this->service(Mailer::class, fn () => new Mailer(
            $this->config['mail']['transport'],
            $this->config['mail']['from'],
            $this->config['mail']['log_dir'],
        ));
    }

    public function photos(): PhotoStorage
    {
        return $this->service(PhotoStorage::class, fn () => new PhotoStorage(
            $this->config['uploads']['dir'],
            (int) $this->config['uploads']['max_bytes'],
        ));
    }

    private static function routes(): Router
    {
        $router = new Router();
        $router->get('/', [HomeController::class, 'index']);
        $router->get('/recherche', [SearchController::class, 'index']);
        $router->get('/logements/{id}', [ListingController::class, 'show']);
        $router->post('/logements/{id}/reserver', [BookingController::class, 'create']);
        $router->get('/reservations/{id}', [BookingController::class, 'show']);
        $router->post('/reservations/{id}/annuler', [BookingController::class, 'cancel']);
        $router->get('/reservations/{id}/avis', [ReviewController::class, 'form']);
        $router->post('/reservations/{id}/avis', [ReviewController::class, 'create']);

        $router->get('/connexion', [AuthController::class, 'loginForm']);
        $router->post('/connexion', [AuthController::class, 'login']);
        $router->get('/inscription', [AuthController::class, 'registerForm']);
        $router->post('/inscription', [AuthController::class, 'register']);
        $router->post('/deconnexion', [AuthController::class, 'logout']);
        $router->get('/mot-de-passe-oublie', [PasswordController::class, 'forgotForm']);
        $router->post('/mot-de-passe-oublie', [PasswordController::class, 'forgot']);
        $router->get('/reinitialisation', [PasswordController::class, 'resetForm']);
        $router->post('/reinitialisation', [PasswordController::class, 'reset']);

        $router->get('/compte', [AccountController::class, 'trips']);
        $router->get('/compte/profil', [AccountController::class, 'profile']);
        $router->post('/compte/profil', [AccountController::class, 'updateProfile']);
        $router->post('/compte/mot-de-passe', [AccountController::class, 'changePassword']);

        $router->get('/mes-logements', [HostController::class, 'index']);
        $router->get('/logements/nouveau', [HostController::class, 'create']);
        $router->post('/logements/nouveau', [HostController::class, 'store']);
        $router->get('/logements/{id}/modifier', [HostController::class, 'edit']);
        $router->post('/logements/{id}/modifier', [HostController::class, 'update']);
        $router->post('/logements/{id}/supprimer', [HostController::class, 'delete']);

        // Démo en ligne uniquement : ces adresses répondent 404 sur un site normal.
        $router->post('/demo/connexion', [DemoController::class, 'login']);
        $router->get('/demo/emails', [DemoController::class, 'emails']);

        return $router;
    }

    private function errorPage(int $status, string $message): Response
    {
        try {
            $body = $this->view()->render('errors/error', ['title' => self::errorTitle($status), 'status' => $status, 'message' => $message]);
        } catch (Throwable) {
            $body = htmlspecialchars($message, ENT_QUOTES);
        }

        return new Response($body, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    private static function errorTitle(int $status): string
    {
        return match ($status) {
            400 => 'Demande refusée',
            403 => 'Accès refusé',
            404 => 'Page introuvable',
            405 => 'Action indisponible',
            default => 'Erreur du serveur',
        };
    }

    private function withSecurityHeaders(Response $response, Request $request): Response
    {
        $response = $response
            ->withHeader('Content-Security-Policy', "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; "
                . "form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'")
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'same-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->withHeader('Cache-Control', 'no-store');
        if ($request->isHttps()) {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @param Closure(): T $factory
     * @return T
     */
    private function service(string $id, Closure $factory): object
    {
        return $this->services[$id] ??= $factory();
    }
}
