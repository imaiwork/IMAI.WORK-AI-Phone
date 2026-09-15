import { PlayKeyEnum, MAX_LINES } from '../enums/play_video'

// 爆款玩法 · 共享类型与纯函数（常量在 enums/play_video，副作用在 hooks/usePlay*）

export interface PlayTemplate {
    ref_id: string
    name: string
    video_url: string
    cover_url?: string
    duration_sec?: number
    heat?: string
    category?: string
    face_count?: number
    allow_bg_replace?: boolean
    script?: { role?: string; name?: string; text: string }[]
}

export interface PlayLine {
    role: string
    text: string
}

export interface PlayPersona {
    id: number
    persona_name: string
    industry?: string
    main_business?: string
    quick_desc?: string
    conversion_hook?: string
}

export type GenPhase = 'running' | 'success' | 'fail'

export interface GenState {
    show: boolean
    phase: GenPhase
    stepLabel: string
    percent: number
    elapsed: number
    sn: string
    videoUrl: string
    failReason: string
    refunded: boolean
    tokensCost: number
}

export const roleOf = (index: number) => (index % 2 ? 'B' : 'A')

/** 模板台本 → 可编辑台词（最多 MAX_LINES 句） */
export function linesFromTemplate(tpl: PlayTemplate | null): PlayLine[] {
    return (tpl?.script || []).slice(0, MAX_LINES).map((s, i) => ({
        role: s.role || s.name || roleOf(i),
        text: s.text || ''
    }))
}

/** 本地兜底改写：前句原样保留，末句去尾标点后接「——{行业}这事，{人设名}，{钩子}。」 */
export function rewriteLinesByPersona(originalLines: string[], persona: PlayPersona): string[] {
    if (originalLines.length === 0) return originalLines
    const lines = [...originalLines]
    const last = lines[lines.length - 1].replace(/[。！？.!?～~]+$/, '')
    const cat = (persona.industry || '').trim()
    const hook = (persona.conversion_hook || '').trim()
    const parts = [cat ? `${cat}这事` : '这事', persona.persona_name]
    if (hook) parts.push(hook)
    lines[lines.length - 1] = `${last}——${parts.join('，')}。`
    return lines
}

/** 提交契约里的 template 字段（三个玩法共用） */
export function buildTemplateParam(
    tpl: PlayTemplate,
    overrides: Partial<Record<string, any>> = {}
) {
    return {
        ref_id: tpl.ref_id,
        video_url: tpl.video_url,
        name: tpl.name,
        cover_url: tpl.cover_url || '',
        duration_sec: tpl.duration_sec || 0,
        face_count: tpl.face_count || 1,
        allow_bg_replace: !!tpl.allow_bg_replace,
        ...overrides
    }
}

/** 客户端任务号：站长按 sn 幂等，重试同 sn 不重复扣费 */
export function makePlaySn() {
    return `pv${Date.now()}${Math.random().toString(36).slice(2, 8)}`
}

// ── 价格/余额交接（选择页 templates 响应带回，制作页价签用）──
let playPrices: Record<string, number> = {}
let playBalance = 0

export function setPlayPricing(
    prices: Record<string, number> | undefined,
    balance: number | undefined
) {
    if (prices) playPrices = prices
    if (typeof balance === 'number') playBalance = balance
}

export function getPlayPricing(play: PlayKeyEnum): { price: number; balance: number } {
    return { price: Number(playPrices[play] || 0), balance: playBalance }
}

// ── 模板在页面间的交接（对象可能带整段台本，不走 URL 参数）──
let currentTemplate: PlayTemplate | null = null

export function setCurrentTemplate(tpl: PlayTemplate | null) {
    currentTemplate = tpl
}

export function getCurrentTemplate(): PlayTemplate | null {
    return currentTemplate
}
