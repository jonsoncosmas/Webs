@extends('layouts.app')

@section('title', 'Discipline — Somalite')

@section('content')
    <div class="card" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <div>
            <h1 style="margin:0;">Discipline</h1>
            <p class="muted" style="margin:4px 0 0 0;">Incident log, resolution pipeline, and behaviour ↔ academics overlay.</p>
        </div>
        @can('create', App\Models\DisciplineIncident::class)
            <a class="btn" href="{{ route('discipline.create') }}">Log incident</a>
        @endcan
    </div>

    <div class="card" style="margin-top:16px;">
        <form method="GET" action="{{ route('discipline.index') }}"
              style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <label style="display:flex; gap:6px; align-items:center; font-size:13px; color:var(--text-soft);">
                Status
                <select name="status" onchange="this.form.submit()">
                    <option value="">Any</option>
                    <option value="open" @selected($filterStatus === 'open')>Open</option>
                    <option value="resolved" @selected($filterStatus === 'resolved')>Resolved</option>
                    <option value="dismissed" @selected($filterStatus === 'dismissed')>Dismissed</option>
                </select>
            </label>
            <label style="display:flex; gap:6px; align-items:center; font-size:13px; color:var(--text-soft);">
                Category
                <select name="category" onchange="this.form.submit()">
                    <option value="">Any</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" @selected($filterCategory === $cat)>{{ ucfirst(str_replace('_', ' ', $cat)) }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        @if ($incidents->isEmpty())
            <p class="muted" style="margin:0;">No incidents match.</p>
        @else
            <div class="grid" style="gap:10px;">
                @foreach ($incidents as $incident)
                    <a href="{{ route('discipline.show', $incident) }}"
                       style="display:grid; grid-template-columns:1fr auto; gap:8px; padding:12px 14px;
                              border:1px solid rgba(15,23,42,0.08); border-radius:12px;
                              background:rgba(255,255,255,0.75); text-decoration:none; color:inherit;">
                        <div>
                            <div style="font-weight:600;">{{ $incident->title }}</div>
                            <div class="muted" style="font-size:13px; margin-top:2px;">
                                {{ $incident->subject?->fullName() ?? '—' }}
                                · {{ $incident->categoryLabel() }}
                                · {{ $incident->occurred_on->format('Y-m-d') }}
                                · severity {{ $incident->severity }}
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span class="badge" style="background:
                                @switch($incident->status)
                                    @case('resolved') rgba(22,163,74,0.12) @break
                                    @case('dismissed') rgba(15,23,42,0.08) @break
                                    @default rgba(217,119,6,0.12)
                                @endswitch
                                ; color:
                                @switch($incident->status)
                                    @case('resolved') #15803d @break
                                    @case('dismissed') #475569 @break
                                    @default #b45309
                                @endswitch
                                ;">{{ $incident->status }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
            <div style="margin-top:14px;">{{ $incidents->links() }}</div>
        @endif
    </div>
@endsection
