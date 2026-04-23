<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function show(): View
    {
        return view('auth.change-password');
    }

    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];

        // After the first forced change, require current password too.
        if (! $user->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $data = $request->validate($rules);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        $logger->log('auth.password_changed');

        return redirect()->route('dashboard')->with('status', 'Password updated.');
    }
}
