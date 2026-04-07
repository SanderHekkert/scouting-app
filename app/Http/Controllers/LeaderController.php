<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderController extends Controller
{
    public function index(Request $request)
    {
        $editId = $request->integer('edit');
        $activeSection = app()->bound('currentSection') ? app('currentSection') : UserSectionRole::SECTION_DOLFIJNEN;

        return Inertia::render('Leaders/Index', [
            'leaders' => User::query()
                ->whereNotNull('first_name')
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

                    return [
                        ...$leader->toArray(),
                        'section_role_label' => match ($role) {
                            UserSectionRole::ROLE_TEAMLEIDER => 'Teamleider',
                            UserSectionRole::ROLE_OUDERCONTACT => 'Oudercontact',
                            UserSectionRole::ROLE_LEIDING => 'Leiding',
                            default => null,
                        },
                    ];
                }),
            'open_edit_leader_id' => $editId > 0 ? $editId : null,
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
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:255'],
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
            'phone_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255', 'email'],
            'bijzonderheden' => ['nullable', 'string', 'max:65535'],
        ]);

        if (empty($data['birthday'])) {
            $data['birthday'] = null;
        }

        $data['name'] = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
        $data['password'] = \Database\Seeders\bcrypt('password');
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
            'phone_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255', 'email'],
            'bijzonderheden' => ['nullable', 'string', 'max:65535'],
        ]);

        if (empty($data['birthday'])) {
            $data['birthday'] = null;
        }

        $data['name'] = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
        $leader->update($data);

        return to_route('leaders.index');
    }

    public function destroy(User $leader)
    {
        $leader->delete();

        return to_route('leaders.index');
    }
}
