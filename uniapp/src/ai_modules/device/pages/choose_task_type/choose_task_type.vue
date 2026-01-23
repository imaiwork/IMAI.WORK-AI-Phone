<template>
    <view class="p-4">
        <view class="grid grid-cols-2 gap-[30rpx]">
            <view
                v-for="(item, index) in taskTypeList"
                :key="index"
                class="relative flex flex-col bg-white rounded-[32rpx] p-[32rpx] transition-all active:scale-95 shadow-[0_8rpx_20rpx_rgba(0,0,0,0.04)]"
                @click="handleClick(item)">
                <view class="flex flex-col items-start gap-y-[20rpx]">
                    <view class="w-[88rpx] h-[88rpx] rounded-[24rpx] flex items-center justify-center bg-[#f7f8fa]">
                        <image :src="item.icon" class="w-[52rpx] h-[52rpx]"></image>
                    </view>

                    <view class="flex flex-col gap-y-[8rpx]">
                        <view class="text-[32rpx] font-semibold text-[#1a1a1a] leading-tight">{{ item.title }}</view>
                        <view class="text-[24rpx] text-[#909399] leading-relaxed line-clamp-1">{{ item.desc }}</view>
                    </view>
                </view>

                <view
                    class="mt-[32rpx] pt-[24rpx] border-t border-[#f2f2f2] flex items-center justify-between"
                    @click.stop="handlePreview(item)">
                    <text class="text-[22rpx] text-[#c0c4cc]">查看演示</text>
                    <view class="w-[36rpx] h-[36rpx] rounded-full bg-[#f0f2f5] flex items-center justify-center">
                        <u-icon name="arrow-right" size="12" color="#909399"></u-icon>
                    </view>
                </view>
            </view>
        </view>
    </view>
    <video-preview v-model="showPreview" :video-url="previewUrl"></video-preview>
</template>

<script setup lang="ts">
import config from "@/config";
import { CreateTypeEnum } from "@/ai_modules/device/enums";
import TaskImgIcon from "@/ai_modules/device/static/images/common/task_type_img.png";
import TaskVideoIcon from "@/ai_modules/device/static/images/common/task_type_video.png";
import TaskClueIcon from "@/ai_modules/device/static/images/common/task_type_clue.png";
import TaskMsgIcon from "@/ai_modules/device/static/images/common/task_type_msg.png";
import TaskFriendIcon from "@/ai_modules/device/static/images/common/task_type_friend.png";
import TaskYhIcon from "@/ai_modules/device/static/images/common/task_type_yh.png";
import TaskCircleIcon from "@/ai_modules/device/static/images/common/task_type_circle.png";
import TaskPrivateIcon from "@/ai_modules/device/static/images/common/task_type_private.png";
import TaskCommentIcon from "@/ai_modules/device/static/images/common/task_type_comment.png";

const taskTypeList = [
    {
        title: "发布图文",
        desc: "自动/定时发布",
        icon: TaskImgIcon,
        disabled: false,
        type: CreateTypeEnum.IMAGE_PUBLISH,
    },
    {
        title: "发布视频",
        desc: "自动/定时发布",
        icon: TaskVideoIcon,
        disabled: false,
        type: CreateTypeEnum.VIDEO_PUBLISH,
    },
    {
        title: "自动获线索",
        desc: "无人工Ai获客",
        icon: TaskClueIcon,
        disabled: false,
        type: CreateTypeEnum.CLUE_AUTO,
    },
    {
        title: "私聊接管",
        desc: "自动处理信息",
        icon: TaskMsgIcon,
        disabled: false,
        type: CreateTypeEnum.CHAT_MANAGE,
    },
    {
        title: "评论获客",
        desc: "评论区截流获客",
        icon: TaskCommentIcon,
        disabled: false,
        type: CreateTypeEnum.COMMENT_MARKETING,
    },
    {
        title: "私信获客",
        desc: "从评论区私信获客",
        icon: TaskPrivateIcon,
        disabled: false,
        type: CreateTypeEnum.DM_MARKETING,
    },
    {
        title: "自动加好友",
        desc: "聚焦省心省力",
        icon: TaskFriendIcon,
        disabled: false,
        type: CreateTypeEnum.FRIEND_ADD,
    },
    {
        title: "自动养号",
        desc: "模拟真人养",
        icon: TaskYhIcon,
        disabled: false,
        type: CreateTypeEnum.ACCOUNT_MAINTAIN,
    },
    {
        title: "发朋友圈",
        desc: "朋友圈发布内容",
        icon: TaskCircleIcon,
        disabled: false,
        type: CreateTypeEnum.CIRCLE,
    },
    {
        title: "朋友圈互动",
        desc: "朋友圈点赞/评论",
        icon: TaskCircleIcon,
        disabled: false,
        type: CreateTypeEnum.CIRCLE_INTERACT,
    },
];

