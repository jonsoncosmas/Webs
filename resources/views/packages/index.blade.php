@extends('layouts.app')

@section('title', 'Packages — Somalite')

@section('content')
    <div class="card">
        <h1 style="margin:0;">Packages</h1>
        <p class="muted" style="margin:4px 0 0 0;">Catalog of subscription tiers and the schools assigned to each.</p>
    </div>

    @if (session('status'))
        <div class="card" style="margin-top:12px; background:rgba(34,197,94,0.08);">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid cols-3" style="margin-top:16px; gap:16px;">
        @foreach ($packages as $package)
            <div class="card">
                <h3 style="margin-top:0;">{{ $package->name }}</h3>
                <p class="muted" style="margin:4px 0; font-size:12px;">
                    {{ $package->max_users ? 'Up to '.$package->max_users.' users' : 'Unlimited users' }}
                </p>
                <ul style="margin:8px 0 0 0; padding-left:18px;">
                    @foreach ($package->features ?? [] as $feature => $on)
                        @if ($on && $feature !== 'bus_tracking')
                            <li>{{ str_replace('_', ' ', $feature) }}</li>
                        @endif
                    @endforeach
                    @if ($package->has_bus_tracking)
                        <li><strong>Bus tracking</strong></li>
                    @endif
                </ul>
            </div>
        @endforeach
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Schools</h3>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="text-align:left; border-bottom:1px solid rgba(15,23,42,0.08);">
                    <th style="padding:8px 6px;">School</th>
                    <th style="padding:8px 6px;">Current package</th>
                    <th style="padding:8px 6px;">Reassign</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($schools as $school)
                    <tr style="border-bottom:1px solid rgba(15,23,42,0.05);">
                        <td style="padding:10px 6px;">
                            <strong>{{ $school->name }}</strong>
                            <div class="muted" style="font-size:12px;">{{ $school->slug }}</div>
                        </td>
                        <td style="padding:10px 6px;">
                            <span class="badge">{{ $school->package?->name ?? 'None' }}</span>
                        </td>
                        <td style="padding:10px 6px;">
                            <form method="POST" action="{{ route('packages.assign', $school) }}"
                                  style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                                @csrf
                                <select name="package_id" required style="padding:6px;">
                                    @foreach ($packages as $pkg)
                                        <option value="{{ $pkg->id }}"
                                                @selected($pkg->id === $school->package_id)>{{ $pkg->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn">Assign</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
