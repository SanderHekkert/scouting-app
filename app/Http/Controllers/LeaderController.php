<?php

namespace App\Http\Controllers;

use App\Models\Leader;
use Illuminate\Http\Request;

class LeaderController extends Controller
{
    public function updateBijzonderheden(Request $request, Leader $leader)
    {
        $data = $request->validate([
            'bijzonderheden' => ['nullable', 'string', 'max:65535'],
        ]);

        $leader->update($data);

        return to_route('members.index');
    }
}
