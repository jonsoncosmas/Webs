<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $service) {}

    public function overview(Request $request): View
    {
        $school = $this->resolveSchool($request);

        return view('analytics.overview', [
            'school' => $school,
            'kpis' => $this->service->schoolOverview($school),
            'trend' => $this->service->scoringTrend($school, 14),
            'correlation' => $this->service->behaviourAcademicsCorrelation($school),
        ]);
    }

    public function exams(Request $request): View
    {
        $school = $this->resolveSchool($request);

        return view('analytics.exams', [
            'school' => $school,
            'kpis' => $this->service->schoolOverview($school),
            'distribution' => $this->service->scoreDistribution($school),
            'bySubject' => $this->service->publishedExamsBySubject($school),
        ]);
    }

    public function discipline(Request $request): View
    {
        $school = $this->resolveSchool($request);

        return view('analytics.discipline', [
            'school' => $school,
            'kpis' => $this->service->schoolOverview($school),
            'byCategory' => $this->service->incidentsByCategory($school),
            'severity' => $this->service->openIncidentSeverity($school),
            'correlation' => $this->service->behaviourAcademicsCorrelation($school),
        ]);
    }

    public function student(Request $request, User $user): View
    {
        if (! $request->user()->can('analytics.view-student', $user)) {
            abort(403);
        }

        return view('analytics.student', [
            'student' => $user->loadMissing(['role', 'school']),
            'data' => $this->service->studentDeepDive($user),
        ]);
    }

    private function resolveSchool(Request $request): School
    {
        $actor = $request->user();
        $school = null;

        if ($actor->hasRole(Role::SYSTEM_ADMIN) && $request->filled('school_id')) {
            $school = School::findOrFail($request->integer('school_id'));
        } else {
            $school = $actor->school;
        }

        if (! $school) {
            abort(404, 'No school context available.');
        }

        if (! $actor->can('analytics.view-school', $school)) {
            abort(403);
        }

        return $school;
    }
}
