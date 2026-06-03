<?php

namespace App\Http\Controllers;

use App\Models\AgendaItem;
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
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AgendaItemController extends Controller
{
    public function create()
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        $activeSection = session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
        $prefillDate = request()->query('date');
        $prefillStartTime = request()->query('start_time');
        $eventDate = is_string($prefillDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $prefillDate) === 1
            ? $prefillDate
            : '';
        $startTime = is_string($prefillStartTime) && preg_match('/^\d{2}:\d{2}$/', $prefillStartTime) === 1
            ? $prefillStartTime
            : '';

        return Inertia::render('Agenda/Create', [
            'item' => [
                'id' => null,
                'theme' => '',
                'event_date' => $eventDate,
                'end_date' => $eventDate,
                'start_time' => $startTime,
                'end_time' => '',
                'location' => '',
                'time_slot' => '',
                'invitees' => '',
                'link_url' => '',
                'notes' => '',
                'audience_scope' => 'self',
                'target_user_ids' => [],
            ],
            'isBestuur' => $activeSection === UserSectionRole::SECTION_BESTUUR,
            'availableUsers' => $this->agendaAudienceUsers($user),
        ]);
    }

    public function index()
    {
        $today = Carbon::today();
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        $activeSection = (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
        $canBrowseAllAgendas = $user->isGlobalAdmin() || $user->isGlobalBoardMember();
        $requestedSection = (string) request()->query('section', $activeSection);
        $selectedSection = in_array($requestedSection, UserSectionRole::ALL_SECTIONS, true) ? $requestedSection : $activeSection;
        $requestedUserId = (int) request()->query('user_id', (int) $user->id);
        $selectedUserId = $requestedUserId > 0 ? $requestedUserId : (int) $user->id;
        $sections = $this->userSections($user);
        $itemsQuery = AgendaItem::query();
        if ($canBrowseAllAgendas) {
            $itemsQuery->where('owner_user_id', $selectedUserId);
        } else {
            $itemsQuery->where(function (Builder $query) use ($user, $sections): void {
                $query->where('owner_user_id', $user->id);
                $query->orWhere('audience_scope', 'all');
                $query->orWhere(function (Builder $q) use ($user): void {
                    $q->where('audience_scope', 'selected')
                        ->whereJsonContains('target_user_ids', (int) $user->id);
                });
                if ($sections !== []) {
                    $query->orWhere(function (Builder $q) use ($sections): void {
                        $q->whereNull('owner_user_id')
                            ->whereIn('section', $sections, 'and', false);
                    });
                }
            });
        }
        $items = $itemsQuery
            ->where(function (Builder $query) use ($today): void {
                $query->whereDate('event_date', '>=', $today)
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->orderBy('event_date', 'asc')
            ->orderBy('theme', 'asc')
            ->get();

        $opkomstenQuery = Event::withoutGlobalScope('section');
        if ($canBrowseAllAgendas) {
            $opkomstenQuery->where(function (Builder $query) use ($selectedSection): void {
                $query->where('section', $selectedSection);
                if ($this->supportsSharedEventsForSection($selectedSection)) {
                    $query->orWhereJsonContains('shared_sections', $selectedSection);
                }
            });
        } else {
            $opkomstenQuery->where(function (Builder $query) use ($sections): void {
                foreach ($sections as $index => $section) {
                    if ($index === 0) {
                        $query->where('section', $section);
                    } else {
                        $query->orWhere('section', $section);
                    }
                    if ($this->supportsSharedEventsForSection($section)) {
                        $query->orWhereJsonContains('shared_sections', $section);
                    }
                }
            });
        }
        $opkomsten = $opkomstenQuery
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date', 'asc')
            ->orderBy('theme', 'asc')
            ->get();

        $tasksQuery = TaskItem::withoutGlobalScope('section');
        if ($canBrowseAllAgendas) {
            $tasksQuery->where(function (Builder $query) use ($selectedSection): void {
                $query->where('section', $selectedSection)
                    ->orWhereJsonContains('shared_sections', $selectedSection);
            });
        } else {
            $tasksQuery->where(function (Builder $query) use ($sections): void {
                foreach ($sections as $index => $section) {
                    if ($index === 0) {
                        $query->where('section', $section);
                    } else {
                        $query->orWhere('section', $section);
                    }
                    $query->orWhereJsonContains('shared_sections', $section);
                }
            });
        }
        $tasks = $tasksQuery
            ->get(['id', 'title', 'deadlines']);

        $filterUsers = User::query()
            ->when($canBrowseAllAgendas, function ($q) use ($selectedSection) {
                $q->whereHas('sectionRoles', function (Builder $query) use ($selectedSection): void {
                    $query->where('section', $selectedSection);
                });
            })
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->get(['id', 'first_name', 'last_name', 'name'])
            ->map(fn (User $u): array => [
                'id' => (int) $u->id,
                'name' => trim((string) ($u->first_name ?? '').' '.(string) ($u->last_name ?? '')) ?: (string) $u->name,
            ])->values();

        return Inertia::render('Agenda/Index', [
            'items' => $items->map(fn (AgendaItem $item): array => [
                ...$item->toArray(),
                'attachment_name' => $this->attachmentName($item->attachments),
                'has_attachment' => $this->attachmentName($item->attachments) !== null,
                'google_calendar_url' => $this->googleCalendarUrl($item),
            ])->values(),
            'opkomsten' => $opkomsten->map(fn (Event $event): array => [
                'id' => (int) $event->id,
                'theme' => (string) ($event->theme ?? ''),
                'event_date' => (string) ($event->event_date ?? ''),
                'event_type' => (string) ($event->event_type ?? ''),
                'activity' => (string) ($event->activity ?? ''),
                'section' => (string) ($event->section ?? ''),
                'is_shared' => count(array_intersect($sections, collect($event->shared_sections ?? [])->map(fn ($s) => (string) $s)->all())) > 0,
                'shared_sections' => collect($event->shared_sections ?? [])->map(fn ($s) => (string) $s)->values()->all(),
                'task_item_ids' => collect($event->task_item_ids ?? [])->map(fn ($v): int => (int) $v)->filter(fn (int $v): bool => $v > 0)->values()->all(),
            ])->values(),
            'tasks' => $tasks->map(function (TaskItem $task): array {
                return [
                    'id' => (int) $task->id,
                    'title' => (string) ($task->title ?? ''),
                    'deadlines' => collect($task->deadlines ?? [])
                        ->map(fn ($d): string => trim((string) $d))
                        ->filter(fn (string $d): bool => $d !== '')
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })->values(),
            'canBrowseAllAgendas' => $canBrowseAllAgendas,
            'sectionOptions' => UserSectionRole::ALL_SECTIONS,
            'selectedSectionFilter' => $selectedSection,
            'selectedUserFilter' => $selectedUserId,
            'filterUsers' => $filterUsers,
        ]);
    }

    public function archived()
    {
        $today = Carbon::today();
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        $sections = $this->userSections($user);
        $itemsQuery = AgendaItem::query();
        if (! $user->isGlobalAdmin() && ! $user->isGlobalBoardMember()) {
            $itemsQuery->where(function (Builder $query) use ($user, $sections): void {
                $query->where('owner_user_id', $user->id);
                $query->orWhere('audience_scope', 'all');
                $query->orWhere(function (Builder $q) use ($user): void {
                    $q->where('audience_scope', 'selected')
                        ->whereJsonContains('target_user_ids', (int) $user->id);
                });
                if ($sections !== []) {
                    $query->orWhere(function (Builder $q) use ($sections): void {
                        $q->whereNull('owner_user_id')
                            ->whereIn('section', $sections, 'and', false);
                    });
                }
            });
        }
        $items = $itemsQuery
            ->where(function (Builder $query) use ($today): void {
                $query->whereDate('end_date', '<', $today)
                    ->orWhere(function (Builder $q) use ($today): void {
                        $q->whereNull('end_date')
                            ->whereDate('event_date', '<', $today);
                    });
            })
            ->orderByDesc('event_date')
            ->orderBy('theme', 'asc')
            ->get();

        return Inertia::render('Agenda/Archived', [
            'archivedItems' => $items->map(fn (AgendaItem $item): array => [
                ...$item->toArray(),
                'attachment_name' => $this->attachmentName($item->attachments),
                'has_attachment' => $this->attachmentName($item->attachments) !== null,
                'google_calendar_url' => $this->googleCalendarUrl($item),
            ])->values(),
        ]);
    }

    public function show(AgendaItem $agendaItem)
    {
        $this->authorizeAgendaItem($agendaItem);

        $targetIds = collect($agendaItem->target_user_ids ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $targetUsers = $targetIds === []
            ? []
            : User::query()
                ->whereIn('id', $targetIds, 'and', false)
                ->orderBy('first_name', 'asc')
                ->orderBy('last_name', 'asc')
                ->get(['id', 'first_name', 'last_name', 'name', 'email'])
                ->map(fn (User $u): array => [
                    'id' => (int) $u->id,
                    'name' => trim((string) ($u->first_name ?? '').' '.(string) ($u->last_name ?? '')) ?: (string) $u->name,
                    'email' => (string) $u->email,
                ])
                ->values()
                ->all();

        return Inertia::render('Agenda/Show', [
            'item' => [
                ...$agendaItem->toArray(),
                'attachment_name' => $this->attachmentName($agendaItem->attachments),
                'google_calendar_url' => $this->googleCalendarUrl($agendaItem),
                'target_users' => $targetUsers,
            ],
        ]);
    }

    public function showOpkomst(Event $event)
    {
        $this->authorizeOpkomst($event);
        $section = (string) ($event->section ?? '');
        $absent = (string) ($event->absent ?? '');
        $presentNames = $this->resolvedOpkomstPresentNames($event, $section, $absent);

        return Inertia::render('Agenda/OpkomstShow', [
            'item' => [
                'id' => (int) $event->id,
                'theme' => (string) ($event->theme ?? ''),
                'event_date' => (string) ($event->event_date ?? ''),
                'event_type' => (string) ($event->event_type ?? ''),
                'activity' => (string) ($event->activity ?? ''),
                'program_by' => (string) ($event->program_by ?? ''),
                'location' => (string) ($event->location ?? ''),
                'time_slot' => (string) ($event->time_slot ?? ''),
                'invitees' => (string) ($event->invitees ?? ''),
                'link_url' => (string) ($event->link_url ?? ''),
                'notes' => (string) ($event->notes ?? ''),
                'present_names' => $presentNames,
                'absent' => $absent,
                'section' => $section,
                'is_shared' => ! empty($event->shared_sections ?? []),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'theme' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:event_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'time_slot' => ['nullable', 'string', 'max:255'],
            'invitees' => ['nullable', 'string'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'attachment_file' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(10 * 1024)],
            'notes' => ['nullable', 'string'],
            'audience_scope' => ['nullable', 'string', Rule::in(['self', 'all', 'selected'])],
            'target_user_ids' => ['nullable', 'array'],
            'target_user_ids.*' => ['integer', Rule::exists('users', 'id')],
        ]);

        $data['theme'] = (string) ($data['theme'] ?? '');
        $data['end_date'] = (string) ($data['end_date'] ?? '') !== '' ? $data['end_date'] : $data['event_date'];
        $data['start_time'] = trim((string) ($data['start_time'] ?? ''));
        $data['end_time'] = trim((string) ($data['end_time'] ?? ''));
        $data['time_slot'] = $this->buildTimeSlot($data['start_time'], $data['end_time']);
        $data['owner_user_id'] = (int) $request->user()->id;
        $data['section'] = session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
        $activeSection = (string) $data['section'];
        $isBestuur = $activeSection === UserSectionRole::SECTION_BESTUUR;
        $data['audience_scope'] = $isBestuur ? (string) ($data['audience_scope'] ?? 'self') : 'self';
        $data['target_user_ids'] = $data['audience_scope'] === 'selected'
            ? $this->normalizeTargetUserIds($data['target_user_ids'] ?? null)
            : [];

        if ($request->hasFile('attachment_file')) {
            $data['attachments'] = $this->encodeAttachmentMeta($request->file('attachment_file'));
        }

        $item = AgendaItem::create($data);

        return to_route('agenda.index');
    }

    public function update(Request $request, AgendaItem $agendaItem)
    {
        $this->authorizeAgendaItem($agendaItem);

        $data = $request->validate([
            'theme' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:event_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'time_slot' => ['nullable', 'string', 'max:255'],
            'invitees' => ['nullable', 'string'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'attachment_file' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(10 * 1024)],
            'notes' => ['nullable', 'string'],
            'audience_scope' => ['nullable', 'string', Rule::in(['self', 'all', 'selected'])],
            'target_user_ids' => ['nullable', 'array'],
            'target_user_ids.*' => ['integer', Rule::exists('users', 'id')],
        ]);
        $data['theme'] = (string) ($data['theme'] ?? '');
        $data['end_date'] = (string) ($data['end_date'] ?? '') !== '' ? $data['end_date'] : $data['event_date'];
        $data['start_time'] = trim((string) ($data['start_time'] ?? ''));
        $data['end_time'] = trim((string) ($data['end_time'] ?? ''));
        $data['time_slot'] = $this->buildTimeSlot($data['start_time'], $data['end_time']);
        $activeSection = (string) ($agendaItem->section ?? session('active_section', UserSectionRole::SECTION_DOLFIJNEN));
        $isBestuur = $activeSection === UserSectionRole::SECTION_BESTUUR;
        $data['audience_scope'] = $isBestuur ? (string) ($data['audience_scope'] ?? ($agendaItem->audience_scope ?? 'self')) : 'self';
        $data['target_user_ids'] = $data['audience_scope'] === 'selected'
            ? $this->normalizeTargetUserIds($data['target_user_ids'] ?? null)
            : [];

        if ($request->hasFile('attachment_file')) {
            $this->deleteAttachmentFile($agendaItem->attachments);
            $data['attachments'] = $this->encodeAttachmentMeta($request->file('attachment_file'));
        }

        $agendaItem->update($data);

        return to_route('agenda.index');
    }

    public function updateSchedule(Request $request, AgendaItem $agendaItem)
    {
        $this->authorizeAgendaItem($agendaItem);

        $data = $request->validate([
            'event_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:event_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $eventDate = (string) $data['event_date'];
        $endDate = (string) ($data['end_date'] ?? '') !== '' ? (string) $data['end_date'] : $eventDate;
        $startTime = trim((string) ($data['start_time'] ?? ''));
        $endTime = trim((string) ($data['end_time'] ?? ''));

        $agendaItem->update([
            'event_date' => $eventDate,
            'end_date' => $endDate,
            'start_time' => $startTime !== '' ? $startTime : null,
            'end_time' => $endTime !== '' ? $endTime : null,
            'time_slot' => $this->buildTimeSlot($startTime, $endTime),
        ]);

        return back();
    }

    public function destroy(AgendaItem $agendaItem)
    {
        $this->authorizeAgendaItem($agendaItem);
        $this->deleteAttachmentFile($agendaItem->attachments);
        $agendaItem->delete();

        return back();
    }

    public function downloadAttachment(AgendaItem $agendaItem): BinaryFileResponse
    {
        $this->authorizeAgendaItem($agendaItem);
        $meta = $this->attachmentMeta($agendaItem->attachments);
        abort_unless($meta !== null, 404);
        abort_unless(Storage::disk('local')->exists($meta['path']), 404);

        return response()->download(Storage::disk('local')->path($meta['path']), $meta['name']);
    }

    public function ics(AgendaItem $agendaItem): Response
    {
        $this->authorizeAgendaItem($agendaItem);
        $startDate = Carbon::parse($agendaItem->event_date)->startOfDay();
        $endDate = Carbon::parse($agendaItem->end_date ?? $agendaItem->event_date)->startOfDay();
        $hasTime = trim((string) ($agendaItem->start_time ?? '')) !== '' || trim((string) ($agendaItem->end_time ?? '')) !== '';
        $start = $startDate->copy();
        $end = $endDate->copy()->addDay();
        if ($hasTime) {
            if (preg_match('/^\d{2}:\d{2}$/', (string) $agendaItem->start_time) === 1) {
                [$h, $m] = array_map('intval', explode(':', (string) $agendaItem->start_time));
                $start->setTime($h, $m);
            }
            if (preg_match('/^\d{2}:\d{2}$/', (string) $agendaItem->end_time) === 1) {
                [$h, $m] = array_map('intval', explode(':', (string) $agendaItem->end_time));
                $end = $endDate->copy()->setTime($h, $m);
                if ($end->lessThanOrEqualTo($start)) {
                    $end = $start->copy()->addHour();
                }
            } else {
                $end = $start->copy()->addHour();
            }
        }
        $uid = 'agenda-item-'.$agendaItem->id.'@scouting-app';
        $summary = $this->icsEscape((string) ($agendaItem->theme ?? 'Agenda-item'));
        $description = $this->icsEscape(trim((string) ($agendaItem->notes ?? '')));
        $location = $this->icsEscape(trim((string) ($agendaItem->location ?? '')));
        $url = trim((string) ($agendaItem->link_url ?? ''));

        $content = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Scouting App//Agenda//NL',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            ...($hasTime
                ? [
                    'DTSTART:'.$start->format('Ymd\THis'),
                    'DTEND:'.$end->format('Ymd\THis'),
                ]
                : [
                    'DTSTART;VALUE=DATE:'.$startDate->format('Ymd'),
                    'DTEND;VALUE=DATE:'.$endDate->copy()->addDay()->format('Ymd'),
                ]),
            'SUMMARY:'.$summary,
            'DESCRIPTION:'.$description,
            'LOCATION:'.$location,
            ...($url !== '' ? ['URL:'.$this->icsEscape($url)] : []),
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);

        $filename = Str::slug((string) ($agendaItem->theme ?: 'agenda-item')).'-'.$start->format('Ymd').'.ics';

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function googleCalendarUrl(AgendaItem $agendaItem): string
    {
        $startDate = Carbon::parse($agendaItem->event_date)->startOfDay();
        $endDate = Carbon::parse($agendaItem->end_date ?? $agendaItem->event_date)->startOfDay();
        $hasTime = trim((string) ($agendaItem->start_time ?? '')) !== '' || trim((string) ($agendaItem->end_time ?? '')) !== '';
        $start = $startDate->copy();
        $end = $endDate->copy()->addDay();
        if ($hasTime) {
            if (preg_match('/^\d{2}:\d{2}$/', (string) $agendaItem->start_time) === 1) {
                [$h, $m] = array_map('intval', explode(':', (string) $agendaItem->start_time));
                $start->setTime($h, $m);
            }
            if (preg_match('/^\d{2}:\d{2}$/', (string) $agendaItem->end_time) === 1) {
                [$h, $m] = array_map('intval', explode(':', (string) $agendaItem->end_time));
                $end = $endDate->copy()->setTime($h, $m);
                if ($end->lessThanOrEqualTo($start)) {
                    $end = $start->copy()->addHour();
                }
            } else {
                $end = $start->copy()->addHour();
            }
        }
        $dateRange = $hasTime
            ? $start->format('Ymd\THis').'/'.$end->format('Ymd\THis')
            : $startDate->format('Ymd').'/'.$endDate->copy()->addDay()->format('Ymd');

        return 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            .'&text='.rawurlencode((string) ($agendaItem->theme ?? 'Agenda-item'))
            .'&dates='.$dateRange
            .'&details='.rawurlencode((string) ($agendaItem->notes ?? ''))
            .'&location='.rawurlencode((string) ($agendaItem->location ?? ''))
            .'&sprop='.rawurlencode((string) ($agendaItem->link_url ?? ''));
    }

    private function buildTimeSlot(string $startTime, string $endTime): ?string
    {
        if ($startTime !== '' && $endTime !== '') {
            return $startTime.' - '.$endTime;
        }
        if ($startTime !== '') {
            return $startTime;
        }
        if ($endTime !== '') {
            return 'tot '.$endTime;
        }

        return null;
    }

    private function encodeAttachmentMeta(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $path = $file->storeAs(
            'agenda-attachments/'.now()->format('Y/m'),
            Str::uuid().'.'.$ext,
            'local'
        );

        return json_encode([
            'path' => $path,
            'name' => (string) ($file->getClientOriginalName() ?: basename($path)),
        ], JSON_UNESCAPED_UNICODE);
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
        if ($path === '' || ! str_starts_with($path, 'agenda-attachments/')) {
            return null;
        }

        return [
            'path' => $path,
            'name' => trim((string) ($decoded['name'] ?? '')) ?: basename($path),
        ];
    }

    private function icsEscape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $value
        );
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

    /**
     * @return list<string>
     */
    private function resolvedOpkomstPresentNames(Event $event, string $section, string $absent): array
    {
        $explicitPresent = $this->uniqueNames(
            collect($event->present_names ?? [])
                ->map(fn ($v): string => trim((string) $v))
                ->filter(fn (string $name): bool => $name !== '')
                ->values()
                ->all()
        );

        $absentLookup = $this->absentLookup($absent);
        $explicitPresent = collect($explicitPresent)
            ->reject(fn (string $name): bool => $this->nameMatchesLookup($name, $absentLookup))
            ->values()
            ->all();

        if (in_array($section, [UserSectionRole::SECTION_WILDE_VAART, UserSectionRole::SECTION_LOODSEN], true)) {
            return $explicitPresent;
        }

        $members = User::query()
            ->whereHas('sectionRoles', function (Builder $query) use ($section): void {
                $query->where('section', $section);
            })
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->get(['first_name', 'last_name', 'name'])
            ->map(function (User $user): string {
                $full = trim((string) ($user->first_name ?? '').' '.(string) ($user->last_name ?? ''));

                return $full !== '' ? $full : trim((string) ($user->name ?? ''));
            })
            ->filter(fn (string $name): bool => $name !== '')
            ->reject(fn (string $name): bool => $this->nameMatchesLookup($name, $absentLookup))
            ->values()
            ->all();

        return $this->uniqueNames([
            ...$members,
            ...$explicitPresent,
        ]);
    }

    /**
     * @return array<string, true>
     */
    private function absentLookup(string $absent): array
    {
        $lookup = [];
        foreach (explode(',', $absent) as $raw) {
            $name = trim((string) $raw);
            if ($name === '') {
                continue;
            }
            foreach ($this->nameKeys($name) as $key) {
                $lookup[$key] = true;
            }
        }

        return $lookup;
    }

    private function nameMatchesLookup(string $name, array $lookup): bool
    {
        foreach ($this->nameKeys($name) as $key) {
            if (isset($lookup[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function nameKeys(string $name): array
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return [];
        }

        $keys = [Str::lower($trimmed)];
        $first = Str::lower((string) Str::of($trimmed)->before(' '));
        if ($first !== '' && ! in_array($first, $keys, true)) {
            $keys[] = $first;
        }

        return $keys;
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    private function uniqueNames(array $names): array
    {
        $seen = [];
        $result = [];

        foreach ($names as $name) {
            $trimmed = trim((string) $name);
            if ($trimmed === '') {
                continue;
            }
            $key = Str::lower($trimmed);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $trimmed;
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>|null  $raw
     * @return list<int>
     */
    private function normalizeTargetUserIds(?array $raw): array
    {
        return collect($raw ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function userSections(User $user): array
    {
        if ($user->isGlobalAdmin() || $user->isGlobalBoardMember()) {
            return UserSectionRole::ALL_SECTIONS;
        }

        return $user->sectionRoles()
            ->where('section', '!=', UserSectionRole::SECTION_ALL)
            ->pluck('section')
            ->map(fn ($v): string => (string) $v)
            ->unique()
            ->values()
            ->all();
    }

    private function agendaAudienceUsers(User $user)
    {
        $sections = $this->userSections($user);

        return User::query()
            ->whereHas('sectionRoles', function (Builder $query) use ($sections): void {
                $query->whereIn('section', $sections, 'and', false);
            })
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->get(['id', 'first_name', 'last_name', 'name', 'email'])
            ->map(fn (User $u): array => [
                'id' => (int) $u->id,
                'name' => trim((string) ($u->first_name ?? '').' '.(string) ($u->last_name ?? '')) ?: (string) $u->name,
                'email' => (string) $u->email,
            ])
            ->values();
    }

    private function authorizeAgendaItem(AgendaItem $agendaItem): void
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        if ($user->isGlobalAdmin() || $user->isGlobalBoardMember()) {
            return;
        }
        if ((int) ($agendaItem->owner_user_id ?? 0) === (int) $user->id) {
            return;
        }

        if (($agendaItem->audience_scope ?? 'self') === 'all') {
            return;
        }
        if (($agendaItem->audience_scope ?? 'self') === 'selected') {
            $targets = collect($agendaItem->target_user_ids ?? [])->map(fn ($id): int => (int) $id)->all();
            if (in_array((int) $user->id, $targets, true)) {
                return;
            }
        }

        $sections = $this->userSections($user);
        if ($agendaItem->owner_user_id === null && in_array((string) ($agendaItem->section ?? ''), $sections, true)) {
            return;
        }

        abort(403);
    }

    private function authorizeOpkomst(Event $event): void
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);

        $sections = $this->userSections($user);
        $eventSection = (string) ($event->section ?? '');
        if (in_array($eventSection, $sections, true)) {
            return;
        }

        $sharedSections = collect($event->shared_sections ?? [])
            ->map(fn ($s): string => (string) $s)
            ->all();

        if (count(array_intersect($sections, $sharedSections)) > 0) {
            return;
        }

        abort(403);
    }
}
