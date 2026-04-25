@extends('layouts.app')

@section('title', 'Results — Somalite')

@section('content')
    <div class="card">
        <h1 style="margin:0;">{{ $student->fullName() }} — results</h1>
    </div>

    <div class="card" style="margin-top:16px;">
        @if ($attempts->isEmpty())
            <p class="muted" style="margin:0;">No scored results yet.</p>
        @else
            <div class="grid" style="gap:10px;">
                @foreach ($attempts as $attempt)
                    <a href="{{ route('portal.result.show', ['attempt' => $attempt, 'student_id' => $student->id]) }}"
                       style="display:grid; grid-template-columns:1fr auto auto; gap:12px; padding:12px 14px;
                              border:1px solid rgba(15,23,42,0.08); border-radius:12px;
                              background:rgba(255,255,255,0.75); text-decoration:none; color:inherit;">
                        <div>
                            <div style="font-weight:600;">{{ $attempt->exam?->title ?? 'Exam #'.$attempt->exam_id }}</div>
                            <div class="muted" style="font-size:12px;">
                                {{ $attempt->exam?->subject }}
                                @if ($attempt->scored_at) · {{ $attempt->scored_at->format('Y-m-d') }} @endif
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:700;">{{ $attempt->score }}/{{ $attempt->total_marks }}</div>
                            @if ($attempt->percentage() !== null)
                                <div class="muted" style="font-size:12px;">{{ $attempt->percentage() }}%</div>
                            @endif
                        </div>
                        <div style="text-align:right;">
                            @if ($attempt->grade) <span class="badge">{{ $attempt->grade }}</span> @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div style="margin-top:16px;">
        <a class="btn ghost" href="{{ route('portal.dashboard', request()->only('student_id')) }}">Back to portal</a>
    </div>
@endsection
