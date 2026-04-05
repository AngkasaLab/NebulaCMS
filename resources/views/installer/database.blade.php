@extends('installer.layout')
@php $currentStep = 2; @endphp

@section('content')
<h1>{{ __('installer.database_title') }}</h1>
<p class="nsi-main__lead">{{ __('installer.database_lead') }}</p>

@if($errors->has('connection'))
<div class="nsi-alert nsi-alert--error">
    <strong>{{ __('installer.connection_failed') }}</strong> {{ $errors->first('connection') }}
</div>
@endif

<form action="{{ route('installer.database.save') }}" method="POST" id="db-form">
    @csrf
    <div class="nsi-panel">
        <div class="nsi-field-row">
            <div class="nsi-field">
                <label for="host">{{ __('installer.label_host') }}</label>
                <input type="text" id="host" name="host" value="{{ old('host', $data['host']) }}" placeholder="127.0.0.1">
                @error('host')<p class="nsi-field__error">{{ $message }}</p>@enderror
            </div>
            <div class="nsi-field">
                <label for="port">{{ __('installer.label_port') }}</label>
                <input type="number" id="port" name="port" value="{{ old('port', $data['port']) }}" placeholder="3306">
                @error('port')<p class="nsi-field__error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="nsi-field">
            <label for="database">{{ __('installer.label_database_name') }}</label>
            <input type="text" id="database" name="database" value="{{ old('database', $data['database']) }}" placeholder="nebula_cms">
            @error('database')<p class="nsi-field__error">{{ $message }}</p>@enderror
        </div>
        <div class="nsi-field">
            <label for="username">{{ __('installer.label_username') }}</label>
            <input type="text" id="username" name="username" value="{{ old('username', $data['username']) }}" placeholder="root">
            @error('username')<p class="nsi-field__error">{{ $message }}</p>@enderror
        </div>
        <div class="nsi-field">
            <label for="password">{{ __('installer.label_password') }} <span class="nsi-optional">{{ __('installer.password_optional') }}</span></label>
            <input type="password" id="password" name="password" value="{{ old('password', $data['password']) }}" placeholder="••••••••">
        </div>
    </div>

    <div style="margin-bottom: 1rem;">
        <button type="button" id="btn-test" class="nsi-btn nsi-btn--outline nsi-btn--sm">{{ __('installer.btn_test_connection') }}</button>
        <span id="test-result" style="margin-left: 0.5rem; font-size: 0.8125rem; display: none;"></span>
    </div>

    <div class="nsi-actions">
        <a href="{{ route('installer.welcome') }}" class="nsi-btn nsi-btn--ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
            {{ __('installer.btn_back') }}
        </a>
        <button type="submit" class="nsi-btn nsi-btn--primary">
            {{ __('installer.btn_continue') }}
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.getElementById('btn-test').addEventListener('click', async function () {
    const btn    = this;
    const result = document.getElementById('test-result');
    const form   = document.getElementById('db-form');

    btn.disabled = true;
    result.style.display = 'inline';
    result.style.color = 'var(--ns-muted)';
    result.textContent = @json(__('installer.testing'));

    const body = new URLSearchParams({
        _token:   document.querySelector('meta[name="csrf-token"]').content,
        host:     form.host.value,
        port:     form.port.value,
        database: form.database.value,
        username: form.username.value,
        password: form.password.value,
    });

    try {
        const res  = await fetch('{{ route("installer.database.test") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
        });
        const data = await res.json();

        if (data.success) {
            result.style.color = '#16a34a';
            result.textContent = @json(__('installer.connection_success'));
        } else {
            result.style.color = '#dc2626';
            result.textContent = data.message;
        }
    } catch (e) {
        result.style.color = '#dc2626';
        result.textContent = @json(__('installer.server_error'));
    }

    btn.disabled = false;
});
</script>
@endpush
