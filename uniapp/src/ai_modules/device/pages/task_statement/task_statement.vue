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
        <view>
            <scroll-view scroll-x>
                <view class="flex gap-x-2 whitespace-nowrap p-4">
                    <view
                        v-for="(item, index) in platformTabs"
                        :key="index"
                        class="px-[24rpx] py-[10rpx] rounded-[50rpx] font-bold"
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
                    <template v-if="currTab == 0">
                        <view v-for="(item, index) in dataList" :key="index">
                            <view class="text-xs text-[#00000066] text-center">12月25日 04:44</view>
                            <view class="rounded-[20rpx] bg-white px-[40rpx] mt-[20rpx]">
                                <view class="flex items-center gap-x-[28rpx] py-[32rpx]">
                                    <image
                                        src="https://update.imai.work/uploads/images/20250606/202506061800390f1080651.jpeg"
                                        class="flex-shrink-0 w-[92rpx] h-[92rpx] rounded-[20rpx]"
                                        mode="widthFix"></image>
                                    <view>
                                        <view class="flex items-center gap-x-[12rpx]">
                                            <view class="text-[30rpx] font-bold break-all line-clamp-1">测试任务</view>
                                            <image :src="SphIcon" class="w-[32rpx] h-[32rpx]"></image>
                                        </view>
                                        <view class="text-xs text-[#00000066] font-bold mt-[4rpx]"
                                            >视频号：imaiwork</view
                                        >
                                    </view>
                                </view>
                                <view class="py-[32rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskFlagIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-bold">任务及状态</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            <text class="font-bold text-primary">视频号获客，</text>
                                            执行<text :class="true ? 'text-primary' : 'text-[#FF2442]'">{{
                                                true ? "成功" : "失败"
                                            }}</text>
                                        </view>
                                    </view>
                                </view>
                                <view class="py-[32rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskClueIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-bold">线索词</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            共线索词<text class="font-bold text-primary">30</text>条
                                        </view>
                                    </view>
                                </view>
                                <view class="py-[32rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskUserSearchIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-bold">获取客资</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            共获取客资<text class="font-bold text-primary">30</text>人
                                        </view>
                                    </view>
                                </view>
                                <view class="py-[32rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskSafeIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-bold">线索检验</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            共有效线索<text class="font-bold text-primary">30</text>条
                                        </view>
                                    </view>
                                </view>
                                <view class="py-[32rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskEmailIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-bold">私信回复</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            共私信<text class="font-bold text-primary">30</text>人， 私信消息<text
                                                class="font-bold text-primary"
                                                >30</text
                                            >条
                                        </view>
                                    </view>
                                </view>
                                <view class="py-[32rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskPostIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <!-- 这里有很多平台 -->
                                            <text class="font-bold">发布小红书</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            共发布内容<text class="font-bold text-primary">30</text>条
                                        </view>
                                    </view>
                                </view>
                                <view class="py-[32rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskPraiseIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-bold">朋友圈点赞</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            共点赞<text class="font-bold text-primary">30</text>次
                                        </view>
                                    </view>
                                </view>
                                <view class="py-[32rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskMsgIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-bold">朋友圈评论</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            共评论<text class="font-bold text-primary">30</text>条
                                        </view>
                                    </view>
                                </view>
                                <view class="py-[32rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskCollectIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-bold">评论关注</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            共关注<text class="font-bold text-primary">30</text>人
                                        </view>
                                    </view>
                                </view>
                                <view class="py-[32rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="TaskPraiseIcon" class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-bold">评论点赞</text>
                                        </view>
                                        <view class="text-[#00000080]">
                                            共点赞<text class="font-bold text-primary">30</text>次
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>
                    <template v-if="currTab == 1">
                        <view v-for="(item, index) in dataList" :key="index">
                            <view class="text-xs text-[#00000066] text-center">12月25日 04:44</view>
                            <view class="rounded-[20rpx] bg-white px-[40rpx] mt-[20rpx]">
                                <view class="text-[30rpx] font-bold py-[24rpx]">2025.12.25 算力统计</view>
                                <view
                                    class="border-[0] border-b border-solid pb-1 border-[#f2f2f2] flex flex-col gap-y-[4rpx]">
                                    <view class="flex items-center pb-2">
                                        <image :src="TokensFlagIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="font-bold text-[#00000080] ml-1">执行任务：</text>
                                        <text class="font-bold">30个</text>
                                    </view>
                                    <view class="flex items-center pb-2">
                                        <image :src="TokensTimeIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="font-bold text-[#00000080] ml-1">开始时间：</text>
                                        <text class="font-bold">2025.12.25 04:44</text>
                                    </view>
                                    <view class="flex items-center pb-2">
                                        <image :src="TokensTimeIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="font-bold text-[#00000080] ml-1">结束时间：</text>
                                        <text class="font-bold">2025.12.25 04:44</text>
                                    </view>
                                    <view class="flex items-center pb-2">
                                        <image :src="TokensIcon" class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="font-bold text-[#00000080] ml-1">算力总消耗：</text>
                                        <text class="font-bold">300000算力</text>
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
                <view class="px-4 font-bold"> 算力总消耗：<text class="text-primary">300000算力</text> </view>
                <scroll-view scroll-y class="grow min-h-0 mt-[22rpx]">
                    <view class="px-4">
                        <view class="bg-white rounded-[20rpx] px-[40rpx]">
                            <view
                                class="flex items-center justify-between py-[26rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                <text class="font-bold">视频号获客：</text>
                                <text class="font-bold"><text class="text-primary mr-[4rpx]">30</text>算力</text>
                            </view>
                            <view
                                class="flex items-center justify-between py-[26rpx] border-[0] border-b border-solid border-[#f2f2f2]">
                                <text class="font-bold">视频号获客：</text>
                                <text class="font-bold"><text class="text-primary mr-[4rpx]">30</text>算力</text>
                            </view>
                            <view class="flex items-center justify-between py-[26rpx]">
                                <text class="font-bold">视频号获客：</text>
                                <text class="font-bold"><text class="text-primary mr-[4rpx]">30</text>算力</text>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
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

const { platformLogo } = useDevice();

const tabList = ref<any[]>([
    { name: "任务报表", type: 1 },
    { name: "算力统计", type: 2 },
]);
const currTab = ref(0);

const platformTabs = [
    { name: "全部", type: 0 },
    { name: "微信", type: 1 },
    { name: "抖音", type: 2 },
    { name: "小红书", type: 3 },
    { name: "快手", type: 4 },
    { name: "视频号", type: 5 },
];
const currPlatformTab = ref(0);

const dataList = ref<any[]>([]);
const pagingRef = shallowRef<any>();

const showTokensDetailPopup = ref(false);

const handleTabChange = (index: number) => {
    currTab.value = index;
};

const handlePlatformTabChange = (index: number) => {
    currPlatformTab.value = index;
};

const queryList = (pageNo: number, pageSize: number) => {
    try {
        pagingRef.value?.complete([
            { id: 1, name: "test" },
            { id: 2, name: "test2" },
        ]);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleTokensDetail = (item: any) => {
    showTokensDetailPopup.value = true;
};

onLoad(() => {});
</script>

<style scoped lang="scss">
:deep(.u-tab-item) {
    font-weight: bold;
}
</style>
