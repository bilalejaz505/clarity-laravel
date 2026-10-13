<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $settings['appName'] }} · {{ $product === 'freelance' ? 'Freelance Tracker' : 'Chat Insights' }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="/css/clarity.css">
    <link rel="stylesheet" href="{{ asset('css/blade.css') }}?v={{ filemtime(public_path('css/blade.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/modern.css') }}?v={{ filemtime(public_path('css/modern.css')) }}">
    <link rel="icon" href="/favicon.svg">
</head>

<body>
    @php($availableProducts = $availableProducts ?? ['chat', 'freelance'])
    <aside class="sidebar" id="sidebar">
        <a href="/" class="sidebar-brand"><x-brand-mark /> {{ $settings['appName'] }}</a>
        <button class="mobile-nav-close icon-only" id="nav-close" aria-label="Close navigation">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
        <x-product-navigation :product="$product" :products="$availableProducts" :demo="$demo" />
        @auth
            <x-member-utility-navigation :product="$product" />
        @else
            <div class="sidebar-bottom">
                <a href="?view=settings" data-view="settings" class="nav-link"><i class="bi bi-shield-check"
                        aria-hidden="true"></i>Privacy & Connections</a>
                <a href="/login" class="nav-link"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>Log In</a>
                <a href="/" class="nav-link"><i class="bi bi-globe2" aria-hidden="true"></i>Public Site</a>
            </div>
        @endauth
    </aside>
    <div class="main-shell">
        <x-member-topbar section-title="Overview" :dynamic-title="true" :show-import="true" :demo="$demo" />
        <main class="main-content">
            @if ($demo)
                <div class="sample-banner">Sample demo · personal imports, persistent storage, and live AI are
                    disabled. <a href="/pricing">View plans <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
            @endif
            <div class="context-bar"><label for="conversation-select">Conversation</label><select
                    id="conversation-select"></select><span class="badge" id="conversation-engine"></span><button
                    class="text-btn" id="run-local">Local Review</button><button class="secondary" id="run-ai"
                    @disabled($demo)>AI Analysis</button><button class="text-btn" id="cancel-analysis"
                    hidden>Cancel</button></div>
            @yield('content')
        </main>
        <footer class="main-footer">Evidence first. Interpretations need context.</footer>
    </div>
    <dialog id="evidence-dialog">
        <div class="flex-between">
            <h2>Source Evidence</h2><button class="icon-only" data-close="evidence-dialog"
                aria-label="Close evidence"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        <div id="evidence-list"></div>
    </dialog>
    <dialog id="import-dialog">
        <div class="flex-between">
            <h2>Import A Conversation</h2><button class="icon-only" data-close="import-dialog"
                aria-label="Close import"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        <p class="muted">Review extracted text and participants before saving.</p>
        <form id="import-form" class="dashboard-form dialog-form">
            <div class="form-grid dashboard-form-grid"><label>Conversation Name<input name="name" required maxlength="200"
                        placeholder="Name this conversation"></label><label>Platform<select name="platform">
                        <option>WhatsApp</option>
                        <option>Telegram</option>
                        <option>Instagram</option>
                        <option>Other</option>
                    </select></label><label>Date Order<select name="order">
                        <option value="DMY">Day / Month / Year</option>
                        <option value="MDY">Month / Day / Year</option>
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
            </div><label>Pasted Or Extracted Text
                <textarea name="text" id="import-text" rows="6" placeholder="[10/10/2026, 09:00] Sender: Message"></textarea>
            </label><div class="dashboard-options-row"><label class="checkbox-line"><input type="checkbox" name="redact">Redact Approximate Email
                Addresses and Phone Numbers</label><label class="checkbox-line"><input type="checkbox"
                    name="anonymize">Replace Participant Names With Aliases</label></div><button type="button"
                class="secondary" id="preview-import">Review Import</button>
            <div id="import-preview"></div>
            <div id="participant-renames" class="form-grid"></div><button class="primary full" type="submit"
                id="confirm-import" disabled>Save Reviewed Conversation</button>
        </form>
    </dialog>
    <script nonce="{{ $cspNonce }}">
        window.clarityBootstrap =
            {{ Illuminate\Support\Js::from(['product' => $product, 'demo' => $demo, 'userId' => auth()->id(), 'initialStore' => $initialStore ?? null]) }};
    </script>
    <x-feedback />
    <script type="module" src="{{ asset('js/workspace.js') }}?v={{ filemtime(public_path('js/workspace.js')) }}"></script>
</body>

</html>
