<template>
    <view>
        <config-card
            title="视频号获客"
            desc="监控视频号账号，出现以下词汇立即寻找线索"
            icon-name="scan"
            icon-color="#FF4D4F"
            icon-bg="#FFF0F0">
            <tag-list
                :items="configData.acquisitionWords"
                add-text="添加"
                @add="emit('add')"
                @edit="emit('edit', $event)"
                @remove="emit('remove', $event)" />
            <view class="mt-5 border-[0] border-t border-solid border-[#F3F4F6] pt-5">
                <view class="flex items-center justify-between mb-3">
                    <view class="flex items-center gap-[10rpx]">
                        <u-icon name="setting" color="#9CA3AF" size="28"></u-icon>
                        <text class="text-[28rpx] font-bold text-[#212121]">执行策略</text>
                    </view>
                </view>
                <view class="relative">
                    <view class="flex items-center justify-between mb-5">
                        <view class="flex items-center gap-[10rpx]">
                            <u-icon name="radio-mark" color="#9CA3AF" size="26"></u-icon>
                            <text class="text-[28rpx] font-bold text-[#212121]">每个线索词获客上限</text>
                        </view>
                        <view class="flex items-center gap-[10rpx]">
                            <view
                                class="w-[120rpx] h-[64rpx] bg-white border border-solid border-[#E8E8E8] rounded-[16rpx] flex items-center justify-center">
                                <input
                                    v-model="configData.acquisitionLimit"
                                    type="digit"
                                    class="w-full text-center text-[30rpx] font-extrabold text-[#212121]"
                                    @blur="emit('input', $event)" />
                            </view>
                            <text class="text-[#888888]">个</text>
                        </view>
                    </view>
                    <view v-if="false">
                        <text class="text-[#888888] block mb-3">当该词获客达标或耗尽时，系统将：</text>
                        <view class="flex items-center gap-[16rpx]">
                            <view
                                v-for="option in STRATEGY_LIST"
                                :key="'acq-' + option.value"
                                class="flex-1 h-[72rpx] rounded-[16rpx] flex items-center justify-center"
                                :class="
                                    configData.acquisitionStrategy === option.value
                                        ? 'bg-[#EEF4FF] border border-solid border-primary shadow-none'
                                        : 'bg-[#F5F5F5]'
                                "
                                @click="configData.acquisitionStrategy = option.value">
                                <text
                                    class="font-medium"
                                    :class="
                                        configData.acquisitionStrategy === option.value
                                            ? 'text-primary font-bold'
                                            : 'text-[#888888]'
                                    ">
                                    {{ option.label }}
                                </text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </config-card>
    </view>
</template>

<script setup lang="ts">
import { ConfigData, STRATEGY_LIST } from "./type";
import ConfigCard from "./config-card.vue";
import TagList from "./tag-list.vue";

const props = defineProps<{
    configData: ConfigData;
}>();

const emit = defineEmits<{
    (e: "add"): void;
    (e: "edit", value: any): void;
    (e: "remove", value: any): void;
    (e: "input", value: any): void;
}>();
</script>

<style scoped></style>
