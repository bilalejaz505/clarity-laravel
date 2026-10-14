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
async function api(url, body, loadingMessage = "Loading Your Account…") {
    window.ClarityFeedback.showLoading(loadingMessage);
    try {
        const r = await fetch(url, {
            method: body ? "POST" : "GET",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    "meta[name=csrf-token]",
                ).content,
            },
            ...(body ? { body: JSON.stringify(body) } : {}),
            credentials: "same-origin",
        });
        const data = await r.json();
        if (r.status === 401) {
            location.href = "/login?next=/account";
            throw Error("Log in to continue.");
        }
        if (!r.ok)
            throw Error(data.error || data.message || "Request failed.");
        return data;
    } finally {
        window.ClarityFeedback.hideLoading();
    }
}
function showError(e) {
    window.ClarityFeedback.notify("error", e.message);
}
document.querySelectorAll("[data-checkout]").forEach(
    (button) =>
        (button.onclick = async () => {
            button.disabled = true;
            try {
                const result = await api(
                    "/api/billing",
                    {
                        action: "checkout",
                        planKey: button.dataset.checkout,
                    },
                    "Preparing Secure PayPal Checkout…",
                );
                window.ClarityFeedback.showLoading("Opening PayPal…");
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
            `<div class="metric"><span>AI Message Units</span><strong>${Number(d.usage.messages).toLocaleString()}</strong><small>of ${Number(e.messages).toLocaleString()}</small></div><div class="metric"><span>AI Requests</span><strong>${Number(d.usage.requests).toLocaleString()}</strong><small>of ${Number(e.requests).toLocaleString()}</small></div>`;
        document.querySelector("#account-actions").innerHTML =
            (e.products.includes("chat")
                ? '<a href="/chats" class="primary"><i class="bi bi-chat-dots" aria-hidden="true"></i>Open Chat Insights</a>'
                : "") +
            (e.products.includes("freelance")
                ? '<a href="/contracts" class="secondary"><i class="bi bi-briefcase" aria-hidden="true"></i>Open Freelance Tracker</a>'
                : "") +
            (d.trialAvailable && !e.allowed
                ? '<button id="start-trial" class="primary"><i class="bi bi-hourglass-split" aria-hidden="true"></i>Start One-Time Trial</button>'
                : "") +
            '<a href="/pricing" class="secondary"><i class="bi bi-card-list" aria-hidden="true"></i>View Plans</a>' +
            (d.admin
                ? '<a href="/admin" class="secondary"><i class="bi bi-speedometer2" aria-hidden="true"></i>Open Admin Panel</a>'
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
                    return `<div class="card detail-card"><h3><i class="bi bi-gift" aria-hidden="true"></i> ${escapeText(s.plan_key)} · Free Package</h3><p class="muted">Active with no payment required.</p></div>`;
                }
                if (s.status === "TEST_PAID") {
                    return `<div class="card detail-card"><h3><i class="bi bi-patch-check" aria-hidden="true"></i> ${escapeText(s.plan_key)} · Test Payment</h3><p class="muted">Pre-launch paid access through ${escapeText(s.paid_until || "the configured test period")}.</p></div>`;
                }

                return `<div class="card detail-card"><h3>${escapeText(s.plan_key)} · ${escapeText(s.status)}</h3><p class="muted">Paid Through: ${escapeText(s.paid_until || "Payment Not Yet Verified")}</p><button class="secondary" data-refresh="${escapeText(s.id)}"><i class="bi bi-arrow-repeat" aria-hidden="true"></i>Verify With PayPal</button> <button class="danger" data-cancel="${escapeText(s.id)}"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancel Renewals</button></div>`;
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

async function completeCheckoutReturn() {
    const url = new URL(location.href);
    if (
        url.searchParams.get("checkout") !== "returned" ||
        !url.searchParams.get("subscription_id")
    ) {
        await loadAccount();
        return;
    }

    try {
        const result = await api(
            "/api/billing",
            {
                action: "complete",
                paypalSubscriptionId: url.searchParams.get("subscription_id"),
            },
            "Confirming Your PayPal Subscription…",
        );
        window.ClarityFeedback.notify(
            result.paidUntil ? "success" : "info",
            result.paidUntil
                ? "Payment confirmed. Your package is now active."
                : "PayPal approval was received. Payment confirmation is still processing.",
        );
        history.replaceState({}, "", "/account");
    } catch (error) {
        showError(error);
    }
    await loadAccount();
}

completeCheckoutReturn();
