@extends('layouts.admin')
@section('title', 'Clarity administration')
@section('content')
    <div class="page-heading admin-page-heading">
        <span class="eyebrow">CONTROL CENTER</span>
        <h1 id="admin-section-heading">System overview</h1>
        <p id="admin-section-description">Monitor access, subscriptions, usage, and platform health.</p>
    </div>
    <section data-admin-panel="overview">
        <div id="admin-totals" class="metrics"></div>
        <div class="card detail-card">
            <h3>System health</h3>
            <p id="admin-health"></p>
        </div>
        <div class="two-col">
            <div class="card detail-card">
                <h3>Recent subscriptions</h3>
                <div id="admin-subscriptions"></div>
            </div>
            <div class="card detail-card">
                <h3>Recent AI usage</h3>
                <div id="admin-usage"></div>
            </div>
        </div>
    </section>
    <section data-admin-panel="users" hidden>
        <div class="card detail-card">
            <form id="user-search-form" class="toolbar">
                <input id="user-search" placeholder="Search names or emails" maxlength="100" aria-label="Search users">
                <button class="secondary"><i class="bi bi-search" aria-hidden="true"></i>Search</button>
            </form>
            <div id="admin-users"></div>
        </div>
    </section>
    <form id="admin-settings-form">
        <section data-admin-panel="ai" hidden class="card detail-card">
            <h2>OpenAI configuration</h2>
            <p class="definition">Keys are encrypted in MySQL and never returned to this page. Leave the replacement
                field blank to retain the saved key.</p>
            <div class="form-grid">
                <label class="checkbox-line"><input type="checkbox" data-setting="aiEnabled">Enable AI processing</label>
                <label>Model<input data-setting="aiModel" maxlength="150"
                        placeholder="Account-accessible model ID"></label>
                <label>Maximum output tokens<input type="number" data-setting="aiMaxOutput" min="1000"
                        max="10000"></label>
                <label>Replace OpenAI key<input type="password" id="openai-key" autocomplete="new-password"
                        placeholder="Leave blank to keep saved key"></label>
            </div>
            <p id="openai-saved" class="muted"></p>
            <div class="toolbar">
                <button type="button" id="test-openai" class="secondary"><i class="bi bi-plug"
                        aria-hidden="true"></i>Test saved connection</button>
                <button type="button" data-delete-secret="openai" class="danger"><i class="bi bi-trash3"
                        aria-hidden="true"></i>Remove saved key</button>
            </div>
        </section>
        <section data-admin-panel="paypal" hidden class="card detail-card">
            <h2>PayPal merchant configuration</h2>
            <p class="definition">Use sandbox first. Merchant credentials, mapped active monthly plans, and a verified
                webhook are required for checkout.</p>
            <div class="form-grid">
                <label>Environment<select data-setting="paypalEnvironment">
                        <option value="sandbox">Sandbox</option>
                        <option value="live">Live</option>
                    </select></label>
                <label>Client ID<input data-setting="paypalClientId" maxlength="200"></label>
                <label>Webhook ID<input data-setting="paypalWebhookId" maxlength="100"></label>
                <label>Replace merchant secret<input type="password" id="paypal-secret" autocomplete="new-password"
                        placeholder="Leave blank to keep saved secret"></label>
            </div>
            <p id="paypal-saved" class="muted"></p>
            <button type="button" data-delete-secret="paypal" class="danger"><i class="bi bi-trash3"
                    aria-hidden="true"></i>Remove saved merchant secret</button>
            <h3>Plans, prices & quotas</h3>
            <div id="admin-plans"></div>
        </section>
        <section data-admin-panel="email" hidden class="card detail-card">
            <h2>Email & SMTP Configuration</h2>
            <p class="definition">Verification, password reset, and system email use these settings. The password is
                encrypted in MySQL and is never returned to this page.</p>
            <div class="form-grid">
                <label class="checkbox-line"><input type="checkbox" data-setting="smtpEnabled">Enable SMTP
                    Delivery</label>
                <label>SMTP Host<input data-setting="smtpHost" maxlength="255"></label>
                <label>SMTP Port<input data-setting="smtpPort" type="number" min="1" max="65535"></label>
                <label>Encryption<select data-setting="smtpEncryption">
                        <option value="none">None</option>
                        <option value="tls">TLS / STARTTLS</option>
                        <option value="ssl">SSL</option>
                    </select></label>
                <label>Username<input data-setting="smtpUsername" maxlength="255" autocomplete="off"></label>
                <label>Replace SMTP Password<input type="password" id="smtp-password" autocomplete="new-password"
                        maxlength="500" placeholder="Leave blank to keep saved password"></label>
                <label>From Address<input data-setting="smtpFromAddress" type="email" maxlength="254"></label>
                <label>From Name<input data-setting="smtpFromName" maxlength="120"></label>
            </div>
            <p id="smtp-saved" class="muted"></p>
            <div class="toolbar">
                <button type="button" id="test-smtp" class="secondary"><i class="bi bi-send-check"
                        aria-hidden="true"></i>Send Test Email</button>
                <button type="button" data-delete-secret="smtp" class="danger"><i class="bi bi-trash3"
                        aria-hidden="true"></i>Remove Saved Password</button>
            </div>
        </section>
        <section data-admin-panel="general" hidden class="card detail-card">
            <h2>System & Site Settings</h2>
            <div class="form-grid">
                <label>Application name<input data-setting="appName" maxlength="80" required></label>
                <label>Site Tagline<input data-setting="siteTagline" maxlength="160" required></label>
                <label>Support Email<input data-setting="supportEmail" type="email" maxlength="254"></label>
                <label>Customer notice<textarea data-setting="notice" maxlength="500"></textarea></label>
                <label>Maintenance Page Title<input data-setting="maintenanceTitle" maxlength="120" required></label>
                <label>Maintenance Message<textarea data-setting="maintenanceMessage" maxlength="500"
                        required></textarea></label>
                <label>Maximum upload MB<input data-setting="maxUploadMB" type="number" min="1" max="50"></label>
                <label>Maximum messages per import<input data-setting="maxImportMessages" type="number" min="100"
                        max="100000"></label>
                <label class="checkbox-line"><input data-setting="maintenance" type="checkbox">Maintenance Mode</label>
                <label class="checkbox-line"><input data-setting="demoEnabled" type="checkbox">Enable Sample Demo</label>
                <label class="checkbox-line"><input data-setting="trialEnabled" type="checkbox">Enable one-time
                    trial</label>
                <label>Trial days<input data-setting="trialDays" type="number" min="1" max="30"></label>
                <label>Trial message units<input data-setting="trialMessages" type="number" min="1"
                        max="10000"></label>
                <label>Trial AI requests<input data-setting="trialRequests" type="number" min="1" max="100"></label>
            </div>
        </section>
        <div id="admin-save-bar" class="sticky-save" hidden>
            <button type="submit" class="primary"><i class="bi bi-floppy" aria-hidden="true"></i>Save settings</button>
            <span class="muted">Changes are protected by a settings revision check.</span>
        </div>
    </form>
    <section data-admin-panel="audit" hidden class="card detail-card">
        <h2>Audit log</h2>
        <div id="admin-audit"></div>
    </section>
@endsection
@section('scripts')
    <script type="module" src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
@endsection
