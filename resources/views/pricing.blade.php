@extends('layouts.public')
@section('content')
    <main class="public-content">
        <div class="public-heading"><span class="eyebrow">A PLAN FOR YOUR NEXT STEP</span>
            <h1>Pay for useful clarity.</h1>
            <p>Choose one app or get both. Monthly limits keep AI usage predictable.</p>
        </div>
        <div id="public-error" class="error-banner" role="alert" hidden></div>
        <div class="pricing-grid">
            @foreach ($settings['plans'] as $plan)
                @if ($plan['enabled'])
                    <article class="card pricing-card {{ $plan['key'] === 'bundle' ? 'featured' : '' }}">
                        <h2>{{ $plan['name'] }}</h2>
                        <p>{{ $plan['description'] }}</p>
                        <div class="price">{{ $plan['currency'] }} <strong>{{ $plan['price'] }}</strong><small>/
                                month</small></div>
                        <ul>
                            <li>✓ {{ number_format($plan['messages']) }} AI message units per period</li>
                            <li>✓ {{ $plan['requests'] }} AI requests per period</li>
                            <li>✓
                                {{ implode(' + ', array_map(fn($p) => $p === 'chat' ? 'Chat Insights' : 'Freelance Tracker', $plan['products'])) }}
                            </li>
                            <li>✓ Private workspace and exports</li>
                        </ul>
                        @if ($local)
                            <a class="primary full" href="{{ $plan['key'] === 'freelance' ? '/agreements' : '/chats' }}">Use
                            localhost free plan</a>@else<button class="primary full" data-checkout="{{ $plan['key'] }}"
                                @disabled(!$plan['paypalPlanId'] || !$settings['paypalClientId'])>{{ $plan['paypalPlanId'] && $settings['paypalClientId'] ? 'Continue to PayPal' : 'Checkout coming soon' }}</button>
                        @endif
                    </article>
                @endif
            @endforeach
        </div>
        <div class="card trial-card">
            <div>
                <h3>{{ $local ? 'Free development plan · localhost only' : ($settings['trialEnabled'] ? $settings['trialDays'] . '-day trial' : 'Explore the sample demo') }}
                </h3>
                <p>{{ $settings['trialMessages'] }} message units and {{ $settings['trialRequests'] }} requests. One trial
                    per account; no automatic renewal.</p>
            </div><a href="/account" class="secondary">{{ $local ? 'Open your account' : 'Start trial' }} →</a>
        </div>
        <p class="definition">Prices are draft launch settings until merchant plans are configured. Each analysis batch,
            question, or reply counts as one request; supplied messages count toward the allowance. Failed AI requests are
            credited back.</p>
    </main>
@endsection
@section('scripts')
    <script src="/js/public.js" defer></script>
@endsection
