<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Your account · Clarity')</title>
    <link rel="icon" href="/favicon.svg">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="/css/clarity.css">
    <link rel="stylesheet" href="{{ asset('css/blade.css') }}?v={{ filemtime(public_path('css/blade.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/modern.css') }}?v={{ filemtime(public_path('css/modern.css')) }}">
</head>

<body class="member-app">
    @php
        $memberEntitlement = app(\App\Services\EntitlementService::class)->entitlement(
            auth()->user(),
            app(\App\Services\EntitlementService::class)->local(request()),
        );
        $memberProducts = $memberEntitlement['products'];
        $requestedProduct = request()->query('product');
        $memberProduct = in_array($requestedProduct, $memberProducts, true)
            ? $requestedProduct
            : (in_array('chat', $memberProducts, true)
                ? 'chat'
                : 'freelance');
    @endphp
    <aside class="sidebar member-app-sidebar" id="member-sidebar">
        <a href="/" class="sidebar-brand"><x-brand-mark /> Clarity</a>
        <button class="mobile-nav-close icon-only" id="member-nav-close" aria-label="Close workspace navigation">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
        @if ($memberProducts)
            <x-product-navigation :product="$memberProduct" :products="$memberProducts" />
        @endif
        <x-member-utility-navigation :product="$memberProduct" :account-active="request()->is('account', 'email/verify')"
            :show-privacy="(bool) $memberProducts" />
    </aside>
    <div class="main-shell member-main-shell">
        <header class="topbar">
            <div class="breadcrumb">
                <button id="member-nav-open" class="mobile-menu icon-only" aria-label="Open workspace navigation">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <span>Clarity /</span><strong>@yield('section-title', 'Account')</strong>
            </div>
            <div class="topbar-right">
                <span class="badge"><i class="bi bi-person" aria-hidden="true"></i> {{ auth()->user()->name }}</span>
                <form method="POST" action="/logout">@csrf<button class="secondary"><i
                            class="bi bi-box-arrow-right" aria-hidden="true"></i>Log out</button></form>
            </div>
        </header>
        <main class="main-content member-app-content">
            @yield('content')
        </main>
        <footer class="main-footer">Clarity workspace · Your data stays connected to your account.</footer>
    </div>
    <x-feedback />
    <script src="{{ asset('js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}" defer></script>
    @yield('scripts')
</body>

</html>
