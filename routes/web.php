<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Exams\ExamController;
use App\Http\Controllers\HR\StaffCertificateController;
use App\Http\Controllers\HR\StaffController;
use App\Http\Controllers\HR\StaffLeaveController;
use App\Http\Controllers\Orion\OrionController;
use App\Http\Controllers\Templates\TemplateAssignmentController;
use App\Http\Controllers\Templates\TemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Guest auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Authenticated + active + (force password change handled inside)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/password/change', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ORION AI (all users interact with same brand; routing is server-side).
        Route::post('/orion/ask', [OrionController::class, 'ask'])->name('orion.ask');

        // Exams + hierarchical override workflow.
        Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
        Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
        Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
        Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
        Route::post('/exams/{exam}/submit', [ExamController::class, 'submit'])->name('exams.submit');
        Route::post('/exams/{exam}/approve', [ExamController::class, 'approve'])->name('exams.approve');
        Route::post('/exams/{exam}/reject', [ExamController::class, 'reject'])->name('exams.reject');
        Route::post('/exams/{exam}/override', [ExamController::class, 'override'])->name('exams.override');
        Route::post('/exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');
        Route::post('/exams/{exam}/archive', [ExamController::class, 'archive'])->name('exams.archive');

        // Templates — System Admin authors; schools list & assign.
        Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
        Route::get('/templates/{template}', [TemplateController::class, 'show'])->name('templates.show');
        Route::post('/templates/{template}/archive', [TemplateController::class, 'archive'])->name('templates.archive');
        Route::post('/templates/{template}/activate', [TemplateController::class, 'activate'])->name('templates.activate');

        // Template assignments — school-scoped.
        Route::get('/assignments', [TemplateAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/assignments/create', [TemplateAssignmentController::class, 'create'])->name('assignments.create');
        Route::post('/assignments', [TemplateAssignmentController::class, 'store'])->name('assignments.store');
        Route::get('/assignments/{assignment}', [TemplateAssignmentController::class, 'show'])->name('assignments.show');
        Route::post('/assignments/{assignment}/ready', [TemplateAssignmentController::class, 'markReady'])->name('assignments.ready');
        Route::post('/assignments/{assignment}/reopen', [TemplateAssignmentController::class, 'reopen'])->name('assignments.reopen');
        Route::get('/assignments/{assignment}/print', [TemplateAssignmentController::class, 'print'])->name('assignments.print');

        // HR / staff records.
        Route::get('/hr/staff', [StaffController::class, 'index'])->name('hr.index');
        Route::get('/hr/me', [StaffController::class, 'me'])->name('hr.me');
        Route::get('/hr/staff/{subject}', [StaffController::class, 'show'])->name('hr.show');
        Route::post('/hr/staff/{subject}/profile', [StaffController::class, 'updateProfile'])->name('hr.profile.update');
        Route::post('/hr/staff/{subject}/suspend', [StaffController::class, 'suspend'])->name('hr.suspend');
        Route::post('/hr/staff/{subject}/deactivate', [StaffController::class, 'deactivate'])->name('hr.deactivate');
        Route::post('/hr/staff/{subject}/activate', [StaffController::class, 'activate'])->name('hr.activate');
        Route::post('/hr/staff/{subject}/certificates', [StaffCertificateController::class, 'store'])->name('hr.certificates.store');
        Route::post('/hr/certificates/{certificate}/archive', [StaffCertificateController::class, 'archive'])->name('hr.certificates.archive');
        Route::post('/hr/staff/{subject}/leaves', [StaffLeaveController::class, 'store'])->name('hr.leaves.store');
        Route::post('/hr/leaves/{leave}/approve', [StaffLeaveController::class, 'approve'])->name('hr.leaves.approve');
        Route::post('/hr/leaves/{leave}/reject', [StaffLeaveController::class, 'reject'])->name('hr.leaves.reject');
        Route::post('/hr/leaves/{leave}/cancel', [StaffLeaveController::class, 'cancel'])->name('hr.leaves.cancel');
    });
});
