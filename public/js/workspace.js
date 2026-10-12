import {
    sampleStore,
    statistics,
    localAnalysis,
    mergeAnalyses,
    parseText,
    deduplicate,
    exportCSV,
    uid,
    displayTime,
} from "./core/domain.js?v=contracts-1";
import { importFiles, screenshotText } from "./core/imports.js";
import {
    documentDraft,
    calendarExport,
} from "./core/documents.js?v=contracts-1";
import { workspaceSchema } from "./core/validation.js";
const boot = window.clarityBootstrap,
    $ = (id) => document.getElementById(id),
    escape = (value) =>
        String(value ?? "").replace(
            /[&<>"']/g,
            (c) =>
                ({
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#39;",
                })[c],
        );
const CACHE = "clarity.workspace.v1." + (boot.userId || "demo"),
    mem = {
        store: boot.initialStore
            ? workspaceSchema.parse(boot.initialStore)
            : sampleStore(),
        selected: "",
        view: "overview",
        config: {
            ai: false,
            cloud: false,
            maxUploadMB: 20,
            maxImportMessages: 100000,
        },
        busy: false,
        cancel: false,
        answers: [],
        preview: null,
    };
if (!boot.demo) {
    try {
        const saved = localStorage.getItem(CACHE);
        if (saved) {
            mem.store = workspaceSchema.parse(JSON.parse(saved).store);
            $("remember-workspace").checked = true;
        }
    } catch {
        error("Saved device data could not be read. Samples were loaded.");
    }
}
const relevant = () =>
        mem.store.conversations.filter((c) =>
            boot.product === "freelance"
                ? c.purpose === "freelance"
                : c.purpose !== "freelance",
        ),
    current = () =>
        relevant().find((c) => c.id === mem.selected) || relevant()[0];
function error(message) {
    if (message) window.ClarityFeedback.notify("error", message);
}
function notice(message) {
    window.ClarityFeedback.notify(
        /…|\.\.\.|Stopping|Answering|Analyzing/i.test(message)
            ? "info"
            : "success",
        message,
    );
}
function save() {
    if (boot.demo) return;
    try {
        if ($("remember-workspace").checked)
            localStorage.setItem(
                CACHE,
                JSON.stringify({ version: 1, store: mem.store }),
            );
        else localStorage.removeItem(CACHE);
    } catch {
        error(
            "Device storage is full. Export a backup before closing this tab.",
        );
    }
}
function download(name, text, type = "text/plain") {
    const url = URL.createObjectURL(new Blob([text], { type })),
        a = document.createElement("a");
    a.href = url;
    a.download = name;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}
async function api(url, { method = "GET", body, signal } = {}) {
    const response = await fetch(url, {
        method,
        signal,
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]")
                .content,
        },
        ...(body !== undefined ? { body: JSON.stringify(body) } : {}),
    });
    let data;
    try {
        data = await response.json();
    } catch {
        throw Error("The server did not return a valid response.");
    }
    if (!response.ok)
        throw Error(data.error || data.message || "Request failed.");
    return data;
}
function button(text, attrs = "") {
    return `<button class="text-btn" ${attrs}>${text}</button>`;
}
function empty(text) {
    return `<div class="empty"><p>${escape(text)}</p></div>`;
}
function metrics(s) {
    return [
        ["Messages", s.total],
        ["Participants", s.participants.length],
        ["Sessions", s.sessions],
        ["Active days", s.days.length],
    ]
        .map(
            ([name, n]) =>
                `<div class="metric card"><span>${name}</span><strong>${n.toLocaleString()}</strong><small>${name === "Messages" ? s.dated + " with timestamps" : name === "Sessions" ? "New session after 8 hours" : ""}</small></div>`,
        )
        .join("");
}
function chart(days) {
    const values = days.slice(-30),
        max = Math.max(1, ...values.map((v) => v[1]));
    return values.length
        ? values
              .map(
                  ([date, n]) =>
                      `<div class="daily-column" title="${escape(date)}: ${n}"><div style="height:${Math.max(3, (n / max) * 120)}px"></div><small>${escape(date.slice(5))}</small></div>`,
              )
              .join("")
        : empty("No usable dates in this conversation.");
}
function evidence(ids) {
    const messages = current()?.messages || [];
    $("evidence-list").innerHTML = ids
        .map((id) => {
            const m = messages.find((m) => m.id === id);
            return m
                ? `<article class="card detail-card"><strong>${escape(m.sender)}</strong><p>${escape(m.text).replaceAll("\n", "<br>")}</p><small class="muted">${escape(m.timestamp ? displayTime(m.timestamp) : "Undated")} · ${escape(m.source)}</small></article>`
                : empty("Source message no longer exists.");
        })
        .join("");
    $("evidence-dialog").showModal();
}
function bindEvidence(root) {
    root.querySelectorAll("[data-evidence]").forEach(
        (b) => (b.onclick = () => evidence(JSON.parse(b.dataset.evidence))),
    );
}
function evidenceButton(ids) {
    return button(
        "View source evidence",
        `data-evidence="${escape(JSON.stringify(ids))}"`,
    );
}
function render() {
    $("overview-heading").innerHTML =
        boot.product === "freelance"
            ? "Clear scope.<br><span>Confident delivery.</span>"
            : "Your conversations.<br><span>A clearer perspective.</span>";
    $("overview-description").textContent =
        boot.product === "freelance"
            ? "See what is agreed, what is due, and what needs your attention."
            : "Explore the patterns. Review the evidence. Decide your next step.";
    const c = current(),
        messages = c?.messages || [],
        stats = statistics(messages),
        analysis = c?.analysis;
    mem.selected = c?.id || "";
    $("conversation-select").innerHTML =
        relevant()
            .map(
                (v) =>
                    `<option value="${escape(v.id)}" ${v.id === mem.selected ? "selected" : ""}>${escape(v.name)}</option>`,
            )
            .join("") || "<option>No conversations</option>";
    $("conversation-engine").textContent = analysis
        ? analysis.engine === "ai"
            ? "AI report"
            : "Local observations"
        : "Not reviewed";
    $("overview-metrics").innerHTML = metrics(stats);
    $("statistics-metrics").innerHTML = metrics(stats);
    $("overview-summary").textContent =
        analysis?.summary ||
        "Import a conversation to begin reviewing patterns and contracts.";
    $("overview-chart").innerHTML = chart(stats.days);
    $("stats-chart").innerHTML = chart(stats.days);
    $("conversation-library").innerHTML =
        relevant()
            .map(
                (v) =>
                    `<article class="card detail-card"><h3>${escape(v.name)}</h3><p class="muted">${escape(v.platform)} · ${v.messages.length} messages · ${v.sample ? "sample" : "personal import"}</p><div class="toolbar">${button("Open", `data-select="${escape(v.id)}"`)}${button("Rename", `data-rename="${escape(v.id)}"`)}${button("Delete", `data-delete="${escape(v.id)}"`)}</div></article>`,
            )
            .join("") ||
        empty(
            "No conversations yet. Import an export or restore a workspace backup.",
        );
    $("conversation-library")
        .querySelectorAll("[data-select]")
        .forEach(
            (b) =>
                (b.onclick = async () => {
                    mem.selected = b.dataset.select;
                    render();
                    show("overview");
                }),
        );
    $("conversation-library")
        .querySelectorAll("[data-rename]")
        .forEach(
            (b) =>
                (b.onclick = async () => {
                    const v = mem.store.conversations.find(
                            (c) => c.id === b.dataset.rename,
                        ),
                        name = prompt("Conversation name", v.name);
                    if (name?.trim()) {
                        v.name = name.trim().slice(0, 200);
                        save();
                        render();
                    }
                }),
        );
    $("conversation-library")
        .querySelectorAll("[data-delete]")
        .forEach(
            (b) =>
                (b.onclick = async () => {
                    if (
                        await window.ClarityFeedback.confirm(
                            "Delete this device conversation? Export a backup first if needed. The server snapshot is separate.",
                        )
                    ) {
                        mem.store.conversations =
                            mem.store.conversations.filter(
                                (c) => c.id !== b.dataset.delete,
                            );
                        save();
                        render();
                    }
                }),
        );
    $("insight-summary").textContent =
        analysis?.summary ||
        "Run a local review or AI analysis to create a report.";
    $("insights-list").innerHTML =
        analysis?.insights
            .map(
                (i) =>
                    `<article class="card insight"><span class="badge ${escape(i.kind)}">${escape(i.kind)}</span><h3>${escape(i.title)}</h3><p>${escape(i.detail)}</p><p class="definition">${escape(i.interpretation)}</p>${evidenceButton(i.evidence)}</article>`,
            )
            .join("") || empty("No observations available.");
    bindEvidence($("insights-list"));
    $("participant-statistics").innerHTML =
        `<div class="table-scroll"><table><thead><tr><th>Participant</th><th>Messages</th><th>Words</th><th>Questions</th><th>Starts</th><th>Median reply</th></tr></thead><tbody>${stats.participants.map((p) => `<tr><td>${escape(p.name)}</td><td>${p.count}</td><td>${p.words}</td><td>${p.questions}</td><td>${p.initiations}</td><td>${p.medianReply === null ? "—" : Math.round(p.medianReply) + " min"}</td></tr>`).join("")}</tbody></table></div><p class="definition">Only responses to another participant are counted. Gaps of 24 hours or more are excluded. No read receipt or availability is inferred.</p>`;
    $("hour-chart").innerHTML = stats.hours
        .map(
            (n, i) =>
                `<div class="hour-cell" title="${i}:00 — ${n} messages"><strong>${n}</strong><small>${i}:00</small></div>`,
        )
        .join("");
    for (const [id, list] of [
        ["word-list", stats.words],
        ["emoji-list", stats.emojis],
    ])
        $(id).innerHTML = list
            .map(
                ([word, n]) =>
                    `<span class="badge">${escape(word)} · ${n}</span>`,
            )
            .join(" ");
    const sender = $("sender-filter").value;
    $("sender-filter").innerHTML =
        '<option value="">All participants</option>' +
        stats.participants
            .map(
                (p) =>
                    `<option ${sender === p.name ? "selected" : ""}>${escape(p.name)}</option>`,
            )
            .join("");
    renderMessages();
    $("compare-conversation").innerHTML = relevant()
        .filter((v) => v.id !== c?.id)
        .map(
            (v) => `<option value="${escape(v.id)}">${escape(v.name)}</option>`,
        )
        .join("");
    renderCompare();
    renderTerms();
    renderTasks();
    renderInvoices();
    renderAnswers();
    $("connection-status").textContent =
        `AI: ${mem.config.ai ? mem.config.model : "Administrator setup required"} · Private server snapshots: ${mem.config.cloud ? "Available" : "Log in to use"}${boot.demo ? " · Disabled in demo" : ""}`;
    for (const id of [
        "save-cloud",
        "load-cloud",
        "delete-cloud",
        "restore-backup",
        "remember-workspace",
        "clear-device",
        "reset-samples",
    ])
        $(id).disabled = boot.demo;
    $("run-ai").disabled = boot.demo || !mem.config.ai || mem.busy;
    $("run-local").disabled = !c || mem.busy;
    $("open-import").disabled = boot.demo || mem.busy;
}
function filteredMessages() {
    return (current()?.messages || []).filter(
        (m) =>
            (!$("sender-filter").value ||
                m.sender === $("sender-filter").value) &&
            (!$("message-search").value ||
                `${m.sender} ${m.text}`
                    .toLowerCase()
                    .includes($("message-search").value.toLowerCase())) &&
            (!$("date-from").value ||
                (m.timestamp &&
                    m.timestamp.slice(0, 10) >= $("date-from").value)) &&
            (!$("date-to").value ||
                (m.timestamp &&
                    m.timestamp.slice(0, 10) <= $("date-to").value)),
    );
}
function renderMessages() {
    const messages = filteredMessages();
    $("message-count").textContent =
        `${messages.length} matching messages · displaying the first 300`;
    $("message-list").innerHTML =
        messages
            .slice(0, 300)
            .map(
                (m) =>
                    `<article class="message-row card"><div><strong>${escape(m.sender)}</strong><time>${escape(m.timestamp ? displayTime(m.timestamp) : "Undated")}</time><p>${escape(m.text).replaceAll("\n", "<br>")}</p><small class="muted">${escape(m.source)}</small>${button("Correct message", `data-edit-message="${escape(m.id)}"`)}</div></article>`,
            )
            .join("") || empty("No messages match your filters.");
    $("message-list")
        .querySelectorAll("[data-edit-message]")
        .forEach(
            (b) =>
                (b.onclick = async () => {
                    const m = current().messages.find(
                            (m) => m.id === b.dataset.editMessage,
                        ),
                        text = prompt("Correct message text", m.text);
                    if (text !== null) {
                        m.text = text;
                        const sender = prompt("Participant name", m.sender);
                        if (sender?.trim()) m.sender = sender.trim();
                        save();
                        render();
                    }
                }),
        );
}
function renderCompare() {
    const a = current(),
        b = relevant().find((v) => v.id === $("compare-conversation").value);
    $("comparison").innerHTML = [a, b]
        .map((v) =>
            v
                ? `<article class="card detail-card"><h3>${escape(v.name)}</h3><div class="metrics">${metrics(statistics(v.messages))}</div><p>${escape(v.analysis?.summary || "No review yet.")}</p></article>`
                : empty("Import another conversation for comparison."),
        )
        .join("");
}
function termMarkup(term) {
    return `<article class="card term-card" data-term="${escape(term.id)}"><div class="flex-between"><h3>${escape(term.title)}</h3><span class="badge">${escape(term.category)}</span></div><p>${escape(term.detail)}</p>${evidenceButton(term.evidence)}<div class="form-grid"><label>Status<select data-term-field="status">${["proposed", "accepted", "unclear", "rejected"].map((s) => `<option ${s === term.status ? "selected" : ""}>${s}</option>`).join("")}</select></label><label>Due date<input data-term-field="due" type="date" value="${escape(term.due)}"></label><label>Amount<input data-term-field="amount" type="number" min="0" step="0.01" value="${term.amount ?? ""}"></label><label>Currency<input data-term-field="currency" value="${escape(term.currency)}" maxlength="10"></label><label>Review note<textarea data-term-field="note" rows="2">${escape(term.note)}</textarea></label><label class="checkbox-line"><input data-term-field="completed" type="checkbox" ${term.completed ? "checked" : ""}>Completed</label></div></article>`;
}
function renderTerms() {
    const terms = current()?.analysis?.terms || [];
    for (const [id, list] of [
        [
            "terms-list",
            terms.filter(
                (t) =>
                    !$("term-filter").value ||
                    t.status === $("term-filter").value,
            ),
        ],
        [
            "scope-list",
            terms.filter((t) =>
                ["scope", "change", "revision"].includes(t.category),
            ),
        ],
    ]) {
        const root = $(id);
        root.innerHTML =
            list.map(termMarkup).join("") ||
            empty(
                "No candidate terms. Import a client conversation and review it.",
            );
        bindEvidence(root);
        root.querySelectorAll("[data-term-field]").forEach(
            (input) =>
                (input.onchange = () => {
                    const term = terms.find(
                            (t) =>
                                t.id ===
                                input.closest("[data-term]").dataset.term,
                        ),
                        field = input.dataset.termField,
                        from = String(term[field]);
                    term[field] =
                        field === "completed"
                            ? input.checked
                            : field === "amount"
                              ? input.value === ""
                                  ? null
                                  : Number(input.value)
                              : input.value;
                    mem.store.reviews ??= [];
                    mem.store.reviews.unshift({
                        id: uid(),
                        conversationId: current().id,
                        termTitle: term.title,
                        from: `${field}: ${from}`,
                        to: `${field}: ${term[field]}`,
                        at: new Date().toISOString(),
                    });
                    save();
                    renderTerms();
                }),
        );
    }
    $("review-history").innerHTML =
        (mem.store.reviews || [])
            .filter((r) => r.conversationId === current()?.id)
            .slice(0, 100)
            .map(
                (r) =>
                    `<p><strong>${escape(r.termTitle)}</strong> · ${escape(r.from)} → ${escape(r.to)}<br><small class="muted">${escape(displayTime(r.at))}</small></p>`,
            )
            .join("") || '<p class="muted">No reviews recorded.</p>';
}
function renderTasks() {
    const tasks = mem.store.tasks.filter(
        (t) => t.conversationId === current()?.id,
    );
    $("task-board").innerHTML = ["todo", "doing", "done"]
        .map(
            (status) =>
                `<section class="card detail-card"><h3>${status === "todo" ? "To do" : status === "doing" ? "In progress" : "Done"}</h3>${tasks
                    .filter((t) => t.status === status)
                    .map(
                        (t) =>
                            `<article class="task-card" data-task="${escape(t.id)}"><label>Title<input data-task-field="title" value="${escape(t.title)}"></label><label>Due<input data-task-field="due" type="date" value="${escape(t.due)}"></label><label>Status<select data-task-field="status">${["todo", "doing", "done"].map((s) => `<option ${s === t.status ? "selected" : ""}>${s}</option>`).join("")}</select></label><label>Notes<textarea data-task-field="note">${escape(t.note)}</textarea></label>${button("Delete", `data-delete-task="${escape(t.id)}"`)}</article>`,
                    )
                    .join("")}</section>`,
        )
        .join("");
    $("task-board")
        .querySelectorAll("[data-task-field]")
        .forEach(
            (i) =>
                (i.onchange = () => {
                    const t = mem.store.tasks.find(
                        (t) => t.id === i.closest("[data-task]").dataset.task,
                    );
                    t[i.dataset.taskField] = i.value;
                    save();
                    renderTasks();
                }),
        );
    $("task-board")
        .querySelectorAll("[data-delete-task]")
        .forEach(
            (b) =>
                (b.onclick = async () => {
                    if (
                        await window.ClarityFeedback.confirm(
                            "Delete this task?",
                        )
                    ) {
                        mem.store.tasks = mem.store.tasks.filter(
                            (t) => t.id !== b.dataset.deleteTask,
                        );
                        save();
                        renderTasks();
                    }
                }),
        );
}
function renderInvoices() {
    const invoices = mem.store.invoices.filter(
            (i) => i.conversationId === current()?.id,
        ),
        totals = {};
    invoices.forEach((i) => {
        totals[i.currency] ??= { paid: 0, unpaid: 0 };
        totals[i.currency][i.status === "paid" ? "paid" : "unpaid"] += i.amount;
    });
    $("invoice-totals").innerHTML = Object.entries(totals)
        .map(
            ([currency, total]) =>
                `<div class="metric card"><span>${escape(currency)}</span><strong>${total.unpaid.toLocaleString()}</strong><small>Unpaid · ${total.paid.toLocaleString()} paid</small></div>`,
        )
        .join("");
    $("invoice-list").innerHTML =
        invoices
            .map(
                (i) =>
                    `<article class="card detail-card" data-invoice="${escape(i.id)}"><h3>${escape(i.client)}</h3><div class="form-grid"><label>Description<input data-invoice-field="description" value="${escape(i.description)}"></label><label>Amount<input type="number" data-invoice-field="amount" min="0" step="0.01" value="${i.amount}"></label><label>Currency<input data-invoice-field="currency" value="${escape(i.currency)}"></label><label>Due<input type="date" data-invoice-field="due" value="${escape(i.due)}"></label><label>Status<select data-invoice-field="status">${["draft", "sent", "paid"].map((s) => `<option ${s === i.status ? "selected" : ""}>${s}</option>`).join("")}</select></label></div>${button("Delete", `data-delete-invoice="${escape(i.id)}"`)}</article>`,
            )
            .join("") || empty("No invoice records.");
    $("invoice-list")
        .querySelectorAll("[data-invoice-field]")
        .forEach(
            (input) =>
                (input.onchange = () => {
                    const invoice = mem.store.invoices.find(
                        (i) =>
                            i.id ===
                            input.closest("[data-invoice]").dataset.invoice,
                    );
                    invoice[input.dataset.invoiceField] =
                        input.dataset.invoiceField === "amount"
                            ? Math.max(0, Number(input.value))
                            : input.value;
                    save();
                    renderInvoices();
                }),
        );
    $("invoice-list")
        .querySelectorAll("[data-delete-invoice]")
        .forEach(
            (b) =>
                (b.onclick = async () => {
                    if (
                        await window.ClarityFeedback.confirm(
                            "Delete this invoice record?",
                        )
                    ) {
                        mem.store.invoices = mem.store.invoices.filter(
                            (i) => i.id !== b.dataset.deleteInvoice,
                        );
                        save();
                        renderInvoices();
                    }
                }),
        );
}
function renderAnswers() {
    $("answers-list").innerHTML = mem.answers
        .filter((a) => a.conversationId === current()?.id)
        .map(
            (a) =>
                `<article class="card detail-card"><h3>${escape(a.question)}</h3><p style="white-space:pre-wrap">${escape(a.answer)}</p>${evidenceButton(a.evidence)}</article>`,
        )
        .join("");
    bindEvidence($("answers-list"));
}
function show(view) {
    if (view === "agreements") view = "contracts";
    if (!document.querySelector(`[data-panel="${view}"]`)) view = "overview";
    mem.view = view;
    document
        .querySelectorAll("[data-panel]")
        .forEach((p) => (p.hidden = p.dataset.panel !== view));
    document
        .querySelectorAll("[data-view]")
        .forEach((a) => a.classList.toggle("active", a.dataset.view === view));
    $("view-title").textContent =
        document.querySelector(
            `[data-product-nav="${boot.product}"] [data-view="${view}"]`,
        )?.textContent ||
        document.querySelector(`[data-view="${view}"]`)?.textContent ||
        "Overview";
    $("sidebar").classList.remove("mobile-open");
    const url = new URL(location.href);
    url.searchParams.set("view", view);
    history.replaceState({}, "", url);
    if (view === "documents" && !$("document-text").value) createDocument();
}
document.querySelectorAll("[data-view]").forEach(
    (a) =>
        (a.onclick = (e) => {
            e.preventDefault();
            show(a.dataset.view);
        }),
);
document
    .querySelectorAll("[data-go]")
    .forEach((b) => (b.onclick = () => show(b.dataset.go)));
