<template>
    <popup-bottom
        :model-value="modelValue"
        title="创建团队"
        height="48%"
        custom-class="bg-white"
        :mask-close-able="true"
        @update:model-value="emit('update:modelValue', $event)">
        <template #content>
            <view class="px-[40rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                <text class="mb-[40rpx] block text-center text-[26rpx] leading-[40rpx] text-[#64748B]">
                    成为团队主，管理成员与企业算力
                </text>

                <input
                    class="create-input"
                    type="text"
                    maxlength="30"
                    placeholder="给你的团队起个名字"
                    :value="name"
                    @input="onInput" />

                <view
                    class="mt-[28rpx] rounded-[32rpx] py-[28rpx] text-center text-[30rpx] font-bold"
                    :class="canSubmit ? 'bg-primary text-white' : 'bg-[#E5EAF3] text-[#94A3B8]'"
                    @click="handleSubmit">
                    {{ submitting ? '创建中...' : '立即开通' }}
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean
    name: string
    submitting?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:name', value: string): void
    (e: 'submit'): void
}>()

const canSubmit = computed(() => props.name.trim().length >= 1 && !props.submitting)

const onInput = (e: any) => {
    emit('update:name', String(e?.detail?.value ?? ''))
}

const handleSubmit = () => {
    if (!canSubmit.value) return
    emit('submit')
}
</script>

<style lang="scss" scoped>
.create-input {
    width: 100%;
    height: 108rpx;
    border-radius: 28rpx;
    border: 3rpx solid #e5eaf3;
    background: #f7f9fc;
    padding: 0 36rpx;
    font-size: 30rpx;
    color: #0f172a;
    text-align: center;
    box-sizing: border-box;
}
</style>
