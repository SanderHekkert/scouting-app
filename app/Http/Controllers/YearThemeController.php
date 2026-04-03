<?php

namespace App\Http\Controllers;

use App\Models\YearThemeEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class YearThemeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('YearThemes/Index', [
            'rows' => YearThemeEntry::query()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (YearThemeEntry $e) => [
                    'id' => $e->id,
                    'label' => $e->label,
                    'value' => $e->value ?? '',
                ]),
        ]);
    }

    public function updateEntry(Request $request, YearThemeEntry $yearThemeEntry)
    {
        $data = $request->validate([
            'value' => ['nullable', 'string', 'max:65535'],
        ]);

        $yearThemeEntry->update($data);

        return back();
    }
}
