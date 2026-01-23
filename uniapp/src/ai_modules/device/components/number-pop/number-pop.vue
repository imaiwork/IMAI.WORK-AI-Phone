<template>
    <u-popup v-model="show" mode="center" width="90%" :border-radius="20" @close="close">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-bold text-center mt-2">{{ title }}</view>
            <view class="mt-[48rpx] bg-[#F3F3F3] px-4 py-2 rounded-[16rpx]">
                <u-input
                    v-model="number"
                    :placeholder="placeholder"
                    type="digit"
                    placeholder-style="color: #0000004d; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold text-[#000000b3]"
                    @click="close">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-bold text-white"
                    @click="confirm"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        number: number;
        title: string;
        placeholder: string;
        confirmText: string;
        max?: number;
    }>(),
    {
        max: 9999999999,
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "close"): void;
    (e: "confirm", value: number): void;
}>();

const number = ref(props.number);

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value: boolean) {
        emit("update:modelValue", value);
    },
});

const close = () => {
    show.value = false;
    emit("close");
};

const confirm = () => {
    if (number.value > (props.max ?? 0)) {
        uni.$u.toast("最大值不能超过" + props.max);
        return;
    }
    emit("confirm", number.value);
    close();
};
</script>

<style scoped></style>
