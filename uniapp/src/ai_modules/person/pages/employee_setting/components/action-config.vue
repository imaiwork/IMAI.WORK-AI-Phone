<template>
    <view>
        <view class="ac-row" :class="{ 'single-action': !showLike && !showFollow }">
            <view v-if="showLike" class="ac-card" :class="{ active: like }" @click="$emit('toggle-like')">
                <view v-if="like" class="ac-chk">
                    <u-icon name="checkmark" color="#ffffff" size="16"></u-icon>
                </view>
                <view class="ac-icon">
                    <image :src="like ? HeartBlueIcon : HeartGrayIcon" mode="aspectFit" class="w-[30rpx] h-[30rpx]" />
                </view>
                <text class="ac-lbl">点赞</text>
            </view>
            <view v-if="showFollow" class="ac-card" :class="{ active: follow }" @click="$emit('toggle-follow')">
                <view v-if="follow" class="ac-chk">
                    <u-icon name="checkmark" color="#ffffff" size="16"></u-icon>
                </view>
                <view class="ac-icon">
                    <image :src="follow ? StarBlueIcon : StarGrayIcon" mode="aspectFit" class="w-[30rpx] h-[30rpx]" />
                </view>
                <text class="ac-lbl">关注</text>
            </view>
            <view class="dual-card" :class="{ active: dual }">
                <text class="dual-badge">二选一</text>
                <view class="dual-inner">
                    <view
                        class="dual-item"
                        :class="{ active: dual === 'comment' }"
                        @click="$emit('dual', dual === 'comment' ? '' : 'comment')">
                        <view v-if="dual === 'comment'" class="dual-chk">
                            <u-icon name="checkmark" color="#ffffff" size="14"></u-icon>
                        </view>
                        <view class="ac-icon small">
                            <image
                                :src="dual === 'comment' ? MessageBlueIcon : MessageGrayIcon"
                                mode="aspectFit"
                                class="w-[28rpx] h-[28rpx]" />
                        </view>
                        <text class="ac-lbl">评论</text>
                    </view>
                    <view class="dual-div"></view>
                    <view
                        class="dual-item"
                        :class="{ active: dual === 'dm' }"
                        @click="$emit('dual', dual === 'dm' ? '' : 'dm')">
                        <view v-if="dual === 'dm'" class="dual-chk">
                            <u-icon name="checkmark" color="#ffffff" size="14"></u-icon>
                        </view>
                        <view class="ac-icon small">
                            <image
                                :src="dual === 'dm' ? SendBlueIcon : SendGrayIcon"
                                mode="aspectFit"
                                class="w-[28rpx] h-[28rpx]" />
                        </view>
                        <text class="ac-lbl">私信</text>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import HeartBlueIcon from "@/ai_modules/person/static/icons/employee/heart-blue.svg";
import HeartGrayIcon from "@/ai_modules/person/static/icons/employee/heart-gray.svg";
import MessageBlueIcon from "@/ai_modules/person/static/icons/employee/message-blue.svg";
import MessageGrayIcon from "@/ai_modules/person/static/icons/employee/message-gray.svg";
import SendBlueIcon from "@/ai_modules/person/static/icons/employee/send-blue.svg";
import SendGrayIcon from "@/ai_modules/person/static/icons/employee/send-gray.svg";
import StarBlueIcon from "@/ai_modules/person/static/icons/employee/star-blue.svg";
import StarGrayIcon from "@/ai_modules/person/static/icons/employee/star-gray.svg";

withDefaults(
    defineProps<{
        like: boolean;
        follow: boolean;
        dual: "" | "comment" | "dm";
        showLike?: boolean;
        showFollow?: boolean;
    }>(),
    {
        showLike: true,
        showFollow: true,
    },
);

defineEmits<{
    (event: "toggle-like"): void;
    (event: "toggle-follow"): void;
    (event: "dual", value: "" | "comment" | "dm"): void;
}>();
</script>

<style lang="scss" scoped>
.ac-row {
    @apply grid grid-cols-3 gap-[16rpx] mb-[24rpx];

    &.single-action {
        @apply grid-cols-1;
    }
}

.ac-card {
    @apply min-h-[206rpx] border-[4rpx] border-solid border-[#e5e7eb] rounded-[30rpx] py-[24rpx] px-[12rpx] text-center relative bg-white flex flex-col items-center justify-center;

    &.active {
        @apply border-[#2f73f6] bg-[#ebf2ff];

        .ac-lbl {
            @apply text-[#2f73f6];
        }
    }
}

.ac-icon {
    @apply w-[76rpx] h-[76rpx] rounded-full bg-[#f3f7fc] mb-[16rpx] flex items-center justify-center;

    &.small {
        @apply w-[66rpx] h-[66rpx] mb-[10rpx];
    }
}

.ac-lbl {
    @apply text-[24rpx] font-semibold text-[#9ca3af];
}

.ac-chk,
.dual-chk {
    @apply absolute top-[16rpx] right-[16rpx] w-[40rpx] h-[40rpx] rounded-full bg-[#2f73f6] flex items-center justify-center;
}

.dual-card {
    @apply min-h-[206rpx] border-[4rpx] border-solid border-[#e5e7eb] rounded-[30rpx] bg-white overflow-hidden flex flex-col;

    &.active {
        @apply border-[#2f73f6] bg-[#ebf2ff];

        .dual-badge {
            @apply bg-[#2f73f6] text-white;
        }

        .dual-div {
            @apply bg-[#c7dcff];
        }
    }
}

.dual-badge {
    @apply h-[48rpx] bg-[#f3f4f6] text-[#9ca3af] text-[22rpx] font-bold text-center flex items-center justify-center;
}

.dual-inner {
    @apply flex flex-1;
}

.dual-item {
    @apply flex-1 pt-[18rpx] px-[4rpx] pb-[22rpx] text-center relative flex flex-col items-center justify-center;

    &.active {
        .ac-icon {
            @apply bg-[#d6e8ff];
        }

        .ac-lbl {
            @apply text-[#2f73f6];
        }
    }
}

.dual-chk {
    @apply top-[8rpx] right-[8rpx] w-[32rpx] h-[32rpx];
}

.dual-div {
    @apply w-[2rpx] bg-[#e5e7eb] my-[16rpx];
}
</style>
