<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Leader;
use App\Models\Member;
use App\Models\TaskItem;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = Carbon::today();

        $todayEvents = Event::query()
            ->whereDate('event_date', $today)
            ->orderBy('theme')
            ->get()
            ->map(fn (Event $e) => $this->serializeEvent($e, $today));

        $upcomingEvents = Event::query()
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date')
            ->orderBy('theme')
            ->limit(5)
            ->get()
            ->map(fn (Event $e) => $this->serializeEvent($e, $today));

        return Inertia::render('Dashboard', [
            'todayEvents' => $todayEvents,
            'upcomingEvents' => $upcomingEvents,
            'upcomingBirthdays' => $this->upcomingBirthdays($today),
            'memberCount' => Member::count(),
            'leaderCount' => Leader::count(),
            'taskCount' => TaskItem::count(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEvent(Event $event, Carbon $today): array
    {
        $date = Carbon::parse($event->event_date)->startOfDay();

        return [
            'id' => $event->id,
            'theme' => $event->theme,
            'event_date' => $date->toDateString(),
            'weekday' => $this->dutchWeekdayShort($date),
            'day_month' => $date->format('d-m'),
            'event_type' => $event->event_type,
            'program_by' => $event->program_by,
            'activity' => $event->activity,
            'is_today' => $date->equalTo($today),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function upcomingBirthdays(Carbon $today): array
    {
        $rows = collect();

        foreach (Member::query()->whereNotNull('birthday')->cursor() as $member) {
            $birthday = Carbon::parse($member->birthday);
            $next = $this->nextBirthdayDate($birthday, $today);
            $rows->push($this->serializeBirthdayRow(
                'member',
                $member->id,
                $member->first_name,
                $member->last_name,
                $birthday,
                $next,
                $today
            ));
        }

        foreach (Leader::query()->whereNotNull('birthday')->cursor() as $leader) {
            $birthday = Carbon::parse($leader->birthday);
            $next = $this->nextBirthdayDate($birthday, $today);
            $rows->push($this->serializeBirthdayRow(
                'leader',
                $leader->id,
                $leader->first_name,
                $leader->last_name,
                $birthday,
                $next,
                $today
            ));
        }

        return $rows
            ->sortBy(fn (array $r) => ($r['next_date'] ?? '').'_'.($r['kind'] ?? '').'_'.($r['last_name'] ?? '').'_'.($r['first_name'] ?? ''))
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeBirthdayRow(
        string $kind,
        int $id,
        string $firstName,
        ?string $lastName,
        Carbon $birthday,
        Carbon $next,
        Carbon $today
    ): array {
        return [
            'kind' => $kind,
            'id' => $id,
            'first_name' => $firstName,
            'last_name' => $lastName ?? '',
            'birthday' => $birthday->toDateString(),
            'next_date' => $next->toDateString(),
            'weekday' => $this->dutchWeekdayShort($next),
            'day_month' => $next->format('d-m'),
            'days_until' => (int) $today->diffInDays($next, false),
            'when_label' => $this->relativeDayLabel($today, $next),
        ];
    }

    private function relativeDayLabel(Carbon $today, Carbon $target): string
    {
        $t = $today->copy()->startOfDay();
        $d = $target->copy()->startOfDay();

        if ($d->equalTo($t)) {
            return 'Vandaag';
        }

        if ($d->equalTo($t->copy()->addDay())) {
            return 'Morgen';
        }

        $n = (int) $t->diffInDays($d, false);

        return "Over {$n} dagen";
    }

    private function nextBirthdayDate(Carbon $birthday, Carbon $today): Carbon
    {
        $next = Carbon::create($today->year, $birthday->month, $birthday->day)->startOfDay();
        if ($next->lt($today)) {
            $next->addYear();
        }

        return $next;
    }

    private function dutchWeekdayShort(Carbon $date): string
    {
        $map = [
            0 => 'zo',
            1 => 'ma',
            2 => 'di',
            3 => 'wo',
            4 => 'do',
            5 => 'vr',
            6 => 'za',
        ];

        return $map[(int) $date->format('w')];
    }
}
