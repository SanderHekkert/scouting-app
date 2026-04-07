<?php

namespace App\Http\Controllers;

use App\Models\UserSectionRole;
use App\Models\YearThemeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class YearThemeController extends Controller
{
    private function ensureAllowed(): ?RedirectResponse
    {
        if (
            app()->bound('currentSection') &&
            in_array(app('currentSection'), [UserSectionRole::SECTION_ZEEVERKENNERS, UserSectionRole::SECTION_WILDE_VAART], true)
        ) {
            return to_route('events.index');
        }

        return null;
    }

    public function index(): Response|RedirectResponse
    {
        if ($redirect = $this->ensureAllowed()) {
            return $redirect;
        }

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

    public function updateEntry(Request $request, YearThemeEntry $yearThemeEntry): RedirectResponse
    {
        if ($redirect = $this->ensureAllowed()) {
            return $redirect;
        }

        $data = $request->validate([
            'value' => ['nullable', 'string', 'max:65535'],
        ]);

        $yearThemeEntry->update($data);

        return back();
    }
}
