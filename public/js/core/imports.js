import {
    parseCSV,
    parseJSON,
    parseRows,
    parseText,
    deduplicate,
} from "./domain.js";
async function importFiles(
    files,
    order,
    onProgress,
    limits = { maxUploadMB: 20, maxImportMessages: 1e5 },
) {
    const all = [];
    for (const file of files) {
        if (file.size > limits.maxUploadMB * 1024 * 1024)
            throw new Error(
                `${file.name}: maximum file size is ${limits.maxUploadMB} MB.`,
            );
        onProgress(`Reading ${file.name}\u2026`);
        const ext = file.name.split(".").pop()?.toLowerCase();
        if (ext === "xlsx") {
            const { default: ExcelJS } =
                await import("../../vendor/exceljs.mjs");
            const workbook = new ExcelJS.Workbook();
            await workbook.xlsx.load(await file.arrayBuffer());
            const sheet = workbook.worksheets[0];
            if (!sheet) throw new Error("The workbook has no sheets.");
            const rows = [];
            sheet.eachRow((r) =>
                rows.push(
                    r.values
                        .slice(1)
                        .map((v) =>
                            v && typeof v === "object" && "text" in v
                                ? String(v.text)
                                : v && typeof v === "object" && "result" in v
                                  ? v.result
                                  : v instanceof Date
                                    ? v.toISOString()
                                    : v,
                        ),
                ),
            );
            all.push(...parseRows(rows, file.name, order));
        } else if (ext === "csv")
            all.push(
                ...parseRows(parseCSV(await file.text()), file.name, order),
            );
        else if (ext === "json")
            all.push(...parseJSON(await file.text(), file.name));
        else if (ext === "txt")
            all.push(...parseText(await file.text(), file.name, order));
        else
            throw new Error(
                `${file.name}: use TXT, CSV, XLSX, JSON, or the screenshot extraction tab.`,
            );
    }
    if (all.length > limits.maxImportMessages)
        throw new Error(
            `Limit each import to ${limits.maxImportMessages.toLocaleString()} messages. Split larger exports.`,
        );
    return deduplicate(all).sort((a, b) =>
        a.timestamp && b.timestamp ? a.timestamp.localeCompare(b.timestamp) : 0,
    );
}
async function screenshotText(files, language, onProgress) {
    const { createWorker } = await import("../../vendor/tesseract.mjs");
    const worker = await createWorker(language, 1, {
        logger: (m) =>
            onProgress(
                `${m.status} \xB7 ${Math.round((m.progress || 0) * 100)}%`,
            ),
    });
    try {
        const parts = [];
        for (const file of files) {
            if (file.size > 10 * 1024 * 1024)
                throw new Error("Screenshots must be under 10 MB.");
            const { data } = await worker.recognize(file);
            parts.push(data.text);
        }
        return parts.join("\n\n");
    } finally {
        await worker.terminate();
    }
}
export { importFiles, screenshotText };
