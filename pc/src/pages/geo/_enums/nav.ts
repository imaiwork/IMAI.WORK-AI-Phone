export type GeoTabKey =
    | 'visibility'
    | 'sentiment'
    | 'quotes'
    | 'scene'
    | 'snapshots'
    | 'manage'
    | 'generate'
    | 'publish'
    | 'kb'
    | 'agents'
    | 'set_topic'
    | 'set_brand'
    | 'set_account'

export type GeoGroupKey = 'monitor' | 'content' | 'assets' | 'settings'

export interface GeoNavTab {
    key: GeoTabKey
    label: string
}

export interface GeoNavGroup {
    key: GeoGroupKey
    label: string
    icon: string
    tabs: GeoNavTab[]
}

export const DEFAULT_GEO_TAB: GeoTabKey = 'visibility'

export const GEO_GROUPS: GeoNavGroup[] = [
    {
        key: 'monitor',
        label: '监测',
        icon: 'el-icon-DataLine',
        tabs: [
            { key: 'visibility', label: '可见度' },
            { key: 'sentiment', label: '舆情' },
            { key: 'quotes', label: '引用' },
            { key: 'scene', label: '场景' },
            { key: 'snapshots', label: '快照' }
        ]
    },
    {
        key: 'content',
        label: '内容',
        icon: 'el-icon-Document',
        tabs: [
            { key: 'manage', label: '文章' },
            { key: 'generate', label: '生成' },
            { key: 'publish', label: '发布' }
        ]
    },
    {
        key: 'assets',
        label: '资产',
        icon: 'el-icon-FolderOpened',
        tabs: [
            { key: 'kb', label: '品牌语料' },
            { key: 'agents', label: 'GEO 助手' }
        ]
    },
    {
        key: 'settings',
        label: '设置',
        icon: 'el-icon-Setting',
        tabs: [
            { key: 'set_topic', label: '话题' },
            { key: 'set_brand', label: '品牌画像' },
            { key: 'set_account', label: '授权账号' }
        ]
    }
]

const TAB_SET = new Set(GEO_GROUPS.flatMap((g) => g.tabs.map((t) => t.key)))

export const resolveGeoTab = (raw: string): GeoTabKey => {
    return TAB_SET.has(raw as GeoTabKey) ? (raw as GeoTabKey) : DEFAULT_GEO_TAB
}

export const groupOfTab = (tab: GeoTabKey): GeoGroupKey => {
    return GEO_GROUPS.find((g) => g.tabs.some((t) => t.key === tab))?.key || 'monitor'
}

export const tabsOfGroup = (group: GeoGroupKey): GeoNavTab[] => {
    return GEO_GROUPS.find((g) => g.key === group)?.tabs || []
}

/** 与后端 GeoTopicLogic::STEP_* / 向导 STEPS 下标对齐 */
export const WIZARD_STEPS = ['设定品牌信息', '确定话题', '设置场景问题', 'AI诊断报告'] as const

/** 诊断未提交时后端 resume_step=3，停在场景问题页以便提交；已提交批次则停在诊断报告看进度 */
export const wizardPageOfResume = (resumeStep: number, diagnosisSubmitted = false) => {
    if (resumeStep === 3) return diagnosisSubmitted ? 3 : 2
    if (resumeStep >= 0 && resumeStep <= 2) return resumeStep
    return 0
}

export const geoDiagnosisSubmitted = (state: {
    running_batch_id?: number
    progress?: { cell_done?: number }
} | null | undefined) => {
    return Number(state?.running_batch_id || 0) > 0 || Number(state?.progress?.cell_done || 0) > 0
}

export const wizardLabelOfResume = (resumeStep: number) => {
    return WIZARD_STEPS[resumeStep] || WIZARD_STEPS[0]
}

export interface GeoListStatus {
    key: 'ready' | 'diagnose' | 'paused' | 'setup'
    label: string
    tag: 'success' | 'warning' | 'info'
    hint: string
}

export const geoListStatus = (p: {
    initialized?: boolean
    interrupted?: boolean
    resume_step?: number
    hint?: string
}): GeoListStatus => {
    if (p.initialized) return { key: 'ready', label: '已就绪', tag: 'success', hint: '' }
    const step = Number(p.resume_step ?? 0)
    const name = wizardLabelOfResume(step)
    if (step === 3) return { key: 'diagnose', label: '待提交诊断', tag: 'warning', hint: p.hint || '场景问题已备齐，提交后开始监测' }
    if (p.interrupted) return { key: 'paused', label: `进行到「${name}」`, tag: 'warning', hint: p.hint || '' }
    return { key: 'setup', label: `进行到「${name}」`, tag: 'info', hint: '' }
}
