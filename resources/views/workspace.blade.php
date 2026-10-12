@extends('layouts.workspace')
@section('content')
    <section data-panel="overview">
        <div class="welcome">
            <div><span class="eyebrow">YOUR WORKSPACE, WITH PERSPECTIVE</span>
                <h1>Make sense of<br><span>what was said.</span></h1>
                <p>Explore the patterns. Review the evidence. Decide your next step.</p>
            </div>
        </div>
        <div class="metrics" id="overview-metrics"></div>
        <div class="two-col">
            <article class="card detail-card">
                <h3>Message activity</h3>
                <div id="overview-chart" class="daily-chart"></div>
            </article>
            <article class="card detail-card">
                <h3>Conversation summary</h3>
                <p id="overview-summary"></p>
                <div class="toolbar"><button class="secondary" data-go="insights">Review insights</button><button
                        class="secondary" data-go="library">View conversations</button></div>
            </article>
        </div>
    </section>
    <section data-panel="library" hidden>
        <div class="page-heading">
            <h1>Your conversations</h1>
            <p>Keep each conversation organized with its source and purpose.</p>
        </div>
        <div id="conversation-library" class="conversation-grid"></div>
    </section>
    <section data-panel="insights" hidden>
        <div class="page-heading">
            <h1>Conversation insights</h1>
            <p>Observed wording, alternative interpretations, and inspectable evidence.</p>
        </div>
        <div class="toolbar"><button id="export-report" class="secondary">Export JSON report</button></div>
        <div id="insight-summary" class="card detail-card"></div>
        <div id="insights-list" class="insights-grid"></div>
    </section>
    <section data-panel="stats" hidden>
        <div class="page-heading">
            <h1>Chat statistics</h1>
            <p>Message counts and timing describe activity, not character or intentions.</p>
        </div>
        <div id="statistics-metrics" class="metrics"></div>
        <div class="card detail-card">
            <h3>Participants & response timing</h3>
            <div id="participant-statistics"></div>
        </div>
        <div class="two-col">
            <div class="card detail-card">
                <h3>Daily activity</h3>
                <div id="stats-chart" class="daily-chart"></div>
            </div>
            <div class="card detail-card">
                <h3>Activity by hour · UTC</h3>
                <div id="hour-chart" class="hours"></div>
            </div>
        </div>
        <div class="two-col">
            <div class="card detail-card">
                <h3>Frequent words</h3>
                <div id="word-list"></div>
            </div>
            <div class="card detail-card">
                <h3>Emoji use</h3>
                <div id="emoji-list"></div>
            </div>
        </div>
    </section>
    <section data-panel="explorer" hidden>
        <div class="page-heading">
            <h1>Message explorer</h1>
            <p>Search, inspect, and correct messages. Exports include all filtered matches.</p>
        </div>
        <div class="filters"><input id="message-search" placeholder="Search messages" aria-label="Search messages"><select
                id="sender-filter" aria-label="Sender">
                <option value="">All participants</option>
            </select><input type="date" id="date-from" aria-label="From date"><input type="date" id="date-to"
                aria-label="To date"><button class="secondary" id="export-messages">Export CSV</button></div>
        <p id="message-count" class="definition"></p>
        <div id="message-list"></div>
    </section>
    <section data-panel="compare" hidden>
        <div class="page-heading">
            <h1>Compare conversations</h1>
            <p>Compare communication patterns without ranking people.</p>
        </div><select id="compare-conversation" aria-label="Comparison conversation"></select>
        <div id="comparison" class="two-col"></div>
    </section>
    <section data-panel="ask" hidden>
        <div class="page-heading">
            <h1>Ask your conversation</h1>
            <p>Answers use a retrieved excerpt, with source links and clear limits.</p>
        </div>
        <form id="ask-form" class="card detail-card"><label>Your question
                <textarea name="question" rows="3" required maxlength="2500" placeholder="What was agreed about the deadline?"></textarea>
            </label><button class="primary" type="submit">Ask with AI →</button>
            <p class="definition">The most relevant 30 messages are supplied. This is not exhaustive semantic retrieval.</p>
        </form>
        <div id="answers-list"></div>
    </section>
    <section data-panel="coach" hidden>
        <div class="page-heading">
            <h1>Reply coach</h1>
            <p>Draft a clear, respectful response without inventing commitments.</p>
        </div>
        <form id="coach-form" class="card detail-card"><label>What would you like to say?
                <textarea name="context" rows="4" required maxlength="2000"></textarea>
            </label><label>Tone<select name="tone">
                    <option>Warm</option>
                    <option>Direct</option>
                    <option>Professional</option>
                    <option>Set a boundary</option>
                </select></label>
            <div class="toolbar"><button class="secondary" type="button" id="local-reply">Create local
                    draft</button><button class="primary" type="submit">Draft with AI</button></div><label>Editable reply
                <textarea id="coach-reply" rows="7"></textarea>
            </label><button class="text-btn" type="button" id="copy-reply">Copy reply</button>
        </form>
    </section>
    <section data-panel="agreements" hidden>
        <div class="page-heading">
            <h1>Agreement tracker</h1>
            <p>Review proposed, accepted, unclear, and rejected terms against the original conversation.</p>
        </div>
        <div class="filters"><select id="term-filter" aria-label="Term status">
                <option value="">All statuses</option>
                <option>proposed</option>
                <option>accepted</option>
                <option>unclear</option>
                <option>rejected</option>
            </select><button class="secondary" id="export-terms">Export terms CSV</button></div>
        <div id="terms-list" class="term-list"></div>
        <div class="card detail-card">
            <h3>Review history</h3>
            <div id="review-history"></div>
        </div>
    </section>
    <section data-panel="scope" hidden>
        <div class="page-heading">
            <h1>Scope & changes</h1>
            <p>Keep baseline scope and proposed changes separate.</p>
        </div>
        <div id="scope-list" class="term-list"></div>
    </section>
    <section data-panel="tasks" hidden>
        <div class="page-heading">
            <h1>Tasks & milestones</h1>
            <p>Track delivery dates and status; export dated tasks to your calendar.</p>
        </div>
        <form id="task-form" class="card detail-card">
            <div class="form-grid"><label>Task title<input name="title" required maxlength="200"></label><label>Due
                    date<input type="date" name="due"></label><label>Notes<input name="note"
                        maxlength="1000"></label></div><button class="primary">Add task</button><button type="button"
                class="secondary" id="export-calendar">Export calendar ICS</button>
        </form>
        <div id="task-board" class="kanban"></div>
    </section>
    <section data-panel="payments" hidden>
        <div class="page-heading">
            <h1>Invoices & payments</h1>
            <p>These are manually reviewed records. They do not collect client payments.</p>
        </div>
        <form id="invoice-form" class="card detail-card">
            <div class="form-grid"><label>Client<input name="client" required
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
                    </select></label><label>Due date<input name="due" type="date"></label></div><button
                class="primary">Add invoice record</button><button type="button" class="secondary"
                id="export-invoices">Export ledger CSV</button>
        </form>
        <div id="invoice-totals" class="metrics"></div>
        <div id="invoice-list"></div>
    </section>
    <section data-panel="documents" hidden>
        <div class="page-heading">
            <h1>Document studio</h1>
            <p>Editable drafts built from reviewed terms. Documents are not signed contracts.</p>
        </div>
        <div class="toolbar"><select id="document-kind" aria-label="Document template">
                <option>Statement of work</option>
                <option>Project proposal</option>
                <option>Agreement summary</option>
                <option>Change request</option>
                <option>Follow-up email</option>
            </select><button class="primary" id="generate-document">Create draft</button><button class="secondary"
                id="download-document">Download TXT</button><button class="secondary" id="print-document">Print / Save
                PDF</button></div>
        <article class="card document-card">
            <textarea id="document-text" rows="24" aria-label="Editable document"></textarea>
            <pre class="print-document" id="print-document-text"></pre>
        </article>
    </section>
    <section data-panel="settings" hidden>
        <div class="page-heading">
            <h1>Privacy & connections</h1>
            <p>Control device storage, backups, and your private server snapshot.</p>
        </div>
        <div class="card detail-card">
            <h3>Workspace storage</h3><label class="checkbox-line"><input type="checkbox"
                    id="remember-workspace">Remember this workspace on this device</label>
            <p class="definition">Session-only by default. Device storage contains message text when enabled. Device data
                is scoped to your account.</p>
            <div class="toolbar"><button class="secondary" id="export-backup">Export workspace backup</button><label
                    class="secondary">Restore backup<input id="restore-backup" type="file" accept=".json"
                        hidden></label><button class="danger" id="clear-device">Delete device workspace</button><button
                    class="text-btn" id="reset-samples">Reset to sample data</button></div>
        </div>
        <div class="card detail-card">
            <h3>Service connections</h3>
            <p id="connection-status"></p>
            <div class="toolbar"><button class="secondary" id="save-cloud">Save to server</button><button
                    class="secondary" id="load-cloud">Load server snapshot</button><button class="danger"
                    id="delete-cloud">Delete server snapshot</button></div>
            <p class="definition">Laravel authorizes snapshots by your logged-in account. Provider secrets stay on the
                server and are configured in admin. Demo data is never persisted.</p>
        </div>
    </section>
@endsection
