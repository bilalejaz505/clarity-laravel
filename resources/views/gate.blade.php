@extends('layouts.public')
@section('content')
    <main class="public-content gate-content">
        <h1>Choose your next step.</h1>
        <p>{{ $reason }}</p>
        <div class="toolbar"><a href="/account" class="primary">My account / trial →</a><a href="/pricing" class="secondary">View
                paid plans</a><a href="/demo">Explore sample demo</a></div>
    </main>
@endsection
