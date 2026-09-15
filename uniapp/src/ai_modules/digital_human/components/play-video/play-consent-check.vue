<template>
    <view class="flex items-center gap-[10rpx]" @click="emit('update:modelValue', !modelValue)">
        <!-- 小程序里 border 默认 style 是 none：只给宽度和颜色、不给 border-solid，未勾选时这个圈在真机上是隐形的 -->
        <view
            class="w-[32rpx] h-[32rpx] rounded-full border-[3rpx] border-solid flex items-center justify-center flex-shrink-0"
            :class="modelValue ? 'bg-[#2680F7] border-[#2680F7]' : 'bg-white border-[#C9D2E0]'"
        >
            <u-icon v-if="modelValue" name="checkmark" color="#fff" size="16" />
        </view>
        <text class="text-[21rpx] text-[#4B5563]">
            我确认使用<text class="font-bold">本人授权</text>的人脸照片
        </text>
    </view>
</template>

<script setup lang="ts">
defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()
</script>
