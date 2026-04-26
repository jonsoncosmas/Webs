<?php

namespace App\Services\Analytics;

use App\Models\DisciplineIncident;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ResultReviewRequest;
use App\Models\Role;
use App\Models\School;
use App\Models\StaffLeave;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * High-level KPI cards for the school overview page.
     *
     * @return array{
     *     active_staff:int, suspended_staff:int, students:int,
     *     published_exams:int, scored_attempts:int,
     *     open_incidents:int, open_reviews:int, pending_leave:int,
     * }
     */
    public function schoolOverview(School $school): array
    {
        $staffRoles = [
            Role::DIRECTOR, Role::DEPUTY_DIRECTOR, Role::SCHOOL_ADMIN,
            Role::ACADEMIC_HEAD, Role::DEPUTY_ACADEMIC_HEAD, Role::EXAMINATION_MASTER,
            Role::DEPARTMENT_HEAD, Role::HR, Role::IT,
            Role::DISCIPLINE_HEAD, Role::DEPUTY_DISCIPLINE_HEAD, Role::TEACHER,
        ];

        $staffQuery = User::query()
            ->where('school_id', $school->id)
            ->whereHas('role', fn ($q) => $q->whereIn('slug', $staffRoles));

        return [
            'active_staff' => (clone $staffQuery)->where('status', User::STATUS_ACTIVE)->count(),
            'suspended_staff' => (clone $staffQuery)->where('status', User::STATUS_SUSPENDED)->count(),
            'students' => User::query()
                ->where('school_id', $school->id)
                ->whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
                ->count(),
            'published_exams' => Exam::query()
                ->where('school_id', $school->id)
                ->where('status', Exam::STATUS_PUBLISHED)
                ->count(),
            'scored_attempts' => ExamAttempt::query()
                ->where('school_id', $school->id)
                ->whereNotNull('score')
                ->count(),
            'open_incidents' => DisciplineIncident::query()
                ->where('school_id', $school->id)
                ->where('status', DisciplineIncident::STATUS_OPEN)
                ->count(),
            'open_reviews' => ResultReviewRequest::query()
                ->where('school_id', $school->id)
                ->whereIn('status', [
                    ResultReviewRequest::STATUS_PENDING,
                    ResultReviewRequest::STATUS_ACKNOWLEDGED,
                ])
                ->count(),
            'pending_leave' => StaffLeave::query()
                ->whereHas('user', fn ($q) => $q->where('school_id', $school->id))
                ->where('status', StaffLeave::STATUS_PENDING)
                ->count(),
        ];
    }

    /**
     * Daily counts of newly-scored attempts for the trailing N days.
     * Returns ISO-date keyed counts so the view can render a tidy bar chart.
     *
     * @return Collection<string,int>
     */
    public function scoringTrend(School $school, int $days = 14): Collection
    {
        $start = Carbon::today()->subDays($days - 1);
        $rows = ExamAttempt::query()
            ->where('school_id', $school->id)
            ->whereNotNull('scored_at')
            ->where('scored_at', '>=', $start)
            ->get(['scored_at']);

        $buckets = collect();
        for ($i = 0; $i < $days; $i++) {
            $buckets->put($start->copy()->addDays($i)->toDateString(), 0);
        }

        foreach ($rows as $row) {
            $key = $row->scored_at->toDateString();
            $buckets->put($key, ($buckets->get($key, 0)) + 1);
        }

        return $buckets;
    }

    /**
     * Score distribution for a school, bucketed into common bands.
     *
     * @return array<string,int>
     */
    public function scoreDistribution(School $school): array
    {
        $rows = ExamAttempt::query()
            ->where('school_id', $school->id)
            ->whereNotNull('score')
            ->whereNotNull('total_marks')
            ->where('total_marks', '>', 0)
            ->get(['score', 'total_marks']);

        $buckets = ['0-39' => 0, '40-59' => 0, '60-79' => 0, '80-100' => 0];
        foreach ($rows as $row) {
            $pct = ($row->score / $row->total_marks) * 100;
            $key = match (true) {
                $pct < 40 => '0-39',
                $pct < 60 => '40-59',
                $pct < 80 => '60-79',
                default => '80-100',
            };
            $buckets[$key]++;
        }

        return $buckets;
    }

    /**
     * Published-exam counts grouped by subject.
     *
     * @return Collection<string,int>
     */
    public function publishedExamsBySubject(School $school): Collection
    {
        return Exam::query()
            ->where('school_id', $school->id)
            ->where('status', Exam::STATUS_PUBLISHED)
            ->selectRaw('COALESCE(subject, "Unspecified") as subject, COUNT(*) as c')
            ->groupBy('subject')
            ->orderByDesc('c')
            ->get()
            ->mapWithKeys(fn ($r) => [(string) $r->subject => (int) $r->c]);
    }

    /**
     * Open incidents grouped by category.
     *
     * @return Collection<string,int>
     */
    public function incidentsByCategory(School $school): Collection
    {
        return DisciplineIncident::query()
            ->where('school_id', $school->id)
            ->selectRaw('category, COUNT(*) as c')
            ->groupBy('category')
            ->orderByDesc('c')
            ->get()
            ->mapWithKeys(fn ($r) => [(string) $r->category => (int) $r->c]);
    }

    /**
     * Severity histogram (1-5) across all open incidents.
     *
     * @return array<int,int>
     */
    public function openIncidentSeverity(School $school): array
    {
        $rows = DisciplineIncident::query()
            ->where('school_id', $school->id)
            ->where('status', DisciplineIncident::STATUS_OPEN)
            ->selectRaw('severity, COUNT(*) as c')
            ->groupBy('severity')
            ->pluck('c', 'severity');

        $out = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($rows as $sev => $c) {
            $sev = (int) $sev;
            if (isset($out[$sev])) {
                $out[$sev] = (int) $c;
            }
        }

        return $out;
    }

    /**
     * Behaviour ↔ academics correlation: average % score for students with
     * at least one open negative incident, vs students with none.
     *
     * @return array{with_incidents:array{count:int, avg_pct:?float}, clean:array{count:int, avg_pct:?float}}
     */
    public function behaviourAcademicsCorrelation(School $school): array
    {
        $negativeCategories = [
            DisciplineIncident::CATEGORY_MINOR,
            DisciplineIncident::CATEGORY_MAJOR,
            DisciplineIncident::CATEGORY_WARNING,
            DisciplineIncident::CATEGORY_SUSPENSION,
        ];

        $studentsWithIncidents = DisciplineIncident::query()
            ->where('school_id', $school->id)
            ->whereIn('category', $negativeCategories)
            ->where('subject_role', Role::STUDENT)
            ->pluck('subject_id')
            ->unique()
            ->values()
            ->all();

        $attempts = ExamAttempt::query()
            ->where('school_id', $school->id)
            ->whereNotNull('score')
            ->whereNotNull('total_marks')
            ->where('total_marks', '>', 0)
            ->get(['student_user_id', 'score', 'total_marks']);

        $withSum = 0.0;
        $withCount = 0;
        $cleanSum = 0.0;
        $cleanCount = 0;
        $cleanStudents = collect();
        $withStudents = collect();

        foreach ($attempts as $a) {
            $pct = ($a->score / $a->total_marks) * 100;
            if (in_array($a->student_user_id, $studentsWithIncidents, true)) {
                $withSum += $pct;
                $withCount++;
                $withStudents->push($a->student_user_id);
            } else {
                $cleanSum += $pct;
                $cleanCount++;
                $cleanStudents->push($a->student_user_id);
            }
        }

        return [
            'with_incidents' => [
                'count' => $withStudents->unique()->count(),
                'avg_pct' => $withCount > 0 ? round($withSum / $withCount, 1) : null,
            ],
            'clean' => [
                'count' => $cleanStudents->unique()->count(),
                'avg_pct' => $cleanCount > 0 ? round($cleanSum / $cleanCount, 1) : null,
            ],
        ];
    }

    /**
     * Per-student academic + discipline overview for the deep-dive page.
     *
     * @return array{
     *     attempts_count:int, avg_pct:?float, last_score:?float,
     *     incidents_total:int, incidents_open:int,
     *     attempts:Collection<int,ExamAttempt>,
     *     incidents:Collection<int,DisciplineIncident>,
     * }
     */
    public function studentDeepDive(User $student): array
    {
        $attempts = ExamAttempt::query()
            ->where('student_user_id', $student->id)
            ->with('exam')
            ->orderByDesc('scored_at')
            ->get();

        $pcts = $attempts->filter(fn ($a) => $a->total_marks > 0 && $a->score !== null)
            ->map(fn ($a) => ($a->score / $a->total_marks) * 100);

        $incidents = DisciplineIncident::query()
            ->where('subject_id', $student->id)
            ->where('subject_role', Role::STUDENT)
            ->orderByDesc('occurred_on')
            ->get();

        return [
            'attempts_count' => $attempts->count(),
            'avg_pct' => $pcts->isNotEmpty() ? round($pcts->avg(), 1) : null,
            'last_score' => $pcts->first(),
            'incidents_total' => $incidents->count(),
            'incidents_open' => $incidents->where('status', DisciplineIncident::STATUS_OPEN)->count(),
            'attempts' => $attempts,
            'incidents' => $incidents,
        ];
    }
}
