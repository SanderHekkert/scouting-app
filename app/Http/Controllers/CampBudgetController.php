<?php

namespace App\Http\Controllers;

use App\Models\CampBudget;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampBudgetController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('CampBudgets/Index', [
            'items' => CampBudget::query()
                ->latest('camp_year')
                ->latest('id')
                ->get()
                ->map(fn (CampBudget $item): array => [
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
            $source = CampBudget::query()->find($copyId);
            if ($source && (string) $source->section === (string) session('active_section', 'dolfijnen')) {
                $copyItem = [
                    'camp_year' => (int) $source->camp_year,
                    'title' => (string) $source->title,
                    'content' => (string) ($source->content ?? ''),
                ];
            }
        }

        return Inertia::render('CampBudgets/Show', [
            'mode' => 'create',
            'item' => null,
            'copyItem' => $copyItem,
        ]);
    }

    public function show(CampBudget $campBudget): Response
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);

        return Inertia::render('CampBudgets/Show', [
            'mode' => 'edit',
            'item' => [
                'id' => (int) $campBudget->id,
                'camp_year' => (int) $campBudget->camp_year,
                'title' => (string) $campBudget->title,
                'content' => (string) ($campBudget->content ?? ''),
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
        CampBudget::create([
            ...$data,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return to_route('camp-budgets.index');
    }

    public function update(Request $request, CampBudget $campBudget)
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);

        $data = $request->validate([
            'camp_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $campBudget->update([
            ...$data,
            'updated_by_user_id' => $request->user()?->id,
        ]);

        return to_route('camp-budgets.index');
    }

    public function destroy(CampBudget $campBudget)
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);
        $campBudget->delete();

        return to_route('camp-budgets.index');
    }

    public function copy(CampBudget $campBudget)
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);

        $userId = request()->user()?->id;
        CampBudget::create([
            'section' => (string) $campBudget->section,
            'camp_year' => (int) $campBudget->camp_year,
            'title' => (string) $campBudget->title.' (kopie)',
            'content' => (string) ($campBudget->content ?? ''),
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return to_route('camp-budgets.index');
    }
}
