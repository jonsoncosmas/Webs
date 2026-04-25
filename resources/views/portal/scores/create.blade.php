@extends('layouts.app')

@section('title', 'Enter scores — Somalite')

@section('content')
    @if (session('status'))<div class="alert success">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
        </div>
    @endif

    <div class="card">
        <h1 style="margin:0;">{{ $exam->title }} — enter scores</h1>
        <p class="muted" style="margin:4px 0 0 0;">
            {{ $exam->subject }}
            @if ($exam->form_level) · {{ $exam->form_level }} @endif
            · total {{ $exam->total_marks ?? '—' }}
            · status {{ $exam->status }}
        </p>
    </div>

    <div class="card" style="margin-top:16px;">
        <form class="form" method="POST" action="{{ route('portal.scores.store', $exam) }}">
            @csrf
            <div class="grid cols-2">
                <div class="field">
                    <label>Student</label>
                    <select name="student_user_id" required>
                        <option value="">Select…</option>
                        @foreach ($students as $s)
                            <option value="{{ $s->id }}" @selected(old('student_user_id') == $s->id)>
                                {{ $s->fullName() }}
                                @if ($existing->has($s->id)) · already scored ({{ $existing[$s->id] }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Grade (optional)</label>
                    <input type="text" name="grade" maxlength="5" value="{{ old('grade') }}" placeholder="A / B+ / ...">
                </div>
            </div>
            <div class="grid cols-2">
                <div class="field">
                    <label>Score</label>
                    <input type="number" name="score" min="0" required value="{{ old('score') }}">
                </div>
                <div class="field">
                    <label>Total marks</label>
                    <input type="number" name="total_marks" min="1" required value="{{ old('total_marks', $exam->total_marks) }}">
                </div>
            </div>
            <div class="field">
                <label>Notes (optional)</label>
                <textarea name="notes" rows="3" maxlength="1000">{{ old('notes') }}</textarea>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn" type="submit">Save score</button>
                <a class="btn ghost" href="{{ route('exams.show', $exam) }}">Back to exam</a>
            </div>
        </form>
    </div>
@endsection
