@extends('layouts.public')
@section('content')
    <main class="public-content">
        <div class="public-heading"><span class="eyebrow">A PLAN FOR YOUR NEXT STEP</span>
            <h1>Pay for useful clarity.</h1>
            <p>Choose one app or get both. Monthly limits keep AI usage predictable.</p>
        </div>
        <div class="pricing-grid">
            @foreach ($settings['plans'] as $plan)
                @if ($plan['enabled'])
                    <article class="card pricing-card {{ $plan['key'] === 'bundle' ? 'featured' : '' }}">
                        <h2>{{ $plan['name'] }}</h2>
                        <p>{{ $plan['description'] }}</p>
                        <div class="price">
                            @if ((float) $plan['price'] === 0.0)
                                <strong>Free</strong><small>No payment required</small>
                            @else
                                {{ $plan['currency'] }} <strong>{{ $plan['price'] }}</strong><small>/ month</small>
                            @endif
                        </div>
                        <ul>
                            <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                {{ number_format($plan['messages']) }} AI message units per period</li>
                            <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> {{ $plan['requests'] }} AI
                                requests per period</li>
                            <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                {{ implode(' + ', array_map(fn($p) => $p === 'chat' ? 'Chat Insights' : 'Freelance Tracker', $plan['products'])) }}
                            </li>
                            <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Private workspace and exports
                            </li>
                        </ul>
                        <button class="primary full" data-checkout="{{ $plan['key'] }}"
                            @disabled((float) $plan['price'] > 0 && (!$plan['paypalPlanId'] || !$settings['paypalClientId']))>
                            {{ (float) $plan['price'] === 0.0 ? 'Subscribe free' : ($plan['paypalPlanId'] && $settings['paypalClientId'] ? 'Continue to PayPal' : 'Checkout coming soon') }}
                        </button>
                    </article>
                @endif
            @endforeach
        </div>
        <div class="card trial-card">
            <div>
                <h3>{{ $settings['trialEnabled'] ? $settings['trialDays'] . '-day trial' : 'Explore the sample demo' }}
                </h3>
                <p>{{ $settings['trialMessages'] }} message units and {{ $settings['trialRequests'] }} requests. One trial
                    per account; no automatic renewal.</p>
            </div><a href="/account" class="secondary">Start trial <i
                    class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <p class="definition">Prices are draft launch settings until merchant plans are configured. Each analysis batch,
            question, or reply counts as one request; supplied messages count toward the allowance. Failed AI requests are
            credited back.</p>
    </main>
@endsection
@section('scripts')
    <script src="{{ asset('js/public.js') }}?v={{ filemtime(public_path('js/public.js')) }}" defer></script>
@endsection
