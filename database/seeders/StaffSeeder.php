<?php

namespace Database\Seeders;

use App\Models\StaffCertificate;
use App\Models\StaffLeave;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds a sample HR record (profile + certificate + leave) on the demo Teacher
 * so the HR module is visible immediately.
 */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $hr = User::where('username', 'mary joseph')->first();
        $teacher = User::where('username', 'rehema saidi')->first();

        if (! $hr || ! $teacher) {
            return;
        }

        StaffProfile::updateOrCreate(
            ['user_id' => $teacher->id],
            [
                'school_id' => $teacher->school_id,
                'employee_no' => 'EMP-1004',
                'employment_type' => 'permanent',
                'hired_on' => now()->subYears(3)->toDateString(),
                'nhif_number' => 'NHIF-12345678',
                'nssf_number' => 'NSSF-87654321',
                'tin_number' => 'TIN-9900112233',
                'bank_name' => 'CRDB',
                'bank_account' => '0150-12345678',
                'bank_branch' => 'Arusha',
                'next_of_kin_name' => 'Saidi Nyambura',
                'next_of_kin_relation' => 'Father',
                'next_of_kin_phone' => '+255 700 000 111',
                'phone' => '+255 700 222 333',
                'email' => 'rehema.nyambura@demo.somalite.test',
                'gender' => 'Female',
                'marital_status' => 'Married',
                'updated_by' => $hr->id,
            ],
        );

        StaffCertificate::firstOrCreate(
            [
                'user_id' => $teacher->id,
                'title' => 'Diploma in Secondary Education',
            ],
            [
                'school_id' => $teacher->school_id,
                'issuer' => 'University of Dar es Salaam',
                'reference_no' => 'UDSM-ED-2019-001',
                'issued_on' => now()->subYears(5)->toDateString(),
                'expires_on' => null,
                'is_active' => true,
                'added_by' => $hr->id,
            ],
        );

        StaffLeave::firstOrCreate(
            [
                'user_id' => $teacher->id,
                'starts_on' => now()->addWeeks(2)->toDateString(),
            ],
            [
                'school_id' => $teacher->school_id,
                'type' => 'annual',
                'ends_on' => now()->addWeeks(2)->addDays(4)->toDateString(),
                'reason' => 'Family visit upcountry.',
                'status' => StaffLeave::STATUS_PENDING,
                'requested_by' => $teacher->id,
            ],
        );
    }
}
