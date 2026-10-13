@extends('layouts.member')
@section('title', 'Verify your email · Clarity')
@section('section-title', 'Email Verification')
@section('content')
    <div class="dashboard-gate">
        <span class="dashboard-gate-icon"><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
        <span class="eyebrow">SECURE YOUR ACCOUNT</span>
        <h1>Verify Your Email</h1>
        <p>Open the confirmation link in your inbox before using the hosted workspace.</p>
        <div class="toolbar">
            <form method="POST" action="{{ route('verification.send') }}" data-ajax-form>@csrf<button class="primary"><i
                        class="bi bi-send" aria-hidden="true"></i>Resend Verification Email</button></form>
            <a href="/account" class="secondary">Back to Account</a>
        </div>
    </div>
@endsection
