<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Member;
use App\Models\TaskItem;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('Dashboard', [
            'upcomingEvents' => Event::query()
                ->whereDate('event_date', '>=', now()->toDateString())
                ->orderBy('event_date')
                ->limit(5)
                ->get(),
            'upcomingBirthdays' => Member::query()
                ->whereNotNull('birthday')
                ->orderByRaw("strftime('%m-%d', birthday)")
                ->limit(5)
                ->get(),
            'taskCount' => TaskItem::count(),
            'memberCount' => Member::count(),
        ]);
    }
}
