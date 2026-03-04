import TurndownService from "turndown";
import * as turndownPluginGfm from "joplin-turndown-plugin-gfm";
import { uploadImage } from "@/api/app";

function cleanAttribute(attribute: string) {
    return attribute ? attribute.replace(/(\n+\s*)+/g, "\n") : "";
}

/**
 * 将 base64 字符串转换为 File 对象
 * 支持格式: data:<mimeType>;base64,<data> 或纯 base64 字符串
 */
function base64ToFile(base64Str: string, filename: string): File {
    const matches = base64Str.match(/^data:([A-Za-z-+/]+);base64,(.+)$/);

    let mimeType = "image/png";
    let base64Data = base64Str;

    if (matches) {
        mimeType = matches[1];
        base64Data = matches[2];
    }

    const byteCharacters = atob(base64Data);
    const byteArrays: Uint8Array[] = [];

    // 分片处理，避免大图片内存溢出
    for (let offset = 0; offset < byteCharacters.length; offset += 512) {
        const slice = byteCharacters.slice(offset, offset + 512);
        const byteNumbers = new Array(slice.length);
        for (let i = 0; i < slice.length; i++) {
            byteNumbers[i] = slice.charCodeAt(i);
        }
        byteArrays.push(new Uint8Array(byteNumbers));
    }
    // @ts-ignore
    const blob = new Blob(byteArrays, { type: mimeType });
    return new File([blob], filename, { type: mimeType });
}

/**
 * 预处理 HTML：将其中 base64 图片上传至服务器，并将 src 替换为远程 URL
 * 由于 turndown 的 replacement 不支持 async，需在转换前提前处理图片
 */
async function replaceBase64Images(html: string): Promise<string> {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");
    const imgElements = doc.querySelectorAll("img");

    const uploadTasks = Array.from(imgElements).map(async (img) => {
        const src = img.getAttribute("src") || "";
        // 仅处理 base64 图片，跳过远程 URL
        if (!src.startsWith("data:")) return;

        try {
            const file = base64ToFile(src, `image-${Date.now()}.png`);
            const res = await uploadImage({ file });
            // 将 src 替换为上传后的远程 URL
            img.setAttribute("src", res.uri);
        } catch (error) {
            console.error("图片上传失败:", error);
        }
    });

    await Promise.all(uploadTasks);

    return doc.body.innerHTML;
}

export const html2md = async (html: string): Promise<string> => {
    try {
        // Step 1: 预处理，上传 base64 图片并替换为远程 URL
        const processedHtml = await replaceBase64Images(html);

        // Step 2: 初始化 turndown
        const turndownService = new TurndownService({
            headingStyle: "atx",
            bulletListMarker: "-",
            codeBlockStyle: "fenced",
            fence: "```",
            emDelimiter: "_",
            strongDelimiter: "**",
            linkStyle: "inlined",
            linkReferenceStyle: "full",
        });

        // 移除不需要的标签（注意：img 不在此列，由 imgElement rule 处理）
        turndownService.remove(["i", "script", "iframe"]);

        turndownService.addRule("codeBlock", {
            filter: "pre",
            replacement(_, node) {
                const content = node.textContent?.trim() || "";
                // @ts-ignore
                const codeName = node?._attrsByQName?.class?.data?.trim() || "";
                return `\n\`\`\`${codeName}\n${content}\n\`\`\`\n`;
            },
        });

        turndownService.addRule("imgElement", {
            filter: "img",
            replacement(_, node) {
                const alt = cleanAttribute(node.getAttribute("alt") || "");
                const src = node.getAttribute("src") || "";
                const title = cleanAttribute(node.getAttribute("title") || "");
                const titlePart = title ? ` "${title}"` : "";
                // 此时 src 已经是上传后的远程 URL，直接输出 markdown 图片语法
                return src ? `![${alt}](${src}${titlePart})` : "";
            },
        });

        turndownService.addRule("brElement", {
            filter: "br",
            replacement() {
                return "";
            },
        });

        turndownService.use(turndownPluginGfm.gfm);

        return turndownService.turndown(processedHtml);
    } catch (error) {
        console.log("html 2 markdown error", error);
        return "";
    }
};
