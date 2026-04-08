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
            in_array(app('currentSection'), [UserSectionRole::SECTION_ZEEVERKENNERS, UserSectionRole::SECTION_LOODSEN, UserSectionRole::SECTION_WILDE_VAART, UserSectionRole::SECTION_BESTUUR], true)
        ) {
            return to_route('opkomsten.index');
        }

        return null;
    }

    public function index(): Response|RedirectResponse
    {
        if ($redirect = $this->ensureAllowed()) {
            return $redirect;
        }

        $section = $this->activeSection();
        $rows = YearThemeEntry::query()
            ->orderBy('sort_order')
            ->get();

        if ($section === UserSectionRole::SECTION_BEVERS) {
            $rows = $rows
                ->sortBy('sort_order')
                ->values()
                ->take(2)
                ->values();

            if (isset($rows[0])) {
                $rows[0]->label = 'Seizoensthema:';
            }
            if (isset($rows[1])) {
                $rows[1]->label = 'Beverkamp thema:';
            }
        }

        return Inertia::render('YearThemes/Index', [
            'rows' => $rows
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

    private function activeSection(): string
    {
        if (app()->bound('currentSection')) {
            $section = app('currentSection');
            if (is_string($section) && $section !== '') {
                return $section;
            }
        }

        $fromSession = session('active_section');
        if (is_string($fromSession) && $fromSession !== '') {
            return $fromSession;
        }

        return UserSectionRole::SECTION_DOLFIJNEN;
    }
}
