@extends('layouts.app')

@section('title', $student->fullName().' — Analytics')

@section('content')
    <div class="card">
        <h1 style="margin:0;">{{ $student->fullName() }}</h1>
        <p class="muted" style="margin:4px 0 0 0;">
            {{ $student->role?->name ?? '—' }}
            @if ($student->school) · {{ $student->school->name }} @endif
        </p>
    </div>

    <div class="grid cols-3" style="margin-top:16px;">
        <div class="stat">
            <div class="label">Scored attempts</div>
            <div class="value">{{ $data['attempts_count'] }}</div>
            <div class="hint">{{ $data['avg_pct'] !== null ? 'avg '.$data['avg_pct'].'%' : '—' }}</div>
        </div>
        <div class="stat">
            <div class="label">Last result</div>
            <div class="value">{{ $data['last_score'] !== null ? round($data['last_score'], 1).'%' : '—' }}</div>
        </div>
        <div class="stat">
            <div class="label">Incidents</div>
            <div class="value">{{ $data['incidents_total'] }}</div>
            <div class="hint">{{ $data['incidents_open'] }} open</div>
        </div>
    </div>

    <div class="grid cols-2" style="margin-top:16px; gap:16px; align-items:start;">
        <div class="card">
            <h3 style="margin-top:0;">Recent results</h3>
            @if ($data['attempts']->isEmpty())
                <p class="muted" style="margin:0;">No scored results yet.</p>
            @else
                <div class="grid" style="gap:8px;">
                    @foreach ($data['attempts']->take(8) as $a)
                        <div style="display:grid; grid-template-columns:1fr auto; gap:8px; padding:10px 12px;
                                    border:1px solid rgba(15,23,42,0.08); border-radius:10px;
                                    background:rgba(255,255,255,0.75);">
                            <div>
                                <div style="font-weight:600;">{{ $a->exam->title ?? 'Exam #'.$a->exam_id }}</div>
                                <div class="muted" style="font-size:12px;">
                                    {{ $a->exam?->subject }}
                                    @if ($a->scored_at) · {{ $a->scored_at->format('Y-m-d') }} @endif
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:700;">{{ $a->score }}/{{ $a->total_marks }}</div>
                                @if ($a->grade) <div class="muted" style="font-size:12px;">{{ $a->grade }}</div> @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="card">
            <h3 style="margin-top:0;">Discipline timeline</h3>
            @if ($data['incidents']->isEmpty())
                <p class="muted" style="margin:0;">No incidents on file.</p>
            @else
                <div class="grid" style="gap:8px;">
                    @foreach ($data['incidents']->take(8) as $i)
                        <div style="padding:10px 12px; border:1px solid rgba(15,23,42,0.08); border-radius:10px;
                                    background:rgba(255,255,255,0.75);">
                            <div style="display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                                <strong>{{ $i->title }}</strong>
                                <span class="badge">{{ $i->categoryLabel() }} · sev {{ $i->severity }}</span>
                            </div>
                            <div class="muted" style="font-size:12px; margin-top:2px;">
                                {{ $i->occurred_on?->format('Y-m-d') ?? '—' }} · {{ $i->status }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div style="margin-top:16px;">
        <a class="btn ghost" href="{{ route('analytics.overview') }}">Back to analytics</a>
    </div>
@endsection
