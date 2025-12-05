<template>
    <u-popup v-model="show" mode="center" border-radius="20" width="80%">
        <view class="rounded-[20rpx] bg-white p-5">
            <view class="text-[30rpx] font-bold text-center">{{ title }}</view>
            <view class="text-xs text-[#00000080] mt-[32rpx]" :class="[center ? 'text-center' : '']">
                <rich-text :nodes="content" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    v-if="showClose"
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold"
                    @click="close">
                    {{ cancelText }}
                </view>
                <view
                    v-if="showConfirm"
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-primary font-bold text-white"
                    @click="confirm"
                    >{{ confirmText }}</view
                >
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        title?: string;
        content?: string;
        confirmText?: string;
        cancelText?: string;
        center?: boolean;
        showClose?: boolean;
        showConfirm?: boolean;
    }>(),
    {
        modelValue: false,
        title: "提示",
        content: "",
        confirmText: "确定",
        cancelText: "取消",
        center: false,
        showClose: true,
        showConfirm: true,
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "close"): void;
    (e: "confirm"): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const close = () => {
    show.value = false;
    emit("close");
};

const confirm = () => {
    show.value = false;
    emit("confirm");
};
</script>

<style scoped></style>
