@extends('layouts.member')
@section('title', 'Choose Your Next Step · Clarity')
@section('section-title', 'Access')
@section('content')
    <div class="dashboard-gate">
        <span class="dashboard-gate-icon"><i class="bi bi-lock" aria-hidden="true"></i></span>
        <span class="eyebrow">PACKAGE ACCESS</span>
        <h1>Choose Your Next Step</h1>
        <p>{{ $reason }}</p>
        <div class="toolbar"><a href="/account" class="primary">My Account / Trial <i class="bi bi-arrow-right"
                    aria-hidden="true"></i></a><a href="/pricing" class="secondary">View Plans</a><a href="/demo"
                class="text-btn">Explore Sample Demo</a></div>
    </div>
@endsection
