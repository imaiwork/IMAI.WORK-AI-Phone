<template>
    <u-popup
        :model-value="state.show"
        mode="center"
        border-radius="28"
        width="654rpx"
        :mask-close-able="false"
        :closeable="false"
    >
        <view class="p-[40rpx]">
            <view v-if="state.phase === 'running'" class="flex flex-col items-center">
                <view class="loading-ring mb-[24rpx]" />
                <text class="text-[30rpx] font-bold text-[#0D1117]">{{ title }}</text>
                <text class="text-[22rpx] text-[#9CA3AF] mt-[8rpx]"
                    >{{ state.stepLabel || '排队等待生成' }} · 已用 {{ state.elapsed }}s</text
                >
                <view class="w-full h-[12rpx] bg-[#F0F2F5] rounded-full mt-[28rpx] overflow-hidden">
                    <view
                        class="h-full rounded-full transition-all duration-500 bar-fill"
                        :style="{ width: state.percent + '%' }"
                    />
                </view>
                <text class="text-[20rpx] text-[#C0C6D0] mt-[12rpx]"
                    >整条链路约 2~5 分钟，可以留在这一页等</text
                >
                <text class="text-[20rpx] text-[#C0C6D0] mt-[4rpx]"
                    >也可后台运行，完成后到「创作记录」查看</text
                >
                <view
                    class="mt-[28rpx] h-[68rpx] px-[48rpx] rounded-full border-[2rpx] border-solid border-[#E5E8EF] flex items-center justify-center active:opacity-70"
                    @click="emit('background')"
                >
                    <text class="text-[24rpx] text-[#4B5563]">后台运行</text>
                </view>
            </view>

            <view v-else-if="state.phase === 'success'" class="flex flex-col items-center">
                <text class="text-[30rpx] font-bold text-[#0D1117] mb-[20rpx]">生成完成 🎉</text>
                <video
                    v-if="state.videoUrl"
                    :src="state.videoUrl"
                    class="w-full h-[640rpx] rounded-[20rpx] bg-black"
                    controls
                    playsinline
                />
                <text class="text-[20rpx] text-[#9CA3AF] mt-[16rpx]"
                    >共消耗 {{ state.tokensCost }} 算力 · 可在「创作记录」中回看</text
                >
                <view class="flex w-full gap-[20rpx] mt-[28rpx]">
                    <view
                        class="flex-1 h-[76rpx] rounded-full border-[2rpx] border-solid border-[#E5E8EF] flex items-center justify-center active:opacity-70"
                        @click="emit('done')"
                    >
                        <text class="text-[26rpx] text-[#4B5563]">完成</text>
                    </view>
                    <view
                        class="flex-1 h-[76rpx] rounded-full flex items-center justify-center active:opacity-80 btn-blue"
                        @click="emit('again')"
                    >
                        <text class="text-[26rpx] font-bold text-white">再生成一条</text>
                    </view>
                </view>
            </view>

            <view v-else class="flex flex-col items-center">
                <text class="text-[30rpx] font-bold text-[#0D1117] mb-[16rpx]">生成失败</text>
                <view class="w-full bg-[#FFF3F0] rounded-[16rpx] p-[20rpx]">
                    <text class="text-[22rpx] text-[#D93025] leading-relaxed break-all">{{
                        state.failReason || '上游未返回原因，请稍后重试'
                    }}</text>
                </view>
                <text v-if="state.refunded" class="text-[20rpx] text-[#16A34A] mt-[12rpx]"
                    >算力已自动退还</text
                >
                <view class="flex w-full gap-[20rpx] mt-[28rpx]">
                    <view
                        class="flex-1 h-[76rpx] rounded-full border-[2rpx] border-solid border-[#E5E8EF] flex items-center justify-center active:opacity-70"
                        @click="emit('close')"
                    >
                        <text class="text-[26rpx] text-[#4B5563]">关闭</text>
                    </view>
                    <view
                        class="flex-1 h-[76rpx] rounded-full flex items-center justify-center active:opacity-80 btn-blue"
                        @click="emit('retry')"
                    >
                        <text class="text-[26rpx] font-bold text-white">重试</text>
                    </view>
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import type { GenState } from '../../utils/play_video'

// 纯展示：状态由页面的 usePlayVideoGen 驱动，关闭一律由页面通过事件收口。
// u-popup 在 Vue3 下读的是 modelValue（内部 valueCom 有条件编译），写成 :value 弹窗永远不显示
defineProps<{ state: GenState; title: string }>()
const emit = defineEmits<{
    (e: 'background'): void
    (e: 'done'): void
    (e: 'again'): void
    (e: 'close'): void
    (e: 'retry'): void
}>()
</script>

<style lang="scss" scoped>
.loading-ring {
    width: 88rpx;
    height: 88rpx;
    border-radius: 50%;
    border: 8rpx solid #eaf1ff;
    border-top-color: #2680f7;
    animation: pv-spin 0.9s linear infinite;
}
@keyframes pv-spin {
    to {
        transform: rotate(360deg);
    }
}
.bar-fill,
.btn-blue {
    background: linear-gradient(90deg, #2680f7, #3e9bff);
}
</style>
