<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $editId = $request->integer('edit');

        return Inertia::render('Members/Index', [
            'members' => Member::query()
                ->orderByRaw('CASE WHEN age IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('age')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
            'open_edit_member_id' => $editId > 0 ? $editId : null,
        ]);
    }

    public function show(Member $member)
    {
        return Inertia::render('Members/Show', [
            'member' => $member,
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
            'bijzonderheden' => ['nullable', 'string', 'max:65535'],
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
            'bijzonderheden' => ['nullable', 'string', 'max:65535'],
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

        if ($validated['tipper_topper_opkomst']) {
            $maxOrder = Member::query()
                ->where('tipper_topper_opkomst', true)
                ->where('id', '!=', $member->id)
                ->max('tipper_topper_opkomst_order');

            $member->update([
                'tipper_topper_opkomst' => true,
                'tipper_topper_opkomst_order' => $maxOrder === null ? 0 : ((int) $maxOrder) + 1,
            ]);
        } else {
            $maxNeeOrder = Member::query()
                ->where('tipper_topper_opkomst', false)
                ->where('id', '!=', $member->id)
                ->max('tipper_topper_opkomst_order');

            $member->update([
                'tipper_topper_opkomst' => false,
                'tipper_topper_opkomst_order' => $maxNeeOrder === null ? 0 : ((int) $maxNeeOrder) + 1,
            ]);
        }

        return back();
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
