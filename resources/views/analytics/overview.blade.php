@extends('layouts.app')

@section('title', 'Analytics — Somalite')

@section('content')
    @include('analytics._nav', ['school' => $school])

    <div class="grid cols-3" style="margin-top:16px;">
        <div class="stat">
            <div class="label">Active staff</div>
            <div class="value">{{ $kpis['active_staff'] }}</div>
            <div class="hint">{{ $kpis['suspended_staff'] }} suspended</div>
        </div>
        <div class="stat">
            <div class="label">Students</div>
            <div class="value">{{ $kpis['students'] }}</div>
            <div class="hint">enrolled at {{ $school->name }}</div>
        </div>
        <div class="stat">
            <div class="label">Published exams</div>
            <div class="value">{{ $kpis['published_exams'] }}</div>
            <div class="hint">{{ $kpis['scored_attempts'] }} scored attempts</div>
        </div>
    </div>

    <div class="grid cols-3" style="margin-top:16px;">
        <div class="stat">
            <div class="label">Open incidents</div>
            <div class="value">{{ $kpis['open_incidents'] }}</div>
            <div class="hint">awaiting decision</div>
        </div>
        <div class="stat">
            <div class="label">Open reviews</div>
            <div class="value">{{ $kpis['open_reviews'] }}</div>
            <div class="hint">student / parent</div>
        </div>
        <div class="stat">
            <div class="label">Pending leave</div>
            <div class="value">{{ $kpis['pending_leave'] }}</div>
            <div class="hint">awaiting HR / supervisor</div>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Scoring activity (last 14 days)</h3>
        @php
            $trendArr = [];
            foreach ($trend as $date => $count) {
                $trendArr[\Illuminate\Support\Carbon::parse($date)->format('M j')] = $count;
            }
        @endphp
        @include('analytics._bars', ['series' => $trendArr])
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Behaviour ↔ Academics</h3>
        <p class="muted" style="margin-top:4px;">
            Students with one or more open negative incidents (minor / major / warning / suspension)
            compared to students with none.
        </p>
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
        @if ($correlation['with_incidents']['avg_pct'] !== null && $correlation['clean']['avg_pct'] !== null)
            @php
                $delta = round($correlation['clean']['avg_pct'] - $correlation['with_incidents']['avg_pct'], 1);
            @endphp
            <p class="muted" style="margin-top:10px;">
                Δ = <strong>{{ $delta > 0 ? '+'.$delta : $delta }} pts</strong>
                @if ($delta > 0) — students without incidents currently outperform.
                @elseif ($delta < 0) — flagged students currently outperform; review the sample.
                @else — no measurable gap yet. @endif
            </p>
        @endif
    </div>
@endsection
