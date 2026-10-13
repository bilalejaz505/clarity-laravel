<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $settings['maintenanceTitle'] }} · {{ $settings['appName'] }}</title>
    <link rel="icon" href="/favicon.svg">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/blade.css') }}?v={{ filemtime(public_path('css/blade.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/modern.css') }}?v={{ filemtime(public_path('css/modern.css')) }}">
</head>

<body class="maintenance-page">
    <main class="maintenance-card card">
        <x-brand-mark />
        <span class="eyebrow">{{ strtoupper($settings['appName']) }}</span>
        <h1>{{ $settings['maintenanceTitle'] }}</h1>
        <p>{{ $settings['maintenanceMessage'] }}</p>
        @if ($settings['supportEmail'])
            <a href="mailto:{{ $settings['supportEmail'] }}" class="secondary"><i class="bi bi-envelope"
                    aria-hidden="true"></i>Contact Support</a>
        @endif
        <a href="{{ route('admin.login') }}" class="text-btn">Administrator Sign In</a>
    </main>
</body>

</html>
