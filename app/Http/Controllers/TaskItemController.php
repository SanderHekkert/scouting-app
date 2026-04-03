<?php

namespace App\Http\Controllers;

use App\Models\TaskCategory;
use App\Models\TaskItem;
use App\Models\User;
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
            ->with('ownerUser:id,name')
            ->get()
            ->sortBy(function (TaskItem $task) use ($taskCategories) {
                $categoryIndex = array_search($task->category, $taskCategories, true);

                return [
                    $categoryIndex === false ? 99 : $categoryIndex,
                    $task->title,
                ];
            })->values();

        return Inertia::render('TaskItems/Index', [
            'tasks' => $tasks,
            'taskCategories' => $taskCategories,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
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
            'owner_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'description' => ['nullable', 'string'],
        ]);

        $data['owner'] = null;
        if (! empty($data['owner_user_id'])) {
            $data['owner'] = User::query()->whereKey($data['owner_user_id'])->value('name');
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
            'owner_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'description' => ['nullable', 'string'],
        ]);

        $data['owner'] = null;
        if (! empty($data['owner_user_id'])) {
            $data['owner'] = User::query()->whereKey($data['owner_user_id'])->value('name');
        }

        $taskItem->update($data);

        return to_route('task-items.index');
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
