<?php

declare(strict_types=1);

use App\App;
use App\Database;
use App\Demo\DemoSite;
use App\Demo\Sandboxes;
use App\Http\Request;
use App\Security\Session;

// Serveur intégré de PHP : les fichiers statiques sont servis directement.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

$config = require dirname(__DIR__) . '/src/bootstrap.php';

$request = Request::fromGlobals();
$session = Session::start($request->isHttps());

if ($config['demo']['enabled']) {
    $sandboxes = new Sandboxes($config['demo']['dir'], $config['demo']['lifetime'], $config['demo']['max_copies']);
    ['config' => $config, 'newCopy' => $newCopy] = (new DemoSite($sandboxes))->prepare($config, $request, $session, new DateTimeImmutable());
    if ($newCopy !== null) {
        setcookie(Sandboxes::COOKIE, $newCopy, [
            'expires' => $sandboxes->expiresAt($newCopy)->getTimestamp(),
            'path' => '/',
            'secure' => $request->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

$app = new App(
    $config,
    Database::connect($config['db']),
    $session,
    static fn (): DateTimeImmutable => new DateTimeImmutable('now'),
);
$app->handle($request)->send();
