<template>
    <view class="min-h-screen bg-[#F7F9FC]">
        <u-navbar
            :is-fixed="false"
            :border-bottom="false"
            is-custom-back-icon
            :custom-back="back"
            :background="{ background: 'transparent' }">
            <template #custom-back-icon>
                <view
                    class="flex items-center justify-center h-[56rpx] px-[28rpx] rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                    <text class="text-[28rpx] font-extrabold text-white tracking-wide whitespace-nowrap">完成</text>
                </view>
            </template>
        </u-navbar>

        <view class="px-[24rpx] pt-[16rpx]">
            <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                <view
                    class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <view class="flex items-center gap-[10rpx]">
                        <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                        <text class="text-[28rpx] font-extrabold text-[#0D1117]">输入文案</text>
                    </view>
                    <view class="flex items-center gap-[6rpx] h-[48rpx] px-[16rpx] rounded-[12rpx] bg-[#F0F6FF]">
                        <text
                            class="text-xs font-bold"
                            :class="formData.content?.length >= textLimit ? 'text-[#F56C6C]' : 'text-primary'">
                            {{ formData.content?.length }}
                        </text>
                        <text class="text-[22rpx] text-[#9CA3AF]">/{{ textLimit }}</text>
                    </view>
                </view>

                <view class="px-[28rpx] py-[24rpx]">
                    <view class="bg-[#F7F9FC] rounded-[20rpx] px-[20rpx] py-[20rpx]">
                        <u-input
                            v-model="formData.content"
                            placeholder="请输入您的文案..."
                            type="textarea"
                            height="480"
                            placeholder-style="font-size:26rpx;color:#C0C4CC;"
                            focus="true"
                            :maxlength="textLimit"
                            :auto-height="false" />
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();

const isEdit = ref<boolean>(false);

const formData = reactive({
    content: "",
});

const textLimit = ref(500);

const back = () => {
    if (!formData.content) {
        if (!isEdit.value) {
            uni.navigateBack();
            return;
        } else {
            uni.$u.toast("请输入文案");
            return;
        }
    }
    emit("confirm", {
        type: ListenerTypeEnum.SZR_COPYWRITER,
        data: formData.content,
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.limit) {
        textLimit.value = parseInt(options.limit);
    }
    if (options.content) {
        formData.content = options.content;
        isEdit.value = true;
    }
});
</script>

<style scoped></style>
