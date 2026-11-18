<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->render('pages/home', [
            'title' => '',
            'listings' => $this->app->listings()->topRated(6),
            'regions' => array_slice($this->app->listings()->regions(), 0, 8),
            'search' => ['destination' => '', 'arrivee' => '', 'depart' => '', 'voyageurs' => 2],
            'today' => $this->app->today()->format('Y-m-d'),
        ]);
    }
}
