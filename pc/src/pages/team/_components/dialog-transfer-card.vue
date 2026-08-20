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
            <button class="cd-close" type="button" @click="showTransferCard = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            <div class="text-center pt-3">
                <div
                    class="w-16 h-16 mx-auto rounded-2xl grid place-items-center"
                    style="
                        background: linear-gradient(135deg, #0d9488, #2dd4bf);
                        box-shadow: 0 10px 24px -6px rgba(13, 148, 136, 0.45);
                    ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" class="w-8 h-8">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                    </svg>
                </div>
                <h3 class="text-[19px] font-[800] text-slate-900 mt-4">转移卡密</h3>
                <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                    将未使用的卡密转给团队成员或站点用户持有<br />
                    卡号 {{ transferCardRow?.card_code || "-" }} · {{ transferCardRow?.tokens ?? 0 }} 算力
                </p>
            </div>

            <div class="mt-6">
                <div class="text-[13px] font-medium text-slate-500 mb-1.5">接收成员</div>
                <ElSelect
                    v-model="transferToUserId"
                    class="w-full"
                    filterable
                    placeholder="请选择成员"
                    size="large">
                    <ElOption
                        v-for="m in transferMembers"
                        :key="m.id"
                        :label="memberLabel(m)"
                        :value="m.id"
                        :disabled="isCurrentOwner(m)" />
                </ElSelect>
                <p v-if="!transferMembers.length" class="text-[12px] text-slate-400 mt-2">
                    暂无可选接收人，请先邀请成员或等待站点用户注册
                </p>
                <p
                    v-else-if="transferMembers.every(isCurrentOwner)"
                    class="text-[12px] text-slate-400 mt-2">
                    当前仅持有人可选，请先邀请其他成员或选择站点用户
                </p>
            </div>

            <div class="flex gap-3 mt-6">
                <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="showTransferCard = false">
                    取消
                </ElButton>
                <ElButton
                    type="primary"
                    class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                    :loading="transferringCard"
                    :disabled="!transferToUserId"
                    @click="onTransferCard">
                    确认转移
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";

const { brand } = useTeamContext();
const {
    showTransferCard,
    transferringCard,
    transferCardRow,
    transferToUserId,
    transferMembers,
    onTransferCard,
} = brand;
const { popupRef, onPopupClose } = usePopupBridge(showTransferCard);

const memberLabel = (m: any) => {
    const name = m?.nickname || `用户${m?.id || ""}`;
    const mobile = m?.mobile ? ` · ${m.mobile}` : "";
    const suffix = isCurrentOwner(m) ? "（当前持有人）" : "";
    return `${name}${mobile}${suffix}`;
};

const isCurrentOwner = (m: any) => {
    const ownerId = Number(transferCardRow.value?.owner_id) || 0;
    return ownerId > 0 && Number(m?.id) === ownerId;
};
</script>
