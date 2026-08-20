/** 计算基准比例 1:100（1:100 → ×1；1:1000 → ×10） */
export const BASE_RATE = 100;

/** 页面默认进货比例 1:1000 */
export const DEFAULT_RATE = 100;

export function parseCostValue(item: any): number {
    if (!item?.cast_price) return 0;
    const match = String(item.cast_price).match(/([\d.]+)/);
    return match ? parseFloat(match[1]) : 0;
}

export function isTokenBilling(item: any): boolean {
    const unit = String(item?.unit || "");
    return unit.includes("tokens") || unit.includes("token");
}

/** 当前比例相对默认 1:100 的倍数：1:100 → 1；1:1000 → 10 */
export function getRatioMultiplier(rate: number): number {
    return (Number(rate) || BASE_RATE) / BASE_RATE;
}

/** 比例进货价格 = 平台进货成本 × (当前比例 / 100) */
export function getRatioPurchasePrice(item: any, rate: number): number {
    return parseCostValue(item) * getRatioMultiplier(rate);
}

/**
 * 按目标利润率，以比例进货价格反算对客销售定价
 * 普通：售价 = 比例进货价格 × (1 + 利润率%)
 * Token：按同等利润率反算（定价数值反向）
 */
export function calcSellScoreByProfitRate(item: any, rate: number, profitRate: number): number | null {
    const ratioPrice = getRatioPurchasePrice(item, rate);
    if (ratioPrice <= 0) return null;

    const factor = 1 + profitRate / 100;
    if (isTokenBilling(item)) {
        const costVal = parseCostValue(item);
        const multiplier = getRatioMultiplier(rate);
        return costVal / (multiplier * factor);
    }
    return ratioPrice * factor;
}
