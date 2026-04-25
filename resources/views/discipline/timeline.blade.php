@extends('layouts.app')

@section('title', $subject->fullName() . ' — Discipline timeline')

@section('content')
    <div class="card">
        <h1 style="margin:0;">{{ $subject->fullName() }} — Discipline timeline</h1>
        <p class="muted" style="margin:4px 0 0 0;">
            {{ $subject->role?->name ?? '—' }}
            · @username {{ $subject->username }}
            @if ($subject->school) · {{ $subject->school->name }} @endif
        </p>
    </div>

    <div class="grid cols-4" style="margin-top:16px;">
        <div class="stat">
            <div class="label">Incidents</div>
            <div class="value">{{ $summary['total'] }}</div>
            <div class="hint">{{ $summary['open'] }} open</div>
        </div>
        <div class="stat">
            <div class="label">Avg severity</div>
            <div class="value">{{ $summary['severity_avg'] }}</div>
            <div class="hint">out of 5</div>
        </div>
        <div class="stat">
            <div class="label">Commendations</div>
            <div class="value">{{ $summary['by_category']['commendation'] ?? 0 }}</div>
            <div class="hint">positive events</div>
        </div>
        <div class="stat">
            <div class="label">Warnings / majors</div>
            <div class="value">{{ ($summary['by_category']['warning'] ?? 0) + ($summary['by_category']['major'] ?? 0) + ($summary['by_category']['suspension'] ?? 0) }}</div>
            <div class="hint">escalated reports</div>
        </div>
    </div>

    <div class="grid cols-2" style="margin-top:16px; gap:16px; align-items:start;">
        <div class="card">
            <h3 style="margin-top:0;">Discipline events</h3>
            @if ($incidents->isEmpty())
                <p class="muted" style="margin:0;">No incidents on record.</p>
            @else
                <div class="grid" style="gap:8px;">
                    @foreach ($incidents as $incident)
                        <a href="{{ route('discipline.show', $incident) }}"
                           style="display:block; padding:10px 12px;
                                  border-left:4px solid
                                    @switch($incident->category)
                                        @case('commendation') #16a34a @break
                                        @case('minor') #f59e0b @break
                                        @case('major') @case('suspension') #dc2626 @break
                                        @default #6366f1
                                    @endswitch
                                  ;
                                  border:1px solid rgba(15,23,42,0.08);
                                  border-left-width:4px;
                                  border-radius:10px;
                                  background:rgba(255,255,255,0.75);
                                  text-decoration:none; color:inherit;">
                            <div style="font-weight:600;">{{ $incident->title }}</div>
                            <div class="muted" style="font-size:12px; margin-top:2px;">
                                {{ $incident->occurred_on->format('Y-m-d') }}
                                · {{ $incident->categoryLabel() }}
                                · severity {{ $incident->severity }}
                                · {{ $incident->status }}
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Academic activity in window</h3>
            <p class="muted" style="font-size:12px; margin:-4px 0 10px 0;">
                Exams approved / published at the school during this student's incident window.
                Watch for clusters of incidents around exam dates.
            </p>
            @if ($exams->isEmpty())
                <p class="muted" style="margin:0;">No matching exam activity yet.</p>
            @else
                <div class="grid" style="gap:8px;">
                    @foreach ($exams as $exam)
                        <div style="padding:10px 12px; border:1px solid rgba(15,23,42,0.08); border-radius:10px; background:rgba(255,255,255,0.75);">
                            <div style="font-weight:600;">{{ $exam->title }}</div>
                            <div class="muted" style="font-size:12px; margin-top:2px;">
                                {{ $exam->subject }}
                                @if ($exam->form_level) · {{ $exam->form_level }} @endif
                                · {{ $exam->created_at?->format('Y-m-d') }}
                                · {{ $exam->status }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div style="margin-top:16px; display:flex; gap:8px;">
        <a class="btn ghost" href="{{ route('discipline.index') }}">Back to list</a>
        @can('create', App\Models\DisciplineIncident::class)
            <a class="btn" href="{{ route('discipline.create', ['subject_id' => $subject->id]) }}">Log another incident</a>
        @endcan
    </div>
@endsection
