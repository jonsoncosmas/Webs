{{-- ORION AI widget — users only see this brand, never the underlying provider. --}}
<div class="orion" id="orion-widget">
    <h3><span class="pulse"></span> ORION AI</h3>
    <p class="muted" style="margin:4px 0 10px 0;">Ask anything — ORION will pick the best engine for the task.</p>

    <form class="form" id="orion-form" onsubmit="return orionAsk(event)">
        @csrf
        <div class="field">
            <label for="orion-prompt">Prompt</label>
            <textarea id="orion-prompt" rows="3" placeholder="Summarize the Form 4 math coverage this term..." maxlength="4000"></textarea>
        </div>
        <div class="field">
            <label for="orion-task">Task</label>
            <select id="orion-task">
                <option value="generic">General</option>
                <option value="report">Report</option>
                <option value="explanation">Explanation</option>
                <option value="data_analysis">Data analysis</option>
            </select>
        </div>
        <button class="btn" type="submit" id="orion-submit">Ask ORION</button>
    </form>

    <div class="response" id="orion-response" hidden></div>
</div>

@push('scripts')
<script>
    async function orionAsk(ev) {
        ev.preventDefault();
        const btn = document.getElementById('orion-submit');
        const out = document.getElementById('orion-response');
        const prompt = document.getElementById('orion-prompt').value.trim();
        const task = document.getElementById('orion-task').value;
        if (!prompt) return false;

        btn.disabled = true;
        btn.textContent = 'Thinking…';
        out.hidden = false;
        out.textContent = '…';

        try {
            const res = await fetch("{{ route('orion.ask') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ prompt: prompt, task_type: task }),
            });
            const data = await res.json();
            if (data.ok) {
                out.textContent = data.content || '(no response)';
            } else {
                out.textContent = data.message || 'ORION is unavailable.';
            }
        } catch (e) {
            out.textContent = 'Network error. Try again.';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Ask ORION';
        }
        return false;
    }
</script>
@endpush
