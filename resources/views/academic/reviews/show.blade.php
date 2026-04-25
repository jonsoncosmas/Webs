@extends('layouts.app')

@section('title', 'Review — Somalite')

@section('content')
    @if (session('status'))<div class="alert success">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
        </div>
    @endif

    <div class="card">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <h1 style="margin:0;">{{ $review->attempt?->exam?->title ?? 'Review request' }}</h1>
                <p class="muted" style="margin:4px 0 0 0;">
                    {{ $review->student?->fullName() }} ·
                    submitted by {{ $review->submitter?->fullName() }} ·
                    {{ $review->created_at->format('Y-m-d H:i') }}
                </p>
            </div>
            <span class="badge">{{ $review->status }}</span>
        </div>

        <div style="margin-top:12px;">
            <div class="muted" style="font-size:12px;">Reason</div>
            <p style="white-space:pre-wrap; margin:4px 0 0 0;">{{ $review->reason }}</p>
        </div>

        @if ($review->attempt)
            <div style="margin-top:12px; padding:10px 12px; background:rgba(15,23,42,0.04); border-radius:10px;">
                <div class="muted" style="font-size:12px;">Current score</div>
                <div style="font-weight:700; font-size:18px;">
                    {{ $review->attempt->score }}/{{ $review->attempt->total_marks }}
                    @if ($review->attempt->grade) · {{ $review->attempt->grade }} @endif
                </div>
            </div>
        @endif

        @if ($review->feedback)
            <div style="margin-top:12px; padding:10px 12px; background:rgba(22,163,74,0.08); border-radius:10px;">
                <div class="muted" style="font-size:12px;">
                    {{ ucfirst($review->status) }} by {{ $review->decider?->fullName() ?? '—' }}
                    · {{ $review->decided_at?->diffForHumans() }}
                </div>
                <div style="white-space:pre-wrap; margin-top:4px;">{{ $review->feedback }}</div>
            </div>
        @endif
    </div>

    @can('decide', $review)
        <div class="grid cols-2" style="margin-top:16px; gap:16px; align-items:start;">
            @if ($review->status === 'pending')
                <div class="card">
                    <h3 style="margin-top:0;">Acknowledge</h3>
                    <p class="muted">Lets the student / parent know you've seen the request.</p>
                    <form method="POST" action="{{ route('academic.reviews.acknowledge', $review) }}">
                        @csrf
                        <button class="btn ghost" type="submit">Mark acknowledged</button>
                    </form>
                </div>
            @endif

            <div class="card">
                <h3 style="margin-top:0;">Resolve</h3>
                <form class="form" method="POST" action="{{ route('academic.reviews.resolve', $review) }}">
                    @csrf
                    <div class="field">
                        <label>Feedback</label>
                        <textarea name="feedback" rows="4" minlength="5" maxlength="2000" required
                            placeholder="Explain what was reviewed and any corrections made."></textarea>
                    </div>
                    <div><button class="btn" type="submit">Resolve</button></div>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Reject</h3>
                <form class="form" method="POST" action="{{ route('academic.reviews.reject', $review) }}">
                    @csrf
                    <div class="field">
                        <label>Reason</label>
                        <textarea name="feedback" rows="4" minlength="5" maxlength="2000" required
                            placeholder="Explain why the request isn't being actioned."></textarea>
                    </div>
                    <div><button class="btn ghost" type="submit">Reject</button></div>
                </form>
            </div>
        </div>
    @endcan

    <div style="margin-top:16px;">
        <a class="btn ghost" href="{{ route('academic.reviews.index') }}">Back to inbox</a>
    </div>
@endsection
