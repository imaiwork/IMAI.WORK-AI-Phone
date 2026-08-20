<template>
    <view class="h-screen bg-[#F4F6FB] flex flex-col">
        <view class="flex flex-1 overflow-hidden">
            <!-- 左侧竖向 Tab -->
            <view class="flex flex-col w-[140rpx] flex-shrink-0 bg-white pt-[16rpx] pb-[40rpx] shadow-sm">
                <view
                    v-for="(tab, index) in tabs"
                    :key="index"
                    class="relative flex flex-col items-center justify-center py-[28rpx] px-[10rpx]"
                    @click="activeTab = index">
                    <!-- 左侧激活指示条 -->
                    <view
                        v-if="activeTab === index"
                        class="absolute left-0 top-1/2 w-[6rpx] h-[48rpx] rounded-r-full bg-primary"
                        :style="{ background: tab.themeColor, transform: 'translateY(-50%)' }" />

                    <view class="z-10 flex flex-col items-center gap-[8rpx]">
                        <view
                            class="w-[64rpx] h-[64rpx] rounded-[18rpx] flex items-center justify-center transition-all"
                            :style="{
                                background: activeTab === index ? tab.lightColor : '#F4F6FB',
                            }">
                            <u-icon
                                :name="tab.icon"
                                size="24"
                                :color="activeTab === index ? tab.themeColor : '#A0AEC0'" />
                        </view>
                        <text
                            class="text-[20rpx] font-medium text-center leading-tight"
                            :style="activeTab === index ? { color: tab.themeColor } : { color: '#A0AEC0' }">
                            {{ tab.label }}
                        </text>
                    </view>
                </view>
            </view>

            <scroll-view scroll-y class="flex-1 pt-[16rpx] pb-[40rpx]">
                <view class="flex flex-col px-[24rpx] gap-[16rpx]">
                    <view class="flex items-center gap-[10rpx] py-[12rpx]">
                        <view
                            class="w-[6rpx] h-[28rpx] rounded-full"
                            :style="{ background: tabs[activeTab].themeColor }" />
                        <text class="text-[26rpx] font-bold text-[#212121]">
                            {{ tabs[activeTab].subtitle }}
                        </text>
                    </view>

                    <view
                        v-for="(item, index) in currentMenuList"
                        :key="index"
                        class="bg-white rounded-[28rpx] px-[32rpx] pt-[32rpx] pb-[24rpx] shadow-sm border border-solid border-[#E5E7EB] active:opacity-80"
                        @click="handleNav(item)">
                        <view class="flex items-start justify-between mb-[24rpx]">
                            <view class="flex flex-col gap-[8rpx] flex-1 mr-[20rpx]">
                                <text class="text-[32rpx] font-bold text-[#212121]">{{ item.title }}</text>
                                <view class="flex items-center gap-[6rpx]">
                                    <view class="w-[8rpx] h-[8rpx] rounded-full" :style="{ background: item.color }" />
                                    <text class="text-[22rpx] font-medium" :style="{ color: item.color }">
                                        {{ item.desc }}
                                    </text>
                                </view>
                            </view>
                            <view
                                class="w-[80rpx] h-[80rpx] rounded-[22rpx] flex items-center justify-center flex-shrink-0"
                                :style="{ background: item.lightColor }">
                                <u-icon :name="item.icon" size="30" :color="item.color" />
                            </view>
                        </view>

                        <view class="flex items-center justify-between">
                            <view class="flex gap-[8rpx] flex-wrap flex-1">
                                <view
                                    v-for="(p, pi) in item.platforms"
                                    :key="pi"
                                    class="px-[16rpx] py-[6rpx] rounded-full bg-[#F4F6FB]">
                                    <text class="text-[20rpx] text-[#676767] font-medium">{{ p }}</text>
                                </view>
                            </view>
                            <view
                                class="w-[48rpx] h-[48rpx] rounded-full bg-[#F4F6FB] flex items-center justify-center flex-shrink-0">
                                <u-icon name="arrow-right" size="16" color="#CBD5E0" />
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
    </view>

    <video-preview v-model="showPreview" :video-url="previewUrl" />
</template>

<script setup lang="ts">
import config from "@/config";
import { CreateTypeEnum } from "@/ai_modules/device/enums";

const activeTab = ref(0);

