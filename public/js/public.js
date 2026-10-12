const escapeText = (value) =>
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
    const data = await r.json();
    if (r.status === 401) {
        location.href = "/login?next=/account";
        throw Error("Log in to continue.");
    }
    if (!r.ok) throw Error(data.error || data.message || "Request failed.");
    return data;
}
function showError(e) {
    const box = document.querySelector("#public-error");
    box.hidden = false;
    box.textContent = e.message;
}
document.querySelectorAll("[data-checkout]").forEach(
    (button) =>
        (button.onclick = async () => {
            button.disabled = true;
            try {
                const result = await api("/api/billing", {
                    action: "checkout",
                    planKey: button.dataset.checkout,
                });
                location.assign(result.approvalUrl);
            } catch (e) {
                showError(e);
                button.disabled = false;
            }
        }),
);
async function loadAccount() {
    if (!document.querySelector("#account-plan")) return;
    try {
        const d = await api("/api/account"),
            e = d.entitlement;
        document.querySelector("#account-plan").textContent = e.plan;
        document.querySelector("#account-reason").textContent = e.reason;
        document.querySelector("#account-usage").innerHTML =
            `<div class="metric"><span>AI message units</span><strong>${d.usage.messages}</strong><small>of ${e.messages}</small></div><div class="metric"><span>AI requests</span><strong>${d.usage.requests}</strong><small>of ${e.requests}</small></div>`;
        document.querySelector("#account-actions").innerHTML =
            (e.products.includes("chat")
                ? '<a href="/chats" class="primary">Open Chat Insights</a>'
                : "") +
            (e.products.includes("freelance")
                ? '<a href="/agreements" class="secondary">Open Freelance Tracker</a>'
                : "") +
            (d.trialAvailable && !e.allowed
                ? '<button id="start-trial" class="primary">Start one-time trial</button>'
                : "") +
            '<a href="/pricing" class="secondary">View plans</a>' +
            (d.admin
                ? '<a href="/admin" class="secondary">Open admin panel</a>'
                : "");
        const trial = document.querySelector("#start-trial");
        if (trial)
            trial.onclick = async () => {
                try {
                    await api("/api/account", {});
                    loadAccount();
                } catch (err) {
                    showError(err);
                }
            };
        document.querySelector("#subscriptions").innerHTML = d.subscriptions
            .map(
                (s) =>
                    `<div class="card detail-card"><h3>${escapeText(s.plan_key)} · ${escapeText(s.status)}</h3><p class="muted">Paid through: ${escapeText(s.paid_until || "Payment not yet verified")}</p><button class="secondary" data-refresh="${escapeText(s.id)}">Verify with PayPal</button> <button class="danger" data-cancel="${escapeText(s.id)}">Cancel renewals</button></div>`,
            )
            .join("");
        document.querySelectorAll("[data-refresh],[data-cancel]").forEach(
            (b) =>
                (b.onclick = async () => {
                    if (
                        b.dataset.cancel &&
                        !confirm(
                            "Cancel future renewals? Already-paid access remains until verified expiry.",
                        )
                    )
                        return;
                    b.disabled = true;
                    try {
                        await api("/api/billing", {
                            action: b.dataset.cancel ? "cancel" : "refresh",
                            subscriptionId: Number(
                                b.dataset.cancel || b.dataset.refresh,
                            ),
                        });
                        await loadAccount();
                    } catch (err) {
                        showError(err);
                        b.disabled = false;
                    }
                }),
        );
    } catch (e) {
        showError(e);
    }
}
loadAccount();
