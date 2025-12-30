<template>
    <view class="w-full h-full bg-white rounded-[40rpx] px-[32rpx] pb-3" @click="handleClick">
        <view
            class="flex justify-between items-center h-[100rpx] border-solid border-[0] border-b-[1rpx] border-[#0000000d] gap-x-2">
            <view class="flex-1 flex items-center gap-x-[24rpx]">
                <view>
                    <view class="line-clamp-1 font-bold">{{ item.username }}</view>
                    <view
                        class="rounded-[12rpx] text-[20rpx] px-1 py-[4rpx] mt-[4rpx] w-fit"
                        :class="
                            [1, 3].includes(item.status) ? 'bg-[#E1FFF6] text-[#25C5AA]' : 'bg-[#FFF1F0] text-[#FF4D4F]'
                        "
                        >{{ item.status == 1 ? "线索有效" : item.status == 2 ? "线索无效" : "内含有效线索" }}</view
                    >
                </view>
                <view
                    class="px-[28rpx] py-[10rpx] rounded text-[22rpx] bg-[#E1FFF6] text-[#25C5AA] font-bold whitespace-nowrap"
                    >#{{ item.exec_keyword }}</view
                >
            </view>
            <view class="flex items-center gap-x-2 break-all">
                <image v-if="item.clue_type == 2" src="/static/images/icons/phone.svg" class="w-4 h-6" />
                <image v-else-if="item.clue_type == 1" src="/static/images/icons/weixin.svg" class="w-4 h-6" />
                <text @click.stop="handleClue(item)" class="flex-1 line-clamp-1">
                    {{ item.reg_content }}
                </text>
            </view>
        </view>
        <view class="mt-[34rpx]">
            <view class="text-[#00000080]">地址: {{ item.address || "-" }}</view>
            <view class="flex items-center justify-between gap-x-[24rpx] mt-[12rpx]">
                <view class="text-[#00000080]">执行账号: {{ item.exec_account_name }}({{ item.exec_account }})</view>
                <view class="text-[#00000080]"> {{ item.exec_time }} </view>
            </view>
        </view>
        <view class="mt-2 flex justify-end text-primary text-[22rpx]">点击查看客资详情</view>
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

const handleClick = () => {
    if (props.item.image) {
        uni.previewImage({
            urls: [props.item.image],
        });
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

<style scoped></style>
