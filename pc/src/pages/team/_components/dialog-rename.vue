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
            <button class="cd-close" @click="showRename = false">
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
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                    </svg>
                </div>
                <h3 class="text-[19px] font-[800] text-slate-900 mt-4">修改企业名称</h3>
                <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                    名称将同步展示给所有成员与归属用户
                </p>
            </div>

            <ElInput
                v-model="renameValue"
                class="mt-5 rename-input"
                size="large"
                maxlength="100"
                show-word-limit
                clearable
                placeholder="输入新的企业名称"
                @keyup.enter="confirmRename" />

            <div class="flex gap-3 mt-6">
                <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="showRename = false">取消</ElButton>
                <ElButton
                    type="primary"
                    class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                    :disabled="!renameValue.trim()"
                    :loading="renameSubmitting"
                    @click="confirmRename">
                    保存
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";

const { info: infoCtx } = useTeamContext();
const { showRename, renameValue, renameSubmitting, confirmRename } = infoCtx;
const { popupRef, onPopupClose } = usePopupBridge(showRename);
</script>

<style scoped>
.rename-input :deep(.el-input__wrapper) {
    border-radius: 12px;
    padding: 4px 14px;
}
</style>
