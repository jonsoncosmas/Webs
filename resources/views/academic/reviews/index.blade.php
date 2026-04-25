@extends('layouts.app')

@section('title', 'Review inbox — Somalite')

@section('content')
    <div class="card">
        <h1 style="margin:0;">Result review inbox</h1>
        <p class="muted" style="margin:4px 0 0 0;">Student and parent requests for result review. Acknowledge to let them know you're looking; resolve / reject with written feedback.</p>
    </div>

    <div class="card" style="margin-top:16px;">
        <form method="GET" action="{{ route('academic.reviews.index') }}" style="display:flex; gap:8px; align-items:center;">
            <label style="display:flex; gap:6px; align-items:center; font-size:13px; color:var(--text-soft);">
                Status
                <select name="status" onchange="this.form.submit()">
                    <option value="">Any</option>
                    <option value="pending" @selected($filterStatus === 'pending')>Pending</option>
                    <option value="acknowledged" @selected($filterStatus === 'acknowledged')>Acknowledged</option>
                    <option value="resolved" @selected($filterStatus === 'resolved')>Resolved</option>
                    <option value="rejected" @selected($filterStatus === 'rejected')>Rejected</option>
                </select>
            </label>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        @if ($reviews->isEmpty())
            <p class="muted" style="margin:0;">Nothing matches.</p>
        @else
            <div class="grid" style="gap:10px;">
                @foreach ($reviews as $review)
                    <a href="{{ route('academic.reviews.show', $review) }}"
                       style="display:grid; grid-template-columns:1fr auto; gap:8px; padding:12px 14px;
                              border:1px solid rgba(15,23,42,0.08); border-radius:12px;
                              background:rgba(255,255,255,0.75); text-decoration:none; color:inherit;">
                        <div>
                            <div style="font-weight:600;">{{ $review->student?->fullName() }} · {{ $review->attempt?->exam?->title ?? '—' }}</div>
                            <div class="muted" style="font-size:12px; margin-top:2px;">
                                Submitted by {{ $review->submitter?->fullName() }} · {{ $review->created_at->diffForHumans() }}
                            </div>
                            <div style="margin-top:6px; font-size:13px;">{{ \Illuminate\Support\Str::limit($review->reason, 160) }}</div>
                        </div>
                        <span class="badge">{{ $review->status }}</span>
                    </a>
                @endforeach
            </div>
            <div style="margin-top:14px;">{{ $reviews->links() }}</div>
        @endif
    </div>
@endsection
