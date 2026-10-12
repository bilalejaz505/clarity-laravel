// Version-one backup validation. Shared shape with Laravel's ValidWorkspace rule.
const object = (value) =>
    value !== null && typeof value === "object" && !Array.isArray(value);
const check = (condition, message) => {
    if (!condition) throw new Error(message);
};
const strings = (value, keys) => {
    check(object(value), "Invalid workspace record.");
    for (const key of keys)
        check(typeof value[key] === "string", `Invalid ${key}.`);
};
const list = (value, limit = Infinity) =>
    check(
        Array.isArray(value) && value.length <= limit,
        "Invalid collection or collection limit.",
    );
const choice = (value, choices) =>
    check(choices.includes(value), "Invalid workspace status.");
const amount = (value) =>
    check(
        typeof value === "number" && Number.isFinite(value) && value >= 0,
        "Invalid amount.",
    );
const evidence = (value) => {
    list(value);
    for (const id of value)
        check(typeof id === "string", "Invalid evidence reference.");
};

export const workspaceSchema = {
    parse(value) {
        check(object(value), "Invalid workspace.");
        list(value.conversations, 200);
        list(value.tasks);
        list(value.invoices);
        for (const conversation of value.conversations) {
            strings(conversation, [
                "id",
                "name",
                "platform",
                "purpose",
                "created",
            ]);
            choice(conversation.purpose, ["personal", "freelance"]);
            check(
                typeof conversation.sample === "boolean",
                "Invalid sample flag.",
            );
            list(conversation.messages, 100000);
            for (const message of conversation.messages) {
                strings(message, ["id", "sender", "text", "source"]);
                check(
                    message.timestamp === null ||
                        (typeof message.timestamp === "string" &&
                            /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/.test(
                                message.timestamp,
                            ) &&
                            Number.isFinite(Date.parse(message.timestamp))),
                    "Invalid timestamp.",
                );
            }
            if (conversation.analysis !== null) {
                const analysis = conversation.analysis;
                strings(analysis, ["summary"]);
                choice(analysis.engine, ["local", "ai"]);
                check(
                    typeof analysis.analyzedCount === "number" &&
                        Number.isFinite(analysis.analyzedCount),
                    "Invalid analysis count.",
                );
                list(analysis.insights);
                list(analysis.terms);
                for (const insight of analysis.insights) {
                    strings(insight, [
                        "id",
                        "title",
                        "detail",
                        "interpretation",
                    ]);
                    choice(insight.kind, ["positive", "attention", "neutral"]);
                    evidence(insight.evidence);
                }
                for (const term of analysis.terms) {
                    strings(term, [
                        "id",
                        "title",
                        "detail",
                        "due",
                        "currency",
                        "note",
                    ]);
                    choice(term.category, [
                        "scope",
                        "payment",
                        "deadline",
                        "revision",
                        "change",
                        "other",
                    ]);
                    choice(term.status, [
                        "proposed",
                        "accepted",
                        "unclear",
                        "rejected",
                    ]);
                    evidence(term.evidence);
                    check(
                        typeof term.completed === "boolean",
                        "Invalid completion flag.",
                    );
                    if (term.amount !== null) amount(term.amount);
                }
            }
        }
        for (const task of value.tasks) {
            strings(task, ["id", "title", "due", "conversationId", "note"]);
            choice(task.status, ["todo", "doing", "done"]);
        }
        for (const invoice of value.invoices) {
            strings(invoice, [
                "id",
                "conversationId",
                "client",
                "description",
                "currency",
                "due",
            ]);
            amount(invoice.amount);
            choice(invoice.status, ["draft", "sent", "paid"]);
        }
        if (value.reviews !== undefined) {
            list(value.reviews);
            for (const review of value.reviews)
                strings(review, [
                    "id",
                    "conversationId",
                    "termTitle",
                    "from",
                    "to",
                    "at",
                ]);
        }
        return structuredClone(value);
    },
};
