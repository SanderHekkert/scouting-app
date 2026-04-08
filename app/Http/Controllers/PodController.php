<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Pod;
use App\Models\PodMembership;
use App\Models\UserSectionRole;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (
            app()->bound('currentSection') &&
            app('currentSection') === UserSectionRole::SECTION_BEVERS
        ) {
            return to_route('members.index');
        }

        /** Vaste volgorde zoals in de seed (kolommen Narwals → Orinoco's → Tuimelaars → Grampers). */
        $podOrder = ['Narwals', "Orinoco's", 'Tuimelaars', 'Grampers'];

        $pods = Pod::query()
            ->with(['memberships' => function ($q) {
                $q->with('member');
            }])
            ->get()
            ->sortBy(function (Pod $pod) use ($podOrder): int {
                $i = array_search($pod->name, $podOrder, true);

                return $i !== false ? $i : 1000;
            })
            ->values();

        return Inertia::render('Pods/Index', [
            'pods' => $pods,
            'unassignedMembers' => Member::query()
                ->where('active', true)
                ->whereDoesntHave('podMemberships')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'age', 'birthday']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Pod::create($data);

        return to_route('pods.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pod $pod)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $pod->update($data);

        return to_route('pods.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pod $pod)
    {
        $pod->delete();

        return to_route('pods.index');
    }

    public function addMember(Request $request, Pod $pod)
    {
        $useZeeverkennersBakRoles = $this->activeSection() === UserSectionRole::SECTION_ZEEVERKENNERS;

        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'role' => ['required', 'string', 'in:Topper,Tipper,Vinlid'],
        ]);

        $memberId = (int) $data['member_id'];
        $role = $data['role'];

        $existingElsewhere = PodMembership::query()
            ->where('member_id', $memberId)
            ->where('pod_id', '!=', $pod->id)
            ->exists();

        if ($existingElsewhere) {
            throw ValidationException::withMessages([
                'member_id' => 'Deze Dolfijn zit al in een andere vin.',
            ]);
        }

        if ($role === 'Topper') {
            $hasTopper = PodMembership::query()
                ->where('pod_id', $pod->id)
                ->where('role', 'Topper')
                ->where('member_id', '!=', $memberId)
                ->exists();

            if ($hasTopper) {
                throw ValidationException::withMessages([
                    'role' => $useZeeverkennersBakRoles
                        ? 'Er is al een Boots in deze bak.'
                        : 'Er is al een Topper in deze vin.',
                ]);
            }
        }

        if ($role === 'Tipper') {
            $hasTipper = PodMembership::query()
                ->where('pod_id', $pod->id)
                ->where('role', 'Tipper')
                ->where('member_id', '!=', $memberId)
                ->exists();

            if ($hasTipper) {
                throw ValidationException::withMessages([
                    'role' => $useZeeverkennersBakRoles
                        ? 'Er is al een Kwartier in deze bak.'
                        : 'Er is al een Tipper in deze vin.',
                ]);
            }
        }

        PodMembership::updateOrCreate(
            ['pod_id' => $pod->id, 'member_id' => $memberId],
            ['role' => $role],
        );

        return to_route('pods.index');
    }

    public function removeMember(PodMembership $podMembership)
    {
        $podMembership->delete();

        return to_route('pods.index');
    }

    public function moveMember(Request $request, PodMembership $podMembership)
    {
        $data = $request->validate([
            'pod_id' => ['required', 'exists:pods,id'],
            'role' => ['required', 'string', 'in:Topper,Tipper,Vinlid'],
        ]);

        $podId = (int) $data['pod_id'];
        $role = (string) $data['role'];
        $memberId = (int) $podMembership->member_id;

        if ($role === 'Topper') {
            $hasTopper = PodMembership::query()
                ->where('pod_id', $podId)
                ->where('role', 'Topper')
                ->where('member_id', '!=', $memberId)
                ->exists();

            if ($hasTopper) {
                return back()->withErrors([
                    'role' => 'Er is al een Topper/Boots in deze vin/bak.',
                ]);
            }
        }

        if ($role === 'Tipper') {
            $hasTipper = PodMembership::query()
                ->where('pod_id', $podId)
                ->where('role', 'Tipper')
                ->where('member_id', '!=', $memberId)
                ->exists();

            if ($hasTipper) {
                return back()->withErrors([
                    'role' => 'Er is al een Tipper/Kwartier in deze vin/bak.',
                ]);
            }
        }

        $podMembership->update([
            'pod_id' => $podId,
            'role' => $role,
        ]);

        return back();
    }

    private function activeSection(): string
    {
        return session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
    }
}
