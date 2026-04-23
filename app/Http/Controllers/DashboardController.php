<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $slug = $user->role?->slug ?? 'guest';

        $view = match ($slug) {
            Role::SYSTEM_ADMIN => 'dashboard.system-admin',
            Role::DIRECTOR, Role::DEPUTY_DIRECTOR => 'dashboard.director',
            Role::SCHOOL_ADMIN => 'dashboard.school-admin',
            default => 'dashboard.generic',
        };

        return view($view, ['user' => $user]);
    }
}
