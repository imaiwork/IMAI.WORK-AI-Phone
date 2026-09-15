<template>
    <view class="issue-banner active:opacity-80" @click="emit('handle', issue.key)">
        <u-icon name="error-circle" color="#FF9900" size="32" class="shrink-0"></u-icon>
        <view class="flex-1 min-w-0 flex flex-col gap-[4rpx]">
            <text class="text-xs leading-[34rpx] text-[#CC7A00]">{{ issue.text }}</text>
            <text v-if="restCount > 0" class="text-[22rpx] text-[#CC7A00]">
                另有 {{ restCount }} 项需要处理
            </text>
        </view>
        <text class="shrink-0 text-xs font-semibold text-[#CC7A00]">{{ issue.actionText }}</text>
    </view>
</template>

<script setup lang="ts">
import type { PersonaIssueEnum } from "@/ai_modules/person/enums";
import type { PersonaIssue } from "../hooks/usePersonaIssues";

defineProps<{
    issue: PersonaIssue;
    /** 除当前这条外还剩几条 */
    restCount: number;
}>();

const emit = defineEmits<{
    (e: "handle", key: PersonaIssueEnum): void;
}>();
</script>

<style lang="scss" scoped>
.issue-banner {
    @apply flex items-center gap-[18rpx] rounded-[24rpx] px-[24rpx] py-[22rpx]
        bg-[#FFF5E6] border-[2rpx] border-solid border-[#FFE0B3];
}
</style>
