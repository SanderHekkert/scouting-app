<?php

namespace App\Http\Controllers;

use App\Models\TaskItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('TaskItems/Index', [
            'tasks' => TaskItem::query()->latest()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'owner' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        TaskItem::create($data);

        return to_route('task-items.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TaskItem $taskItem)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'owner' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $taskItem->update($data);

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
