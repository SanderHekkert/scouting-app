<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
}
