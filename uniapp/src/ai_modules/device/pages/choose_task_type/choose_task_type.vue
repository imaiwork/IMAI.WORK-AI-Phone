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
import { CreateTypeEnum } from "@/ai_modules/device/enums";
import TaskImgIcon from "@/ai_modules/device/static/images/common/task_type_img.png";
import TaskVideoIcon from "@/ai_modules/device/static/images/common/task_type_video.png";
import TaskClueIcon from "@/ai_modules/device/static/images/common/task_type_clue.png";
import TaskMsgIcon from "@/ai_modules/device/static/images/common/task_type_msg.png";
import TaskFriendIcon from "@/ai_modules/device/static/images/common/task_type_friend.png";
import TaskYhIcon from "@/ai_modules/device/static/images/common/task_type_yh.png";
import TaskCircleIcon from "@/ai_modules/device/static/images/common/task_type_circle.png";
import TaskXhsNoIcon from "@/ai_modules/device/static/images/common/task_type_xhsno.png";
import TaskPrivateIcon from "@/ai_modules/device/static/images/common/task_type_private.png";
import TaskCommentIcon from "@/ai_modules/device/static/images/common/task_type_comment.png";

// 创建类型

const taskTypeList = [
    {
        title: "发布图文",
        desc: "自动/定时发布",
        icon: TaskImgIcon,
        disabled: false,
        type: CreateTypeEnum.IMG,
    },
    {
        title: "发布视频",
        desc: "自动/定时发布",
        icon: TaskVideoIcon,
        disabled: false,
        type: CreateTypeEnum.VIDEO,
    },
    {
        title: "自动获线索",
        desc: "无人工Ai获客",
        icon: TaskClueIcon,
        disabled: false,
        type: CreateTypeEnum.CLUE,
    },
    {
        title: "私聊接管",
        desc: "自动处理信息",
        icon: TaskMsgIcon,
        disabled: false,
        type: CreateTypeEnum.MSG,
    },
    {
        title: "评论获客",
        desc: "评论区截流获客",
        icon: TaskCommentIcon,
        disabled: false,
        type: CreateTypeEnum.COMMENT,
    },
    {
        title: "私信获客",
        desc: "从评论区私信获客",
        icon: TaskPrivateIcon,
        disabled: false,
        type: CreateTypeEnum.PRIVATE_MESSAGE,
    },
    {
        title: "自动加好友",
        desc: "聚焦省心省力",
        icon: TaskFriendIcon,
        disabled: false,
        type: CreateTypeEnum.FRIEND,
    },
    {
        title: "自动养号",
        desc: "模拟真人养",
        icon: TaskYhIcon,
        disabled: false,
        type: CreateTypeEnum.YH,
    },
    {
        title: "发朋友圈",
        desc: "即将解锁",
        icon: TaskCircleIcon,
        disabled: true,
    },
    {
        title: "小红书起号",
        desc: "即将解锁",
        icon: TaskXhsNoIcon,
        disabled: true,
    },
];

const handleClick = (item: any) => {
    if (item.disabled) {
        uni.$u.toast("敬请期待~");
        return;
    }
    const urls = {
        [CreateTypeEnum.IMG]: "/ai_modules/device/pages/create_task/create_task?type=2",
        [CreateTypeEnum.VIDEO]: "/ai_modules/device/pages/create_task/create_task?type=1",
        [CreateTypeEnum.CLUE]: "/ai_modules/sph/pages/create_task/create_task",
        [CreateTypeEnum.MSG]: "/ai_modules/device/pages/create_private_take/create_private_take",
        [CreateTypeEnum.COMMENT]: `/ai_modules/device/pages/create_closure/create_closure?type=${CreateTypeEnum.COMMENT}`,
        [CreateTypeEnum.PRIVATE_MESSAGE]: `/ai_modules/device/pages/create_closure/create_closure?type=${CreateTypeEnum.PRIVATE_MESSAGE}`,
        [CreateTypeEnum.FRIEND]: "/ai_modules/device/pages/create_add_wechat/create_add_wechat",
        [CreateTypeEnum.YH]: "/ai_modules/device/pages/create_account_building/create_account_building",
    };

    uni.navigateTo({
        url: urls[item.type as CreateTypeEnum],
    });
};
</script>

<style scoped></style>
