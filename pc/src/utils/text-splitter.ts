export const CUSTOM_SPLIT_SIGN = "-----CUSTOM_SPLIT_SIGN-----";

type SplitProps = {
    text: string;
    chunkLen: number;
    overlapRatio?: number;
    customReg?: string[];
};
export type TextSplitProps = Omit<SplitProps, "text" | "chunkLen"> & {
    chunkLen?: number;
};

type SplitResponse = {
    chunks: string[];
    chars: number;
};

export const replaceRegChars = (text: string) => text.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

// 判断字符串是否为markdown的表格形式
const strIsMdTable = (str: string) => {
    // 检查是否包含表格分隔符 |
    if (!str.includes("|")) {
        return false;
    }

    const lines = str.split("\n");

    // 检查表格是否至少有两行
    if (lines.length < 2) {
        return false;
    }

    // 检查表头行是否包含 |
    const headerLine = lines[0].trim();
    if (!headerLine.startsWith("|") || !headerLine.endsWith("|")) {
        return false;
    }

    // 检查分隔行是否由 | 和 - 组成
    const separatorLine = lines[1].trim();
    const separatorRegex = /^(\|[\s:]*-+[\s:]*)+\|$/;
    if (!separatorRegex.test(separatorLine)) {
        return false;
    }

    // 检查数据行是否包含 |
    for (let i = 2; i < lines.length; i++) {
        const dataLine = lines[i].trim();
        if (dataLine && (!dataLine.startsWith("|") || !dataLine.endsWith("|"))) {
            return false;
        }
    }

    return true;
};
const markdownTableSplit = (props: SplitProps): SplitResponse => {
    const { text = "", chunkLen } = props;
    const splitText2Lines = text.split("\n");
    const header = splitText2Lines[0];
    const headerSize = header.split("|").length - 2;

    const mdSplitString = `| ${new Array(headerSize > 0 ? headerSize : 1)
        .fill(0)
        .map(() => "---")
        .join(" | ")} |`;

    const chunks: string[] = [];
    let chunk = `${header}
                ${mdSplitString}
                `;
    for (let i = 2; i < splitText2Lines.length; i++) {
        if (chunk.length + splitText2Lines[i].length > chunkLen * 1.2) {
            chunks.push(chunk);
            chunk = `${header}
                    ${mdSplitString}
                    `;
        }
        chunk += `${splitText2Lines[i]}\n`;
    }

    if (chunk) {
        chunks.push(chunk);
    }

    return {
        chunks,
        chars: chunks.reduce((sum, chunk) => sum + chunk.length, 0),
    };
};

// 预编译正则表达式
const preCompiledRegExps = {
    codeBlock: /(```[\s\S]*?```|~~~[\s\S]*?~~~)/g,
    multipleNewlines: /(\r?\n|\r){3,}/g,
};

