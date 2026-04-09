<template>
    <view
        class="w-full bg-white rounded-[24rpx] p-[24rpx] shadow-sm mb-[20rpx] border border-solid border-[#e5e5e5] relative overflow-hidden"
        @click="handleClick">
        <view class="flex justify-between items-start">
            <view class="flex items-center gap-x-2 flex-1 mr-2">
                <view class="font-medium text-[30rpx] text-[#333] line-clamp-1">
                    {{ item.username || "未知用户" }}
                </view>
                <view
                    class="px-[12rpx] py-[4rpx] bg-[#F0F5FF] rounded-[6rpx] text-[20rpx] text-primary font-medium shrink-0">
                    #{{ item.exec_keyword }}
                </view>
            </view>
            <view
                class="px-[16rpx] py-[6rpx] rounded-[8rpx] text-[22rpx] font-medium shrink-0"
                :class="getStatusClass(item.status)">
                {{ getStatusText(item.status) }}
            </view>
        </view>

        <view
            class="mt-[24rpx] bg-[#F9FAFB] rounded-[16rpx] p-[20rpx] flex justify-between items-center border border-solid border-[#F0F0F0]"
            @click.stop="handleClue(item)">
            <view class="flex items-center gap-x-3 flex-1 overflow-hidden">
                <view
                    class="w-[64rpx] h-[64rpx] bg-white rounded-full flex items-center justify-center shadow-sm shrink-0">
                    <image
                        v-if="item.clue_type == 2"
                        src="/static/images/icons/phone.svg"
                        class="w-[32rpx] h-[32rpx]" />
                    <image
                        v-else-if="item.clue_type == 1"
                        src="/static/images/icons/weixin.svg"
                        class="w-[36rpx] h-[36rpx]" />
                    <u-icon v-else name="file-text" size="32" color="#999"></u-icon>
                </view>

                <view class="flex flex-col flex-1 overflow-hidden">
                    <text class="text-[22rpx] text-[#999] mb-[2rpx]">
                        {{ item.clue_type == 2 ? "手机号码" : "微信号码" }}
                    </text>
                    <text class="text-[30rpx] font-medium text-[#333] font-DIN truncate">
                        {{ item.reg_content }}
                    </text>
                </view>
            </view>

            <view
                class="w-[60rpx] h-[60rpx] flex items-center justify-center bg-white rounded-full border border-[#e5e5e5] active:bg-[#e5e5e5] shadow-sm ml-2">
                <u-icon
                    :name="item.clue_type == 1 ? 'cut' : 'phone-fill'"
                    size="32"
                    :color="item.clue_type == 1 ? '#2979ff' : '#19be6b'"></u-icon>
            </view>
        </view>

        <view class="mt-[24rpx] space-y-[12rpx]">
            <view class="flex items-start gap-x-2">
                <u-icon name="map" size="24" color="#999" class="mt-[4rpx]"></u-icon>
                <text class="text-[24rpx] text-[#666] leading-[1.4] line-clamp-2">
                    {{ item.address || "暂无地址信息" }}
                </text>
            </view>

            <view class="h-[1rpx] bg-[#F5F5F5] w-full my-[16rpx]"></view>

            <view class="flex justify-between items-center text-[22rpx] text-[#999]">
                <view class="flex items-center gap-x-1 max-w-[60%]">
                    <u-icon name="account" size="22" color="#999"></u-icon>
                    <text class="truncate">{{ item.exec_account_name }} ({{ item.exec_account }})</text>
                </view>
                <view class="flex items-center gap-x-1">
                    <u-icon name="clock" size="22" color="#999"></u-icon>
                    <text>{{ formatTimeShort(item.exec_time) }}</text>
                </view>
            </view>
        </view>

        <view
            v-if="item.image"
            class="absolute top-0 right-0 w-[60rpx] h-[60rpx] bg-[#0065fb]/10 rounded-bl-[24rpx] flex items-center justify-center">
            <u-icon name="photo" size="28" color="#2979ff"></u-icon>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useCopy } from "@/hooks/useCopy";

const props = defineProps({
    item: {
        type: Object,
        default: () => ({}),
    },
});

const { copy } = useCopy();

// 状态样式逻辑
const getStatusClass = (status: number) => {
    if ([1, 3].includes(status)) {
        return "bg-[#E1FFF6] text-[#00B578]"; // 绿色系，文字加深一点增强对比度
    } else if (status == 2) {
        return "bg-[#FFF1F0] text-[#FF4D4F]"; // 红色系
    }
    return "bg-gray-100 text-gray-500";
};

// 状态文案逻辑
const getStatusText = (status: number) => {
    if (status == 1) return "线索有效";
    if (status == 2) return "线索无效";
    if (status == 3) return "内含有效";
    return "待处理";
};

// 简单的日期格式化，去掉秒或者年份，视情况而定
const formatTimeShort = (timeStr: string) => {
    if (!timeStr) return "";
    // 假设时间格式是 "YYYY-MM-DD HH:mm:ss"，只取 "MM-DD HH:mm"
    return timeStr.slice(5, 16);
};

const handleClick = () => {
    if (props.item.image) {
        uni.previewImage({
            urls: [props.item.image],
        });
    } else {
        // 如果没有图片，可以提示或者不做操作
        // uni.showToast({ title: '暂无截图', icon: 'none' });
    }
};

const handleClue = (item: any) => {
    if (item.clue_type == 1) {
        copy(item.reg_content);
    } else {
        uni.makePhoneCall({
            phoneNumber: item.reg_content,
        });
    }
};
</script>
