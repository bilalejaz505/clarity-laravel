@extends('layouts.member')
@section('title', 'Your account · Clarity')
@section('section-title', 'My account')
@section('content')
    <div class="page-heading dashboard-page-heading"><span class="eyebrow">ACCOUNT CENTER</span>
        <h1>Your account</h1>
        <p>Manage access, usage, and subscriptions.</p>
    </div>
    <div class="account-content">
        @if (!auth()->user()->hasVerifiedEmail())
            <div class="progress-banner">Verify your email for hosted access. <a href="/email/verify">Send a verification
                    email</a></div>
        @endif
        <div class="card detail-card">
            <div class="flex-between">
                <div>
                    <h2>{{ auth()->user()->name }}</h2>
                    <p class="muted">{{ auth()->user()->email }}</p>
                </div><span id="account-plan" class="badge"></span>
            </div>
            <p id="account-reason"></p>
            <div id="account-usage" class="metrics"></div>
            <div id="account-actions" class="toolbar"></div>
        </div>
        <div id="subscriptions"></div>
    </div>
@endsection
@section('scripts')
    <script src="{{ asset('js/public.js') }}?v={{ filemtime(public_path('js/public.js')) }}" defer></script>
@endsection
