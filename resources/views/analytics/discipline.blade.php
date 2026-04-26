@extends('layouts.app')

@section('title', 'Discipline analytics — Somalite')

@section('content')
    @include('analytics._nav', ['school' => $school])

    <div class="grid cols-3" style="margin-top:16px;">
        <div class="stat">
            <div class="label">Open incidents</div>
            <div class="value">{{ $kpis['open_incidents'] }}</div>
        </div>
        <div class="stat">
            <div class="label">Suspended staff</div>
            <div class="value">{{ $kpis['suspended_staff'] }}</div>
        </div>
        <div class="stat">
            <div class="label">Students</div>
            <div class="value">{{ $kpis['students'] }}</div>
            <div class="hint">enrolled at {{ $school->name }}</div>
        </div>
    </div>

    <div class="grid cols-2" style="margin-top:16px; gap:16px; align-items:start;">
        <div class="card">
            <h3 style="margin-top:0;">Incidents by category</h3>
            @php
                $catLabels = [
                    'minor' => 'Minor',
                    'major' => 'Major',
                    'warning' => 'Warning',
                    'suspension' => 'Suspension',
                    'commendation' => 'Commendation',
                    'teacher_conduct' => 'Teacher conduct',
                ];
                $byCatLabelled = [];
                foreach ($byCategory as $k => $v) {
                    $byCatLabelled[$catLabels[$k] ?? $k] = $v;
                }
            @endphp
            @include('analytics._bars', ['series' => $byCatLabelled])
        </div>
        <div class="card">
            <h3 style="margin-top:0;">Open incidents by severity</h3>
            @php
                $sevLabels = [];
                foreach ($severity as $k => $v) {
                    $sevLabels['Severity '.$k] = $v;
                }
            @endphp
            @include('analytics._bars', ['series' => $sevLabels])
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Behaviour ↔ Academics</h3>
        <div class="grid cols-2" style="margin-top:8px;">
            <div class="stat">
                <div class="label">With incidents</div>
                <div class="value">
                    {{ $correlation['with_incidents']['avg_pct'] !== null
                        ? $correlation['with_incidents']['avg_pct'].'%'
                        : '—' }}
                </div>
                <div class="hint">{{ $correlation['with_incidents']['count'] }} students sampled</div>
            </div>
            <div class="stat">
                <div class="label">No incidents</div>
                <div class="value">
                    {{ $correlation['clean']['avg_pct'] !== null
                        ? $correlation['clean']['avg_pct'].'%'
                        : '—' }}
                </div>
                <div class="hint">{{ $correlation['clean']['count'] }} students sampled</div>
            </div>
        </div>
    </div>
@endsection
