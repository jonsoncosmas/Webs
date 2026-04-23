@extends('layouts.app')

@section('title', 'New exam — Somalite')

@section('content')
    <div class="card" style="max-width:720px; margin:0 auto;">
        <h1 style="margin-top:0;">New exam</h1>
        <p class="muted">
            @if (auth()->user()->hasRole(\App\Models\Role::DIRECTOR))
                As Director, this exam will be <strong>locked to you</strong> and auto-approved.
            @else
                This exam starts as a draft. Submit it for approval when ready.
            @endif
        </p>

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
        @endif

        <form class="form" method="POST" action="{{ route('exams.store') }}">
            @csrf
            <div class="field">
                <label for="title">Title</label>
                <input id="title" name="title" type="text" required maxlength="200" value="{{ old('title') }}">
            </div>
            <div class="grid cols-2">
                <div class="field">
                    <label for="subject">Subject</label>
                    <input id="subject" name="subject" type="text" required maxlength="100" value="{{ old('subject') }}">
                </div>
                <div class="field">
                    <label for="form_level">Form / Class</label>
                    <input id="form_level" name="form_level" type="text" maxlength="40"
                           placeholder="e.g. Form 4" value="{{ old('form_level') }}">
                </div>
            </div>
            <div class="grid cols-3">
                <div class="field">
                    <label for="curriculum">Curriculum</label>
                    <select id="curriculum" name="curriculum">
                        <option value="">—</option>
                        @foreach (['NECTA','Cambridge','Other'] as $c)
                            <option value="{{ $c }}" @selected(old('curriculum') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="duration_minutes">Duration (min)</label>
                    <input id="duration_minutes" name="duration_minutes" type="number" min="5" max="600" value="{{ old('duration_minutes') }}">
                </div>
                <div class="field">
                    <label for="total_marks">Total marks</label>
                    <input id="total_marks" name="total_marks" type="number" min="1" max="1000" value="{{ old('total_marks') }}">
                </div>
            </div>
            <div class="field">
                <label for="scheduled_at">Scheduled at</label>
                <input id="scheduled_at" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at') }}">
            </div>
            <div class="field">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn" type="submit">Create</button>
                <a class="btn ghost" href="{{ route('exams.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
