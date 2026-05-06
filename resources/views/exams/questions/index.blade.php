@extends('layouts.app')

@section('title', 'Questions — ' . $exam->title)

@section('content')
    <div class="card">
        <h1 style="margin:0;">Questions for "{{ $exam->title }}"</h1>
        <p class="muted" style="margin:6px 0 0 0;">
            {{ $exam->subject }} · {{ $exam->total_marks ?? '—' }} marks ·
            <a href="{{ route('exams.show', $exam) }}">Back to exam</a>
        </p>
    </div>

    @if (session('status'))
        <div class="alert" style="margin-top:12px;">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert danger" style="margin-top:12px;">
            <ul style="margin:0; padding-left:16px;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Existing questions</h3>
        @if ($questions->isEmpty())
            <p class="muted" style="margin:0;">No questions yet.</p>
        @else
            <ol style="padding-left:18px; margin:0;">
                @foreach ($questions as $q)
                    <li style="padding:10px 0; border-top:1px solid rgba(15,23,42,0.08);">
                        <div><strong>{{ $q->prompt }}</strong></div>
                        <div class="muted" style="font-size:13px;">
                            {{ strtoupper(str_replace('_', ' ', $q->type)) }} · {{ $q->marks }} marks
                        </div>
                        @if ($q->type === \App\Models\ExamQuestion::TYPE_MCQ)
                            <ul style="margin:6px 0 0 16px;">
                                @foreach ($q->options as $opt)
                                    <li>
                                        {{ $opt->text }}
                                        @if ($opt->is_correct)
                                            <span class="badge" style="background:rgba(22,163,74,0.1); color:#15803d;">correct</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @elseif ($q->type === \App\Models\ExamQuestion::TYPE_TRUE_FALSE)
                            <div class="muted" style="margin-top:4px;">Answer key: <strong>{{ $q->answer_key }}</strong></div>
                        @elseif ($q->answer_key)
                            <div class="muted" style="margin-top:4px;">Reference: {{ $q->answer_key }}</div>
                        @endif

                        @if ($canManage)
                            <form method="POST" action="{{ route('exams.questions.destroy', $q) }}"
                                  onsubmit="return confirm('Delete this question?');"
                                  style="margin-top:6px;">
                                @csrf
                                <button class="btn ghost" type="submit">Delete</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    @if ($canManage)
        <div class="card" style="margin-top:16px;">
            <h3 style="margin-top:0;">Add question</h3>
            <form method="POST" action="{{ route('exams.questions.store', $exam) }}">
                @csrf
                <label>Prompt</label>
                <textarea name="prompt" required minlength="3" maxlength="2000" rows="3">{{ old('prompt') }}</textarea>

                <div class="grid cols-2" style="margin-top:12px;">
                    <div>
                        <label>Type</label>
                        <select name="type" id="qtype">
                            <option value="mcq" {{ old('type') === 'mcq' ? 'selected' : '' }}>Multiple choice</option>
                            <option value="true_false" {{ old('type') === 'true_false' ? 'selected' : '' }}>True / False</option>
                            <option value="short_answer" {{ old('type') === 'short_answer' ? 'selected' : '' }}>Short answer</option>
                        </select>
                    </div>
                    <div>
                        <label>Marks</label>
                        <input name="marks" type="number" min="1" max="100" value="{{ old('marks', 1) }}" required>
                    </div>
                </div>

                <div style="margin-top:12px;">
                    <label>Answer key (reference text; for true/false use <code>true</code> or <code>false</code>)</label>
                    <input name="answer_key" type="text" maxlength="2000" value="{{ old('answer_key') }}">
                </div>

                <fieldset style="margin-top:12px; border:1px solid rgba(15,23,42,0.12); padding:12px; border-radius:8px;">
                    <legend>MCQ options (2–6)</legend>
                    @for ($i = 0; $i < 4; $i++)
                        <div style="display:flex; gap:8px; margin-bottom:6px; align-items:center;">
                            <input name="options[{{ $i }}][text]" type="text" maxlength="500" placeholder="Option {{ $i + 1 }}"
                                   value="{{ old('options.'.$i.'.text') }}" style="flex:1;">
                            <label style="display:flex; align-items:center; gap:4px;">
                                <input name="options[{{ $i }}][is_correct]" type="checkbox" value="1"
                                       {{ old('options.'.$i.'.is_correct') ? 'checked' : '' }}>
                                correct
                            </label>
                        </div>
                    @endfor
                </fieldset>

                <button class="btn" type="submit" style="margin-top:12px;">Add question</button>
            </form>
        </div>
    @endif
@endsection
