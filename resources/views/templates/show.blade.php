@extends('layouts.app')

@section('title', $template->name . ' — Somalite')

@section('content')
    <div class="card">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <h1 style="margin:0;">{{ $template->name }}</h1>
                <p class="muted" style="margin:6px 0 0 0;">
                    {{ $template->kindLabel() }}
                    · slug: <code>{{ $template->slug }}</code>
                </p>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
                <span class="badge">{{ $template->is_active ? 'active' : 'archived' }}</span>
            </div>
        </div>

        @if ($template->description)
            <div style="margin-top:16px;">
                <div class="label" style="font-size:12px; color:#475569; text-transform:uppercase; letter-spacing:0.4px;">Description</div>
                <p style="margin:6px 0 0 0; white-space:pre-wrap;">{{ $template->description }}</p>
            </div>
        @endif

        <div class="grid cols-2" style="margin-top:16px;">
            <div class="stat">
                <div class="label">Layout view</div>
                <div class="value" style="font-size:14px; font-family:ui-monospace,monospace;">{{ $template->viewName() }}</div>
            </div>
            <div class="stat">
                <div class="label">Created by</div>
                <div class="value" style="font-size:16px;">{{ $template->creator?->fullName() ?? 'System' }}</div>
                <div class="hint">{{ $template->created_at?->diffForHumans() }}</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Actions</h3>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            @can('create', App\Models\TemplateAssignment::class)
                @if ($template->is_active)
                    <a class="btn" href="{{ route('assignments.create', ['template_id' => $template->id]) }}">Assign to a class</a>
                @endif
            @endcan

            @can('archive', $template)
                @if ($template->is_active)
                    <form method="POST" action="{{ route('templates.archive', $template) }}"
                          onsubmit="return confirm('Archive this template? Schools won\'t be able to assign it until reactivated.');">
                        @csrf
                        <button class="btn ghost" type="submit">Archive</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('templates.activate', $template) }}">
                        @csrf
                        <button class="btn" type="submit">Reactivate</button>
                    </form>
                @endif
            @endcan

            <a class="btn ghost" href="{{ route('templates.index') }}">Back to templates</a>
        </div>
    </div>
@endsection
