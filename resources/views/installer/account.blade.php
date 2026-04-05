@extends('installer.layout')
@php $currentStep = 4; @endphp

@section('content')
<h1>{{ __('installer.account_title') }}</h1>
<p class="nsi-main__lead">{{ __('installer.account_lead') }}</p>

<form action="{{ route('installer.account.save') }}" method="POST">
    @csrf
    <div class="nsi-panel">
        <div class="nsi-field">
            <label for="name">{{ __('installer.label_name') }}</label>
            <input type="text" id="name" name="name" value="{{ old('name', $data['name']) }}" placeholder="Administrator">
            @error('name')<p class="nsi-field__error">{{ $message }}</p>@enderror
        </div>
        <div class="nsi-field">
            <label for="email">{{ __('installer.label_email') }}</label>
            <input type="email" id="email" name="email" value="{{ old('email', $data['email']) }}" placeholder="admin@example.com">
            @error('email')<p class="nsi-field__error">{{ $message }}</p>@enderror
        </div>
        <div class="nsi-field">
            <label for="password">{{ __('installer.label_password') }}</label>
            <input type="password" id="password" name="password" placeholder="{{ __('installer.placeholder_min_chars') }}">
            @error('password')<p class="nsi-field__error">{{ $message }}</p>@enderror
        </div>
        <div class="nsi-field">
            <label for="password_confirmation">{{ __('installer.label_confirm_pass') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="{{ __('installer.placeholder_repeat') }}">
        </div>
    </div>

    <div class="nsi-alert nsi-alert--info">
        {!! __('installer.account_info') !!}
    </div>

    <div class="nsi-actions">
        <a href="{{ route('installer.site') }}" class="nsi-btn nsi-btn--ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
            {{ __('installer.btn_back') }}
        </a>
        <button type="submit" class="nsi-btn nsi-btn--primary">
            {{ __('installer.btn_start_install') }}
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
</form>
@endsection
