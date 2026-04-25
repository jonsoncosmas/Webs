<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffProfile extends Model
{
    public const EMPLOYMENT_TYPES = ['permanent', 'contract', 'part_time', 'intern', 'casual'];

    protected $fillable = [
        'user_id',
        'school_id',
        'employee_no',
        'employment_type',
        'hired_on',
        'contract_ends_on',
        'nhif_number',
        'nssf_number',
        'tin_number',
        'national_id',
        'bank_name',
        'bank_account',
        'bank_branch',
        'next_of_kin_name',
        'next_of_kin_relation',
        'next_of_kin_phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'phone',
        'email',
        'address',
        'date_of_birth',
        'gender',
        'marital_status',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'hired_on' => 'date',
        'contract_ends_on' => 'date',
        'date_of_birth' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
