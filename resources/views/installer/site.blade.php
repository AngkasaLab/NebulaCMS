@extends('installer.layout')
@php $currentStep = 3; @endphp

@section('content')
<h1>{{ __('installer.site_title') }}</h1>
<p class="nsi-main__lead">{{ __('installer.site_lead') }}</p>

<form action="{{ route('installer.site.save') }}" method="POST">
    @csrf
    <div class="nsi-panel">
        <div class="nsi-field">
            <label for="app_name">{{ __('installer.label_site_name') }}</label>
            <input type="text" id="app_name" name="app_name" value="{{ old('app_name', $data['app_name']) }}" placeholder="NebulaCMS">
            @error('app_name')<p class="nsi-field__error">{{ $message }}</p>@enderror
        </div>
        <div class="nsi-field">
            <label for="app_url">{{ __('installer.label_site_url') }}</label>
            <input type="url" id="app_url" name="app_url" value="{{ old('app_url', $data['app_url']) }}" placeholder="https://example.com">
            @error('app_url')<p class="nsi-field__error">{{ $message }}</p>@enderror
        </div>
        <div class="nsi-field">
            <label>{{ __('installer.label_environment') }}</label>
            <div class="nsi-radio-group">
                <label class="nsi-radio">
                    <input type="radio" name="app_env" value="production" {{ old('app_env', $data['app_env']) === 'production' ? 'checked' : '' }}>
                    <div class="nsi-radio__box">
                        <strong>{{ __('installer.env_production') }}</strong>
                        <span>{{ __('installer.env_production_desc') }}</span>
                    </div>
                </label>
                <label class="nsi-radio">
                    <input type="radio" name="app_env" value="local" {{ old('app_env', $data['app_env']) === 'local' ? 'checked' : '' }}>
                    <div class="nsi-radio__box">
                        <strong>{{ __('installer.env_development') }}</strong>
                        <span>{{ __('installer.env_development_desc') }}</span>
                    </div>
                </label>
            </div>
            @error('app_env')<p class="nsi-field__error">{{ $message }}</p>@enderror
        </div>
        <div class="nsi-field">
            <label for="locale">{{ __('installer.label_default_lang') }}</label>
            <select id="locale" name="locale">
                <option value="id" {{ old('locale', $data['locale']) === 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
                <option value="en" {{ old('locale', $data['locale']) === 'en' ? 'selected' : '' }}>English</option>
            </select>
            @error('locale')<p class="nsi-field__error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="nsi-actions">
        <a href="{{ route('installer.database') }}" class="nsi-btn nsi-btn--ghost">
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
