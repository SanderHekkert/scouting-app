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
        return $this->renderMembersIndex($request);
    }

    public function indexBijzonderheden(Request $request)
    {
        return $this->renderMembersIndex($request);
    }

    private function renderMembersIndex(Request $request)
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
            'last_name' => ['nullable', 'string', 'max:255'],
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
        $data['last_name'] = trim((string) ($data['last_name'] ?? ''));

        Member::create($data);

        return back();
    }

    /**
     * Snel één tekstveld bijwerken (tabel / detail + EditableTextCell).
     */
    public function quickUpdate(Request $request, Member $member)
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'birthday' => ['sometimes', 'nullable', 'date'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone_mother' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone_father' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bijzonderheden' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:99'],
        ]);

        if (array_key_exists('last_name', $data)) {
            $data['last_name'] = trim((string) ($data['last_name'] ?? ''));
        }

        $member->update($data);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Member $member)
    {
        $data = $request->validate([
            'installed' => ['nullable', 'boolean'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
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
        $data['last_name'] = trim((string) ($data['last_name'] ?? ''));

        $member->update($data);

        return back();
    }

    public function updateInstalled(Request $request, Member $member)
    {
        $validated = $request->validate([
            'installed' => ['required', 'boolean'],
        ]);

        $member->update(['installed' => $validated['installed']]);

        return back();
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
    public function destroy(Request $request, Member $member)
    {
        $member->delete();

        $referer = (string) $request->headers->get('referer', '');
        if (str_contains($referer, '/members/bijzonderheden')) {
            return to_route('members.bijzonderheden');
        }

        return to_route('members.index');
    }
}
