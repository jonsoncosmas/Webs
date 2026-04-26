@extends('layouts.app')

@section('title', 'Exam analytics — Somalite')

@section('content')
    @include('analytics._nav', ['school' => $school])

    <div class="grid cols-3" style="margin-top:16px;">
        <div class="stat">
            <div class="label">Published exams</div>
            <div class="value">{{ $kpis['published_exams'] }}</div>
        </div>
        <div class="stat">
            <div class="label">Scored attempts</div>
            <div class="value">{{ $kpis['scored_attempts'] }}</div>
        </div>
        <div class="stat">
            <div class="label">Open reviews</div>
            <div class="value">{{ $kpis['open_reviews'] }}</div>
        </div>
    </div>

    <div class="grid cols-2" style="margin-top:16px; gap:16px; align-items:start;">
        <div class="card">
            <h3 style="margin-top:0;">Score distribution</h3>
            <p class="muted" style="margin-top:4px;">All scored attempts, bucketed by percentage.</p>
            @include('analytics._bars', ['series' => $distribution])
        </div>
        <div class="card">
            <h3 style="margin-top:0;">Published exams by subject</h3>
            @include('analytics._bars', ['series' => $bySubject])
        </div>
    </div>
@endsection
