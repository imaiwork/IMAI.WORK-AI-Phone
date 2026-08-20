<template>
    <view>
        <config-card
            title="视频截流设置"
            desc="搜索同行视频，并监控其评论区寻找潜在客户"
            icon-name="share"
            icon-color="#FF8C00"
            icon-bg="#FFF5F0">
            <view>
                <view class="flex items-center gap-[10rpx] mb-[16rpx]">
                    <view
                        class="w-[40rpx] h-[40rpx] rounded-full bg-[#FF8C00]/10 flex items-center justify-center flex-shrink-0">
                        <text class="text-[22rpx] font-extrabold text-[#FF8C00]">1</text>
                    </view>
                    <view>
                        <text class="text-[28rpx] font-bold text-[#212121]">视频搜索词</text>
                        <text class="text-[22rpx] text-[#999999] ml-[10rpx]">用于在社媒平台搜索相关的同行视频</text>
                    </view>
                </view>
                <tag-list
                    :items="configData.interceptionSearchWords"
                    add-text="添加"
                    @add="emit('add', KeyWordsType.InterceptionSearchWords)"
                    @edit="emit('edit', KeyWordsType.InterceptionSearchWords, $event)"
                    @remove="emit('remove', KeyWordsType.InterceptionSearchWords, $event)" />
            </view>

            <view class="mt-5 border-[0] border-t border-solid border-[#F3F4F6] pt-5">
                <view class="flex items-center gap-[10rpx] mb-[16rpx]">
                    <view
                        class="w-[40rpx] h-[40rpx] rounded-full bg-[#FF8C00]/10 flex items-center justify-center flex-shrink-0">
                        <text class="text-[22rpx] font-extrabold text-[#FF8C00]">2</text>
                    </view>
                    <view>
                        <text class="text-[28rpx] font-bold text-[#212121]">评论匹配词</text>
                        <text class="text-[22rpx] text-[#999999] ml-[10rpx]"
                            >在上述视频的评论区中，筛选出包含以下意向词的客户</text
                        >
                    </view>
                </view>
                <tag-list
                    :items="configData.interceptionMatchWords"
                    add-text="添加"
                    @add="emit('add', KeyWordsType.InterceptionMatchWords)"
                    @edit="emit('edit', KeyWordsType.InterceptionMatchWords, $event)"
                    @remove="emit('remove', KeyWordsType.InterceptionMatchWords, $event)" />
            </view>

            <view class="mt-5 border-[0] border-t border-solid border-[#F3F4F6] pt-5">
                <view class="flex items-center justify-between mb-3">
                    <view class="flex items-center gap-[10rpx]">
                        <u-icon name="setting" color="#9CA3AF" size="28"></u-icon>
                        <text class="text-[28rpx] font-bold text-[#212121]">执行策略</text>
                    </view>
                </view>
                <view class="relative">
                    <view class="flex items-center justify-between">
                        <view class="flex items-center gap-[10rpx]">
                            <u-icon name="radio-mark" color="#9CA3AF" size="26"></u-icon>
                            <text class="text-[28rpx] font-bold text-[#212121]">每个匹配词截流上限</text>
                        </view>
                        <view class="flex items-center gap-[10rpx]">
                            <view
                                class="w-[120rpx] h-[64rpx] bg-white border border-solid border-[#E8E8E8] rounded-[16rpx] flex items-center justify-center">
                                <input
                                    v-model="configData.interceptionLimit"
                                    type="digit"
                                    class="w-full text-center text-[30rpx] font-extrabold text-[#212121]"
                                    @blur="emit('input', $event)" />
                            </view>
                            <text class="text-[#888888]">个</text>
                        </view>
                    </view>

                    <view class="flex items-center gap-[8rpx] mt-[10rpx] mb-5">
                        <u-icon name="info-circle" color="#BBBBBB" size="22"> </u-icon>
                        <text class="text-[22rpx] text-[#BBBBBB]"> 输入 0 表示不限制截流数量 </text>
                    </view>

                    <view v-if="false">
                        <text class="text-[#888888] block mb-3">当该词截流达标或耗尽时，系统将：</text>
                        <view class="flex items-center gap-[16rpx]">
                            <view
                                v-for="option in STRATEGY_LIST"
                                :key="'int-' + option.value"
                                class="flex-1 h-[72rpx] rounded-[16rpx] flex items-center justify-center"
                                :class="
                                    configData.interceptionStrategy === option.value
                                        ? 'bg-[#EEF4FF] border border-solid border-primary shadow-none'
                                        : 'bg-[#F5F5F5]'
                                "
                                @click="configData.interceptionStrategy = option.value">
                                <text
                                    class="font-medium"
                                    :class="
                                        configData.interceptionStrategy === option.value
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
import { ConfigData, STRATEGY_LIST, KeyWordsType } from "./type";
import TagList from "./tag-list.vue";
import ConfigCard from "./config-card.vue";

const props = defineProps<{
    configData: ConfigData;
}>();

const emit = defineEmits<{
    (e: "add", type: KeyWordsType): void;
    (e: "edit", type: KeyWordsType, index: number): void;
    (e: "remove", type: KeyWordsType, index: number): void;
    (e: "input", value: any): void;
}>();
</script>

<style scoped></style>
