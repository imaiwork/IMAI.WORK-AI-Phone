<template>
    <u-popup v-model="show" mode="center" border-radius="24" width="90%">
        <view class="bg-white rounded-[24rpx] p-[48rpx]">
            <view class="font-bold text-center">图片/视频上传须知</view>
            <view class="mt-[48rpx]">
                <view class="flex items-center gap-x-1">
                    <text class="text-[#FF3C26]">*</text>
                    <text>视频素材时长范围：{{ videoDuration[0] }}s-{{ videoDuration[1] }}s；{{ videoSize }}MB内</text>
                </view>
                <view class="flex items-center gap-x-1 mt-[32rpx]">
                    <text class="text-[#FF3C26]">*</text>
                    <text
                        >图片素材支持{{ imageAccept.join("、") }}格式；{{ imageSize }}MB内，分辨率不超过{{
                            imageResolution[0]
                        }}*{{ imageResolution[1] }}</text
                    >
                </view>
                <view class="flex items-center gap-x-1 mt-[32rpx]">
                    <text class="text-[#FF3C26]">*</text>
                    <text>所有素材只支持H.264编码</text>
                </view>
                <view class="flex items-center gap-x-1 mt-[32rpx]">
                    <text class="text-[#FF3C26]">*</text>
                    <text>不符合条件的素材会被自动删除</text>
                </view>
            </view>
            <view class="flex gap-x-3 mt-[48rpx]">
                <view
                    class="flex-1 h-[100rpx] flex items-center justify-center shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] rounded-[16rpx]"
                    @click="
                        emit('close');
                        show = false;
                    "
                    >取消</view
                >
                <view
                    class="flex-1 h-[100rpx] flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,101,251,0.2)] bg-[#000000] rounded-[16rpx] text-white"
                    @click="
                        emit('handle-upload');
                        show = false;
                    "
                    >去上传</view
                >
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
