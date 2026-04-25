@extends('layouts.app')

@section('title', $incident->title . ' — Discipline')

@section('content')
    @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
        </div>
    @endif

    <div class="card">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <h1 style="margin:0;">{{ $incident->title }}</h1>
                <p class="muted" style="margin:6px 0 0 0;">
                    {{ $incident->categoryLabel() }}
                    · Severity {{ $incident->severity }}
                    · {{ $incident->occurred_on->format('Y-m-d') }}
                </p>
            </div>
            <span class="badge" style="background:
                @switch($incident->status)
                    @case('resolved') rgba(22,163,74,0.12) @break
                    @case('dismissed') rgba(15,23,42,0.08) @break
                    @default rgba(217,119,6,0.12)
                @endswitch
                ; color:
                @switch($incident->status)
                    @case('resolved') #15803d @break
                    @case('dismissed') #475569 @break
                    @default #b45309
                @endswitch
                ;">{{ $incident->status }}</span>
        </div>

        <hr style="margin:16px 0; border:none; border-top:1px solid rgba(15,23,42,0.08);">

        <div class="grid cols-2" style="gap:16px;">
            <div>
                <div class="muted" style="font-size:12px;">Subject</div>
                @if ($incident->subject)
                    <a href="{{ route('discipline.timeline', $incident->subject) }}" style="font-weight:600;">
                        {{ $incident->subject->fullName() }}
                    </a>
                @else
                    <span style="font-weight:600;">—</span>
                @endif
                <div class="muted" style="font-size:12px; margin-top:2px;">
                    {{ $incident->subject?->role?->name ?? '—' }}
                </div>
            </div>
            <div>
                <div class="muted" style="font-size:12px;">Reported by</div>
                <div style="font-weight:600;">{{ $incident->reporter?->fullName() ?? '—' }}</div>
                <div class="muted" style="font-size:12px; margin-top:2px;">
                    {{ $incident->created_at?->diffForHumans() }}
                </div>
            </div>
        </div>

        @if ($incident->description)
            <div style="margin-top:12px;">
                <div class="muted" style="font-size:12px;">Description</div>
                <p style="white-space:pre-wrap; margin:4px 0 0 0;">{{ $incident->description }}</p>
            </div>
        @endif

        @if ($incident->decided_at)
            <div style="margin-top:12px; padding:10px 12px; background:rgba(15,23,42,0.04); border-radius:10px;">
                <div class="muted" style="font-size:12px;">
                    {{ ucfirst($incident->status) }} by {{ $incident->decider?->fullName() ?? '—' }}
                    · {{ $incident->decided_at->diffForHumans() }}
                </div>
                @if ($incident->resolution)
                    <div style="margin-top:6px; white-space:pre-wrap;">{{ $incident->resolution }}</div>
                @endif
            </div>
        @endif
    </div>

    @can('decide', $incident)
        <div class="grid cols-2" style="margin-top:16px; gap:16px;">
            <div class="card">
                <h3 style="margin-top:0;">Resolve</h3>
                <form class="form" method="POST" action="{{ route('discipline.resolve', $incident) }}">
                    @csrf
                    <div class="field">
                        <label>Resolution</label>
                        <textarea name="resolution" rows="3" required maxlength="2000"
                            placeholder="What action was taken? (e.g. parent meeting, written warning, detention)"></textarea>
                    </div>
                    <div><button class="btn" type="submit">Mark resolved</button></div>
                </form>
            </div>
            <div class="card">
                <h3 style="margin-top:0;">Dismiss</h3>
                <form class="form" method="POST" action="{{ route('discipline.dismiss', $incident) }}">
                    @csrf
                    <div class="field">
                        <label>Reason (optional)</label>
                        <textarea name="resolution" rows="3" maxlength="2000"
                            placeholder="Why is this report being dismissed?"></textarea>
                    </div>
                    <div><button class="btn ghost" type="submit">Dismiss incident</button></div>
                </form>
            </div>
        </div>
    @endcan

    <div style="margin-top:16px; display:flex; gap:8px;">
        <a class="btn ghost" href="{{ route('discipline.index') }}">Back to list</a>
        @if ($incident->subject)
            <a class="btn ghost" href="{{ route('discipline.timeline', $incident->subject) }}">View subject timeline</a>
        @endif
    </div>
@endsection
