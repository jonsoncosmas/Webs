<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\StaffCertificate;
use App\Models\StaffLeave;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\HR\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', StaffProfile::class);
        $user = $request->user();

        $query = User::query()
            ->with(['role', 'staffProfile'])
            ->orderBy('first_name');

        if (! $user->hasRole(Role::SYSTEM_ADMIN)) {
            $query->where('school_id', $user->school_id);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return view('hr.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function show(Request $request, User $subject): View
    {
        $actor = $request->user();
        $this->authorizeView($actor, $subject);

        $subject->loadMissing(['role', 'school', 'staffProfile.lastEditor']);

        return view('hr.show', [
            'actor' => $actor,
            'subject' => $subject,
            'profile' => $subject->staffProfile,
            'certificates' => $subject->certificates()->with('addedBy')->latest()->get(),
            'leaves' => $subject->leaves()->with(['requester', 'decider'])->latest()->get(),
            'canEdit' => $actor->can('updateFor', [StaffProfile::class, $subject]),
            'canAddCert' => $actor->can('createFor', [StaffCertificate::class, $subject]),
            'canRequestLeaveFor' => $actor->can('createFor', [StaffLeave::class, $subject]),
            'employmentTypes' => StaffProfile::EMPLOYMENT_TYPES,
            'leaveTypes' => StaffLeave::TYPES,
        ]);
    }

    public function me(Request $request): View
    {
        $user = $request->user();
        if (! $user->can('viewUser', [StaffProfile::class, $user])) {
            abort(403);
        }

        return $this->show($request, $user);
    }

    public function updateProfile(Request $request, User $subject, StaffService $service): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor->can('updateFor', [StaffProfile::class, $subject])) {
            abort(403);
        }

        $data = $request->validate([
            'employee_no' => ['nullable', 'string', 'max:60'],
            'employment_type' => ['nullable', 'in:'.implode(',', StaffProfile::EMPLOYMENT_TYPES)],
            'hired_on' => ['nullable', 'date'],
            'contract_ends_on' => ['nullable', 'date'],
            'nhif_number' => ['nullable', 'string', 'max:60'],
            'nssf_number' => ['nullable', 'string', 'max:60'],
            'tin_number' => ['nullable', 'string', 'max:60'],
            'national_id' => ['nullable', 'string', 'max:60'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account' => ['nullable', 'string', 'max:60'],
            'bank_branch' => ['nullable', 'string', 'max:120'],
            'next_of_kin_name' => ['nullable', 'string', 'max:120'],
            'next_of_kin_relation' => ['nullable', 'string', 'max:60'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:40'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'marital_status' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->upsertProfile($actor, $subject, $data);

        return redirect()->route('hr.show', $subject)->with('status', 'Profile saved.');
    }

    public function suspend(Request $request, User $subject, StaffService $service): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor->can('suspend', [StaffProfile::class, $subject])) {
            abort(403);
        }

        $service->setStatus($actor, $subject, User::STATUS_SUSPENDED);

        return redirect()->route('hr.show', $subject)->with('status', 'Staff suspended.');
    }

    public function deactivate(Request $request, User $subject, StaffService $service): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor->can('suspend', [StaffProfile::class, $subject])) {
            abort(403);
        }

        $service->setStatus($actor, $subject, User::STATUS_DEACTIVATED);

        return redirect()->route('hr.show', $subject)->with('status', 'Staff deactivated.');
    }

    public function activate(Request $request, User $subject, StaffService $service): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor->can('activate', [StaffProfile::class, $subject])) {
            abort(403);
        }

        $service->setStatus($actor, $subject, User::STATUS_ACTIVE);

        return redirect()->route('hr.show', $subject)->with('status', 'Staff reactivated.');
    }

    private function authorizeView(User $actor, User $subject): void
    {
        if ($actor->can('viewUser', [StaffProfile::class, $subject])) {
            return;
        }

        abort(403);
    }
}
