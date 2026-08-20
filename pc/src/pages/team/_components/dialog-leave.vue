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
            <button class="cd-close" @click="showLeave = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            <div class="text-center pt-3">
                <div
                    class="w-16 h-16 mx-auto rounded-2xl grid place-items-center"
                    style="
                        background: linear-gradient(135deg, #f59e0b, #fbbf24);
                        box-shadow: 0 10px 24px -6px rgba(245, 158, 11, 0.5);
                    ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" class="w-8 h-8">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3m7 14 5-5m0 0-5-5m5 5H9" />
                    </svg>
                </div>
                <h3 class="text-[19px] font-[800] text-slate-900 mt-4">退出团队</h3>
                <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                    确定退出「<span class="font-semibold text-slate-600">{{ teamDisplayName }}</span
                    >」吗？<br />退出后需重新受邀才能加入
                </p>
            </div>

            <!-- 影响说明 -->
            <div class="rounded-xl bg-[#fffbeb] border border-amber-100 px-4 py-3.5 mt-5 flex flex-col gap-2.5">
                <div v-for="(item, i) in IMPACTS" :key="i" class="flex items-start gap-2.5">
                    <span class="w-4 h-4 rounded-full bg-amber-100 grid place-items-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5" class="w-2.5 h-2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v9m0 3.5h.01" />
                        </svg>
                    </span>
                    <span class="text-[13px] text-slate-600 leading-relaxed">{{ item }}</span>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="showLeave = false">取消</ElButton>
                <ElButton
                    type="warning"
                    class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                    :loading="leaveSubmitting"
                    @click="confirmLeave">
                    确认退出
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { TEAM_LEAVE_BIND_TIP } from "@/utils/teamSwitchTip";
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";

const { info: infoCtx } = useTeamContext();
const { info, showLeave, leaveSubmitting, confirmLeave } = infoCtx;
const { popupRef, onPopupClose } = usePopupBridge(showLeave);

const teamDisplayName = computed(() => info.value?.name || "当前团队");

const IMPACTS = ["退出后将失去团队权益，个人已有算力保留", TEAM_LEAVE_BIND_TIP];
</script>
