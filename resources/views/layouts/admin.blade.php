<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $settings['appName'] . ' Administration')</title>
    <link rel="icon" href="/favicon.svg">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="/css/clarity.css">
    <link rel="stylesheet" href="{{ asset('css/blade.css') }}?v={{ filemtime(public_path('css/blade.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/modern.css') }}?v={{ filemtime(public_path('css/modern.css')) }}">
</head>

<body class="admin-app">
    <aside class="sidebar admin-app-sidebar" id="admin-sidebar">
        <a href="/" class="sidebar-brand"><x-brand-mark /> {{ $settings['appName'] }}</a>
        <button class="mobile-nav-close icon-only" id="admin-nav-close" aria-label="Close administration navigation">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
        <span class="eyebrow">ADMINISTRATION</span>
        <nav class="admin-nav" aria-label="Administration sections">
            <button class="nav-link" data-tab="overview"><i class="bi bi-grid" aria-hidden="true"></i>Overview</button>
            <button class="nav-link" data-tab="users"><i class="bi bi-people" aria-hidden="true"></i>Users</button>
            <button class="nav-link" data-tab="ai"><i class="bi bi-stars" aria-hidden="true"></i>OpenAI</button>
            <button class="nav-link" data-tab="paypal"><i class="bi bi-paypal" aria-hidden="true"></i>PayPal &
                Plans</button>
            <button class="nav-link" data-tab="email"><i class="bi bi-envelope-at" aria-hidden="true"></i>Email &
                SMTP</button>
            <button class="nav-link" data-tab="general"><i class="bi bi-sliders" aria-hidden="true"></i>System &
                Site</button>
            <button class="nav-link" data-tab="audit"><i class="bi bi-journal-text" aria-hidden="true"></i>Audit
                Log</button>
        </nav>
        <div class="sidebar-bottom">
            <a href="/" class="nav-link"><i class="bi bi-globe2" aria-hidden="true"></i>Public Site</a>
        </div>
    </aside>
    <div class="main-shell admin-main-shell">
        <header class="topbar">
            <div class="breadcrumb">
                <button id="admin-nav-open" class="mobile-menu icon-only" aria-label="Open administration navigation">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <span>{{ $settings['appName'] }} / Administration /</span><strong id="admin-view-title">Overview</strong>
            </div>
            <div class="topbar-right">
                <span class="badge"><i class="bi bi-shield-check" aria-hidden="true"></i>Super Admin</span>
                <form method="POST" action="/logout">
                    @csrf
                    <button class="secondary"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Log Out</button>
                </form>
            </div>
        </header>
        <main class="main-content admin-app-content">
            @yield('content')
        </main>
        <footer class="main-footer">{{ $settings['appName'] }} Administration · Changes are recorded in the audit log.</footer>
    </div>
    <x-feedback />
    @yield('scripts')
</body>

</html>
