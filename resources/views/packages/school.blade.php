@extends('layouts.app')

@section('title', 'Subscription — Somalite')

@section('content')
    <div class="card">
        <h1 style="margin:0;">Subscription</h1>
        <p class="muted" style="margin:4px 0 0 0;">{{ $school->name }}</p>
    </div>

    @if ($school->package)
        <div class="card" style="margin-top:16px;">
            <h2 style="margin-top:0;">{{ $school->package->name }}</h2>
            <p class="muted" style="margin-top:4px;">
                {{ $school->package->max_users ? 'Up to '.$school->package->max_users.' users' : 'Unlimited users' }}
            </p>
            <h3>Active features</h3>
            <ul style="margin:8px 0 0 0; padding-left:18px;">
                @foreach ($school->package->features ?? [] as $feature => $on)
                    @if ($on)
                        <li>{{ ucwords(str_replace('_', ' ', $feature)) }}</li>
                    @endif
                @endforeach
                @if ($school->hasFeature('bus_tracking'))
                    <li><strong>Bus tracking</strong> —
                        <a href="{{ route('bus.index') }}">manage routes &amp; vehicles</a></li>
                @endif
            </ul>
        </div>
    @else
        <div class="card" style="margin-top:16px; background:rgba(234,179,8,0.08);">
            <p style="margin:0;">No package assigned. Contact your System Admin.</p>
        </div>
    @endif
@endsection
