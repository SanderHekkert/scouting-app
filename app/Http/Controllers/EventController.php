<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use App\Models\UserSectionRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $today = Carbon::today();

        $active = Event::query()
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date')
            ->orderBy('theme')
            ->get();

        return Inertia::render('Events/Index', [
            'events' => $active,
            'leaders' => $this->leaderNamesForActiveSection(),
        ]);
    }

    /**
     * Gearchiveerde opkomsten (event_date vóór vandaag), eigen pagina onder Agenda.
     */
    public function archived()
    {
        $today = Carbon::today();

        $archived = Event::query()
            ->whereDate('event_date', '<', $today)
            ->orderByDesc('event_date')
            ->orderBy('theme')
            ->get();

        return Inertia::render('Events/Archived', [
            'archivedEvents' => $archived,
            'leaders' => $this->leaderNamesForActiveSection(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'theme' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'activity' => ['nullable', 'string', 'max:255'],
            'program_by' => ['nullable', 'string', 'max:255'],
            'absent' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        Event::create($data);

        return to_route('events.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'theme' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'activity' => ['nullable', 'string', 'max:255'],
            'program_by' => ['nullable', 'string', 'max:255'],
            'absent' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $event->update($data);

        return to_route('events.index');
    }

    /**
     * Partial update (bv. dashboard: alleen thema aanpassen).
     */
    public function updateTheme(Request $request, Event $event)
    {
        $data = $request->validate([
            'theme' => ['nullable', 'string', 'max:255'],
        ]);

        $event->update($data);

        return back();
    }

    /**
     * Snel één agenda-veld bijwerken vanuit de tabel (EditableTextCell).
     */
    public function quickUpdate(Request $request, Event $event)
    {
        $data = $request->validate([
            'theme' => ['sometimes', 'nullable', 'string', 'max:255'],
            'event_date' => ['sometimes', 'date'],
            'event_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'activity' => ['sometimes', 'nullable', 'string', 'max:255'],
            'program_by' => ['sometimes', 'nullable', 'string', 'max:255'],
            'absent' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_key_exists('theme', $data) && $data['theme'] === null) {
            $data['theme'] = '';
        }
        $event->update($data);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return back();
    }

    /**
     * Leden kunnen hier alleen hun eigen aanwezigheid melden.
     */
    public function updateOwnAttendance(Request $request, Event $event)
    {
        $section = $this->activeSection();
        $role = $request->user()?->roleInSection($section);
        if (
            $role !== UserSectionRole::ROLE_LID
            || ! in_array($section, [UserSectionRole::SECTION_LOODSEN, UserSectionRole::SECTION_WILDE_VAART], true)
        ) {
            abort(403);
        }

        $data = $request->validate([
            'present' => ['required', 'boolean'],
        ]);

        $name = $this->currentUserDisplayName($request->user());
        if ($name === '') {
            return back()->withErrors(['attendance' => 'Kon je naam niet bepalen.']);
        }

        $existing = collect(explode(',', (string) ($event->absent ?? '')))
            ->map(fn (string $item): string => trim($item))
            ->filter(fn (string $item): bool => $item !== '')
            ->values();

        $normalizedSelf = Str::lower($name);

        $filtered = $existing->reject(function (string $item) use ($normalizedSelf): bool {
            return Str::lower($item) === $normalizedSelf;
        })->values();

        if (! $data['present']) {
            $filtered->push($name);
        }

        $event->update([
            'absent' => $filtered->unique()->implode(', '),
        ]);

        return back();
    }

    private function activeSection(): string
    {
        return session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
    }

    private function currentUserDisplayName(?User $user): string
    {
        if (! $user) {
            return '';
        }

        $full = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        if ($full !== '') {
            return $full;
        }

        return trim((string) ($user->name ?? ''));
    }

    /**
     * @return list<string>
     */
    private function leaderNamesForActiveSection(): array
    {
        $section = $this->activeSection();

        return User::query()
            ->whereNotNull('first_name')
            ->whereHas('sectionRoles', function (Builder $query) use ($section): void {
                $query->where('section', $section)
                    ->whereIn('role', [
                        UserSectionRole::ROLE_TEAMLEIDER,
                        UserSectionRole::ROLE_LEIDING,
                        UserSectionRole::ROLE_OUDERCONTACT,
                    ]);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (User $leader): string {
                return trim(($leader->first_name ?? '').' '.($leader->last_name ?? ''));
            })
            ->filter(fn (string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->all();
    }
}
