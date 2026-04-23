@extends('layouts.app')

@section('title', 'System Admin — Somalite')

@section('content')
    <div class="card">
        <h1>System Command Center</h1>
        <p class="muted">Global control over schools, packages, AI routing and platform health.</p>
    </div>

    <div class="grid cols-4" style="margin-top:16px;">
        <div class="stat">
            <div class="label">Schools</div>
            <div class="value">{{ \App\Models\School::count() }}</div>
            <div class="hint">Active: {{ \App\Models\School::where('status', 'active')->count() }}</div>
        </div>
        <div class="stat">
            <div class="label">Users</div>
            <div class="value">{{ \App\Models\User::count() }}</div>
            <div class="hint">Across all schools</div>
        </div>
        <div class="stat">
            <div class="label">Packages</div>
            <div class="value">{{ \App\Models\Package::count() }}</div>
            <div class="hint">Basic / Pro / Elite</div>
        </div>
        <div class="stat">
            <div class="label">ORION requests (24h)</div>
            <div class="value">{{ \App\Models\AiRequest::where('created_at', '>=', now()->subDay())->count() }}</div>
            <div class="hint">Auto-routed</div>
        </div>
    </div>

    <div class="grid cols-2" style="margin-top:16px;">
        <div class="card">
            <h3>ORION routing</h3>
            <p class="muted">Only you see the underlying providers.</p>
            <ul style="line-height:1.9; padding-left:18px;">
                <li><strong>Reports</strong> → Claude</li>
                <li><strong>Explanations</strong> → ChatGPT (OpenAI)</li>
                <li><strong>Data analysis</strong> → Gemini</li>
                <li><strong>Fallback</strong> → DeepSeek</li>
            </ul>
        </div>
        @include('partials.orion-widget')
    </div>
@endsection
