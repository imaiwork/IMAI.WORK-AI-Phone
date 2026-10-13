// ================================================================
// types.ts  —  抖音账号搜寻获客：枚举 / 常量 / 表单类型 / 默认值
// ================================================================

export const STEPS = [
    { step: 1, title: '选择类型' },
    { step: 2, title: '设置线索' },
    { step: 3, title: '填设置' },
    { step: 4, title: '设定时间' }
]

/** 搜寻方式 */
export enum SearchModeEnum {
    /** 用行业泛词搜一批同类账号 */
    KEYWORD = 'keyword',
    /** 已经有名单，逐个进去查 */
    ACCOUNT = 'account'
}

export const SEARCH_MODE_LIST = [
    {
        value: SearchModeEnum.KEYWORD,
        icon: 'search',
        name: '关键词搜寻',
        desc: '用行业泛词搜一批同类账号'
    },
    {
        value: SearchModeEnum.ACCOUNT,
        icon: 'account-fill',
        name: '指定账号',
        desc: '已经有名单，逐个进去查'
    }
]

/** 进到账号主页后，从哪几处找联系方式 */
export enum ScanPositionEnum {
    BIO = 'bio',
    DOUYIN_ID = 'dyid',
    COVER = 'cover'
}

export const SCAN_POSITION_LIST = [
    {
        value: ScanPositionEnum.BIO,
        name: '简介',
        desc: '最常见的位置。「微信同号」「V：xxx」这类写法都认。'
    },
    {
        value: ScanPositionEnum.DOUYIN_ID,
        name: '抖音号',
        desc: '很多商家直接把抖音号改成手机号或微信号。默认号会被排除，见下方规则。'
    },
    // 封面这项的说明跟着张数变，由 config-step 自己拼，这里留空
    { value: ScanPositionEnum.COVER, name: '置顶作品封面', desc: '' }
]

/**
 * 页面上真正露出来的查看位置。
 * 「置顶作品封面」按需求先不露出，但 scan_cover / cover_count / stitch 仍按默认值提交，
 * 所以只从渲染和摘要里摘掉，不动 createDefaultFormData 里的 scan_positions。
 */
export const VISIBLE_SCAN_POSITION_LIST = SCAN_POSITION_LIST.filter(
    (item) => item.value !== ScanPositionEnum.COVER
)

/** 识别哪几类联系方式 */
export enum ContactTypeEnum {
    PHONE = 'phone',
    WECHAT = 'wechat',
    QQ = 'qq'
}

export const CONTACT_TYPE_LIST = [
    { value: ContactTypeEnum.PHONE, name: '手机号', desc: '11 位、1[3-9] 开头' },
    {
        value: ContactTypeEnum.WECHAT,
        name: '微信号',
        desc: '字母开头 6~20 位，含字母/数字/下划线/减号'
    },
    { value: ContactTypeEnum.QQ, name: 'QQ 号', desc: '5~11 位纯数字，且带「Q」「扣」等提示词' }
]

/** 多张置顶封面的拼接方向 */
export enum StitchEnum {
    H = 'h',
    V = 'v',
    BOTH = 'both'
}

export const STITCH_LIST = [
    { value: StitchEnum.H, label: '横向' },
    { value: StitchEnum.V, label: '纵向' },
    { value: StitchEnum.BOTH, label: '都试' }
]

/** 简介、抖音号都没找到联系方式时怎么办（封面这处目前未露出） */
export enum MissActionEnum {
    /** 私信主动问 */
    DM = 'dm',
    /** 直接跳过 */
    SKIP = 'skip'
}

/** 识别到多种联系方式时，拿哪一种去加好友（值是接口码值，别改成中文） */
export enum AddRuleEnum {
    ALL = 'all',
    PHONE = 'phone',
    WECHAT = 'wechat',
    PHONE_FIRST = 'phone_first'
}

export const ADD_RULE_LIST = [
    { value: AddRuleEnum.ALL, label: '全部' },
    { value: AddRuleEnum.PHONE, label: '仅手机号' },
    { value: AddRuleEnum.WECHAT, label: '仅微信号' },
    { value: AddRuleEnum.PHONE_FIRST, label: '手机号优先' }
]

/** 每个线索词最多取多少个账号，0 = 不限 */
export const PER_KEYWORD_LIST = [
    { value: 10, label: '10 个' },
    { value: 30, label: '30 个' },
    { value: 50, label: '50 个' },
    { value: 100, label: '100 个' },
    { value: 0, label: '不限' }
]

/** 一天最多新增多少条客资，0 = 不限 */
export const DAY_CAP_LIST = [
    { value: 20, label: '20 个' },
    { value: 50, label: '50 个' },
    { value: 100, label: '100 个' },
    { value: 0, label: '不限' }
]

/** 取前几个置顶作品的封面 */
export const COVER_COUNT_LIST = [1, 2, 3, 4, 5]

/**
 * 默认抖音号长什么样——把判断规则摊开给用户看，不然「为什么这个号没抓到」
 * 只能靠猜。前两条是系统分配的默认格式，后两条是用户自己改过的，才有联系意义。
 */
