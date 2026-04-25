@extends('layouts.app')

@section('title', 'Staff directory — Somalite')

@section('content')
    <div class="card" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <div>
            <h1 style="margin:0;">Staff directory</h1>
            <p class="muted" style="margin:4px 0 0 0;">HR records, certificates and leaves.</p>
        </div>
        <form method="GET" action="{{ route('hr.index') }}" style="display:flex; gap:8px; align-items:center;">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search name or username"
                   style="border:1px solid rgba(15,23,42,0.12); background:rgba(255,255,255,0.9); border-radius:12px; padding:10px 14px; font-size:15px;">
            <button class="btn ghost" type="submit">Search</button>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        @if ($users->isEmpty())
            <p class="muted" style="margin:0;">No staff match.</p>
        @else
            <div class="grid" style="gap:10px;">
                @foreach ($users as $person)
                    <a href="{{ route('hr.show', $person) }}"
                       style="display:grid; grid-template-columns:1fr auto; gap:8px; padding:12px 14px; border:1px solid rgba(15,23,42,0.08); border-radius:12px; background:rgba(255,255,255,0.75); text-decoration:none; color:inherit;">
                        <div>
                            <div style="font-weight:600;">{{ $person->fullName() }}</div>
                            <div class="muted" style="font-size:13px; margin-top:2px;">
                                {{ $person->role?->name ?? '—' }}
                                · @username {{ $person->username }}
                                @if ($person->staffProfile?->employee_no) · #{{ $person->staffProfile->employee_no }} @endif
                            </div>
                        </div>
                        <div style="align-self:center; display:flex; gap:6px;">
                            @if ($person->staffProfile)
                                <span class="badge">profile</span>
                            @else
                                <span class="badge" style="background:rgba(217,119,6,0.12); color:#b45309;">no profile</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
            <div style="margin-top:14px;">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
