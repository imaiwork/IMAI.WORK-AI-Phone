<template>
    <view>
        <view class="flex items-center justify-between mb-[8rpx]">
            <text class="text-[20rpx] text-[#9ca3af] font-medium">{{ label }}</text>
            <view v-if="!editing" class="text-[20rpx] text-[#60a5fa] px-[12rpx] py-[4rpx]" @click="handleEdit"
                >✎ 编辑</view
            >
        </view>
        <text
            v-if="!editing"
            class="block leading-[40rpx]"
            :class="multiline ? 'text-xs text-[#4b5563]' : 'text-sm text-[#1f2937] font-bold'">
            {{ modelValue }}
        </text>
        <view v-else class="mt-[12rpx]">
            <textarea
                v-if="multiline"
                class="w-full box-border h-[340rpx] border-[2rpx] border-[#bfdbfe] rounded-[24rpx] bg-[rgba(230,239,255,0.4)] px-[24rpx] py-[16rpx] text-[26rpx] text-[#1f2937] leading-[40rpx]"
                :value="draft"
                :maxlength="-1"
                @input="handleInput" />
            <input
                v-else
                class="w-full box-border h-[106rpx] border-[2rpx] border-[#bfdbfe] rounded-[24rpx] bg-[rgba(230,239,255,0.4)] px-[24rpx] py-[16rpx] text-[26rpx] text-[#1f2937]"
                :value="draft"
                :maxlength="-1"
                @input="handleInput" />
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
    label: string;
    modelValue: string;
    multiline?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    multiline: false,
});

const emit = defineEmits<{
    (event: "update:modelValue", value: string): void;
    (event: "save", value: string): void;
}>();

const editing = ref(false);
const draft = ref(props.modelValue);

watch(
    () => props.modelValue,
    (value) => {
        draft.value = value;
    },
);

const handleInput = (event: any) => {
    draft.value = event.detail.value;
};

const handleEdit = () => {
    draft.value = props.modelValue;
    editing.value = true;
};

const handleCancel = () => {
    draft.value = props.modelValue;
    editing.value = false;
};

const handleSave = () => {
    emit("update:modelValue", draft.value);
    emit("save", draft.value);
    editing.value = false;
};
</script>
