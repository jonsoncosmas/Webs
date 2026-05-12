<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('pdf._styles')
</head>
<body>
@php
    $exam = $attempt->exam;
    $school = $attempt->school;
    $student = $attempt->student;
@endphp

<table class="hdr">
    <tr>
        <td>
            <div class="school">{{ $school?->name ?? 'School' }}</div>
            <div>Exam result card</div>
        </td>
        <td class="right meta">
            <div><strong>Generated:</strong> {{ now()->format('d M Y H:i') }}</div>
            <div><strong>Document ID:</strong> RC-{{ str_pad((string) $attempt->id, 6, '0', STR_PAD_LEFT) }}</div>
        </td>
    </tr>
</table>

<h1 style="margin-bottom:4pt;">{{ $exam?->title ?? 'Exam' }}</h1>
<div class="muted">
    {{ $exam?->subject ?? '—' }}
    @if ($exam?->form_level) · {{ $exam->form_level }} @endif
    @if ($exam?->curriculum) · {{ strtoupper($exam->curriculum) }} @endif
</div>

<div class="pillbox">
    <h3>Student</h3>
    <table style="width:100%;">
        <tr>
            <td style="width:50%;">
                <div class="small muted">Name</div>
                <div><strong>{{ $student?->fullName() ?? '—' }}</strong></div>
            </td>
            <td>
                <div class="small muted">Username</div>
                <div><strong>{{ $student?->username ?? '—' }}</strong></div>
            </td>
        </tr>
    </table>
</div>

<div style="margin-top:14pt;">
    <div class="stat">
        <div class="label">Score</div>
        <div class="value">{{ $attempt->score ?? 0 }} / {{ $attempt->total_marks ?? 0 }}</div>
    </div>
    <div class="stat">
        <div class="label">Percentage</div>
        <div class="value">{{ $attempt->percentage() !== null ? $attempt->percentage() . '%' : '—' }}</div>
    </div>
    <div class="stat">
        <div class="label">Grade</div>
        <div class="value">{{ $attempt->grade ?? '—' }}</div>
    </div>
    <div class="stat">
        <div class="label">Scored on</div>
        <div class="value" style="font-size:11pt;">{{ $attempt->scored_at?->format('d M Y') ?? '—' }}</div>
    </div>
</div>

@if ($attempt->notes)
    <div class="pillbox">
        <h3>Teacher notes</h3>
        <div style="white-space:pre-wrap;">{{ $attempt->notes }}</div>
    </div>
@endif

<table class="sig" style="width:100%;">
    <tr>
        <td>Class teacher</td>
        <td>Academic Head</td>
        <td>Director</td>
    </tr>
</table>

<div class="small muted center" style="margin-top:20pt;">
    Scored by {{ $attempt->scorer?->fullName() ?? '—' }}. This document is a system-generated record;
    its authenticity can be verified by referencing document ID RC-{{ str_pad((string) $attempt->id, 6, '0', STR_PAD_LEFT) }}.
</div>
</body>
</html>
