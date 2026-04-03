<?php

namespace App\Http\Controllers;

use App\Models\InfoNote;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InfoNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('InfoNotes/Index', [
            'notes' => InfoNote::query()->orderBy('category')->latest()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        InfoNote::create($data);

        return to_route('info-notes.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InfoNote $infoNote)
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $infoNote->update($data);

        return to_route('info-notes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InfoNote $infoNote)
    {
        $infoNote->delete();

        return to_route('info-notes.index');
    }
}
