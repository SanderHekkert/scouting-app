<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Leader;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Members/Index', [
            'members' => Member::query()
                ->orderByRaw('CASE WHEN age IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('age')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
            'leaders' => Leader::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'installed' => ['nullable', 'boolean'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'age' => ['nullable', 'integer', 'min:0', 'max:99'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone_mother' => ['nullable', 'string', 'max:255'],
            'phone_father' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['installed'] = $request->boolean('installed');
        $data['active'] = $request->boolean('active', true);

        Member::create($data);

        return to_route('members.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Member $member)
    {
        $data = $request->validate([
            'installed' => ['nullable', 'boolean'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'age' => ['nullable', 'integer', 'min:0', 'max:99'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone_mother' => ['nullable', 'string', 'max:255'],
            'phone_father' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['installed'] = $request->boolean('installed');
        $data['active'] = $request->boolean('active', true);

        $member->update($data);

        return to_route('members.index');
    }

    public function updateTipperTopperOpkomst(Request $request, Member $member)
    {
        $validated = $request->validate([
            'tipper_topper_opkomst' => ['required', 'boolean'],
        ]);

        $member->update([
            'tipper_topper_opkomst' => $validated['tipper_topper_opkomst'],
        ]);

        return to_route('members.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member)
    {
        $member->delete();

        return to_route('members.index');
    }
}
