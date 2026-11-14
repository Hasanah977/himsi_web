<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canSwitchWorkspace(), 403);
        $data = $request->validate(['role' => ['required', Rule::in(['kbh', $user->assignedRole()])]]);
        $request->session()->put('workspace.'.$user->id, $data['role']);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
