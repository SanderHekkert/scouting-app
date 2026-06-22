<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\SectionPermission;
use App\Models\TaskCategory;
use App\Models\TaskItem;
use App\Models\User;
use App\Models\UserSectionRole;
use App\Services\SectionPermissionGate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TaskItemController extends Controller
{
    public function __construct(
        private readonly SectionPermissionGate $permissionGate,
    ) {}

    private function activeSection(): string
    {
        $fromSession = session('active_section');
        if (is_string($fromSession) && $fromSession !== '') {
            return $fromSession;
        }

        return 'dolfijnen';
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        $section = $this->activeSection();
        $canCreateCrossSection = $this->canCreateCrossSection($user, $section);
        $visibleSections = $canCreateCrossSection ? $this->targetSectionsForBoard() : [$section];
        $taskCategories = TaskCategory::withoutGlobalScope('section')
            ->whereIn('section', $visibleSections, 'and', false)
            ->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
        $taskCategoriesBySection = TaskCategory::withoutGlobalScope('section')
            ->whereIn('section', $visibleSections, 'and', false)
            ->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->get(['section', 'name'])
            ->groupBy('section')
            ->map(fn ($rows) => $rows->pluck('name')->values()->all())
            ->all();

        $events = Event::withoutGlobalScope('section')
            ->where(function ($query) use ($visibleSections): void {
                foreach ($visibleSections as $visibleSection) {
                    $query->orWhere('section', $visibleSection)
                        ->orWhereJsonContains('shared_sections', $visibleSection);
                }
            })
            ->orderBy('event_date', 'asc')
            ->orderBy('theme', 'asc')
            ->get(['id', 'event_date', 'theme', 'task_item_ids']);

        $eventIdsByTask = [];
        foreach ($events as $event) {
            $ids = collect($event->task_item_ids ?? [])->map(fn ($v): int => (int) $v)->filter(fn (int $v): bool => $v > 0)->unique()->values();
            foreach ($ids as $taskId) {
                $eventIdsByTask[$taskId] ??= [];
                $eventIdsByTask[$taskId][] = (int) $event->id;
            }
        }

        $tasks = TaskItem::withoutGlobalScope('section')
            ->where(function ($query) use ($visibleSections): void {
                foreach ($visibleSections as $visibleSection) {
                    $query->orWhere('section', $visibleSection)
                        ->orWhereJsonContains('shared_sections', $visibleSection);
                }
            })
            ->get()
            ->sortBy(function (TaskItem $task) use ($taskCategories) {
                $categoryIndex = array_search($task->category, $taskCategories, true);

                return [
                    $this->isTaskFullyComplete($task) ? 1 : 0,
                    $categoryIndex === false ? 99 : $categoryIndex,
                    $task->title,
                ];
            })
            ->values()
            ->map(function (TaskItem $task) use ($user): array {
                return [
                    'id' => $task->id,
                    'section' => (string) $task->section,
                    'category' => $task->category,
                    'title' => $task->title,
                    'owner' => $task->owner,
                    'owner_user_id' => $task->owner_user_id,
                    'owner_user_ids' => $task->owner_user_ids ?? [],
                    'description' => $task->description,
                    'deadlines' => $this->normalizedDeadlines($task->deadlines),
                    'deadline_completions' => $this->normalizedDeadlineCompletions($task->deadline_completions),
                    'completed_at' => $task->completed_at?->toIso8601String(),
                    'event_ids' => collect($eventIdsByTask[(int) $task->id] ?? [])->map(fn ($v): int => (int) $v)->unique()->values()->all(),
                    'shared_sections' => $this->normalizedSharedSections($task->shared_sections ?? null),
                    'can_update' => $this->canEditOrDeleteTask($user, $task),
                    'can_delete' => $this->canEditOrDeleteTask($user, $task),
                    'can_complete' => $this->canCompleteTask($user, $task),
                ];
            });

        $leaders = User::query()
            ->whereNotNull('first_name', 'and')
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $leader) => [
                'id' => $leader->id,
                'name' => trim(($leader->first_name ?? '').' '.($leader->last_name ?? '')) ?: $leader->name,
            ]);

        return Inertia::render('TaskItems/Index', [
            'tasks' => $tasks,
            'taskCategories' => $taskCategories,
            'taskCategoriesBySection' => $taskCategoriesBySection,
            'leaders' => $leaders,
            'events' => $events->map(fn (Event $event): array => [
                'id' => (int) $event->id,
                'event_date' => (string) $event->event_date,
                'theme' => (string) ($event->theme ?? ''),
            ])->values()->all(),
            'canCreateCrossSection' => $canCreateCrossSection,
            'targetSections' => $canCreateCrossSection ? $this->targetSectionsForBoard() : [],
        ]);
    }

    /**
     * Keuzepagina: nieuw taak-item of nieuwe sectie.
     */
    public function create()
    {
        $section = $this->activeSection();
        $canCreateCategory = ! in_array($section, [
            UserSectionRole::SECTION_BEVERS,
            UserSectionRole::SECTION_ZEEVERKENNERS,
            UserSectionRole::SECTION_LOODSEN,
            UserSectionRole::SECTION_WILDE_VAART,
            UserSectionRole::SECTION_BESTUUR,
        ], true);
        $taskCategories = TaskCategory::query()
            ->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->all();
        $leaders = User::query()
            ->whereNotNull('first_name', 'and')
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $leader) => [
                'id' => (int) $leader->id,
                'name' => trim(($leader->first_name ?? '').' '.($leader->last_name ?? '')) ?: $leader->name,
            ])
            ->values()
            ->all();

        return Inertia::render('TaskItems/Create', [
            'canCreateCategory' => $canCreateCategory,
            'taskCategories' => $taskCategories,
            'leaders' => $leaders,
            'activeSection' => $section,
            'allSections' => UserSectionRole::ALL_SECTIONS,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $section = $this->activeSection();
        $canCreateCrossSection = $this->canCreateCrossSection($user, $section);
        $data = $request->validate([
            'category' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'owner_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'owner_user_ids' => ['nullable', 'array'],
            'owner_user_ids.*' => ['integer', Rule::exists('users', 'id')],
            'description' => ['required', 'string'],
            'deadlines' => ['nullable', 'array'],
            'deadlines.*' => ['date_format:Y-m-d'],
            'shared_sections' => ['nullable', 'array'],
            'shared_sections.*' => ['string', Rule::in(UserSectionRole::ALL_SECTIONS)],
            'target_section' => [$canCreateCrossSection ? 'required' : 'nullable', 'string', Rule::in($this->targetSectionsForBoard())],
        ]);
        $targetSection = $section;
        if ($canCreateCrossSection) {
            $targetSection = (string) $data['target_section'];
        }
        validator(['category' => $data['category']], [
            'category' => [
                'required',
                'string',
                Rule::exists('task_categories', 'name')->where(
                    fn ($query) => $query->where('section', $targetSection)
                ),
            ],
        ])->validate();

        $this->hydrateOwnerFields($data);
        $this->hydrateDeadlineFields($data);
        $data['shared_sections'] = $this->normalizedSharedSections($data['shared_sections'] ?? null);
        $data['section'] = $targetSection;

        TaskItem::create($data);

        return $this->redirectAfterSave($request, config('save-redirects.task_items'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TaskItem $task_item)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canEditOrDeleteTask($user, $task_item), 403);
        $section = $this->activeSection();
        $data = $request->validate([
            'category' => [
                'required',
                'string',
                Rule::exists('task_categories', 'name')->where(
                    fn ($query) => $query->where('section', $section)
                ),
            ],
            'title' => ['required', 'string', 'max:255'],
            'owner_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'owner_user_ids' => ['nullable', 'array'],
            'owner_user_ids.*' => ['integer', Rule::exists('users', 'id')],
            'description' => ['required', 'string'],
            'deadlines' => ['nullable', 'array'],
            'deadlines.*' => ['date_format:Y-m-d'],
            'shared_sections' => ['nullable', 'array'],
            'shared_sections.*' => ['string', Rule::in(UserSectionRole::ALL_SECTIONS)],
        ]);

        $this->hydrateOwnerFields($data);
        $this->hydrateDeadlineFields($data);
        $data['shared_sections'] = $this->normalizedSharedSections($data['shared_sections'] ?? null);

        $task_item->update($data);

        return $this->redirectAfterSave($request, config('save-redirects.task_items'));
    }

    public function toggleComplete(Request $request, TaskItem $taskItem)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canCompleteTask($user, $taskItem), 403);

        $data = $request->validate([
            'completed' => ['required', 'boolean'],
            'deadline' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $deadline = isset($data['deadline']) ? Carbon::parse($data['deadline'])->toDateString() : null;
        $deadlines = $this->normalizedDeadlines($taskItem->deadlines);

        if ($deadline !== null) {
            abort_unless(in_array($deadline, $deadlines, true), 422, 'Onbekende deadline voor deze taak.');

            $completions = $this->normalizedDeadlineCompletions($taskItem->deadline_completions);
            if ($data['completed']) {
                $completions[$deadline] = $this->completionEntryForUser($user);
            } else {
                unset($completions[$deadline]);
            }

            $taskItem->update([
                'deadline_completions' => $this->completionsForDeadlines($deadlines, $completions),
            ]);
            $this->syncCompletedAtFromDeadlines($taskItem->refresh());
        } else {
            abort_unless($deadlines === [], 422, 'Gebruik per deadline afvinken voor deze taak.');

            $taskItem->update([
                'completed_at' => $data['completed'] ? now() : null,
                'deadline_completions' => null,
            ]);
        }

        return back();
    }

    /**
     * Snel één veld bijwerken (tabel + dubbelklik / EditableTextCell).
     */
    public function quickUpdate(Request $request, TaskItem $taskItem)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canEditOrDeleteTask($user, $taskItem), 403);
        $section = $this->activeSection();
        $data = $request->validate([
            'category' => [
                'sometimes',
                'required',
                'string',
                Rule::exists('task_categories', 'name')->where(
                    fn ($query) => $query->where('section', $section)
                ),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'owner_user_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'owner_user_ids' => ['sometimes', 'nullable', 'array'],
            'owner_user_ids.*' => ['integer', Rule::exists('users', 'id')],
            'description' => ['sometimes', 'required', 'string'],
            'deadlines' => ['sometimes', 'nullable', 'array'],
            'deadlines.*' => ['date_format:Y-m-d'],
            'shared_sections' => ['sometimes', 'nullable', 'array'],
            'shared_sections.*' => ['string', Rule::in(UserSectionRole::ALL_SECTIONS)],
        ]);

        if (array_key_exists('owner_user_id', $data) || array_key_exists('owner_user_ids', $data)) {
            $this->hydrateOwnerFields($data);
        }
        if (array_key_exists('deadlines', $data)) {
            $this->hydrateDeadlineFields($data);
            $completions = $this->normalizedDeadlineCompletions($taskItem->deadline_completions);
            $data['deadline_completions'] = $this->completionsForDeadlines($data['deadlines'], $completions);
        }
        if (array_key_exists('shared_sections', $data)) {
            $data['shared_sections'] = $this->normalizedSharedSections($data['shared_sections']);
        }

        if ($data !== []) {
            $taskItem->update($data);
            if (array_key_exists('deadlines', $data)) {
                $this->syncCompletedAtFromDeadlines($taskItem->refresh());
            }
        }

        return back();
    }

    public function storeCategory(Request $request)
    {
        $section = $this->activeSection();
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('task_categories', 'name')->where(
                    fn ($query) => $query->where('section', $section)
                ),
            ],
        ]);

        $maxPosition = (int) TaskCategory::query()->max('position');

        TaskCategory::create([
            'name' => $data['name'],
            'position' => $maxPosition + 1,
        ]);

        return to_route('task-items.index');
    }

    public function reorderCategories(Request $request)
    {
        $section = $this->activeSection();
        $data = $request->validate([
            'ordered_categories' => ['required', 'array', 'min:1'],
            'ordered_categories.*' => ['required', 'string'],
        ]);

        $current = TaskCategory::query()
            ->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->values()
            ->all();

        $requested = collect($data['ordered_categories'])
            ->map(fn ($v): string => trim((string) $v))
            ->filter(fn (string $v): bool => $v !== '')
            ->unique()
            ->values()
            ->all();

        $requestedSet = array_fill_keys($requested, true);
        foreach ($requested as $name) {
            if (! in_array($name, $current, true)) {
                abort(422, "Onbekende sectie: {$name}");
            }
        }

        $finalOrder = [
            ...$requested,
            ...array_values(array_filter($current, fn (string $name): bool => ! isset($requestedSet[$name]))),
        ];

        DB::transaction(function () use ($section, $finalOrder): void {
            foreach ($finalOrder as $index => $name) {
                TaskCategory::query()
                    ->where('section', $section)
                    ->where('name', $name)
                    ->update(['position' => $index + 1]);
            }
        });

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TaskItem $taskItem)
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canEditOrDeleteTask($user, $taskItem), 403);
        TaskItem::destroy($taskItem->getKey());

        return to_route('task-items.index');
    }

    public function updateLinkedEvents(Request $request, TaskItem $taskItem)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canEditOrDeleteTask($user, $taskItem), 403);
        $section = $this->activeSection();
        $data = $request->validate([
            'event_ids' => ['nullable', 'array'],
            'event_ids.*' => ['integer'],
        ]);

        $eventIds = collect($data['event_ids'] ?? [])
            ->map(fn ($v): int => (int) $v)
            ->filter(fn (int $v): bool => $v > 0)
            ->unique()
            ->values()
            ->all();

        $taskId = (int) $taskItem->id;
        $validEventIds = Event::withoutGlobalScope('section')
            ->where(function ($query) use ($section): void {
                $query->where('section', $section)
                    ->orWhereJsonContains('shared_sections', $section);
            })
            ->whereIn('id', $eventIds, 'and', false)
            ->pluck('id')
            ->map(fn ($v): int => (int) $v)
            ->all();

        $events = Event::withoutGlobalScope('section')
            ->where(function ($query) use ($section): void {
                $query->where('section', $section)
                    ->orWhereJsonContains('shared_sections', $section);
            })
            ->get();
        foreach ($events as $event) {
            $current = collect($event->task_item_ids ?? [])
                ->map(fn ($v): int => (int) $v)
                ->filter(fn (int $v): bool => $v > 0)
                ->reject(fn (int $v): bool => $v === $taskId)
                ->values();

            if (in_array((int) $event->id, $validEventIds, true)) {
                $current->push($taskId);
            }

            $event->update([
                'task_item_ids' => $current->unique()->values()->all(),
            ]);
        }

        return back();
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function hydrateOwnerFields(array &$data): void
    {
        $ids = [];
        if (array_key_exists('owner_user_ids', $data)) {
            $ids = collect($data['owner_user_ids'] ?? [])->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
        } elseif (! empty($data['owner_user_id'])) {
            $ids = [(int) $data['owner_user_id']];
        }

        $data['owner_user_ids'] = $ids;
        $data['owner_user_id'] = $ids[0] ?? null;

        if ($ids === []) {
            $data['owner'] = null;

            return;
        }

        $names = User::query()
            ->whereIn('id', $ids, 'and', false)
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get()
            ->map(fn (User $u) => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: $u->name)
            ->filter()
            ->values()
            ->all();

        $data['owner'] = $names !== [] ? implode(', ', $names) : null;
    }

    private function hydrateDeadlineFields(array &$data): void
    {
        $data['deadlines'] = $this->normalizedDeadlines($data['deadlines'] ?? null);
    }

    /**
     * @return list<string>
     */
    private function normalizedDeadlines(mixed $deadlines): array
    {
        return collect(is_array($deadlines) ? $deadlines : [])
            ->map(fn ($v): string => trim((string) $v))
            ->filter()
            ->map(fn (string $v): string => Carbon::parse($v)->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<string, array{completed_at: string, completed_by_user_id: ?int, completed_by_name: ?string}>
     */
    private function normalizedDeadlineCompletions(mixed $completions): array
    {
        return collect(is_array($completions) ? $completions : [])
            ->mapWithKeys(function (mixed $value, mixed $key): array {
                try {
                    $date = Carbon::parse((string) $key)->toDateString();
                } catch (\Throwable) {
                    return [];
                }

                if (is_string($value)) {
                    try {
                        return [$date => [
                            'completed_at' => Carbon::parse($value)->toIso8601String(),
                            'completed_by_user_id' => null,
                            'completed_by_name' => null,
                        ]];
                    } catch (\Throwable) {
                        return [];
                    }
                }

                if (! is_array($value)) {
                    return [];
                }

                try {
                    $completedAt = Carbon::parse((string) ($value['completed_at'] ?? ''))->toIso8601String();
                } catch (\Throwable) {
                    return [];
                }

                $userId = isset($value['completed_by_user_id']) ? (int) $value['completed_by_user_id'] : null;

                return [$date => [
                    'completed_at' => $completedAt,
                    'completed_by_user_id' => $userId > 0 ? $userId : null,
                    'completed_by_name' => $this->nullableTrimmedString($value['completed_by_name'] ?? null),
                ]];
            })
            ->filter()
            ->all();
    }

    /**
     * @return array{completed_at: string, completed_by_user_id: int, completed_by_name: ?string}
     */
    private function completionEntryForUser(User $user): array
    {
        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: (string) ($user->name ?? '');

        return [
            'completed_at' => now()->toIso8601String(),
            'completed_by_user_id' => (int) $user->id,
            'completed_by_name' => $name !== '' ? $name : null,
        ];
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string !== '' ? $string : null;
    }

    /**
     * @param  list<string>  $deadlines
     * @param  array<string, array{completed_at: string, completed_by_user_id: ?int, completed_by_name: ?string}>  $completions
     * @return array<string, array{completed_at: string, completed_by_user_id: ?int, completed_by_name: ?string}>
     */
    private function completionsForDeadlines(array $deadlines, array $completions): array
    {
        $allowed = array_fill_keys($deadlines, true);

        return array_intersect_key($completions, $allowed);
    }

    private function isTaskFullyComplete(TaskItem $task): bool
    {
        $deadlines = $this->normalizedDeadlines($task->deadlines);
        if ($deadlines === []) {
            return $task->completed_at !== null;
        }

        $completions = $this->normalizedDeadlineCompletions($task->deadline_completions);

        return collect($deadlines)->every(fn (string $date): bool => isset($completions[$date]));
    }

    private function syncCompletedAtFromDeadlines(TaskItem $task): void
    {
        $deadlines = $this->normalizedDeadlines($task->deadlines);
        if ($deadlines === []) {
            return;
        }

        $completions = $this->normalizedDeadlineCompletions($task->deadline_completions);
        $allComplete = collect($deadlines)->every(fn (string $date): bool => isset($completions[$date]));

        if (! $allComplete) {
            $task->update(['completed_at' => null]);

            return;
        }

        $latest = collect($completions)
            ->map(fn (array $entry): Carbon => Carbon::parse($entry['completed_at']))
            ->sortDesc()
            ->first();

        $task->update([
            'completed_at' => $latest ?? now(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function normalizedSharedSections(mixed $sharedSections): array
    {
        $active = $this->activeSection();

        return collect(is_array($sharedSections) ? $sharedSections : [])
            ->map(fn ($v): string => trim((string) $v))
            ->filter()
            ->filter(fn (string $v): bool => $v !== $active)
            ->filter(fn (string $v): bool => in_array($v, UserSectionRole::ALL_SECTIONS, true))
            ->unique()
            ->values()
            ->all();
    }

    private function canCreateCrossSection(User $user, string $activeSection): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        return $activeSection === UserSectionRole::SECTION_BESTUUR
            && in_array((string) $user->roleInSection(UserSectionRole::SECTION_BESTUUR), UserSectionRole::BESTUUR_ROLES, true);
    }

    /**
     * @return list<string>
     */
    private function targetSectionsForBoard(): array
    {
        return [
            UserSectionRole::SECTION_BEVERS,
            UserSectionRole::SECTION_DOLFIJNEN,
            UserSectionRole::SECTION_ZEEVERKENNERS,
            UserSectionRole::SECTION_WILDE_VAART,
            UserSectionRole::SECTION_LOODSEN,
        ];
    }

    private function canEditOrDeleteTask(User $user, TaskItem $task): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        return $user->roleInSection((string) $task->section) === UserSectionRole::ROLE_TEAMLEIDER;
    }

    private function canCompleteTask(User $user, TaskItem $task): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        if ($this->permissionGate->allows($user, $this->activeSection(), SectionPermission::MODULE_TASK_ITEMS, 'update')) {
            return true;
        }

        $ownerIds = collect($task->owner_user_ids ?? [])
            ->map(fn ($v): int => (int) $v)
            ->filter(fn (int $v): bool => $v > 0)
            ->values()
            ->all();

        if ($task->owner_user_id) {
            $ownerIds[] = (int) $task->owner_user_id;
        }

        return in_array((int) $user->id, array_unique($ownerIds), true);
    }
}
