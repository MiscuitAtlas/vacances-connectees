<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Associe une méthode et un chemin à une action de contrôleur.
 * Les paramètres s'écrivent {nom} et ne contiennent que des chiffres : /logements/{id}.
 */
final class Router
{
    /**
     * @var list<array{method: string, regex: string, handler: array{0: class-string, 1: string}}>
     */
    private array $routes = [];

    /**
     * @param array{0: class-string, 1: string} $handler
     */
    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     */
    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * @return array{handler: array{0: class-string, 1: string}, params: array<string, int>}
     */
    public function match(string $method, string $path): array
    {
        $allowed = false;
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed = true;
                continue;
            }
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = (int) $value;
                }
            }

            return ['handler' => $route['handler'], 'params' => $params];
        }

        throw new HttpException($allowed ? 405 : 404);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     */
    private function add(string $method, string $path, array $handler): void
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[1-9][0-9]{0,9})', $path);
        $this->routes[] = ['method' => $method, 'regex' => '#^' . $regex . '$#', 'handler' => $handler];
    }
}
