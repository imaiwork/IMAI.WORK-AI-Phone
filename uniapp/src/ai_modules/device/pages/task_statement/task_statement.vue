<template>
    <view class="h-screen flex flex-col">
        <u-navbar
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
            <view class="ml-[100rpx] mr-[64rpx] w-full">
                <u-tabs
                    :list="tabList"
                    :is-scroll="false"
                    :current="currTab"
                    active-color="#000000"
                    inactive-color="#00000080"
                    bg-color="transparent"
                    @change="handleTabChange"></u-tabs>
            </view>
        </u-navbar>
        <view class="mt-[20rpx] mx-4 w-[308rpx]" v-if="false">
            <view class="bg-white rounded-[16rpx] px-[4rpx]">
                <view class="grid grid-cols-2 gap-x-1 h-[72rpx] relative">
                    <view
                        v-for="(item, index) in taskStatementTabs"
                        :key="index"
                        class="task-statement-tab-item"
                        :class="{ active: index == currTaskStatementTab }"
                        @click="currTaskStatementTab = index">
                        <view>
                            {{ item.name }}
                        </view>
                    </view>
                    <view
                        class="tab-slider"
                        :style="{ transform: `translateX(${currTaskStatementTab * 100}%)` }"></view>
                </view>
            </view>
        </view>
        <view>
            <scroll-view scroll-x>
                <view class="flex gap-x-2 whitespace-nowrap p-4">
                    <view
                        v-for="(item, index) in platformTabs"
                        :key="index"
                        class="px-[24rpx] py-[10rpx] rounded-[50rpx] font-medium"
                        :class="
                            currPlatformTab === index ? 'bg-black text-white' : 'shadow-[0_0_0_2rpx_rgba(0,0,0,0.1)]'
                        "
                        @click="handlePlatformTabChange(index)">
                        {{ item.name }}
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-4 flex flex-col gap-y-[50rpx]">
                    <template v-if="currTaskStatementTab == 0">
                        <view v-for="(item, index) in dataList" :key="index">
                            <view class="text-xs text-[#00000066] text-center">{{ formatDate(item.create_time) }}</view>
                            <view class="rounded-[20rpx] bg-white px-[40rpx] mt-[20rpx]">
                                <view class="flex items-center gap-x-[28rpx] py-[32rpx]">
                                    <image
                                        :src="item.account?.avatar"
                                        class="flex-shrink-0 w-[92rpx] h-[92rpx] rounded-[20rpx]"
                                        mode="widthFix"></image>
                                    <view>
                                        <view class="flex items-center gap-x-[12rpx]">
                                            <view class="text-[30rpx] font-medium break-all line-clamp-1">{{
                                                item.account?.nickname
                                            }}</view>
                                            <image
                                                :src="getPlatformIcon(item.account?.type)"
                                                class="w-[32rpx] h-[32rpx]"></image>
                                        </view>
                                        <view class="text-xs text-[#00000066] font-medium mt-[4rpx]"
                                            >{{ getPlatformName(item.account?.type) }}：{{
                                                item.account?.account
                                            }}</view
                                        >
                                    </view>
                                </view>
                                <view class="py-[32rpx]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskFlagIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-medium">任务及状态</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            <text class="font-medium text-primary">{{ item.task_name }}，</text>
                                            执行<text
                                                class="font-medium"
                                                :class="item.status == 2 ? 'text-primary' : 'text-[#FF2442]'"
                                                >{{ item.status == 2 ? "成功" : "失败" }}</text
                                            >
                                        </view>
                                    </view>
                                </view>
                                <template v-if="item.task_type == TaskTypeEnum.CLUES">
                                    <view class="py-[32rpx] border-[0] border-t border-solid border-[#f2f2f2]">
                                        <view class="flex items-center justify-between">
                                            <view class="flex items-center gap-x-2">
                                                <image :src="TaskClueIcon" class="w-[28rpx] h-[28rpx]"></image>
                                                <text class="font-medium">线索词</text>
                                            </view>
                                            <view class="text-[#00000080]">
                                                共线索词<text class="font-medium text-primary">{{
                                                    item.data_info?.keyword_number || 0
                                                }}</text
                                                >条
                                            </view>
                                        </view>
                                    </view>
                                    <view class="py-[32rpx] border-[0] border-t border-solid border-[#f2f2f2]">
                                        <view class="flex items-center justify-between">
                                            <view class="flex items-center gap-x-2">
                                                <image :src="TaskUserSearchIcon" class="w-[28rpx] h-[28rpx]"></image>
                                                <text class="font-medium">获取客资</text>
                                            </view>
                                            <view class="text-[#00000080]">
                                                共获取客资<text class="font-medium text-primary">{{
                                                    item.data_info?.clues_number || 0
                                                }}</text
                                                >人
                                            </view>
                                        </view>
                                    </view>
                                </template>

                                <view
                                    class="py-[32rpx] border-[0] border-t border-solid border-[#f2f2f2]"
                                    v-if="item.task_type == TaskTypeEnum.TAKEOVER">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskEmailIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-medium">私信回复</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            私信消息<text class="font-medium text-primary">{{
                                                item.data_info?.reply_number || 0
                                            }}</text
                                            >条
                                        </view>
                                    </view>
                                </view>
                                <!-- <view
                                    class="py-[32rpx] border-[0] border-t border-solid border-[#f2f2f2]"
                                    v-if="item.task_type == TaskTypeEnum.WECHAT_CIRCLE">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskPostIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-medium">发布朋友圈</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            共发布内容<text class="font-medium text-primary">30</text>条
                                        </view>
                                    </view>
                                </view> -->
                                <template v-if="item.task_type == TaskTypeEnum.WECHAT_CIRCLE_THUMB_COMMENT">
                                    <view class="py-[32rpx] border-[0] border-t border-solid border-[#f2f2f2]">
                                        <view class="flex items-center justify-between">
                                            <view class="flex items-center gap-x-2">
                                                <image :src="TaskPraiseIcon" class="w-[28rpx] h-[28rpx]"></image>
                                                <text class="font-medium">点赞评论</text>
                                            </view>
                                            <view class="text-[#00000080]">
                                                共点赞/评论<text class="font-medium text-primary">{{
                                                    item.data_info?.like_comment_number || 0
                                                }}</text
                                                >次
                                            </view>
                                        </view>
                                    </view>
                                </template>

                                <view
                                    class="py-[32rpx] border-[0] border-t border-solid border-[#f2f2f2]"
                                    v-if="item.task_type == TaskTypeEnum.FRIENDS">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskWechatIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-medium">自动加微</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            共自动加好友<text class="font-medium text-primary">{{
                                                item.data_info?.add_wechat_number || 0
                                            }}</text
                                            >人
                                        </view>
                                    </view>
                                </view>
                                <template v-if="item.task_type == TaskTypeEnum.TOUCH">
                                    <view
                                        class="py-[32rpx] border-[0] border-t border-solid border-[#f2f2f2]"
                                        v-if="item.task_scene == 1">
                                        <view class="flex items-center justify-between">
                                            <view class="flex items-center gap-x-2">
                                                <image :src="TaskMsgIcon" class="w-[28rpx] h-[28rpx]"></image>
                                                <text class="font-medium">评论区回复</text>
                                            </view>
                                            <view class="text-[#00000080]">
                                                评论消息<text class="font-medium text-primary">{{
                                                    item.data_info?.comment_number || 0
                                                }}</text
                                                >人
                                            </view>
                                        </view>
                                    </view>
                                    <view
                                        class="py-[32rpx] border-[0] border-t border-solid border-[#f2f2f2]"
                                        v-if="item.task_scene == 2">
                                        <view class="flex items-center justify-between">
                                            <view class="flex items-center gap-x-2">
                                                <image :src="TaskEmailIcon" class="w-[28rpx] h-[28rpx]"></image>
                                                <text class="font-medium">评论区私信</text>
                                            </view>
                                            <view class="text-[#00000080]">
                                                私信消息<text class="font-medium text-primary">{{
                                                    item.data_info?.comment_number || 0
                                                }}</text
                                                >条
                                            </view>
                                        </view>
                                    </view>
                                    <view
                                        class="py-[32rpx] border-[0] border-t border-solid border-[#f2f2f2]"
                                        v-if="item.task_scene == 3">
                                        <view class="flex items-center justify-between">
                                            <view class="flex items-center gap-x-2">
                                                <image :src="TaskEmailIcon" class="w-[28rpx] h-[28rpx]"></image>
                                                <text class="font-medium">关注/点赞</text>
                                            </view>
                                            <view class="text-[#00000080]">
                                                共关注/点赞<text class="font-medium text-primary">{{
                                                    item.data_info?.comment_number || 0
                                                }}</text
                                                >条
                                            </view>
                                        </view>
                                    </view>
                                </template>
                            </view>
                        </view>
                    </template>
                    <template v-if="currTaskStatementTab == 1">
                        <view v-for="(item, index) in dataList" :key="index">
                            <view class="text-xs text-[#00000066] text-center">12月25日 04:44</view>
                            <view class="rounded-[20rpx] bg-white px-[40rpx] mt-[20rpx]">
                                <view class="text-[30rpx] font-medium py-[24rpx]">2025.12.25 算力统计</view>
                                <view
                                    class="border-[0] border-t border-solid pb-1 border-[#f2f2f2] flex flex-col gap-y-[4rpx]">
                                    <view class="flex items-center pb-2">
                                        <image :src="TokensFlagIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="font-medium text-[#00000080] ml-1">执行任务：</text>
                                        <text class="font-medium">30个</text>
                                    </view>
                                    <view class="flex items-center pb-2">
                                        <image :src="TokensTimeIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="font-medium text-[#00000080] ml-1">开始时间：</text>
                                        <text class="font-medium">2025.12.25 04:44</text>
                                    </view>
                                    <view class="flex items-center pb-2">
                                        <image :src="TokensTimeIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="font-medium text-[#00000080] ml-1">结束时间：</text>
                                        <text class="font-medium">2025.12.25 04:44</text>
                                    </view>
                                    <view class="flex items-center pb-2">
                                        <image :src="TokensIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="font-medium text-[#00000080] ml-1">算力总消耗：</text>
                                        <text class="font-medium">300000算力</text>
                                    </view>
                                </view>
                                <view class="flex items-center justify-between py-3" @click="handleTokensDetail(item)">
                                    <text>算力消耗明细</text>
                                    <u-icon name="arrow-right" size="20" color="#B2B2B2"></u-icon>
                                </view>
                            </view>
                        </view>
                    </template>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
    </view>
    <popup-bottom
        v-model="showTokensDetailPopup"
        title="算力消耗明细"
        custom-class="bg-[#F6F6F6]"
        @close="showTokensDetailPopup = false">
        <template #content>
            <view class="h-full flex flex-col py-4">
                <view class="px-4 font-medium"> 算力总消耗：<text class="text-primary">300000算力</text> </view>
                <scroll-view scroll-y class="grow min-h-0 mt-[22rpx]">
                    <view class="px-4">
                        <view class="bg-white rounded-[20rpx] px-[40rpx]">
                            <view
                                class="flex items-center justify-between py-[26rpx] border-[0] border-t border-solid border-[#f2f2f2]">
                                <text class="font-medium">视频号获客：</text>
                                <text class="font-medium"><text class="text-primary mr-[4rpx]">30</text>算力</text>
                            </view>
                            <view
                                class="flex items-center justify-between py-[26rpx] border-[0] border-t border-solid border-[#f2f2f2]">
                                <text class="font-medium">视频号获客：</text>
                                <text class="font-medium"><text class="text-primary mr-[4rpx]">30</text>算力</text>
                            </view>
                            <view class="flex items-center justify-between py-[26rpx]">
                                <text class="font-medium">视频号获客：</text>
                                <text class="font-medium"><text class="text-primary mr-[4rpx]">30</text>算力</text>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getDeviceTaskDashboard } from "@/api/device";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import SphIcon from "@/static/images/common/sph_s.png";
import TaskClueIcon from "@/ai_modules/device/static/icons/task_clue.svg";
import TaskEmailIcon from "@/ai_modules/device/static/icons/task_email.svg";
import TaskFlagIcon from "@/ai_modules/device/static/icons/task_flag.svg";
import TaskMsgIcon from "@/ai_modules/device/static/icons/task_msg.svg";
import TaskSafeIcon from "@/ai_modules/device/static/icons/task_safe.svg";
import TaskUserSearchIcon from "@/ai_modules/device/static/icons/task_user_search.svg";
import TaskWechatIcon from "@/ai_modules/device/static/icons/task_wechat.svg";
import TaskPostIcon from "@/ai_modules/device/static/icons/task_post.svg";
import TaskPraiseIcon from "@/ai_modules/device/static/icons/task_praise.svg";
import TaskCollectIcon from "@/ai_modules/device/static/icons/task_collect.svg";
import TokensFlagIcon from "@/ai_modules/device/static/icons/tokens_flag.svg";
import TokensTimeIcon from "@/ai_modules/device/static/icons/tokens_time.svg";
import TokensIcon from "@/ai_modules/device/static/icons/tokens.svg";

enum TaskTypeEnum {
    PUBLISH = 1, // 发布
    TAKEOVER = 2, // 接管
    ACTIVE = 3, // 养号
    CLUES = 4, // 获客
    FRIENDS = 5, // 加好友
    TOUCH = 6, //截流获客
    WECHAT_CIRCLE = 7, // 朋友圈发布
    WECHAT_CIRCLE_THUMB_COMMENT = 8, // 朋友圈点赞评论
    CLUES_WECHAT = 9, //视频号获客加微任务
}

const { platform } = useDevice();
const deviceCode = ref("");
const tabList = ref<any[]>([
    { name: "24h任务", type: 1 },
    { name: "手动任务", type: 2 },
]);
const currTab = ref(0);

const taskStatementTabs = ref<any[]>([
    { name: "任务报表", type: 1 },
    { name: "算力统计", type: 2 },
]);
const currTaskStatementTab = ref(0);

const platformTabs = [
    { name: "全部", type: 0 },
    { name: "微信", type: 2 },
    { name: "抖音", type: 4 },
    { name: "小红书", type: 3 },
    { name: "快手", type: 5 },
    { name: "视频号", type: 1 },
];
const currPlatformTab = ref(0);

const dataList = ref<any[]>([]);
const pagingRef = shallowRef<any>();

const showTokensDetailPopup = ref(false);

const handleTabChange = (index: number) => {
    currTab.value = index;
    pagingRef.value?.reload();
};

const handlePlatformTabChange = (index: number) => {
    currPlatformTab.value = index;
    pagingRef.value?.reload();
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getDeviceTaskDashboard({
            device_code: deviceCode.value,
            auto_type: currTab.value === 0 ? 1 : 0,
            page_no,
            page_size,
            account_type: platformTabs[currPlatformTab.value].type,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleTokensDetail = (item: any) => {
    showTokensDetailPopup.value = true;
};

const formatDate = (date: string) => {
    return uni.$u.timeFormat(date, "yyyy年mm月dd日 hh:MM");
};

const getPlatformIcon = (app_type: number) => {
    return platform.value[app_type as keyof typeof platform.value]?.activeIcon;
};

const getPlatformName = (app_type: number) => {
    return platform.value[app_type as keyof typeof platform.value]?.name;
};

onLoad((options: any) => {
    if (options.device_code) {
        deviceCode.value = options.device_code;
    }
});
</script>

<style scoped lang="scss">
:deep(.u-tab-item) {
    font-weight: bold;
}

.task-statement-tab-item {
    @apply flex flex-col items-center justify-center rounded-[16rpx] text-[#00000080] relative z-10 transition-colors duration-500;
    &.active {
        @apply text-black font-medium relative;
    }
}
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-[#F9FAFB] absolute top-[4rpx] left-0 transition-all duration-500;
    &::after {
        content: "";
        @apply absolute bottom-0 w-[20%] h-[4rpx] bg-black;
        // 让线居中
        left: 0;
        right: 0;
        margin: auto;
    }
}
</style>
