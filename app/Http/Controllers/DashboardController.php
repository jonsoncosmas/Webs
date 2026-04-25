<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $slug = $user->role?->slug ?? 'guest';

        if (in_array($slug, [Role::STUDENT, Role::PARENT_ROLE], true)) {
            return redirect()->route('portal.dashboard');
        }

        $view = match ($slug) {
            Role::SYSTEM_ADMIN => 'dashboard.system-admin',
            Role::DIRECTOR, Role::DEPUTY_DIRECTOR => 'dashboard.director',
            Role::SCHOOL_ADMIN => 'dashboard.school-admin',
            default => 'dashboard.generic',
        };

        return view($view, ['user' => $user]);
    }
}
