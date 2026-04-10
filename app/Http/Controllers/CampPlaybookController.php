<?php

namespace App\Http\Controllers;

use App\Models\CampPlaybook;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampPlaybookController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('CampPlaybooks/Index', [
            'items' => CampPlaybook::query()
                ->latest('camp_year')
                ->latest('id')
                ->get()
                ->map(fn (CampPlaybook $item): array => [
                    'id' => (int) $item->id,
                    'camp_year' => (int) $item->camp_year,
                    'title' => (string) $item->title,
                    'content' => (string) ($item->content ?? ''),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'camp_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $userId = $request->user()?->id;
        CampPlaybook::create([
            ...$data,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return to_route('camp-playbooks.index');
    }

    public function update(Request $request, CampPlaybook $campPlaybook)
    {
        $data = $request->validate([
            'camp_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $campPlaybook->update([
            ...$data,
            'updated_by_user_id' => $request->user()?->id,
        ]);

        return to_route('camp-playbooks.index');
    }
}
