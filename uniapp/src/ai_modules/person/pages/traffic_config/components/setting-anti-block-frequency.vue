<template>
    <view>
        <config-card title="触达时间限制" desc="" icon-name="clock" icon-color="#8B5CF6" icon-bg="#F3F0FF">
            <view class="mb-6">
                <text class="text-[28rpx] font-bold text-[#212121] block mb-3">内容发布日期</text>
                <view class="grid grid-cols-4 gap-[12rpx]">
                    <view
                        v-for="option in TIME_LIST"
                        :key="'content-' + option.value"
                        class="h-[64rpx] rounded-[16rpx] flex items-center justify-center"
                        :class="configData.contentPublishTime === option.value ? 'bg-primary' : 'bg-[#F5F5F5]'"
                        @click="configData.contentPublishTime = option.value">
                        <text
                            class="font-medium"
                            :class="configData.contentPublishTime === option.value ? 'text-white' : 'text-[#888888]'">
                            {{ option.label }}
                        </text>
                    </view>
                </view>
            </view>
            <view>
                <text class="text-[28rpx] font-bold text-[#212121] block mb-3">评论发布日期</text>
                <view class="grid grid-cols-4 gap-[12rpx]">
                    <view
                        v-for="option in TIME_LIST"
                        :key="'comment-' + option.value"
                        class="h-[64rpx] rounded-[16rpx] flex items-center justify-center"
                        :class="configData.commentPublishTime === option.value ? 'bg-primary' : 'bg-[#F5F5F5]'"
                        @click="configData.commentPublishTime = option.value">
                        <text
                            class="font-medium"
                            :class="configData.commentPublishTime === option.value ? 'text-white' : 'text-[#888888]'">
                            {{ option.label }}
                        </text>
                    </view>
                </view>
            </view>
        </config-card>

        <config-card title="防封控与频率限制" desc="" icon-name="setting-fill" icon-color="#0065FB" icon-bg="#E6F0FF">
            <view class="bg-[#E6F0FF]/60 rounded-[16rpx] p-3 mb-6">
                <text class="text-xs text-primary leading-relaxed">
                    已开启"拟人随机停顿"。每次互动后，系统将随机停留 30秒~2分钟，模拟真人浏览行为，降低风控风险。
                </text>
            </view>
            <view class="mb-6">
                <view class="flex items-center justify-between mb-4">
                    <text class="text-[28rpx] font-bold text-[#212121]">截流主动私信每天最大互动人数</text>
                    <text class="text-[32rpx] font-extrabold text-primary">{{ configData.messageNumber }}人</text>
                </view>
                <view class="mb-2">
                    <u-slider
                        v-model="configData.messageNumber"
                        min="0"
                        max="30"
                        inactive-color="#E5E7EB"
                        block-color="#0065fb"
                        block-width="36"></u-slider>
                </view>
                <view class="flex items-center justify-between">
                    <text class="text-[22rpx] text-[#b4b4b4]">保守 (防封)</text>
                    <text class="text-[22rpx] text-[#b4b4b4]">激进 (易封)</text>
                </view>
            </view>
            <view class="mb-6">
                <view class="flex items-center justify-between mb-4">
                    <text class="text-[28rpx] font-bold text-[#212121]">同城触达评论每天最大互动人数</text>
                    <text class="text-[32rpx] font-extrabold text-primary">{{ configData.cityCommentNumber }}人</text>
                </view>
                <view class="mb-2">
                    <u-slider
                        v-model="configData.cityCommentNumber"
                        min="0"
                        max="30"
                        inactive-color="#E5E7EB"
                        block-color="#0065fb"
                        block-width="36"></u-slider>
                </view>
                <view class="flex items-center justify-between">
                    <text class="text-[22rpx] text-[#b4b4b4]">保守 (防封)</text>
                    <text class="text-[22rpx] text-[#b4b4b4]">激进 (易封)</text>
                </view>
            </view>

            <view class="mb-6">
                <view class="flex items-center justify-between mb-4">
                    <text class="text-[28rpx] font-bold text-[#212121]">每日视频截流人数上限</text>
                    <text class="text-[32rpx] font-extrabold text-primary">{{ configData.videoMessageNumber }}人</text>
                </view>
                <view class="mb-2">
                    <u-slider
                        v-model="configData.videoMessageNumber"
                        min="0"
                        max="30"
                        inactive-color="#E5E7EB"
                        block-color="#0065fb"
                        block-width="36"></u-slider>
                </view>
                <view class="flex items-center justify-between">
                    <text class="text-[22rpx] text-[#b4b4b4]">保守 (防封)</text>
                    <text class="text-[22rpx] text-[#b4b4b4]">激进 (易封)</text>
                </view>
            </view>
            <view class="mb-6">
                <view class="flex items-center justify-between mb-4">
                    <text class="text-[28rpx] font-bold text-[#212121]">每日同城截流人数上限</text>
                    <text class="text-[32rpx] font-extrabold text-primary">{{ configData.cityMessageNumber }}人</text>
                </view>
                <view class="mb-2">
                    <u-slider
                        v-model="configData.cityMessageNumber"
                        min="0"
                        max="30"
                        inactive-color="#E5E7EB"
                        block-color="#0065fb"
                        block-width="36"></u-slider>
                </view>
                <view class="flex items-center justify-between">
                    <text class="text-[22rpx] text-[#b4b4b4]">保守 (防封)</text>
                    <text class="text-[22rpx] text-[#b4b4b4]">激进 (易封)</text>
                </view>
            </view>
            <view class="mb-6">
                <view class="flex items-center justify-between mb-4">
                    <text class="text-[28rpx] font-bold text-[#212121]">每日团购截流人数上限</text>
                    <text class="text-[32rpx] font-extrabold text-primary"
                        >{{ configData.grouponMessageNumber }}人</text
                    >
                </view>
                <view class="mb-2">
                    <u-slider
                        v-model="configData.grouponMessageNumber"
                        min="0"
                        max="30"
                        inactive-color="#E5E7EB"
                        block-color="#0065fb"
                        block-width="36"></u-slider>
                </view>
                <view class="flex items-center justify-between">
                    <text class="text-[22rpx] text-[#b4b4b4]">保守 (防封)</text>
                    <text class="text-[22rpx] text-[#b4b4b4]">激进 (易封)</text>
                </view>
            </view>
            <view class="mb-6">
                <view class="flex items-center justify-between mb-4">
                    <text class="text-[28rpx] font-bold text-[#212121]">私信每接管个用户回复数</text>
                    <text class="text-[32rpx] font-extrabold text-primary">
                        <text v-if="configData.replyNumber === 1">{{ configData.replyNumber }}人</text>
                        <text v-else>无限制</text>
                    </text>
                </view>
                <view class="mb-2">
                    <u-slider
                        v-model="configData.replyNumber"
                        min="1"
                        max="2"
                        inactive-color="#E5E7EB"
                        block-color="#0065fb"
                        block-width="36"></u-slider>
                </view>
                <view class="flex items-center justify-between">
                    <text class="text-[22rpx] text-[#b4b4b4]">1条</text>
                    <text class="text-[22rpx] text-[#b4b4b4]">无限制</text>
                </view>
            </view>
        </config-card>
    </view>
</template>

<script setup lang="ts">
import { TIME_LIST, ConfigData } from "./type";
import ConfigCard from "./config-card.vue";

const props = defineProps<{
    configData: ConfigData;
}>();
</script>

<style scoped></style>
