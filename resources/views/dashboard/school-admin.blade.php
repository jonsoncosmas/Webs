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
@endsection
