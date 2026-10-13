(() => {
    let confirming = false;
    let queuedNotice = null;
    let lastNotice = "";
    let lastNoticeAt = 0;

    function fallback(icon, message) {
        document.querySelector(".feedback-fallback-toast")?.remove();
        const toast = document.createElement("div");
        toast.className = "feedback-fallback-toast";
        toast.setAttribute("role", icon === "error" ? "alert" : "status");
        const text = document.createElement("span");
        text.textContent = message;
        const close = document.createElement("button");
        close.type = "button";
        close.className = "bi bi-x-lg";
        close.setAttribute("aria-label", "Dismiss notification");
        close.onclick = () => toast.remove();
        toast.append(text, close);
        document.body.append(toast);
    }

    function notify(icon, message, title) {
        if (!message) return;
        message = String(message);
        if (confirming) {
            queuedNotice = [icon, message, title];
            return;
        }
        const key = `${icon}:${message}`;
        if (lastNotice === key && Date.now() - lastNoticeAt < 700) return;
        lastNotice = key;
        lastNoticeAt = Date.now();
        if (!window.Swal) return fallback(icon, message);
        const headings = {
            error: "Unable to continue",
            success: "All set",
            info: "Update",
            warning: "Please review",
        };
        window.Swal.fire({
            toast: true,
            position: "top-end",
            icon,
            titleText: title || headings[icon] || "Update",
            text: message,
            showConfirmButton: false,
            showCloseButton: true,
            closeButtonAriaLabel: "Dismiss notification",
            timer: icon === "error" ? undefined : 5500,
            timerProgressBar: icon !== "error",
            scrollbarPadding: false,
            customClass: {
                popup: "clarity-toast",
                title: "clarity-toast-title",
                htmlContainer: "clarity-toast-body",
                timerProgressBar: "clarity-toast-progress",
            },
            didOpen: (toast) => {
                toast.addEventListener("mouseenter", window.Swal.stopTimer);
                toast.addEventListener("mouseleave", window.Swal.resumeTimer);
            },
        });
    }

    async function confirm(message) {
        if (confirming) return false;
        if (!window.Swal) {
            notify(
                "error",
                "Confirmation could not be loaded. Reload the page and try again.",
            );
            return false;
        }
        confirming = true;
        try {
            const removing = /^(Delete|Remove)\b/i.test(message);
            const result = await window.Swal.fire({
                icon: "warning",
                titleText: removing ? "Confirm removal" : "Please confirm",
                text: String(message),
                showCancelButton: true,
                confirmButtonText: removing ? "Yes, remove" : "Continue",
                cancelButtonText: "Cancel",
                confirmButtonColor: removing ? "#cb506a" : "#5165ed",
                cancelButtonColor: "#64748b",
                focusCancel: true,
                reverseButtons: true,
                allowOutsideClick: false,
                customClass: { popup: "clarity-confirm" },
            });
            return result.isConfirmed;
        } finally {
            confirming = false;
            if (queuedNotice) {
                const next = queuedNotice;
                queuedNotice = null;
                notify(...next);
            }
        }
    }

    async function requestText(title, value = "", multiline = false) {
        if (!window.Swal) {
            notify(
                "error",
                "The editor could not be loaded. Reload the page and try again.",
            );
            return null;
        }
        const result = await window.Swal.fire({
            titleText: String(title),
            input: multiline ? "textarea" : "text",
            inputValue: String(value ?? ""),
            inputAttributes: { maxlength: multiline ? "100000" : "200" },
            showCancelButton: true,
            confirmButtonText: "Save",
            cancelButtonText: "Cancel",
            confirmButtonColor: "#5165ed",
            cancelButtonColor: "#64748b",
            allowOutsideClick: false,
            inputValidator: (input) =>
                String(input ?? "").trim()
                    ? undefined
                    : "This field is required.",
            customClass: { popup: "clarity-confirm" },
        });

        return result.isConfirmed ? String(result.value).trim() : null;
    }

    function fieldFor(form, name) {
        return [...form.elements].find(
            (field) =>
                !field.disabled &&
                (field.name === name ||
                    field.dataset.setting === name ||
                    field.id === name),
        );
    }

    function clearFieldError(field) {
        if (!field) return;
        field.removeAttribute("aria-invalid");
        const errorId = field.getAttribute("aria-errormessage");
        if (errorId) document.getElementById(errorId)?.remove();
        field.removeAttribute("aria-errormessage");
    }

    function clearFieldErrors(form) {
        form.querySelectorAll(".field-error").forEach((item) => item.remove());
        form.querySelectorAll('[aria-invalid="true"]').forEach((field) => {
            field.removeAttribute("aria-invalid");
            field.removeAttribute("aria-errormessage");
        });
    }

    function showFieldErrors(form, errors, resolver) {
        clearFieldErrors(form);
        let firstField = null;
        let unmatched = 0;

        Object.entries(errors || {}).forEach(([name, messages], index) => {
            const field = resolver?.(name) || fieldFor(form, name);
            const message = Array.isArray(messages) ? messages[0] : messages;
            if (!field || !message) {
                unmatched += 1;
                return;
            }

            const error = document.createElement("span");
            error.className = "field-error";
            error.id = `field-error-${Date.now()}-${index}`;
            error.setAttribute("role", "alert");
            error.textContent = String(message);
            field.setAttribute("aria-invalid", "true");
            field.setAttribute("aria-errormessage", error.id);
            field.insertAdjacentElement("afterend", error);
            firstField ||= field;
        });

        firstField?.focus({ preventScroll: true });
        firstField?.scrollIntoView({ behavior: "smooth", block: "center" });

        return { matched: Boolean(firstField), unmatched };
    }

    function validationMessage(field) {
        const label =
            field.closest("label")?.childNodes[0]?.textContent?.trim() ||
            field.getAttribute("aria-label") ||
            "This field";
        const validity = field.validity;
        if (validity.valueMissing) return `${label} is required.`;
        if (validity.typeMismatch)
            return `${label} must contain a valid value.`;
        if (validity.tooShort)
            return `${label} must be at least ${field.minLength} characters.`;
        if (validity.tooLong)
            return `${label} must be no more than ${field.maxLength} characters.`;
        if (validity.rangeUnderflow)
            return `${label} must be at least ${field.min}.`;
        if (validity.rangeOverflow)
            return `${label} must be no more than ${field.max}.`;
        if (validity.stepMismatch) return `${label} has an invalid amount.`;
        if (validity.patternMismatch) return `${label} has an invalid format.`;
        return `${label} is invalid.`;
    }

    function validateForm(form) {
        const errors = {};
        [...form.elements].forEach((field) => {
            const name =
                field.name || field.dataset.setting || field.id || "field";
            if (
                field.willValidate &&
                !field.validity.valid &&
                !errors[name]
            ) {
                errors[name] = [validationMessage(field)];
            }
        });
        if (!Object.keys(errors).length) {
            clearFieldErrors(form);
            return true;
        }
        showFieldErrors(form, errors);
        return false;
    }

    document.addEventListener("input", (event) => {
        if (event.target.form) clearFieldError(event.target);
    });
    document.addEventListener("change", (event) => {
        if (event.target.form) clearFieldError(event.target);
    });
    document.querySelectorAll("form").forEach((form) => {
        form.noValidate = true;
    });

    window.ClarityFeedback = {
        notify,
        confirm,
        requestText,
        clearFieldErrors,
        showFieldErrors,
        validateForm,
    };
    const initial = window.clarityFeedbackMessages || {};
    if (initial.error) {
        notify(
            "error",
            initial.error,
            /\/login$/.test(location.pathname)
                ? "Sign-in failed"
                : "Please check your details",
        );
    } else if (initial.success) {
        notify("success", initial.success);
    }
})();