const tabs = [
    {
        label: "内容分发",
        icon: "share",
        subtitle: "一键发布社媒平台",
        themeColor: "#0065fb",
        lightColor: "#EBF2FF",
    },
    {
        label: "AI获客",
        icon: "search",
        subtitle: "全域智能自动获客",
        themeColor: "#7C4DFF",
        lightColor: "#F2EEFF",
    },
    {
        label: "私域运营",
        icon: "grid",
        subtitle: "智能管理私域流量",
        themeColor: "#F50057",
        lightColor: "#FFE6EE",
    },
    {
        label: "客服接管",
        icon: "chat",
        subtitle: "7x24h自动回复信息",
        themeColor: "#FF6D00",
        lightColor: "#FFF2E6",
    },
    {
        label: "同城曝光",
        icon: "map",
        subtitle: "同城曝光达人",
        themeColor: "#00BFA5",
        lightColor: "#E6F9F6",
    },
];

const menuMap: Record<number, any[]> = {
    // ── 内容分发 ──
    0: [
        {
            title: "发布图文",
            icon: "photo",
            desc: "自动/定时发布",
            color: "#0065fb",
            lightColor: "#EBF2FF",
            platforms: ["小红书", "视频号", "抖音", "快手"],
            key: CreateTypeEnum.IMAGE_PUBLISH,
            navUrl: "/ai_modules/device/pages/create_task/create_task?type=2",
        },
        {
            title: "发布视频",
            icon: "play-circle",
            desc: "自动/定时发布",
            color: "#7C4DFF",
            lightColor: "#F2EEFF",
            platforms: ["小红书", "视频号", "抖音", "快手"],
            key: CreateTypeEnum.VIDEO_PUBLISH,
            navUrl: "/ai_modules/device/pages/create_task/create_task?type=1",
        },
        {
            title: "发布朋友圈",
            icon: "moments",
            desc: "朋友圈发布内容",
            color: "#00BFA5",
            lightColor: "#E6F9F6",
            platforms: ["朋友圈"],
            key: CreateTypeEnum.CIRCLE,
            navUrl: "/ai_modules/device/pages/create_circle/create_circle",
        },
    ],
    // ── AI获客 ──
    1: [
        {
            title: "自动获线索",
            icon: "account-fill",
            desc: "无人工Ai获客",
            color: "#00BFA5",
            lightColor: "#E6F9F6",
            platforms: ["视频号"],
            key: CreateTypeEnum.CLUE_AUTO,
            navUrl: "/ai_modules/sph/pages/create_task/create_task",
        },
        {
            title: "截流获客",
            icon: "account-fill",
            desc: "评论区评论/私信",
            color: "#FF6D00",
            lightColor: "#FFF2E6",
            platforms: ["小红书", "抖音"],
            key: CreateTypeEnum.COMMENT_MARKETING,
            navUrl: `/ai_modules/device/pages/create_closure/create_closure?type=${CreateTypeEnum.COMMENT_MARKETING}`,
        },
        {
            title: "留痕获客",
            icon: "account-fill",
            desc: "点赞/关注等互动",
            color: "#0065fb",
            lightColor: "#EBF2FF",
            platforms: ["小红书", "抖音"],
            key: CreateTypeEnum.COLLECT_MARKETING,
            navUrl: `/ai_modules/device/pages/create_closure/create_closure?type=${CreateTypeEnum.COLLECT_MARKETING}`,
        },
        {
            title: "团购评论截流",
            icon: "shopping-cart",
            desc: "团购评论区自动截流",
            color: "#FF6D00",
            lightColor: "#FFF2E6",
            platforms: ["抖音"],
            key: CreateTypeEnum.GROUP_PURCHASE,
            navUrl: `/ai_modules/device/pages/create_group_purchase/create_group_purchase`,
        },
        {
            title: "同城视频评论截流",
            icon: "map",
            desc: "同城视频评论区自动截流",
            color: "#00BFA5",
            lightColor: "#E6F9F6",
            platforms: ["抖音"],
            key: CreateTypeEnum.CITY_INTERCEPT,
            navUrl: "/ai_modules/device/pages/create_city_intercept/create_city_intercept",
        },
    ],
    // ── 私域运营 ──
    2: [
        {
            title: "自动加好友",
            icon: "account-fill",
            desc: "线索客资自动加好友",
            color: "#FF6D00",
            lightColor: "#FFF2E6",
            platforms: ["微信"],
            key: CreateTypeEnum.FRIEND_ADD,
            navUrl: "/ai_modules/device/pages/create_add_wechat/create_add_wechat",
        },
        {
            title: "朋友圈互动",
            icon: "moments",
            desc: "朋友圈自动点赞/评论",
            color: "#00BFA5",
            lightColor: "#E6F9F6",
            platforms: ["朋友圈"],
            key: CreateTypeEnum.CIRCLE_INTERACT,
            navUrl: "/ai_modules/device/pages/create_circle_interact/create_circle_interact",
        },
        {
            title: "自动养号",
            icon: "thumb-up",
            desc: "智能模拟真人操控",
            color: "#F50057",
            lightColor: "#FFE6EE",
            platforms: ["小红书", "快手", "抖音"],
            key: CreateTypeEnum.ACCOUNT_MAINTAIN,
            navUrl: "/ai_modules/device/pages/create_account_building/create_account_building",
        },
    ],
    // ── 客服接管 ──
    3: [
        {
            title: "私信接管",
            icon: "chat",
            desc: "AI自动接管平台私信",
            color: "#FF6D00",
            lightColor: "#FFF2E6",
            platforms: ["小红书", "抖音"],
            key: CreateTypeEnum.CHAT_MANAGE,
            navUrl: "/ai_modules/device/pages/create_private_take/create_private_take",
        },
        {
            title: "个微接管",
            icon: "chat-fill",
            desc: "自动处理个微回复",
            color: "#00BFA5",
            lightColor: "#E6F9F6",
            platforms: ["微信"],
            key: CreateTypeEnum.WECHAT_MSG,
            navUrl: "/ai_modules/device/pages/create_wechat_private/create_wechat_private",
        },
    ],
    // ── 同城曝光 ──
    4: [
        {
            title: "同城曝光",
            icon: "map",
            desc: "同城曝光达人",
            color: "#00BFA5",
            lightColor: "#E6F9F6",
            platforms: ["抖音"],
            key: CreateTypeEnum.CITY_EXPOSURE,
            navUrl: "/ai_modules/device/pages/create_city_exposure/create_city_exposure",
        },
    ],
};

