@extends('layouts.app')

@section('title', 'Dashboard — Somalite')

@section('content')
    <div class="card">
        <h1>Welcome, {{ $user->first_name }}</h1>
        <p class="muted">Role: <strong>{{ $user->role?->name ?? 'Unassigned' }}</strong></p>
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <div class="card">
            <h3>Quick actions</h3>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                @can('viewAny', App\Models\Exam::class)
                    <a class="btn ghost" href="{{ route('exams.index') }}">Exams</a>
                @endcan
                @can('create', App\Models\Exam::class)
                    <a class="btn" href="{{ route('exams.create') }}">New exam</a>
                @endcan
                @can('viewAny', App\Models\TemplateAssignment::class)
                    <a class="btn ghost" href="{{ route('assignments.index') }}">Assignments</a>
                @endcan
                @can('create', App\Models\TemplateAssignment::class)
                    <a class="btn" href="{{ route('assignments.create') }}">New assignment</a>
                @endcan
                @can('viewAny', App\Models\StaffProfile::class)
                    <a class="btn ghost" href="{{ route('hr.index') }}">Staff directory</a>
                @endcan
                <a class="btn ghost" href="{{ route('hr.me') }}">My HR profile</a>
                @can('viewAny', App\Models\DisciplineIncident::class)
                    <a class="btn ghost" href="{{ route('discipline.index') }}">Discipline</a>
                @endcan
                @can('create', App\Models\DisciplineIncident::class)
                    <a class="btn ghost" href="{{ route('discipline.create') }}">Log incident</a>
                @endcan
                @can('viewAny', App\Models\ResultReviewRequest::class)
                    <a class="btn ghost" href="{{ route('academic.reviews.index') }}">Review inbox</a>
                @endcan
                @if ($user->school && $user->can('analytics.view-school', $user->school))
                    <a class="btn ghost" href="{{ route('analytics.overview') }}">Analytics</a>
                @endif
            </div>
            <p class="muted" style="margin-top:10px;">More role-specific tools will appear here as modules roll out.</p>
        </div>
        @include('partials.orion-widget')
    </div>
@endsection
