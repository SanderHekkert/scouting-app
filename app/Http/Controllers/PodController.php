<?php

namespace App\Http\Controllers;

use App\Models\Pod;
use App\Models\PodMembership;
use App\Models\Member;
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
        $pods = Pod::query()
            ->with(['memberships' => function ($q) {
                $q->with('member');
            }])
            ->orderBy('name')
            ->get();

        return Inertia::render('Pods/Index', [
            'pods' => $pods,
            'unassignedMembers' => Member::query()
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
                'member_id' => 'Dit lid zit al in een andere vin.',
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
                    'role' => 'Er is al een Topper in deze vin.',
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
                    'role' => 'Er is al een Tipper in deze vin.',
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
}
