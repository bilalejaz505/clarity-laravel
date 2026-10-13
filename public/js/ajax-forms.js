(() => {
    const csrf = () =>
        document.querySelector('meta[name="csrf-token"]')?.content || "";

    async function submit(form) {
        if (!window.ClarityFeedback.validateForm(form)) return;
        window.ClarityFeedback.clearFieldErrors(form);
        const submitter = form.querySelector('[type="submit"]');
        submitter?.setAttribute("disabled", "disabled");
        form.setAttribute("aria-busy", "true");
        try {
            const response = await fetch(form.action, {
                method: (form.method || "POST").toUpperCase(),
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf(),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: new FormData(form),
                credentials: "same-origin",
            });
            let data = {};
            try {
                data = await response.json();
            } catch {
                throw Error("The server returned an invalid response.");
            }
            if (!response.ok) {
                if (data.errors) {
                    const result = window.ClarityFeedback.showFieldErrors(
                        form,
                        data.errors,
                    );
                    if (result.matched) return;
                }
                throw Error(
                    data.message ||
                        data.error ||
                        (response.status === 419
                            ? "Your session expired. Reload the page and try again."
                            : "Unable to submit the form."),
                );
            }
            window.ClarityFeedback.notify(
                "success",
                data.message || "Your changes were saved.",
            );
            if (form.hasAttribute("data-reset-on-success")) form.reset();
            if (data.redirect) {
                window.setTimeout(() => location.assign(data.redirect), 250);
            }
        } catch (error) {
            window.ClarityFeedback.notify("error", error.message);
        } finally {
            submitter?.removeAttribute("disabled");
            form.removeAttribute("aria-busy");
        }
    }

    document.addEventListener("submit", (event) => {
        const form = event.target.closest("form[data-ajax-form]");
        if (!form) return;
        event.preventDefault();
        if (form.getAttribute("aria-busy") === "true") return;
        submit(form);
    });

    document.querySelectorAll("form[data-ajax-form]").forEach((form) => {
        form.noValidate = true;
    });
})();
