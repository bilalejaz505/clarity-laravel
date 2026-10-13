@extends('layouts.workspace')
@section('content')
    <section data-panel="overview">
        <div class="welcome">
            <div><span class="eyebrow">YOUR WORKSPACE, WITH PERSPECTIVE</span>
                <h1 id="overview-heading">Your Conversations<br><span>A Clearer Perspective</span></h1>
                <p id="overview-description">Explore the patterns. Review the evidence. Decide your next step.</p>
            </div>
        </div>
        <div class="metrics" id="overview-metrics"></div>
        <div class="two-col">
            <article class="card detail-card">
                <h3>Message Activity</h3>
                <div id="overview-chart" class="daily-chart"></div>
            </article>
            <article class="card detail-card">
                <h3>Conversation Summary</h3>
                <p id="overview-summary"></p>
                <div class="toolbar"><button class="secondary" data-go="insights">Review insights</button><button
                        class="secondary" data-go="library">View conversations</button></div>
            </article>
        </div>
    </section>
    <section data-panel="library" hidden>
        <div class="page-heading">
            <h1>Your Conversations</h1>
            <p>Keep each conversation organized with its source and purpose.</p>
        </div>
        <div id="conversation-library" class="conversation-grid"></div>
    </section>
    <section data-panel="insights" hidden>
        <div class="page-heading">
            <h1>Conversation Insights</h1>
            <p>Observed wording, alternative interpretations, and inspectable evidence.</p>
        </div>
        <div class="toolbar"><button id="export-report" class="secondary"><i class="bi bi-download"
                    aria-hidden="true"></i>Export JSON report</button></div>
        <div id="insight-summary" class="card detail-card"></div>
        <div id="insights-list" class="insights-grid"></div>
    </section>
    <section data-panel="stats" hidden>
        <div class="page-heading">
            <h1>Chat Statistics</h1>
            <p>Message counts and timing describe activity, not character or intentions.</p>
        </div>
        <div id="statistics-metrics" class="metrics"></div>
        <div class="card detail-card">
            <h3>Participants & Response Timing</h3>
            <div id="participant-statistics"></div>
        </div>
        <div class="two-col">
            <div class="card detail-card">
                <h3>Daily Activity</h3>
                <div id="stats-chart" class="daily-chart"></div>
            </div>
            <div class="card detail-card">
                <h3>Activity By Hour · UTC</h3>
                <div id="hour-chart" class="hours"></div>
            </div>
        </div>
        <div class="two-col">
            <div class="card detail-card">
                <h3>Frequent Words</h3>
                <div id="word-list"></div>
            </div>
            <div class="card detail-card">
                <h3>Emoji Use</h3>
                <div id="emoji-list"></div>
            </div>
        </div>
    </section>
    <section data-panel="explorer" hidden>
        <div class="page-heading">
            <h1>Message Explorer</h1>
            <p>Search, inspect, and correct messages. Exports include all filtered matches.</p>
        </div>
        <div class="filters"><input id="message-search" placeholder="Search messages" aria-label="Search messages"><select
                id="sender-filter" aria-label="Sender">
                <option value="">All participants</option>
            </select><input type="date" id="date-from" aria-label="From date"><input type="date" id="date-to"
                aria-label="To date"><button class="secondary" id="export-messages"><i class="bi bi-filetype-csv"
                    aria-hidden="true"></i>Export CSV</button></div>
        <p id="message-count" class="definition"></p>
        <div id="message-list"></div>
    </section>
    <section data-panel="compare" hidden>
        <div class="page-heading">
            <h1>Compare Conversations</h1>
            <p>Compare communication patterns without ranking people.</p>
        </div><select id="compare-conversation" aria-label="Comparison conversation"></select>
        <div id="comparison" class="two-col"></div>
    </section>
    <section data-panel="ask" hidden>
        <div class="page-heading">
            <h1>Ask Your Conversation</h1>
            <p>Answers use a retrieved excerpt, with source links and clear limits.</p>
        </div>
        <form id="ask-form" class="card detail-card dashboard-form"><label>Your Question
                <textarea name="question" rows="3" required maxlength="2500" placeholder="What was agreed about the deadline?"></textarea>
            </label><button class="primary" type="submit">Ask with AI <i class="bi bi-stars"
                    aria-hidden="true"></i></button>
            <p class="definition">The most relevant 30 messages are supplied. This is not exhaustive semantic retrieval.</p>
        </form>
        <div id="answers-list"></div>
    </section>
    <section data-panel="coach" hidden>
        <div class="page-heading">
            <h1>Reply Coach</h1>
            <p>Draft a clear, respectful response without inventing commitments.</p>
        </div>
        <form id="coach-form" class="card detail-card dashboard-form"><label>What Would You Like to Say?
                <textarea name="context" rows="4" required maxlength="2000"></textarea>
            </label><label>Tone<select name="tone">
                    <option>Warm</option>
                    <option>Direct</option>
                    <option>Professional</option>
                    <option>Set a boundary</option>
                </select></label>
            <div class="toolbar"><button class="secondary" type="button" id="local-reply"><i
                        class="bi bi-pencil-square" aria-hidden="true"></i>Create Local Draft</button><button
                    class="primary" type="submit"><i class="bi bi-stars" aria-hidden="true"></i>Draft With AI</button>
            </div><label>Editable Reply
                <textarea id="coach-reply" rows="7"></textarea>
            </label><button class="text-btn" type="button" id="copy-reply"><i class="bi bi-copy"
                    aria-hidden="true"></i>Copy Reply</button>
        </form>
    </section>
    <section data-panel="contracts" hidden>
        <div class="page-heading">
            <h1>Contract Tracker</h1>
            <p>Review proposed, accepted, unclear, and rejected terms against the original conversation.</p>
        </div>
        <div class="filters"><select id="term-filter" aria-label="Term status">
                <option value="">All statuses</option>
                <option value="proposed">Proposed</option>
                <option value="accepted">Accepted</option>
                <option value="unclear">Unclear</option>
                <option value="rejected">Rejected</option>
            </select><button class="secondary" id="export-terms"><i class="bi bi-filetype-csv"
                    aria-hidden="true"></i>Export terms CSV</button></div>
        <div id="terms-list" class="term-list"></div>
        <div class="card detail-card">
            <h3>Review History</h3>
            <div id="review-history"></div>
        </div>
    </section>
    <section data-panel="scope" hidden>
        <div class="page-heading">
            <h1>Scope & Changes</h1>
            <p>Keep baseline scope and proposed changes separate.</p>
        </div>
        <div id="scope-list" class="term-list"></div>
    </section>
    <section data-panel="tasks" hidden>
        <div class="page-heading">
            <h1>Tasks & Milestones</h1>
            <p>Track delivery dates and status; export dated tasks to your calendar.</p>
        </div>
        <form id="task-form" class="card detail-card dashboard-form">
            <div class="form-grid dashboard-form-grid"><label>Task Title<input name="title" required maxlength="200"></label><label>Due
                    Date<input type="date" name="due"></label><label class="dashboard-field-full">Notes<input name="note"
                        maxlength="1000"></label></div><div class="toolbar"><button class="primary"><i class="bi bi-plus-circle"
                        aria-hidden="true"></i>Add Task</button><button type="button" class="secondary"
                    id="export-calendar"><i class="bi bi-calendar3" aria-hidden="true"></i>Export Calendar ICS</button></div>
        </form>
        <div id="task-board" class="kanban"></div>
    </section>
    <section data-panel="payments" hidden>
        <div class="page-heading">
            <h1>Invoices & Payments</h1>
            <p>These are manually reviewed records. They do not collect client payments.</p>
        </div>
        <form id="invoice-form" class="card detail-card dashboard-form">
            <div class="form-grid dashboard-form-grid"><label>Client<input name="client" required
                        maxlength="200"></label><label>Description<input name="description" required
                        maxlength="1000"></label><label>Amount<input name="amount" type="number" min="0"
                        step="0.01" required></label><label>Currency<select name="currency">
                        <option>USD</option>
                        <option>EUR</option>
                        <option>GBP</option>
                        <option>PKR</option>
                        <option>AED</option>
                        <option>CAD</option>
                        <option>AUD</option>
                    </select></label><label class="dashboard-field-full">Due Date<input name="due" type="date"></label></div><div class="toolbar"><button
                    class="primary"><i class="bi bi-plus-circle" aria-hidden="true"></i>Add Invoice Record</button><button
                    type="button" class="secondary" id="export-invoices"><i class="bi bi-filetype-csv"
                        aria-hidden="true"></i>Export Ledger CSV</button></div>
        </form>
        <div id="invoice-totals" class="metrics"></div>
        <div id="invoice-list"></div>
    </section>
    <section data-panel="documents" hidden>
        <div class="page-heading">
            <h1>Document Studio</h1>
            <p>Editable drafts built from reviewed terms. Documents are not signed contracts.</p>
        </div>
        <div class="toolbar"><select id="document-kind" aria-label="Document template">
                <option value="Statement of work">Statement Of Work</option>
                <option value="Project proposal">Project Proposal</option>
                <option value="Contract summary">Contract Summary</option>
                <option value="Change request">Change Request</option>
                <option value="Follow-up email">Follow-Up Email</option>
            </select><button class="primary" id="generate-document"><i class="bi bi-file-earmark-plus"
                    aria-hidden="true"></i>Create draft</button><button class="secondary" id="download-document"><i
                    class="bi bi-download" aria-hidden="true"></i>Download TXT</button><button class="secondary"
                id="print-document"><i class="bi bi-printer" aria-hidden="true"></i>Print / Save PDF</button></div>
        <article class="card document-card">
            <textarea id="document-text" rows="24" aria-label="Editable document"></textarea>
            <pre class="print-document" id="print-document-text"></pre>
        </article>
    </section>
    <section data-panel="settings" hidden>
        <div class="page-heading">
            <h1>Privacy & Connections</h1>
            <p>Control device storage, backups, and your private server snapshot.</p>
        </div>
        <div class="card detail-card">
            <h3>Workspace Storage</h3><label class="checkbox-line"><input type="checkbox"
                    id="remember-workspace">Remember This Workspace on This Device</label>
            <p class="definition">Session-only by default. Device storage contains message text when enabled. Device data
                is scoped to your account.</p>
            <div class="toolbar"><button class="secondary" id="export-backup"><i class="bi bi-download"
                        aria-hidden="true"></i>Export workspace backup</button><label class="secondary"><i
                        class="bi bi-upload" aria-hidden="true"></i>Restore backup<input id="restore-backup"
                        type="file" accept=".json" hidden></label><button class="danger" id="clear-device"><i
                        class="bi bi-trash3" aria-hidden="true"></i>Delete device workspace</button><button
                    class="text-btn" id="reset-samples"><i class="bi bi-arrow-counterclockwise"
                        aria-hidden="true"></i>Reset to sample data</button></div>
        </div>
        <div class="card detail-card">
            <h3>Service Connections</h3>
            <p id="connection-status"></p>
            <div class="toolbar"><button class="secondary" id="save-cloud"><i class="bi bi-cloud-arrow-up"
                        aria-hidden="true"></i>Save to server</button><button class="secondary" id="load-cloud"><i
                        class="bi bi-cloud-arrow-down" aria-hidden="true"></i>Load server snapshot</button><button
                    class="danger" id="delete-cloud"><i class="bi bi-cloud-slash" aria-hidden="true"></i>Delete server
                    snapshot</button></div>
            <p class="definition">Laravel authorizes snapshots by your logged-in account. Provider secrets stay on the
                server and are configured in admin. Demo data is never persisted.</p>
        </div>
    </section>
@endsection
