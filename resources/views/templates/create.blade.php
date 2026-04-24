@extends('layouts.app')

@section('title', 'New template — Somalite')

@section('content')
    <div class="card" style="max-width:720px; margin:0 auto;">
        <h1 style="margin-top:0;">New template</h1>
        <p class="muted">Templates are authored once by System Admin and then picked up by any school.</p>

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
        @endif

        <form class="form" method="POST" action="{{ route('templates.store') }}">
            @csrf
            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" required maxlength="160" value="{{ old('name') }}">
            </div>
            <div class="grid cols-2">
                <div class="field">
                    <label for="kind">Kind</label>
                    <select id="kind" name="kind" required>
                        @foreach ($kinds as $value => $label)
                            <option value="{{ $value }}" @selected(old('kind') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" maxlength="160"
                           placeholder="(auto from name)" value="{{ old('slug') }}">
                </div>
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" maxlength="2000">{{ old('description') }}</textarea>
            </div>
            <div class="field">
                <label for="layout_view">Layout view (optional)</label>
                <input id="layout_view" name="layout_view" type="text" maxlength="200"
                       placeholder="templates.layouts.result_marklist_basic" value="{{ old('layout_view') }}">
                <span class="muted">Blade view to render when printing. Leave blank to use the default for the selected kind.</span>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn" type="submit">Create</button>
                <a class="btn ghost" href="{{ route('templates.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
