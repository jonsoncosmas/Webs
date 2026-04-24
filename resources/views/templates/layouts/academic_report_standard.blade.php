@php
    $student = $assignment->data['student'] ?? [];
    $subjects = collect($assignment->data['subjects'] ?? []);
    $comments = $assignment->data['comments'] ?? [];
    $schoolName = $assignment->school?->name ?? 'School';
@endphp

<div class="sheet">
    <div class="hdr">
        <div>
            <div class="school">{{ $schoolName }}</div>
            <div>Academic report card · {{ $assignment->class_label }}</div>
        </div>
        <div class="meta">
            <div><strong>Term:</strong> {{ $assignment->term ?? '—' }}</div>
            <div><strong>Printed:</strong> {{ now()->format('d M Y') }}</div>
            <div><strong>Prepared by:</strong> {{ $assignment->assignee?->fullName() ?? $assignment->creator?->fullName() ?? '—' }}</div>
        </div>
    </div>

    <table style="margin-bottom:8pt;">
        <tbody>
            <tr>
                <th style="width:90pt;">Student</th>
                <td>{{ $student['name'] ?? '' }}</td>
                <th style="width:90pt;">Adm. No.</th>
                <td style="width:140pt;">{{ $student['adm_no'] ?? '' }}</td>
            </tr>
            <tr>
                <th>Stream</th>
                <td>{{ $student['stream'] ?? $assignment->class_label }}</td>
                <th>Position</th>
                <td>{{ $student['position'] ?? '' }}</td>
            </tr>
        </tbody>
    </table>

    <h3 style="margin-top:10pt;">Subject performance</h3>
    @if ($subjects->isEmpty())
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th style="width:70pt;">Score</th>
                    <th style="width:50pt;">Grade</th>
                    <th>Teacher comment</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < 8; $i++)
                    <tr>
                        <td style="height:22pt;"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th style="width:70pt;">Score</th>
                    <th style="width:50pt;">Grade</th>
                    <th>Teacher comment</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subjects as $subject)
                    <tr>
                        <td>{{ $subject['name'] ?? '' }}</td>
                        <td>{{ $subject['score'] ?? '' }}</td>
                        <td>{{ $subject['grade'] ?? '' }}</td>
                        <td>{{ $subject['comment'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div style="margin-top:16pt;">
        <h3>Class teacher's comment</h3>
        <p style="min-height:40pt; border:1px solid #cbd5e1; padding:6pt;">{{ $comments['class_teacher'] ?? '' }}</p>
    </div>
    <div style="margin-top:8pt;">
        <h3>Head teacher's comment</h3>
        <p style="min-height:40pt; border:1px solid #cbd5e1; padding:6pt;">{{ $comments['head_teacher'] ?? '' }}</p>
    </div>

    <div class="sig">
        <div class="slot">Class teacher</div>
        <div class="slot">Academic Head</div>
        <div class="slot">Director</div>
    </div>
</div>
