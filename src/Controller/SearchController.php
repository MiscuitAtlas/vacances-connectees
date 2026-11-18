<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Catalog;
use App\Domain\Stay;
use App\Domain\ValidationException;
use App\Http\Request;
use App\Http\Response;

final class SearchController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request): Response
    {
        $errors = [];
        $search = [
            'destination' => mb_substr($request->query('destination'), 0, 100),
            'arrivee' => $request->query('arrivee'),
            'depart' => $request->query('depart'),
            'voyageurs' => self::intBetween($request->query('voyageurs'), 1, 30, 1),
            'chambres' => self::intBetween($request->query('chambres'), 0, 20, 0),
            'prix_max' => $request->query('prix_max') === '' ? null : self::intBetween($request->query('prix_max'), 10, 5000, 5000),
            'tri' => isset(Catalog::SORTS[$request->query('tri')]) ? $request->query('tri') : 'note',
        ];

        // Les dates ne filtrent les disponibilités que si elles forment un séjour valable.
        $nights = null;
        if ($search['arrivee'] !== '' || $search['depart'] !== '') {
            try {
                $stay = Stay::fromStrings($search['arrivee'], $search['depart']);
                if ($stay->arrival < $this->app->today()) {
                    throw ValidationException::field('arrivee', 'La date d\'arrivée est déjà passée.');
                }
                $nights = $stay->nights();
            } catch (ValidationException $error) {
                $errors = $error->errors;
            }
        }
        $criteria = $search;
        if ($nights === null) {
            $criteria['arrivee'] = '';
            $criteria['depart'] = '';
        }

        $page = self::intBetween($request->query('page'), 1, 1000, 1);
        $results = $this->app->listings()->search($criteria, $page, self::PER_PAGE);

        return $this->render('pages/search', [
            'title' => 'Rechercher un logement',
            'search' => $search,
            'errors' => $errors,
            'nights' => $nights,
            'listings' => $results['items'],
            'total' => $results['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($results['total'] / self::PER_PAGE)),
            'today' => $this->app->today()->format('Y-m-d'),
        ]);
    }

    private static function intBetween(string $value, int $min, int $max, int $default): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);

        return $number === false ? $default : max($min, min($max, $number));
    }
}
