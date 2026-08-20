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
            <button class="cd-close" @click="showEditTokens = false">
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v7h5l-7 11v-7H6l7-11z" />
                    </svg>
                </div>
                <h3 class="text-[19px] font-[800] text-slate-900 mt-4">修改算力</h3>
                <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                    给「{{ editTokensRow?.nickname }}」分配本企业算力<br />调高从超管算力扣除，调低退回超管
                </p>
            </div>
            <button
                type="button"
                class="w-full flex items-center justify-between rounded-xl bg-[#f8fafc] px-4 py-3 mt-5 text-[13px] hover:bg-[#f1f5f9] transition-colors"
                @click="onViewCurrentDetail">
                <span class="text-slate-400">当前算力</span>
                <span class="inline-flex items-center gap-1">
                    <span class="font-bold text-primary">{{ formatNum(editTokensRow?.tokens ?? 0) }}</span>
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="w-3.5 h-3.5 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                    </svg>
                </span>
            </button>
            <div class="flex items-center justify-between rounded-xl bg-[#f8fafc] px-4 py-3 mt-2 text-[13px]">
                <span class="text-slate-400">可分配上限</span>
                <span class="font-bold text-slate-700">{{ formatNum(editTokensMax) }}</span>
            </div>
            <ElInput
                v-model="editTokensValue"
                v-number-input="{ min: 0, decimal: 2, max: editTokensMax }"
                placeholder="请输入算力数量"
                size="large"
                class="mt-3"
                @keyup.enter="submitEditTokens">
                <template #suffix><span class="text-slate-400 text-[13px]">算力</span></template>
            </ElInput>
            <div class="flex gap-3 mt-6">
                <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="showEditTokens = false">取消</ElButton>
                <ElButton
                    type="primary"
                    class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                    :loading="editTokensLoading"
                    @click="submitEditTokens">
                    保存
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";
import { formatNum } from "../_composables/helpers";

const { members, consumption } = useTeamContext();
const { showEditTokens, editTokensRow, editTokensValue, editTokensLoading, editTokensMax, submitEditTokens } = members;
const { onConsumption } = consumption;
const { popupRef, onPopupClose } = usePopupBridge(showEditTokens);

const onViewCurrentDetail = () => {
    const row = editTokensRow.value;
    if (!row) return;
    showEditTokens.value = false;
    nextTick(() => onConsumption(row));
};
</script>
