<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\TaskItem;
use App\Models\User;
use App\Models\UserSectionRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $today = Carbon::today();
        $section = $this->activeSection();

        $active = Event::withoutGlobalScope('section')
            ->where(function (Builder $query) use ($section): void {
                $query->where('section', $section);
                if ($this->supportsSharedEventsForSection($section)) {
                    $query->orWhereJsonContains('shared_sections', $section);
                }
            })
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date')
            ->orderBy('theme')
            ->get();

        $taskItems = TaskItem::query()
            ->withoutGlobalScope('section')
            ->where(function (Builder $query) use ($section): void {
                $query->where('section', $section);
                if ($this->supportsSharedEventsForSection($section)) {
                    $query->orWhereJsonContains('shared_sections', $section);
                }
            })
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (TaskItem $task): array => [
                'id' => $task->id,
                'title' => (string) $task->title,
            ])
            ->values()
            ->all();

        return Inertia::render('Events/Index', [
            'events' => $active->map(function (Event $event): array {
                return [
                    ...$event->toArray(),
                    'attachment_name' => $this->attachmentName($event->attachments),
                    'has_attachment' => $this->attachmentName($event->attachments) !== null,
                ];
            })->values(),
            'leaders' => $this->leaderNamesForActiveSection(),
            'taskItems' => $taskItems,
            'allSections' => UserSectionRole::ALL_SECTIONS,
        ]);
    }

    /**
     * Gearchiveerde opkomsten (event_date vóór vandaag), eigen pagina onder Agenda.
     */
    public function archived()
    {
        $today = Carbon::today();
        $section = $this->activeSection();

        $archived = Event::withoutGlobalScope('section')
            ->where(function (Builder $query) use ($section): void {
                $query->where('section', $section);
                if ($this->supportsSharedEventsForSection($section)) {
                    $query->orWhereJsonContains('shared_sections', $section);
                }
            })
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
     * Show a single event edit page.
     */
    public function show(Event $event)
    {
        $section = $this->activeSection();
        $taskItems = TaskItem::withoutGlobalScope('section')
            ->where(function (Builder $query) use ($section): void {
                $query->where('section', $section);
                if ($this->supportsSharedEventsForSection($section)) {
                    $query->orWhereJsonContains('shared_sections', $section);
                }
            })
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (TaskItem $task): array => [
                'id' => (int) $task->id,
                'title' => (string) $task->title,
            ])
            ->values()
            ->all();

        return Inertia::render('Events/Show', [
            'event' => [
                'id' => (int) $event->id,
                'section' => (string) ($event->section ?? ''),
                'theme' => (string) ($event->theme ?? ''),
                'event_date' => (string) ($event->event_date ?? ''),
                'event_type' => (string) ($event->event_type ?? ''),
                'activity' => (string) ($event->activity ?? ''),
                'program_by' => (string) ($event->program_by ?? ''),
                'location' => (string) ($event->location ?? ''),
                'time_slot' => (string) ($event->time_slot ?? ''),
                'invitees' => (string) ($event->invitees ?? ''),
                'link_url' => (string) ($event->link_url ?? ''),
                'attachments' => (string) ($event->attachments ?? ''),
                'attachment_name' => $this->attachmentName($event->attachments),
                'absent' => (string) ($event->absent ?? ''),
                'present_names' => collect($event->present_names ?? [])->map(fn ($v): string => trim((string) $v))->filter()->values()->all(),
                'notes' => (string) ($event->notes ?? ''),
                'task_item_ids' => collect($event->task_item_ids ?? [])->map(fn ($v): int => (int) $v)->filter()->values()->all(),
                'shared_sections' => $this->normalizeSharedSections($event->shared_sections ?? null),
            ],
            'leaders' => $this->leaderNamesForActiveSection(),
            'taskItems' => $taskItems,
            'allSections' => UserSectionRole::ALL_SECTIONS,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $section = $this->activeSection();
        $data = $request->validate([
            'theme' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'activity' => ['nullable', 'string', 'max:255'],
            'program_by' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'time_slot' => ['nullable', 'string', 'max:255'],
            'invitees' => ['nullable', 'string'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'attachments' => ['nullable', 'string'],
            'attachment_file' => ['nullable', 'file', 'max:10240'],
            'absent' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'task_item_ids' => ['nullable', 'array'],
            'task_item_ids.*' => ['integer'],
            'shared_sections' => ['nullable', 'array'],
            'shared_sections.*' => ['string', Rule::in(UserSectionRole::ALL_SECTIONS)],
        ]);
        if (($data['theme'] ?? null) === null) {
            $data['theme'] = '';
        }
        $data['task_item_ids'] = $this->normalizeTaskItemIds($data['task_item_ids'] ?? null);
        $data['shared_sections'] = $this->normalizeSharedSections($data['shared_sections'] ?? null);
        if ($request->hasFile('attachment_file')) {
            $data['attachments'] = $this->encodeAttachmentMeta($request->file('attachment_file'));
        }
        if ($this->isBestuurSection($section)) {
            $data['event_type'] = '';
            $data['activity'] = '';
            $data['program_by'] = '';
            $data['shared_sections'] = [];
            $data['absent'] = '';
        }

        Event::create($data);

        return to_route('events.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $section = $this->activeSection();
        $data = $request->validate([
            'theme' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'activity' => ['nullable', 'string', 'max:255'],
            'program_by' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'time_slot' => ['nullable', 'string', 'max:255'],
            'invitees' => ['nullable', 'string'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'attachments' => ['nullable', 'string'],
            'attachment_file' => ['nullable', 'file', 'max:10240'],
            'absent' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'task_item_ids' => ['nullable', 'array'],
            'task_item_ids.*' => ['integer'],
            'shared_sections' => ['nullable', 'array'],
            'shared_sections.*' => ['string', Rule::in(UserSectionRole::ALL_SECTIONS)],
        ]);
        if (($data['theme'] ?? null) === null) {
            $data['theme'] = '';
        }
        $data['task_item_ids'] = $this->normalizeTaskItemIds($data['task_item_ids'] ?? null);
        $data['shared_sections'] = $this->normalizeSharedSections($data['shared_sections'] ?? null);
        if ($request->hasFile('attachment_file')) {
            $this->deleteAttachmentFile($event->attachments);
            $data['attachments'] = $this->encodeAttachmentMeta($request->file('attachment_file'));
        }
        if ($this->isBestuurSection($section)) {
            $data['event_type'] = '';
            $data['activity'] = '';
            $data['program_by'] = '';
            $data['shared_sections'] = [];
            $data['absent'] = '';
        }

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
        $section = $this->activeSection();
        $data = $request->validate([
            'theme' => ['sometimes', 'nullable', 'string', 'max:255'],
            'event_date' => ['sometimes', 'date'],
            'event_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'activity' => ['sometimes', 'nullable', 'string', 'max:255'],
            'program_by' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'time_slot' => ['sometimes', 'nullable', 'string', 'max:255'],
            'invitees' => ['sometimes', 'nullable', 'string'],
            'link_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'attachments' => ['sometimes', 'nullable', 'string'],
            'absent' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'task_item_ids' => ['sometimes', 'nullable', 'array'],
            'task_item_ids.*' => ['integer'],
            'shared_sections' => ['sometimes', 'nullable', 'array'],
            'shared_sections.*' => ['string', Rule::in(UserSectionRole::ALL_SECTIONS)],
        ]);

        if (array_key_exists('theme', $data) && $data['theme'] === null) {
            $data['theme'] = '';
        }
        if (array_key_exists('task_item_ids', $data)) {
            $data['task_item_ids'] = $this->normalizeTaskItemIds($data['task_item_ids']);
        }
        if (array_key_exists('shared_sections', $data)) {
            $data['shared_sections'] = $this->normalizeSharedSections($data['shared_sections']);
        }
        if ($this->isBestuurSection($section)) {
            if (array_key_exists('event_type', $data)) {
                $data['event_type'] = '';
            }
            if (array_key_exists('activity', $data)) {
                $data['activity'] = '';
            }
            if (array_key_exists('program_by', $data)) {
                $data['program_by'] = '';
            }
            if (array_key_exists('shared_sections', $data)) {
                $data['shared_sections'] = [];
            }
            if (array_key_exists('absent', $data)) {
                $data['absent'] = '';
            }
        }
        $event->update($data);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $this->deleteAttachmentFile($event->attachments);
        $event->delete();

        return back();
    }

    public function downloadAttachment(Event $event): BinaryFileResponse
    {
        $meta = $this->attachmentMeta($event->attachments);
        abort_unless($meta !== null, 404);
        abort_unless(Storage::disk('local')->exists($meta['path']), 404);

        return response()->download(
            Storage::disk('local')->path($meta['path']),
            $meta['name']
        );
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
        $present = collect($event->present_names ?? [])
            ->map(fn ($item): string => trim((string) $item))
            ->filter(fn (string $item): bool => $item !== '')
            ->values();

        $normalizedSelf = Str::lower($name);

        $filtered = $existing->reject(function (string $item) use ($normalizedSelf): bool {
            return Str::lower($item) === $normalizedSelf;
        })->values();
        $presentFiltered = $present->reject(function (string $item) use ($normalizedSelf): bool {
            return Str::lower($item) === $normalizedSelf;
        })->values();

        if ($data['present']) {
            $presentFiltered->push($name);
        } else {
            $filtered->push($name);
        }

        $event->update([
            'absent' => $filtered->unique()->implode(', '),
            'present_names' => $presentFiltered->unique()->values()->all(),
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

    /**
     * @param  array<int, mixed>|null  $raw
     * @return list<int>
     */
    private function normalizeTaskItemIds(?array $raw): array
    {
        $section = $this->activeSection();
        $ids = collect($raw ?? [])
            ->map(fn ($v): int => (int) $v)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $allowed = TaskItem::withoutGlobalScope('section')
            ->where(function (Builder $query) use ($section): void {
                $query->where('section', $section);
                if ($this->supportsSharedEventsForSection($section)) {
                    $query->orWhereJsonContains('shared_sections', $section);
                }
            })
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return array_values($allowed);
    }

    /**
     * @param  array<int, mixed>|null  $raw
     * @return list<string>
     */
    private function normalizeSharedSections(?array $raw): array
    {
        $active = $this->activeSection();
        $allowed = [
            UserSectionRole::SECTION_BEVERS,
            UserSectionRole::SECTION_DOLFIJNEN,
            UserSectionRole::SECTION_ZEEVERKENNERS,
            UserSectionRole::SECTION_WILDE_VAART,
        ];

        return collect($raw ?? [])
            ->map(fn ($v): string => (string) $v)
            ->filter(fn (string $v): bool => $v !== '' && $v !== $active)
            ->filter(fn (string $v): bool => in_array($v, $allowed, true))
            ->unique()
            ->values()
            ->all();
    }

    private function supportsSharedEventsForSection(string $section): bool
    {
        return in_array($section, [
            UserSectionRole::SECTION_BEVERS,
            UserSectionRole::SECTION_DOLFIJNEN,
            UserSectionRole::SECTION_ZEEVERKENNERS,
            UserSectionRole::SECTION_WILDE_VAART,
        ], true);
    }

    private function isBestuurSection(string $section): bool
    {
        return $section === UserSectionRole::SECTION_BESTUUR;
    }

    private function encodeAttachmentMeta(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $path = $file->storeAs(
            'event-attachments/'.now()->format('Y/m'),
            Str::uuid().'.'.$ext,
            'local'
        );

        return json_encode([
            'path' => $path,
            'name' => (string) ($file->getClientOriginalName() ?: basename($path)),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array{path:string,name:string}|null
     */
    private function attachmentMeta(?string $raw): ?array
    {
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return null;
        }
        $path = trim((string) ($decoded['path'] ?? ''));
        if ($path === '') {
            return null;
        }

        return [
            'path' => $path,
            'name' => trim((string) ($decoded['name'] ?? '')) ?: basename($path),
        ];
    }

    private function attachmentName(?string $raw): ?string
    {
        return $this->attachmentMeta($raw)['name'] ?? null;
    }

    private function deleteAttachmentFile(?string $raw): void
    {
        $meta = $this->attachmentMeta($raw);
        if ($meta && Storage::disk('local')->exists($meta['path'])) {
            Storage::disk('local')->delete($meta['path']);
        }
    }
}
