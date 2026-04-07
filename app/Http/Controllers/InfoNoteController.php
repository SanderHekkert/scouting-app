<?php

namespace App\Http\Controllers;

use App\Models\InfoNote;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InfoNoteController extends Controller
{
    protected function prepareLinkForValidation(Request $request): void
    {
        $link = $request->input('link');
        if (! is_string($link) || trim($link) === '') {
            $request->merge(['link' => null]);

            return;
        }
        $link = trim($link);
        if (! preg_match('#^https?://#i', $link)) {
            $link = 'https://'.$link;
        }
        $request->merge(['link' => $link]);
    }

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
        $this->prepareLinkForValidation($request);
        $data = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'link' => ['nullable', 'string', 'max:2048', 'url'],
        ]);

        InfoNote::create($data);

        return to_route('info-notes.index');
    }

    /**
     * Snel één veld bijwerken (tabel + dubbelklik).
     */
    public function quickUpdate(Request $request, InfoNote $info_note)
    {
        if ($request->has('link')) {
            $this->prepareLinkForValidation($request);
        }

        $data = $request->validate([
            'category' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'link' => ['sometimes', 'nullable', 'string', 'max:2048', 'url'],
        ]);

        $info_note->update($data);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InfoNote $info_note)
    {
        $this->prepareLinkForValidation($request);
        $data = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'link' => ['nullable', 'string', 'max:2048', 'url'],
        ]);

        $info_note->update($data);

        return to_route('info-notes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InfoNote $info_note)
    {
        $info_note->delete();

        return to_route('info-notes.index');
    }
}
