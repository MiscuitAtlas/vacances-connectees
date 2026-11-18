<?php

declare(strict_types=1);

/*
 * Configuration par défaut.
 *
 * Chaque valeur peut venir d'une variable d'environnement. Pour une installation locale,
 * copier config.local.example.php en config.local.php : ce fichier n'est pas versionné.
 */

$env = static fn (string $name, ?string $default = null): ?string =>
    ($value = getenv($name)) !== false && $value !== '' ? $value : $default;

$config = [
    // dev : erreurs détaillées. prod : page d'erreur générique, détail dans var/log.
    'env' => $env('APP_ENV', 'prod'),

    // Adresse publique du site, utilisée dans les emails. Jamais déduite de l'en-tête Host.
    'url' => rtrim((string) $env('APP_URL', 'http://localhost:8000'), '/'),

    'db' => [
        'dsn' => $env('DB_DSN', 'mysql:host=127.0.0.1;port=3306;dbname=vacances_connectees;charset=utf8mb4'),
        'user' => $env('DB_USER', 'root'),
        'password' => $env('DB_PASSWORD', ''),
    ],

    'mail' => [
        // log : les emails sont écrits dans var/mail au lieu d'être envoyés. php : fonction mail().
        'transport' => $env('MAIL_TRANSPORT', 'log'),
        'from' => $env('MAIL_FROM', 'VacancesConnectées <ne-pas-repondre@vacances-connectees.test>'),
        'log_dir' => dirname(__DIR__) . '/var/mail',
    ],

    'security' => [
        // Connexion bloquée après ce nombre d'échecs pour un même email, pendant la durée indiquée.
        'max_login_failures' => 5,
        'login_lock_minutes' => 15,
        // Durée de validité d'un lien de réinitialisation du mot de passe.
        'reset_token_minutes' => 30,
    ],

    'uploads' => [
        'enabled' => true,
        'dir' => dirname(__DIR__) . '/public/uploads/logements',
        'url' => '/uploads/logements',
        'max_bytes' => 5 * 1024 * 1024,
    ],

    // Démo en ligne : chaque visiteur reçoit sa propre copie d'une base SQLite de démonstration,
    // supprimée au bout de deux heures. Voir src/Demo/Sandboxes.php.
    'demo' => [
        'enabled' => filter_var($env('DEMO_MODE', 'false'), FILTER_VALIDATE_BOOL),
        'dir' => dirname(__DIR__) . '/var/demo',
        'lifetime' => 2 * 3600,
        'max_copies' => 200,
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
