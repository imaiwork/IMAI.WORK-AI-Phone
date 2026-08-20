<template>
    <view class="flex items-center gap-[6rpx] px-[14rpx] py-[4rpx] rounded border" :style="badgeStyle.style">
        <text class="text-[20rpx]" :style="{ color: badgeStyle.color }">{{ badgeStyle.label }}</text>
    </view>
</template>

<script setup lang="ts">
import { FocusEnum } from "@/ai_modules/person/pages/task_flow/stores/taskStore";
const props = defineProps<{ focus: FocusEnum }>();

const badgeStyle = computed(() => {
    switch (props.focus) {
        case FocusEnum.GENERAL:
            return {
                style: "background:#F3F4F6;border-color:#E5E7EB;",
                color: "#4B5563",
                label: "综合",
            };
        case FocusEnum.ACQUIRE:
            return {
                style: "background:#FFF7ED;border-color:#FED7AA;",
                color: "#EA580C",
                label: "偏获客",
            };
        case FocusEnum.OPERATE:
            return {
                style: "background:#F0FDF4;border-color:#BBF7D0;",
                color: "#16A34A",
                label: "偏养号",
            };
        case FocusEnum.NURTURE:
            return {
                style: "background:#EFF6FF;border-color:#BFDBFE;",
                color: "#2563EB",
                label: "偏运营",
            };
    }
});
</script>
