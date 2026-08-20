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
            <button class="cd-close" @click="showFeature = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            <div class="text-center pt-3">
                <div
                    class="w-16 h-16 mx-auto rounded-2xl grid place-items-center"
                    style="
                        background: linear-gradient(135deg, #8b5cf6, #a78bfa);
                        box-shadow: 0 10px 24px -6px rgba(139, 92, 246, 0.5);
                    ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" class="w-8 h-8">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 3l1.9 5.8H20l-4.9 3.6 1.9 5.8-5-3.6-5 3.6 1.9-5.8L4 8.8h6.1Z" />
                    </svg>
                </div>
                <h3 class="text-[19px] font-[800] text-slate-900 mt-4">请求开通功能</h3>
                <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                    申请开通「<span class="font-semibold text-slate-600">{{ featureApp?.label }}</span
                    >」<br />提交后由平台处理，开通结果可在本页查看
                </p>
            </div>

            <div class="flex items-center justify-between rounded-xl bg-[#f8fafc] px-4 py-3 mt-5 text-[13px]">
                <span class="text-slate-400">申请功能</span>
                <span class="font-bold text-slate-700">{{ featureApp?.label || "-" }}</span>
            </div>

            <div class="flex gap-3 mt-6">
                <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="showFeature = false">取消</ElButton>
                <ElButton
                    type="primary"
                    class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                    :loading="featureSubmitting"
                    @click="confirmRequestFeature">
                    提交申请
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";

const { info: infoCtx } = useTeamContext();
const { showFeature, featureApp, featureSubmitting, confirmRequestFeature } = infoCtx;
const { popupRef, onPopupClose } = usePopupBridge(showFeature);
</script>
