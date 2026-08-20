<template>
    <popup
        ref="popupRef"
        width="480px"
        class="consume-detail-dialog"
        :show-close="false"
        cancel-button-text=""
        confirm-button-text=""
        footer-class="!p-0"
        header-class="!p-0"
        @close="onPopupClose">
        <div v-if="outputRow">
            <div class="flex items-start justify-between mb-5">
                <div class="flex items-center gap-3 min-w-0">
                    <ElAvatar :size="44" :src="outputRow.avatar || undefined" class="shrink-0 !rounded-2xl">
                        {{ (outputRow.user_name || "U").slice(0, 1) }}
                    </ElAvatar>
                    <div class="min-w-0">
                        <h3 class="text-[17px] font-[800] text-slate-900 leading-tight">消耗详情</h3>
                        <p class="text-[12px] text-slate-400 mt-1">{{ outputRow.create_time }}</p>
                    </div>
                </div>
                <button class="cd-close" @click="showOutput = false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <div
                class="flex items-center justify-between rounded-2xl p-4 mb-4"
                style="background: linear-gradient(135deg, #f8fafc, #eef2f7)">
                <div class="flex items-center gap-3 min-w-0">
                    <ElAvatar :size="40" :src="outputRow.avatar || undefined" class="shrink-0 !rounded-xl">
                        {{ (outputRow.user_name || "U").slice(0, 1) }}
                    </ElAvatar>
                    <div class="min-w-0">
                        <div class="text-[14px] font-semibold text-slate-800 truncate">{{ outputRow.user_name }}</div>
                        <div class="text-[12px] text-slate-400 truncate">
                            {{ outputRow.biz_name }}
                            <template v-if="outputRow.type_desc">（{{ outputRow.type_desc }}）</template>
                        </div>
                    </div>
                </div>
                <div class="text-right shrink-0 pl-3">
                    <div class="text-[11px] text-slate-400 mb-0.5">
                        {{ Number(outputRow.action) === 1 ? "退回算力" : "消耗算力" }}
                    </div>
                    <div
                        class="text-[22px] font-[900] leading-none"
                        :class="Number(outputRow.action) === 1 ? 'text-emerald-500' : 'text-amber-500'">
                        {{ Number(outputRow.action) === 1 ? "+" : "-" }}{{ formatNum(outputRow.change_amount) }}
                    </div>
                </div>
            </div>

            <div
                v-if="outputRow.remark"
                class="flex items-start gap-2 rounded-xl bg-[#f8fafc] px-3.5 py-2.5 text-[13px]">
                <span class="text-slate-400 shrink-0">备注</span>
                <span class="text-slate-600">{{ outputRow.remark }}</span>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";
import { formatNum } from "../_composables/helpers";

const { consumption } = useTeamContext();
const { showOutput, outputRow } = consumption;
const { popupRef, onPopupClose } = usePopupBridge(showOutput);
</script>
