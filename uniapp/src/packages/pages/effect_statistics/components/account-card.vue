<template>
    <view class="flex items-center gap-[24rpx] bg-[#F7F8FB] rounded-[20rpx] p-[24rpx]">
        <template v-if="loading || !account">
            <skeleton-block w="80rpx" h="80rpx" radius="50%" />
            <view class="flex-1">
                <skeleton-block w="45%" h="26rpx" />
                <skeleton-block class="mt-[14rpx]" w="65%" h="22rpx" />
            </view>
        </template>

        <template v-else>
            <view class="relative shrink-0">
                <image
                    v-if="account.avatar"
                    :src="account.avatar"
                    mode="aspectFill"
                    class="w-[80rpx] h-[80rpx] rounded-full"
                />
                <view
                    v-else
                    class="w-[80rpx] h-[80rpx] rounded-full bg-[#EDEFF4] flex items-center justify-center"
                >
                    <u-icon name="account" color="#9AA3B2" :size="32"></u-icon>
                </view>
                <image
                    :src="platform.icon"
                    mode="aspectFit"
                    class="w-[34rpx] h-[34rpx] absolute -right-[6rpx] -bottom-[6rpx]"
                />
            </view>

            <view class="flex-1 min-w-0">
                <text class="block text-[27rpx] font-bold text-main line-clamp-1">
                    {{ account.nickname || '—' }}
                </text>
                <text class="block text-[22rpx] text-[#9AA3B2] mt-[4rpx] line-clamp-1">
                    {{ platform.acctLabel }} {{ account.uniqueId }}
                </text>
            </view>

            <view class="flex gap-[28rpx] shrink-0">
                <view v-for="item in stats" :key="item.label" class="text-center">
                    <text class="block text-[27rpx] font-bold text-main">{{ item.value }}</text>
                    <text class="block text-[20rpx] text-[#9AA3B2]">{{ item.label }}</text>
                </view>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { formatCount } from '../utils'
import SkeletonBlock from './skeleton-block.vue'
import type { AnalyticsAccount, PlatformConfig } from '../enums'

const props = defineProps<{
    account: AnalyticsAccount | null
    platform: PlatformConfig
    loading: boolean
}>()

const stats = computed(() => [
    { label: '粉丝', value: formatCount(props.account?.follower) },
    { label: props.platform.workLabel, value: formatCount(props.account?.totalWorks) },
    { label: '获赞', value: formatCount(props.account?.totalLiked) }
])
</script>
