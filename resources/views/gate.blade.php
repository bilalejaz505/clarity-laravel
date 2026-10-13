@extends('layouts.member')
@section('title', 'Choose your next step · Clarity')
@section('section-title', 'Access')
@section('content')
    <div class="dashboard-gate">
        <span class="dashboard-gate-icon"><i class="bi bi-lock" aria-hidden="true"></i></span>
        <span class="eyebrow">PACKAGE ACCESS</span>
        <h1>Choose your next step.</h1>
        <p>{{ $reason }}</p>
        <div class="toolbar"><a href="/account" class="primary">My account / trial <i class="bi bi-arrow-right"
                    aria-hidden="true"></i></a><a href="/pricing" class="secondary">View plans</a><a href="/demo"
                class="text-btn">Explore sample demo</a></div>
    </div>
@endsection
