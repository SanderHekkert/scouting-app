<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Inertia\Inertia;

class TipperTopperOpkomstController extends Controller
{
    public function __invoke()
    {
        if (app()->bound('currentSection') && app('currentSection') === 'zeeverkenners') {
            return to_route('members.index');
        }

        return Inertia::render('TipperTopperOpkomst/Index', [
            'members' => Member::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
        ]);
    }
}
