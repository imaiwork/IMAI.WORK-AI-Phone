/**
 * 文案质量校验工具（前端版）
 *
 * 解决两类问题：
 *   1. 英文：AI 返回单词拼接、缺少空格（如 "HelloWorldThisIsATest"）
 *   2. 其他外文（日/韩/法/西等）：乱码、非法 UTF-8 序列、替换字符 U+FFFD
 *
 * 逻辑与后端 PHP 版 TextQualityValidator 对称，关键判定（英文占比阈值、空格间隔阈值）保持一致。
 */

// ─── 常量 ───────────────────────────────────────────────────────────

const REPLACEMENT_CHAR = '\uFFFD'

const LANG = {
  ENGLISH: 'english',
  SPACE_SEPARATED: 'space_separated', // 所有空格分词语系（拉丁系/西里尔/希腊等）
  CJK: 'cjk',                         // 中日韩（非空格分词）
  MIXED: 'mixed',
  UNKNOWN: 'unknown',
} as const

// ─── 语言检测 ──────────────────────────────────────────────────────

/**
 * 粗粒度语言检测（与后端 PHP TextQualityValidator::detectLanguage 完全对齐）
 *
 * 覆盖：英文、拉丁系（法/西/德/意/葡）、西里尔系（俄/乌/保）、希腊语、中文、日文、韩文
 */
export function detectLanguage(text: string): string {
  if (!text) return LANG.UNKNOWN
  // trim 后再算，纯空白直接返回 UNKNOWN
  text = text.trim()
  const total = [...text].length
  if (total === 0) return LANG.UNKNOWN

  let en = 0 // A-Z a-z (ASCII 纯英文)
  let cjk = 0 // 中日韩统一表意 + 假名 + 韩文
  let latinExt = 0 // 拉丁扩展 U+00C0-U+00FF（é ç ñ ö 等带变音）
  let cyrillic = 0 // 西里尔 U+0400-U+04FF（俄文 Украина Россия）
  let greek = 0 // 希腊字母 U+0370-U+03FF

  for (const ch of text) {
    const code = ch.charCodeAt(0)
    // ASCII 英文字母
    if ((code >= 65 && code <= 90) || (code >= 97 && code <= 122)) en++
    // CJK 统一表意 (U+4E00-U+9FFF)
    else if (code >= 0x4e00 && code <= 0x9fff) cjk++
    // 日文假名 (U+3040-U+30FF)
    else if (code >= 0x3040 && code <= 0x30ff) cjk++
    // 韩文 (U+AC00-U+D7AF)
    else if (code >= 0xac00 && code <= 0xd7af) cjk++
    // 西里尔字母 (U+0400-U+04FF) — 俄/乌/保/白俄/塞尔维亚等
    else if (code >= 0x0400 && code <= 0x04ff) cyrillic++
    // 希腊字母 (U+0370-U+03FF)
    else if (code >= 0x0370 && code <= 0x03ff) greek++
    // 拉丁扩展 (U+00C0-U+00FF) — é à â ç ñ ö ü 等
    else if (code >= 0x00c0 && code <= 0x00ff) latinExt++
  }

  const enRatio = en / total
  const cjkRatio = cjk / total

  // 判定顺序（与后端 PHP 完全一致）：
  //   1. CJK ≥ 20%           → CJK（中日韩，非空格分词）
  //   2. 西里尔 ≥ 1          → SPACE_SEPARATED
  //   3. 希腊 ≥ 1            → SPACE_SEPARATED
  //   4. 拉丁扩展 ≥ 1        → SPACE_SEPARATED
  //   5. 纯英文 ≥ 60%        → ENGLISH
  //   6. 拉丁扩展+英文 ≥ 50% → SPACE_SEPARATED
  //   7. 兜底：其他含大量字母 → SPACE_SEPARATED
  //   8. 都不满足            → MIXED
  if (cjkRatio >= 0.2) return LANG.CJK
  if (cyrillic >= 1) return LANG.SPACE_SEPARATED
  if (greek >= 1) return LANG.SPACE_SEPARATED
  if (latinExt >= 1) return LANG.SPACE_SEPARATED
  if (enRatio >= 0.6) return LANG.ENGLISH
  if ((latinExt + en) / total >= 0.5) return LANG.SPACE_SEPARATED
  // 兜底：所有 Unicode 字母占比高（\p{L} 覆盖）
  const allLetters = (text.match(/\p{L}/gu) || []).length
  if (allLetters / total >= 0.6) return LANG.SPACE_SEPARATED
  return LANG.MIXED
}

// ─── 乱码检测 ──────────────────────────────────────────────────────

/**
 * 检测乱码：替换字符、非法控制字符
 */
export function detectMojibake(text: string): { hasGarbage: boolean; details: string[] } {
  const details: string[] = []

  // 1. 替换字符 U+FFFD
  let replacementCount = 0
  for (const ch of text) {
    if (ch === REPLACEMENT_CHAR) replacementCount++
  }
  if (replacementCount > 0) {
    details.push(`包含 ${replacementCount} 个 Unicode 替换字符 (U+FFFD)`)
  }

  // 2. 控制字符（允许 \t \n \r）
  const controlCharMatches = text.match(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g)
  if (controlCharMatches && controlCharMatches.length > 0) {
    details.push('包含 ASCII 控制字符')
  }

  return {
    hasGarbage: details.length > 0,
    details,
  }
}

/**
 * 剥离乱码字符
 */
