<?php

declare(strict_types=1);

/*
 * Exemple de configuration locale : copier ce fichier en config.local.php et l'adapter.
 * Seules les valeurs à changer sont nécessaires.
 */

return [
    'env' => 'dev',
    'url' => 'http://localhost:8000',
    'db' => [
        // MySQL ou MariaDB
        'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=vacances_connectees;charset=utf8mb4',
        'user' => 'vacances',
        'password' => 'mot de passe choisi pour la base',

        // Ou SQLite, sans serveur de base de données :
        // 'dsn' => 'sqlite:' . dirname(__DIR__) . '/var/vacances.sqlite',
    ],
];