$("nav-open").onclick = () => $("sidebar").classList.add("mobile-open");
$("nav-close").onclick = () => $("sidebar").classList.remove("mobile-open");
document
    .querySelectorAll("[data-close]")
    .forEach((b) => (b.onclick = () => $(b.dataset.close).close()));
$("conversation-select").onchange = () => {
    mem.selected = $("conversation-select").value;
    $("document-text").value = "";
    render();
};
$("compare-conversation").onchange = renderCompare;
for (const id of ["message-search", "sender-filter", "date-from", "date-to"])
    $(id).oninput = renderMessages;
$("term-filter").onchange = renderTerms;
$("run-local").onclick = () => {
    if (current()) {
        current().analysis = localAnalysis(current().messages);
        save();
        render();
        notice("Local observations created. Review the evidence and context.");
    }
};
$("run-ai").onclick = async () => {
    if (boot.demo || mem.busy || !current()) return;
    const c = current(),
        messages = [...c.messages],
        parts = [];
    mem.busy = true;
    mem.cancel = false;
    error("");
    $("cancel-analysis").hidden = false;
    render();
    try {
        for (let i = 0; i < messages.length; i += 100) {
            if (mem.cancel) break;
            notice(
                `Analyzing ${Math.min(i + 100, messages.length)} of ${messages.length} messages…`,
            );
            const chunk = messages.slice(Math.max(0, i - 5), i + 100),
                result = await api("/api/ai", {
                    method: "POST",
                    body: {
                        mode: "analyze",
                        purpose: c.purpose,
                        messages: chunk,
                    },
                });
            parts.push(result.analysis);
        }
        if (parts.length) {
            c.analysis = mergeAnalyses(
                parts,
                Math.min(parts.length * 100, messages.length),
            );
            save();
        }
        notice(
            mem.cancel
                ? "Analysis stopped. Completed batches are retained."
                : "AI report ready. Review findings and evidence.",
        );
    } catch (e) {
        error(e.message);
    } finally {
        mem.busy = false;
        $("cancel-analysis").hidden = true;
        render();
    }
};
$("cancel-analysis").onclick = () => {
    mem.cancel = true;
    notice("Stopping after the current request.");
};
$("open-import").onclick = () => {
    if (boot.demo) return;
    $("import-dialog").showModal();
    $("import-form").elements.purpose.value =
        boot.product === "freelance" ? "freelance" : "personal";
};
$("extract-ocr").onclick = async () => {
    try {
        if (!$("ocr-files").files.length)
            throw Error("Choose screenshots first.");
        $("extract-ocr").disabled = true;
        $("import-text").value = await screenshotText(
            [...$("ocr-files").files],
            $("ocr-language").value,
            notice,
        );
        notice(
            "OCR text extracted. Add Sender: labels and review before importing.",
        );
    } catch (e) {
        error(e.message);
    } finally {
        $("extract-ocr").disabled = false;
    }
};
$("preview-import").onclick = async () => {
    try {
        $("confirm-import").disabled = true;
        const form = $("import-form"),
            data = new FormData(form),
            order = data.get("order");
        let messages = await importFiles(
            [...$("import-files").files],
            order,
            notice,
            mem.config,
        );
        if (data.get("text").trim())
            messages.push(
                ...parseText(
                    data.get("text"),
                    "Pasted or screenshot text",
                    order,
                ),
            );
        messages = deduplicate(messages);
        if (!messages.length)
            throw Error(
                "No messages found. Use a supported export or Sender: labels.",
            );
        if (messages.length > mem.config.maxImportMessages)
            throw Error("Import exceeds the message limit.");
        if (data.get("redact"))
            messages = messages.map((m) => ({
                ...m,
                text: m.text
                    .replace(
                        /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi,
                        "[email redacted]",
                    )
                    .replace(/(?:\+?\d[\d\s().-]{8,}\d)/g, "[phone redacted]"),
            }));
        const names = [...new Set(messages.map((m) => m.sender))];
        mem.preview = messages;
        $("import-preview").innerHTML =
            `<div class="progress-banner">${messages.length} messages · ${names.length} participants · ${messages.filter((m) => m.timestamp).length} timestamps</div><div class="preview-messages">${messages
                .slice(0, 8)
                .map(
                    (m) =>
                        `<p><strong>${escape(m.sender)}</strong>: ${escape(m.text)}</p>`,
                )
                .join("")}</div>`;
        $("participant-renames").innerHTML = names
            .map(
                (name, i) =>
                    `<label>${escape(name)}<input data-original-name="${escape(name)}" value="${escape(data.get("anonymize") ? "Participant " + (i + 1) : name)}" required maxlength="200"></label>`,
            )
            .join("");
        $("confirm-import").disabled = false;
        notice("Review sender names and sample messages, then save.");
    } catch (e) {
        error(e.message);
    }
};
$("import-form").onsubmit = (e) => {
    e.preventDefault();
    if (boot.demo || !mem.preview) return;
    const data = new FormData(e.currentTarget),
        map = {};
    $("participant-renames")
        .querySelectorAll("input")
        .forEach((i) => (map[i.dataset.originalName] = i.value.trim()));
    if (Object.values(map).some((v) => !v)) {
        error("Participant names cannot be empty.");
        return;
    }
    const messages = mem.preview.map((m) => ({
            ...m,
            sender: map[m.sender] || m.sender,
        })),
        c = {
            id: uid(),
            name: String(data.get("name")).trim(),
            platform: data.get("platform"),
            purpose: data.get("purpose"),
            messages,
            created: new Date().toISOString(),
            sample: false,
            analysis: null,
        };
    mem.store.conversations.unshift(c);
    mem.selected = c.id;
    save();
    render();
    $("import-dialog").close();
    e.currentTarget.reset();
    mem.preview = null;
    notice("Conversation imported.");
    if ((boot.product === "freelance") !== (c.purpose === "freelance"))
        notice(
            "Imported. Switch to the matching app to view this conversation. Export a backup first if device storage is off.",
        );
};
$("task-form").onsubmit = (e) => {
    e.preventDefault();
    if (!current()) return;
    const data = new FormData(e.currentTarget);
    mem.store.tasks.push({
        id: uid(),
        conversationId: current().id,
        title: data.get("title"),
        due: data.get("due"),
        note: data.get("note"),
        status: "todo",
    });
    save();
    renderTasks();
    e.currentTarget.reset();
};
$("invoice-form").onsubmit = (e) => {
    e.preventDefault();
    if (!current()) return;
    const data = new FormData(e.currentTarget);
    mem.store.invoices.push({
        id: uid(),
        conversationId: current().id,
        client: data.get("client"),
        description: data.get("description"),
        amount: Number(data.get("amount")),
        currency: data.get("currency"),
        due: data.get("due"),
        status: "draft",
    });
    save();
    renderInvoices();
    e.currentTarget.reset();
};
$("ask-form").onsubmit = async (e) => {
    e.preventDefault();
    if (boot.demo) {
        error("Live AI is disabled in the sample demo.");
        return;
    }
    const c = current(),
        question = new FormData(e.currentTarget).get("question");
    if (!c) return;
    const words = question.toLowerCase().match(/\p{L}{3,}/gu) || [],
        messages = c.messages
            .map((m, i) => ({
                m,
                i,
                score: words.reduce(
                    (n, w) => n + (m.text.toLowerCase().includes(w) ? 1 : 0),
                    0,
                ),
            }))
            .sort((a, b) => b.score - a.score || b.i - a.i)
            .slice(0, 30)
            .sort((a, b) => a.i - b.i)
            .map((v) => v.m);
    try {
        notice("Answering from a relevant excerpt…");
        const answer = await api("/api/ai", {
            method: "POST",
            body: { mode: "ask", purpose: c.purpose, question, messages },
        });
        mem.answers.unshift({ question, ...answer, conversationId: c.id });
        renderAnswers();
        notice("Answer ready. Check its evidence.");
    } catch (err) {
        error(err.message);
    }
};
$("local-reply").onclick = () => {
    const data = new FormData($("coach-form"));
    $("coach-reply").value =
        (data.get("tone") === "Set a boundary"
            ? "I want to be clear about what works for me. "
            : data.get("tone") === "Professional"
              ? "Thank you for your message. "
              : "Thanks for sharing this. ") +
        data.get("context") +
        "\n\nCould we agree on the next step?";
};
$("coach-form").onsubmit = async (e) => {
    e.preventDefault();
    if (boot.demo) {
        error("Live AI is disabled in the sample demo.");
        return;
    }
    const c = current(),
        data = new FormData(e.currentTarget);
    if (!c) return;
    try {
        const d = await api("/api/ai", {
            method: "POST",
            body: {
                mode: "reply",
                purpose: c.purpose,
                question: `${data.get("tone")} tone. Goal: ${data.get("context")}`,
                messages: c.messages.slice(-30),
            },
        });
        $("coach-reply").value = d.answer;
        notice("Reply draft ready. Review before sending.");
    } catch (err) {
        error(err.message);
    }
};
$("copy-reply").onclick = async () => {
    try {
        await navigator.clipboard.writeText($("coach-reply").value);
        notice("Reply copied.");
    } catch {
        error("Select the reply text and copy it manually.");
    }
};
function createDocument() {
    if (current())
        $("document-text").value = documentDraft(
            $("document-kind").value,
            current(),
        );
}
$("generate-document").onclick = createDocument;
$("download-document").onclick = () =>
    download("clarity-document.txt", $("document-text").value);
