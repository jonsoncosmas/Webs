@extends('layouts.app')

@section('title', 'Bus map — Somalite')

@section('content')
    <div class="card">
        <h1 style="margin:0;">Bus map</h1>
        <p class="muted" style="margin:4px 0 0 0;">{{ $school->name }} · last-known positions</p>
    </div>

    @if (! $apiKey)
        <div class="card" style="margin-top:12px; background:rgba(234,179,8,0.08);">
            <strong>Google Maps API key not configured.</strong>
            <p class="muted" style="margin:4px 0 0 0;">
                Add <code>GOOGLE_MAPS_API_KEY</code> to your <code>.env</code> to render the live map.
                The vehicle list below works without it.
            </p>
        </div>
    @endif

    @if ($vehicles->isEmpty())
        <div class="card" style="margin-top:12px;">
            <p class="muted" style="margin:0;">No active vehicles to plot.</p>
        </div>
    @else
        @if ($apiKey)
            <div id="bus-map" style="width:100%; height:480px; margin-top:12px; border-radius:12px;
                                     border:1px solid rgba(15,23,42,0.08);"></div>
            <script>
                window.somaliteBuses = @json($vehicles->map(fn ($v) => [
                    'plate' => $v->plate_number,
                    'label' => $v->label,
                    'route' => $v->route?->name,
                    'lat' => $v->last_latitude,
                    'lng' => $v->last_longitude,
                ])->values());
                function initSomaliteMap() {
                    var withPositions = window.somaliteBuses.filter(function (b) { return b.lat && b.lng; });
                    var center = withPositions.length
                        ? { lat: parseFloat(withPositions[0].lat), lng: parseFloat(withPositions[0].lng) }
                        : { lat: -6.7924, lng: 39.2083 }; // Dar es Salaam fallback
                    var map = new google.maps.Map(document.getElementById('bus-map'), {
                        center: center, zoom: 12,
                    });
                    withPositions.forEach(function (b) {
                        var m = new google.maps.Marker({
                            position: { lat: parseFloat(b.lat), lng: parseFloat(b.lng) },
                            map: map,
                            title: b.plate + (b.label ? ' — ' + b.label : ''),
                        });
                        var info = new google.maps.InfoWindow({
                            content: '<strong>' + b.plate + '</strong>'
                                + (b.route ? '<br>Route: ' + b.route : '')
                                + (b.label ? '<br>' + b.label : ''),
                        });
                        m.addListener('click', function () { info.open(map, m); });
                    });
                }
            </script>
            <script defer
                    src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($apiKey) }}&callback=initSomaliteMap"></script>
        @endif

        <div class="card" style="margin-top:12px;">
            <h3 style="margin-top:0;">Vehicles</h3>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left; border-bottom:1px solid rgba(15,23,42,0.08);">
                        <th style="padding:8px 6px;">Plate</th>
                        <th style="padding:8px 6px;">Route</th>
                        <th style="padding:8px 6px;">Position</th>
                        <th style="padding:8px 6px;">Last seen</th>
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
                            <td style="padding:10px 6px;">
                                @if ($v->hasKnownPosition())
                                    {{ $v->last_latitude }}, {{ $v->last_longitude }}
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td style="padding:10px 6px;">
                                {{ $v->last_position_at?->diffForHumans() ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
