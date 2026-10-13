@props([
    'sectionTitle' => 'Overview',
    'dynamicTitle' => false,
    'showImport' => false,
    'demo' => false,
])

<header class="topbar">
    <div class="breadcrumb">
        <button id="{{ $demo || $showImport ? 'nav-open' : 'member-nav-open' }}" class="mobile-menu icon-only"
            aria-label="Open Workspace Navigation">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <span>{{ app(\App\Services\AppSettingsService::class)->get()['appName'] }} /</span>
        <strong @if ($dynamicTitle) id="view-title" @endif>{{ $sectionTitle }}</strong>
    </div>
    <div class="topbar-right">
        <span class="badge"><i class="bi bi-person" aria-hidden="true"></i>
            {{ $demo ? 'Sample Demo' : auth()->user()->name }}</span>
        @if ($showImport)
            <button class="primary" id="open-import" @disabled($demo)>
                <i class="bi bi-plus-circle" aria-hidden="true"></i>Import Chat
            </button>
        @endif
        @auth
            <form method="POST" action="/logout" data-ajax-form>
                @csrf
                <button class="secondary"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Log Out</button>
            </form>
        @endauth
    </div>
</header>
