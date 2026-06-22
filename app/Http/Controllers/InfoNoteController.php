<?php

namespace App\Http\Controllers;

use App\Models\InfoNote;
use App\Models\User;
use App\Models\UserSectionRole;
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
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        $activeSection = $this->activeSection();
        $canCreateCrossSection = $this->canCreateCrossSection($user, $activeSection);
        $notesQuery = $canCreateCrossSection
            ? InfoNote::withoutGlobalScope('section')->whereIn('section', $this->targetSectionsForBoard(), 'and', false)
            : InfoNote::query();

        return Inertia::render('InfoNotes/Index', [
            'notes' => $notesQuery
                ->orderBy('category', 'asc')
                ->latest()
                ->get()
                ->map(fn (InfoNote $note): array => [
                    'id' => (int) $note->id,
                    'category' => (string) ($note->category ?? ''),
                    'content' => (string) ($note->content ?? ''),
                    'link' => (string) ($note->link ?? ''),
                    'section' => (string) ($note->section ?? ''),
                    'can_update' => $this->canEditOrDelete($user, $note),
                    'can_delete' => $this->canEditOrDelete($user, $note),
                ])
                ->values()
                ->all(),
            'canCreateCrossSection' => $canCreateCrossSection,
            'targetSections' => $canCreateCrossSection ? $this->targetSectionsForBoard() : [],
        ]);
    }

    public function show(InfoNote $info_note)
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canEditOrDelete($user, $info_note), 403);

        return Inertia::render('InfoNotes/Show', [
            'note' => [
                'id' => (int) $info_note->id,
                'category' => (string) ($info_note->category ?? ''),
                'content' => (string) ($info_note->content ?? ''),
                'link' => (string) ($info_note->link ?? ''),
            ],
        ]);
    }

    public function create()
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        $activeSection = $this->activeSection();
        $canCreateCrossSection = $this->canCreateCrossSection($user, $activeSection);

        return Inertia::render('InfoNotes/Create', [
            'canCreateCrossSection' => $canCreateCrossSection,
            'targetSections' => $canCreateCrossSection ? $this->targetSectionsForBoard() : [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $activeSection = $this->activeSection();
        $canCreateCrossSection = $this->canCreateCrossSection($user, $activeSection);

        $this->prepareLinkForValidation($request);
        $data = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'link' => ['nullable', 'string', 'max:2048', 'url'],
            'target_section' => ['nullable', 'string', 'in:bevers,dolfijnen,zeeverkenners,wilde_vaart,loodsen'],
        ]);

        $targetSection = $activeSection;
        if ($canCreateCrossSection && ! empty($data['target_section'])) {
            $targetSection = (string) $data['target_section'];
        }

        InfoNote::create([
            'category' => $data['category'],
            'content' => $data['content'],
            'link' => $data['link'] ?? null,
            'section' => $targetSection,
        ]);

        return $this->redirectAfterSave($request, config('save-redirects.info_notes'));
    }

    /**
     * Snel één veld bijwerken (tabel + dubbelklik).
     */
    public function quickUpdate(Request $request, InfoNote $info_note)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canEditOrDelete($user, $info_note), 403);

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
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canEditOrDelete($user, $info_note), 403);

        $this->prepareLinkForValidation($request);
        $data = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'link' => ['nullable', 'string', 'max:2048', 'url'],
        ]);

        $info_note->update($data);

        return $this->redirectAfterSave($request, config('save-redirects.info_notes'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InfoNote $info_note)
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canEditOrDelete($user, $info_note), 403);

        $info_note->delete();

        return to_route('info-notes.index');
    }

    private function activeSection(): string
    {
        return (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
    }

    private function canCreateCrossSection(User $user, string $activeSection): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        return $activeSection === UserSectionRole::SECTION_BESTUUR
            && in_array((string) $user->roleInSection(UserSectionRole::SECTION_BESTUUR), UserSectionRole::BESTUUR_ROLES, true);
    }

    /**
     * @return list<string>
     */
    private function targetSectionsForBoard(): array
    {
        return [
            UserSectionRole::SECTION_BEVERS,
            UserSectionRole::SECTION_DOLFIJNEN,
            UserSectionRole::SECTION_ZEEVERKENNERS,
            UserSectionRole::SECTION_WILDE_VAART,
            UserSectionRole::SECTION_LOODSEN,
        ];
    }

    private function canEditOrDelete(User $user, InfoNote $note): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        return $user->roleInSection((string) $note->section) === UserSectionRole::ROLE_TEAMLEIDER;
    }
}
