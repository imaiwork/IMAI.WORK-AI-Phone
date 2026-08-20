<template>
    <popup-bottom
        v-model="show"
        :title="title"
        height="42%"
        border-radius="44"
        custom-class="bg-white"
        :z-index="5002"
        :mask-close-able="true"
    >
        <template #content>
            <view class="keyword-pop">
                <text class="keyword-label">{{ label }}</text>
                <view class="keyword-input-wrap">
                    <input
                        v-model="localValue"
                        :maxlength="maxlength"
                        class="keyword-input"
                        :placeholder="placeholder"
                        confirm-type="done"
                        @confirm="handleConfirm"
                    />
                    <text v-if="localValue" class="keyword-clear" @click="localValue = ''">×</text>
                </view>
                <view class="keyword-tip-row">
                    <text class="keyword-tip">{{ tip }}</text>
                    <text class="keyword-count" :class="{ 'is-close': closeLimit }">
                        {{ localValue.length }}/{{ maxlength }}
                    </text>
                </view>
                <button class="plain-btn keyword-save" @click="handleConfirm">
                    {{ confirmText }}
                </button>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        modelValue: boolean
        title?: string
        label?: string
        value?: string
        placeholder?: string
        tip?: string
        confirmText?: string
        maxlength?: number
    }>(),
    {
        title: '编辑关键词',
        label: '关键词',
        value: '',
        placeholder: '输入关键词，如：敏感肌、换季护肤',
        tip: '保存后，AI 会按这个词条执行对应配置。',
        confirmText: '保存',
        maxlength: 20
    }
)

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'confirm', value: string): void
}>()

const localValue = ref('')

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) localValue.value = props.value || ''
    },
    { immediate: true }
)

watch(
    () => props.value,
    (value) => {
        if (props.modelValue) localValue.value = value || ''
    }
)

const handleConfirm = () => {
    emit('confirm', localValue.value.trim())
}

// 接近上限（>= 90%）时计数器变橙，给用户软提醒
const closeLimit = computed(
    () => props.maxlength > 0 && localValue.value.length >= props.maxlength * 0.9
)
</script>

<style lang="scss" scoped>
.keyword-pop {
    @apply h-full flex flex-col px-[36rpx] pt-[24rpx] pb-[calc(28rpx+env(safe-area-inset-bottom))];
}

.keyword-label {
    @apply text-[24rpx] text-[#9ca3af] font-semibold mb-[16rpx];
}

.keyword-input-wrap {
    @apply min-h-[104rpx] rounded-[28rpx] bg-[#F4F7FA] border-[3rpx] border-solid border-[#E5ECF7] px-[28rpx] flex items-center;
}

.keyword-input {
    @apply flex-1 min-w-0 h-[96rpx] bg-[transparent] text-[#1d2129];
}

.keyword-clear {
    @apply w-[44rpx] h-[44rpx] rounded-full bg-[#E5EAF3] text-[#9CA3AF] text-[32rpx] leading-[40rpx] text-center shrink-0;
}

.keyword-tip-row {
    @apply flex items-end justify-between gap-[16rpx] mt-[18rpx];
}

.keyword-tip {
    @apply flex-1 min-w-0 text-[22rpx] text-[#A0A9B8] leading-[1.6];
}

.keyword-count {
    @apply shrink-0 text-[22rpx] font-semibold text-[#A0A9B8] leading-[1.6] tabular-nums;

    &.is-close {
        @apply text-[#F97316];
    }
}

.plain-btn {
    @apply m-0 p-0 leading-none border-none bg-[transparent];

    &::after {
        border: none;
    }
}

.keyword-save {
    @apply mt-auto h-[92rpx] rounded-[28rpx] bg-primary text-white text-[30rpx] font-bold flex items-center justify-center;
    box-shadow: 0 8rpx 28rpx rgba(47, 115, 246, 0.22);
}
</style>
