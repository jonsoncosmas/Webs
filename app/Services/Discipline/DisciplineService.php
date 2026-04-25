<?php

namespace App\Services\Discipline;

use App\Models\DisciplineIncident;
use App\Models\Exam;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Collection;

class DisciplineService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function logIncident(User $actor, User $subject, array $attributes): DisciplineIncident
    {
        $incident = DisciplineIncident::create([
            'school_id' => $subject->school_id,
            'subject_id' => $subject->id,
            'subject_role' => $subject->role?->slug,
            'reported_by' => $actor->id,
            'category' => $attributes['category'],
            'title' => $attributes['title'],
            'description' => $attributes['description'] ?? null,
            'occurred_on' => $attributes['occurred_on'],
            'severity' => $attributes['severity'] ?? 2,
            'status' => DisciplineIncident::STATUS_OPEN,
        ]);

        $this->logger->log('discipline.incident.logged', $incident, [
            'subject_id' => $subject->id,
            'category' => $incident->category,
            'severity' => $incident->severity,
        ]);

        return $incident;
    }

    public function resolve(User $actor, DisciplineIncident $incident, string $resolution): DisciplineIncident
    {
        $incident->update([
            'status' => DisciplineIncident::STATUS_RESOLVED,
            'resolution' => $resolution,
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ]);

        $this->logger->log('discipline.incident.resolved', $incident, [
            'subject_id' => $incident->subject_id,
        ]);

        return $incident;
    }

    public function dismiss(User $actor, DisciplineIncident $incident, ?string $reason = null): DisciplineIncident
    {
        $incident->update([
            'status' => DisciplineIncident::STATUS_DISMISSED,
            'resolution' => $reason,
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ]);

        $this->logger->log('discipline.incident.dismissed', $incident, [
            'subject_id' => $incident->subject_id,
        ]);

        return $incident;
    }

    /**
     * Build a unified timeline of incidents for a subject, alongside recent exam
     * activity in their school so a reviewer can eyeball behaviour ↔ academics
     * correlation without needing per-student scoring data.
     *
     * @return array{
     *     incidents: Collection<int, DisciplineIncident>,
     *     exams: Collection<int, Exam>,
     *     summary: array{
     *         total: int,
     *         by_category: array<string, int>,
     *         open: int,
     *         severity_sum: int,
     *         severity_avg: float,
     *     }
     * }
     */
    public function timeline(User $subject): array
    {
        $incidents = DisciplineIncident::query()
            ->where('subject_id', $subject->id)
            ->with(['reporter', 'decider'])
            ->orderByDesc('occurred_on')
            ->get();

        $windowStart = $incidents->min('occurred_on') ?? now()->subYear();
        $windowEnd = $incidents->max('occurred_on') ?? now();

        $exams = Exam::query()
            ->where('school_id', $subject->school_id)
            ->whereIn('status', [Exam::STATUS_APPROVED, Exam::STATUS_PUBLISHED])
            ->whereBetween('created_at', [$windowStart, (clone $windowEnd)->addDays(1)])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $byCategory = $incidents->groupBy('category')->map->count()->toArray();
        $total = $incidents->count();
        $severitySum = (int) $incidents->sum('severity');

        return [
            'incidents' => $incidents,
            'exams' => $exams,
            'summary' => [
                'total' => $total,
                'by_category' => $byCategory,
                'open' => $incidents->where('status', DisciplineIncident::STATUS_OPEN)->count(),
                'severity_sum' => $severitySum,
                'severity_avg' => $total > 0 ? round($severitySum / $total, 2) : 0.0,
            ],
        ];
    }
}
