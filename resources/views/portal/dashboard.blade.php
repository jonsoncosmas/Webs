@extends('layouts.app')

@section('title', 'Portal — Somalite')

@section('content')
    <div class="card">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center;">
            <div>
                <h1 style="margin:0;">{{ $student->fullName() }}</h1>
                <p class="muted" style="margin:4px 0 0 0;">
                    {{ $student->role?->name ?? '—' }}
                    @if ($student->school) · {{ $student->school->name }} @endif
                </p>
            </div>
            @if ($children->count() > 1)
                <form method="GET" action="{{ route('portal.dashboard') }}">
                    <select name="student_id" onchange="this.form.submit()">
                        @foreach ($children as $child)
                            <option value="{{ $child->id }}" @selected($child->id === $student->id)>
                                {{ $child->fullName() }}
                                @if ($child->pivot->relationship) ({{ $child->pivot->relationship }}) @endif
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>

    <div class="grid cols-3" style="margin-top:16px;">
        <div class="stat">
            <div class="label">Published exams</div>
            <div class="value">{{ $exams->count() }}</div>
            <div class="hint">available at your school</div>
        </div>
        <div class="stat">
            <div class="label">Results on record</div>
            <div class="value">{{ $attempts->count() }}</div>
            <div class="hint">scored by staff</div>
        </div>
        <div class="stat">
            <div class="label">Open reviews</div>
            <div class="value">{{ $reviews->whereIn('status', ['pending', 'acknowledged'])->count() }}</div>
            <div class="hint">awaiting feedback</div>
        </div>
    </div>

    <div class="grid cols-2" style="margin-top:16px; gap:16px; align-items:start;">
        <div class="card">
            <h3 style="margin-top:0;">Recent results</h3>
            @if ($attempts->isEmpty())
                <p class="muted" style="margin:0;">No results recorded yet.</p>
            @else
                <div class="grid" style="gap:8px;">
                    @foreach ($attempts->take(5) as $attempt)
                        <a href="{{ route('portal.result.show', ['attempt' => $attempt, 'student_id' => $student->id]) }}"
                           style="display:grid; grid-template-columns:1fr auto; gap:8px; padding:10px 12px;
                                  border:1px solid rgba(15,23,42,0.08); border-radius:10px;
                                  background:rgba(255,255,255,0.75); text-decoration:none; color:inherit;">
                            <div>
                                <div style="font-weight:600;">{{ $attempt->exam->title ?? 'Exam #'.$attempt->exam_id }}</div>
                                <div class="muted" style="font-size:12px;">
                                    {{ $attempt->exam?->subject }}
                                    @if ($attempt->scored_at) · {{ $attempt->scored_at->format('Y-m-d') }} @endif
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:700;">{{ $attempt->score ?? '—' }}/{{ $attempt->total_marks ?? '—' }}</div>
                                @if ($attempt->grade) <div class="muted" style="font-size:12px;">{{ $attempt->grade }}</div> @endif
                            </div>
                        </a>
                    @endforeach
                </div>
                <div style="margin-top:10px;">
                    <a class="btn ghost" href="{{ route('portal.results', request()->only('student_id')) }}">All results →</a>
                </div>
            @endif
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Upcoming exams</h3>
            @if ($exams->isEmpty())
                <p class="muted" style="margin:0;">No published exams at the moment.</p>
            @else
                <div class="grid" style="gap:8px;">
                    @foreach ($exams->take(5) as $exam)
                        <div style="padding:10px 12px; border:1px solid rgba(15,23,42,0.08); border-radius:10px; background:rgba(255,255,255,0.75);">
                            <div style="font-weight:600;">{{ $exam->title }}</div>
                            <div class="muted" style="font-size:12px;">
                                {{ $exam->subject }}
                                @if ($exam->form_level) · {{ $exam->form_level }} @endif
                                @if ($exam->scheduled_at) · {{ $exam->scheduled_at->format('Y-m-d H:i') }} @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($reviews->isNotEmpty())
        <div class="card" style="margin-top:16px;">
            <h3 style="margin-top:0;">Result review requests</h3>
            <div class="grid" style="gap:8px;">
                @foreach ($reviews as $review)
                    <div style="padding:10px 12px; border:1px solid rgba(15,23,42,0.08); border-radius:10px; background:rgba(255,255,255,0.75); display:grid; grid-template-columns:1fr auto; gap:8px;">
                        <div>
                            <div style="font-weight:600;">{{ \Illuminate\Support\Str::limit($review->reason, 120) }}</div>
                            <div class="muted" style="font-size:12px;">{{ $review->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="badge">{{ $review->status }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
