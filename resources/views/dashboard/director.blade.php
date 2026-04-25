@extends('layouts.app')

@section('title', 'Director — Somalite')

@section('content')
    <div class="card">
        <h1>{{ $user->school?->name ?? 'Director' }}</h1>
        <p class="muted">Top school authority · full override power.</p>
    </div>

    <div class="grid cols-3" style="margin-top:16px;">
        <div class="stat">
            <div class="label">Staff</div>
            <div class="value">—</div>
            <div class="hint">Pending module</div>
        </div>
        <div class="stat">
            <div class="label">Students</div>
            <div class="value">—</div>
            <div class="hint">Pending module</div>
        </div>
        <div class="stat">
            <div class="label">Exams</div>
            <div class="value">—</div>
            <div class="hint">Pending module</div>
        </div>
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <div class="card">
            <h3>Override authority</h3>
            <p class="muted">You can override any exam created by lower-ranked staff. Exams you create are locked to you.</p>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">
                <a class="btn ghost" href="{{ route('exams.index') }}">Exams</a>
                <a class="btn" href="{{ route('exams.create') }}">New exam</a>
            </div>
        </div>
        @include('partials.orion-widget')
    </div>

    <div class="card" style="margin-top:16px;">
        <h3>Templates &amp; report cards</h3>
        <p class="muted">Pick a result marklist or academic report template, assign it to a class and a staff member.</p>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn ghost" href="{{ route('templates.index') }}">Templates</a>
            <a class="btn ghost" href="{{ route('assignments.index') }}">Assignments</a>
            <a class="btn" href="{{ route('assignments.create') }}">New assignment</a>
        </div>
    </div>

    <div class="grid cols-2" style="margin-top:16px; gap:16px;">
        <div class="card">
            <h3>HR &amp; staff records</h3>
            <p class="muted">Browse the staff directory, view profiles, certificates, and leave history.</p>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a class="btn ghost" href="{{ route('hr.index') }}">Staff directory</a>
                <a class="btn ghost" href="{{ route('hr.me') }}">My HR profile</a>
            </div>
        </div>
        <div class="card">
            <h3>Discipline &amp; behaviour</h3>
            <p class="muted">Log incidents, resolve reports, and overlay behaviour against academic activity.</p>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a class="btn ghost" href="{{ route('discipline.index') }}">Incidents</a>
                <a class="btn" href="{{ route('discipline.create') }}">Log incident</a>
            </div>
        </div>
    </div>
@endsection
