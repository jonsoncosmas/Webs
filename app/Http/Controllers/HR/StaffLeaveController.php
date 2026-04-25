<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\StaffLeave;
use App\Models\User;
use App\Services\HR\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffLeaveController extends Controller
{
    public function store(Request $request, User $subject, StaffService $service): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor->can('createFor', [StaffLeave::class, $subject])) {
            abort(403);
        }

        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', StaffLeave::TYPES)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->requestLeave($actor, $subject, $data);

        return redirect()->route('hr.show', $subject)->with('status', 'Leave request submitted.');
    }

    public function approve(Request $request, StaffLeave $leave, StaffService $service): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor->can('decide', $leave)) {
            abort(403);
        }

        $data = $request->validate([
            'decision_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->approveLeave($actor, $leave, $data['decision_comment'] ?? null);

        return redirect()->route('hr.show', $leave->user)->with('status', 'Leave approved.');
    }

    public function reject(Request $request, StaffLeave $leave, StaffService $service): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor->can('decide', $leave)) {
            abort(403);
        }

        $data = $request->validate([
            'decision_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->rejectLeave($actor, $leave, $data['decision_comment'] ?? null);

        return redirect()->route('hr.show', $leave->user)->with('status', 'Leave rejected.');
    }

    public function cancel(Request $request, StaffLeave $leave, StaffService $service): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor->can('cancel', $leave)) {
            abort(403);
        }

        $service->cancelLeave($actor, $leave);

        return redirect()->route('hr.show', $leave->user)->with('status', 'Leave cancelled.');
    }
}
