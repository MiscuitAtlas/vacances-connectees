<?php

declare(strict_types=1);

use App\App;
use App\Database;
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
$app = new App(
    $config,
    Database::connect($config['db']),
    Session::start($request->isHttps()),
    static fn (): DateTimeImmutable => new DateTimeImmutable('now'),
);
$app->handle($request)->send();
