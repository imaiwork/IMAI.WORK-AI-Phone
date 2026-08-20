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
            <button class="cd-close" @click="showRemove = false">
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
                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-3 5 5m0-5-5 5" />
                    </svg>
                </div>
                <h3 class="text-[19px] font-[800] text-slate-900 mt-4">移出团队</h3>
                <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                    确定将「<span class="font-semibold text-slate-600">{{ removeRow?.nickname }}</span
                    >」移出团队吗？<br />移出后需重新邀请才能加入
                </p>
            </div>

            <!-- 影响说明 -->
            <div class="rounded-xl bg-[#fef2f2] border border-red-100 px-4 py-3.5 mt-5 flex flex-col gap-2.5">
                <div v-for="(item, i) in impacts" :key="i" class="flex items-start gap-2.5">
                    <span class="w-4 h-4 rounded-full bg-red-100 grid place-items-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" class="w-2.5 h-2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v9m0 3.5h.01" />
                        </svg>
                    </span>
                    <span class="text-[13px] text-slate-600 leading-relaxed">{{ item }}</span>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="showRemove = false">取消</ElButton>
                <ElButton
                    type="danger"
                    class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                    :loading="removeSubmitting"
                    @click="confirmRemoveMember">
                    确认移出
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";
import { formatNum } from "../_composables/helpers";

const { members } = useTeamContext();
const { showRemove, removeRow, removeSubmitting, confirmRemoveMember } = members;
const { popupRef, onPopupClose } = usePopupBridge(showRemove);

const impacts = computed(() => {
    const tokens = Number(removeRow.value?.tokens) || 0;
    const list = [
        tokens > 0
            ? `其在本企业剩余的算力（${formatNum(tokens)}）将退回给超级管理员`
            : "其企业算力钱包已无余额，无需退回",
        "名下未使用的团队卡密与会员兑换码将收回给团队主",
        "其创建的智能体与知识库将不再共享给团队",
    ];
    return list;
});
</script>
