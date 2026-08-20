<template>
    <u-popup v-model="show" mode="center" border-radius="32" width="90%">
        <view class="bg-white rounded-[32rpx] overflow-hidden">
            <view
                class="flex items-center justify-center gap-[10rpx] px-[40rpx] h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                style="background: linear-gradient(135deg, #ebf2ff 0%, #f0f9ff 100%)">
                <u-icon name="info-circle-fill" color="#0065fb" size="28" />
                <text class="text-[30rpx] font-extrabold text-[#0D1117]">图片/视频上传须知</text>
            </view>

            <view class="px-[40rpx] py-[32rpx] flex flex-col gap-[20rpx]">
                <view class="bg-[#F0F6FF] rounded-[20rpx] px-[24rpx] py-[20rpx] flex items-start gap-[14rpx]">
                    <view
                        class="w-[32rpx] h-[32rpx] rounded-full bg-primary flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                        <text class="text-white text-[18rpx] font-extrabold">1</text>
                    </view>
                    <text class="text-xs text-[#374151] leading-relaxed flex-1">
                        视频素材时长范围：<text class="text-primary font-semibold"
                            >{{ videoDuration[0] }}s - {{ videoDuration[1] }}s</text
                        >；大小 <text class="text-primary font-semibold">{{ videoSize }}MB</text> 以内
                    </text>
                </view>

                <view class="bg-[#F0F6FF] rounded-[20rpx] px-[24rpx] py-[20rpx] flex items-start gap-[14rpx]">
                    <view
                        class="w-[32rpx] h-[32rpx] rounded-full bg-primary flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                        <text class="text-white text-[18rpx] font-extrabold">2</text>
                    </view>
                    <text class="text-xs text-[#374151] leading-relaxed flex-1">
                        图片支持
                        <text class="text-primary font-semibold">{{ imageAccept.join("、") }}</text>
                        格式；大小
                        <text class="text-primary font-semibold">{{ imageSize }}MB</text>
                        以内，分辨率不超过
                        <text class="text-primary font-semibold"
                            >{{ imageResolution[0] }}*{{ imageResolution[1] }}</text
                        >
                    </text>
                </view>

                <!-- 编码须知 -->
                <view class="bg-[#F0F6FF] rounded-[20rpx] px-[24rpx] py-[20rpx] flex items-start gap-[14rpx]">
                    <view
                        class="w-[32rpx] h-[32rpx] rounded-full bg-primary flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                        <text class="text-white text-[18rpx] font-extrabold">3</text>
                    </view>
                    <text class="text-xs text-[#374151] leading-relaxed flex-1">
                        所有素材只支持
                        <text class="text-primary font-semibold">H.264、H.265</text> 编码
                    </text>
                </view>

                <view class="bg-[#FEF2F2] rounded-[20rpx] px-[24rpx] py-[20rpx] flex items-start gap-[14rpx]">
                    <view
                        class="w-[32rpx] h-[32rpx] rounded-full bg-[#EF4444] flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                        <u-icon name="warning" color="#fff" size="16" />
                    </view>
                    <text class="text-xs text-[#EF4444] leading-relaxed flex-1 font-semibold">
                        不符合条件的素材会被自动删除
                    </text>
                </view>
            </view>

            <view class="flex items-center gap-[16rpx] px-[40rpx] pb-[40rpx]">
                <view
                    class="flex-1 h-[96rpx] flex items-center justify-center rounded-[24rpx] bg-[#F0F2F5] border border-solid border-[#E5E9F0]"
                    @click="
                        emit('close');
                        show = false;
                    ">
                    <text class="text-[28rpx] font-semibold text-[#4B5563]">取消</text>
                </view>
                <view
                    class="flex-1 h-[96rpx] flex items-center justify-center rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_24rpx_rgba(0,101,251,0.30)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="
                        emit('handle-upload');
                        show = false;
                    ">
                    <text class="text-[28rpx] font-extrabold text-white">去上传</text>
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { montageConfig } from "@/ai_modules/digital_human/config";

const props = withDefaults(defineProps<{ modelValue: boolean }>(), {
    modelValue: false,
});

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "handle-upload"): void;
    (e: "close"): void;
}>();

const { imageAccept, imageSize, imageResolution, videoSize, videoDuration } = montageConfig;

const show = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit("update:modelValue", value);
    },
});
</script>

<style scoped></style>
