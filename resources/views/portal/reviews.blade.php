@extends('layouts.app')

@section('title', 'Review requests — Somalite')

@section('content')
    <div class="card">
        <h1 style="margin:0;">{{ $student->fullName() }} — review requests</h1>
    </div>

    <div class="card" style="margin-top:16px;">
        @if ($reviews->isEmpty())
            <p class="muted" style="margin:0;">No review requests yet.</p>
        @else
            <div class="grid" style="gap:10px;">
                @foreach ($reviews as $review)
                    <div style="padding:12px 14px; border:1px solid rgba(15,23,42,0.08); border-radius:12px; background:rgba(255,255,255,0.75);">
                        <div style="display:flex; justify-content:space-between; gap:8px; align-items:center; flex-wrap:wrap;">
                            <div>
                                <div style="font-weight:600;">{{ $review->attempt?->exam?->title ?? 'Exam' }}</div>
                                <div class="muted" style="font-size:12px;">{{ $review->created_at->format('Y-m-d H:i') }}</div>
                            </div>
                            <span class="badge">{{ $review->status }}</span>
                        </div>
                        <div style="margin-top:8px; white-space:pre-wrap;">{{ $review->reason }}</div>
                        @if ($review->feedback)
                            <div style="margin-top:10px; padding:10px 12px; background:rgba(22,163,74,0.08); border-radius:8px;">
                                <div class="muted" style="font-size:12px;">
                                    {{ ucfirst($review->status) }} by {{ $review->decider?->fullName() ?? '—' }}
                                    · {{ $review->decided_at?->diffForHumans() }}
                                </div>
                                <div style="white-space:pre-wrap; margin-top:4px;">{{ $review->feedback }}</div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
