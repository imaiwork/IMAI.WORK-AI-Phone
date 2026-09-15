import {
    EXTRA_BILL_ITEM_KEY,
    PRICE_GROUP_OTHER,
    PRICE_GROUPS,
    RECHARGE_ICON_DEFAULT,
    RECHARGE_ICON_RULES
} from './_enums'

const pad = (n: number) => String(n).padStart(2, '0')

/** 本地日期 YYYY-MM-DD。不能用 toISOString：那是 UTC 日期，凌晨 0~8 点会差一天 */
export const toDateKey = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

export const todayKey = () => toDateKey(new Date())

export const shiftDateKey = (key: string, delta: number) => {
    const [y, m, d] = key.split('-').map(Number)
    return toDateKey(new Date(y, m - 1, d + delta))
}

export const fmtDateCN = (key: string) => {
    const [y, m, d] = key.split('-').map(Number)
    return `${y}年${m}月${d}日`
}

export const fmtDateShort = (key: string) => {
    const [, m, d] = key.split('-').map(Number)
    return `${m}月${d}日`
}

/** 自然月首末日，offset=0 本月 / -1 上月（跨年正确） */
export const monthRange = (offset: number) => {
    const now = new Date()
    return {
        start: toDateKey(new Date(now.getFullYear(), now.getMonth() + offset, 1)),
        end: toDateKey(new Date(now.getFullYear(), now.getMonth() + offset + 1, 0))
    }
}

/** 后端 ListsValidate 按 date 规则校验 start_time/end_time，须传日期时间串 */
export const dayStart = (key: string) => `${key} 00:00:00`
export const dayEnd = (key: string) => `${key} 23:59:59`

export const formatNum = (n: any) => {
    const v = Number(n)
    if (!Number.isFinite(v)) return '0'
    if (Number.isInteger(v)) return String(v)
    return String(Math.round(v * 100) / 100)
}

export const formatWithComma = (n: any) => {
    const s = formatNum(n)
    const [intPart, dec] = s.split('.')
    const withComma = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
    return dec != null ? `${withComma}.${dec}` : withComma
}

/** 消耗金额：后端汇总正数为消耗、负数为净退回 */
export const signedConsume = (n: any) => {
    const v = Number(n) || 0
    return v < 0 ? `+${formatWithComma(-v)}` : `-${formatWithComma(v)}`
}

/**
 * 计费单价文案。tokens/算力 是反向计价：score 是折算 1 算力所需的 token 数，
 * 显示成「900 tokens = 1 算力」，直接显示成 900 算力会吓跑用户。
 */
export const priceText = (item: { score?: any; unit?: string }) => {
    const unit = String(item?.unit || '算力/次')
    const score = Number(item?.score) || 0
    if (unit.includes('/算力')) {
        return score ? `${formatNum(score)} ${unit.split('/')[0]} = 1 算力` : '不消耗算力'
    }
    const per = unit.includes('/') ? unit.split('/')[1] : unit
    return `${formatNum(score)} 算力/${per}`
}

export interface PriceGroupView {
    key: string
    name: string
    items: any[]
}

/** 计费项按后台工作台分组，白名单外的归「其他」 */
export const buildPriceGroups = (list: any[]): PriceGroupView[] => {
    const groups: PriceGroupView[] = PRICE_GROUPS.map((g) => ({ key: g.key, name: g.name, items: [] }))
    const byKey = new Map(groups.map((g) => [g.key, g]))
    ;(list || []).forEach((item) => {
        const hit = PRICE_GROUPS.find((g) => g.scenes.includes(item?.scene))
        byKey.get(hit ? hit.key : PRICE_GROUP_OTHER)?.items.push(item)
    })
    return groups.filter((g) => g.items.length > 0)
}

export const rechargeIcon = (remark: any) => {
    const text = String(remark || '')
    const hit = RECHARGE_ICON_RULES.find((rule) => rule.keywords.some((k) => text.includes(k)))
    return hit ? hit.icon : RECHARGE_ICON_DEFAULT
}

export interface ExtraEntry {
    key: string
    value: string
}

/** 流水 extra 全量转成可展示的键值对；值若是对象/数组转成 JSON 文本，不丢信息 */
export const extraEntries = (extra: any): ExtraEntry[] => {
    if (!extra || typeof extra !== 'object' || Array.isArray(extra)) return []
    return Object.entries(extra)
        .filter(([key, value]) => key !== EXTRA_BILL_ITEM_KEY && value !== '' && value != null)
        .map(([key, value]) => ({
            key,
            value: typeof value === 'object' ? JSON.stringify(value) : String(value)
        }))
}
