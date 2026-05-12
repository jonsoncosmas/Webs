<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('pdf._styles')
</head>
<body>
@php
    $rows = collect($assignment->data['rows'] ?? []);
    $subjects = collect($assignment->data['subjects'] ?? []);
    $schoolName = $assignment->school?->name ?? 'School';
    $kindLabel = $assignment->template?->kindLabel() ?? 'Marklist';
@endphp

<table class="hdr">
    <tr>
        <td>
            <div class="school">{{ $schoolName }}</div>
            <div>{{ $kindLabel }} · {{ $assignment->class_label }}@if ($assignment->subject) · {{ $assignment->subject }}@endif</div>
        </td>
        <td class="right meta">
            <div><strong>Term:</strong> {{ $assignment->term ?? '—' }}</div>
            <div><strong>Generated:</strong> {{ now()->format('d M Y H:i') }}</div>
            <div><strong>Prepared by:</strong> {{ $assignment->assignee?->fullName() ?? $assignment->creator?->fullName() ?? '—' }}</div>
        </td>
    </tr>
</table>

@if ($rows->isEmpty())
    <p class="muted">This marklist has no rows yet — the responsible teacher hasn't filled in scores.</p>
@else
    <table class="data">
        <thead>
            <tr>
                <th style="width:30pt;">#</th>
                <th>Student name</th>
                <th style="width:80pt;">Adm. No.</th>
                @if ($subjects->isEmpty())
                    <th style="width:60pt;">Score</th>
                    <th style="width:50pt;">Grade</th>
                @else
                    @foreach ($subjects as $subject)
                        <th>{{ $subject }}</th>
                    @endforeach
                    <th style="width:50pt;">Avg.</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['name'] ?? '' }}</td>
                    <td>{{ $row['adm_no'] ?? '' }}</td>
                    @if ($subjects->isEmpty())
                        <td>{{ $row['score'] ?? '' }}</td>
                        <td>{{ $row['grade'] ?? '' }}</td>
                    @else
                        @foreach ($subjects as $subject)
                            <td>{{ $row['scores'][$subject] ?? '' }}</td>
                        @endforeach
                        <td>{{ $row['average'] ?? '' }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<table class="sig" style="width:100%;">
    <tr>
        <td>Class teacher</td>
        <td>Academic Head</td>
        <td>Director</td>
    </tr>
</table>
</body>
</html>
