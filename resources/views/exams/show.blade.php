@extends('layouts.app')

@section('title', $exam->title . ' — Somalite')

@section('content')
    <div class="card">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <h1 style="margin:0;">{{ $exam->title }}</h1>
                <p class="muted" style="margin:6px 0 0 0;">
                    {{ $exam->subject }}
                    @if ($exam->form_level) · {{ $exam->form_level }} @endif
                    @if ($exam->curriculum) · {{ $exam->curriculum }} @endif
                </p>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
                <span class="badge">{{ str_replace('_', ' ', $exam->status) }}</span>
                @if ($exam->isLocked())
                    <span class="badge" style="background:rgba(220, 38, 38, 0.1); color:#b91c1c;">
                        Locked to {{ $exam->lockedTo?->fullName() }}
                    </span>
                @endif
            </div>
        </div>

        <div class="grid cols-3" style="margin-top:16px;">
            <div class="stat">
                <div class="label">Creator</div>
                <div class="value" style="font-size:16px;">{{ $exam->creator?->fullName() }}</div>
                <div class="hint">{{ $exam->creator?->role?->name }} · level {{ $exam->creator_role_level }}</div>
            </div>
            <div class="stat">
                <div class="label">Duration / Marks</div>
                <div class="value" style="font-size:16px;">
                    {{ $exam->duration_minutes ? $exam->duration_minutes.' min' : '—' }}
                    /
                    {{ $exam->total_marks ?? '—' }}
                </div>
                <div class="hint">{{ $exam->scheduled_at?->format('D, d M Y H:i') ?? 'Not scheduled' }}</div>
            </div>
            <div class="stat">
                <div class="label">Approver</div>
                <div class="value" style="font-size:16px;">{{ $exam->approver?->fullName() ?? '—' }}</div>
                <div class="hint">{{ $exam->approver ? $exam->approver->role?->name : 'Not approved yet' }}</div>
            </div>
        </div>

        @if ($exam->notes)
            <div style="margin-top:16px;">
                <div class="label" style="font-size:12px; color:#475569; text-transform:uppercase; letter-spacing:0.4px;">Notes</div>
                <p style="margin:6px 0 0 0; white-space:pre-wrap;">{{ $exam->notes }}</p>
            </div>
        @endif
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Actions</h3>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            @can('submit', $exam)
                <form method="POST" action="{{ route('exams.submit', $exam) }}">
                    @csrf
                    <button class="btn" type="submit">Submit for approval</button>
                </form>
            @endcan

            @can('approve', $exam)
                <form method="POST" action="{{ route('exams.approve', $exam) }}">
                    @csrf
                    <button class="btn" type="submit">Approve</button>
                </form>
                <form method="POST" action="{{ route('exams.reject', $exam) }}">
                    @csrf
                    <button class="btn danger" type="submit">Reject</button>
                </form>
            @endcan

            @can('publish', $exam)
                <form method="POST" action="{{ route('exams.publish', $exam) }}">
                    @csrf
                    <button class="btn" type="submit">Publish</button>
                </form>
            @endcan

            @can('override', $exam)
                <form method="POST" action="{{ route('exams.override', $exam) }}"
                      onsubmit="return confirm('Override this exam? You will become the owner and the exam will return to draft.');">
                    @csrf
                    <button class="btn ghost" type="submit">Override</button>
                </form>
            @endcan

            @can('archive', $exam)
                <form method="POST" action="{{ route('exams.archive', $exam) }}">
                    @csrf
                    <button class="btn ghost" type="submit">Archive</button>
                </form>
            @endcan
        </div>
        @if ($exam->isLocked() && auth()->id() !== $exam->locked_to_id && ! auth()->user()->hasRole(\App\Models\Role::SYSTEM_ADMIN))
            <p class="muted" style="margin:12px 0 0 0;">
                This exam is <strong>locked to the Director</strong>. Only they can edit or override it.
            </p>
        @endif
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Activity</h3>
        @if ($exam->events->isEmpty())
            <p class="muted" style="margin:0;">No events yet.</p>
        @else
            <ul style="list-style:none; padding:0; margin:0;">
                @foreach ($exam->events as $event)
                    <li style="padding:10px 0; border-top:1px solid rgba(15,23,42,0.08);">
                        <strong>{{ ucfirst($event->action) }}</strong>
                        <span class="muted">by {{ $event->actor?->fullName() }} ({{ $event->actor?->role?->name }})</span>
                        <span class="muted">· {{ $event->created_at->diffForHumans() }}</span>
                        @if ($event->from_status && $event->to_status && $event->from_status !== $event->to_status)
                            <div class="muted" style="font-size:13px;">
                                {{ str_replace('_', ' ', $event->from_status) }} → {{ str_replace('_', ' ', $event->to_status) }}
                            </div>
                        @endif
                        @if ($event->comment)
                            <div style="margin-top:4px;">{{ $event->comment }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <p style="margin-top:16px;"><a href="{{ route('exams.index') }}" class="muted">← All exams</a></p>
@endsection
