<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            is-custom-back-icon
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
            <view class="text-[30rpx] font-bold">AI手机</view>
        </u-navbar>
        <view class="px-[26rpx] mt-2 flex items-center gap-x-2">
            <view
                class="flex-1 flex items-center gap-x-2 bg-primary h-[90rpx] rounded-[10rpx] justify-center"
                @click="toPage('/packages/pages/rpa_code/rpa_code')">
                <image src="/static/images/icons/device_white.svg" class="w-[28rpx] h-[28rpx]"></image>
                <text class="text-[30rpx] font-bold text-white">新增设备</text>
            </view>
            <view
                class="flex-1 bg-black rounded-[10rpx] h-[90rpx] flex items-center gap-x-2 justify-center"
                @click="toPage('/ai_modules/device/pages/task_calendar/task_calendar')">
                <image src="/static/images/icons/calendar_white.svg" class="w-[28rpx] h-[28rpx]"></image>
                <text class="text-[30rpx] font-bold text-white">任务日历</text>
            </view>
        </view>
        <view class="grow min-h-0 mt-5">
            <z-paging
                ref="pagingRef"
                v-model="deviceList"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-[26rpx]">
                    <view class="flex flex-col gap-y-[20rpx]">
                        <view
                            v-for="(item, index) in deviceList"
                            :key="index"
                            class="bg-white rounded-[20rpx] px-5 py-[28rpx]"
                            @click="handleDetail(item)">
                            <view class="flex items-center justify-between gap-x-4">
                                <view class="line-clamp-1 text-[30rpx] font-bold break-all"
                                    >{{ item.device_name || "-" }}
                                </view>
                                <view
                                    class="flex-shrink-0 px-[20rpx] py-[6rpx] rounded-[12rpx] font-bold"
                                    :class="getDeviceStatusStyle(item.status)">
                                    {{ item.status === 0 ? "离线" : item.status === 1 ? "在线" : "工作中" }}
                                </view>
                            </view>
                            <view class="mt-[18rpx] flex flex-wrap items-center gap-1">
                                <image
                                    :src="getDeviceIcon(account.type)"
                                    class="w-[40rpx] h-[40rpx]"
                                    v-for="(account, index) in item.accounts"
                                    :key="index"></image>
                            </view>
                            <view
                                class="mt-[26rpx] py-[26rpx] border-[0] border-t-[2rpx] border-b-[2rpx] border-solid border-[#00000008]">
                                <view class="font-bold">当前任务（{{ item.task_count }}）</view>
                                <view class="flex gap-x-10" v-if="item.tasks?.length > 0">
                                    <view class="flex-1">
                                        <view
                                            class="flex items-center gap-x-2 mt-[20rpx]"
                                            :class="{
                                                'text-[#FF2442]': [3, 4].includes(task.status),
                                                'text-primary': [1].includes(task.status),
                                                'text-[#0000004d]': [0].includes(task.status),
                                                'text-[#00C08E]': [2].includes(task.status),
                                            }"
                                            v-for="task in item.tasks"
                                            :key="task.id">
                                            <view class="line-clamp-1 text-xs w-[60%]"
                                                ><text>•</text> {{ task.task_name }}</view
                                            >
                                            <view
                                                class="text-xs flex-shrink-0"
                                                :class="{
                                                    'text-[#FF2442]': [3, 4].includes(task.status),
                                                    'text-primary': [1].includes(task.status),
                                                    'text-[#0000004d]': [0].includes(task.status),
                                                    'text-[#00C08E]': [2].includes(task.status),
                                                }">
                                                {{ getTaskStatusText(task.status) }}
                                            </view>
                                        </view>
                                    </view>
                                    <view class="flex-shrink-0">
                                        <circle-progress
                                            :percent="getTaskStatusPercent(item)"
                                            borderWidth="10rpx"
                                            width="136rpx"
                                            progressColor="#467EF9"
                                            notProgressColor="#F1F1F1"></circle-progress>
                                    </view>
                                </view>
                                <view v-else class="text-xs text-[#0000004d] py-10 text-center">暂无内容</view>
                            </view>
                            <view class="flex items-center justify-between gap-x-2 mt-[22rpx]">
                                <text class="text-xs text-[#0000004d]">查看详情</text>
                                <u-icon name="arrow-right" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
        <tabbar />
    </view>
</template>

<script setup lang="ts">
import { getDeviceList } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import wechatActiveIcon from "@/static/images/common/wechat_s.png";
import redbookActiveIcon from "@/static/images/common/redbook_s.png";
import douyinActiveIcon from "@/static/images/common/douyin_s.png";
import kuaishouActiveIcon from "@/static/images/common/kuaishou_s.png";

const deviceList = ref<any[]>([]);
const pagingRef = shallowRef();

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getDeviceList({
            page_no,
            page_size,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const getDeviceIcon = (type: number) => {
    switch (type) {
        case AppTypeEnum.WECHAT:
            return wechatActiveIcon;
        case AppTypeEnum.XHS:
            return redbookActiveIcon;
        case AppTypeEnum.DOUYIN:
            return douyinActiveIcon;
        case AppTypeEnum.KUAISHOU:
            return kuaishouActiveIcon;
    }
};

// 获取设备状态样式
const getDeviceStatusStyle = (status: number) => {
    switch (status) {
        case 0:
            return "text-[#FF2442] bg-[rgba(255,36,66,0.1)]";
        case 1:
            return "text-[#00C08E] bg-[rgba(0,192,142,0.1)]";
        case 2:
        default:
            return "text-primary bg-primary-light-9";
    }
};

const getTaskStatusText = (status: number) => {
    switch (status) {
        case 0:
            return "等待中";
        case 1:
            return "执行中";
        case 2:
            return "执行完成";
        case 3:
            return "执行失败";
        case 4:
            return "中断";
        default:
            return "-";
    }
};

const getTaskStatusPercent = (item: any) => {
    const { task_count, task_complete } = item;
    if (task_count == 0) return 0;
    return Math.round((task_complete / task_count) * 100);
};

const toPage = (url: string) => {
    uni.navigateTo({
        url,
    });
};

const handleDetail = (item: any) => {
    uni.navigateTo({
        url: `/ai_modules/device/pages/detail/detail?device_code=${item.device_code}`,
    });
};

onShow(async () => {
    await nextTick();
    pagingRef.value?.reload();
});
</script>

<style scoped></style>
