<template>
    <u-popup v-model="show" mode="center" border-radius="32" width="85%" @close="close">
        <view class="bg-white rounded-[32rpx] overflow-hidden">
            <view
                class="flex items-center justify-center gap-[10rpx] px-[40rpx] h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                style="background: linear-gradient(135deg, #ebf2ff 0%, #f0f9ff 100%)">
                <u-icon name="info-circle-fill" color="#0065fb" size="28" />
                <text class="text-[30rpx] font-extrabold text-[#0D1117]">{{ title }}</text>
            </view>

            <view class="px-[40rpx] py-[32rpx]">
                <view class="bg-[#F7F9FC] rounded-[20rpx] px-[24rpx] py-[20rpx]" :class="center ? 'text-center' : ''">
                    <rich-text class="text-[#4B5563] leading-relaxed" :nodes="content" />
                </view>
            </view>

            <view class="flex items-center gap-[16rpx] px-[40rpx] pb-[40rpx]">
                <view
                    v-if="showClose"
                    class="flex-1 h-[96rpx] flex items-center justify-center rounded-[24rpx] bg-[#F0F2F5] border border-solid border-[#E5E9F0]"
                    @click="close">
                    <text class="text-[28rpx] font-semibold text-[#4B5563]">{{ cancelText }}</text>
                </view>
                <view
                    v-if="showConfirm"
                    class="flex-1 h-[96rpx] flex items-center justify-center rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_24rpx_rgba(0,101,251,0.30)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="confirm">
                    <text class="text-[28rpx] font-extrabold text-white">{{ confirmText }}</text>
                </view>
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
    },
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
