<template>
    <popup-bottom v-model="show" height="35%" title="选择方式" @close="emit('close')">
        <template #content>
            <view class="h-full p-[38rpx]">
                <view
                    class="h-[120rpx] flex items-center gap-x-2 px-[40rpx] bg-[#F6F6F6] rounded-[20rpx]"
                    @click="emit('recorder')">
                    <image src="@/ai_modules/digital_human/static/icons/mic.svg" class="w-[40rpx] h-[40rpx]"></image>
                    <text class="font-bold text-[30rpx]">录制音频</text>
                </view>
                <view
                    class="h-[120rpx] flex items-center gap-x-2 px-[40rpx] bg-[#F6F6F6] rounded-[20rpx] mt-[24rpx]"
                    @click="emit('file')">
                    <image
                        src="@/ai_modules/digital_human/static/icons/audio_file.svg"
                        class="w-[40rpx] h-[40rpx]"></image>
                    <text class="font-bold text-[30rpx]">从聊天记录中上传</text>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", val: boolean): void;
    (e: "recorder"): void;
    (e: "file"): void;
    (e: "close"): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});
</script>

<style scoped></style>
