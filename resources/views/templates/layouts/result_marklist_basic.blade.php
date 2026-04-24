@php
    $rows = collect($assignment->data['rows'] ?? []);
    $subjects = collect($assignment->data['subjects'] ?? []);
    $schoolName = $assignment->school?->name ?? 'School';
    $term = $assignment->term ?? '—';
@endphp

<div class="sheet">
    <div class="hdr">
        <div>
            <div class="school">{{ $schoolName }}</div>
            <div>Result marklist · {{ $assignment->class_label }} @if ($assignment->subject) · {{ $assignment->subject }} @endif</div>
        </div>
        <div class="meta">
            <div><strong>Term:</strong> {{ $term }}</div>
            <div><strong>Printed:</strong> {{ now()->format('d M Y') }}</div>
            <div><strong>Prepared by:</strong> {{ $assignment->assignee?->fullName() ?? $assignment->creator?->fullName() ?? '—' }}</div>
        </div>
    </div>

    @if ($rows->isEmpty())
        <p>This marklist has no rows yet. The responsible teacher can fill them in before printing.</p>
        <table>
            <thead>
                <tr>
                    <th style="width:36pt;">#</th>
                    <th>Student name</th>
                    <th>Adm. No.</th>
                    @if ($subjects->isEmpty())
                        <th>Score</th>
                        <th>Grade</th>
                    @else
                        @foreach ($subjects as $subject)
                            <th>{{ $subject }}</th>
                        @endforeach
                        <th>Avg.</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 10; $i++)
                    <tr>
                        <td>{{ $i }}</td>
                        <td style="height:22pt;"></td>
                        <td></td>
                        @if ($subjects->isEmpty())
                            <td></td>
                            <td></td>
                        @else
                            @foreach ($subjects as $subject)
                                <td></td>
                            @endforeach
                            <td></td>
                        @endif
                    </tr>
                @endfor
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:36pt;">#</th>
                    <th>Student name</th>
                    <th>Adm. No.</th>
                    @if ($subjects->isEmpty())
                        <th>Score</th>
                        <th>Grade</th>
                    @else
                        @foreach ($subjects as $subject)
                            <th>{{ $subject }}</th>
                        @endforeach
                        <th>Avg.</th>
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

    <div class="sig">
        <div class="slot">Class teacher</div>
        <div class="slot">Academic Head</div>
        <div class="slot">Director</div>
    </div>
</div>
