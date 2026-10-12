<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Clarity — Conversation Insights & Freelance Agreements')</title>
    <link rel="icon" href="/favicon.svg">
    <link rel="stylesheet" href="/css/clarity.css">
    <link rel="stylesheet" href="{{ asset('css/blade.css') }}?v={{ filemtime(public_path('css/blade.css')) }}">
</head>

<body>
    <div class="public-site">
        <header class="public-header"><a href="/" class="public-brand"><span
                    class="brand-mark">▤</span>{{ strtolower($settings['appName'] ?? config('app.name', 'Clarity')) }}.</a>
            <nav><a href="/demo">Try the demo</a><a href="/pricing">Plans</a>@auth<a href="/account"
                class="secondary">My account ↗</a>@else<a href="/login" class="secondary">Log in ↗</a>@endauth
        </nav>
    </header>
    @if (session('status'))
        <div class="public-content">
            <div class="progress-banner" role="status">{{ session('status') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="public-content">
            <div class="error-banner" role="alert">{{ $errors->first() }}</div>
        </div>
    @endif
    @yield('content')<footer class="public-footer"><span>Clarity · Evidence first. Always.</span><a
            href="/admin">Admin access</a></footer>
</div>@yield('scripts')
</body>

</html>
