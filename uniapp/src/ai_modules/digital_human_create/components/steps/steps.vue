<template>
    <view class="flex items-start">
        <view
            v-for="item in steps"
            :key="item.step"
            class="flex-1 flex flex-col items-center relative"
            @click="emit('step', item.step)">
            <view
                v-if="item.step < steps.length"
                class="absolute top-[28rpx] left-1/2 w-full h-[4rpx] rounded-full z-0 transition-all duration-500"
                :class="step > item.step ? 'bg-primary' : 'bg-[#F0F2F5]'" />
            <view
                class="relative z-10 w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center transition-all duration-300"
                :class="{
                    'bg-primary shadow-[0_0_0_8rpx_rgba(28,111,235,0.12)]': step === item.step,
                    'bg-primary': step > item.step,
                    'bg-[#F0F2F5]': step < item.step,
                }">
                <u-icon v-if="step > item.step" name="checkmark" color="#fff" size="20" />
                <text
                    v-else
                    class="text-[22rpx] font-bold"
                    :class="step === item.step ? 'text-white' : 'text-[#9CA3AF]'">
                    {{ item.step }}
                </text>
            </view>
            <text
                class="mt-[8rpx] text-[20rpx] transition-all duration-300"
                :class="{
                    'text-primary font-bold': step === item.step,
                    'text-[#4B5563] font-semibold': step > item.step,
                    'text-[#9CA3AF] font-medium': step < item.step,
                }">
                {{ item.title }}
            </text>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = defineProps<{
    steps: any[];
    step: number;
}>();

const emit = defineEmits<{
    (e: "step", step: number): void;
}>();
</script>

<style scoped></style>
