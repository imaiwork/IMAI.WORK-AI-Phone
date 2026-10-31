/**
 * 文案质量校验工具（PC 前端版）
 *
 * 与 uniapp 版 textValidator.ts 逻辑对称。
 * 解决两类问题：
 *   1. 英文：AI 返回单词拼接、缺少空格（如 "HelloWorldThisIsATest"）
 *   2. 其他外文（日/韩/法/西等）：乱码、非法 UTF-8 序列、替换字符 U+FFFD
 */

const REPLACEMENT_CHAR = '\uFFFD'

const LANG = {
  ENGLISH: 'english',
  SPACE_SEPARATED: 'space_separated', // 所有空格分词语系（拉丁系/西里尔/希腊等）
  CJK: 'cjk',                         // 中日韩（非空格分词）
  MIXED: 'mixed',
  UNKNOWN: 'unknown',
} as const

export function detectLanguage(text: string): string {
  if (!text) return LANG.UNKNOWN
  // trim 后再算，纯空白直接返回 UNKNOWN
  text = text.trim()
  const total = [...text].length
  if (total === 0) return LANG.UNKNOWN

  let en = 0 // ASCII 纯英文 A-Za-z
  let cjk = 0 // 中日韩统一表意 + 假名 + 韩文
  let latinExt = 0 // 拉丁扩展 U+00C0-U+00FF
  let cyrillic = 0 // 西里尔 U+0400-U+04FF（俄/乌/保）
  let greek = 0 // 希腊字母 U+0370-U+03FF

  for (const ch of text) {
    const code = ch.charCodeAt(0)
    if ((code >= 65 && code <= 90) || (code >= 97 && code <= 122)) en++
    else if (code >= 0x4e00 && code <= 0x9fff) cjk++
    else if (code >= 0x3040 && code <= 0x30ff) cjk++
    else if (code >= 0xac00 && code <= 0xd7af) cjk++
    else if (code >= 0x0400 && code <= 0x04ff) cyrillic++
    else if (code >= 0x0370 && code <= 0x03ff) greek++
    else if (code >= 0x00c0 && code <= 0x00ff) latinExt++
  }

  const enRatio = en / total
  const cjkRatio = cjk / total

  // 判定顺序（与后端 PHP / uniapp 完全一致）
  if (cjkRatio >= 0.2) return LANG.CJK
  if (cyrillic >= 1) return LANG.SPACE_SEPARATED
  if (greek >= 1) return LANG.SPACE_SEPARATED
  if (latinExt >= 1) return LANG.SPACE_SEPARATED
  if (enRatio >= 0.6) return LANG.ENGLISH
  if ((latinExt + en) / total >= 0.5) return LANG.SPACE_SEPARATED
  const allLetters = (text.match(/\p{L}/gu) || []).length
  if (allLetters / total >= 0.6) return LANG.SPACE_SEPARATED
  return LANG.MIXED
}

export function detectMojibake(text: string): { hasGarbage: boolean; details: string[] } {
  const details: string[] = []
  let replacementCount = 0
  for (const ch of text) {
    if (ch === REPLACEMENT_CHAR) replacementCount++
  }
  if (replacementCount > 0) details.push(`包含 ${replacementCount} 个 Unicode 替换字符 (U+FFFD)`)
  const controlCharMatches = text.match(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g)
  if (controlCharMatches && controlCharMatches.length > 0) details.push('包含 ASCII 控制字符')
  return { hasGarbage: details.length > 0, details }
}

export function stripGarbage(text: string): string {
  let clean = text.split(REPLACEMENT_CHAR).join('')
  clean = clean.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '')
  return clean.trim()
}

// ─── 空格分词语种的空格/拼接检测 ───────────────────────────────────

/**
 * 空格分词语种（英文 / 法文 / 西班牙文 / 德文 / 意大利文 / 葡文等拉丁系）
 * 检测文案是否存在"单词拼接/缺空格"问题
 *
 * 判定规则（满足任一即视为异常）：
 *   a) 存在 ≥15 个连续 Unicode 字母类字符不含任何空格
 *   b) 拉丁系文本单词平均长度（字母数/(空格数+1)）≥ 12
 *   c) 存在 ≥2 处驼峰式大小写拼接（小写→大写边界）
 */
