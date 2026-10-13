@props([
    'product' => 'chat',
    'accountActive' => false,
    'showPrivacy' => true,
])

@php
    $workspaceUrl = $product === 'freelance' ? '/contracts' : '/chats';
@endphp

<div class="sidebar-bottom">
    @if ($showPrivacy)
        <a href="{{ $workspaceUrl }}?view=settings" data-view="settings" data-privacy-link class="nav-link">
            <i class="bi bi-shield-check" aria-hidden="true"></i>Privacy & connections
        </a>
    @endif
    <a href="/account?product={{ $product }}" data-account-link
        class="nav-link {{ $accountActive ? 'active' : '' }}">
        <i class="bi bi-person-circle" aria-hidden="true"></i>My account
    </a>
    <a href="/pricing" class="nav-link">
        <i class="bi bi-card-list" aria-hidden="true"></i>Plans
    </a>
    @if (app(\App\Services\EntitlementService::class)->admin(auth()->user()))
        <a href="/admin" class="nav-link">
            <i class="bi bi-speedometer2" aria-hidden="true"></i>Admin panel
        </a>
    @endif
    <a href="/" class="nav-link">
        <i class="bi bi-globe2" aria-hidden="true"></i>Public area
    </a>
</div>
