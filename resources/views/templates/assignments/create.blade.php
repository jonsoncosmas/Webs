@extends('layouts.app')

@section('title', 'New assignment — Somalite')

@section('content')
    <div class="card" style="max-width:720px; margin:0 auto;">
        <h1 style="margin-top:0;">New assignment</h1>
        <p class="muted">Pick a template, the class it applies to, and the staff member responsible. They can fill in marks / comments afterwards.</p>

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
        @endif

        @if ($templates->isEmpty())
            <div class="alert info">No active templates available yet. Ask System Admin to publish one.</div>
        @else
        <form class="form" method="POST" action="{{ route('assignments.store') }}">
            @csrf
            <div class="field">
                <label for="template_id">Template</label>
                <select id="template_id" name="template_id" required>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}"
                                @selected((int) old('template_id', $selectedTemplateId) === $template->id)>
                            {{ $template->name }} ({{ $template->kindLabel() }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="grid cols-2">
                <div class="field">
                    <label for="class_label">Class</label>
                    <input id="class_label" name="class_label" type="text" required maxlength="120"
                           placeholder="e.g. Form 4 Stream A" value="{{ old('class_label') }}">
                </div>
                <div class="field">
                    <label for="subject">Subject (optional)</label>
                    <input id="subject" name="subject" type="text" maxlength="120"
                           placeholder="e.g. Mathematics" value="{{ old('subject') }}">
                </div>
            </div>
            <div class="grid cols-2">
                <div class="field">
                    <label for="term">Term / period</label>
                    <input id="term" name="term" type="text" maxlength="60"
                           placeholder="e.g. Term 1 2025" value="{{ old('term') }}">
                </div>
                <div class="field">
                    <label for="assigned_to_id">Responsible staff</label>
                    <select id="assigned_to_id" name="assigned_to_id">
                        <option value="">—</option>
                        @foreach ($staff as $person)
                            <option value="{{ $person->id }}" @selected((int) old('assigned_to_id') === $person->id)>
                                {{ $person->fullName() }} ({{ $person->role?->name ?? '—' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn" type="submit">Create</button>
                <a class="btn ghost" href="{{ route('assignments.index') }}">Cancel</a>
            </div>
        </form>
        @endif
    </div>
@endsection
