/**
 * 文本切片：按句末标点优先切分，并带约 25% 重叠
 * 移植自 PC `utils/file-reader.ts` 的 splitText2Chunks，
 * 改为不使用正则后行断言(lookbehind)，兼容小程序 JS 引擎。
 */
const SENTENCE_END = '。！？；.!?;\n'

export const splitText2Chunks = (text: string, maxLen = 512): string[] => {
    if (!text) return []
    const overlapLen = Math.floor(maxLen * 0.25)

    // 按句末标点切句，保留标点
    const sentences: string[] = []
    let buf = ''
    for (const ch of text) {
        buf += ch
        if (SENTENCE_END.includes(ch)) {
            sentences.push(buf)
            buf = ''
        }
    }
    if (buf) sentences.push(buf)

    const chunks: string[] = []
    let preChunk = ''
    let chunk = ''
    for (let i = 0; i < sentences.length; i++) {
        const sentence = sentences[i]
        chunk += sentence
        if (chunk.length > maxLen - overlapLen) {
            preChunk += sentence
        }
        if (chunk.length >= maxLen) {
            chunks.push(chunk)
            chunk = preChunk
            preChunk = ''
        }
    }
    if (chunk) chunks.push(chunk)

    return chunks.filter((c) => c.trim())
}
