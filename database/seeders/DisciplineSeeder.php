<?php

namespace Database\Seeders;

use App\Models\DisciplineIncident;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds a handful of incidents on the demo student so the timeline view
 * has something to render out of the box.
 */
class DisciplineSeeder extends Seeder
{
    public function run(): void
    {
        $reporter = User::where('username', 'samuel kweka')->first();
        $teacher = User::where('username', 'rehema saidi')->first();
        $student = User::where('username', 'joyce neema')->first();

        if (! $reporter || ! $student) {
            return;
        }

        $incidents = [
            [
                'category' => 'minor',
                'title' => 'Late to assembly',
                'description' => 'Arrived 15 minutes after assembly started.',
                'occurred_on' => now()->subDays(42)->toDateString(),
                'severity' => 1,
            ],
            [
                'category' => 'major',
                'title' => 'Disrespect to duty teacher',
                'description' => 'Shouted at duty teacher during break and refused correction.',
                'occurred_on' => now()->subDays(30)->toDateString(),
                'severity' => 3,
            ],
            [
                'category' => 'warning',
                'title' => 'Uniform violation — second offence',
                'description' => 'Wrong shoes for the second time this term.',
                'occurred_on' => now()->subDays(15)->toDateString(),
                'severity' => 2,
            ],
            [
                'category' => 'commendation',
                'title' => 'Helped new student adjust',
                'description' => 'Buddied a Form 1 transfer student through their first week.',
                'occurred_on' => now()->subDays(7)->toDateString(),
                'severity' => 1,
            ],
        ];

        foreach ($incidents as $data) {
            DisciplineIncident::firstOrCreate(
                [
                    'subject_id' => $student->id,
                    'title' => $data['title'],
                ],
                array_merge($data, [
                    'school_id' => $student->school_id,
                    'subject_role' => $student->role?->slug,
                    'reported_by' => ($teacher && $data['category'] !== 'commendation') ? $teacher->id : $reporter->id,
                    'status' => DisciplineIncident::STATUS_OPEN,
                ]),
            );
        }
    }
}
