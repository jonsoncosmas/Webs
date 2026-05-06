@extends('layouts.app')

@section('title', 'My exams — Somalite')

@section('content')
    <div class="card">
        <h1 style="margin:0;">My exams</h1>
        <p class="muted" style="margin:4px 0 0 0;">Published exams at {{ $student->school?->name ?? 'your school' }}.</p>
    </div>

    <div class="card" style="margin-top:16px;">
        @if ($exams->isEmpty())
            <p class="muted" style="margin:0;">No published exams right now.</p>
        @else
            <div class="grid" style="gap:10px;">
                @foreach ($exams as $exam)
                    <div style="padding:12px 14px; border:1px solid rgba(15,23,42,0.08); border-radius:12px; background:rgba(255,255,255,0.75);">
                        <div style="display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                            <div>
                                <div style="font-weight:600;">{{ $exam->title }}</div>
                                <div class="muted" style="font-size:13px; margin-top:2px;">
                                    {{ $exam->subject }}
                                    @if ($exam->form_level) · {{ $exam->form_level }} @endif
                                    @if ($exam->curriculum) · {{ $exam->curriculum }} @endif
                                    @if ($exam->duration_minutes) · {{ $exam->duration_minutes }} min @endif
                                    @if ($exam->total_marks) · {{ $exam->total_marks }} marks @endif
                                    @if ($exam->scheduled_at) · {{ $exam->scheduled_at->format('Y-m-d H:i') }} @endif
                                </div>
                            </div>
                            @if ($user->hasRole(\App\Models\Role::STUDENT))
                                <a class="btn" href="{{ route('portal.exams.take', $exam) }}">Take exam</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div style="margin-top:16px;">
        <a class="btn ghost" href="{{ route('portal.dashboard', request()->only('student_id')) }}">Back to portal</a>
    </div>
@endsection
