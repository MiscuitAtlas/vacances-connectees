<?php

declare(strict_types=1);

namespace App\Demo;

use App\Http\Request;
use App\Security\Csrf;
use App\Security\Session;
use DateTimeImmutable;

/**
 * Prépare une requête de la démo en ligne : choisit la base du visiteur et adapte la configuration.
 */
final class DemoSite
{
    public function __construct(private readonly Sandboxes $sandboxes)
    {
    }

    /**
     * @param array<string, mixed> $config
     * @return array{config: array<string, mixed>, newCopy: string|null}
     */
    public function prepare(array $config, Request $request, Session $session, DateTimeImmutable $now): array
    {
        $copy = $this->sandboxes->find($request->cookie(Sandboxes::COOKIE), $now);

        // La copie du visiteur a expiré : ses comptes et ses réservations n'existent plus,
        // sa connexion non plus.
        $previous = $session->get('_demo');
        if (is_string($previous) && $previous !== '' && $previous !== $copy) {
            $session->clear();
            $session->flash('info', 'Votre copie de la démo a expiré : le site est revenu à son état de départ.');
        }

        // Une copie n'est créée qu'au premier formulaire valable. Un robot qui parcourt les pages,
        // sans cookie ni jeton, lit seulement la base modèle.
        $newCopy = null;
        if ($copy === null && $request->isPost() && (new Csrf($session))->isValid($request->input(Csrf::FIELD))) {
            $copy = $newCopy = $this->sandboxes->create($now);
        }
        $session->set('_demo', $copy ?? '');

        if ($copy === null) {
            $config['db'] = ['dsn' => 'sqlite:' . $this->sandboxes->template($now), 'readonly' => true];
        } else {
            $config['db'] = ['dsn' => 'sqlite:' . $this->sandboxes->database($copy)];
            $config['mail']['log_dir'] = $this->sandboxes->mailDir($copy);
        }
        $config['mail']['transport'] = 'log';
        // Les photos envoyées seraient visibles par tous à leur adresse : la démo ne les accepte pas.
        $config['uploads']['enabled'] = false;
        $config['demo']['copy'] = $copy;

        return ['config' => $config, 'newCopy' => $newCopy];
    }
}
