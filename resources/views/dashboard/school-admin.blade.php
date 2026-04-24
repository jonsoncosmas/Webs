@extends('layouts.app')

@section('title', 'School Admin — Somalite')

@section('content')
    <div class="card">
        <h1>Operations Center</h1>
        <p class="muted">Teachers, students, staff, subjects and departments.</p>
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <div class="card">
            <h3>Teacher coverage</h3>
            <p class="muted">Coverage analytics will appear here once lesson data is flowing.</p>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">
                <a class="btn ghost" href="{{ route('exams.index') }}">Exams</a>
                <a class="btn" href="{{ route('exams.create') }}">New exam</a>
            </div>
        </div>
        @include('partials.orion-widget')
    </div>

    <div class="card" style="margin-top:16px;">
        <h3>Marklists &amp; report cards</h3>
        <p class="muted">Assign templates to classes, track readiness, and print A4 sheets.</p>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn ghost" href="{{ route('templates.index') }}">Templates</a>
            <a class="btn ghost" href="{{ route('assignments.index') }}">Assignments</a>
            <a class="btn" href="{{ route('assignments.create') }}">New assignment</a>
        </div>
    </div>
@endsection
