<template>
    <view class="p-4">
        <view class="grid grid-cols-2 gap-[22rpx]">
            <view
                v-for="(item, index) in taskTypeList"
                :key="index"
                class="flex flex-col gap-y-[4rpx]"
                @click="handleClick(item)">
                <view class="mx-auto w-[70%] h-[6rpx] bg-white rounded-full"></view>
                <view class="mx-auto w-[80%] h-[6rpx] bg-white rounded-full"></view>
                <view class="bg-white rounded-[20rpx] p-5 flex items-center justify-between gap-x-2">
                    <view>
                        <view class="text-[30rpx] font-bold">{{ item.title }}</view>
                        <view class="text-[22rpx] text-[#0000004d] font-bold mt-1">{{ item.desc }}</view>
                    </view>
                    <image :src="item.icon" class="w-[48rpx] h-[48rpx]"></image>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import taskImgIcon from "@/ai_modules/device/static/images/common/task_type_img.png";
import taskVideoIcon from "@/ai_modules/device/static/images/common/task_type_video.png";
import taskClueIcon from "@/ai_modules/device/static/images/common/task_type_clue.png";
import taskMsgIcon from "@/ai_modules/device/static/images/common/task_type_msg.png";
import taskFriendIcon from "@/ai_modules/device/static/images/common/task_type_friend.png";
import taskYhIcon from "@/ai_modules/device/static/images/common/task_type_yh.png";
import taskCircleIcon from "@/ai_modules/device/static/images/common/task_type_circle.png";
import taskXhsNoIcon from "@/ai_modules/device/static/images/common/task_type_xhsno.png";

const taskTypeList = [
    {
        title: "发布图文",
        desc: "自动/定时发布",
        icon: taskImgIcon,
        disabled: false,
        type: "img",
    },
    {
        title: "发布视频",
        desc: "自动/定时发布",
        icon: taskVideoIcon,
        disabled: false,
        type: "video",
    },
    {
        title: "自动获线索",
        desc: "无人工Ai获客",
        icon: taskClueIcon,
        disabled: false,
        type: "clue",
    },
    {
        title: "私聊接管",
        desc: "自动处理信息",
        icon: taskMsgIcon,
        disabled: false,
        type: "msg",
    },
    {
        title: "自动加好友",
        desc: "聚焦省心省力",
        icon: taskFriendIcon,
        disabled: false,
        type: "friend",
    },
    {
        title: "自动养号",
        desc: "模拟真人养",
        icon: taskYhIcon,
        disabled: false,
        type: "yh",
    },
    {
        title: "发朋友圈",
        desc: "即将解锁",
        icon: taskCircleIcon,
        disabled: true,
    },
    {
        title: "小红书起号",
        desc: "即将解锁",
        icon: taskXhsNoIcon,
        disabled: true,
    },
];

const handleClick = (item: any) => {
    if (item.disabled) {
        uni.$u.toast("敬请期待~");
        return;
    }

    switch (item.type) {
        case "img":
            uni.navigateTo({
                url: `/ai_modules/device/pages/create_task/create_task?type=2`,
            });
            break;
        case "video":
            uni.navigateTo({
                url: `/ai_modules/device/pages/create_task/create_task?type=1`,
            });
            break;
        case "clue":
            uni.navigateTo({
                url: `/ai_modules/sph/pages/create_task/create_task`,
            });
            break;
        case "msg":
            uni.navigateTo({
                url: `/ai_modules/device/pages/create_private_take/create_private_take`,
            });
            break;
        case "friend":
            uni.navigateTo({
                url: `/ai_modules/device/pages/create_add_wechat/create_add_wechat`,
            });
            break;
        case "yh":
            uni.navigateTo({
                url: `/ai_modules/device/pages/create_account_building/create_account_building`,
            });
            break;
    }
};
</script>

<style scoped></style>
