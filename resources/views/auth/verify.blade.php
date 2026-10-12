@extends('layouts.public')
@section('content')
    <main class="public-content gate-content">
        <h1>Verify your email</h1>
        <p>Open the confirmation link in your inbox before using the hosted workspace.</p>
        <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="primary">Resend verification
                email</button></form><a href="/account">Back to account</a>
    </main>
@endsection
