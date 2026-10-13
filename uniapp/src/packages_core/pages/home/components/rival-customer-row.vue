<template>
    <view class="border-b-[2rpx] border-[#f9fafb] last:border-b-0">
        <view class="px-[28rpx] py-[24rpx] flex items-center gap-[20rpx]" @click="$emit('toggle', customer.id)">
            <image
                :src="customer.avatar"
                mode="aspectFill"
                class="w-[80rpx] h-[80rpx] rounded-full bg-[#f3f4f6] flex-shrink-0" />
            <view class="flex-1 min-w-0">
                <view class="flex items-center gap-[12rpx]">
                    <text class="text-sm font-bold text-[#1f2937] line-clamp-1">{{ customer.nickname }}</text>
                    <text
                        class="rounded-[8rpx] px-[12rpx] py-[2rpx] text-[20rpx] font-semibold flex-shrink-0"
                        :class="badge.cls">
                        {{ badge.label }}
                    </text>
                </view>
                <view class="mt-[6rpx] flex items-center gap-[8rpx] text-[22rpx] text-[#9ca3af]">
                    <text>{{ accountLabel }}：</text>
                    <text>{{ customer.douyin_id }}</text>
                </view>
            </view>
            <view class="flex items-center gap-[12rpx] flex-shrink-0">
                <view
                    v-if="customer.screenshot_url"
                    class="w-[56rpx] h-[56rpx] rounded-full bg-primary-light-9 flex items-center justify-center"
                    @click.stop="$emit('preview', customer.screenshot_url)">
                    <image :src="imageIcon" mode="aspectFit" class="w-[28rpx] h-[28rpx]" />
                </view>
                <u-icon :name="expanded ? 'arrow-up' : 'arrow-down'" size="24" color="#9ca3af"></u-icon>
            </view>
        </view>
        <view v-if="expanded" class="px-[28rpx] pb-[28rpx]">
            <view class="bg-[#f9fafb] rounded-[24rpx] p-[24rpx] flex flex-col gap-[20rpx]">
                <view class="flex items-center justify-between gap-[16rpx]">
                    <view class="min-w-0 flex-1">
                        <text class="block text-[20rpx] text-[#9ca3af] mb-[4rpx]">{{ nicknameLabel }}</text>
                        <text class="block text-xs font-medium text-[#374151] line-clamp-1">
                            {{ customer.nickname }}
                        </text>
                    </view>
                    <view
                        class="flex items-center gap-[8rpx] text-[20rpx] text-primary bg-white border-[2rpx] border-[#dbeafe] px-[16rpx] py-[8rpx] rounded-[12rpx] flex-shrink-0"
                        @click.stop="$emit('copy', customer.nickname)">
                        <image :src="copyIcon" mode="aspectFit" class="w-[22rpx] h-[22rpx]" />
                        <text>复制</text>
                    </view>
                </view>
                <view class="pt-[16rpx] border-t-[2rpx] border-[#f3f4f6] flex items-center justify-between gap-[16rpx]">
                    <view class="min-w-0 flex-1">
                        <text class="block text-[20rpx] text-[#9ca3af] mb-[4rpx]">{{ accountLabel }}</text>
                        <text class="block text-xs font-medium text-[#374151] line-clamp-1">
                            {{ customer.douyin_id }}
                        </text>
                    </view>
                    <view
                        class="flex items-center gap-[8rpx] text-[20rpx] text-primary bg-white border-[2rpx] border-[#dbeafe] px-[16rpx] py-[8rpx] rounded-[12rpx] flex-shrink-0"
                        @click.stop="$emit('copy', customer.douyin_id)">
                        <image :src="copyIcon" mode="aspectFit" class="w-[22rpx] h-[22rpx]" />
                        <text>复制</text>
                    </view>
                </view>
                <view class="pt-[16rpx] border-t-[2rpx] border-[#f3f4f6] flex items-center gap-[12rpx]">
                    <image :src="tagIcon" mode="aspectFit" class="w-[24rpx] h-[24rpx] flex-shrink-0" />
                    <text class="text-[20rpx] text-[#9ca3af]">命中关键词：</text>
                    <text class="text-[22rpx] text-warning bg-warning-light-9 rounded-[8rpx] px-[12rpx] py-[2rpx]">
                        {{ customer.matched_keyword }}
                    </text>
                </view>
                <view v-if="customer.content_text" class="pt-[16rpx] border-t-[2rpx] border-[#f3f4f6]">
                    <text class="block text-[20rpx] text-[#9ca3af] mb-[8rpx]">
                        {{ contentLabel }}
                    </text>
                    <text
                        class="block text-xs text-[#4b5563] bg-white rounded-[16rpx] px-[20rpx] py-[16rpx] leading-[40rpx]">
                        {{ customer.content_text }}
                    </text>
                </view>
                <view class="pt-[16rpx] border-t-[2rpx] border-[#f3f4f6] flex items-center gap-[8rpx]">
                    <image :src="clockIcon" mode="aspectFit" class="w-[22rpx] h-[22rpx] flex-shrink-0" />
                    <text class="text-[22rpx] text-[#9ca3af]">{{ customer.time }}</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import config from "@/config";

type RivalCustomerAction = "private" | "comment" | "like" | "flyer";
interface RivalCustomer {
    id: string;
    avatar: string;
    nickname: string;
    action: RivalCustomerAction;
    douyin_id: string;
    screenshot_url?: string;
    matched_keyword: string;
    content_label?: string;
    content_text?: string;
    time: string;
    platform_name?: string;
}

const props = defineProps<{
    customer: RivalCustomer;
    expanded: boolean;
    badge: { label: string; cls: string };
}>();

defineEmits<{
    (e: "toggle", id: string): void;
    (e: "copy", text: string): void;
    (e: "preview", url: string): void;
}>();

const clockIcon = config.baseUrl + "static/images/mp/workflow_clock.svg";
const tagIcon = config.baseUrl + "static/images/mp/workflow_tag.svg";
const imageIcon = config.baseUrl + "static/images/mp/workflow_image.svg";
const copyIcon = config.baseUrl + "static/images/mp/workflow_copy.svg";

const ACTION_TO_CONTENT_LABEL: Record<RivalCustomerAction, string> = {
    private: "私信内容",
    comment: "评论内容",
    like: "互动备注",
    flyer: "传单内容",
};
const contentLabel = computed(
    () => props.customer.content_label || ACTION_TO_CONTENT_LABEL[props.customer.action] || "内容",
);

// 账号/昵称标签按平台动态展示：抖音→抖音号/抖音昵称，小红书→小红书号/小红书昵称…
const accountLabel = computed(() => (props.customer.platform_name ? `${props.customer.platform_name}号` : "账号"));
const nicknameLabel = computed(() => (props.customer.platform_name ? `${props.customer.platform_name}昵称` : "昵称"));
</script>
