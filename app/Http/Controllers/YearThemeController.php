<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class YearThemeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('YearThemes/Index', [
            'rows' => [
                [
                    'label' => 'Seizoensthema:',
                    'value' => 'Wereldreis door de continenten',
                ],
                [
                    'label' => 'Pinksterkamp thema:',
                    'value' => 'Hollywood?',
                ],
                [
                    'label' => 'Zomerkamp thema:',
                    'value' => 'Nawaka',
                ],
            ],
        ]);
    }
}
