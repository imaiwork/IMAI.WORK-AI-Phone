<template>
    <popup-bottom v-model="show" title="目标地区" height="40%" @close="close">
        <template #content>
            <view class="h-full flex flex-col px-4 pt-[24rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))]">
                <view class="grow min-h-0 flex flex-col gap-[16rpx]">
                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] h-[80rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[28rpx] bg-primary rounded-full" />
                            <text class="font-extrabold text-[#0D1117]">输入目标地区</text>
                        </view>
                        <view
                            class="px-[28rpx] py-[20rpx] flex items-center bg-[#F7F9FC] mx-[20rpx] my-[16rpx] rounded-[16rpx] border border-solid border-[#E5E9F0]">
                            <u-input
                                class="flex-1"
                                v-model="value"
                                placeholder="请输入目标地区，如：深圳"
                                placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                maxlength="30"
                                clearable />
                        </view>
                    </view>

                    <view class="flex items-start gap-[10rpx] px-[4rpx]">
                        <u-icon name="info-circle" color="#9CA3AF" size="24" class="flex-shrink-0 mt-[2rpx]" />
                        <text class="text-xs text-[#9CA3AF] leading-relaxed flex-1">
                            填写地区后，每个搜索词前会添加「XX地区 + 线索词」前缀
                        </text>
                    </view>
                </view>

                <view
                    class="h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden transition-all duration-200 mt-[24rpx]"
                    :class="value ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                    :style="
                        value ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)' : 'background: #C0C4CC'
                    "
                    @click="handleConfirm">
                    <text class="text-[30rpx] font-extrabold text-white tracking-wide">确定</text>
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
