@extends('installer.layout')
@php $currentStep = 6; @endphp

@section('content')
<div class="nsi-done">
    <div class="nsi-done__icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>

    <h1 style="margin-bottom: 0.5rem;">{{ __('installer.done_title') }}</h1>
    <p class="nsi-main__lead" style="margin-bottom: 0;">{{ __('installer.done_lead') }}</p>
</div>

<div class="nsi-panel" style="margin-top: 2rem;">
    <div class="nsi-row"><span class="nsi-row__label"><span class="nsi-dot nsi-dot--ok"></span> {{ __('installer.done_config_written') }}</span></div>
    <div class="nsi-row"><span class="nsi-row__label"><span class="nsi-dot nsi-dot--ok"></span> {{ __('installer.done_migration') }}</span></div>
    <div class="nsi-row"><span class="nsi-row__label"><span class="nsi-dot nsi-dot--ok"></span> {{ __('installer.done_seeded') }}</span></div>
    <div class="nsi-row"><span class="nsi-row__label"><span class="nsi-dot nsi-dot--ok"></span> {{ __('installer.done_admin_created') }}</span></div>
</div>

<div class="nsi-alert nsi-alert--info">
    {{ __('installer.done_locked') }}
</div>

<div class="nsi-done__actions">
    <a href="/admin" class="nsi-btn nsi-btn--primary">{{ __('installer.btn_go_admin') }}</a>
    <a href="/" class="nsi-btn nsi-btn--outline">{{ __('installer.btn_view_site') }}</a>
</div>
@endsection
