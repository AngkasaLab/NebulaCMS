@extends('installer.layout')
@php $currentStep = 5; @endphp

@section('content')
<h1>{{ __('installer.installing_title') }}</h1>
<p class="nsi-main__lead">{{ __('installer.installing_lead') }}</p>

<div class="nsi-progress-bar">
    <div class="nsi-progress-bar__fill" id="progress-bar"></div>
</div>

<div class="nsi-panel">
    @php
        $installSteps = [
            __('installer.install_step_1'),
            __('installer.install_step_2'),
            __('installer.install_step_3'),
            __('installer.install_step_4'),
            __('installer.install_step_5'),
            __('installer.install_step_6'),
        ];
    @endphp
    @foreach($installSteps as $i => $label)
    <div class="nsi-progress-item" id="step-{{ $i }}">
        <span class="nsi-progress-icon" id="step-icon-{{ $i }}">
            <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--ns-border);"></span>
        </span>
        <span id="step-label-{{ $i }}">{{ $label }}</span>
    </div>
    @endforeach
</div>

<div id="error-panel" class="nsi-alert nsi-alert--error" style="display: none;">
    <strong>{{ __('installer.install_failed') }}</strong> <span id="error-message"></span>
    <br><a href="{{ route('installer.account') }}" style="margin-top: 0.5rem; display: inline-block;">{{ __('installer.install_back_retry') }}</a>
</div>
@endsection

@push('scripts')
<script>
const totalSteps = 6;
/** Wizard storage token (disk); does not depend on session cookie after APP_KEY change. */
const installToken = @json($installToken ?? '');
/** Next step index on the server (for resume after page refresh). */
const startStep = {{ min(max((int) ($installCursor ?? 0), 0), 5) }};

function setStepState(index, state) {
    const item = document.getElementById('step-' + index);
    const icon = document.getElementById('step-icon-' + index);

    item.className = 'nsi-progress-item';

    if (state === 'running') {
        item.classList.add('is-running');
        icon.innerHTML = '<span class="nsi-spinner"></span>';
    } else if (state === 'success') {
        item.classList.add('is-done');
        icon.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>';
    } else if (state === 'error') {
        item.classList.add('is-error');
        icon.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>';
    }
}

async function runInstall() {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const bar  = document.getElementById('progress-bar');

    let stepIndex = startStep;

    for (let j = 0; j < startStep; j++) {
        setStepState(j, 'success');
        bar.style.width = Math.round(((j + 1) / totalSteps) * 100) + '%';
    }

    try {
        while (stepIndex < totalSteps) {
            setStepState(stepIndex, 'running');
            bar.style.width = Math.round(((stepIndex + 0.15) / totalSteps) * 100) + '%';

            const res = await fetch('{{ route("installer.run") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: '_token=' + encodeURIComponent(csrf) + '&install_token=' + encodeURIComponent(installToken),
            });

            const data = await res.json().catch(() => ({}));

            if (res.status === 422 && data.error) {
                document.getElementById('error-message').textContent = data.error;
                document.getElementById('error-panel').style.display = 'block';
                setStepState(stepIndex, 'error');
                return;
            }

            if (!data.success) {
                const msg = data.step?.message || @json(__('installer.unexpected_error'));
                document.getElementById('error-message').textContent = msg;
                document.getElementById('error-panel').style.display = 'block';
                for (let j = 0; j < stepIndex; j++) {
                    setStepState(j, 'success');
                }
                setStepState(data.stepIndex ?? stepIndex, 'error');
                return;
            }

            const doneIdx = typeof data.stepIndex === 'number' ? data.stepIndex : stepIndex;
            setStepState(doneIdx, 'success');
            bar.style.width = Math.round(((doneIdx + 1) / totalSteps) * 100) + '%';

            if (data.finished) {
                bar.style.width = '100%';
                setTimeout(() => { window.location.href = '{{ route("installer.done") }}'; }, 800);
                return;
            }

            stepIndex++;
        }
    } catch (err) {
        document.getElementById('error-message').textContent = @json(__('installer.cannot_reach_server')) + err.message;
        document.getElementById('error-panel').style.display = 'block';
        setStepState(stepIndex, 'error');
    }
}

setTimeout(runInstall, 200);
</script>
@endpush
