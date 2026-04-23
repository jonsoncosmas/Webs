@extends('layouts.app')

@section('title', 'Exams — Somalite')

@section('content')
    <div class="card" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
        <div>
            <h1 style="margin:0;">Exams</h1>
            <p class="muted" style="margin:4px 0 0 0;">Hierarchical lifecycle with override audit.</p>
        </div>
        @can('create', App\Models\Exam::class)
            <a class="btn" href="{{ route('exams.create') }}">New exam</a>
        @endcan
    </div>

    <div class="card" style="margin-top:16px;">
        @if ($exams->isEmpty())
            <p class="muted" style="margin:0;">No exams yet.</p>
        @else
            <div class="grid" style="gap:10px;">
                @foreach ($exams as $exam)
                    <a href="{{ route('exams.show', $exam) }}"
                       style="display:grid; grid-template-columns:1fr auto; gap:8px; padding:12px 14px; border:1px solid rgba(15,23,42,0.08); border-radius:12px; background:rgba(255,255,255,0.75); text-decoration:none; color:inherit;">
                        <div>
                            <div style="font-weight:600;">{{ $exam->title }}</div>
                            <div class="muted" style="font-size:13px; margin-top:2px;">
                                {{ $exam->subject }}
                                @if ($exam->form_level) · {{ $exam->form_level }} @endif
                                · by {{ $exam->creator?->fullName() ?? '—' }}
                                ({{ $exam->creator?->role?->name ?? '—' }})
                            </div>
                        </div>
                        <div style="align-self:center;">
                            <span class="badge">{{ str_replace('_', ' ', $exam->status) }}</span>
                            @if ($exam->isLocked())
                                <span class="badge" style="background:rgba(220, 38, 38, 0.1); color:#b91c1c; margin-left:4px;">locked</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
            <div style="margin-top:14px;">{{ $exams->links() }}</div>
        @endif
    </div>
@endsection
