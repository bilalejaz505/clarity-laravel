@extends('layouts.member')
@section('title', 'Verify your email · Clarity')
@section('section-title', 'Email verification')
@section('content')
    <div class="dashboard-gate">
        <span class="dashboard-gate-icon"><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
        <span class="eyebrow">SECURE YOUR ACCOUNT</span>
        <h1>Verify your email</h1>
        <p>Open the confirmation link in your inbox before using the hosted workspace.</p>
        <div class="toolbar">
            <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="primary"><i
                        class="bi bi-send" aria-hidden="true"></i>Resend verification email</button></form>
            <a href="/account" class="secondary">Back to account</a>
        </div>
    </div>
@endsection
