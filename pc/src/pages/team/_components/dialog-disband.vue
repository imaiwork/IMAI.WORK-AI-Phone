<template>
    <popup
        ref="popupRef"
        width="440px"
        class="consume-detail-dialog"
        :show-close="false"
        cancel-button-text=""
        confirm-button-text=""
        footer-class="!p-0"
        header-class="!p-0"
        @close="onPopupClose">
        <div class="px-1 pb-1">
            <button class="cd-close" @click="showDisband = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            <div class="text-center pt-3">
                <div
                    class="w-16 h-16 mx-auto rounded-2xl grid place-items-center"
                    style="
                        background: linear-gradient(135deg, #ef4444, #f87171);
                        box-shadow: 0 10px 24px -6px rgba(239, 68, 68, 0.5);
                    ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" class="w-8 h-8">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                    </svg>
                </div>
                <h3 class="text-[19px] font-[800] text-slate-900 mt-4">解散企业</h3>
                <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                    即将解散「<span class="font-semibold text-slate-600">{{ teamDisplayName }}</span
                    >」<br />此操作无法恢复，请仔细确认以下影响
                </p>
            </div>

            <!-- 影响清单 -->
            <div class="rounded-xl bg-[#fef2f2] border border-red-100 px-4 py-3.5 mt-5 flex flex-col gap-2.5">
                <div v-for="(item, i) in IMPACTS" :key="i" class="flex items-start gap-2.5">
                    <span class="w-4 h-4 rounded-full bg-red-100 grid place-items-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" class="w-2.5 h-2.5">
                            <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </span>
                    <span class="text-[13px] text-slate-600 leading-relaxed">{{ item }}</span>
                </div>
            </div>

            <!-- 二次确认 -->
            <label
                class="flex items-center gap-2.5 mt-4 px-1 cursor-pointer select-none"
                @click.prevent="acknowledged = !acknowledged">
                <span
                    class="w-[18px] h-[18px] rounded-md border grid place-items-center shrink-0 transition-colors"
                    :class="acknowledged ? 'bg-red-500 border-red-500' : 'bg-white border-slate-300'">
                    <svg
                        v-if="acknowledged"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="#fff"
                        stroke-width="3"
                        class="w-3 h-3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <span class="text-[13px] text-slate-500">我已了解上述影响，确认解散该企业</span>
            </label>

            <div class="flex gap-3 mt-5">
                <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="showDisband = false">取消</ElButton>
                <ElButton
                    type="danger"
                    class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                    :disabled="!acknowledged"
                    :loading="disbanding"
                    @click="confirmDisband">
                    确认解散
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { TEAM_DISBAND_BIND_TIP } from "@/utils/teamSwitchTip";
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";

const { info: infoCtx } = useTeamContext();
const { info, showDisband, disbanding, confirmDisband } = infoCtx;
const { popupRef, onPopupClose } = usePopupBridge(showDisband);

const teamDisplayName = computed(() => info.value?.name || "当前企业");

const IMPACTS = [
    "所有成员与归属用户将被释放，企业算力钱包与未使用卡密算力将退回给你",
    "品牌、域名、小程序等 OEM 配置将被清除，且无法恢复",
    TEAM_DISBAND_BIND_TIP,
];

// 每次打开弹窗都需重新勾选,避免误触
const acknowledged = ref(false);
watch(showDisband, (v) => {
    if (v) acknowledged.value = false;
});
</script>
