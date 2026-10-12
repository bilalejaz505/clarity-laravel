const uid = () => globalThis.crypto.randomUUID();
function displayTime(value) {
    return new Date(value).toLocaleString("en-GB", {
        timeZone: "UTC",
        dateStyle: "medium",
        timeStyle: "short",
    });
}
function parseDate(value, order = "DMY") {
    if (!value?.trim()) return null;
    if (/^\d{4}-\d{2}-\d{2}/.test(value)) {
        const d2 = new Date(value);
        return Number.isNaN(+d2) ? null : d2.toISOString();
    }
    const m = value
        .replace(/[\u200e\u200f\u202f]/g, " ")
        .match(
            /(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{2,4})(?:[, ]+(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM)?)?/i,
        );
    if (!m) return null;
    let [, a, b, y, h = "0", mi = "0", sec = "0", ampm] = m;
    let year = +y;
    if (year < 100) year += 2e3;
    let hour = +h;
    if (ampm) hour = (hour % 12) + (/pm/i.test(ampm) ? 12 : 0);
    const day = order === "MDY" ? +b : +a,
        month = order === "MDY" ? +a : +b;
    const d = new Date(Date.UTC(year, month - 1, day, hour, +mi, +sec));
    if (
        d.getUTCFullYear() !== year ||
        d.getUTCMonth() !== month - 1 ||
        d.getUTCDate() !== day ||
        hour > 23 ||
        +mi > 59
    )
        return null;
    return d.toISOString();
}
function parseCSV(text) {
    const rows = [];
    let row = [],
        cell = "",
        quoted = false;
    for (let i = 0; i < text.length; i++) {
        const c = text[i];
        if (c === '"') {
            if (quoted && text[i + 1] === '"') {
                cell += '"';
                i++;
            } else quoted = !quoted;
        } else if (c === "," && !quoted) {
            row.push(cell);
            cell = "";
        } else if ((c === "\n" || c === "\r") && !quoted) {
            if (c === "\r" && text[i + 1] === "\n") i++;
            row.push(cell);
            if (row.some((v) => v.trim())) rows.push(row);
            row = [];
            cell = "";
        } else cell += c;
    }
    if (quoted) throw new Error("Unclosed quoted field in CSV.");
    row.push(cell);
    if (row.some((v) => v.trim())) rows.push(row);
    return rows;
}
function parseRows(rows, source, order = "DMY") {
    if (rows.length < 2)
        throw new Error("Include a header row and at least one message.");
    const headers = rows[0].map((v) =>
        String(v ?? "")
            .trim()
            .toLowerCase(),
    );
    const sender = headers.findIndex((h) =>
        ["sender", "from", "author", "name", "participant"].includes(h),
    );
    const body = headers.findIndex((h) =>
        ["text", "message", "content", "body"].includes(h),
    );
    const date = headers.findIndex((h) =>
        ["date", "timestamp", "datetime", "time"].includes(h),
    );
    if (sender < 0 || body < 0)
        throw new Error(
            "Expected columns: sender, text, timestamp (optional).",
        );
    return rows
        .slice(1)
        .filter((r) => String(r[body] ?? "").trim())
        .map((r, i) => ({
            id: uid(),
            sender: String(r[sender] ?? "Unknown"),
            text: String(r[body]),
            timestamp:
                date < 0 ? null : parseDate(String(r[date] ?? ""), order),
            source: `${source}:row ${i + 2}`,
        }));
}
function parseText(text, source, order = "DMY") {
    const out = [];
    for (const [index, line] of text.replace(/\r/g, "").split("\n").entries()) {
        const clean = line.replace(/[\u200e\u200f]/g, "");
        const whatsapp = clean.match(
            /^(?:\[([^\]]+)\]|(\d{1,2}[/.\-]\d{1,2}[/.\-]\d{2,4},?\s+\d{1,2}:\d{2}(?::\d{2})?\s*(?:AM|PM)?)\s*-)[ ]*([^:]+):\s*(.*)$/i,
        );
        if (whatsapp) {
            out.push({
                id: uid(),
                sender: whatsapp[3].trim(),
                text: whatsapp[4],
                timestamp: parseDate(whatsapp[1] || whatsapp[2], order),
                source: `${source}:line ${index + 1}`,
            });
            continue;
        }
        if (/^\[?\d{1,2}[/.\-]\d{1,2}[/.\-]\d{2,4}/.test(clean)) continue;
        const simple = clean.match(/^([^:\[\]]{1,60}):\s*(.+)$/);
        if (simple && !/^\d{1,2}:/.test(clean))
            out.push({
                id: uid(),
                sender: simple[1].trim(),
                text: simple[2],
                timestamp: null,
                source: `${source}:line ${index + 1}`,
            });
        else if (
            out.length &&
            clean.trim() &&
            !/^\[?\d{1,2}[/.\-]\d{1,2}[/.\-]\d{2,4}/.test(clean)
        )
            out[out.length - 1].text += "\n" + clean;
    }
    if (!out.length)
        throw new Error(
            "No messages recognized. Use WhatsApp export text or one \u201CSender: message\u201D per line.",
        );
    return out;
}
function parseJSON(text, source) {
    const parsed = JSON.parse(text);
    const rows = Array.isArray(parsed) ? parsed : parsed.messages;
    if (!Array.isArray(rows))
        throw new Error(
            "Expected a message array, Telegram export, or Instagram messages JSON.",
        );
    return rows
        .filter((r) => r.text || r.content || r.message)
        .map((r, i) => ({
            id: uid(),
            sender: String(
                r.sender || r.sender_name || r.from || r.author || "Unknown",
            ),
            text: Array.isArray(r.text)
                ? r.text
                      .map((t) => (typeof t === "string" ? t : t.text || ""))
                      .join("")
                : String(r.text || r.content || r.message),
            timestamp: r.timestamp_ms
                ? new Date(r.timestamp_ms).toISOString()
                : r.date_unixtime
                  ? new Date(+r.date_unixtime * 1e3).toISOString()
                  : parseDate(String(r.timestamp || r.date || "")),
            source: `${source}:message ${i + 1}`,
        }))
        .sort((a, b) =>
            a.timestamp && b.timestamp
                ? a.timestamp.localeCompare(b.timestamp)
                : 0,
        );
}
function deduplicate(messages) {
    const seen = /* @__PURE__ */ new Set();
    return messages.filter((m) => {
        if (!m.timestamp) return true;
        const key = JSON.stringify([m.sender, m.timestamp, m.text]);
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
    });
}
function statistics(messages) {
    const people = [...new Set(messages.map((m) => m.sender))];
    const participants = people.map((name) => ({
        name,
        count: 0,
        words: 0,
        questions: 0,
        initiations: 0,
        responses: [],
    }));
    const days = {},
        hours = Array(24).fill(0),
        emojis = {},
        words = {};
    let dated = 0,
        sessions = 0;
    let prev;
    for (const m of messages) {
        const p = participants.find((p2) => p2.name === m.sender);
        p.count++;
        p.words += m.text.trim().split(/\s+/).filter(Boolean).length;
        p.questions += (m.text.match(/[?؟]/g) || []).length;
        for (const e of m.text.match(/\p{Extended_Pictographic}/gu) || [])
            emojis[e] = (emojis[e] || 0) + 1;
        for (const w of m.text.toLowerCase().match(/[\p{L}]{4,}/gu) || [])
            if (
                ![
                    "this",
                    "that",
                    "with",
                    "have",
                    "your",
                    "will",
                    "just",
                    "from",
                    "what",
                    "would",
                    "there",
                    "about",
                    "could",
                    "thanks",
                ].includes(w)
            )
                words[w] = (words[w] || 0) + 1;
        if (m.timestamp) {
            dated++;
            const d = new Date(m.timestamp);
            const day = m.timestamp.slice(0, 10);
            days[day] = (days[day] || 0) + 1;
            hours[d.getUTCHours()]++;
            const gap = prev?.timestamp
                ? (+d - +new Date(prev.timestamp)) / 6e4
                : null;
            if (!prev || gap === null || gap > 480) {
                p.initiations++;
                sessions++;
            }
            if (
                prev?.timestamp &&
                prev.sender !== m.sender &&
                gap !== null &&
                gap >= 0 &&
                gap < 1440
            )
                p.responses.push(gap);
        }
        prev = m;
    }
    const top = (obj) =>
        Object.entries(obj)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 8);
    return {
        total: messages.length,
        dated,
        sessions,
        participants: participants.map((p) => ({
            ...p,
            medianReply: p.responses.length
                ? [...p.responses].sort((a, b) => a - b)[
                      Math.floor(p.responses.length / 2)
                  ]
                : null,
        })),
        days: Object.entries(days).sort((a, b) => a[0].localeCompare(b[0])),
        hours,
        emojis: top(emojis),
        words: top(words),
    };
}
const signalRules = [
    {
        title: "Appreciation expressed",
        kind: "positive",
        pattern:
            /\b(thank you|thanks|appreciate|proud of you|well done|shukriya)\b/i,
        detail: "Messages contain explicit appreciation. This identifies wording, not overall character.",
    },
    {
        title: "Support offered",
        kind: "positive",
        pattern:
            /\b(here for you|help you|take your time|understand how|no pressure)\b/i,
        detail: "Messages include offers of help or space. Consider their surrounding context.",
    },
    {
        title: "Potentially hurtful wording",
        kind: "attention",
        pattern: /\b(stupid|idiot|useless|shut up|worthless)\b/i,
        detail: "Potential insults appear. Quoting, joking, and context can change their meaning.",
    },
    {
        title: "Pressure-related wording",
        kind: "attention",
        pattern:
            /\b(if you loved|prove you|you must|right now or|your fault|always your fault)\b/i,
        detail: "These phrases may convey pressure. Review the exchange before interpreting intent.",
    },
    {
        title: "An apology appears",
        kind: "neutral",
        pattern: /\b(sorry|apologize|my mistake|my fault)\b/i,
        detail: "An apology is expressed. The conversation alone does not establish follow-through.",
    },
    {
        title: "A boundary is expressed",
        kind: "neutral",
        pattern:
            /\b(need space|not comfortable|please stop|cannot do|can't do|not okay)\b/i,
        detail: "A participant expresses a limit. Check subsequent messages for how it was handled.",
    },
];
function localAnalysis(messages) {
    const insights = signalRules.flatMap((r) => {
        const matches = messages.filter((m) => r.pattern.test(m.text));
        return matches.length
            ? [
                  {
                      id: uid(),
                      title: r.title,
                      kind: r.kind,
                      detail: `${matches.length} matching message${matches.length === 1 ? "" : "s"}. ${r.detail}`,
                      evidence: matches.slice(0, 5).map((m) => m.id),
                      interpretation:
                          "Local phrase match; contextual interpretation requires AI review.",
                  },
              ]
            : [];
    });
    const termRules = [
        [
            "payment",
            /\b(payment|deposit|invoice|paid|price|budget|usd|pkr|eur|gbp|dollars)\b|[$£€]/i,
        ],
        [
            "deadline",
            /\b(deadline|due|deliver by|by friday|by monday|tomorrow|next week|delivery date)\b/i,
        ],
        ["revision", /\b(revision|revisions|rounds of|changes included)\b/i],
        [
            "change",
            /\b(additionally|also add|extra page|out of scope|scope change|new requirement)\b/i,
        ],
        [
            "scope",
            /\b(deliverable|build|design|landing page|website|logo|provide|includes|scope)\b/i,
        ],
    ];
    const terms = [];
    for (const m of messages) {
        const rule = termRules.find(([, r]) => r.test(m.text));
        if (!rule) continue;
        const money = m.text.match(
            /(?:\b(USD|PKR|EUR|GBP)\s*|([$£€]))([\d,]+(?:\.\d{1,2})?)/i,
        );
        terms.push({
            id: uid(),
            category: rule[0],
            title: m.text.slice(0, 85),
            detail: m.text,
            status: "unclear",
            evidence: [m.id],
            due: "",
            amount:
                rule[0] === "payment" && money
                    ? Number(money[3].replace(/,/g, ""))
                    : null,
            currency:
                money?.[1]?.toUpperCase() ||
                (money?.[2] === "\xA3"
                    ? "GBP"
                    : money?.[2] === "\u20AC"
                      ? "EUR"
                      : "UNKNOWN"),
            completed: false,
            note: "",
        });
    }
    return {
        summary: `${messages.length.toLocaleString()} messages across ${new Set(messages.map((m) => m.sender)).size} participants. Local results identify explicit phrases and candidate terms. Review the evidence; agreement and intent are not inferred.`,
        insights,
        terms,
        engine: "local",
        analyzedCount: messages.length,
    };
}
function mergeAnalyses(parts, count) {
    return {
        summary: parts
            .map((p, i) => `Part ${i + 1}: ${p.summary}`)
            .join("\n\n"),
        insights: parts.flatMap((p) => p.insights),
        terms: parts.flatMap((p) => p.terms),
        engine: "ai",
        analyzedCount: count,
    };
}
function sampleStore() {
    const personal = `[01/10/2026, 09:00] Alex: Hey, how did your presentation go?
[01/10/2026, 09:14] Sam: It went really well! Thanks for checking in \u{1F60A}
[01/10/2026, 09:16] Alex: Proud of you. Want to celebrate this weekend?
[01/10/2026, 09:32] Sam: Yes! Saturday afternoon works.
[02/10/2026, 18:00] Sam: How was your day?
[02/10/2026, 18:08] Alex: A bit stressful. Could use a quiet evening.
[02/10/2026, 18:10] Sam: I understand. No pressure, take your time.
[03/10/2026, 11:00] Alex: Sorry, I need to move our plan to Sunday.
[03/10/2026, 11:30] Sam: I'm disappointed. I had set aside Saturday.
[03/10/2026, 11:40] Alex: You're right. My mistake for changing it late.
[03/10/2026, 11:43] Sam: Thank you for understanding. Sunday at 3?
[03/10/2026, 11:48] Alex: Agreed. I'll book the cafe.
[05/10/2026, 10:00] Sam: Thanks for yesterday \u2764\uFE0F
[05/10/2026, 10:03] Alex: Loved it. How is your new project going?
[06/10/2026, 20:00] Sam: Please stop messaging when I'm in meetings.
[06/10/2026, 20:10] Alex: Sorry. I'll check in after work instead.
[08/10/2026, 18:00] Alex: How did today's meeting go?
[08/10/2026, 18:12] Sam: Much better. Appreciate you remembering.`;
    const client = `[01/10/2026, 10:00] Morgan: We need a landing page and three product pages for our studio.
[01/10/2026, 10:15] Taylor: I can design and build those four pages for USD 1800, with two revision rounds.
[01/10/2026, 10:25] Morgan: Agreed on USD 1800 and two revisions.
[01/10/2026, 10:30] Taylor: The deposit is USD 900 before work starts, with USD 900 after approval.
[01/10/2026, 10:35] Morgan: Yes, that payment schedule works.
[02/10/2026, 09:00] Taylor: Please provide final copy and images by October 8.
[02/10/2026, 09:20] Morgan: I will provide the copy and images by October 8.
[03/10/2026, 14:00] Taylor: The first design delivery date is October 16, 2026.
[03/10/2026, 14:05] Morgan: October 16 is fine.
[06/10/2026, 11:00] Morgan: Can we also add a booking system and an extra page?
[06/10/2026, 11:12] Taylor: Those are out of scope. I will quote them separately.
[07/10/2026, 09:00] Morgan: The images may be late. Could we keep the deadline?
[07/10/2026, 09:15] Taylor: Let's confirm a new schedule once the images arrive.
[09/10/2026, 12:00] Morgan: Thanks for keeping everything clear. The deposit has been paid.`;
    const make = (name, text, purpose) => {
        const messages = parseText(text, "Sample WhatsApp export");
        return {
            id: uid(),
            name,
            platform: "WhatsApp",
            purpose,
            messages,
            created: /* @__PURE__ */ new Date().toISOString(),
            analysis: localAnalysis(messages),
            sample: true,
        };
    };
    return {
        conversations: [
            make("Alex & Sam", personal, "personal"),
            make("Studio website \xB7 Morgan", client, "freelance"),
        ],
        tasks: [],
        invoices: [],
    };
}
function exportCSV(rows) {
    if (!rows.length) return "";
    const keys = Object.keys(rows[0]);
    const quote = (v) => {
        let s = String(v ?? "");
        if (/^[=+@\-\t\r]/.test(s)) s = "'" + s;
        return '"' + s.replace(/"/g, '""') + '"';
    };
    return [
        keys.map(quote).join(","),
        ...rows.map((r) => keys.map((k) => quote(r[k])).join(",")),
    ].join("\r\n");
}
export {
    deduplicate,
    displayTime,
    exportCSV,
    localAnalysis,
    mergeAnalyses,
    parseCSV,
    parseDate,
    parseJSON,
    parseRows,
    parseText,
    sampleStore,
    statistics,
    uid,
};
