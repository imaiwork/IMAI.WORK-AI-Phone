<template>
    <popup
        ref="popupRef"
        width="420px"
        class="consume-detail-dialog"
        :show-close="false"
        cancel-button-text=""
        confirm-button-text=""
        footer-class="!p-0"
        header-class="!p-0"
        @close="onPopupClose">
        <div class="px-1 pb-1">
            <button class="cd-close" @click="showExpire = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
            <div class="text-center pt-3">
                <div
                    class="w-16 h-16 mx-auto rounded-2xl grid place-items-center"
                    style="
                        background: linear-gradient(135deg, #3b82f6, #60a5fa);
                        box-shadow: 0 10px 24px -6px rgba(59, 130, 246, 0.5);
                    ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" class="w-8 h-8">
                        <rect x="3" y="5" width="18" height="16" rx="3" />
                        <path stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18" />
                    </svg>
                </div>
                <h3 class="text-[19px] font-[800] text-slate-900 mt-4">设置到期时间</h3>
                <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                    设置「{{ expireRow?.nickname }}」的团队权益有效期<br />到期后将无法使用企业空间
                </p>
            </div>

            <div class="flex items-center justify-between rounded-xl bg-[#f8fafc] px-4 py-3 mt-5 text-[13px]">
                <span class="text-slate-400">当前到期</span>
                <span class="font-bold" :class="expireRow?.expired ? 'text-red-500' : 'text-slate-700'">
                    {{ expireRow?.team_expire_time_desc || "永久" }}
                    <template v-if="expireRow?.expired">（已到期）</template>
                </span>
            </div>

            <!-- 快捷时长:从今天起算 -->
            <div class="flex gap-2 mt-3">
                <button
                    v-for="opt in QUICK_OPTIONS"
                    :key="opt.label"
                    type="button"
                    class="expire-chip"
                    :class="{ active: isChipActive(opt) }"
                    @click="applyQuick(opt)">
                    {{ opt.label }}
                </button>
            </div>

            <ElDatePicker
                v-model="expireDate"
                type="datetime"
                placeholder="选择日期时间，留空为永久有效"
                class="!w-full mt-3 expire-picker"
                size="large"
                value-format="x" />

            <div class="flex gap-3 mt-6">
                <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="showExpire = false">取消</ElButton>
                <ElButton
                    type="primary"
                    class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                    :loading="saving"
                    @click="onConfirm">
                    保存
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";

const { members } = useTeamContext();
const { showExpire, expireDate, expireRow, onSaveExpire } = members;
const { popupRef, onPopupClose } = usePopupBridge(showExpire);

interface QuickOption {
    label: string;
    months: number; // 0 = 永久
}
const QUICK_OPTIONS: QuickOption[] = [
    { label: "1个月", months: 1 },
    { label: "3个月", months: 3 },
    { label: "半年", months: 6 },
    { label: "1年", months: 12 },
    { label: "永久", months: 0 },
];

const quickTs = (months: number): number | "" => {
    if (months <= 0) return "";
    const d = new Date();
    d.setMonth(d.getMonth() + months);
    return d.getTime();
};

const applyQuick = (opt: QuickOption) => {
    expireDate.value = quickTs(opt.months);
};

// 同一天视为命中该快捷项(时分误差忽略)
const isChipActive = (opt: QuickOption) => {
    if (opt.months === 0) return !expireDate.value;
    if (!expireDate.value) return false;
    const target = quickTs(opt.months);
    if (!target) return false;
    return new Date(Number(expireDate.value)).toDateString() === new Date(target).toDateString();
};

const saving = ref(false);
const onConfirm = async () => {
    if (saving.value) return;
    saving.value = true;
    try {
        await onSaveExpire();
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
.expire-chip {
    flex: 1;
    height: 32px;
    border-radius: 10px;
    font-size: 12px;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid transparent;
    transition: all 0.15s ease;
}
.expire-chip:hover {
    background: #f1f5f9;
    color: #334155;
}
.expire-chip.active {
    color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
    border-color: var(--el-color-primary-light-5);
    font-weight: 600;
}
</style>
