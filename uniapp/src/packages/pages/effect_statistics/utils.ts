const DATE_TEXT_RE = /(\d{4})-(\d{1,2})-(\d{1,2})[T ](\d{1,2}):(\d{1,2})/
const SECOND_TIMESTAMP_MAX = 1e12

// 上游可能给 ISO 字符串、"Y-m-d H:i:s" 或秒/毫秒时间戳，
// iOS 小程序不接受带 "-" 的日期字符串，统一在这里兜住
export const parseTime = (value: string | number | null | undefined): Date | null => {
    if (value === null || value === undefined || value === '') return null

    if (typeof value === 'number') {
        const date = new Date(value < SECOND_TIMESTAMP_MAX ? value * 1000 : value)
        return Number.isNaN(date.getTime()) ? null : date
    }

    const text = String(value)
    const matched = text.match(DATE_TEXT_RE)
    if (matched) {
        return new Date(
            Number(matched[1]),
            Number(matched[2]) - 1,
            Number(matched[3]),
            Number(matched[4]),
            Number(matched[5])
        )
    }

    const date = new Date(text.replace(/-/g, '/'))
    return Number.isNaN(date.getTime()) ? null : date
}

const pad = (value: number) => String(value).padStart(2, '0')

export const formatCount = (value: number | null | undefined) => {
    if (value === null || value === undefined) return '—'
    const num = Number(value)
    if (!Number.isFinite(num)) return '—'
    if (num < 10000) return String(num)
    return `${(num / 10000).toFixed(1).replace(/\.0$/, '')}万`
}

// 2026-09-04 15:30 发布
export const formatPublishTime = (value: string | number) => {
    const date = parseTime(value)
    if (!date) return ''
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(
        date.getHours()
    )}:${pad(date.getMinutes())}`
}

// 09-04 15:30
export const formatUpdateTime = (value: string | number) => {
    const date = parseTime(value)
    if (!date) return ''
    return `${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(
        date.getMinutes()
    )}`
}

// 折线图横轴：09/04
export const formatAxisDate = (value: string) =>
    String(value ?? '')
        .slice(5)
        .replace('-', '/')

export const formatDuration = (seconds: number) => {
    const total = Math.max(Math.floor(Number(seconds) || 0), 0)
    return `${pad(Math.floor(total / 60))}:${pad(total % 60)}`
}
