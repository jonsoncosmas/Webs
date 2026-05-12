@extends('layouts.app')

@section('title', ($assignment->template?->name ?? 'Assignment') . ' — Somalite')

@section('content')
    <div class="card">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <h1 style="margin:0;">{{ $assignment->template?->name ?? 'Assignment' }}</h1>
                <p class="muted" style="margin:6px 0 0 0;">
                    {{ $assignment->class_label }}
                    @if ($assignment->subject) · {{ $assignment->subject }} @endif
                    @if ($assignment->term) · {{ $assignment->term }} @endif
                </p>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
                <span class="badge">{{ $assignment->statusLabel() }}</span>
                <span class="muted" style="font-size:12px;">{{ $assignment->template?->kindLabel() }}</span>
            </div>
        </div>

        <div class="grid cols-3" style="margin-top:16px;">
            <div class="stat">
                <div class="label">School</div>
                <div class="value" style="font-size:16px;">{{ $assignment->school?->name ?? '—' }}</div>
            </div>
            <div class="stat">
                <div class="label">Created by</div>
                <div class="value" style="font-size:16px;">{{ $assignment->creator?->fullName() ?? '—' }}</div>
                <div class="hint">{{ $assignment->creator?->role?->name }}</div>
            </div>
            <div class="stat">
                <div class="label">Responsible staff</div>
                <div class="value" style="font-size:16px;">{{ $assignment->assignee?->fullName() ?? '—' }}</div>
                <div class="hint">{{ $assignment->assignee?->role?->name ?? 'Not assigned' }}</div>
            </div>
        </div>

        @if ($assignment->notes)
            <div style="margin-top:16px;">
                <div class="label" style="font-size:12px; color:#475569; text-transform:uppercase; letter-spacing:0.4px;">Notes</div>
                <p style="margin:6px 0 0 0; white-space:pre-wrap;">{{ $assignment->notes }}</p>
            </div>
        @endif

        @if ($assignment->printed_at)
            <p class="muted" style="margin-top:12px;">Last printed {{ $assignment->printed_at->diffForHumans() }}.</p>
        @endif
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Actions</h3>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            @can('markReady', $assignment)
                <form method="POST" action="{{ route('assignments.ready', $assignment) }}">
                    @csrf
                    <button class="btn" type="submit">Mark ready</button>
                </form>
            @endcan

            @can('reopen', $assignment)
                <form method="POST" action="{{ route('assignments.reopen', $assignment) }}">
                    @csrf
                    <button class="btn ghost" type="submit">Reopen as draft</button>
                </form>
            @endcan

            @can('print', $assignment)
                <a class="btn" href="{{ route('assignments.print', $assignment) }}">Open print view</a>
            @endcan

            @can('view', $assignment)
                <a class="btn ghost" href="{{ route('assignments.pdf', $assignment) }}">Download PDF</a>
            @endcan

            <a class="btn ghost" href="{{ route('assignments.index') }}">Back</a>
        </div>
    </div>
@endsection
