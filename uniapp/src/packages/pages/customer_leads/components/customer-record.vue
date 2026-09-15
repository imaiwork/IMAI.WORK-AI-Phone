<template>
    <view class="customer-record" @click="emit('detail', customer)">
        <view class="record-header" hover-class="record-pressed">
            <view class="record-avatar">
                <image
                    v-if="customer.avatar && !avatarFailed"
                    :src="customer.avatar"
                    class="h-full w-full"
                    mode="aspectFill"
                    @error="avatarFailed = true"
                />
                <text v-else>{{ avatarInitial }}</text>
            </view>
            <view class="min-w-0 flex-1">
                <text class="record-name">{{ customer.name }}</text>
                <text class="record-origin">
                    {{ customer.platformDesc }} · {{ customer.source }}
                </text>
            </view>
            <u-icon name="arrow-right" color="#637187" :size="28" />
        </view>

        <view class="record-intent" hover-class="record-pressed" @click.stop="handleChat">
            <text class="record-intent-label">最新意图</text>
            <text>{{ customer.latestMessage || '暂无意图记录' }}</text>
        </view>

        <view class="record-contact">
            <u-icon name="account" color="#637187" :size="26" />
            <text class="min-w-0 break-all">
                {{ customer.wechat ? `微信：${customer.wechat}` : '未识别到微信号' }}
            </text>
            <text
                v-if="customer.wxStatusText"
                class="record-status"
                :class="customer.wxStatus === 'added' ? 'bg-success-light-9' : 'bg-warning-light-9'"
            >
                {{ customer.wxStatusText }}
            </text>
        </view>

        <view class="record-evidence">
            <template v-if="isPrivate">
                <view>
                    <text>私聊消息</text>
                    <text class="record-value">{{
                        formatDisplayNumber(customer.privateMessages)
                    }}</text>
                </view>
                <view>
                    <text>朋友圈互动</text>
                    <text class="record-value">{{
                        formatDisplayNumber(customer.momentActions)
                    }}</text>
                </view>
            </template>
            <template v-else>
                <view>
                    <text>平台回复</text>
                    <text class="record-value">{{
                        formatDisplayNumber(customer.publicReplies)
                    }}</text>
                </view>
                <text class="min-w-0 break-all">
                    首次私聊 {{ customer.firstChatTime || '未私聊' }}
                </text>
            </template>
        </view>

        <view
            v-if="customer.momentSummary"
            class="record-interaction"
            hover-class="record-pressed"
            @click.stop="handleInteraction"
        >
            <text>{{ isPrivate ? '朋友圈互动' : '平台互动' }}：</text>
            <text>{{ customer.momentSummary }}</text>
        </view>

        <view v-if="customer.groupName" class="record-group" @click.stop>
            <u-icon name="chat" color="currentColor" :size="26" />
            <text class="min-w-0 break-all">当前群聊：{{ customer.groupName }}</text>
        </view>

        <view class="record-actions">
            <view class="record-action" hover-class="record-pressed" @click.stop="handleChat">
                <u-icon name="chat" color="currentColor" :size="26" />
                <text>{{ isPrivate ? '私聊记录' : '聊天记录' }}</text>
            </view>
            <view
                class="record-action"
                hover-class="record-pressed"
                @click.stop="handleInteraction"
            >
                <u-icon name="heart" color="currentColor" :size="26" />
                <text>{{ isPrivate ? '朋友圈互动' : '平台互动' }}</text>
            </view>
            <view
                class="record-action record-action-primary"
                hover-class="record-pressed"
                @click.stop="emit('track', customer)"
            >
                <u-icon name="clock" color="currentColor" :size="26" />
                <text>跟进记录</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { formatNumberToWanOrYi } from '@/utils/util'
import type { ActivityType, Customer } from './types'

const props = defineProps<{ customer: Customer }>()
const emit = defineEmits<{
    (event: 'detail', customer: Customer): void
    (event: 'track', customer: Customer): void
    (event: 'activity', customer: Customer, type: ActivityType): void
}>()

const avatarFailed = ref(false)
const avatarInitial = computed(() => Array.from(props.customer.name || '客')[0])
const isPrivate = computed(() => props.customer.domain === 'private')
const handleChat = () => emit('activity', props.customer, 'chat')
const handleInteraction = () => emit('activity', props.customer, 'moments')

watch(
    () => props.customer.avatar,
    () => {
        avatarFailed.value = false
    }
)

const formatDisplayNumber = (value: number) => String(formatNumberToWanOrYi(value || 0))
</script>

<style lang="scss" scoped>
.customer-record {
    @apply bg-white px-[32rpx] pt-[34rpx];
}

.record-header {
    @apply flex min-h-[88rpx] w-full items-center gap-[20rpx];
}

.record-avatar {
    @apply flex h-[76rpx] w-[76rpx] shrink-0 items-center justify-center overflow-hidden rounded-[24rpx] bg-primary-light-9 text-[32rpx] font-medium text-primary;
}

.record-name {
    @apply block line-clamp-1 text-[30rpx] font-semibold leading-[40rpx] text-[#1D2B41];
}

.record-origin {
    @apply mt-[6rpx] block line-clamp-1 text-[22rpx] leading-[32rpx] text-[#637187];
}

.record-intent {
    @apply mt-[30rpx] mb-[26rpx] line-clamp-2 break-all text-[26rpx] leading-[46rpx] text-[#1D2B41];
}

.record-intent-label {
    @apply mr-[12rpx] text-[22rpx] text-[#637187];
}

.record-contact,
.record-group {
    @apply my-[22rpx] flex flex-wrap items-center gap-[14rpx] text-[22rpx] leading-[34rpx] text-[#637187];
}

.record-status {
    @apply shrink-0 rounded-[8rpx] px-[10rpx] py-[2rpx] text-[20rpx] leading-[28rpx] text-[#475569];
}

.record-evidence {
    @apply flex flex-wrap gap-x-[24rpx] gap-y-[6rpx] text-[22rpx] leading-[40rpx] text-[#637187];
}

.record-value {
    @apply ml-[6rpx] font-semibold text-[#1D2B41];
    font-variant-numeric: tabular-nums;
}

.record-interaction {
    @apply mt-[6rpx] line-clamp-1 text-[22rpx] leading-[36rpx] text-[#637187];
}

.record-actions {
    @apply mt-[24rpx] flex gap-[12rpx] border-[0] border-t-[2rpx] border-solid border-[#E8EDF3];
}

.record-action {
    @apply flex min-h-[88rpx] min-w-0 flex-1 items-center justify-center gap-[8rpx] whitespace-nowrap text-[22rpx] text-[#637187];
}

.record-action-primary {
    @apply text-primary;
}

.record-pressed {
    @apply opacity-60;
}
</style>