export const ID_EXAMPLES = [
    { id: 'douyin123456789', ok: false, why: '默认号' },
    { id: '87654321012', ok: false, why: '默认号' },
    { id: '13800138000', ok: true, why: '手机号' },
    { id: 'changjia_888', ok: true, why: '微信号' }
]

/** 拼图示意里每格写的假号码片段，只用于画示意图 */
export const COVER_DEMO_PARTS = ['加微', '信 138', '0013 8000', '备用 139', '0013 9000']

export const DEFAULT_MISS_DM_LIST = [
    '老板你好，看到您做这块，方便留个微信吗？',
    '请问怎么联系您？想咨询下产品',
    '有微信吗～想问下价格',
    '您好，能加个微信详聊吗'
]

export const DEFAULT_REMARK_LIST = [
    '抖音看到的~ 咨询一下~',
    '老板，你好，抖音~',
    '抖音刷到的，通过一下~',
    '你好～通过一下～',
    'hello～想咨询一下～'
]

/** 数字输入的下限，低于它按下限兜底 */
export const NUM_MIN = {
    fans_min: 0,
    works_min: 0,
    active_days: 1,
    add_number: 1,
    add_interval_time: 1
} as const

export type NumFieldKey = keyof typeof NUM_MIN

/**
 * 数字输入统一走这里：空串和负数都按下限兜底。
 * 不能用 parseInt——'1e3' 是 number 输入框的合法值（=1000），parseInt 只取到 1。
 */
export function clampNumField(key: NumFieldKey, value: unknown): number {
    const min = NUM_MIN[key]
    const num = Math.floor(Number(value))
    return !isFinite(num) || num < min ? min : num
}

export interface AccountSearchFormData {
    // ── 第 1 步 ──
    search_mode: SearchModeEnum
    // ── 第 2 步 ──
    keywords: string[]
    /** 指定账号模式下的账号名单，提交时和 keywords 合并成同一个字段 */
    target_accounts: string[]
    per_kw: number
    pre_filter: number
    fans_min: number
    works_min: number
    active_days: number
    only_biz: number
    // ── 第 3 步：看哪里 ──
    scan_positions: ScanPositionEnum[]
    cover_count: number
    stitch: StitchEnum
    // ── 第 3 步：认什么 ──
    contact_types: ContactTypeEnum[]
    phone_as_wechat: number
    // ── 第 3 步：没识别到 ──
    miss_action: MissActionEnum
    miss_scripts: string[]
    miss_once: number
    // ── 第 3 步：识别到之后 ──
    add_type: number
    /** 加微客服微信，存 account(wxid) 逗号分隔；选择组件的数组由页面转换 */
    wechat_id: string
    add_rule: AddRuleEnum
    add_number: number
    add_interval_time: number
    add_remark_enable: number
    remarks: string[]
    // ── 第 4 步（base-setting 契约字段）──
    name: string
    device_codes: string[]
    task_frep: number
    custom_date: string[]
    time_config: string[]
    task_exec_type: number
    minutes: number
    day_cap: number
    task_ids: any[]
}

/** 任务名默认带创建时间戳，和站内其他获客任务一个格式 */
export function createDefaultTaskName(): string {
    const d = new Date()
    const p = (n: number) => String(n).padStart(2, '0')
    return `抖音搜寻获客任务${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}${p(
        d.getHours()
    )}${p(d.getMinutes())}`
}

export function createDefaultFormData(): AccountSearchFormData {
    return {
        search_mode: SearchModeEnum.KEYWORD,
        keywords: [],
        target_accounts: [],
        per_kw: 30,
        pre_filter: 1,
        fans_min: 500,
        works_min: 5,
        active_days: 90,
        only_biz: 0,
        scan_positions: [ScanPositionEnum.BIO, ScanPositionEnum.DOUYIN_ID, ScanPositionEnum.COVER],
        cover_count: 3,
        stitch: StitchEnum.H,
        contact_types: [ContactTypeEnum.PHONE, ContactTypeEnum.WECHAT],
        phone_as_wechat: 1,
        miss_action: MissActionEnum.DM,
        miss_scripts: [...DEFAULT_MISS_DM_LIST],
        miss_once: 1,
        add_type: 0,
        wechat_id: '',
        add_rule: AddRuleEnum.ALL,
        add_number: 15,
        add_interval_time: 10,
        add_remark_enable: 0,
        remarks: [...DEFAULT_REMARK_LIST],
        name: createDefaultTaskName(),
        device_codes: [],
        task_frep: 1,
        custom_date: [],
        // 默认时段跟着当前时间走（与站内其他获客任务一致）。
        // 写死 09:00 的话，晚上建的任务摘要里会显示一个早就过去的时间。
        time_config: [
            uni.$u.timeFormat(new Date(), 'hh:MM'),
            uni.$u.timeFormat(new Date(new Date().getTime() + 30 * 60 * 1000), 'hh:MM')
        ],
        task_exec_type: 1,
        minutes: 15,
        day_cap: 50,
        task_ids: []
    }
}
