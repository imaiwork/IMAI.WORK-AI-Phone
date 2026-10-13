<template>
    <view>
        <view class="flex items-center justify-between mb-[8rpx]">
            <text class="text-[20rpx] text-[#9ca3af] font-medium">话题标签</text>
            <view
                v-if="!editing"
                class="text-[20rpx] text-[#60a5fa] px-[12rpx] py-[4rpx]"
                @click="handleEdit"
                >✎ 编辑</view
            >
        </view>
        <view v-if="!editing" class="flex flex-wrap gap-[12rpx]">
            <text
                v-for="tag in modelValue"
                :key="tag"
                class="text-primary bg-primary-light-9 rounded-[12rpx] px-[16rpx] py-[4rpx] text-xs"
                >#{{ tag }}</text
            >
        </view>
        <view v-else class="mt-[12rpx]">
            <input
                class="w-full box-border min-h-[76rpx] border-[2rpx] border-[#bfdbfe] rounded-[24rpx] bg-[rgba(230,239,255,0.4)] px-[24rpx] py-[16rpx] text-[26rpx] text-[#1f2937]"
                placeholder="用空格分隔多个话题标签"
                placeholder-class="text-[#9ca3af]"
                :value="draft"
                @input="handleInput"
            />
            <text class="block mt-[10rpx] text-[20rpx] text-[#9ca3af]">多个话题用空格隔开</text>
            <view class="flex gap-[16rpx] mt-[16rpx]">
                <view
                    class="flex-1 flex items-center justify-center rounded-[24rpx] px-[24rpx] py-[20rpx] text-xs font-semibold text-[#4b5563] bg-[#f9fafb] border-[2rpx] border-[#f3f4f6]"
                    @click="handleCancel"
                    >取消</view
                >
                <view
                    class="flex-1 flex items-center justify-center rounded-[24rpx] px-[24rpx] py-[20rpx] text-xs font-semibold text-primary bg-primary-light-9"
                    @click="handleSave"
                    >保存</view
                >
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
interface Props {
    modelValue: string[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
    (event: 'update:modelValue', value: string[]): void
    (event: 'save', value: string[]): void
}>()

const editing = ref(false)
const draft = ref(props.modelValue.join(' '))

watch(
    () => props.modelValue,
    (value) => {
        draft.value = value.join(' ')
    }
)

const handleInput = (event: any) => {
    draft.value = event.detail.value
}

const handleEdit = () => {
    draft.value = props.modelValue.join(' ')
    editing.value = true
}

const handleCancel = () => {
    draft.value = props.modelValue.join(' ')
    editing.value = false
}

const handleSave = () => {
    const tags = draft.value
        .split(/[\s,，]+/)
        .map((item) => item.trim().replace(/^#/, ''))
        .filter(Boolean)

    emit('update:modelValue', tags)
    emit('save', tags)
    editing.value = false
}
</script>
