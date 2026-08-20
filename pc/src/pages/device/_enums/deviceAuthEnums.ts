// 设备激活码（授权码）相关枚举与工具

// 预支付 / 支付状态 业务来源标识
export const DEVICE_AUTH_PAY_FROM = "device_auth";

// 手机列表筛选 tab
export type DeviceAuthTabKey = "all" | "active" | "inactive";

// 前端 tab → 接口 tab 参数（全部传空字符串）
export const DEVICE_AUTH_TAB_PARAM: Record<DeviceAuthTabKey, string> = {
    all: "",
    active: "active",
    inactive: "inactive",
};

// 支付方式（接口 pay_type）：1 付费，2 算力（与 uniapp / 后端保持一致）
export enum DeviceAuthPayType {
    CASH = 1,
    COMPUTE = 2,
}

// 套餐类型（接口 auth_type，与 admin / 后端保持一致）：
// 0无 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义
export enum DeviceAuthPlanType {
    NONE = 0,
    PERMANENT = 1,
    WEEK = 2,
    MONTH = 3,
    QUARTER = 4,
    HALF_YEAR = 5,
    YEAR = 6,
    CUSTOM = 7,
}

export const DEVICE_AUTH_PLAN_TYPE_LABEL: Record<number, string> = {
    [DeviceAuthPlanType.PERMANENT]: "永久卡",
    [DeviceAuthPlanType.WEEK]: "周卡",
    [DeviceAuthPlanType.MONTH]: "月卡",
    [DeviceAuthPlanType.QUARTER]: "季卡",
    [DeviceAuthPlanType.HALF_YEAR]: "半年卡",
    [DeviceAuthPlanType.YEAR]: "年卡",
    [DeviceAuthPlanType.CUSTOM]: "自定义",
};

// 数字类型码 → 卡片名称；非数字（文本/空）返回空串，由调用方走文本兜底
export const getPlanTypeLabel = (value: unknown): string => {
    if (value === null || value === undefined || value === "" || typeof value === "boolean") return "";
    const num = Number(value);
    return Number.isNaN(num) ? "" : DEVICE_AUTH_PLAN_TYPE_LABEL[num] ?? "";
};

// 永久卡判定（按数字类型码）：仅当等于 PERMANENT(1) 时为真，文本/空不误判
export const isPermanentPlanType = (value: unknown): boolean => {
    if (value === null || value === undefined || value === "" || typeof value === "boolean") return false;
    return Number(value) === DeviceAuthPlanType.PERMANENT;
};

// ---- 设备授权状态归一化（对齐后端 auth_* 字段） ----
// 设备列表接口字段：
//   auth_type        套餐类型数字码（0无 1永久卡 …7自定义）
//   auth_status      授权状态：0未激活 1已激活 2已过期
//   auth_start_time  激活时间
//   auth_expire_time 过期时间（永久卡为字符串 "永久"）
//   auth_type_name   套餐名称（如 "无" / "永久卡" / "月卡"）
//   auth_code        激活码

// 授权状态（接口 auth_status）
export enum DeviceAuthStatus {
    INACTIVE = 0,
    ACTIVATED = 1,
    EXPIRED = 2,
}

const PERMANENT_EXPIRE_TEXT = "永久";
const NOT_ACTIVATED_TEXT = ["0", "false", "inactive", "unactivated", "not_active", "未激活"];

