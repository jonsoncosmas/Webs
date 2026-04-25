<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ResultReviewRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\PortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __construct(private readonly PortalService $service) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $this->assertPortalAccess($user);

        $student = $this->service->defaultStudentFor($user, $request->integer('student_id') ?: null);

        if (! $student) {
            return view('portal.no-child', ['user' => $user]);
        }

        $attempts = $this->service->attemptsFor($student);
        $exams = $this->service->availableExamsFor($student);
        $reviews = ResultReviewRequest::query()
            ->where('student_user_id', $student->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('portal.dashboard', [
            'user' => $user,
            'student' => $student,
            'children' => $user->hasRole(Role::PARENT_ROLE) ? $user->children()->with('role')->get() : collect(),
            'attempts' => $attempts,
            'exams' => $exams,
            'reviews' => $reviews,
        ]);
    }

    public function exams(Request $request): View
    {
        $user = $request->user();
        $this->assertPortalAccess($user);
        $student = $this->service->defaultStudentFor($user, $request->integer('student_id') ?: null);
        abort_if($student === null, 404);

        return view('portal.exams', [
            'user' => $user,
            'student' => $student,
            'exams' => $this->service->availableExamsFor($student),
        ]);
    }

    public function results(Request $request): View
    {
        $user = $request->user();
        $this->assertPortalAccess($user);
        $student = $this->service->defaultStudentFor($user, $request->integer('student_id') ?: null);
        abort_if($student === null, 404);

        return view('portal.results', [
            'user' => $user,
            'student' => $student,
            'attempts' => $this->service->attemptsFor($student),
        ]);
    }

    public function showResult(Request $request, ExamAttempt $attempt): View
    {
        $this->authorize('view', $attempt);

        return view('portal.result-show', [
            'user' => $request->user(),
            'attempt' => $attempt->load(['exam', 'scorer', 'student', 'reviewRequests.decider']),
        ]);
    }

    public function submitReview(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        if (! $request->user()->can('create', [ResultReviewRequest::class, $attempt])) {
            abort(403);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $this->service->submitReviewRequest($request->user(), $attempt, $data['reason']);

        return redirect()->route('portal.result.show', $attempt)->with('status', 'Review request submitted.');
    }

    public function reviews(Request $request): View
    {
        $user = $request->user();
        $this->assertPortalAccess($user);
        $student = $this->service->defaultStudentFor($user, $request->integer('student_id') ?: null);
        abort_if($student === null, 404);

        $reviews = ResultReviewRequest::query()
            ->where('student_user_id', $student->id)
            ->with(['attempt.exam', 'decider'])
            ->orderByDesc('created_at')
            ->get();

        return view('portal.reviews', [
            'user' => $user,
            'student' => $student,
            'reviews' => $reviews,
        ]);
    }

    private function assertPortalAccess(User $user): void
    {
        if (! $user->hasRole(Role::STUDENT, Role::PARENT_ROLE)) {
            abort(403, 'Portal is for students and parents.');
        }
    }
}
