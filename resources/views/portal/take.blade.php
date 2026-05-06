@extends('layouts.app')

@section('title', 'Take — ' . $exam->title)

@section('content')
    <div class="card">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <h1 style="margin:0;">{{ $exam->title }}</h1>
                <p class="muted" style="margin:6px 0 0 0;">
                    {{ $exam->subject }}
                    @if ($exam->form_level) · {{ $exam->form_level }} @endif
                    @if ($exam->total_marks) · {{ $exam->total_marks }} marks @endif
                </p>
            </div>
            @if ($attempt->deadline_at)
                <div class="stat" style="min-width:180px;">
                    <div class="label">Time remaining</div>
                    <div class="value" id="take-timer" data-deadline="{{ $attempt->deadline_at->toIso8601String() }}">—</div>
                </div>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="alert" style="margin-top:12px;">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('portal.exams.submit', $exam) }}" id="take-form">
        @csrf
        @foreach ($exam->questions as $i => $q)
            @php $prior = $answers->get($q->id); @endphp
            <div class="card" style="margin-top:16px;">
                <div style="display:flex; justify-content:space-between; gap:8px;">
                    <strong>Q{{ $i + 1 }}. {{ $q->prompt }}</strong>
                    <span class="muted">{{ $q->marks }} marks</span>
                </div>

                @if ($q->type === \App\Models\ExamQuestion::TYPE_MCQ)
                    <div style="margin-top:8px;">
                        @foreach ($q->options as $opt)
                            <label style="display:block; padding:6px 0;">
                                <input type="radio"
                                       name="answers[{{ $q->id }}][selected_option_id]"
                                       value="{{ $opt->id }}"
                                       {{ $prior && $prior->selected_option_id === $opt->id ? 'checked' : '' }}>
                                {{ $opt->text }}
                            </label>
                        @endforeach
                    </div>
                @elseif ($q->type === \App\Models\ExamQuestion::TYPE_TRUE_FALSE)
                    <div style="margin-top:8px;">
                        @foreach (['true' => 'True', 'false' => 'False'] as $val => $label)
                            <label style="display:inline-block; margin-right:16px; padding:6px 0;">
                                <input type="radio"
                                       name="answers[{{ $q->id }}][answer_text]"
                                       value="{{ $val }}"
                                       {{ $prior && strtolower((string) $prior->answer_text) === $val ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                @else
                    <textarea name="answers[{{ $q->id }}][answer_text]" rows="3"
                              style="margin-top:8px;" maxlength="4000">{{ $prior?->answer_text }}</textarea>
                @endif
            </div>
        @endforeach

        <div class="card" style="margin-top:16px;">
            <button class="btn" type="submit"
                    onclick="return confirm('Submit this exam? You won\'t be able to change your answers.');">
                Submit exam
            </button>
            <a class="btn ghost" href="{{ route('portal.exams') }}" style="margin-left:8px;">Cancel</a>
        </div>
    </form>

    @if ($attempt->deadline_at)
        <script>
            (function () {
                var el = document.getElementById('take-timer');
                var form = document.getElementById('take-form');
                if (!el || !form) return;
                var deadline = new Date(el.getAttribute('data-deadline')).getTime();
                var autoSubmitted = false;

                function pad(n) { return n < 10 ? '0' + n : '' + n; }

                function tick() {
                    var diff = Math.floor((deadline - Date.now()) / 1000);
                    if (diff <= 0) {
                        el.textContent = '00:00';
                        if (!autoSubmitted) {
                            autoSubmitted = true;
                            form.submit();
                        }
                        return;
                    }
                    var m = Math.floor(diff / 60);
                    var s = diff % 60;
                    el.textContent = pad(m) + ':' + pad(s);
                }

                tick();
                setInterval(tick, 1000);
            })();
        </script>
    @endif
@endsection
