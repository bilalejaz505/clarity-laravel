<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Clarity — Conversation Insights & Freelance Contracts')</title>
    <link rel="icon" href="/favicon.svg">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="/css/clarity.css">
    <link rel="stylesheet" href="{{ asset('css/blade.css') }}?v={{ filemtime(public_path('css/blade.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/modern.css') }}?v={{ filemtime(public_path('css/modern.css')) }}">
</head>

<body>
    <div class="public-site">
        <header class="public-header">
            <a href="/" class="public-brand">
                <x-brand-mark />
                {{ $settings['appName'] ?? config('app.name', 'Clarity') }}
            </a>
            <nav>
                <a href="/demo">Try the Demo</a>
                <a href="/pricing">Plans</a>
                @auth
                    <a href="/dashboard" class="secondary"><i class="bi bi-speedometer2" aria-hidden="true"></i>
                        Dashboard</a>
                @else
                    <a href="/login" class="secondary"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                        Log In</a>
                @endauth
            </nav>
        </header>
        @yield('content')
        <footer class="public-footer">
            <span>{{ $settings['appName'] }} <span class="footer-separator">/</span>
                {{ $settings['siteTagline'] }}</span>
            <a href="{{ route('admin.login') }}"><i class="bi bi-shield-lock" aria-hidden="true"></i> Admin Access</a>
        </footer>
    </div>
    <x-feedback />
    @yield('scripts')
</body>

</html>
