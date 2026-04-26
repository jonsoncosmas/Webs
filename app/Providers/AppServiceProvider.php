<?php

namespace App\Providers;

use App\Models\BusRoute;
use App\Models\BusVehicle;
use App\Models\DisciplineIncident;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Package;
use App\Models\ResultReviewRequest;
use App\Models\StaffCertificate;
use App\Models\StaffLeave;
use App\Models\StaffProfile;
use App\Models\Template;
use App\Models\TemplateAssignment;
use App\Policies\AnalyticsPolicy;
use App\Policies\BusPolicy;
use App\Policies\DisciplineIncidentPolicy;
use App\Policies\ExamAttemptPolicy;
use App\Policies\ExamPolicy;
use App\Policies\PackagePolicy;
use App\Policies\ResultReviewRequestPolicy;
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
        Gate::policy(DisciplineIncident::class, DisciplineIncidentPolicy::class);
        Gate::policy(ExamAttempt::class, ExamAttemptPolicy::class);
        Gate::policy(ResultReviewRequest::class, ResultReviewRequestPolicy::class);

        Gate::define('analytics.view-school', [AnalyticsPolicy::class, 'viewSchool']);
        Gate::define('analytics.view-student', [AnalyticsPolicy::class, 'viewStudent']);

        Gate::policy(Package::class, PackagePolicy::class);
        Gate::policy(BusRoute::class, BusPolicy::class);
        Gate::policy(BusVehicle::class, BusPolicy::class);
    }
}