// 统一解析授权状态：优先 auth_status，缺失时兼容历史布尔/字符串字段
export const getDeviceAuthStatus = (device: Record<string, any>): DeviceAuthStatus => {
    const raw = device.auth_status;
    if (raw !== undefined && raw !== null && raw !== "") {
        const num = Number(raw);
        if (num === DeviceAuthStatus.ACTIVATED) return DeviceAuthStatus.ACTIVATED;
        if (num === DeviceAuthStatus.EXPIRED) return DeviceAuthStatus.EXPIRED;
        return DeviceAuthStatus.INACTIVE;
    }
    const legacy =
        device.is_active ??
        device.is_activated ??
        device.activate_status ??
        device.activation_status ??
        device.active_status;
    if (legacy === true) return DeviceAuthStatus.ACTIVATED;
    if (typeof legacy === "number") return legacy === 1 ? DeviceAuthStatus.ACTIVATED : DeviceAuthStatus.INACTIVE;
    if (typeof legacy === "string")
        return NOT_ACTIVATED_TEXT.includes(legacy) ? DeviceAuthStatus.INACTIVE : DeviceAuthStatus.ACTIVATED;
    return DeviceAuthStatus.INACTIVE;
};

const getDeviceExpireRemainMs = (device: Record<string, any>): number | null => {
    const expire = device.auth_expire_time;
    if (!expire || String(expire).trim() === PERMANENT_EXPIRE_TEXT) return null;
    const normalizedExpireTime = String(expire).replace(/年|-/g, "/").replace(/月/g, "/").replace(/日/g, "").trim();
    const ms = Date.parse(normalizedExpireTime);
    if (Number.isNaN(ms)) return null;
    return ms - Date.now();
};

// 是否已过期（auth_status===2，或 auth_expire_time 剩余时间小于 0）
export const isDeviceExpired = (device: Record<string, any>): boolean => {
    const remainMs = getDeviceExpireRemainMs(device);
    return getDeviceAuthStatus(device) === DeviceAuthStatus.EXPIRED || (remainMs !== null && remainMs < 0);
};

// 设备是否已激活（仅 auth_status===1；已过期视为未激活，需续费/重新激活）
export const isDeviceActivated = (device: Record<string, any>): boolean =>
    getDeviceAuthStatus(device) === DeviceAuthStatus.ACTIVATED && !isDeviceExpired(device);

// 永久卡：auth_type===1、过期时间为 "永久"，或套餐名含永久关键字
export const isDevicePermanent = (device: Record<string, any>): boolean => {
    if (isPermanentPlanType(device.auth_type)) return true;
    if (String(device.auth_expire_time ?? "").trim() === PERMANENT_EXPIRE_TEXT) return true;
    return /permanent|forever|永久|终身/.test(String(device.auth_type_name ?? ""));
};

// 套餐名称：后端 auth_type_name 优先，其次按数字码映射
export const getDevicePlanName = (device: Record<string, any>): string =>
    device.auth_type_name || getPlanTypeLabel(device.auth_type) || "";

// 剩余天数：后端直接给则用之，否则按过期时间 auth_expire_time 计算；永久/无到期返回 null
export const getDeviceRemainDays = (device: Record<string, any>): number | null => {
    const direct = device.remain_days ?? device.remaining_days;
    if (direct !== undefined && direct !== null && direct !== "") {
        const num = Number(direct);
        return Number.isNaN(num) ? null : num;
    }
    const expire = device.auth_expire_time;
    if (!expire || String(expire).trim() === PERMANENT_EXPIRE_TEXT) return null;
    const remainMs = getDeviceExpireRemainMs(device);
    if (remainMs === null) return null;
    return Math.floor(remainMs / 86400000);
};

export interface DeviceAuthPlan {
    id: number;
    name: string;
    type: number;
    type_desc?: string;
    duration_days: number;
    price: string | number;
    tokens_price: number;
    is_recommend: number;
    sort: number;
    status: number;
    [key: string]: any;
}

// 套餐有效期文案
export const getPlanDurationText = (plan: DeviceAuthPlan): string => {
    const days = Number(plan.duration_days);
    return !days || days <= 0 ? "永久有效" : `${days} 天有效`;
};

// 千分位格式化
export const formatTokens = (value: number | string): string => {
    const text = String(value ?? 0).replace(/,/g, "");
    const [integer, decimal] = text.split(".");
    const formatted = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return decimal ? `${formatted}.${decimal}` : formatted;
};
