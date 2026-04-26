@extends('layouts.app')

@section('title', 'Bus tracking — Somalite')

@section('content')
    <div class="card" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <div>
            <h1 style="margin:0;">Bus tracking</h1>
            <p class="muted" style="margin:4px 0 0 0;">{{ $school->name }} · Elite feature</p>
        </div>
        <div style="margin-left:auto; display:flex; gap:8px;">
            <a class="btn ghost" href="{{ route('bus.map') }}">Live map</a>
        </div>
    </div>

    @if (session('status'))
        <div class="card" style="margin-top:12px; background:rgba(34,197,94,0.08);">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="card" style="margin-top:12px; background:rgba(239,68,68,0.08);">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="grid cols-2" style="margin-top:16px; gap:16px; align-items:start;">
        <div class="card">
            <h3 style="margin-top:0;">New route</h3>
            <form method="POST" action="{{ route('bus.routes.store') }}">
                @csrf
                <label>Name <input name="name" required maxlength="120" style="width:100%; padding:6px;"></label>
                <label>Code (optional) <input name="code" maxlength="32" style="width:100%; padding:6px;"></label>
                <label>Description (optional)
                    <textarea name="description" rows="2" maxlength="500" style="width:100%; padding:6px;"></textarea>
                </label>
                <button class="btn" style="margin-top:8px;">Add route</button>
            </form>
        </div>
        <div class="card">
            <h3 style="margin-top:0;">New vehicle</h3>
            <form method="POST" action="{{ route('bus.vehicles.store') }}">
                @csrf
                <label>Plate number <input name="plate_number" required maxlength="32"
                                            style="width:100%; padding:6px;"></label>
                <label>Label (optional) <input name="label" maxlength="120" style="width:100%; padding:6px;"></label>
                <label>Capacity <input name="capacity" type="number" min="0" max="200" value="0"
                                       style="width:100%; padding:6px;"></label>
                <label>Route
                    <select name="bus_route_id" style="width:100%; padding:6px;">
                        <option value="">— Unassigned —</option>
                        @foreach ($routes as $route)
                            <option value="{{ $route->id }}">{{ $route->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="btn" style="margin-top:8px;">Add vehicle</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Routes</h3>
        @if ($routes->isEmpty())
            <p class="muted" style="margin:0;">No routes yet.</p>
        @else
            <div class="grid" style="gap:12px;">
                @foreach ($routes as $route)
                    <div style="padding:12px; border:1px solid rgba(15,23,42,0.08); border-radius:10px;
                                background:rgba(255,255,255,0.75);">
                        <div style="display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                            <strong>{{ $route->name }}</strong>
                            <span class="muted" style="font-size:12px;">
                                {{ $route->stops->count() }} stops · {{ $route->vehicles->count() }} vehicles
                            </span>
                        </div>
                        @if ($route->description)
                            <p class="muted" style="margin:4px 0 0 0;">{{ $route->description }}</p>
                        @endif

                        <details style="margin-top:8px;">
                            <summary class="muted" style="cursor:pointer;">Add stop</summary>
                            <form method="POST" action="{{ route('bus.stops.store', $route) }}"
                                  style="margin-top:8px; display:grid; gap:6px;">
                                @csrf
                                <input name="name" placeholder="Stop name" required maxlength="120" style="padding:6px;">
                                <div style="display:flex; gap:6px;">
                                    <input name="latitude" placeholder="lat" type="number" step="0.0000001"
                                           style="flex:1; padding:6px;">
                                    <input name="longitude" placeholder="lng" type="number" step="0.0000001"
                                           style="flex:1; padding:6px;">
                                </div>
                                <button class="btn">Add stop</button>
                            </form>
                        </details>

                        @if ($route->stops->isNotEmpty())
                            <ol style="margin:8px 0 0 0;">
                                @foreach ($route->stops as $stop)
                                    <li>
                                        {{ $stop->name }}
                                        @if ($stop->latitude !== null && $stop->longitude !== null)
                                            <span class="muted" style="font-size:12px;">
                                                ({{ $stop->latitude }}, {{ $stop->longitude }})
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Vehicles</h3>
        @if ($vehicles->isEmpty())
            <p class="muted" style="margin:0;">No vehicles yet.</p>
        @else
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left; border-bottom:1px solid rgba(15,23,42,0.08);">
                        <th style="padding:8px 6px;">Plate</th>
                        <th style="padding:8px 6px;">Route</th>
                        <th style="padding:8px 6px;">Capacity</th>
                        <th style="padding:8px 6px;">Last position</th>
                        <th style="padding:8px 6px;">Update position</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vehicles as $v)
                        <tr style="border-bottom:1px solid rgba(15,23,42,0.05);">
                            <td style="padding:10px 6px;">
                                <strong>{{ $v->plate_number }}</strong>
                                @if ($v->label) <div class="muted" style="font-size:12px;">{{ $v->label }}</div> @endif
                            </td>
                            <td style="padding:10px 6px;">{{ $v->route?->name ?? '—' }}</td>
                            <td style="padding:10px 6px;">{{ $v->capacity ?: '—' }}</td>
                            <td style="padding:10px 6px;">
                                @if ($v->hasKnownPosition())
                                    {{ $v->last_latitude }}, {{ $v->last_longitude }}
                                    <div class="muted" style="font-size:12px;">
                                        {{ $v->last_position_at?->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td style="padding:10px 6px;">
                                <form method="POST" action="{{ route('bus.vehicles.position', $v) }}"
                                      style="display:flex; gap:4px;">
                                    @csrf
                                    <input name="latitude" type="number" step="0.0000001" placeholder="lat" required
                                           style="width:90px; padding:4px;">
                                    <input name="longitude" type="number" step="0.0000001" placeholder="lng" required
                                           style="width:90px; padding:4px;">
                                    <button class="btn">Update</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
