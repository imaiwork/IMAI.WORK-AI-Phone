// 设备授权码（激活码）相关枚举与选项

// 套餐类型（type 值与后端保持一致）
// 0无 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义
export enum DeviceAuthPlanType {
    NONE = 0,
    PERMANENT = 1,
    WEEK = 2,
    MONTH = 3,
    QUARTER = 4,
    HALF_YEAR = 5,
    YEAR = 6,
    CUSTOM = 7
}

export const DEVICE_AUTH_PLAN_TYPE = [
    { label: '永久卡', value: DeviceAuthPlanType.PERMANENT },
    { label: '年卡', value: DeviceAuthPlanType.YEAR },
    { label: '半年卡', value: DeviceAuthPlanType.HALF_YEAR },
    { label: '季卡', value: DeviceAuthPlanType.QUARTER },
    { label: '月卡', value: DeviceAuthPlanType.MONTH },
    { label: '周卡', value: DeviceAuthPlanType.WEEK },
    { label: '自定义', value: DeviceAuthPlanType.CUSTOM }
]

// 设备授权状态（接口 auth_status）：0未激活 1已激活 2已过期
export enum DeviceAuthStatus {
    INACTIVE = 0,
    ACTIVATED = 1,
    EXPIRED = 2
}

export const DEVICE_AUTH_STATUS = [
    { label: '未激活', value: DeviceAuthStatus.INACTIVE, tag: 'warning' as const },
    { label: '已激活', value: DeviceAuthStatus.ACTIVATED, tag: 'success' as const },
    { label: '已过期', value: DeviceAuthStatus.EXPIRED, tag: 'danger' as const }
]

const PERMANENT_EXPIRE_TEXT = '永久'
const NOT_ACTIVATED_TEXT = ['0', 'false', 'inactive', 'unactivated', 'not_active', '未激活']

const DEVICE_AUTH_PLAN_TYPE_LABEL: Record<number, string> = {
    [DeviceAuthPlanType.PERMANENT]: '永久卡',
    [DeviceAuthPlanType.WEEK]: '周卡',
    [DeviceAuthPlanType.MONTH]: '月卡',
    [DeviceAuthPlanType.QUARTER]: '季卡',
    [DeviceAuthPlanType.HALF_YEAR]: '半年卡',
    [DeviceAuthPlanType.YEAR]: '年卡',
    [DeviceAuthPlanType.CUSTOM]: '自定义'
}

export const getPlanTypeLabel = (value: unknown): string => {
    if (value === null || value === undefined || value === '' || typeof value === 'boolean') return ''
    const num = Number(value)
    return Number.isNaN(num) ? '' : DEVICE_AUTH_PLAN_TYPE_LABEL[num] ?? ''
}

export const isPermanentPlanType = (value: unknown): boolean => {
    if (value === null || value === undefined || value === '' || typeof value === 'boolean') return false
    return Number(value) === DeviceAuthPlanType.PERMANENT
}

/** 统一解析授权状态：优先 auth_status，缺失时兼容历史字段 */
export const getDeviceAuthStatus = (device: Record<string, any>): DeviceAuthStatus => {
    const raw = device.auth_status
    if (raw !== undefined && raw !== null && raw !== '') {
        const num = Number(raw)
        if (num === DeviceAuthStatus.ACTIVATED) return DeviceAuthStatus.ACTIVATED
        if (num === DeviceAuthStatus.EXPIRED) return DeviceAuthStatus.EXPIRED
        return DeviceAuthStatus.INACTIVE
    }
    const legacy =
        device.is_active ??
        device.is_activated ??
        device.activate_status ??
        device.activation_status ??
        device.active_status
    if (legacy === true) return DeviceAuthStatus.ACTIVATED
    if (typeof legacy === 'number') return legacy === 1 ? DeviceAuthStatus.ACTIVATED : DeviceAuthStatus.INACTIVE
    if (typeof legacy === 'string') {
        return NOT_ACTIVATED_TEXT.includes(legacy) ? DeviceAuthStatus.INACTIVE : DeviceAuthStatus.ACTIVATED
    }
    return DeviceAuthStatus.INACTIVE
}

export const getExpireRemainMs = (expireTime?: string): number | null => {
    if (!expireTime || String(expireTime).trim() === PERMANENT_EXPIRE_TEXT) return null
    const normalizedExpireTime = String(expireTime).replace(/年|-/g, '/').replace(/月/g, '/').replace(/日/g, '').trim()
    const expireMs = Date.parse(normalizedExpireTime)
    if (Number.isNaN(expireMs)) return null
    return expireMs - Date.now()
}

