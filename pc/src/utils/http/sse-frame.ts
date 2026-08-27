/**
 * SSE 帧解析。
 *
 * 后端统一按 `data:{json}\n\n` 输出（ToolsService.php / CozeLogic.php），
 * request.ts 的 eventStream 已经保证交到这里的是**完整帧**（不会切在半个 JSON 或半个汉字上）。
 * 这里只负责把帧文本变成业务对象，所有流式消费方共用同一份实现，
 * 避免每个页面各自 copy 一遍 `split("data:") + JSON.parse + try/catch`。
 */

const DATA_PREFIX = "data:";

/**
 * 把一帧（或多帧）SSE 文本解析成业务对象数组。
 *
 * 按行剥 `data:` 前缀，而不是 `split("data:")`——正文里出现 `data:image/...;base64,`
 * 这类内容时，后者会把一帧从中间切开，导致整帧被丢弃。
 */
export function parseSseFrames<T = any>(value: string, onError?: (raw: string, error: unknown) => void): T[] {
    const result: T[] = [];
    for (const raw of splitSseFrames(value)) {
        try {
            result.push(JSON.parse(raw));
        } catch (error) {
            reportSseError(raw, error, onError);
        }
    }
    return result;
}

/**
 * 逐帧处理。handler 内部抛错只影响当前帧，不会中断整条流
 * —— 与各页面原来「每帧一个 try/catch」的行为一致。
 */
export function handleSseFrames<T = any>(
    value: string,
    handler: (data: T) => void,
    onError?: (raw: string, error: unknown) => void,
): void {
    for (const raw of splitSseFrames(value)) {
        try {
            handler(JSON.parse(raw));
        } catch (error) {
            reportSseError(raw, error, onError);
        }
    }
}

/** 取出帧内所有 data 行的负载；非 data 行（注释、心跳）直接忽略 */
function splitSseFrames(value: string): string[] {
    return value
        .split(/\r?\n/)
        .map((line) => (line.startsWith(DATA_PREFIX) ? line.slice(DATA_PREFIX.length) : line).trim())
        .filter((text) => text.startsWith("{"));
}

function reportSseError(raw: string, error: unknown, onError?: (raw: string, error: unknown) => void) {
    if (onError) return onError(raw, error);
    // 默认也要有声音：以前各处都是空 catch，出问题时完全查不到
    console.warn("[sse] 帧处理失败:", raw, error);
}
