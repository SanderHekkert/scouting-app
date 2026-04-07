<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Member;
use App\Models\User;
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
            'leaderCount' => User::query()->whereNotNull('first_name')->count(),
            'yearEventsCount' => $this->yearEventsCountExcludingVacation($today),
            'leaderAbsenceChart' => $this->leaderAbsenceChart($today),
        ]);
    }

    /**
     * Aantal opkomsten in huidig jaar exclusief vakantie-opkomsten.
     */
    private function yearEventsCountExcludingVacation(Carbon $today): int
    {
        return Event::query()
            ->whereYear('event_date', $today->year)
            ->where(function ($query) {
                $query
                    ->whereNull('event_type')
                    ->orWhereRaw('LOWER(event_type) NOT LIKE ?', ['%vakantie%']);
            })
            ->count();
    }

    /**
     * Aantal keer dat een leidinglid genoemd wordt bij agenda-afwezigheden (vrij tekstveld).
     * Gebruikt users.leader_name (scoutingnaam) als die via hetzelfde e-mailadres als de leider bekend is,
     * anders de bestaande herkenning op voor-/achternaam.
     *
     * @return list<array{id: int, name: string, leader_name: ?string, absence_count: int}>
     */
    private function leaderAbsenceChart(Carbon $today): array
    {
        $events = Event::query()
            ->whereDate('event_date', '<=', $today)
            ->whereNotNull('absent')
            ->pluck('absent');

        $usersByEmail = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get()
            ->keyBy(fn (User $u) => mb_strtolower(trim($u->email)));

        $rows = [];
        foreach (
            User::query()
                ->whereNotNull('first_name')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get() as $leader
        ) {
            $full = trim(implode(' ', array_filter([
                trim((string) $leader->first_name),
                trim((string) $leader->last_name),
            ])));
            $first = trim((string) $leader->first_name);
            $last = trim((string) $leader->last_name);

            $emailKey = mb_strtolower(trim((string) $leader->email));
            $scoutName = ($emailKey !== '' && isset($usersByEmail[$emailKey]))
                ? trim((string) ($usersByEmail[$emailKey]->leader_name ?? ''))
                : '';
            $scoutName = $scoutName !== '' ? $scoutName : null;

            $count = 0;
            foreach ($events as $absent) {
                $text = trim((string) $absent);
                if ($text === '') {
                    continue;
                }
                if ($this->absentTextMentionsInAgenda($text, $scoutName, $full, $first, $last)) {
                    $count++;
                }
            }

            $realName = trim(($leader->first_name ?? '').' '.($leader->last_name ?? '')) ?: ($leader->name ?: ('Leiding #'.$leader->id));
            $chartLabel = $scoutName ?? $realName;

            $rows[] = [
                'id' => $leader->id,
                'name' => $chartLabel,
                'leader_name' => $scoutName,
                'real_name' => $realName,
                'absence_count' => $count,
            ];
        }

        usort($rows, function (array $a, array $b) {
            if ($a['absence_count'] !== $b['absence_count']) {
                return $b['absence_count'] <=> $a['absence_count'];
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $rows;
    }

    /**
     * Eén match per opkomst: eerst scoutingnaam (heel woord), anders burgerlijke naam-logica.
     */
    private function absentTextMentionsInAgenda(
        string $absent,
        ?string $scoutName,
        string $full,
        string $first,
        string $last,
    ): bool {
        if ($scoutName !== null && $scoutName !== '') {
            if (preg_match('/\b'.preg_quote($scoutName, '/').'\b/iu', $absent)) {
                return true;
            }
        }

        return $this->absentTextMentionsLeader($absent, $full, $first, $last);
    }

    private function absentTextMentionsLeader(string $absent, string $full, string $first, string $last): bool
    {
        if ($full !== '' && stripos($absent, $full) !== false) {
            return true;
        }

        if ($first === '') {
            return false;
        }

        if (! preg_match('/\b'.preg_quote($first, '/').'\b/iu', $absent)) {
            return false;
        }

        if ($last === '') {
            return true;
        }

        return stripos($absent, $last) !== false;
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

        foreach (User::query()->whereNotNull('first_name')->whereNotNull('birthday')->cursor() as $leader) {
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
