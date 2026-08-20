<template>
    <popup-bottom v-model="show" title="选择创建任务" height="30%">
        <template #content>
            <view class="flex flex-col gap-[16rpx] p-[32rpx]">
                <view
                    class="w-full h-[96rpx] rounded-full bg-primary flex items-center justify-center shadow-md active:opacity-90"
                    @click="emit('select', 'timer')">
                    <text class="text-[28rpx] font-bold text-white">创建定时发布任务</text>
                </view>
                <view
                    class="w-full h-[96rpx] rounded-full bg-[#F4F6FB] flex items-center justify-center active:opacity-70"
                    @click="emit('select', 'qrcode')">
                    <text class="text-[28rpx] font-semibold text-[#676767]">创建发布视频二维码</text>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { computed } from "vue";

export type PublishTaskType = "timer" | "qrcode";

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "select", type: PublishTaskType): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});
</script>
