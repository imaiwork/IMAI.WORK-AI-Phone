<template>
    <u-popup v-model="show" mode="center" width="90%" :border-radius="32">
        <view class="rounded-[32rpx] bg-white p-[40rpx]">
            <view class="mb-[12rpx] text-center text-[34rpx] font-extrabold text-[#0F172A]">修改算力</view>
            <text class="mb-[32rpx] block text-center text-[24rpx] leading-[38rpx] text-[#94A3B8]">
                给「{{ member?.nickname || '' }}」分配本企业算力
            </text>
            <view
                class="mb-[16rpx] flex items-center justify-between rounded-[24rpx] bg-[#F8FAFC] px-[28rpx] py-[24rpx] active:opacity-80"
                @click.stop="emit('view-detail')">
                <text class="text-[26rpx] text-[#94A3B8]">当前算力</text>
                <view class="flex items-center gap-[8rpx]">
                    <text class="text-[28rpx] font-bold text-primary">{{ currentText }}</text>
                    <u-icon name="arrow-right" size="24" color="#94A3B8" />
                </view>
            </view>
            <view class="mb-[24rpx] flex items-center justify-between rounded-[24rpx] bg-[#F8FAFC] px-[28rpx] py-[24rpx]">
                <text class="text-[26rpx] text-[#94A3B8]">可分配上限</text>
                <text class="text-[28rpx] font-bold text-[#0F172A]">{{ maxText }}</text>
            </view>
            <view class="mb-[40rpx] rounded-[24rpx] bg-[#F4F6FB] px-[28rpx] py-[16rpx]">
                <u-input
                    v-model="innerValue"
                    type="digit"
                    placeholder="请输入算力数量"
                    clearable
                    placeholder-style="color: #9CA3AF; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-[16rpx]">
                <view
                    class="flex h-[88rpx] flex-1 items-center justify-center rounded-full bg-[#F4F6FB] active:opacity-70"
                    @click="show = false">
                    <text class="text-[28rpx] font-semibold text-[#676767]">取消</text>
                </view>
                <view
                    class="flex h-[88rpx] flex-1 items-center justify-center rounded-full bg-primary active:opacity-90"
                    @click="emit('confirm', innerValue)">
                    <text class="text-[28rpx] font-bold text-white">{{ submitting ? '保存中...' : '保存' }}</text>
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean
    member: any
    value: string
    max: number
    submitting?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:value', value: string): void
    (e: 'confirm', value: string): void
    (e: 'view-detail'): void
}>()

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v)
})

const innerValue = computed({
    get: () => props.value,
    set: (v: string) => emit('update:value', v)
})

const formatNum = (n: number) => {
    if (!Number.isFinite(n)) return '0'
    if (Number.isInteger(n)) return String(n)
    return String(Math.round(n * 100) / 100)
}

const currentText = computed(() => formatNum(Number(props.member?.tokens) || 0))
const maxText = computed(() => formatNum(props.max))
</script>
