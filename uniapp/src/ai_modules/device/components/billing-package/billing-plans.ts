// 套餐唯一标识：静态兜底用字符串，接口数据用 plan_id
export type PlanKey = string | number;

// 套餐类型（接口 type，与 admin / 后端保持一致）：0无 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义
// 注意：本文件被 packages 子包（user.vue）跨子包引用，必须保持零依赖、可安全共享，
// 因此套餐类型枚举/工具就近定义在此，不从 device 子包内的 enums 引入。
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

// 永久卡判定：仅当 type 为数字码且等于 PERMANENT(1) 时为真，文本/空不误判
export const isPermanentPlanType = (value: unknown): boolean => {
    if (value === null || value === undefined || value === "" || typeof value === "boolean") return false;
    return Number(value) === DeviceAuthPlanType.PERMANENT;
};

export type BillingPayType = "compute" | "cash";

export interface PlanItem {
    key: PlanKey;
    planId: number | string;
    name: string;
    price: number;
    compute: number;
    computeLabel: string;
    daysText: string;
    validText: string;
    activationText: string;
    isPermanent: boolean;
    recommend?: boolean;
    /** 微信小程序虚拟支付产品ID */
    productId?: string;
}

export const formatBillingNumber = (value: number | string) => {
    const text = String(value || 0).replace(/,/g, "");
    const [integer, decimal] = text.split(".");
    const formattedInteger = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return decimal ? `${formattedInteger}.${decimal}` : formattedInteger;
};

const toNumber = (value: any) => {
    const num = Number(value);
    return Number.isNaN(num) ? 0 : num;
};

// 接口套餐字段不稳定，统一在此归一化为 PlanItem
export const normalizePlan = (plan: Record<string, any>, index = 0): PlanItem => {
    const planId = plan.plan_id ?? plan.id ?? plan.key ?? index;
    const price = toNumber(plan.price ?? plan.amount ?? plan.cash ?? plan.money);
    const compute = toNumber(plan.tokens_price ?? plan.compute ?? plan.tokens ?? plan.power);
    const days = plan.duration_days ?? plan.days ?? plan.duration ?? plan.valid_days;
    const numberDays = toNumber(days);
    const name = plan.name;
    const isPermanent =
        plan.is_permanent === 1 || plan.is_permanent === true || isPermanentPlanType(plan.type ?? plan.plan_type);

    return {
        key: planId,
        planId,
        name,
        price,
        compute,
        computeLabel: formatBillingNumber(compute),
        daysText: isPermanent ? "永久" : `${numberDays} 天`,
        validText: isPermanent ? "永久有效" : `有效期 ${numberDays} 天`,
        activationText: isPermanent ? "永久有效" : `${numberDays} 天有效`,
        isPermanent,
        recommend: !!(plan.recommend ?? plan.is_recommend ?? plan.is_hot),
        productId: String(plan.product_id ?? plan.productId ?? "").trim() || undefined,
    };
};

// 过滤下架套餐（status===0），按 sort 升序后归一化
export const normalizePlanList = (list: any[] | undefined | null): PlanItem[] =>
    Array.isArray(list)
        ? list
              .filter((item) => item?.status === undefined || Number(item.status) !== 0)
              .slice()
              .sort((a, b) => toNumber(a?.sort) - toNumber(b?.sort))
              .map((item, index) => normalizePlan(item, index))
        : [];

// 选中套餐：默认第一个
export const resolveDefaultPlanKey = (list: PlanItem[]): PlanKey => list[0]?.key ?? "";

// ---- 设备授权状态归一化（device list / detail 统一入口，对齐后端 auth_* 字段） ----
// 设备列表接口字段：
//   auth_type        套餐类型数字码（0无 1永久卡 2周卡 …7自定义）
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

// 是否已激活（仅 auth_status===1；已过期视为未激活，需续费/重新激活）
export const isDeviceActivated = (device: Record<string, any>): boolean =>
    getDeviceAuthStatus(device) === DeviceAuthStatus.ACTIVATED && !isDeviceExpired(device);

// 永久卡：auth_type===1、过期时间为 "永久"，或套餐名含永久关键字
export const isDevicePermanent = (device: Record<string, any>): boolean => {
    if (isPermanentPlanType(device.auth_type)) return true;
    if (String(device.auth_expire_time ?? "").trim() === PERMANENT_EXPIRE_TEXT) return true;
    if (device.is_permanent === 1 || device.is_permanent === true) return true;
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

export type ActivationCodeStatus = "unused" | "used";

export interface ActivationCodeItem {
    code: string;
    planName: string;
    daysLabel: string;
    status: ActivationCodeStatus;
    buyTime: string;
    usedDeviceName?: string;
    usedTime?: string;
    [key: string]: any;
}

const USED_TEXT = ["used", "activated", "is_used", "1", "已使用", "已激活"];

// 接口激活码字段不稳定，统一归一化为 ActivationCodeItem
export const normalizeActivationCode = (code: Record<string, any>): ActivationCodeItem => {
    const statusValue = code.status ?? code.use_status ?? code.used_status ?? code.activate_status;
    const usedTime = code.used_time ?? code.use_time ?? code.activate_time ?? "";
    let isUsed: boolean;
    if (typeof statusValue === "number") isUsed = statusValue === 1;
    else if (typeof statusValue === "boolean") isUsed = statusValue;
    else if (statusValue !== undefined && statusValue !== null) isUsed = USED_TEXT.includes(`${statusValue}`);
    else isUsed = !!usedTime;

    const days = code.days ?? code.duration ?? code.valid_days ?? code.duration_days;
    const numberDays = toNumber(days);
    const isPermanent =
        code.is_permanent === 1 ||
        code.is_permanent === true ||
        isPermanentPlanType(code.type ?? code.plan_type) ||
        /永久|终身|permanent|forever/i.test(`${code.plan_type ?? code.type ?? code.plan_name ?? ""}`);

    return {
        ...code,
        code: code.code ?? code.card_no ?? code.card_code ?? code.sn ?? "",
        planName:
            code.plan_name ??
            code.planName ??
            code.name ??
            code.type_desc ??
            (getPlanTypeLabel(code.type ?? code.plan_type) || "CDK"),
        daysLabel:
            code.days_label ?? code.daysLabel ?? (isPermanent ? "永久" : numberDays ? `${numberDays} 天` : "CDK"),
        status: isUsed ? "used" : "unused",
        buyTime: code.purchase_time ?? "",
        usedDeviceName: code.used_device_name ?? code.usedDeviceName ?? code.device_name ?? code.device_code ?? "",
        usedTime,
    };
};

export const normalizeActivationCodeList = (list: any[] | undefined | null): ActivationCodeItem[] =>
    Array.isArray(list) ? list.map((item) => normalizeActivationCode(item)) : [];
