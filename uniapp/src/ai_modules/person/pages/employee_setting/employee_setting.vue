<template>
    <view class="h-screen overflow-hidden bg-[#F3F7FC] flex flex-col">
        <view class="shrink-0 bg-white z-40">
            <u-navbar
                :border-bottom="false"
                :background="{ background: '#FFFFFF' }"
                title="修改人设"
                title-bold
                :custom-back="back" />
        </view>

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-[36rpx] pt-[28rpx] pb-[400rpx]">
                    <template v-if="!pageLoading">
                        <basic-info-step
                            ref="basicInfoStepRef"
                            :basic-form="basicFormForChild"
                            :avatar-url="formData.avatar_url"
                            @update:avatar="handleAvatarUpdate"
                            @popup-visible-change="basicPopupVisible = $event" />

                        <business-desc-step
                            :is-add-mode="false"
                            :analysis-token-score="getAnalysisTokenScore"
                            :current-sub-form="currentSubForm" />
                    </template>
                </view>
            </scroll-view>
        </view>

        <view
            v-show="!footerHidden"
            class="fixed bottom-0 left-0 right-0 bg-white px-[36rpx] pt-[26rpx] shadow-[0_-4rpx_20rpx_rgba(0,0,0,0.05)] z-50"
            style="padding-bottom: calc(24rpx + env(safe-area-inset-bottom))">
            <view v-if="pageLoading" class="animate-pulse">
                <view class="h-[100rpx] rounded-[28rpx] bg-[#E5E7EB]"></view>
            </view>
            <u-button
                v-else
                type="primary"
                shape="square"
                :loading="submitting"
                :ripple="true"
                :custom-style="{
                    height: '100rpx',
                    borderRadius: '28rpx',
                    fontSize: '30rpx',
                    fontWeight: '900',
                    border: 'none',
                    background: 'linear-gradient(135deg, #3D82F7, #2563EB)',
                    boxShadow: '0 12rpx 40rpx rgba(47, 115, 246, 0.28)',
                }"
                @click="handleFooterClick">
                <u-icon name="checkmark" color="#ffffff" size="28"></u-icon>
                <text class="ml-[12rpx]">{{ footerButtonText }}</text>
            </u-button>
        </view>
    </view>

    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import RecorderControl from "@/ai_modules/person/components/recorder-control/recorder-control.vue";
import BasicInfoStep from "../create/components/basic-info-step.vue";
import BusinessDescStep from "../create/components/business-desc-step.vue";
import { useEmployeeSetting } from "./hooks/useEmployeeSetting";

const {
    back,
    basicForm,
    currentSubForm,
    footerButtonText,
    formData,
    getAnalysisTokenScore,
    handleAvatarUpdate,
    handleFooterClick,
    init,
    pageLoading,
    setPersonId,
    showRecorder,
    submitting,
} = useEmployeeSetting();

const basicFormForChild = basicForm as any;
const basicPopupVisible = ref(false);
const footerHidden = computed(() => basicPopupVisible.value || showRecorder.value);

onLoad((options: any) => {
    if (options.tab === "emp") {
        uni.redirectTo({
            url: `/ai_modules/person/pages/ai_employee/ai_employee?id=${options.id ?? ""}`,
        });
        return;
    }
    setPersonId(options.id);
    init();
});
</script>
