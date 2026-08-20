<template>
    <u-popup v-model="show" mode="center" width="90%" :border-radius="32">
        <view class="bg-white rounded-[32rpx] p-[40rpx]">
            <view class="flex items-center gap-x-[12rpx] mb-[32rpx]">
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full"></view>
                <text class="text-[30rpx] font-bold text-[#212121]">编辑名称</text>
            </view>
            <view class="bg-[#F4F6FB] px-[28rpx] py-[16rpx] rounded-[20rpx] mb-[40rpx]">
                <u-input
                    v-model="innerName"
                    placeholder="请输入名称"
                    maxlength="30"
                    clearable
                    placeholder-style="color: #9CA3AF; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-[16rpx]">
                <view
                    class="flex-1 h-[88rpx] flex items-center justify-center rounded-full bg-[#F4F6FB] active:opacity-70"
                    @click="show = false">
                    <text class="text-[28rpx] font-semibold text-[#676767]">取消</text>
                </view>
                <view
                    class="flex-1 h-[88rpx] flex items-center justify-center rounded-full bg-primary shadow-sm active:opacity-90"
                    @click="emit('confirm', innerName)">
                    <text class="text-[28rpx] font-bold text-white">确定</text>
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    modelValue: boolean;
    name: string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "update:name", v: string): void;
    (e: "confirm", v: string): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const innerName = computed({
    get: () => props.name,
    set: (v: string) => emit("update:name", v),
});
</script>