$("print-document").onclick = () => {
    $("print-document-text").textContent = $("document-text").value;
    window.print();
};
$("export-calendar").onclick = () =>
    download(
        "clarity-milestones.ics",
        calendarExport(
            mem.store.tasks.filter((t) => t.conversationId === current()?.id),
        ),
        "text/calendar",
    );
$("export-messages").onclick = () =>
    download("clarity-messages.csv", exportCSV(filteredMessages()), "text/csv");
$("export-terms").onclick = () =>
    download(
        "clarity-terms.csv",
        exportCSV(current()?.analysis?.terms || []),
        "text/csv",
    );
$("export-invoices").onclick = () =>
    download(
        "clarity-ledger.csv",
        exportCSV(
            mem.store.invoices.filter(
                (i) => i.conversationId === current()?.id,
            ),
        ),
        "text/csv",
    );
$("export-report").onclick = () =>
    download(
        "clarity-report.json",
        JSON.stringify(
            { conversation: current()?.name, analysis: current()?.analysis },
            null,
            2,
        ),
        "application/json",
    );
$("export-backup").onclick = () =>
    download(
        "clarity-workspace.json",
        JSON.stringify({ version: 1, store: mem.store }, null, 2),
        "application/json",
    );
$("remember-workspace").onchange = save;
$("restore-backup").onchange = async (e) => {
    if (boot.demo) return;
    try {
        const file = e.target.files[0];
        if (file.size > 10 * 1024 * 1024)
            throw Error("Backups are limited to 10 MB.");
        const decoded = JSON.parse(await file.text()),
            store = workspaceSchema.parse(decoded.store || decoded);
        if (
            !(await window.ClarityFeedback.confirm(
                "Replace the device workspace with this backup?",
            ))
        )
            return;
        mem.store = store;
        save();
        render();
        notice("Workspace restored.");
    } catch (err) {
        error("Backup could not be restored: " + err.message);
    }
};
$("clear-device").onclick = async () => {
    if (boot.demo) return;
    if (
        await window.ClarityFeedback.confirm(
            "Delete this device workspace? The server snapshot is separate.",
        )
    ) {
        mem.store = { conversations: [], tasks: [], invoices: [] };
        mem.answers = [];
        $("remember-workspace").checked = false;
        localStorage.removeItem(CACHE);
        render();
        notice("Device workspace cleared.");
    }
};
$("reset-samples").onclick = async () => {
    if (
        !boot.demo &&
        (await window.ClarityFeedback.confirm(
            "Replace the device workspace with sample data?",
        ))
    ) {
        mem.store = sampleStore();
        save();
        render();
    }
};
$("save-cloud").onclick = async () => {
    if (boot.demo) return;
    try {
        await api("/api/workspace", {
            method: "PUT",
            body: { product: boot.product, store: mem.store },
        });
        notice("Private server snapshot saved.");
    } catch (e) {
        error(e.message);
    }
};
$("load-cloud").onclick = async () => {
    if (
        boot.demo ||
        !(await window.ClarityFeedback.confirm(
            "Replace device data with your server snapshot?",
        ))
    )
        return;
    try {
        const d = await api("/api/workspace");
        if (!d.store)
            throw Error("No server snapshot exists for this account.");
        mem.store = workspaceSchema.parse(d.store);
        save();
        render();
        notice("Server snapshot loaded.");
    } catch (e) {
        error(e.message);
    }
};
$("delete-cloud").onclick = async () => {
    if (
        boot.demo ||
        !(await window.ClarityFeedback.confirm("Delete your server snapshot?"))
    )
        return;
    try {
        await api("/api/workspace", { method: "DELETE" });
        notice("Server snapshot deleted.");
    } catch (e) {
        error(e.message);
    }
};
render();
show(new URL(location.href).searchParams.get("view") || "overview");
api("/api/config")
    .then((config) => {
        mem.config = config;
        render();
        if (config.notice) notice(config.notice);
    })
    .catch((e) => error(e.message));

document.querySelectorAll("[data-switch-product]").forEach(
    (link) =>
        (link.onclick = (event) => {
            event.preventDefault();
            const product = link.dataset.switchProduct;
            if (!boot.demo && !mem.config.products?.includes(product)) {
                location.assign(link.href);
                return;
            }
            boot.product = product;
            document.title = `Clarity · ${product === "freelance" ? "Freelance Tracker" : "Chat Insights"}`;
            document.querySelector(".sidebar > .eyebrow").textContent =
                product === "freelance"
                    ? "YOUR CLIENT WORK"
                    : "YOUR CONVERSATIONS";
            document
                .querySelectorAll("[data-product-nav]")
                .forEach(
                    (nav) => (nav.hidden = nav.dataset.productNav !== product),
                );
            document
                .querySelectorAll("[data-switch-product]")
                .forEach((a) =>
                    a.classList.toggle(
                        "active",
                        a.dataset.switchProduct === product,
                    ),
                );
            const url = new URL(location.href);
            if (!boot.demo)
                url.pathname =
                    product === "freelance" ? "/contracts" : "/chats";
            else url.searchParams.set("product", product);
            history.replaceState({}, "", url);
            mem.selected = "";
            render();
            show("overview");
        }),
);
