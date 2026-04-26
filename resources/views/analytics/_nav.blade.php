<div class="card" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
    <strong style="margin-right:8px;">Analytics</strong>
    <a class="btn ghost" href="{{ route('analytics.overview') }}">Overview</a>
    <a class="btn ghost" href="{{ route('analytics.exams') }}">Exams</a>
    <a class="btn ghost" href="{{ route('analytics.discipline') }}">Discipline</a>
    <span class="muted" style="margin-left:auto; font-size:12px;">
        {{ $school->name ?? '—' }}
    </span>
</div>
