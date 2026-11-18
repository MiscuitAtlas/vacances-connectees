<?php

declare(strict_types=1);

/*
 * Création de la base : php bin/install.php [--fresh] [--demo]
 *
 *   --fresh  supprime d'abord les tables existantes
 *   --demo   ajoute les données de démonstration (comptes, logements, séjours, avis)
 */

use App\Database;
use App\Demo\DemoData;

$config = require dirname(__DIR__) . '/src/bootstrap.php';

$options = array_slice($argv, 1);
$unknown = array_diff($options, ['--fresh', '--demo']);
if ($unknown !== []) {
    fwrite(STDERR, 'Option inconnue : ' . implode(' ', $unknown) . "\nUsage : php bin/install.php [--fresh] [--demo]\n");
    exit(1);
}

$pdo = Database::connect($config['db']);

if (in_array('--fresh', $options, true)) {
    if (!Database::isSqlite($pdo)) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    }
    foreach (['echecs_connexion', 'jetons_reinitialisation', 'avis', 'reservations', 'logements', 'utilisateurs'] as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
    }
    if (!Database::isSqlite($pdo)) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    echo "Tables supprimées.\n";
}

try {
    $pdo->query('SELECT 1 FROM utilisateurs LIMIT 1');
    fwrite(STDERR, "Les tables existent déjà. Pour repartir d'une base vide : php bin/install.php --fresh\n");
    exit(1);
} catch (PDOException) {
    // Base vide : on crée les tables.
}

Database::createSchema($pdo);
echo "Tables créées.\n";

if (in_array('--demo', $options, true)) {
    DemoData::load($pdo, new DateTimeImmutable('today'));
    printf(
        "Données de démonstration ajoutées. Connexion : demo@exemple.fr (voyageuse) ou hote@exemple.fr (propriétaire), mot de passe %s\n",
        DemoData::PASSWORD,
    );
}
