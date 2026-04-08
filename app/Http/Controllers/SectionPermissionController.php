<?php

namespace App\Http\Controllers;

use App\Models\SectionPermission;
use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SectionPermissionController extends Controller
{
    public function index(Request $request): Response
    {
        [$user, $manageableSections, $isAdmin] = $this->accessContext($request);

        $requestedSection = (string) $request->query('section', '');
        $section = $isAdmin
            ? (in_array($requestedSection, $manageableSections, true) ? $requestedSection : ($manageableSections[0] ?? UserSectionRole::SECTION_DOLFIJNEN))
            : ($manageableSections[0] ?? UserSectionRole::SECTION_DOLFIJNEN);

        $rows = SectionPermission::query()
            ->where('section', $section)
            ->whereIn('role', [UserSectionRole::ROLE_LEIDING, UserSectionRole::ROLE_OUDERCONTACT])
            ->orderBy('role')
            ->orderBy('module')
            ->get()
            ->map(fn (SectionPermission $row) => [
                'id' => $row->id,
                'section' => $row->section,
                'role' => $row->role,
                'module' => $row->module,
                'can_view' => (bool) $row->can_view,
                'can_create' => (bool) $row->can_create,
                'can_update' => (bool) $row->can_update,
                'can_delete' => (bool) $row->can_delete,
            ])
            ->values();

        return Inertia::render('Admin/Permissions', [
            'manageableSections' => $manageableSections,
            'selectedSection' => $section,
            'isAdmin' => $isAdmin,
            'rows' => $rows,
            'roles' => [UserSectionRole::ROLE_LEIDING, UserSectionRole::ROLE_OUDERCONTACT],
            'modules' => SectionPermission::ALL_MODULES,
        ]);
    }

    public function update(Request $request, SectionPermission $sectionPermission)
    {
        [, $manageableSections, $isAdmin] = $this->accessContext($request);

        if (! $isAdmin && ! in_array($sectionPermission->section, $manageableSections, true)) {
            abort(403, 'Je mag alleen rechten in je eigen speltak beheren.');
        }

        $data = $request->validate([
            'can_view' => ['required', 'boolean'],
            'can_create' => ['required', 'boolean'],
            'can_update' => ['required', 'boolean'],
            'can_delete' => ['required', 'boolean'],
        ]);

        $sectionPermission->update($data);

        return back();
    }

    /**
     * @return array{0:User,1:list<string>,2:bool}
     */
    private function accessContext(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $isAdmin = $user->isGlobalAdmin();
        if ($isAdmin) {
            return [$user, UserSectionRole::ALL_SECTIONS, true];
        }

        $manageableSections = $user->sectionRoles()
            ->where('role', UserSectionRole::ROLE_TEAMLEIDER)
            ->whereIn('section', UserSectionRole::ALL_SECTIONS)
            ->pluck('section')
            ->unique()
            ->values()
            ->all();

        if ($manageableSections === []) {
            abort(403, 'Alleen teamleider of admin kan rechten beheren.');
        }

        return [$user, $manageableSections, false];
    }
}
