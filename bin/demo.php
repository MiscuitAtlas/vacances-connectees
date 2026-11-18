<?php

declare(strict_types=1);

/*
 * Démo en ligne : prépare la base modèle du jour. Lancé au démarrage du conteneur Docker,
 * pour que la première visite n'attende pas sa création.
 */

use App\Demo\Sandboxes;

$config = require dirname(__DIR__) . '/src/bootstrap.php';

$sandboxes = new Sandboxes($config['demo']['dir'], $config['demo']['lifetime'], $config['demo']['max_copies']);
echo 'Base modèle de la démo : ' . $sandboxes->template(new DateTimeImmutable()) . "\n";
