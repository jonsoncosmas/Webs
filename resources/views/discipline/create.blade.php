@extends('layouts.app')

@section('title', 'Log incident — Somalite')

@section('content')
    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
        </div>
    @endif

    <div class="card">
        <h1 style="margin:0;">Log discipline incident</h1>
        <p class="muted" style="margin:4px 0 0 0;">Reports are visible to Discipline leadership and school administration.</p>

        <form class="form" method="POST" action="{{ route('discipline.store') }}" style="margin-top:16px;">
            @csrf
            <div class="grid cols-2">
                <div class="field">
                    <label>Subject (student / teacher)</label>
                    <select name="subject_id" required>
                        <option value="">Select…</option>
                        @foreach ($subjects as $s)
                            <option value="{{ $s->id }}"
                                @selected((int) old('subject_id', $preselected) === $s->id)>
                                {{ $s->fullName() }} — {{ $s->role?->name ?? 'No role' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Category</label>
                    <select name="category" required>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected(old('category') === $cat)>
                                {{ ucfirst(str_replace('_', ' ', $cat)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="field">
                <label>Title</label>
                <input type="text" name="title" maxlength="200" required value="{{ old('title') }}" placeholder="Short summary e.g. 'Disrespect to duty teacher'">
            </div>

            <div class="field">
                <label>Description</label>
                <textarea name="description" rows="4" maxlength="3000">{{ old('description') }}</textarea>
            </div>

            <div class="grid cols-2">
                <div class="field">
                    <label>Occurred on</label>
                    <input type="date" name="occurred_on" required value="{{ old('occurred_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}">
                </div>
                <div class="field">
                    <label>Severity (1 trivial → 5 critical)</label>
                    <select name="severity" required>
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" @selected((int) old('severity', 2) === $i)>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:10px;">
                <button class="btn" type="submit">Log incident</button>
                <a class="btn ghost" href="{{ route('discipline.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
