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

    public function create(Request $request): Response
    {
        $copyId = (int) $request->query('copy', 0);
        $copyItem = null;
        if ($copyId > 0) {
            $source = CampPlaybook::query()->find($copyId);
            if ($source && (string) $source->section === (string) session('active_section', 'dolfijnen')) {
                $copyItem = [
                    'camp_year' => (int) $source->camp_year,
                    'title' => (string) $source->title,
                    'content' => (string) ($source->content ?? ''),
                ];
            }
        }

        return Inertia::render('CampPlaybooks/Show', [
            'mode' => 'create',
            'item' => null,
            'copyItem' => $copyItem,
        ]);
    }

    public function show(CampPlaybook $campPlaybook): Response
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);

        return Inertia::render('CampPlaybooks/Show', [
            'mode' => 'edit',
            'item' => [
                'id' => (int) $campPlaybook->id,
                'camp_year' => (int) $campPlaybook->camp_year,
                'title' => (string) $campPlaybook->title,
                'content' => (string) ($campPlaybook->content ?? ''),
            ],
            'copyItem' => null,
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
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);

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

    public function destroy(CampPlaybook $campPlaybook)
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);
        $campPlaybook->delete();

        return to_route('camp-playbooks.index');
    }

    public function copy(CampPlaybook $campPlaybook)
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);

        $userId = request()->user()?->id;
        CampPlaybook::create([
            'section' => (string) $campPlaybook->section,
            'camp_year' => (int) $campPlaybook->camp_year,
            'title' => (string) $campPlaybook->title.' (kopie)',
            'content' => (string) ($campPlaybook->content ?? ''),
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return to_route('camp-playbooks.index');
    }
}
