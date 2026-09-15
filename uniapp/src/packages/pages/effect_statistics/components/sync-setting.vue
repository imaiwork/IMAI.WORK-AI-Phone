<template>
    <view class="pb-[24rpx] mb-[24rpx] border-[0] border-b-[2rpx] border-solid border-[#DCE5F5]">
        <view class="flex items-center justify-between gap-[24rpx] min-h-[72rpx]">
            <view class="flex-1 min-w-0">
                <text class="block text-[28rpx] font-semibold text-main">定时同步作品数据</text>
                <text class="block text-[23rpx] text-[#5B6B8C] mt-[8rpx]">
                    {{
                        saving
                            ? '正在保存…'
                            : enabled
                            ? '已开启 · 每小时同步一次'
                            : '已关闭 · 不自动同步、不扣费'
                    }}
                </text>
            </view>
            <u-switch
                :model-value="enabled"
                :loading="saving"
                :disabled="saving"
                :size="44"
                active-color="var(--color-primary, #0065FB)"
                inactive-color="#CBD5E1"
                @change="emit('change', $event)"
            />
        </view>
        <text class="block text-[23rpx] text-[#5B6B8C] leading-relaxed mt-[16rpx]">
            对当前人设的抖音、小红书绑定账号生效。{{
                costText ? `每个账号每次同步消耗 ${costText}。` : '当前同步不消耗算力。'
            }}关闭后仍可手动刷新{{ costText ? '，按次扣费' : '' }}。
        </text>
    </view>
</template>

<script setup lang="ts">
defineProps<{
    enabled: boolean
    saving: boolean
    costText: string
}>()

const emit = defineEmits<{
    (event: 'change', enabled: boolean): void
}>()
</script>