const previewUrl = ref("");
const showPreview = ref(false);

const handleClick = (item: any) => {
    if (item.disabled) {
        uni.$u.toast("敬请期待~");
        return;
    }
    const urls = {
        [CreateTypeEnum.IMAGE_PUBLISH]: "/ai_modules/device/pages/create_task/create_task?type=2",
        [CreateTypeEnum.VIDEO_PUBLISH]: "/ai_modules/device/pages/create_task/create_task?type=1",
        [CreateTypeEnum.CLUE_AUTO]: "/ai_modules/sph/pages/create_task/create_task",
        [CreateTypeEnum.CHAT_MANAGE]: "/ai_modules/device/pages/create_private_take/create_private_take",
        [CreateTypeEnum.COMMENT_MARKETING]: `/ai_modules/device/pages/create_closure/create_closure?type=${CreateTypeEnum.COMMENT_MARKETING}`,
        [CreateTypeEnum.DM_MARKETING]: `/ai_modules/device/pages/create_closure/create_closure?type=${CreateTypeEnum.PRIVATE_MESSAGE}`,
        [CreateTypeEnum.FRIEND_ADD]: "/ai_modules/device/pages/create_add_wechat/create_add_wechat",
        [CreateTypeEnum.ACCOUNT_MAINTAIN]: "/ai_modules/device/pages/create_account_building/create_account_building",
        [CreateTypeEnum.CIRCLE]: "/ai_modules/device/pages/create_circle/create_circle",
        [CreateTypeEnum.CIRCLE_INTERACT]: "/ai_modules/device/pages/create_circle_interact/create_circle_interact",
    };
    uni.navigateTo({
        url: urls[item.type as keyof typeof urls],
    });
};
const handlePreview = (item: any) => {
    const urls = {
        [CreateTypeEnum.IMAGE_PUBLISH]: `${config.baseUrl}static/videos/task_image_publish.mp4`,
        [CreateTypeEnum.VIDEO_PUBLISH]: `${config.baseUrl}static/videos/task_video_publish.mp4`,
        [CreateTypeEnum.CLUE_AUTO]: `${config.baseUrl}static/videos/task_clue_auto.mp4`,
        [CreateTypeEnum.CHAT_MANAGE]: `${config.baseUrl}static/videos/task_chat_manage.mp4`,
        [CreateTypeEnum.COMMENT_MARKETING]: `${config.baseUrl}static/videos/task_comment_marketing.mp4`,
        [CreateTypeEnum.DM_MARKETING]: `${config.baseUrl}static/videos/task_dm_marketing.mp4`,
        [CreateTypeEnum.FRIEND_ADD]: `${config.baseUrl}static/videos/task_friend_add.mp4`,
        [CreateTypeEnum.ACCOUNT_MAINTAIN]: `${config.baseUrl}static/videos/task_account_maintain.mp4`,
        [CreateTypeEnum.CIRCLE]: `${config.baseUrl}static/videos/task_publish_circle.mp4`,
        [CreateTypeEnum.CIRCLE_INTERACT]: `${config.baseUrl}static/videos/task_circle_comment.mp4`,
    };

    previewUrl.value = urls[item.type as keyof typeof urls];
    showPreview.value = true;
};
</script>

<style scoped></style>
