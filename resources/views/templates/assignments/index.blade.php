@extends('layouts.app')

@section('title', 'Assignments — Somalite')

@section('content')
    <div class="card" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <div>
            <h1 style="margin:0;">Template assignments</h1>
            <p class="muted" style="margin:4px 0 0 0;">Per-class marklists and report cards.</p>
        </div>
        <div style="display:flex; gap:8px;">
            <a class="btn ghost" href="{{ route('templates.index') }}">Templates</a>
            @can('create', App\Models\TemplateAssignment::class)
                <a class="btn" href="{{ route('assignments.create') }}">New assignment</a>
            @endcan
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        @if ($assignments->isEmpty())
            <p class="muted" style="margin:0;">No assignments yet.</p>
        @else
            <div class="grid" style="gap:10px;">
                @foreach ($assignments as $assignment)
                    <a href="{{ route('assignments.show', $assignment) }}"
                       style="display:grid; grid-template-columns:1fr auto; gap:8px; padding:12px 14px; border:1px solid rgba(15,23,42,0.08); border-radius:12px; background:rgba(255,255,255,0.75); text-decoration:none; color:inherit;">
                        <div>
                            <div style="font-weight:600;">{{ $assignment->template?->name ?? '—' }}</div>
                            <div class="muted" style="font-size:13px; margin-top:2px;">
                                {{ $assignment->class_label }}
                                @if ($assignment->subject) · {{ $assignment->subject }} @endif
                                @if ($assignment->term) · {{ $assignment->term }} @endif
                                · by {{ $assignment->creator?->fullName() ?? '—' }}
                                @if ($assignment->assignee) · to {{ $assignment->assignee->fullName() }} @endif
                            </div>
                        </div>
                        <div style="align-self:center;">
                            <span class="badge">{{ $assignment->statusLabel() }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
            <div style="margin-top:14px;">{{ $assignments->links() }}</div>
        @endif
    </div>
@endsection
