/** 对齐小程序 IMAGE_REF_MAX：图片模式最多 3 张参考图 */
export const IMAGE_REF_MAX = 3

export const RATIO_OPTIONS = [
    { key: 'smart', label: '智能', w: 18, h: 18 },
    { key: '21:9', label: '21:9', w: 28, h: 12 },
    { key: '16:9', label: '16:9', w: 26, h: 15 },
    { key: '3:2', label: '3:2', w: 24, h: 16 },
    { key: '4:3', label: '4:3', w: 22, h: 17 },
    { key: '1:1', label: '1:1', w: 18, h: 18 },
    { key: '3:4', label: '3:4', w: 16, h: 22 },
    { key: '2:3', label: '2:3', w: 14, h: 22 },
    { key: '9:16', label: '9:16', w: 12, h: 22 }
]

/** 生图宽高须为 16 的倍数（image-2 等模型要求） */
export const RATIO_PRESETS: Record<string, [number, number]> = {
    '21:9': [2464, 1056],
    '16:9': [2048, 1152],
    '3:2': [1536, 1024],
    '4:3': [1600, 1200],
    '1:1': [1088, 1088],
    '3:4': [1200, 1600],
    '2:3': [1024, 1536],
    '9:16': [1440, 2560],
    smart: [1024, 1024]
}

/** 将尺寸对齐为 ≥16 的 16 倍数；非法输入回退到 fallback */
export function snapDimToMultipleOf16(value: number, fallback = 1024): number {
    const n = Number(value)
    if (!Number.isFinite(n) || n <= 0) return fallback
    return Math.max(16, Math.round(n / 16) * 16)
}

export const PPT_PAGES = ['5-15页', '15-25页', '25-35页', '35页以上']

export const PPT_SCENES = [
    '通用',
    '项目提案',
    '公众演讲',
    '工作汇报',
    '教学课件',
    '作业展示',
    '产品推广',
    '市场宣传',
    '论文答辩',
    '学术研讨',
    '总结计划',
    '商业洽谈'
]

export const VIDEO_RATIO_OPTIONS = [
    { key: '16:9', label: '16:9', w: 26, h: 15, resolution: '720p' },
    { key: '9:16', label: '9:16', w: 12, h: 22, resolution: '720p' },
    { key: '1:1', label: '1:1', w: 18, h: 18, resolution: '720p' },
    { key: '4:3', label: '4:3', w: 22, h: 17, resolution: '720p' },
    { key: '3:4', label: '3:4', w: 16, h: 22, resolution: '720p' },
    { key: '21:9', label: '21:9', w: 28, h: 12, resolution: '720p' }
]

export const DIGITAL_MODES = [
    { key: 'dh-montage', label: '数字人口播混剪' },
    { key: 'dh-video', label: '数字人视频' },
    { key: 'human-montage', label: '真人口播混剪' },
    { key: 'material-montage', label: '素材混剪' },
    { key: 'news', label: '新闻体' }
]

export const DIGITAL_AVATARS = [
    {
        id: 'default',
        name: '默认形象',
        cover: 'https://product.imai.work/uploads/thumbnails/20260613/thumb_d2dca317c04e10ec2fa38e77dc1fa3d5.jpg',
        emoji: '👤',
        bg: 'linear-gradient(135deg,#bae6fd,#60a5fa)',
        shanjianAnchorId: '6a2d0bd4b864400031886b7b',
        chanjingAnchorId: 'C-921e187e2db44567ab00005a496995cd'
    }
]

export const DIGITAL_STYLES = [
    {
        id: 'random',
        name: '随机',
        emoji: '🎲',
        bg: 'linear-gradient(135deg,#f3f4f6,#9ca3af)'
    },
    {
        id: 'vlog',
        name: 'Vlog 日常',
        emoji: '🎬',
        bg: 'linear-gradient(135deg,#fef9c3,#fbbf24)'
    },
    {
        id: 'business',
        name: '商务专业',
        emoji: '💼',
        bg: 'linear-gradient(135deg,#dbeafe,#1d4ed8)'
    },
    {
        id: 'news',
        name: '新闻播报',
        emoji: '📰',
        bg: 'linear-gradient(135deg,#e5e7eb,#374151)'
    },
    {
        id: 'vibrant',
        name: '活力潮流',
        emoji: '🔥',
        bg: 'linear-gradient(135deg,#fecaca,#ef4444)'
    },
    {
        id: 'soft',
        name: '温柔治愈',
        emoji: '🌸',
        bg: 'linear-gradient(135deg,#fce7f3,#f472b6)'
    }
]

export const DIGITAL_VOICES = [{ id: '6a2d1196b864400031887248', name: '默认音色' }]

export const DIGITAL_SETTING_VOICES = [{ id: 'original', name: '视频原声' }, ...DIGITAL_VOICES]

export const MATERIAL_MODES = [
    { value: 'ai', label: 'AI 找素材' },
    { value: 'persona', label: '使用人设素材' },
    { value: 'ai_persona', label: 'AI + 人设素材' },
    { value: 'current', label: '当前素材' }
]