export function stripGarbage(text: string): string {
  // 移除替换字符
  let clean = text.split(REPLACEMENT_CHAR).join('')
  // 移除控制字符（保留 \t \n \r）
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

  // 规则 a：连续 Unicode 字母类字符 ≥15 且不含空格
  // \p{L} 匹配任何语言的字母（含带变音的 é ü ñ ç ö 等）
  const letterSegments = text.match(/[\p{L}]{15,}/gu)
  if (letterSegments) {
    for (const segment of letterSegments) {
      const upperCount = (segment.match(/\p{Lu}/gu) || []).length
      const totalLen = [...segment].length
      // 全大写缩写（≥70%）跳过，如 "CNNABC"
      if (upperCount >= totalLen * 0.7) continue
      issues.push(`连续 ${totalLen} 个字母字符无空格（疑似单词拼接）`)
      break
    }
  }

  // 规则 b：单词平均长度（使用 \p{L} 覆盖拉丁扩展字母）
  const spaceCount = (text.match(/ /g) || []).length
  const letterCount = (text.match(/\p{L}/gu) || []).length
  if (spaceCount > 0 && letterCount > 0) {
    const avgWordLen = letterCount / (spaceCount + 1)
    if (avgWordLen >= 12) {
      issues.push(`单词平均长度 ${avgWordLen.toFixed(1)} 字符，明显偏高（正常拉丁系 4-7）`)
    }
  }

  // 规则 c：驼峰拼接（小写→大写边界，支持带变音字符）
  const camelHits = (text.match(/\p{Ll}\p{Lu}/gu) || []).length
  if (camelHits >= 2) {
    issues.push(`发现 ${camelHits} 处大小写拼接（疑似单词间缺空格）`)
  }

  const suggestedFix = issues.length > 0 ? fixLatinSpaces(text) : text

  return { hasIssue: issues.length > 0, suggestedFix }
}

/**
 * 拉丁系拼接修复（保守策略，避免误拆专有名词如 iPhone）
 *   1. 小写 → 大写 边界插入空格（camelCase → camel Case，含带变音 é→É）
 *   2. 连续大写后接小写的边界插入空格（HTMLParser → HTML Parser）
 *   3. 数字与字母间插入空格
 *   4. 合并多余空格 + trim
 */
export function fixLatinSpaces(text: string): string {
  let fixed = text
  // 小写 → 大写 边界
  fixed = fixed.replace(/(\p{Ll})(\p{Lu})/gu, '$1 $2')
  // 连续大写后接小写：HELLOWorld → HELLO World
  fixed = fixed.replace(/(\p{Lu}+)(\p{Lu}\p{Ll})/gu, '$1 $2')
  // 数字与字母间
  fixed = fixed.replace(/(\p{L})(\p{N})/gu, '$1 $2')
  fixed = fixed.replace(/(\p{N})(\p{L})/gu, '$1 $2')
  // 合并多余空格 + trim
  fixed = fixed.replace(/\s{2,}/g, ' ').trim()
  return fixed
}

// 兼容旧方法名
export const detectEnglishNoSpace = detectLatinNoSpace
export const fixEnglishSpaces = fixLatinSpaces

// ─── 统一校验入口 ───────────────────────────────────────────────────

export interface ValidationResult {
  status: 'ok' | 'warning' | 'error'
  type: 'latin_no_space' | 'mojibake' | null
  details: string[]
  suggestedFix: string | null
}

/**
 * 单条文案校验
 */
export function validateText(text: string): ValidationResult {
  const trimmed = text?.trim() ?? ''
  if (trimmed === '') {
    return { status: 'ok', type: null, details: [], suggestedFix: null }
  }

  // 1. 先检测乱码（任何语言都可能有）
  const mojibake = detectMojibake(trimmed)
  if (mojibake.hasGarbage) {
    return {
      status: 'error',
      type: 'mojibake',
      details: mojibake.details,
      suggestedFix: stripGarbage(trimmed),
    }
  }

  // 2. 语言检测
  const lang = detectLanguage(trimmed)

  // 3. 所有空格分词语种（英文 / 法西德意葡等拉丁系）→ 检测单词拼接/缺空格
  const isSpaceSeparated = lang === LANG.ENGLISH || lang === LANG.SPACE_SEPARATED
  if (isSpaceSeparated) {
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

/**
 * 文案语言/质量硬校验（视频创作场景）
 *
 * 用于「确认文案时 + 提交生成时」双重拦截：
 * 通过返回 null；不通过返回原因文案（供 toast / modal 提示）
 */
export function getCopyLangFailReason(text: string): string | null {
  const result = validateText(text?.trim() ?? '')
  if (result.status === 'ok') return null
  return result.type === 'mojibake' ? '包含外文乱码或非法字符' : '疑似外文单词间缺空格'
}

/**
 * 批量校验文案数组，返回带 warning 的结果
 */
export interface SanitizeResult {
  content: string[]
  warnings: Array<{
    index: number
    type: string
    original: string
    fixed: string
  }>
}

export function sanitizeTextArray(texts: string[]): SanitizeResult {
  const warnings: SanitizeResult['warnings'] = []
  const cleaned: string[] = []

  texts.forEach((text, index) => {
    if (!text || text === '') {
      cleaned.push(text || '')
      return
    }

    // 先剥离乱码
    let safe = text
    const { hasGarbage } = detectMojibake(safe)
    if (hasGarbage) {
      safe = stripGarbage(safe)
    }

    // 再做空格分词语种检测（英文 / 法西德意葡等拉丁系）
    const result = validateText(safe)
    if (result.status !== 'ok') {
      warnings.push({
        index,
        type: result.type!,
        original: safe,
        fixed: result.suggestedFix ?? safe,
      })
      cleaned.push(result.suggestedFix ?? safe)
    } else {
      cleaned.push(safe)
    }
  })

  return { content: cleaned, warnings }
}
