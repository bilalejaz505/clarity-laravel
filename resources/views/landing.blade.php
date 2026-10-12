@extends('layouts.public')
@section('content')
    <main class="public-content landing-content">
        <section class="landing-hero">
            <div class="hero-copy">
                <span class="hero-kicker"><span class="status-dot"></span> YOUR NEXT STEP, MADE CLEAR</span>
                <h1>Less guesswork.<br><span>More clarity.</span></h1>
                <p>Turn conversations into perspective. Turn client agreements into a plan. Two thoughtful tools, one
                    organized workspace.</p>
                <div class="toolbar">
                    <a href="/register" class="primary">Create your workspace <span aria-hidden="true">↗</span></a>
                    @if ($settings['demoEnabled'])
                        <a href="/demo" class="secondary">Explore the demo</a>
                    @endif
                </div>
                <div class="hero-proof"><span>✓ Evidence-linked insights</span><span>✓ Your data, your control</span></div>
            </div>
            <div class="hero-preview" aria-label="Preview of conversation insights and project tracking">
                <div class="preview-top"><span class="preview-icon">↗</span><span>Workspace overview</span><span
                        class="preview-badge">ALL IN ONE PLACE</span></div>
                <div class="preview-metrics">
                    <div><span>Messages reviewed</span><strong>248<span>↗</span></strong></div>
                    <div><span>Next milestone</span><strong>Oct 20</strong></div>
                </div>
                <div class="preview-chart">
                    <div class="flex-between"><strong>Conversation activity</strong><span>This week</span></div>
                    <div class="preview-bars"><i style="--bar:35%"></i><i style="--bar:63%"></i><i style="--bar:46%"></i><i
                            style="--bar:85%"></i><i style="--bar:58%"></i><i style="--bar:100%"></i><i
                            style="--bar:72%"></i></div>
                    <div class="preview-days">
                        <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                    </div>
                </div>
                <div class="preview-agreement"><span class="preview-check">✓</span>
                    <div><strong>Scope confirmed</strong><span>Four pages · Two revision rounds</span></div><span
                        class="badge positive">Reviewed</span>
                </div>
                <div class="preview-foot">Illustrative preview · Explore the interactive demo</div>
            </div>
        </section>
        <section class="landing-products">
            <div class="section-intro"><span class="eyebrow">ONE WORKSPACE. TWO SUPERPOWERS.</span>
                <h2>From conversation<br>to a confident next step.</h2>
                <p>Keep the context. Make the decision.</p>
            </div>
            <div class="public-products">
                <article class="card product-card"><span class="product-icon">↗</span><span class="eyebrow">CHAT
                        INSIGHTS</span>
                    <h2>Understand the conversation.</h2>
                    <p>Explore communication patterns, ask questions about your chats, and see the messages behind every
                        observation.</p>
                    <div class="feature-tags"><span>Chat statistics</span><span>Reply coaching</span><span>Evidence
                            explorer</span></div><a href="/chats" class="text-btn">Open Chat Insights <span>→</span></a>
                </article>
                <article class="card product-card"><span class="product-icon violet">◇</span><span class="eyebrow">FREELANCE
                        TRACKER</span>
                    <h2>Keep client work on track.</h2>
                    <p>Bring scope, deadlines, milestones, and invoices together. Know what was agreed, and what still needs
                        a decision.</p>
                    <div class="feature-tags"><span>Agreements</span><span>Milestones</span><span>Document studio</span>
                    </div><a href="/agreements" class="text-btn">Open Freelance Tracker <span>→</span></a>
                </article>
            </div>
        </section>
        <section class="landing-cta">
            <div><span class="eyebrow">BUILT FOR REAL CONVERSATIONS</span>
                <h2>A little context goes a long way.</h2>
                <p>Review the source. Understand the possibilities. Choose your next move.</p>
            </div><a href="/pricing" class="primary">Find your plan →</a>
        </section>
        @if ($settings['notice'])
            <div class="progress-banner">{{ $settings['notice'] }}</div>
        @endif
    </main>
@endsection
