<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\PortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttemptScoreController extends Controller
{
    public function __construct(private readonly PortalService $service) {}

    public function create(Request $request, Exam $exam): View
    {
        $this->authorize('viewAny', ExamAttempt::class);
        $actor = $request->user();
        abort_if(
            $exam->school_id !== $actor->school_id && ! $actor->hasRole(Role::SYSTEM_ADMIN),
            403,
        );

        $students = User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->when(! $actor->hasRole(Role::SYSTEM_ADMIN), fn ($q) => $q->where('school_id', $actor->school_id))
            ->orderBy('first_name')
            ->get();

        $existing = ExamAttempt::where('exam_id', $exam->id)
            ->pluck('score', 'student_user_id');

        return view('portal.scores.create', [
            'exam' => $exam,
            'students' => $students,
            'existing' => $existing,
        ]);
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('viewAny', ExamAttempt::class);
        $actor = $request->user();

        $data = $request->validate([
            'student_user_id' => ['required', 'integer', 'exists:users,id'],
            'total_marks' => ['required', 'integer', 'min:1'],
            'score' => ['required', 'integer', 'min:0', 'lte:total_marks'],
            'grade' => ['nullable', 'string', 'max:5'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $student = User::findOrFail($data['student_user_id']);
        if ($exam->school_id !== $actor->school_id && ! $actor->hasRole(Role::SYSTEM_ADMIN)) {
            abort(403);
        }
        if ($student->school_id !== $actor->school_id && ! $actor->hasRole(Role::SYSTEM_ADMIN)) {
            abort(403);
        }
        if ($exam->school_id !== $student->school_id) {
            abort(403);
        }

        $attempt = $this->service->recordScore(
            $actor,
            $student,
            $exam,
            (int) $data['score'],
            (int) $data['total_marks'],
            $data['grade'] ?? null,
            $data['notes'] ?? null,
        );

        return redirect()->route('portal.scores.create', $exam)
            ->with('status', "Score recorded for {$student->fullName()}.");
    }
}
