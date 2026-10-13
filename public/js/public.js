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
    window.ClarityFeedback.notify("error", e.message);
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
                location.assign(result.redirectUrl || result.approvalUrl);
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
                ? '<a href="/chats" class="primary"><i class="bi bi-chat-dots" aria-hidden="true"></i>Open Chat Insights</a>'
                : "") +
            (e.products.includes("freelance")
                ? '<a href="/contracts" class="secondary"><i class="bi bi-briefcase" aria-hidden="true"></i>Open Freelance Tracker</a>'
                : "") +
            (d.trialAvailable && !e.allowed
                ? '<button id="start-trial" class="primary"><i class="bi bi-hourglass-split" aria-hidden="true"></i>Start one-time trial</button>'
                : "") +
            '<a href="/pricing" class="secondary"><i class="bi bi-card-list" aria-hidden="true"></i>View plans</a>' +
            (d.admin
                ? '<a href="/admin" class="secondary"><i class="bi bi-speedometer2" aria-hidden="true"></i>Open admin panel</a>'
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
            .map((s) => {
                if (s.status === "FREE_ACTIVE") {
                    return `<div class="card detail-card"><h3><i class="bi bi-gift" aria-hidden="true"></i> ${escapeText(s.plan_key)} · Free package</h3><p class="muted">Active with no payment required.</p></div>`;
                }
                if (s.status === "TEST_PAID") {
                    return `<div class="card detail-card"><h3><i class="bi bi-patch-check" aria-hidden="true"></i> ${escapeText(s.plan_key)} · Test payment</h3><p class="muted">Pre-launch paid access through ${escapeText(s.paid_until || "the configured test period")}.</p></div>`;
                }

                return `<div class="card detail-card"><h3>${escapeText(s.plan_key)} · ${escapeText(s.status)}</h3><p class="muted">Paid through: ${escapeText(s.paid_until || "Payment not yet verified")}</p><button class="secondary" data-refresh="${escapeText(s.id)}"><i class="bi bi-arrow-repeat" aria-hidden="true"></i>Verify with PayPal</button> <button class="danger" data-cancel="${escapeText(s.id)}"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancel renewals</button></div>`;
            })
            .join("");
        document.querySelectorAll("[data-refresh],[data-cancel]").forEach(
            (b) =>
                (b.onclick = async () => {
                    if (
                        b.dataset.cancel &&
                        !(await window.ClarityFeedback.confirm(
                            "Cancel future renewals? Already-paid access remains until verified expiry.",
                        ))
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
