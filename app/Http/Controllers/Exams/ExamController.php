<?php

namespace App\Http\Controllers\Exams;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamRequest;
use App\Models\Exam;
use App\Models\Role;
use App\Services\Exams\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Exam::class);

        $user = $request->user();

        $query = Exam::query()->latest();
        if (! $user->hasRole(Role::SYSTEM_ADMIN)) {
            $query->where('school_id', $user->school_id);
        }

        return view('exams.index', [
            'exams' => $query->with(['creator.role', 'approver'])->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Exam::class);

        return view('exams.create');
    }

    public function store(StoreExamRequest $request, ExamService $service): RedirectResponse
    {
        $exam = $service->create($request->user(), $request->validated());

        return redirect()
            ->route('exams.show', $exam)
            ->with('status', $exam->isLocked() ? 'Exam created & auto-approved (Director-locked).' : 'Exam draft created.');
    }

    public function show(Exam $exam): View
    {
        $this->authorize('view', $exam);

        return view('exams.show', [
            'exam' => $exam->load(['creator.role', 'lockedTo', 'approver', 'events.actor.role']),
        ]);
    }

    public function submit(Request $request, Exam $exam, ExamService $service): RedirectResponse
    {
        $this->authorize('submit', $exam);
        $service->submit($request->user(), $exam);

        return redirect()->route('exams.show', $exam)->with('status', 'Submitted for approval.');
    }

    public function approve(Request $request, Exam $exam, ExamService $service): RedirectResponse
    {
        $this->authorize('approve', $exam);
        $service->approve($request->user(), $exam, $request->input('comment'));

        return redirect()->route('exams.show', $exam)->with('status', 'Approved.');
    }

    public function reject(Request $request, Exam $exam, ExamService $service): RedirectResponse
    {
        $this->authorize('reject', $exam);
        $service->reject($request->user(), $exam, $request->input('comment'));

        return redirect()->route('exams.show', $exam)->with('status', 'Rejected. Creator may revise.');
    }

    public function override(Request $request, Exam $exam, ExamService $service): RedirectResponse
    {
        $this->authorize('override', $exam);
        $service->override($request->user(), $exam, $request->input('comment'));

        return redirect()
            ->route('exams.show', $exam)
            ->with('status', 'Overridden — you are now the exam owner.');
    }

    public function publish(Request $request, Exam $exam, ExamService $service): RedirectResponse
    {
        $this->authorize('publish', $exam);
        $service->publish($request->user(), $exam);

        return redirect()->route('exams.show', $exam)->with('status', 'Published.');
    }

    public function archive(Request $request, Exam $exam, ExamService $service): RedirectResponse
    {
        $this->authorize('archive', $exam);
        $service->archive($request->user(), $exam);

        return redirect()->route('exams.show', $exam)->with('status', 'Archived.');
    }
}
