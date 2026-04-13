<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LeaderController extends Controller
{
    public function create()
    {
        return Inertia::render('Leaders/Create');
    }

    public function index()
    {
        $activeSection = app()->bound('currentSection') ? app('currentSection') : UserSectionRole::SECTION_DOLFIJNEN;

        return Inertia::render('Leaders/Index', [
            'leaders' => User::query()
                ->whereNotNull('first_name')
                ->whereHas('sectionRoles', function ($query) use ($activeSection): void {
                    $query->where('section', $activeSection)
                        ->whereIn('role', [
                            UserSectionRole::ROLE_TEAMLEIDER,
                            UserSectionRole::ROLE_LEIDING,
                            UserSectionRole::ROLE_OUDERCONTACT,
                        ]);
                })
                ->with(['sectionRoles' => function ($query) use ($activeSection): void {
                    $query->where('section', $activeSection)
                        ->whereIn('role', [
                            UserSectionRole::ROLE_TEAMLEIDER,
                            UserSectionRole::ROLE_LEIDING,
                            UserSectionRole::ROLE_OUDERCONTACT,
                        ]);
                }])
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->map(function (User $leader) {
                    $role = $leader->sectionRoles->pluck('role')->first();

                    return array_merge($leader->toArray(), [
                        'email_verified' => $leader->email_verified_at !== null,
                        'section_role_label' => match ($role) {
                            UserSectionRole::ROLE_TEAMLEIDER => 'Teamleider',
                            UserSectionRole::ROLE_OUDERCONTACT => 'Oudercontact',
                            UserSectionRole::ROLE_LEIDING => 'Leiding',
                            default => null,
                        },
                    ]);
                })
                ->values(),
        ]);
    }

    public function show(User $leader)
    {
        return Inertia::render('Leaders/Show', [
            'leader' => $leader,
        ]);
    }

    /**
     * Snel één veld bijwerken (tabel / detail + EditableTextCell).
     */
    public function quickUpdate(Request $request, User $leader)
    {
        if ($request->has('email') && $request->input('email') === '') {
            $request->merge(['email' => null]);
        }

        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'birthday' => ['sometimes', 'nullable', 'date'],
            'installed' => ['sometimes', 'boolean'],
            'gedoopt' => ['sometimes', 'boolean'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'emergency_contact' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'string', 'max:255', 'email'],
            'bijzonderheden' => ['sometimes', 'nullable', 'string', 'max:65535'],
        ]);

        if (array_key_exists('birthday', $data) && empty($data['birthday'])) {
            $data['birthday'] = null;
        }

        $leader->update($data);

        return back();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'installed' => ['nullable', 'boolean'],
            'gedoopt' => ['nullable', 'boolean'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255', 'email'],
            'bijzonderheden' => ['nullable', 'string', 'max:65535'],
        ]);

        if (empty($data['birthday'])) {
            $data['birthday'] = null;
        }
        $data['installed'] = $request->boolean('installed');
        $data['gedoopt'] = $request->boolean('gedoopt');

        $data['name'] = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
        $data['password'] = Str::password(24);
        User::create($data);

        return to_route('leaders.index');
    }

    public function update(Request $request, User $leader)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'installed' => ['nullable', 'boolean'],
            'gedoopt' => ['nullable', 'boolean'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255', 'email'],
            'bijzonderheden' => ['nullable', 'string', 'max:65535'],
        ]);

        if (empty($data['birthday'])) {
            $data['birthday'] = null;
        }
        $data['installed'] = $request->boolean('installed');
        $data['gedoopt'] = $request->boolean('gedoopt');

        $data['name'] = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
        $leader->update($data);

        return to_route('leaders.index');
    }

    public function destroy(User $leader)
    {
        $leader->delete();

        return to_route('leaders.index');
    }

    public function updateInstalled(Request $request, User $leader)
    {
        $validated = $request->validate([
            'installed' => ['required', 'boolean'],
        ]);
        $leader->update(['installed' => $validated['installed']]);

        return back();
    }

    public function updateGedoopt(Request $request, User $leader)
    {
        $validated = $request->validate([
            'gedoopt' => ['required', 'boolean'],
        ]);
        $leader->update(['gedoopt' => $validated['gedoopt']]);

        return back();
    }
}
