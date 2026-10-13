<template>
    <u-popup
        v-model="show"
        mode="center"
        border-radius="32"
        width="85%"
        :custom-style="{ backgroundColor: 'transparent' }"
        :mask-close-able="false">
        <view class="w-full bg-white rounded-[32rpx] overflow-hidden">
            <view
                class="relative flex flex-col items-center pt-[60rpx] pb-[40rpx] px-[50rpx]"
                style="background: linear-gradient(135deg, #ebf2ff 0%, #f0f9ff 100%)">
                <view
                    class="absolute top-0 right-0 w-[160rpx] h-[160rpx] rounded-full opacity-40"
                    style="
                        background: radial-gradient(circle, #bfdbfe, transparent);
                        transform: translate(40rpx, -40rpx);
                    " />
                <view
                    class="absolute bottom-0 left-0 w-[120rpx] h-[120rpx] rounded-full opacity-30"
                    style="
                        background: radial-gradient(circle, #93c5fd, transparent);
                        transform: translate(-30rpx, 30rpx);
                    " />

                <view
                    class="w-[96rpx] h-[96rpx] rounded-full flex items-center justify-center relative z-10 shadow-[0_8rpx_24rpx_rgba(0,101,251,0.25)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                    <text class="text-white text-[48rpx] font-bold">✓</text>
                </view>

                <text class="text-[34rpx] font-extrabold text-[#0D1117] mt-[24rpx] relative z-10">{{ title }}</text>
                <text class="text-[#6B7280] leading-relaxed text-center mt-[16rpx] relative z-10">{{ desc }}</text>
            </view>

            <view class="px-[40rpx] py-[36rpx] flex flex-col gap-[20rpx]">
                <view
                    v-if="seekText"
                    class="w-full h-[96rpx] flex items-center justify-center rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_24rpx_rgba(0,101,251,0.30)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="emit('seek')">
                    <text class="text-white text-[28rpx] font-extrabold relative z-10 tracking-wide">{{
                        seekText
                    }}</text>
                </view>

                <view
                    v-if="toText"
                    class="w-full h-[96rpx] flex items-center justify-center rounded-[24rpx] bg-[#F0F2F5] border border-solid border-[#E5E9F0]"
                    @click="emit('to')">
                    <text class="text-[28rpx] font-semibold text-[#4B5563]">{{ toText }}</text>
                </view>

                <slot name="custom-btn" />
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
// script 完全不变
const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        title: string;
        desc: string;
        toText?: string;
        seekText?: string;
    }>(),
    {
        modelValue: false,
        toText: "立即去发布",
        seekText: "查看创作记录",
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "to"): void;
    (e: "seek"): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit("update:modelValue", value);
    },
});
</script>
