<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Inertia\Inertia;

class TipperTopperOpkomstController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('TipperTopperOpkomst/Index', [
            'members' => Member::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
        ]);
    }
}
