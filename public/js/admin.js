const $ = (id) => document.getElementById(id),
    escape = (v) =>
        String(v ?? "").replace(
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
let state = null;
async function api(url, body) {
    const r = await fetch(url, {
        method: body ? "POST" : "GET",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]")
                .content,
        },
        ...(body ? { body: JSON.stringify(body) } : {}),
    });
    const d = await r.json();
    if (!r.ok) throw Error(d.error || d.message || "Request failed.");
    return d;
}
function error(e) {
    $("admin-error").hidden = false;
    $("admin-error").textContent = e.message;
}
function notice(text) {
    $("admin-notice").hidden = false;
    $("admin-notice").textContent = text;
}
function tab(name) {
    document
        .querySelectorAll("[data-admin-panel]")
        .forEach((p) => (p.hidden = p.dataset.adminPanel !== name));
    $("admin-save-bar").hidden = !["ai", "paypal", "general"].includes(name);
    document
        .querySelectorAll("[data-tab]")
        .forEach((b) => b.classList.toggle("active", b.dataset.tab === name));
}
function table(rows, fields) {
    return rows.length
        ? `<div class="table-scroll"><table><thead><tr>${fields.map((f) => `<th>${escape(f.replaceAll("_", " "))}</th>`).join("")}</tr></thead><tbody>${rows.map((row) => `<tr>${fields.map((f) => `<td>${escape(row[f])}</td>`).join("")}</tr>`).join("")}</tbody></table></div>`
        : '<p class="muted">No records yet.</p>';
}
async function load(search = "") {
    state = await api("/api/admin?search=" + encodeURIComponent(search));
    for (const input of document.querySelectorAll("[data-setting]")) {
        const value = state.config[input.dataset.setting];
        if (input.type === "checkbox") input.checked = value;
        else input.value = value ?? "";
    }
    $("admin-totals").innerHTML = Object.entries(state.totals)
        .map(
            ([label, n]) =>
                `<div class="metric card"><span>${escape(label)}</span><strong>${Number(n).toLocaleString()}</strong></div>`,
        )
        .join("");
    $("admin-health").textContent =
        `Database: ${state.server.database} · Authentication: ${state.server.auth} · Encryption: ${state.server.encryption ? "Ready" : "Missing app key"}`;
    for (const id of ["openai", "paypal"])
        $(id + "-saved").textContent = state.secrets.some((s) => s.id === id)
            ? "Encrypted credential is saved."
            : `No saved ${id === "openai" ? "OpenAI key" : "merchant secret"}.`;
    $("admin-subscriptions").innerHTML = table(state.subscriptions, [
        "plan_key",
        "status",
        "paid_until",
    ]);
    $("admin-usage").innerHTML = table(state.usage, [
        "product",
        "kind",
        "messages",
        "status",
    ]);
    $("admin-audit").innerHTML = table(state.audit, [
        "created_at",
        "actor",
        "action",
        "detail",
    ]);
    $("admin-users").innerHTML = state.users
        .map(
            (u) =>
                `<article class="flex-between user-row"><div><strong>${escape(u.name)}</strong><p class="muted">${escape(u.email)} · ${escape(u.status)}</p></div><button class="${u.status === "active" ? "danger" : "secondary"}" data-user="${escape(u.id)}" data-status="${u.status === "active" ? "suspended" : "active"}" ${u.is_admin ? "disabled" : ""}>${u.status === "active" ? "Suspend" : "Restore"}</button></article>`,
        )
        .join("");
    $("admin-users")
        .querySelectorAll("[data-user]")
        .forEach(
            (b) =>
                (b.onclick = async () => {
                    if (!confirm(`Set this account to ${b.dataset.status}?`))
                        return;
                    try {
                        await api("/api/admin", {
                            action: "user-status",
                            userId: b.dataset.user,
                            status: b.dataset.status,
                        });
                        await load($("user-search").value);
                        notice("Account status updated.");
                    } catch (e) {
                        error(e);
                    }
                }),
        );
    $("admin-plans").innerHTML = state.config.plans
        .map(
            (p, index) =>
                `<article class="plan-editor" data-plan="${index}"><h3>${escape(p.name)}</h3><div class="form-grid">${["name", "description", "price", "currency", "messages", "requests", "paypalPlanId"].map((field) => `<label>${escape(field === "paypalPlanId" ? "PayPal plan ID" : field)}<input data-plan-field="${field}" type="${["price", "messages", "requests"].includes(field) ? "number" : "text"}" ${field === "price" ? 'min="1" step="0.01"' : ""} value="${escape(p[field])}"></label>`).join("")}<label class="checkbox-line"><input type="checkbox" data-plan-field="enabled" ${p.enabled ? "checked" : ""}>Enabled</label><label class="checkbox-line"><input type="checkbox" data-product="chat" ${p.products.includes("chat") ? "checked" : ""}>Chat Insights</label><label class="checkbox-line"><input type="checkbox" data-product="freelance" ${p.products.includes("freelance") ? "checked" : ""}>Freelance Tracker</label></div></article>`,
        )
        .join("");
}
document
    .querySelectorAll("[data-tab]")
    .forEach((b) => (b.onclick = () => tab(b.dataset.tab)));
$("user-search-form").onsubmit = async (e) => {
    e.preventDefault();
    try {
        await load($("user-search").value);
    } catch (err) {
        error(err);
    }
};
$("admin-settings-form").onsubmit = async (e) => {
    e.preventDefault();
    if (!state) return;
    const config = structuredClone(state.config);
    document
        .querySelectorAll("[data-setting]")
        .forEach(
            (i) =>
                (config[i.dataset.setting] =
                    i.type === "checkbox"
                        ? i.checked
                        : i.type === "number"
                          ? Number(i.value)
                          : i.value),
        );
    document.querySelectorAll("[data-plan]").forEach((row) => {
        const p = config.plans[Number(row.dataset.plan)];
        row.querySelectorAll("[data-plan-field]").forEach(
            (i) =>
                (p[i.dataset.planField] =
                    i.type === "checkbox"
                        ? i.checked
                        : i.type === "number"
                          ? Number(i.value)
                          : i.value),
        );
        p.products = [...row.querySelectorAll("[data-product]:checked")].map(
            (i) => i.dataset.product,
        );
    });
    try {
        await api("/api/admin", {
            action: "settings",
            config,
            revision: state.revision,
            openaiKey: $("openai-key").value,
            paypalSecret: $("paypal-secret").value,
        });
        $("openai-key").value = "";
        $("paypal-secret").value = "";
        await load();
        notice("Settings saved.");
    } catch (err) {
        error(err);
    }
};
document.querySelectorAll("[data-delete-secret]").forEach(
    (b) =>
        (b.onclick = async () => {
            if (!confirm("Remove this saved provider credential?")) return;
            try {
                await api("/api/admin", {
                    action: "delete-secret",
                    id: b.dataset.deleteSecret,
                });
                await load();
                notice("Saved credential removed.");
            } catch (err) {
                error(err);
            }
        }),
);
$("test-openai").onclick = async () => {
    try {
        const result = await api("/api/connection", {});
        notice("Saved connection verified: " + result.model);
    } catch (err) {
        error(err);
    }
};
tab("overview");
load().catch(error);
