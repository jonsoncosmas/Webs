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
        </div>
        @include('partials.orion-widget')
    </div>
@endsection
