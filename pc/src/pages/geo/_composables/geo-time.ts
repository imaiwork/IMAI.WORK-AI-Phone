/** ORM 的 create_time 可能是 unix 秒，也可能是 Y-m-d H:i:s */
export const toGeoTs = (t: any): number => {
    if (t == null || t === '') return 0
    if (typeof t === 'number') return t < 1e12 ? t : Math.floor(t / 1000)
    const s = String(t).trim()
    if (/^\d+$/.test(s)) {
        const n = Number(s)
        return n < 1e12 ? n : Math.floor(n / 1000)
    }
    const n = Date.parse(s.includes('T') ? s : s.replace(/-/g, '/'))
    return Number.isNaN(n) ? 0 : Math.floor(n / 1000)
}

const pad = (n: number) => String(n).padStart(2, '0')

export const fmtGeoTime = (t: any): string => {
    const ts = toGeoTs(t)
    if (!ts) return '–'
    const d = new Date(ts * 1000)
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}
