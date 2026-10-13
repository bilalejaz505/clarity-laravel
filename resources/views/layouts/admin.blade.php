<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Clarity administration')</title>
    <link rel="icon" href="/favicon.svg">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="/css/clarity.css">
    <link rel="stylesheet" href="{{ asset('css/blade.css') }}?v={{ filemtime(public_path('css/blade.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/modern.css') }}?v={{ filemtime(public_path('css/modern.css')) }}">
</head>

<body class="admin-app">
    <aside class="sidebar admin-app-sidebar" id="admin-sidebar">
        <a href="/" class="sidebar-brand"><x-brand-mark /> Clarity</a>
        <button class="mobile-nav-close icon-only" id="admin-nav-close" aria-label="Close administration navigation">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
        <span class="eyebrow">ADMINISTRATION</span>
        <nav class="admin-nav" aria-label="Administration sections">
            <button class="nav-link" data-tab="overview"><i class="bi bi-grid" aria-hidden="true"></i>Overview</button>
            <button class="nav-link" data-tab="users"><i class="bi bi-people" aria-hidden="true"></i>Users</button>
            <button class="nav-link" data-tab="ai"><i class="bi bi-stars" aria-hidden="true"></i>OpenAI</button>
            <button class="nav-link" data-tab="paypal"><i class="bi bi-paypal" aria-hidden="true"></i>PayPal &
                plans</button>
            <button class="nav-link" data-tab="general"><i class="bi bi-sliders" aria-hidden="true"></i>Application
                settings</button>
            <button class="nav-link" data-tab="audit"><i class="bi bi-journal-text" aria-hidden="true"></i>Audit
                log</button>
        </nav>
        <div class="sidebar-bottom">
            <a href="/dashboard" class="nav-link"><i class="bi bi-speedometer2" aria-hidden="true"></i>Member
                dashboard</a>
            <a href="/" class="nav-link"><i class="bi bi-globe2" aria-hidden="true"></i>Public area</a>
        </div>
    </aside>
    <div class="main-shell admin-main-shell">
        <header class="topbar">
            <div class="breadcrumb">
                <button id="admin-nav-open" class="mobile-menu icon-only" aria-label="Open administration navigation">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <span>Clarity / Administration /</span><strong id="admin-view-title">Overview</strong>
            </div>
            <div class="topbar-right">
                <span class="badge"><i class="bi bi-shield-check" aria-hidden="true"></i> Super admin</span>
                <a href="/dashboard" class="secondary admin-dashboard-link"><i class="bi bi-window"
                        aria-hidden="true"></i>
                    Member dashboard</a>
            </div>
        </header>
        <main class="main-content admin-app-content">
            @yield('content')
        </main>
        <footer class="main-footer">Clarity administration · Changes are recorded in the audit log.</footer>
    </div>
    <x-feedback />
    @yield('scripts')
</body>

</html>
