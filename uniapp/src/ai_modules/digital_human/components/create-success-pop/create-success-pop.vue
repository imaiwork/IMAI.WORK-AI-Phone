<template>
    <u-popup
        v-model="show"
        mode="center"
        border-radius="24"
        width="85%"
        :custom-style="{ backgroundColor: 'transparent' }"
        :mask-close-able="false">
        <view class="w-full bg-white rounded-[24rpx] pt-[80rpx] px-[50rpx] pb-[50rpx] relative overflow-hidden">
            <view
                class="absolute top-0 right-0 w-[120rpx] h-[120rpx] rounded-full opacity-60"
                style="
                    background: linear-gradient(135deg, #dcfce7 0%, #f0fdf4 100%);
                    transform: translate(30rpx, -30rpx);
                "></view>

            <view class="flex justify-center mb-[30rpx]">
                <view
                    class="w-[80rpx] h-[80rpx] rounded-full flex items-center justify-center shadow-lg"
                    style="background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%)">
                    <text class="text-white text-[40rpx]">✓</text>
                </view>
            </view>

            <view class="text-[36rpx] font-medium text-center mb-[20rpx] text-[#1f2937]">
                {{ title }}
            </view>

            <view class="text-[28rpx] text-center leading-relaxed mb-[60rpx] text-[#6b7280]">
                {{ desc }}
            </view>

            <view class="space-y-[24rpx]">
                <view
                    v-if="seekText"
                    class="btn-dark w-full text-white text-[30rpx] font-semibold rounded-[20rpx] h-[96rpx] flex items-center justify-center relative overflow-hidden"
                    style="
                        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #000000 100%);
                        box-shadow: 0 8rpx 15rpx rgba(0, 0, 0, 0.3), 0 0 0 1rpx rgba(255, 255, 255, 0.1);
                    "
                    @click="emit('seek')">
                    <view class="btn-inner-glow"></view>
                    <view class="btn-sweep"></view>
                    <view class="btn-border-glow"></view>
                    <text class="relative z-10 tracking-wide">{{ seekText }}</text>
                </view>

                <view
                    v-if="toText"
                    class="btn-secondary w-full text-[30rpx] font-medium rounded-[20rpx] h-[96rpx] flex items-center justify-center relative overflow-hidden border border-solid text-[#475569] border-[#e2e8f0]"
                    style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)"
                    @click="emit('to')">
                    <view class="btn-ripple"></view>
                    <text class="relative z-10">{{ toText }}</text>
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
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
    }
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

<style scoped>
.btn-dark {
    position: relative;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateY(0);
    border: 1rpx solid rgba(255, 255, 255, 0.1);
}

.btn-dark:active {
    transform: translateY(3rpx) scale(0.97);
    box-shadow: 0 4rpx 15rpx rgba(0, 0, 0, 0.4), 0 0 0 1rpx rgba(255, 255, 255, 0.2),
        inset 0 2rpx 4rpx rgba(255, 255, 255, 0.1) !important;
}

.btn-dark:hover {
    transform: translateY(-2rpx);
    box-shadow: 0 12rpx 35rpx rgba(0, 0, 0, 0.4), 0 0 0 1rpx rgba(255, 255, 255, 0.2),
        0 0 20rpx rgba(255, 255, 255, 0.1) !important;
}

.btn-inner-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 50%;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
    border-radius: 20rpx 20rpx 0 0;
}

.btn-sweep {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transform: skewX(-20deg);
    transition: left 0.8s ease;
}

.btn-dark:hover .btn-sweep {
    left: 100%;
}

.btn-border-glow {
    position: absolute;
    top: -1rpx;
    left: -1rpx;
    right: -1rpx;
    bottom: -1rpx;
    background: linear-gradient(45deg, #ffffff, #cccccc, #ffffff, #cccccc);
    border-radius: 20rpx;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}
</style>
