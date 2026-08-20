<template>
    <popup-bottom
        :model-value="modelValue"
        height="auto"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        @update:model-value="emit('update:modelValue', $event)">
        <template #header>
            <view class="px-[40rpx]">
                <view class="flex justify-center">
                    <view class="p-1" @click="emit('update:modelValue', false)">
                        <view class="mt-4 h-[8rpx] w-[72rpx] rounded bg-[#E5EAF3]" />
                    </view>
                </view>
                <view class="mt-[24rpx] mb-[8rpx] flex items-start justify-between gap-[20rpx] pb-[8rpx]">
                    <view class="flex min-w-0 items-center gap-[20rpx]">
                        <view
                            class="flex h-[88rpx] w-[88rpx] flex-shrink-0 items-center justify-center overflow-hidden rounded-[24rpx] bg-[#F1F5F9] text-[32rpx] font-bold text-[#64748B]">
                            <image
                                v-if="row?.avatar"
                                :src="row.avatar"
                                class="h-full w-full"
                                mode="aspectFill" />
                            <text v-else>{{ avatarText }}</text>
                        </view>
                        <view class="min-w-0">
                            <text class="block text-[34rpx] font-bold text-[#0F172A]">消耗详情</text>
                            <text class="mt-[6rpx] block text-[24rpx] text-[#94A3B8]">
                                {{ row?.create_time || '—' }}
                            </text>
                        </view>
                    </view>
                    <view
                        class="flex h-[56rpx] w-[56rpx] flex-shrink-0 items-center justify-center rounded-full bg-[#F1F5F9]"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" size="28" color="#64748B" />
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="px-[40rpx] pb-[calc(32rpx+env(safe-area-inset-bottom))]">
                <view
                    class="mb-[24rpx] flex items-center justify-between rounded-[28rpx] px-[28rpx] py-[28rpx]"
                    style="background: linear-gradient(135deg, #f8fafc, #eef2f7)">
                    <view class="flex min-w-0 items-center gap-[20rpx]">
                        <view
                            class="flex h-[80rpx] w-[80rpx] flex-shrink-0 items-center justify-center overflow-hidden rounded-[20rpx] bg-[#F1F5F9] text-[28rpx] font-bold text-[#64748B]">
                            <image
                                v-if="row?.avatar"
                                :src="row.avatar"
                                class="h-full w-full"
                                mode="aspectFill" />
                            <text v-else>{{ avatarText }}</text>
                        </view>
                        <view class="min-w-0">
                            <text class="block truncate text-[28rpx] font-semibold text-[#0F172A]">
                                {{ row?.user_name || '成员' }}
                            </text>
                            <text class="mt-[4rpx] block truncate text-[22rpx] text-[#94A3B8]">
                                {{ row?.biz_name || '消耗' }}
                                <text v-if="row?.type_desc">（{{ row.type_desc }}）</text>
                            </text>
                        </view>
                    </view>
                    <view class="flex-shrink-0 pl-[16rpx] text-right">
                        <text class="mb-[4rpx] block text-[22rpx] text-[#94A3B8]">
                            {{ Number(row?.action) === 1 ? "退回算力" : "消耗算力" }}
                        </text>
                        <text
                            class="block text-[40rpx] font-bold leading-none"
                            :class="Number(row?.action) === 1 ? 'text-[#16A34A]' : 'text-[#F59E0B]'">
                            {{ Number(row?.action) === 1 ? "+" : "-" }}{{ formatNum(row?.change_amount) }}
                        </text>
                    </view>
                </view>

                <view
                    v-if="row?.remark"
                    class="flex items-start gap-[16rpx] rounded-[24rpx] bg-[#F8FAFC] px-[28rpx] py-[24rpx]">
                    <text class="flex-shrink-0 text-[26rpx] text-[#94A3B8]">备注</text>
                    <text class="min-w-0 flex-1 text-[26rpx] leading-[1.6] text-[#475569]">{{ row.remark }}</text>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean
    row: any | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()

const avatarText = computed(() => String(props.row?.user_name || '?').slice(0, 1))

const formatNum = (n: any) => {
    const v = Number(n)
    if (!Number.isFinite(v)) return '0'
    if (Number.isInteger(v)) return String(v)
    return String(Math.round(v * 100) / 100)
}
</script>
