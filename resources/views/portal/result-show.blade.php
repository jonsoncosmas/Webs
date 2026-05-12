@extends('layouts.app')

@section('title', ($attempt->exam?->title ?? 'Result') . ' — Somalite')

@section('content')
    @if (session('status'))<div class="alert success">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
        </div>
    @endif

    <div class="card">
        <h1 style="margin:0;">{{ $attempt->exam?->title ?? 'Result' }}</h1>
        <p class="muted" style="margin:4px 0 0 0;">
            {{ $attempt->exam?->subject }}
            @if ($attempt->exam?->form_level) · {{ $attempt->exam->form_level }} @endif
            · Student: {{ $attempt->student?->fullName() }}
        </p>

        <div class="grid cols-3" style="margin-top:14px;">
            <div class="stat">
                <div class="label">Score</div>
                <div class="value">{{ $attempt->score }}/{{ $attempt->total_marks }}</div>
                @if ($attempt->percentage() !== null)
                    <div class="hint">{{ $attempt->percentage() }}%</div>
                @endif
            </div>
            <div class="stat">
                <div class="label">Grade</div>
                <div class="value">{{ $attempt->grade ?? '—' }}</div>
            </div>
            <div class="stat">
                <div class="label">Scored</div>
                <div class="value" style="font-size:18px;">{{ $attempt->scored_at?->format('Y-m-d') ?? '—' }}</div>
                <div class="hint">by {{ $attempt->scorer?->fullName() ?? '—' }}</div>
            </div>
        </div>

        @if ($attempt->notes)
            <div style="margin-top:12px; padding:10px 12px; background:rgba(15,23,42,0.04); border-radius:10px;">
                <div class="muted" style="font-size:12px;">Teacher notes</div>
                <div style="white-space:pre-wrap;">{{ $attempt->notes }}</div>
            </div>
        @endif
    </div>

    @can('create', [App\Models\ResultReviewRequest::class, $attempt])
        <div class="card" style="margin-top:16px;">
            <h3 style="margin-top:0;">Request a review</h3>
            <p class="muted" style="margin-top:4px;">Your request is sent to the Academic Head. You'll see the feedback here once they respond.</p>
            <form class="form" method="POST" action="{{ route('portal.result.review', $attempt) }}">
                @csrf
                <div class="field">
                    <label>Reason</label>
                    <textarea name="reason" rows="4" minlength="10" maxlength="2000" required
                        placeholder="Explain what you'd like reviewed — e.g. a specific question, marking, or the overall grade."></textarea>
                </div>
                <div><button class="btn" type="submit">Submit review request</button></div>
            </form>
        </div>
    @endcan

    @if ($attempt->reviewRequests->isNotEmpty())
        <div class="card" style="margin-top:16px;">
            <h3 style="margin-top:0;">Review history</h3>
            <div class="grid" style="gap:8px;">
                @foreach ($attempt->reviewRequests->sortByDesc('created_at') as $req)
                    <div style="padding:10px 12px; border:1px solid rgba(15,23,42,0.08); border-radius:10px; background:rgba(255,255,255,0.75);">
                        <div style="display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap; align-items:center;">
                            <div class="muted" style="font-size:12px;">{{ $req->created_at->format('Y-m-d H:i') }}</div>
                            <span class="badge">{{ $req->status }}</span>
                        </div>
                        <div style="margin-top:6px; white-space:pre-wrap;">{{ $req->reason }}</div>
                        @if ($req->feedback)
                            <div style="margin-top:10px; padding:10px 12px; background:rgba(22,163,74,0.08); border-radius:8px;">
                                <div class="muted" style="font-size:12px;">
                                    Feedback from {{ $req->decider?->fullName() ?? '—' }}
                                    · {{ $req->decided_at?->diffForHumans() }}
                                </div>
                                <div style="white-space:pre-wrap; margin-top:4px;">{{ $req->feedback }}</div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div style="margin-top:16px; display:flex; gap:8px; flex-wrap:wrap;">
        <a class="btn ghost" href="{{ route('portal.results', ['student_id' => $attempt->student_user_id]) }}">Back to results</a>
        <a class="btn" href="{{ route('portal.result.pdf', $attempt) }}">Download result PDF</a>
    </div>
@endsection
