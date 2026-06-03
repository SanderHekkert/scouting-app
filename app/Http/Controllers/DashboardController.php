<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Member;
use App\Models\TaskItem;
use App\Models\User;
use App\Models\UserSectionRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = Carbon::today();
        $section = $this->activeSection();

        $todayEvents = Event::withoutGlobalScope('section')
            ->where(function (Builder $query) use ($section): void {
                $query->where('section', $section)
                    ->orWhereJsonContains('shared_sections', $section);
            })
            ->whereDate('event_date', $today)
            ->orderBy('theme', 'asc')
            ->get()
            ->map(fn (Event $e) => $this->serializeEvent($e, $today));

        $upcomingEvents = Event::withoutGlobalScope('section')
            ->where(function (Builder $query) use ($section): void {
                $query->where('section', $section)
                    ->orWhereJsonContains('shared_sections', $section);
            })
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date', 'asc')
            ->orderBy('theme', 'asc')
            ->limit(3)
            ->get()
            ->map(fn (Event $e) => $this->serializeEvent($e, $today));

        return Inertia::render('Dashboard', [
            'todayEvents' => $todayEvents,
            'upcomingEvents' => $upcomingEvents,
            'upcomingBirthdays' => $this->upcomingBirthdays($today),
            'memberCount' => (int) Member::query()->count('*'),
            'leaderCount' => (int) $this->scopedLeadersQuery()->count('*'),
            'nextUpcomingAttendance' => $this->nextUpcomingAttendanceState($today),
            'leaderAbsenceChart' => $this->leaderAbsenceChart($today),
            'myTaskDeadlines' => $this->myTasks($today),
        ]);
    }

    public function updateUpcomingAttendance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'present' => ['required', 'boolean'],
        ]);

        $today = Carbon::today();
        $section = $this->activeSection();
        $nextEvent = Event::withoutGlobalScope('section')
            ->where(function (Builder $query) use ($section): void {
                $query->where('section', $section)
                    ->orWhereJsonContains('shared_sections', $section);
            })
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date', 'asc')
            ->orderBy('theme', 'asc')
            ->first();

        if (! $nextEvent) {
            return back();
        }

        $leaderName = $this->currentLeaderName(Auth::user());
        if ($leaderName === null) {
            return back();
        }

        $names = $this->splitAbsentNames((string) ($nextEvent->absent ?? ''));
        $present = (bool) $data['present'];

        if ($present) {
            $names = array_values(array_filter($names, fn (string $n): bool => mb_strtolower($n) !== mb_strtolower($leaderName)));
        } else {
            $exists = collect($names)->contains(fn (string $n): bool => mb_strtolower($n) === mb_strtolower($leaderName));
            if (! $exists) {
                $names[] = $leaderName;
            }
        }

        $nextEvent->update([
            'absent' => $this->joinAbsentNames($names),
        ]);

        return back();
    }

    /**
     * @return array{event_id:int,event_theme:string,event_date:string,is_absent:bool,leader_name:string}|null
     */
    private function nextUpcomingAttendanceState(Carbon $today): ?array
    {
        $section = $this->activeSection();
        $nextEvent = Event::withoutGlobalScope('section')
            ->where(function (Builder $query) use ($section): void {
                $query->where('section', $section)
                    ->orWhereJsonContains('shared_sections', $section);
            })
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date', 'asc')
            ->orderBy('theme', 'asc')
            ->first();
        $currentUser = Auth::user();
        if (! $nextEvent || ! $currentUser) {
            return null;
        }

        $leaderName = $this->currentLeaderName($currentUser);
        if ($leaderName === null) {
            return null;
        }

        $names = $this->splitAbsentNames((string) ($nextEvent->absent ?? ''));
        $isAbsent = collect($names)->contains(fn (string $n): bool => mb_strtolower($n) === mb_strtolower($leaderName));

        return [
            'event_id' => $nextEvent->id,
            'event_theme' => (string) ($nextEvent->theme ?? ''),
            'event_date' => Carbon::parse($nextEvent->event_date)->toDateString(),
            'is_absent' => $isAbsent,
            'leader_name' => $leaderName,
        ];
    }

    /**
     * Aantal keer dat een leidinglid genoemd wordt bij agenda-afwezigheden (vrij tekstveld).
     *
     * @return list<array{id: int, name: string, real_name: string, absence_count: int}>
     */
    private function leaderAbsenceChart(Carbon $today): array
    {
        $section = $this->activeSection();
        $events = Event::withoutGlobalScope('section')
            ->where(function (Builder $query) use ($section): void {
                $query->where('section', $section)
                    ->orWhereJsonContains('shared_sections', $section);
            })
            ->whereNotNull('absent', 'and')
            ->pluck('absent');

        $leaders = $this->scopedLeadersQuery()
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get();

        $rows = [];
        foreach ($leaders as $leader) {
            $full = trim(implode(' ', array_filter([
                trim((string) $leader->first_name),
                trim((string) $leader->last_name),
            ])));
            $first = trim((string) $leader->first_name);
            $last = trim((string) $leader->last_name);

            $count = 0;
            foreach ($events as $absent) {
                $text = trim((string) $absent);
                if ($text === '') {
                    continue;
                }
                if ($this->absentTextMentionsLeader($text, $full, $first, $last)) {
                    $count++;
                }
            }

            $realName = trim(($leader->first_name ?? '').' '.($leader->last_name ?? '')) ?: ($leader->name ?: ('Leiding #'.$leader->id));
            $chartLabel = $realName;

            $rows[] = [
                'id' => $leader->id,
                'name' => $chartLabel,
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

    private function absentTextMentionsLeader(string $absent, string $full, string $first, string $last): bool
    {
        $items = collect($this->splitAbsentNames($absent))
            ->map(fn (string $name): string => $this->normalizePersonName($name))
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            return false;
        }

        $fullNorm = $this->normalizePersonName($full);
        $firstNorm = $this->normalizePersonName($first);
        $lastNorm = $this->normalizePersonName($last);

        if ($fullNorm !== '' && $items->contains($fullNorm)) {
            return true;
        }

        // Agenda-afwezigheid wordt vaak als alleen voornaam ingevuld.
        if ($firstNorm !== '' && $items->contains($firstNorm)) {
            return true;
        }

        if ($firstNorm === '') {
            return false;
        }

        return $items->contains(function (string $item) use ($firstNorm, $lastNorm): bool {
            if (! str_contains(' '.$item.' ', ' '.$firstNorm.' ')) {
                return false;
            }

            return $lastNorm === '' || str_contains(' '.$item.' ', ' '.$lastNorm.' ');
        });
    }

    private function normalizePersonName(string $value): string
    {
        return preg_replace('/\s+/u', ' ', mb_strtolower(trim($value))) ?? '';
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
        $section = $this->activeSection();

        foreach (Member::query()->whereNotNull('birthday', 'and')->cursor(['*']) as $member) {
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

        foreach (
            User::query()
                ->whereNotNull('first_name', 'and')
                ->whereNotNull('birthday', 'and')
                ->whereHas('sectionRoles', function (Builder $query) use ($section): void {
                    $query->where('section', $section)
                        ->whereIn('role', [
                            UserSectionRole::ROLE_TEAMLEIDER,
                            UserSectionRole::ROLE_LEIDING,
                            UserSectionRole::ROLE_OUDERCONTACT,
                        ]);
                })
                ->cursor(['*']) as $leader
        ) {
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
            ->take(3)
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
        return mb_strtolower($date->locale('nl')->isoFormat('dd'));
    }

    private function activeSection(): string
    {
        $fromSession = session('active_section');
        if (is_string($fromSession) && $fromSession !== '') {
            return $fromSession;
        }

        return UserSectionRole::SECTION_DOLFIJNEN;
    }

    private function scopedLeadersQuery(): Builder
    {
        $section = $this->activeSection();

        return User::query()
            ->whereNotNull('first_name', 'and')
            ->whereHas('sectionRoles', function (Builder $query) use ($section): void {
                $query->where('section', $section)
                    ->whereIn('role', [
                        UserSectionRole::ROLE_TEAMLEIDER,
                        UserSectionRole::ROLE_LEIDING,
                        UserSectionRole::ROLE_OUDERCONTACT,
                    ]);
            });
    }

    /**
     * @return list<array{id:int,title:string,category:string,deadline:?string,is_overdue:bool}>
     */
    private function myTasks(Carbon $today): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $section = $this->activeSection();

        return TaskItem::withoutGlobalScope('section')
            ->where(function (Builder $query) use ($section): void {
                $query->where('section', $section)
                    ->orWhereJsonContains('shared_sections', $section);
            })
            ->where(function (Builder $query) use ($user): void {
                $query->where('owner_user_id', $user->id)
                    ->orWhereJsonContains('owner_user_ids', $user->id);
            })
            ->get()
            ->map(function (TaskItem $task) use ($today): array {
                $deadline = $this->nextTaskDeadline($task, $today);

                return [
                    'id' => $task->id,
                    'title' => (string) $task->title,
                    'category' => (string) ($task->category ?? ''),
                    'deadline' => $deadline?->toDateString(),
                    'is_overdue' => $deadline ? $deadline->lt($today) : false,
                ];
            })
            ->sortBy(function (array $row): array {
                return [
                    $row['deadline'] === null ? 1 : 0,
                    $row['deadline'] ?? '9999-12-31',
                    mb_strtolower((string) ($row['title'] ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    private function nextTaskDeadline(TaskItem $task, Carbon $today): ?Carbon
    {
        $completedDates = collect(is_array($task->deadline_completions) ? $task->deadline_completions : [])
            ->keys()
            ->map(fn ($v): string => trim((string) $v))
            ->filter()
            ->flip();

        $dates = collect(is_array($task->deadlines) ? $task->deadlines : [])
            ->map(fn ($v): string => trim((string) $v))
            ->filter()
            ->reject(fn (string $v): bool => $completedDates->has($v))
            ->map(function (string $v): ?Carbon {
                try {
                    return Carbon::parse($v)->startOfDay();
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter()
            ->values();

        if ($dates->isEmpty()) {
            return null;
        }

        $upcoming = $dates
            ->filter(fn (Carbon $d): bool => $d->gte($today))
            ->sortBy(fn (Carbon $d) => $d->toDateString())
            ->values()
            ->first();

        if ($upcoming) {
            return $upcoming;
        }

        return $dates
            ->sortBy(fn (Carbon $d) => $d->toDateString())
            ->values()
            ->last();
    }

    private function currentLeaderName(?User $user): ?string
    {
        if (! $user) {
            return null;
        }
        $full = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $full !== '' ? $full : ($user->name ?: null);
    }

    /**
     * @return list<string>
     */
    private function splitAbsentNames(string $absent): array
    {
        $text = trim($absent);
        if ($text === '') {
            return [];
        }

        $items = array_map(
            static fn (string $name): string => trim($name),
            explode(',', $text)
        );
        $items = array_values(array_filter($items, static fn (string $name): bool => $name !== ''));

        return array_values(array_unique($items));
    }

    /**
     * @param  list<string>  $names
     */
    private function joinAbsentNames(array $names): string
    {
        return implode(', ', $names);
    }
}