export function detectLatinNoSpace(text: string): { hasIssue: boolean; suggestedFix: string } {
  const issues: string[] = []
  // 规则 a：\p{L} 匹配任何语言字母（含带变音 é ü ñ ç ö 等）
  const letterSegments = text.match(/[\p{L}]{15,}/gu)
  if (letterSegments) {
    for (const segment of letterSegments) {
      const upperCount = (segment.match(/\p{Lu}/gu) || []).length
      const totalLen = [...segment].length
      if (upperCount >= totalLen * 0.7) continue // 全大写缩写跳过
      issues.push(`连续 ${totalLen} 个字母字符无空格（疑似单词拼接）`)
      break
    }
  }
  // 规则 b
  const spaceCount = (text.match(/ /g) || []).length
  const letterCount = (text.match(/\p{L}/gu) || []).length
  if (spaceCount > 0 && letterCount > 0) {
    const avgWordLen = letterCount / (spaceCount + 1)
    if (avgWordLen >= 12) issues.push(`单词平均长度 ${avgWordLen.toFixed(1)} 字符，明显偏高`)
  }
  // 规则 c
  const camelHits = (text.match(/\p{Ll}\p{Lu}/gu) || []).length
  if (camelHits >= 2) issues.push(`发现 ${camelHits} 处大小写拼接（疑似缺空格）`)

  const suggestedFix = issues.length > 0 ? fixLatinSpaces(text) : text
  return { hasIssue: issues.length > 0, suggestedFix }
}

export function fixLatinSpaces(text: string): string {
  let fixed = text
  fixed = fixed.replace(/(\p{Ll})(\p{Lu})/gu, '$1 $2')
  fixed = fixed.replace(/(\p{Lu}+)(\p{Lu}\p{Ll})/gu, '$1 $2')
  fixed = fixed.replace(/(\p{L})(\p{N})/gu, '$1 $2')
  fixed = fixed.replace(/(\p{N})(\p{L})/gu, '$1 $2')
  return fixed.replace(/\s{2,}/g, ' ').trim()
}

// 兼容旧方法名
export const detectEnglishNoSpace = detectLatinNoSpace
export const fixEnglishSpaces = fixLatinSpaces

export interface ValidationResult {
  status: 'ok' | 'warning' | 'error'
  type: 'latin_no_space' | 'mojibake' | null
  details: string[]
  suggestedFix: string | null
}

export function validateText(text: string): ValidationResult {
  const trimmed = text?.trim() ?? ''
  if (trimmed === '') return { status: 'ok', type: null, details: [], suggestedFix: null }

  const mojibake = detectMojibake(trimmed)
  if (mojibake.hasGarbage) {
    return { status: 'error', type: 'mojibake', details: mojibake.details, suggestedFix: stripGarbage(trimmed) }
  }

  const lang = detectLanguage(trimmed)
  // 所有空格分词语种（英文 / 法西德意葡等拉丁系）
  if (lang === LANG.ENGLISH || lang === LANG.SPACE_SEPARATED) {
    const space = detectLatinNoSpace(trimmed)
    if (space.hasIssue) {
      return {
        status: 'warning',
        type: 'latin_no_space',
        details: ['空格分词语种疑似单词间缺空格', '建议在驼峰/大写字母前插入空格'],
        suggestedFix: space.suggestedFix,
      }
    }
  }
  return { status: 'ok', type: null, details: [], suggestedFix: null }
}

export interface SanitizeResult {
  content: string[]
  warnings: Array<{ index: number; type: string; original: string; fixed: string }>
}

export function sanitizeTextArray(texts: string[]): SanitizeResult {
  const warnings: SanitizeResult['warnings'] = []
  const cleaned: string[] = []

  texts.forEach((text, index) => {
    if (!text || text === '') { cleaned.push(text || ''); return }
    let safe = text
    const { hasGarbage } = detectMojibake(safe)
    if (hasGarbage) safe = stripGarbage(safe)
    const result = validateText(safe)
    if (result.status !== 'ok') {
      warnings.push({ index, type: result.type!, original: safe, fixed: result.suggestedFix ?? safe })
      cleaned.push(result.suggestedFix ?? safe)
    } else {
      cleaned.push(safe)
    }
  })
  return { content: cleaned, warnings }
}
