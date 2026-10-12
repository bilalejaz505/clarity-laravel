@extends('layouts.public')
@section('content')
    <main class="public-content">
        <section class="public-hero"><span class="badge positive">✧ Two useful apps. One clear workspace.</span>
            <h1>Conversations are messy.<br><em>Bring a little clarity.</em></h1>
            <p>Understand how you communicate. Keep client scope, deadlines, and payments connected to what was actually
                agreed.</p>
            <div class="toolbar"><a href="/pricing" class="primary">Explore plans →</a>
                @if ($settings['demoEnabled'])
                    <a href="/demo" class="secondary">Explore sample demo</a>
                    @endif @if ($local)
                        <a href="/chats" class="secondary">Open localhost free plan</a>
                    @endif
            </div>
            <small
                class="muted">{{ $local ? 'Free development access on localhost. Log in to your workspace.' : 'Paid plans and a limited one-time trial.' }}</small>
        </section>
        <div class="public-products">
            <article class="card"><span class="eyebrow">CHAT INSIGHTS</span>
                <h2>Find the pattern.<br>Keep the perspective.</h2>
                <p>Chat statistics, evidence-backed observations, conversation comparison, and thoughtful reply coaching.
                </p><a href="/chats" class="text-btn">Open Chat Insights →</a>
            </article>
            <article class="card"><span class="eyebrow">FREELANCE AGREEMENTS</span>
                <h2>Less ambiguity.<br>More confident delivery.</h2>
                <p>Review terms, track scope changes, plan milestones, record invoices, and create clear document drafts.
                </p><a href="/agreements" class="text-btn">Open Freelance Tracker →</a>
            </article>
        </div>
        <div class="public-trust">
            <div>
                <h3>Context you can inspect</h3>
                <p>Insights link back to source messages. Review the interpretation and decide what to do next.</p>
            </div>
        </div>
        @if ($settings['notice'])
            <div class="progress-banner">{{ $settings['notice'] }}</div>
        @endif
    </main>
@endsection
