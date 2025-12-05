import TurndownService from "turndown";
import * as turndownPluginGfm from "joplin-turndown-plugin-gfm";

function cleanAttribute(attribute: string) {
    return attribute ? attribute.replace(/(\n+\s*)+/g, "\n") : "";
}
function generateRandomString() {
    // 使用随机数生成器生成16个字节长的数组
    const array = new Uint8Array(16);
    crypto.getRandomValues(array);

    // 将字节转换为十六进制字符串
    const hexString = Array.from(array)
        .map((byte) => byte.toString(16).padStart(2, "0"))
        .join("");

    return hexString;
}
export const html2md = (html: string): string => {
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

    try {
        turndownService.remove(["i", "script", "iframe", "img"]);
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
                const alt = cleanAttribute(node.getAttribute("alt"));
                const src = node.getAttribute("src") || "";
                const title = cleanAttribute(node.getAttribute("title"));
                const titlePart = title ? ' "' + title + '"' : "";
                return src
                    ? "![" + alt + "]" + "(/uploads/images/" + generateRandomString() + ".png" + titlePart + ")"
                    : "";
            },
        });
        turndownService.addRule("brElement", {
            filter: "br",
            replacement(_, node) {
                return "";
            },
        });

        turndownService.use(turndownPluginGfm.gfm);

        return turndownService.turndown(html);
    } catch (error) {
        console.log("html 2 markdown error", error);
        return "";
    }
};
