function documentDraft(kind, c) {
    const terms = c.analysis?.terms || [],
        accepted = terms.filter((t) => t.status === "accepted"),
        pending = terms.filter(
            (t) => !["accepted", "rejected"].includes(t.status),
        );
    const details = (list) =>
        list.length
            ? list
                  .map(
                      (t, i) =>
                          `${i + 1}. ${t.detail}${
                              t.due
                                  ? `
   Due: ${t.due}`
                                  : ""
                          }${
                              t.amount !== null
                                  ? `
   Amount: ${t.currency} ${t.amount}`
                                  : ""
                          }${
                              t.note
                                  ? `
   Review note: ${t.note}`
                                  : ""
                          }`,
                  )
                  .join("\n\n")
            : "No reviewed terms in this category.";
    const scope = accepted.filter((t) => t.category === "scope"),
        payments = accepted.filter((t) => t.category === "payment"),
        dates = accepted.filter((t) => t.category === "deadline"),
        revisions = accepted.filter((t) => t.category === "revision"),
        changes = terms.filter(
            (t) => t.category === "change" && t.status !== "rejected",
        );
    const header = `${kind.toUpperCase()}
${c.name}
Prepared: ${/* @__PURE__ */ new Date().toLocaleDateString("en-GB")}

Draft for review. This document is not signed.

`;
    const open = `OPEN QUESTIONS
${pending.map((t) => `\u2022 ${t.detail}`).join("\n") || "None recorded."}`;
    if (kind === "Follow-up email")
        return `Subject: Confirming the next steps for ${c.name}

Hi,

I would like to confirm our current understanding of the project.

${details(accepted)}

Could you please clarify the following?

${pending.map((t) => `\u2022 ${t.detail}`).join("\n") || "Please confirm the reviewed terms above."}

Once confirmed, I can update the delivery plan.

Thanks,
[Your name]`;
    if (kind === "Change request")
        return (
            header +
            `PROPOSED CHANGES
${details(changes)}

BASELINE SCOPE
${details(scope)}

COST & TIMELINE IMPACT
[Review and fill in the additional fee, dependencies, and revised dates before sending.]

${open}

APPROVAL
Freelancer: __________________
Client: _____________________
Date: _______________________`
        );
    if (kind === "Project proposal")
        return (
            header +
            `PROJECT OBJECTIVE
[Add the client's objective and success criteria.]

PROPOSED APPROACH
${details(scope)}

BUDGET
${details(payments)}

TIMELINE
${details(dates)}

${open}

NEXT STEP
Please confirm the deliverables, budget, and timeline before formalizing the agreement.`
        );
    if (kind === "Agreement summary")
        return (
            header +
            `REVIEWED TERMS
${details(accepted)}

${open}

SOURCE
${c.name}
This summary reflects the statuses reviewed in the app; inspect the original messages for context.`
        );
    return (
        header +
        `DELIVERABLES & SCOPE
${details(scope)}

PAYMENT TERMS
${details(payments)}

SCHEDULE & DEPENDENCIES
${details(dates)}

REVISION LIMITS
${details(revisions)}

OTHER REVIEWED TERMS
${details(accepted.filter((t) => !["scope", "payment", "deadline", "revision"].includes(t.category)))}

CHANGE CONTROL
Confirm changes to scope, fees, or delivery dates before proceeding.

${open}

APPROVAL
Freelancer: __________________ Date: __________
Client: _____________________ Date: __________`
    );
}
function calendarExport(tasks) {
    const esc = (s) =>
        s
            .replace(/\\/g, "\\\\")
            .replace(/\n/g, "\\n")
            .replace(/,/g, "\\,")
            .replace(/;/g, "\\;");
    return [
        "BEGIN:VCALENDAR",
        "VERSION:2.0",
        "PRODID:-//Clarity//Project Milestones//EN",
        ...tasks
            .filter((t) => /^\d{4}-\d{2}-\d{2}$/.test(t.due))
            .flatMap((t) => [
                "BEGIN:VEVENT",
                `UID:${t.id}@clarity.local`,
                `DTSTAMP:${/* @__PURE__ */ new Date()
                    .toISOString()
                    .replace(/[-:]/g, "")
                    .replace(/\.\d+Z/, "Z")}`,
                `DTSTART;VALUE=DATE:${t.due.replace(/-/g, "")}`,
                `SUMMARY:${esc(t.title)}`,
                `DESCRIPTION:${esc(t.note)}`,
                "END:VEVENT",
            ]),
        "END:VCALENDAR",
    ].join("\r\n");
}
export { calendarExport, documentDraft };
