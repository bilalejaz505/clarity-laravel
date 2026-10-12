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
        close.textContent = "×";
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

    window.ClarityFeedback = { notify, confirm };
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
