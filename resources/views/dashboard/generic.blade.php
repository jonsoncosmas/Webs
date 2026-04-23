@extends('layouts.app')

@section('title', 'Dashboard — Somalite')

@section('content')
    <div class="card">
        <h1>Welcome, {{ $user->first_name }}</h1>
        <p class="muted">Role: <strong>{{ $user->role?->name ?? 'Unassigned' }}</strong></p>
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <div class="card">
            <h3>Quick actions</h3>
            <p class="muted">Role-specific tools will appear here as modules roll out.</p>
        </div>
        @include('partials.orion-widget')
    </div>
@endsection
