<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    // Canonical slugs used across the system.
    public const SYSTEM_ADMIN = 'system_admin';

    public const DIRECTOR = 'director';

    public const DEPUTY_DIRECTOR = 'deputy_director';

    public const SCHOOL_ADMIN = 'school_admin';

    public const ACADEMIC_HEAD = 'academic_head';

    public const DEPUTY_ACADEMIC_HEAD = 'deputy_academic_head';

    public const EXAMINATION_MASTER = 'examination_master';

    public const DEPARTMENT_HEAD = 'department_head';

    public const HR = 'hr';

    public const IT = 'it';

    public const DISCIPLINE_HEAD = 'discipline_head';

    public const DEPUTY_DISCIPLINE_HEAD = 'deputy_discipline_head';

    public const TEACHER = 'teacher';

    public const STUDENT = 'student';

    public const PARENT_ROLE = 'parent';

    protected $fillable = ['slug', 'name', 'level', 'scope', 'description'];

    protected $casts = [
        'level' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Canonical hierarchy. Lower level = higher authority.
     *
     * @return array<int, array{slug:string,name:string,level:int,scope:string,description:string}>
     */
    public static function catalog(): array
    {
        return [
            ['slug' => self::SYSTEM_ADMIN, 'name' => 'System Admin', 'level' => 0, 'scope' => 'global', 'description' => 'Global control over all schools and platform config.'],
            ['slug' => self::DIRECTOR, 'name' => 'Director', 'level' => 10, 'scope' => 'school', 'description' => 'Top school authority.'],
            ['slug' => self::DEPUTY_DIRECTOR, 'name' => 'Deputy Director', 'level' => 20, 'scope' => 'school', 'description' => 'Director powers except payments/staff changes.'],
            ['slug' => self::SCHOOL_ADMIN, 'name' => 'School Admin', 'level' => 30, 'scope' => 'school', 'description' => 'Operations core.'],
            ['slug' => self::ACADEMIC_HEAD, 'name' => 'Academic Head', 'level' => 40, 'scope' => 'school', 'description' => 'Academic leadership.'],
            ['slug' => self::DEPUTY_ACADEMIC_HEAD, 'name' => 'Deputy Academic Head', 'level' => 50, 'scope' => 'school', 'description' => 'Academic leadership (limited scope).'],
            ['slug' => self::EXAMINATION_MASTER, 'name' => 'Examination Master', 'level' => 55, 'scope' => 'school', 'description' => 'Exam scheduling and generation.'],
            ['slug' => self::HR, 'name' => 'HR', 'level' => 60, 'scope' => 'school', 'description' => 'Staff records, certificates, NHIF.'],
            ['slug' => self::IT, 'name' => 'IT', 'level' => 60, 'scope' => 'school', 'description' => 'System + academic support.'],
            ['slug' => self::DEPARTMENT_HEAD, 'name' => 'Department Head', 'level' => 60, 'scope' => 'school', 'description' => 'Manages department teachers.'],
            ['slug' => self::DISCIPLINE_HEAD, 'name' => 'Discipline Head', 'level' => 65, 'scope' => 'school', 'description' => 'Student behavior + teacher conduct.'],
            ['slug' => self::DEPUTY_DISCIPLINE_HEAD, 'name' => 'Deputy Discipline Head', 'level' => 70, 'scope' => 'school', 'description' => 'Discipline deputy.'],
            ['slug' => self::TEACHER, 'name' => 'Teacher', 'level' => 80, 'scope' => 'school', 'description' => 'Subject teaching and materials.'],
            ['slug' => self::STUDENT, 'name' => 'Student', 'level' => 90, 'scope' => 'school', 'description' => 'Learner account.'],
            ['slug' => self::PARENT_ROLE, 'name' => 'Parent', 'level' => 90, 'scope' => 'school', 'description' => 'Parent / guardian account.'],
        ];
    }

    /**
     * Roles allowed to generate exams (in order of override precedence, highest first).
     *
     * @return array<int, string>
     */
    public static function examGenerators(): array
    {
        return [
            self::DIRECTOR,
            self::SCHOOL_ADMIN,
            self::ACADEMIC_HEAD,
            self::DEPUTY_ACADEMIC_HEAD,
            self::EXAMINATION_MASTER,
            self::IT,
        ];
    }
}
