<template>
    <popup-bottom v-model="show" title="目标地区" height="40%" @close="close">
        <template #content>
            <view class="h-full flex flex-col px-[60rpx] py-[40rpx]">
                <view class="grow min-h-0">
                    <view class="p-2 bg-[#F3F3F3] rounded-[10rpx]">
                        <u-input
                            v-model="value"
                            placeholder="请输入目标地区，如：深圳"
                            placeholder-style="font-size: 26rpx;"
                            maxlength="30"
                            clearable />
                    </view>
                    <view class="mt-2 font-bold text-[#000000]/50">
                        填写地区后，每个搜索词前会添加「XX地区 + 线索词」前缀
                    </view>
                </view>
                <view
                    class="w-full h-[90rpx] flex items-center justify-center text-white font-bold bg-black rounded-[20rpx]"
                    @click="handleConfirm">
                    确定
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean;
    region: string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", show: boolean): void;
    (e: "confirm", region: string): void;
    (e: "close"): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const value = ref(props.region);

const handleConfirm = () => {
    emit("confirm", value.value);
};

const close = () => {
    show.value = false;
    emit("close");
};
</script>

<style scoped></style>