const currentMenuList = computed(() => menuMap[activeTab.value] ?? []);

const previewUrl = ref("");
const showPreview = ref(false);

const videoUrls: Record<string, string> = {
    [CreateTypeEnum.IMAGE_PUBLISH]: `${config.baseUrl}static/videos/task_image_publish.mp4`,
    [CreateTypeEnum.VIDEO_PUBLISH]: `${config.baseUrl}static/videos/task_video_publish.mp4`,
    [CreateTypeEnum.CLUE_AUTO]: `${config.baseUrl}static/videos/task_clue_auto.mp4`,
    [CreateTypeEnum.CHAT_MANAGE]: `${config.baseUrl}static/videos/task_chat_manage.mp4`,
    [CreateTypeEnum.COMMENT_MARKETING]: `${config.baseUrl}static/videos/task_comment_marketing.mp4`,
    [CreateTypeEnum.COLLECT_MARKETING]: `${config.baseUrl}static/videos/task_collect_marketing.mp4`,
    [CreateTypeEnum.FRIEND_ADD]: `${config.baseUrl}static/videos/task_friend_add.mp4`,
    [CreateTypeEnum.ACCOUNT_MAINTAIN]: `${config.baseUrl}static/videos/task_account_maintain.mp4`,
    [CreateTypeEnum.CIRCLE]: `${config.baseUrl}static/videos/task_publish_circle.mp4`,
    [CreateTypeEnum.CIRCLE_INTERACT]: `${config.baseUrl}static/videos/task_circle_comment.mp4`,
    [CreateTypeEnum.WECHAT_MSG]: `${config.baseUrl}static/videos/task_wechat_msg.mp4`,
};

// 点击卡片 → 跳转创建任务
const handleNav = (item: any) => {
    uni.navigateTo({ url: item.navUrl });
};

// 点击箭头 → 视频预览弹窗
const handlePreview = (item: any) => {
    previewUrl.value = videoUrls[item.key] ?? "";
    showPreview.value = true;
};
</script>

<style scoped lang="scss">
.bg-primary {
    background: linear-gradient(135deg, #0065fb, #4d9fff);
}
view {
    transition: all 0.25s ease;
}
</style>
