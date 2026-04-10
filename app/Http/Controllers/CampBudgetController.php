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
}
