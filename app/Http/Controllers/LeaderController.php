<?php

namespace App\Http\Controllers;

use App\Models\Leader;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderController extends Controller
{
    public function index(Request $request)
    {
        $editId = $request->integer('edit');

        return Inertia::render('Leaders/Index', [
            'leaders' => Leader::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
            'open_edit_leader_id' => $editId > 0 ? $editId : null,
        ]);
    }

    public function show(Leader $leader)
    {
        return Inertia::render('Leaders/Show', [
            'leader' => $leader,
        ]);
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

        Leader::create($data);

        return to_route('leaders.index');
    }

    public function update(Request $request, Leader $leader)
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

        $leader->update($data);

        return to_route('leaders.index');
    }

    public function destroy(Leader $leader)
    {
        $leader->delete();

        return to_route('leaders.index');
    }
}
