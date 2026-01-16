<template>
    <view class="min-h-screen bg-[#F8FAFD] p-[30rpx]">
        <view class="mb-8 px-2">
            <view class="flex items-center gap-2">
                <view class="w-2 h-6 bg-primary rounded-full"></view>
                <text class="text-[44rpx] font-bold text-[#1A202C]">矩阵工作台</text>
            </view>
            <text class="text-[24rpx] text-[#A0AEC0] mt-2 block">Matrix Marketing Workspace</text>
        </view>

        <view class="grid grid-cols-2 gap-[30rpx]">
            <view
                v-for="(item, index) in richMenuList"
                :key="index"
                class="glass-card"
                hover-class="glass-card-active"
                @click="handleNav(item)">
                <view class="absolute top-0 left-0 w-full h-[6rpx]" :style="{ background: item.color }"></view>

                <view
                    class="absolute -top-10 -right-10 w-24 h-24 rounded-full opacity-10 blur-[30px]"
                    :style="{ background: item.color }"></view>

                <view class="z-10">
                    <view
                        class="w-[90rpx] h-[90rpx] rounded-[24rpx] flex items-center justify-center mb-4 transition-transform icon-box"
                        :style="{ background: item.lightColor }">
                        <u-icon :name="item.icon" size="32" :color="item.color"></u-icon>
                    </view>

                    <view>
                        <text class="text-[32rpx] font-bold text-[#2D3748]">{{ item.title }}</text>
                        <view class="flex items-center mt-1">
                            <text
                                class="text-[20rpx] uppercase tracking-wider font-medium"
                                :style="{ color: item.color }">
                                {{ item.tag }}
                            </text>
                        </view>
                    </view>
                </view>

                <view class="mt-6 flex items-center justify-between">
                    <text class="text-[22rpx] text-[#718096]">查看教程</text>
                    <view class="p-1 rounded-lg bg-gray-50">
                        <u-icon name="arrow-right" size="10" color="#CBD5E0"></u-icon>
                    </view>
                </view>
            </view>
        </view>
    </view>
    <video-preview v-model="showPreview" :video-url="previewUrl"></video-preview>
</template>

<script setup lang="ts">
import config from "@/config";
enum MenuKey {
    IMAGE_PUBLISH = "IMAGE_PUBLISH", // 发布图文
    VIDEO_PUBLISH = "VIDEO_PUBLISH", // 发布视频
    CLUE_AUTO = "CLUE_AUTO", // 自动获线索
    CHAT_MANAGE = "CHAT_MANAGE", // 私聊接管
    COMMENT_MARKETING = "COMMENT_MARKETING", // 评论获客
    DM_MARKETING = "DM_MARKETING", // 私信获客
    FRIEND_ADD = "FRIEND_ADD", // 自动加好友
    ACCOUNT_MAINTAIN = "ACCOUNT_MAINTAIN", // 自动养号
}
const richMenuList = ref([
    {
        title: "发布图文",
        icon: "photo",
        tag: "Publish",
        color: "#0065fb",
        lightColor: "#E6EFFF",
        key: MenuKey.IMAGE_PUBLISH,
    },
    {
        title: "发布视频",
        icon: "play-circle",
        tag: "Video",
        color: "#0065fb",
        lightColor: "#E6EFFF",
        key: MenuKey.VIDEO_PUBLISH,
    },
    {
        title: "自动获线索",
        icon: "search",
        tag: "AI Smart",
        color: "#7C4DFF",
        lightColor: "#F2EEFF",
        key: MenuKey.CLUE_AUTO,
    },
    {
        title: "私聊接管",
        icon: "chat",
        tag: "Message",
        color: "#00BFA5",
        lightColor: "#E6F9F6",
        key: MenuKey.CHAT_MANAGE,
    },
    {
        title: "评论获客",
        icon: "edit-pen",
        tag: "Social",
        color: "#FF6D00",
        lightColor: "#FFF2E6",
        key: MenuKey.COMMENT_MARKETING,
    },
    {
        title: "私信获客",
        icon: "email",
        tag: "Social",
        color: "#FF6D00",
        lightColor: "#FFF2E6",
        key: MenuKey.DM_MARKETING,
    },
    {
        title: "自动加好友",
        icon: "account-fill",
        tag: "Traffic",
        color: "#F50057",
        lightColor: "#FFE6EE",
        key: MenuKey.FRIEND_ADD,
    },
    {
        title: "自动养号",
        icon: "thumb-up",
        tag: "System",
        color: "#3F51B5",
        lightColor: "#ECEDF9",
        key: MenuKey.ACCOUNT_MAINTAIN,
    },
]);

const previewUrl = ref("");
const showPreview = ref(false);

const handleNav = (item: any) => {
    const urls = {
        [MenuKey.IMAGE_PUBLISH]: `${config.baseUrl}static/videos/task_image_publish.mp4`,
        [MenuKey.VIDEO_PUBLISH]: `${config.baseUrl}static/videos/task_video_publish.mp4`,
        [MenuKey.CLUE_AUTO]: `${config.baseUrl}static/videos/task_clue_auto.mp4`,
        [MenuKey.CHAT_MANAGE]: `${config.baseUrl}static/videos/task_chat_manage.mp4`,
        [MenuKey.COMMENT_MARKETING]: `${config.baseUrl}static/videos/task_comment_marketing.mp4`,
        [MenuKey.DM_MARKETING]: `${config.baseUrl}static/videos/task_dm_marketing.mp4`,
        [MenuKey.FRIEND_ADD]: `${config.baseUrl}static/videos/task_friend_add.mp4`,
        [MenuKey.ACCOUNT_MAINTAIN]: `${config.baseUrl}static/videos/task_account_maintain.mp4`,
    };

    previewUrl.value = urls[item.key as keyof typeof urls];
    showPreview.value = true;
};
</script>

<style scoped lang="scss">
.glass-card {
    @apply relative flex flex-col justify-between p-[30rpx] rounded-[36rpx] bg-white overflow-hidden;

    box-shadow: 0 10rpx 20rpx -5rpx rgba(153, 171, 198, 0.15), 0 0 0 1px rgba(255, 255, 255, 1) inset,
        0 0 0 1px rgba(226, 232, 240, 0.6);

    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.glass-card-active {
    transform: scale(0.96) translateY(2rpx);
    @apply bg-[#FDFDFF];
    box-shadow: 0 4rpx 10rpx rgba(153, 171, 198, 0.1);
}

.icon-box {
    box-shadow: 0 8rpx 16rpx -4rpx rgba(0, 0, 0, 0.05);
}

.glass-card {
    animation: fadeIn 0.6s ease-out backwards;
    @for $i from 1 through 10 {
        &:nth-child(#{$i}) {
            animation-delay: #{$i * 0.05}s;
        }
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20rpx);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
