export const UI_TYPE_READY = 1
export const UI_TYPE_FRAME = 2

export const UI_TYPES = [
    { key: UI_TYPE_READY, name: '成品文案', tag: 'primary' },
    { key: UI_TYPE_FRAME, name: '提示词框架', tag: 'warning' }
] as const

export const TYPE_CARDS = [
    {
        key: UI_TYPE_READY,
        name: '成品文案',
        desc: '客户挑了改改就能直接发',
        ex: '例：我是{姓名}，在{所在地}专做{主要服务}，{卖点}。',
        ph: '我是{姓名}，在{所在地}做{行业}，专做{主要服务}…',
        example:
            '花钱之前，先花一分钟看完这段话。\n我是{姓名}，做{行业}这些年，见过太多人因为没问清楚多花冤枉钱。\n我们专做{主要服务}，{卖点}。\n拿不准的地方直接私信我，免费帮你看看。'
    },
    {
        key: UI_TYPE_FRAME,
        name: '提示词框架',
        desc: '给 AI 看的写作骨架，客户复制去让 AI 照着写',
        ex: '例：开头点出{目标客户}的难处 → 讲{主要服务}怎么解决 → 结尾引导私信',
        ph: '开头：…\n中间：…\n结尾：…\n要求：…',
        example:
            '30秒口播骨架：痛点→服务→私信\n开头：一句话戳中{目标客户}最头疼的问题，不超过 20 个字\n中间：用{姓名}的口吻，讲在{所在地}怎么用{主要服务}解决它，把「{卖点}」说具体\n结尾：一句话引导私信或到店\n要求：口语化，全文 150 字以内，不用「最」「第一」这类夸张词'
    }
] as const

export const LIBRARY_TYPES = [
    { key: 'news', label: '新闻体', library_type: 1, driver_type: 1 },
    { key: 'oral', label: '口播文案', library_type: 1, driver_type: 2 },
    { key: 'mixcut', label: '素材混剪口播', library_type: 1, driver_type: 3 },
    { key: 'publish', label: '发布文案', library_type: 2, driver_type: 0 }
] as const

export const PLACEHOLDERS = [
    { k: '姓名', label: '人设名称', from: '人设名称' },
    { k: '行业', label: '行业', from: '行业' },
    { k: '所在地', label: '城市/地区', from: '城市 / 地区' },
    { k: '主要服务', label: '卖什么', from: '「你在做什么 / 卖什么」' },
    { k: '目标客户', label: '卖给谁', from: '「你想卖给谁」' },
    { k: '卖点', label: '好在哪', from: '「你比别人好在哪」' }
] as const

export const ALIAS: Record<string, string> = {
    名字: '姓名',
    服务区域: '所在地',
    地区: '所在地',
    城市: '所在地',
    为什么选你: '卖点'
}

export const OK_KEYS = new Set([...PLACEHOLDERS.map((item) => item.k), ...Object.keys(ALIAS)])
export const PLACEHOLDER_RE = /[{｛][^}｝]{0,12}[}｝]/gu

export const SAMPLES = [
    {
        id: 'full',
        label: '李师傅',
        sub: '装修 · 成都，资料齐全',
        p: {
            name: '李师傅',
            industry: '装修',
            city: '成都',
            service: '老房翻新',
            customers: '家里有老房子要翻新的业主',
            selling: '十年工龄，合同价不增项'
        }
    },
    {
        id: 'beauty',
        label: '小雅',
        sub: '美容护肤 · 杭州',
        p: {
            name: '小雅',
            industry: '美容护肤',
            city: '杭州',
            service: '皮肤管理和补水护理',
            customers: '在意皮肤状态的上班族',
            selling: '一对一建档，不推销办卡'
        }
    },
    {
        id: 'thin',
        label: '资料没填全的客户',
        sub: '只填了名字和行业',
        p: { name: '王姐', industry: '餐饮', city: '', service: '', customers: '', selling: '' }
    }
]

export type PublicCopyRow = {
    id?: number
    title?: string
    content?: string
    topic?: string
    ui_type?: number
    library_type?: number
    driver_type?: number
    industry?: string
    sort?: number
    enabled?: number
    version?: number
}

export const uiTypeName = (value?: number) =>
    UI_TYPES.find((item) => item.key === Number(value))?.name || '成品文案'

export const libraryTypeName = (row: { library_type?: number; driver_type?: number }) =>
    LIBRARY_TYPES.find(
        (item) => item.library_type === Number(row.library_type) && item.driver_type === Number(row.driver_type)
    )?.label || '口播文案'

export const tokenState = (token: string): 'ok' | 'missing' | 'mixed' => {
    const key = token.slice(1, -1)
    if (!OK_KEYS.has(key.trim())) return 'missing'
    return key === key.trim() && token[0] === '{' === (token[token.length - 1] === '}') ? 'ok' : 'mixed'
}

export const rewriteText = (text: string, persona: Record<string, string>) => {
    if (!text) return ''
    const mapping: Record<string, string> = {
        姓名: persona.name || '',
        名字: persona.name || '',
        行业: persona.industry || '',
        所在地: persona.city || '',
        城市: persona.city || '',
        地区: persona.city || '',
        服务区域: persona.city || '',
        主要服务: persona.service || '',
        目标客户: persona.customers || '',
        卖点: persona.selling || '',
        为什么选你: persona.selling || ''
    }
    let out = text
    Object.entries(mapping).forEach(([key, value]) => {
        if (!value) return
        out = out.split(`{${key}}`).join(value).split(`｛${key}｝`).join(value)
    })
    return out.replace(PLACEHOLDER_RE, '').trim()
}

export const autoTitle = (text: string) => {
    const plain = text.replace(PLACEHOLDER_RE, '').trim()
    const source = [...plain].length < 6 ? text : plain
    return [...source].slice(0, 16).join('')
}

export const escapeHtml = (value: string) =>
    value.replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char] || char))

export const highlightPlaceholders = (text: string) => {
    const source = String(text || '')
    let out = ''
    let last = 0
    source.replace(PLACEHOLDER_RE, (match, offset: number) => {
        const bad = tokenState(match) !== 'ok'
        out += escapeHtml(source.slice(last, offset))
        out += `<span class="ph${bad ? ' bad' : ''}">${escapeHtml(match)}</span>`
        last = offset + match.length
        return match
    })
    return out + escapeHtml(source.slice(last))
}
