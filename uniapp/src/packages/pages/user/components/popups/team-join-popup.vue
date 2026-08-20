<template>
    <popup-bottom
        :model-value="modelValue"
        height="62%"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        @update:model-value="emit('update:modelValue', $event)">
        <template #header>
            <view class="px-[40rpx]">
                <view class="flex justify-center">
                    <view class="p-1" @click="emit('update:modelValue', false)">
                        <view class="mt-4 h-[8rpx] w-[66rpx] rounded bg-black"></view>
                    </view>
                </view>
                <view class="mt-[32rpx] mb-[24rpx] flex items-center justify-between">
                    <view class="flex w-[120rpx] items-center gap-[4rpx] text-[26rpx] text-[#64748B]" @click="handleBack">
                        <u-icon name="arrow-left" size="28" color="#64748B" />
                        <text>返回</text>
                    </view>
                    <text class="text-[34rpx] font-bold text-[#0F172A]">加入团队</text>
                    <view class="w-[120rpx]" />
                </view>
            </view>
        </template>
        <template #content>
            <view class="px-[40rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                <text class="mb-[48rpx] block text-center text-[26rpx] leading-[40rpx] text-[#64748B]">
                    输入团队邀请码，或向团队管理员索取邀请二维码
                </text>

                <input
                    class="join-input"
                    type="text"
                    maxlength="32"
                    placeholder="输入团队邀请码"
                    :value="code"
                    @input="onInput" />

                <view
                    class="mt-[28rpx] rounded-[32rpx] py-[28rpx] text-center text-[30rpx] font-bold"
                    :class="canSubmit ? 'bg-primary text-white' : 'bg-[#E5EAF3] text-[#94A3B8]'"
                    @click="handleSubmit">
                    {{ submitting ? '提交中...' : '申请加入' }}
                </view>

                <view
                    class="mt-[28rpx] flex items-start gap-[16rpx] rounded-[24rpx] border-[2rpx] border-solid border-[#BFDBFE] bg-[#EFF6FF] px-[26rpx] py-[22rpx]">
                    <image :src="infoIcon" class="mt-[4rpx] h-[28rpx] w-[28rpx] flex-shrink-0" />
                    <text class="text-[24rpx] leading-[38rpx] text-[#1E40AF]">
                        加入后你将以「成员」身份进入团队，可查看团队成员，但不可进行管理操作。
                    </text>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean
    code: string
    submitting?: boolean
    infoIcon: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:code', value: string): void
    (e: 'back'): void
    (e: 'submit'): void
}>()

const canSubmit = computed(() => props.code.trim().length >= 4 && !props.submitting)

const onInput = (e: any) => {
    const raw = String(e?.detail?.value ?? '')
    emit('update:code', raw.replace(/\s/g, '').toUpperCase())
}

const handleBack = () => {
    emit('update:modelValue', false)
    emit('back')
}

const handleSubmit = () => {
    if (!canSubmit.value) return
    emit('submit')
}
</script>

<style lang="scss" scoped>
.join-input {
    width: 100%;
    height: 108rpx;
    border-radius: 28rpx;
    border: 3rpx solid #e5eaf3;
    background: #f7f9fc;
    padding: 0 36rpx;
    font-size: 30rpx;
    color: #0f172a;
    text-align: center;
    letter-spacing: 6rpx;
    box-sizing: border-box;
}
</style>
