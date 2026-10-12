<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Clarity · {{ $product === 'freelance' ? 'Freelance Tracker' : 'Chat Insights' }}</title>
    <link rel="stylesheet" href="/css/clarity.css">
    <link rel="stylesheet" href="{{ asset('css/blade.css') }}?v={{ filemtime(public_path('css/blade.css')) }}">
    <link rel="icon" href="/favicon.svg">
</head>

<body>
    @php $items=$product==='freelance'?['overview'=>'Overview','library'=>'Client conversations','agreements'=>'Agreement tracker','scope'=>'Scope & changes','tasks'=>'Tasks & milestones','payments'=>'Invoices & payments','documents'=>'Document studio','ask'=>'Ask your project']:['overview'=>'Overview','library'=>'Conversations','insights'=>'Conversation insights','stats'=>'Chat statistics','explorer'=>'Message explorer','compare'=>'Compare conversations','ask'=>'Ask your chat','coach'=>'Reply coach']; @endphp
    <aside class="sidebar" id="sidebar"><a href="/" class="sidebar-brand">▤ clarity.</a><button
            class="mobile-nav-close" id="nav-close" aria-label="Close navigation">×</button>
        <div class="product-switch"><a data-switch-product="chat" href="{{ $demo ? '/demo?product=chat' : '/chats' }}"
                class="{{ $product === 'chat' ? 'active' : '' }}">Chat Insights</a><a data-switch-product="freelance"
                href="{{ $demo ? '/demo?product=freelance' : '/agreements' }}"
                class="{{ $product === 'freelance' ? 'active' : '' }}">Freelance Tracker</a></div><span
            class="eyebrow">{{ $product === 'chat' ? 'YOUR CONVERSATIONS' : 'YOUR CLIENT WORK' }}</span>
        <nav data-product-nav="chat" @if ($product !== 'chat') hidden @endif><a href="?view=overview"
                data-view="overview" class="nav-link">Overview</a><a href="?view=library" data-view="library"
                class="nav-link">Conversations</a><a href="?view=insights" data-view="insights"
                class="nav-link">Conversation insights</a><a href="?view=stats" data-view="stats" class="nav-link">Chat
                statistics</a><a href="?view=explorer" data-view="explorer" class="nav-link">Message explorer</a><a
                href="?view=compare" data-view="compare" class="nav-link">Compare conversations</a><a href="?view=ask"
                data-view="ask" class="nav-link">Ask your chat</a><a href="?view=coach" data-view="coach"
                class="nav-link">Reply coach</a></nav>
        <nav data-product-nav="freelance" @if ($product !== 'freelance') hidden @endif><a href="?view=overview"
                data-view="overview" class="nav-link">Overview</a><a href="?view=library" data-view="library"
                class="nav-link">Client conversations</a><a href="?view=agreements" data-view="agreements"
                class="nav-link">Agreement tracker</a><a href="?view=scope" data-view="scope" class="nav-link">Scope &
                changes</a><a href="?view=tasks" data-view="tasks" class="nav-link">Tasks & milestones</a><a
                href="?view=payments" data-view="payments" class="nav-link">Invoices & payments</a><a
                href="?view=documents" data-view="documents" class="nav-link">Document studio</a><a href="?view=ask"
                data-view="ask" class="nav-link">Ask your project</a></nav>
        <div class="sidebar-bottom">
            <a href="?view=settings" data-view="settings" class="nav-link">Privacy & connections</a>
            @auth
                <a href="/account" class="nav-link">My account</a>
                @if (app(\App\Services\EntitlementService::class)->admin(auth()->user()))
                    <a href="/admin" class="nav-link">Admin panel</a>
                @endif
            @else
                <a href="/login" class="nav-link">Log in</a>
            @endauth
            <a href="/" class="nav-link">Public area ↗</a>
        </div>
    </aside>
    <div class="main-shell">
        <header class="topbar">
            <div class="breadcrumb"><button id="nav-open" class="mobile-menu"
                    aria-label="Open navigation">☰</button><span>Clarity /</span><strong
                    id="view-title">Overview</strong></div>
            <div class="topbar-right"><span class="badge">{{ $demo ? 'Sample demo' : auth()->user()->name }}</span><button
                    class="primary" id="open-import" @disabled($demo)>Import chat +</button></div>
        </header>
        <main class="main-content">
            <div id="workspace-error" class="error-banner" role="alert" hidden></div>
            <div id="workspace-notice" class="progress-banner" role="status" hidden></div>
            @if ($demo)
                <div class="sample-banner">Sample demo · personal imports, persistent storage, and live AI are
                    disabled. <a href="/pricing">View plans →</a></div>
            @endif
            <div class="context-bar"><label for="conversation-select">Conversation</label><select
                    id="conversation-select"></select><span class="badge" id="conversation-engine"></span><button
                    class="text-btn" id="run-local">Local review</button><button class="secondary" id="run-ai"
                    @disabled($demo)>AI analysis</button><button class="text-btn" id="cancel-analysis"
                    hidden>Cancel</button></div>
            @yield('content')
        </main>
        <footer class="main-footer">Evidence first. Interpretations need context.</footer>
    </div>
    <dialog id="evidence-dialog">
        <div class="flex-between">
            <h2>Source evidence</h2><button data-close="evidence-dialog" aria-label="Close evidence">×</button>
        </div>
        <div id="evidence-list"></div>
    </dialog>
    <dialog id="import-dialog">
        <div class="flex-between">
            <h2>Import a conversation</h2><button data-close="import-dialog" aria-label="Close import">×</button>
        </div>
        <p class="muted">Review extracted text and participants before saving.</p>
        <form id="import-form">
            <div class="form-grid"><label>Conversation name<input name="name" required maxlength="200"
                        placeholder="Name this conversation"></label><label>Platform<select name="platform">
                        <option>WhatsApp</option>
                        <option>Telegram</option>
                        <option>Instagram</option>
                        <option>Other</option>
                    </select></label><label>Date order<select name="order">
                        <option value="DMY">Day / month / year</option>
                        <option value="MDY">Month / day / year</option>
                    </select></label><label>Purpose<select name="purpose">
                        <option value="personal">Personal</option>
                        <option value="freelance">Freelance</option>
                    </select></label></div><label>Files · TXT, CSV, XLSX, JSON<input type="file" id="import-files"
                    accept=".txt,.csv,.xlsx,.json" multiple></label>
            <p class="definition">Use the first worksheet for XLSX. Date headers and sender labels matter. Legacy XLS,
                PDF, ZIP, and audio imports are not supported.</p><label>Screenshots<input type="file"
                    id="ocr-files" accept="image/png,image/jpeg,image/webp" multiple></label>
            <div class="toolbar"><select id="ocr-language" aria-label="OCR language">
                    <option value="eng">English</option>
                    <option value="urd">Urdu</option>
                    <option value="hin">Hindi</option>
                    <option value="ara">Arabic</option>
                    <option value="eng+urd">English + Urdu</option>
                </select><button type="button" class="secondary" id="extract-ocr">Extract screenshot text</button>
            </div><label>Pasted or extracted text
                <textarea name="text" id="import-text" rows="6" placeholder="[10/10/2026, 09:00] Sender: Message"></textarea>
            </label><label class="checkbox-line"><input type="checkbox" name="redact">Redact approximate email
                addresses and phone numbers</label><label class="checkbox-line"><input type="checkbox"
                    name="anonymize">Replace participant names with aliases</label><button type="button"
                class="secondary" id="preview-import">Review import</button>
            <div id="import-preview"></div>
            <div id="participant-renames" class="form-grid"></div><button class="primary full" type="submit"
                id="confirm-import" disabled>Save reviewed conversation</button>
        </form>
    </dialog>
    <script>
        window.clarityBootstrap =
            {{ Illuminate\Support\Js::from(['product' => $product, 'demo' => $demo, 'userId' => auth()->id()]) }};
    </script>
    <script type="module" src="/js/workspace.js"></script>
</body>

</html>
