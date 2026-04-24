@extends('layouts.app')

@section('title', 'Templates — Somalite')

@section('content')
    <div class="card" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <div>
            <h1 style="margin:0;">Templates</h1>
            <p class="muted" style="margin:4px 0 0 0;">Result marklists and academic report formats. System Admin authors; schools assign.</p>
        </div>
        <div style="display:flex; gap:8px;">
            @can('viewAny', App\Models\TemplateAssignment::class)
                <a class="btn ghost" href="{{ route('assignments.index') }}">Assignments</a>
            @endcan
            @if ($canCreate)
                <a class="btn" href="{{ route('templates.create') }}">New template</a>
            @endif
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        @if ($templates->isEmpty())
            <p class="muted" style="margin:0;">No templates yet.</p>
        @else
            <div class="grid" style="gap:10px;">
                @foreach ($templates as $template)
                    <a href="{{ route('templates.show', $template) }}"
                       style="display:grid; grid-template-columns:1fr auto; gap:8px; padding:12px 14px; border:1px solid rgba(15,23,42,0.08); border-radius:12px; background:rgba(255,255,255,0.75); text-decoration:none; color:inherit;">
                        <div>
                            <div style="font-weight:600;">{{ $template->name }}</div>
                            <div class="muted" style="font-size:13px; margin-top:2px;">
                                {{ $template->kindLabel() }}
                                · slug: <code>{{ $template->slug }}</code>
                                @if ($template->creator) · by {{ $template->creator->fullName() }} @endif
                            </div>
                        </div>
                        <div style="align-self:center;">
                            <span class="badge">{{ $template->is_active ? 'active' : 'archived' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
            <div style="margin-top:14px;">{{ $templates->links() }}</div>
        @endif
    </div>
@endsection
