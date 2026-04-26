@php
    /** @var array<string,int>|\Illuminate\Support\Collection $series */
    $series = $series ?? [];
    if ($series instanceof \Illuminate\Support\Collection) {
        $series = $series->all();
    }
    $max = !empty($series) ? max(array_map('intval', $series)) : 0;
@endphp

@if (empty($series))
    <p class="muted" style="margin:0;">No data yet.</p>
@else
    <div class="grid" style="gap:6px;">
        @foreach ($series as $label => $value)
            @php $pct = $max > 0 ? round(($value / $max) * 100) : 0; @endphp
            <div style="display:grid; grid-template-columns:140px 1fr 56px; gap:8px; align-items:center;">
                <div class="muted" style="font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ $label }}
                </div>
                <div style="height:14px; border-radius:8px; background:rgba(15,23,42,0.06); overflow:hidden;">
                    <div style="width:{{ $pct }}%; height:100%; background:linear-gradient(135deg, #3b82f6, #6366f1);"></div>
                </div>
                <div style="text-align:right; font-weight:600;">{{ $value }}</div>
            </div>
        @endforeach
    </div>
@endif
