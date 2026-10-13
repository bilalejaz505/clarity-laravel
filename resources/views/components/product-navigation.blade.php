@props([
    'product' => 'chat',
    'products' => ['chat', 'freelance'],
    'demo' => false,
])

@php
    $chatNavigation = [
        'overview' => ['Overview', 'grid'],
        'library' => ['Conversations', 'chat-left-text'],
        'insights' => ['Conversation insights', 'lightbulb'],
        'stats' => ['Chat statistics', 'bar-chart'],
        'explorer' => ['Message explorer', 'search'],
        'compare' => ['Compare conversations', 'columns-gap'],
        'ask' => ['Ask your chat', 'stars'],
        'coach' => ['Reply coach', 'chat-heart'],
    ];
    $freelanceNavigation = [
        'overview' => ['Overview', 'grid'],
        'library' => ['Client conversations', 'chat-square-text'],
        'contracts' => ['Contract tracker', 'file-earmark-text'],
        'scope' => ['Scope & changes', 'list-check'],
        'tasks' => ['Tasks & milestones', 'check2-square'],
        'payments' => ['Invoices & payments', 'receipt'],
        'documents' => ['Document studio', 'file-earmark-richtext'],
        'ask' => ['Ask your project', 'stars'],
    ];
    $navigation = $product === 'freelance' ? $freelanceNavigation : $chatNavigation;
    $baseUrl = $demo ? "/demo?product={$product}&view=" : ($product === 'freelance' ? '/contracts?view=' : '/chats?view=');
@endphp

<div class="product-switch">
    @if (in_array('chat', $products, true))
        <a data-switch-product="chat" href="{{ $demo ? '/demo?product=chat' : '/chats' }}"
            class="{{ $product === 'chat' ? 'active' : '' }}"><i class="bi bi-chat-dots"
                aria-hidden="true"></i>Chat Insights</a>
    @endif
    @if (in_array('freelance', $products, true))
        <a data-switch-product="freelance" href="{{ $demo ? '/demo?product=freelance' : '/contracts' }}"
            class="{{ $product === 'freelance' ? 'active' : '' }}"><i class="bi bi-briefcase"
                aria-hidden="true"></i>Freelance Tracker</a>
    @endif
</div>

<span class="eyebrow">{{ $product === 'chat' ? 'YOUR CONVERSATIONS' : 'YOUR CLIENT WORK' }}</span>
<nav data-product-nav="{{ $product }}" aria-label="{{ $product === 'chat' ? 'Chat Insights' : 'Freelance Tracker' }}">
    @foreach ($navigation as $view => [$label, $icon])
        <a href="{{ $baseUrl }}{{ $view }}" data-view="{{ $view }}" class="nav-link">
            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>{{ $label }}
        </a>
    @endforeach
</nav>
