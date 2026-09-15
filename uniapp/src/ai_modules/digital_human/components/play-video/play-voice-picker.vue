<template>
    <view class="flex flex-wrap gap-[16rpx]">
        <view
            v-for="v in VOICES"
            :key="v"
            class="px-[26rpx] py-[14rpx] rounded-[18rpx] border-[3rpx]"
            :class="
                modelValue === v ? 'bg-[#EBF2FF] border-[#2680F7]' : 'bg-white border-[transparent]'
            "
            @click="emit('update:modelValue', v)"
        >
            <text
                class="text-[22rpx] font-semibold"
                :class="modelValue === v ? 'text-[#2680F7]' : 'text-[#8A93A3]'"
            >
                {{ v }}
            </text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { VOICES } from '../../enums/play_video'

defineProps<{ modelValue: string }>()
const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()
</script>
