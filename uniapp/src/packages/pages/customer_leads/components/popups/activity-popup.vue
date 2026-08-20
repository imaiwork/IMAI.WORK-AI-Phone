<template>
    <popup-bottom
        v-model="show"
        height="82%"
        custom-class="bg-white"
        :clearable="false"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <popup-header
                :customer="customer"
                :title="activityType === 'chat' ? '私信聊天记录' : '朋友圈互动记录'"
                :subtitle="activityType === 'chat' ? '平台与私域沟通明细' : '点赞、评论'"
                @close="emit('update:modelValue', false)" />
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="px-[32rpx] py-[28rpx] pb-[64rpx]">
                    <view v-if="loading" class="py-[120rpx] flex flex-col items-center">
                        <u-loading mode="circle" size="40" color="#2845F5"></u-loading>
                        <text class="text-xs text-[#9CA3AF] mt-[18rpx]">加载记录中...</text>
                    </view>

                    <!-- 私信聊天 -->
                    <template v-else-if="activityType === 'chat'">
                        <view
                            v-if="customer?.chatLog.length || customer?.chatScreenshots.length"
                            class="flex flex-col gap-[20rpx]">
                            <view
                                v-for="(item, index) in customer.chatLog"
                                :key="index"
                                class="flex gap-[18rpx]"
                                :class="item.from === 'agent' ? 'flex-row-reverse' : ''">
                                <view
                                    class="w-[60rpx] h-[60rpx] rounded-full flex items-center justify-center shrink-0 overflow-hidden"
                                    :class="item.from === 'agent' ? 'bg-[#2845F5]' : customer.avatarClass">
                                    <image
                                        v-if="item.from !== 'agent' && customer.avatar"
                                        :src="customer.avatar"
                                        class="w-full h-full"
                                        mode="aspectFill"></image>
                                    <text v-else class="text-[18rpx] font-bold text-white">
                                        {{ item.from === "agent" ? "AI" : customer.name.slice(0, 1) }}
                                    </text>
                                </view>
                                <view class="max-w-[72%]">
                                    <view
                                        class="px-[24rpx] py-[18rpx] rounded-[24rpx]"
                                        :class="
                                            item.from === 'agent'
                                                ? 'bg-[#2845F5] rounded-br-[8rpx]'
                                                : 'bg-[#F4F6FA] rounded-bl-[8rpx]'
                                        ">
                                        <text
                                            class="text-xs leading-relaxed break-all"
                                            :class="item.from === 'agent' ? 'text-white' : 'text-[#374151]'">
                                            {{ item.text }}
                                        </text>
                                    </view>
                                    <text class="text-[20rpx] text-[#9CA3AF] mt-[6rpx] block text-center">
                                        {{ item.time }}
                                    </text>
                                </view>
                            </view>

                            <view
                                v-if="customer.chatScreenshots.length"
                                class="grid grid-cols-3 gap-[14rpx] pt-[12rpx]">
                                <image
                                    v-for="item in customer.chatScreenshots"
                                    :key="item"
                                    :src="item"
                                    class="w-full h-[180rpx] rounded-[18rpx] bg-[#F4F6FA]"
                                    mode="aspectFill"></image>
                            </view>
                        </view>
                        <empty v-else text="暂无聊天记录" />
                    </template>

                    <!-- 朋友圈互动 -->
                    <template v-else>
                        <view v-if="customer?.momentsLog.length" class="flex flex-col gap-[18rpx]">
                            <view
                                v-for="(item, index) in customer.momentsLog"
                                :key="index"
                                class="bg-[#F9FAFB] rounded-[24rpx] p-[24rpx] border border-solid border-[#F0F0F0]">
                                <text class="text-[20rpx] text-[#9CA3AF] mb-[10rpx] block">
                                    {{ item.time }}
                                </text>
                                <text class="text-xs text-[#374151] leading-relaxed block">
                                    {{ item.content }}
                                </text>
                                <image
                                    v-if="item.image"
                                    :src="item.image"
                                    class="w-full h-[320rpx] rounded-[20rpx] bg-[#F4F6FA] mt-[18rpx]"
                                    mode="aspectFill"></image>
                                <view class="flex flex-wrap gap-[10rpx] mt-[18rpx]">
                                    <text
                                        v-if="item.liked"
                                        class="text-[20rpx] font-semibold text-[#2845F5] bg-[#EEF2FF] rounded-full px-[16rpx] py-[4rpx]">
                                        已点赞
                                    </text>
                                    <text
                                        v-if="item.comment"
                                        class="text-[20rpx] font-semibold text-[#16A34A] bg-[#DCFCE7] rounded-full px-[16rpx] py-[4rpx]">
                                        评论：{{ item.comment }}
                                    </text>
                                </view>
                            </view>
                        </view>
                        <empty v-else text="暂无朋友圈互动" />
                    </template>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import PopupHeader from "../popup-header.vue";
import type { ActivityType, Customer } from "../types";

const props = defineProps<{
    modelValue: boolean;
    customer: Customer | null;
    activityType: ActivityType;
    loading: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});
</script>