export const getExpireRemainText = (expireTime?: string): string => {
    const remainMs = getExpireRemainMs(expireTime)
    if (remainMs === null) return '已激活'
    if (remainMs < 0) return '已过期'
    if (remainMs >= 86400000) return `剩余 ${Math.floor(remainMs / 86400000)} 天`
    if (remainMs >= 3600000) return `剩余 ${Math.ceil(remainMs / 3600000)} 小时`
    return `剩余 ${Math.max(1, Math.ceil(remainMs / 60000))} 分钟`
}

/** 是否已过期（auth_status===2，或过期时间已到） */
export const isDeviceExpired = (device: Record<string, any>): boolean => {
    const remainMs = getExpireRemainMs(device.auth_expire_time)
    return getDeviceAuthStatus(device) === DeviceAuthStatus.EXPIRED || (remainMs !== null && remainMs < 0)
}

/** 是否已激活（仅 auth_status===1 且未过期） */
export const isDeviceActivated = (device: Record<string, any>): boolean =>
    getDeviceAuthStatus(device) === DeviceAuthStatus.ACTIVATED && !isDeviceExpired(device)

/** 永久卡：auth_type===1、过期时间为「永久」，或套餐名含永久关键字 */
export const isDevicePermanent = (device: Record<string, any>): boolean => {
    if (isPermanentPlanType(device.auth_type)) return true
    if (String(device.auth_expire_time ?? '').trim() === PERMANENT_EXPIRE_TEXT) return true
    return /permanent|forever|永久|终身/.test(String(device.auth_type_name ?? device.cdk_type_name ?? ''))
}

/** 套餐名称：cdk_type_name / auth_type_name 优先，其次按数字码映射 */
export const getDevicePlanName = (device: Record<string, any>): string =>
    device.cdk_type_name || device.auth_type_name || getPlanTypeLabel(device.auth_type) || ''

/** 授权状态/有效期文案（对齐 PC 设备列表） */
export const getDeviceAuthStatusText = (device: Record<string, any>): string => {
    if (!isDeviceActivated(device) && !isDeviceExpired(device)) return '未激活'
    if (isDeviceExpired(device)) return '已过期'
    if (isDevicePermanent(device)) return '永久有效'
    return getExpireRemainText(device.auth_expire_time)
}

/** 授权状态标签类型（Element Plus Tag） */
export const getDeviceAuthStatusTag = (device: Record<string, any>): 'success' | 'warning' | 'danger' | 'info' => {
    if (!isDeviceActivated(device) && !isDeviceExpired(device)) return 'danger'
    if (isDeviceExpired(device)) return 'danger'
    if (isDevicePermanent(device)) return 'warning'
    const remainMs = getExpireRemainMs(device.auth_expire_time)
    if (remainMs === null) return 'success'
    const days = Math.floor(remainMs / 86400000)
    if (days <= 7) return 'danger'
    if (days <= 30) return 'warning'
    return 'success'
}

/** 过期时间展示：未激活显示 -，永久卡显示「永久」 */
export const getDeviceExpireTimeText = (device: Record<string, any>): string => {
    if (!isDeviceActivated(device) && !isDeviceExpired(device)) return '-'
    if (isDevicePermanent(device)) return PERMANENT_EXPIRE_TEXT
    return device.auth_expire_time || '-'
}

// 激活码使用状态
export enum DeviceAuthCodeStatus {
    UNUSED = 0,
    USED = 1
}

export const DEVICE_AUTH_CODE_STATUS = [
    { label: '未使用', value: DeviceAuthCodeStatus.UNUSED },
    { label: '已使用', value: DeviceAuthCodeStatus.USED }
]

// 套餐启用状态
export enum DeviceAuthPlanStatus {
    DISABLED = 0,
    ENABLED = 1
}

// 订单支付方式
export const DEVICE_AUTH_PAY_TYPE = [
    { label: '在线支付', value: 1 },
    { label: '算力支付', value: 2 }
]

// 订单支付状态
export enum DeviceAuthPayStatus {
    UNPAID = 0,
    PAID = 1
}

export const DEVICE_AUTH_PAY_STATUS = [
    { label: '待支付', value: DeviceAuthPayStatus.UNPAID },
    { label: '已支付', value: DeviceAuthPayStatus.PAID }
]

// 订单业务类型
export const DEVICE_AUTH_BIZ_TYPE = [
    { label: '购买授权码', value: 1 },
    { label: '设备续费', value: 2 }
]
