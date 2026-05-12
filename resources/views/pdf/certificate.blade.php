<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('pdf._styles')
    <style>
        body { font-size: 12pt; }
        .cert-frame { border: 4pt double #0f172a; padding: 28pt 32pt; min-height: 600pt; }
        .cert-title { text-align: center; font-size: 22pt; font-weight: 700; letter-spacing: 4px; margin-bottom: 14pt; }
        .cert-body p { margin: 10pt 0; line-height: 1.7; }
    </style>
</head>
<body>
@php
    $school = $subject->school;
    $profile = $subject->staffProfile;
    $role = $subject->role?->name ?? 'Staff';
    $hireDate = $profile?->hired_on;
    $today = now();
@endphp

<div class="cert-frame">
    <div style="text-align:center; margin-bottom: 18pt;">
        <div class="school" style="font-size:16pt;">{{ $school?->name ?? 'School' }}</div>
        <div class="muted small">Office of Human Resources</div>
    </div>

    <div class="cert-title">CERTIFICATE OF SERVICE</div>

    <div class="cert-body">
        <p>This is to certify that <strong>{{ $subject->fullName() }}</strong>
            @if ($profile?->employee_no)
                (Staff No. {{ $profile->employee_no }})
            @endif
            has been a member of staff at <strong>{{ $school?->name ?? 'this institution' }}</strong>
            in the capacity of <strong>{{ $role }}</strong>
            @if ($hireDate)
                since <strong>{{ $hireDate->format('d F Y') }}</strong>
            @endif.
        </p>

        @if ($profile?->employment_type)
            <p>Employment type: <strong>{{ ucfirst(str_replace('_', ' ', $profile->employment_type)) }}</strong>.</p>
        @endif

        <p>This certificate is issued at the request of the staff member for whatever purpose it may serve.</p>
    </div>

    <table class="sig" style="width:100%; margin-top: 50pt;">
        <tr>
            <td style="width:50%;">
                Issued by<br>
                <strong>{{ $issuer?->fullName() ?? 'HR Department' }}</strong><br>
                <span class="small muted">{{ $issuer?->role?->name ?? 'HR' }}</span>
            </td>
            <td style="width:50%;">Date<br>
                <strong>{{ $today->format('d F Y') }}</strong>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
