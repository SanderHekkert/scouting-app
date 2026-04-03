<?php

namespace App\Http\Controllers;

use App\Models\Pod;
use App\Models\PodMembership;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Pods/Index', [
            'pods' => Pod::query()->with('memberships.member')->orderBy('name')->get(),
            'members' => Member::query()->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
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
            'role' => ['required', 'string', 'max:255'],
        ]);

        PodMembership::updateOrCreate(
            ['pod_id' => $pod->id, 'member_id' => $data['member_id']],
            ['role' => $data['role']],
        );

        return to_route('pods.index');
    }

    public function removeMember(PodMembership $podMembership)
    {
        $podMembership->delete();

        return to_route('pods.index');
    }
}
