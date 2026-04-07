<?php

namespace App\Http\Controllers;

use App\Models\TaskCategory;
use App\Models\TaskItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TaskItemController extends Controller
{
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
        $taskCategories = TaskCategory::query()
            ->orderBy('position')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $tasks = TaskItem::query()
            ->get()
            ->sortBy(function (TaskItem $task) use ($taskCategories) {
                $categoryIndex = array_search($task->category, $taskCategories, true);

                return [
                    $categoryIndex === false ? 99 : $categoryIndex,
                    $task->title,
                ];
            })
            ->values()
            ->map(function (TaskItem $task): array {
                return [
                    'id' => $task->id,
                    'category' => $task->category,
                    'title' => $task->title,
                    'owner' => $task->owner,
                    'owner_user_id' => $task->owner_user_id,
                    'description' => $task->description,
                    'deadline' => $task->deadline ? Carbon::parse((string) $task->deadline)->toDateString() : null,
                ];
            });

        $leaders = User::query()
            ->whereNotNull('first_name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $leader) => [
                'id' => $leader->id,
                'name' => trim(($leader->first_name ?? '').' '.($leader->last_name ?? '')) ?: $leader->name,
            ]);

        return Inertia::render('TaskItems/Index', [
            'tasks' => $tasks,
            'taskCategories' => $taskCategories,
            'leaders' => $leaders,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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
            'description' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
        ]);

        $data['owner'] = null;
        if (! empty($data['owner_user_id'])) {
            $owner = User::query()->find($data['owner_user_id']);
            $data['owner'] = $owner?->name;
        }

        TaskItem::create($data);

        return to_route('task-items.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TaskItem $taskItem)
    {
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
            'description' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
        ]);

        $data['owner'] = null;
        if (! empty($data['owner_user_id'])) {
            $owner = User::query()->find($data['owner_user_id']);
            $data['owner'] = $owner?->name;
        }

        $taskItem->update($data);

        return to_route('task-items.index');
    }

    /**
     * Snel één veld bijwerken (tabel + dubbelklik / EditableTextCell).
     */
    public function quickUpdate(Request $request, TaskItem $taskItem)
    {
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
            'description' => ['sometimes', 'nullable', 'string'],
            'deadline' => ['sometimes', 'nullable', 'date'],
        ]);

        if (array_key_exists('owner_user_id', $data)) {
            $data['owner'] = null;
            if (! empty($data['owner_user_id'])) {
                $owner = User::query()->find($data['owner_user_id']);
                $data['owner'] = $owner?->name;
            }
        }

        $taskItem->update($data);

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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TaskItem $taskItem)
    {
        $taskItem->delete();

        return to_route('task-items.index');
    }
}
