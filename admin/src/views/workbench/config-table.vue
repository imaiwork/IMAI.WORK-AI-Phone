<template>
    <div>
        <el-card class="!border-none" shadow="never" v-if="data.length > 0">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div
                    v-for="item in data"
                    :key="item.scene"
                    class="bg-white rounded-xl border flex flex-col relative overflow-hidden"
                    :class="isZeroCost(item) ? 'border-[#86efac]' : 'border-[#e5e7eb]'">
                    <div
                        class="px-5 py-3 border-b flex justify-between items-center"
                        :class="isZeroCost(item) ? 'border-[#bbf7d0] bg-[#f0fdf4]' : 'border-[#f3f4f6] bg-[#f9fafb]'">
                        <h3 class="font-bold text-[#1f2937]">{{ item.name }}</h3>
                        <span class="text-xs bg-[#e5e7eb] text-[#4b5563] px-2 py-1 rounded">
                            {{ getBillingLabel(item) }}
                        </span>
                    </div>

                    <div class="p-5 flex-1">
                        <div class="mb-4">
                            <div class="text-xs text-[#6b7280] mb-1 flex justify-between">
                                <span>平台进货成本</span>
                                <span
                                    class="font-medium"
                                    :class="isZeroCost(item) ? 'text-[#16a34a]' : 'text-[#f97316]'">
                                    {{ getCostLabel(item) }}{{ item.unit }}
                                </span>
                            </div>
                            <div
                                v-if="isZeroCost(item)"
                                class="bg-[#f0fdf4] rounded p-2 text-sm flex justify-between items-center border border-[#bbf7d0]">
                                <span class="text-[#166534]">折合人民币成本：</span>
                                <span
                                    class="inline-flex items-center gap-1 bg-[#16a34a] text-white text-xs font-bold px-3 py-1 rounded">
                                    🎁 限时免费
                                </span>
                            </div>
                            <div
                                v-else
                                class="bg-[#fff7ed] rounded p-2 text-sm flex justify-between items-center border border-[#ffedd5]">
                                <span class="text-[#9a3412]">折合人民币成本：</span>
                                <span class="font-bold text-[#ea580c]">{{ getCostRmb(item) }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="text-xs text-[#2563eb] font-bold mb-1 flex justify-between items-end">
                                <span>对客销售定价</span>
                                <div class="flex items-center">
                                    <span class="text-xs text-[#6b7280] ml-1">{{ item.price }}{{ item.unit }}</span>
                                </div>
                            </div>
                            <div
                                class="bg-[#eff6ff] rounded p-2 text-sm flex justify-between items-center border border-[#bfdbfe]">
                                <span class="text-[#1e40af]">用户实际支付：</span>
                                <span class="font-bold text-[#2563eb]">{{ getSellRmb(item) }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="px-5 py-3 border-t flex justify-between items-center"
                        :class="isZeroCost(item) ? 'bg-[#f0fdf4] border-[#bbf7d0]' : 'bg-[#f9fafb] border-[#f3f4f6]'">
                        <div class="text-sm text-[#4b5563] font-medium">
                            单笔利润
                            <span class="text-xs text-[#9ca3af] font-normal">{{ getProfitUnit(item) }}</span>
                        </div>
                        <div class="text-right flex flex-col items-end">
                            <div
                                class="text-lg font-black"
                                :class="
                                    getProfitValue(item) === 0
                                        ? 'text-[#6b7280]'
                                        : getProfitValue(item) > 0
                                        ? 'text-[#16a34a]'
                                        : 'text-[#dc2626]'
                                ">
                                {{ getProfitDisplay(item) }}
                            </div>
                            <div
                                class="text-xs mt-1 font-bold px-2 py-1 rounded"
                                :class="
                                    getProfitValue(item) === 0
                                        ? 'text-[#4b5563] bg-[#e5e7eb]'
                                        : getProfitValue(item) > 0
                                        ? 'text-[#15803d] bg-[#dcfce7]'
                                        : 'text-[#b91c1c] bg-[#fee2e2]'
                                ">
                                {{ getProfitValue(item) === 0 ? "盈亏平衡" : getProfitMarginDisplay(item) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </el-card>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    data: any[];
}>();

const globalRate = defineModel<number>("globalRate", { default: 100 });

function getBillingType(
    item: any,
): "tokens" | "times" | "seconds" | "rows" | "images" | "minutes" | "pieces" | "words" {
    if (!item.unit) return "times";
    const unit: string = item.unit;
    if (unit.includes("tokens") || unit.includes("token")) return "tokens";
    if (unit.includes("秒")) return "seconds";
    if (unit.includes("分钟")) return "minutes";
    if (unit.includes("条")) return "rows";
    if (unit.includes("张")) return "images";
    if (unit.includes("个")) return "pieces";
    if (unit.includes("字")) return "words";
    return "times"; // 算力/次 兜底
}

function parseCostValue(item: any): number {
    if (!item.cast_price) return 0;
    const cp: string = item.cast_price.toString();
    const match = cp.match(/([\d.]+)/);
    return match ? parseFloat(match[1]) : 0;
}

/** 是否为0成本 */
function isZeroCost(item: any): boolean {
    return parseCostValue(item) === 0;
}

function getBillingLabel(item: any): string {
    const type = getBillingType(item);
    const map: Record<string, string> = {
        tokens: "按 Token 计费",
        seconds: "按 秒 计费",
        minutes: "按 分钟 计费",
        rows: "按 条 计费",
        images: "按 张 计费",
        pieces: "按 个 计费",
        times: "按 次 计费",
        words: "按 字 计费",
    };
    return map[type] ?? "按 次 计费";
}

function getCostLabel(item: any): string {
    return item.cast_price ?? "";
}

function getCostRmb(item: any): string {
    const rate = globalRate.value || 100;
    const costVal = parseCostValue(item);
    if (costVal === 0) return "免费";
    let rmb: number;
    if (getBillingType(item) === "tokens") {
        rmb = 1000 / costVal / rate;
    } else {
        rmb = costVal / rate;
    }
    return `￥${rmb.toFixed(4)}`;
}

function getSellRmb(item: any): string {
    const rate = globalRate.value || 100;
    const sell = item.price || 0;
    let rmb: number;
    if (getBillingType(item) === "tokens") {
        rmb = sell > 0 ? 1000 / sell / rate : 0;
    } else {
        rmb = sell / rate;
    }
    return `￥${rmb.toFixed(4)}`;
}

function getProfitValue(item: any): number {
    const rate = globalRate.value || 100;
    const costVal = parseCostValue(item);
    const sell = item.price || 0;
    if (getBillingType(item) === "tokens") {
        const costRmb = costVal === 0 ? 0 : 1000 / costVal / rate;
        const sellRmb = sell > 0 ? 1000 / sell / rate : 0;
        return sellRmb - costRmb;
    } else {
        const costRmb = costVal / rate;
        const sellRmb = sell / rate;
        return sellRmb - costRmb;
    }
}

function getProfitDisplay(item: any): string {
    const val = getProfitValue(item);
    const sign = val >= 0 ? "+ " : "- ";
    return `${sign}￥${Math.abs(val).toFixed(4)}`;
}

function getProfitMarginDisplay(item: any): string {
    const rate = globalRate.value || 100;
    const costVal = parseCostValue(item);
    const sell = item.price || 0;
    // 0成本特殊处理
    if (costVal === 0) {
        return "纯利润: 100% (0成本)";
    }
    let costRmb: number;
    let sellRmb: number;
    if (getBillingType(item) === "tokens") {
        costRmb = 1000 / costVal / rate;
        sellRmb = sell > 0 ? 1000 / sell / rate : 0;
    } else {
        costRmb = costVal / rate;
        sellRmb = sell / rate;
    }
    const profit = getProfitValue(item);
    if (profit >= 0) {
        const margin = (profit / costRmb) * 100;
        return `利润率: ${margin.toFixed(1)}%`;
    } else {
        const lossRate = (Math.abs(profit) / costRmb) * 100;
        return `亏损率: ${lossRate.toFixed(1)}% (倒贴)`;
    }
}

function getProfitUnit(item: any): string {
    return getBillingType(item) === "tokens" ? "(每 1k tokens)" : "(每次调用)";
}

function getStep(item: any): number {
    return getBillingType(item) === "tokens" ? 100 : 0.5;
}
</script>

<style scoped lang="scss">
.rate-input :deep(.el-input__inner) {
    text-align: center;
    font-size: 1.2rem;
    font-weight: 900;
    color: #2563eb;
    border: none;
    border-bottom: 2px solid #3b82f6;
    border-radius: 0;
    background: transparent;
}
</style>
