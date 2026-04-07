<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\UserSectionRole;
use Inertia\Inertia;

class TipperTopperOpkomstController extends Controller
{
    public function __invoke()
    {
        if (
            app()->bound('currentSection') &&
            in_array(app('currentSection'), [UserSectionRole::SECTION_ZEEVERKENNERS, UserSectionRole::SECTION_WILDE_VAART], true)
        ) {
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
