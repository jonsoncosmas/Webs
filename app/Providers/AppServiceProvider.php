<?php

namespace App\Providers;

use App\Models\Exam;
use App\Models\StaffCertificate;
use App\Models\StaffLeave;
use App\Models\StaffProfile;
use App\Models\Template;
use App\Models\TemplateAssignment;
use App\Policies\ExamPolicy;
use App\Policies\StaffCertificatePolicy;
use App\Policies\StaffLeavePolicy;
use App\Policies\StaffProfilePolicy;
use App\Policies\TemplateAssignmentPolicy;
use App\Policies\TemplatePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Exam::class, ExamPolicy::class);
        Gate::policy(Template::class, TemplatePolicy::class);
        Gate::policy(TemplateAssignment::class, TemplateAssignmentPolicy::class);
        Gate::policy(StaffProfile::class, StaffProfilePolicy::class);
        Gate::policy(StaffCertificate::class, StaffCertificatePolicy::class);
        Gate::policy(StaffLeave::class, StaffLeavePolicy::class);
    }
}
