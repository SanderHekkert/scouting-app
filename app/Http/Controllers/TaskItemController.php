<?php

namespace App\Http\Controllers;

use App\Models\Leader;
use App\Models\TaskCategory;
use App\Models\TaskItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TaskItemController extends Controller
{
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
            ->with('ownerLeader:id,first_name,last_name')
            ->get()
            ->sortBy(function (TaskItem $task) use ($taskCategories) {
                $categoryIndex = array_search($task->category, $taskCategories, true);

                return [
                    $categoryIndex === false ? 99 : $categoryIndex,
                    $task->title,
                ];
            })->values();

        $leaders = Leader::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Leader $leader) => [
                'id' => $leader->id,
                'name' => $leader->full_name,
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
        $data = $request->validate([
            'category' => ['required', 'string', Rule::exists('task_categories', 'name')],
            'title' => ['required', 'string', 'max:255'],
            'owner_leader_id' => ['nullable', 'integer', Rule::exists('leaders', 'id')],
            'description' => ['nullable', 'string'],
        ]);

        $data['owner'] = null;
        $data['owner_user_id'] = null;
        if (! empty($data['owner_leader_id'])) {
            $data['owner'] = Leader::query()->find($data['owner_leader_id'])?->full_name;
        }

        TaskItem::create($data);

        return to_route('task-items.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TaskItem $taskItem)
    {
        $data = $request->validate([
            'category' => ['required', 'string', Rule::exists('task_categories', 'name')],
            'title' => ['required', 'string', 'max:255'],
            'owner_leader_id' => ['nullable', 'integer', Rule::exists('leaders', 'id')],
            'description' => ['nullable', 'string'],
        ]);

        $data['owner'] = null;
        $data['owner_user_id'] = null;
        if (! empty($data['owner_leader_id'])) {
            $data['owner'] = Leader::query()->find($data['owner_leader_id'])?->full_name;
        }

        $taskItem->update($data);

        return to_route('task-items.index');
    }

    /**
     * Snel één veld bijwerken (tabel + dubbelklik / EditableTextCell).
     */
    public function quickUpdate(Request $request, TaskItem $taskItem)
    {
        $data = $request->validate([
            'category' => ['sometimes', 'required', 'string', Rule::exists('task_categories', 'name')],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'owner_leader_id' => ['sometimes', 'nullable', 'integer', Rule::exists('leaders', 'id')],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_key_exists('owner_leader_id', $data)) {
            $data['owner_user_id'] = null;
            $data['owner'] = null;
            if (! empty($data['owner_leader_id'])) {
                $data['owner'] = Leader::query()->find($data['owner_leader_id'])?->full_name;
            }
        }

        $taskItem->update($data);

        return back();
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:task_categories,name'],
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
