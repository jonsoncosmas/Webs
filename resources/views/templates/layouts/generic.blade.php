@php
    $schoolName = $assignment->school?->name ?? 'School';
@endphp

<div class="sheet">
    <div class="hdr">
        <div>
            <div class="school">{{ $schoolName }}</div>
            <div>{{ $assignment->template?->name }} · {{ $assignment->class_label }}</div>
        </div>
        <div class="meta">
            <div><strong>Term:</strong> {{ $assignment->term ?? '—' }}</div>
            <div><strong>Printed:</strong> {{ now()->format('d M Y') }}</div>
        </div>
    </div>

    <p>This template does not ship with a layout yet. Ask System Admin to configure a layout view.</p>

    <div class="sig">
        <div class="slot">Class teacher</div>
        <div class="slot">Academic Head</div>
        <div class="slot">Director</div>
    </div>
</div>