export const commonSplit = (props: SplitProps): SplitResponse => {
    // eslint-disable-next-line prefer-const
    let { text = "", chunkLen, overlapRatio = 0.2, customReg = [] } = props;

    const splitMarker = "SPLIT_HERE_SPLIT_HERE";
    const codeBlockMarker = "CODE_BLOCK_LINE_MARKER";
    const overlapLen = Math.round(chunkLen * overlapRatio);
    const MAX_RECURSION_DEPTH = 50; // 限制递归深度

    // 预处理：保护代码块
    text = text.replace(preCompiledRegExps.codeBlock, function (match) {
        return match.replace(/\n/g, codeBlockMarker);
    });

    // 预处理：规范化换行符
    text = text.replace(preCompiledRegExps.multipleNewlines, "\n\n\n");

    // 定义分割规则
    const stepReges: { reg: RegExp; maxLen: number }[] = [
        ...customReg.map((text) => ({
            reg: new RegExp(`(${replaceRegChars(text)})`, "g"),
            maxLen: chunkLen * 1.4,
        })),
        { reg: /^(#\s[^\n]+)\n/gm, maxLen: chunkLen * 1.2 },
        { reg: /^(##\s[^\n]+)\n/gm, maxLen: chunkLen * 1.2 },
        { reg: /^(###\s[^\n]+)\n/gm, maxLen: chunkLen * 1.2 },
        { reg: /^(####\s[^\n]+)\n/gm, maxLen: chunkLen * 1.2 },
        { reg: /([\n]([`~]))/g, maxLen: chunkLen * 4 },
        { reg: /([\n](?!\s*[\*\-|>0-9]))/g, maxLen: chunkLen * 2 },
        { reg: /([\n])/g, maxLen: chunkLen * 1.2 },
        { reg: /([。]|([a-zA-Z])\.\s)/g, maxLen: chunkLen * 1.2 },
        { reg: /([！]|!\s)/g, maxLen: chunkLen * 1.2 },
        { reg: /([？]|\?\s)/g, maxLen: chunkLen * 1.4 },
        { reg: /([；]|;\s)/g, maxLen: chunkLen * 1.6 },
        { reg: /([，]|,\s)/g, maxLen: chunkLen * 2 },
    ];

    const customRegLen = customReg.length;
    const checkIsCustomStep = (step: number) => step < customRegLen;
    const checkIsMarkdownSplit = (step: number) => step >= customRegLen && step <= 3 + customRegLen;
    const checkIndependentChunk = (step: number) => step >= customRegLen && step <= 4 + customRegLen;
    const checkForbidOverlap = (step: number) => step <= 6 + customRegLen;

    // 获取分割文本
    const getSplitTexts = ({ text, step }: { text: string; step: number }) => {
        if (step >= stepReges.length) {
            return [{ text, title: "" }];
        }

        const isCustomSteep = checkIsCustomStep(step);
        const isMarkdownSplit = checkIsMarkdownSplit(step);
        const independentChunk = checkIndependentChunk(step);
        const { reg } = stepReges[step];

        const splitTexts = text
            .replace(
                reg,
                (() => {
                    if (isCustomSteep) return splitMarker;
                    if (independentChunk) return `${splitMarker}$1`;
                    return `$1${splitMarker}`;
                })()
            )
            .split(`${splitMarker}`)
            .filter((part) => part.trim());

        return splitTexts
            .map((text) => {
                const matchTitle = isMarkdownSplit ? text.match(reg)?.[0] || "" : "";

                return {
                    text: isMarkdownSplit ? text.replace(matchTitle, "") : text,
                    title: matchTitle,
                };
            })
            .filter((item) => item.text.trim());
    };

    // 获取重叠文本
    const getOneTextOverlapText = ({ text, step }: { text: string; step: number }): string => {
        const forbidOverlap = checkForbidOverlap(step);
        const maxOverlapLen = chunkLen * 0.4;

        if (forbidOverlap || overlapLen === 0 || step >= stepReges.length) return "";

        const splitTexts = getSplitTexts({ text, step });
        let overlayText = "";

        for (let i = splitTexts.length - 1; i >= 0; i--) {
            const currentText = splitTexts[i].text;
            const newText = currentText + overlayText;
            const newTextLen = newText.length;

            if (newTextLen > overlapLen) {
                if (newTextLen > maxOverlapLen) {
                    const text = getOneTextOverlapText({
                        text: newText,
                        step: step + 1,
                    });
                    return text || overlayText;
                }
                return newText;
            }

            overlayText = newText;
        }
        return overlayText;
    };

    // 主要的分割函数（带递归深度限制）
    const splitTextRecursively = ({
        text = "",
        step,
        lastText,
        mdTitle = "",
        depth = 0,
    }: {
        text: string;
        step: number;
        lastText: string;
        mdTitle: string;
        depth?: number;
    }): string[] => {
        // 递归深度保护
        if (depth > MAX_RECURSION_DEPTH) {
            const fullText = `${mdTitle}${lastText}${text}`;
            if (fullText.length <= chunkLen * 3) {
                return [fullText];
            }

            // 使用迭代方式分割大文本
            const chunks: string[] = [];
            for (let i = 0; i < fullText.length; i += chunkLen - overlapLen) {
                const chunk = fullText.slice(i, i + chunkLen);
                if (chunk.length > 0) {
                    chunks.push(chunk);
                }
            }
            return chunks;
        }

        const independentChunk = checkIndependentChunk(step);
        const isCustomStep = checkIsCustomStep(step);

        if (step >= stepReges.length) {
            const fullText = `${mdTitle}${lastText}${text}`;
            if (fullText.length < chunkLen * 3) {
                return [fullText];
            }

            const chunks: string[] = [];
            for (let i = 0; i < fullText.length; i += chunkLen - overlapLen) {
                chunks.push(fullText.slice(i, i + chunkLen));
            }
            return chunks;
        }

        const splitTexts = getSplitTexts({ text, step });
        const maxLen = splitTexts.length > 1 ? stepReges[step].maxLen : chunkLen;
        const minChunkLen = chunkLen * 0.7;
        const miniChunkLen = 30;

        const chunks: string[] = [];
        let currentLastText = lastText;

        for (let i = 0; i < splitTexts.length; i++) {
            const item = splitTexts[i];
            const currentTitle = `${mdTitle}${item.title}`;
            const currentText = item.text;
            const currentTextLen = currentText.length;
            const lastTextLen = currentLastText.length;
            const newText = currentLastText + currentText;
            const newTextLen = lastTextLen + currentTextLen;

            if (newTextLen > maxLen) {
                if (lastTextLen > minChunkLen) {
                    chunks.push(`${currentTitle}${currentLastText}`);
                    currentLastText = getOneTextOverlapText({
                        text: currentLastText,
                        step,
                    });
                    i--;
                    continue;
                }

                const innerChunks = splitTextRecursively({
                    text: newText,
                    step: step + 1,
                    lastText: "",
                    mdTitle: currentTitle,
                    depth: depth + 1,
                });

                const lastChunk = innerChunks[innerChunks.length - 1];
                if (!independentChunk && lastChunk.length < minChunkLen) {
                    chunks.push(...innerChunks.slice(0, -1));
                    currentLastText = lastChunk;
                } else {
                    chunks.push(...innerChunks);
                    currentLastText = getOneTextOverlapText({
                        text: lastChunk,
                        step,
                    });
                }
                continue;
            }

            currentLastText = newText;

            if (isCustomStep || (independentChunk && newTextLen > miniChunkLen) || newTextLen >= chunkLen) {
                chunks.push(`${currentTitle}${currentLastText}`);
                currentLastText = getOneTextOverlapText({
                    text: currentLastText,
                    step,
                });
            }
        }

        if (currentLastText && chunks.length > 0 && !chunks[chunks.length - 1].endsWith(currentLastText)) {
            if (currentLastText.length < chunkLen * 0.4) {
                chunks[chunks.length - 1] = chunks[chunks.length - 1] + currentLastText;
            } else {
                chunks.push(`${mdTitle}${currentLastText}`);
            }
        } else if (currentLastText && chunks.length === 0) {
            chunks.push(currentLastText);
        }

        return chunks;
    };

    try {
        const startTime = performance.now();

        const chunks = splitTextRecursively({
            text,
            step: 0,
            lastText: "",
            mdTitle: "",
            depth: 0,
        }).map((chunk) => chunk?.replaceAll(codeBlockMarker, "\n") || "");

        const chars = chunks.reduce((sum, chunk) => sum + chunk.length, 0);

        const endTime = performance.now();

        return { chunks, chars };
    } catch (err) {
        console.error("文本分割错误:", err);
        // 降级处理：使用简单分割
        const fallbackChunks: string[] = [];
        for (let i = 0; i < text.length; i += chunkLen - overlapLen) {
            fallbackChunks.push(text.slice(i, i + chunkLen));
        }
        return {
            chunks: fallbackChunks,
            chars: text.length,
        };
    }
};

/**
 * text split into chunks
 * chunkLen - one chunk len. max: 3500
 * overlapLen - The size of the before and after Text
 * chunkLen > overlapLen
 * markdown
 */
export const splitText2ChunksArray = (props: SplitProps): string[] => {
    const { text = "" } = props;
    const splitWithCustomSign = text.split(CUSTOM_SPLIT_SIGN);

    const splitResult = splitWithCustomSign.map((item) => {
        if (strIsMdTable(item)) {
            return markdownTableSplit(props);
        }

        return commonSplit(props);
    });
    return splitResult.map((item) => item.chunks).flat();
};
