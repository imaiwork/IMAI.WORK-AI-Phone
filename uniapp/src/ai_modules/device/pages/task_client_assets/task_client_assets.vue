<template>
    <view class="h-screen flex flex-col bg-[#F4F6F9]">
        <u-navbar :border-bottom="false" :background="{ background: '#FFFFFF' }">
            <view class="ml-[100rpx] mr-[64rpx] w-full">
                <u-tabs
                    :list="tabList"
                    :is-scroll="false"
                    :current="currTab"
                    active-color="#0065fb"
                    inactive-color="#666666"
                    bar-width="40"
                    bar-height="6"
                    bg-color="transparent"
                    @change="handleTabChange">
                </u-tabs>
            </view>
        </u-navbar>

        <view
            class="h-[100rpx] bg-white border-[0] border-b border-solid border-[#F0F0F0] px-[32rpx] py-[20rpx] flex items-center justify-between z-40 shadow-[0_4rpx_10rpx_rgba(0,0,0,0.01)]">
            <view
                class="flex items-center gap-[8rpx] bg-[#F8F9FA] px-[24rpx] py-[12rpx] rounded-full border border-[#E5E5E5] active:scale-95 transition-transform"
                @click="handleTabOptionChange()">
                <text class="text-[#333333] font-medium">{{ getTabText(currTab) }}</text>
                <u-icon name="arrow-down" size="20" color="#999999"></u-icon>
            </view>

            <view
                class="flex items-center gap-[8rpx] active:opacity-70 transition-opacity"
                @click="showCalendar = true">
                <text class="text-xs text-[#666666] font-medium">{{ calendarRange.join(" - ") }}</text>
                <u-icon name="arrow-down-fill" size="18" color="#333333"></u-icon>
            </view>
        </view>

        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-[32rpx] pt-[24rpx] pb-[40rpx] flex flex-col gap-[24rpx]">
                    <template v-if="currTab === 0">
                        <view
                            v-for="(item, index) in dataList"
                            :key="'leads_' + index"
                            class="bg-white rounded-[24rpx] p-[32rpx] shadow-[0_4rpx_16rpx_rgba(0,0,0,0.02)] flex flex-col">
                            <view class="flex items-start justify-between mb-[16rpx] gap-[24rpx]">
                                <view class="flex-1 min-w-0">
                                    <view class="flex items-center flex-wrap gap-[12rpx]">
                                        <text class="text-[32rpx] font-extrabold text-[#1A1A1A] break-all">{{
                                            item.reg_content
                                        }}</text>
                                        <view
                                            class="shrink-0 px-[16rpx] py-[4rpx] rounded-[8rpx] text-[20rpx] font-bold"
                                            :class="getClueStatusClass(item.status)">
                                            {{ getClueStatusText(item.status) }}
                                        </view>
                                    </view>
                                </view>
                                <text
                                    class="text-xs shrink-0 mt-[8rpx] font-medium"
                                    :class="item.is_verify == 1 ? 'text-success' : 'text-[#B4B4B4]'"
                                    >{{ item.is_verify == 1 ? "已添加" : "未添加" }}</text
                                >
                            </view>

                            <view class="flex items-center gap-[8rpx] text-[#999999] mb-[20rpx]">
                                <u-icon name="share" size="24"></u-icon>
                                <text class="text-xs truncate">来源：{{ item.task_name }}</text>
                            </view>

                            <view class="bg-[#F8F9FA] rounded-[16rpx] p-[24rpx] flex flex-col gap-[16rpx]">
                                <text class="text-[#666666] leading-relaxed">{{ item.task_detail_described }}</text>
                                <view class="flex items-center justify-between mt-[8rpx]">
                                    <text class="text-[22rpx] text-[#B4B4B4] font-mono">{{ item.exec_time }}</text>
                                    <view
                                        class="flex items-center gap-[4rpx] text-[#B4B4B4] active:opacity-70"
                                        @click="handleDetail(item)">
                                        <text class="text-[22rpx]">详情</text>
                                        <u-icon name="arrow-right" size="20"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>

                    <template v-if="currTab === 1">
                        <view
                            v-for="(item, index) in dataList"
                            :key="'follow_' + index"
                            class="bg-white rounded-[24rpx] p-[32rpx] shadow-[0_4rpx_16rpx_rgba(0,0,0,0.02)] flex flex-col">
                            <view class="flex items-start justify-between mb-[20rpx] gap-[24rpx]">
                                <view class="flex flex-col gap-[8rpx] flex-1 min-w-0">
                                    <text class="text-[32rpx] font-extrabold text-[#1A1A1A] truncate">{{
                                        item.account
                                    }}</text>
                                    <text class="text-[22rpx] text-[#999999]">触发时间：{{ item.update_time }}</text>
                                </view>
                                <view
                                    class="px-[20rpx] py-[8rpx] rounded-full text-[22rpx] font-bold shrink-0 mt-[4rpx]"
                                    :class="getFollowStatusStyle(item.intention_type)">
                                    {{ getFollowStatusText(item.intention_type) }}
                                </view>
                            </view>

                            <view class="flex items-center gap-[8rpx] text-[#999999] mb-[20rpx]">
                                <u-icon name="share" size="24"></u-icon>
                                <text class="text-xs truncate">来源：{{ item.task_name }}</text>
                            </view>

                            <view class="bg-[#F8F9FA] rounded-[16rpx] p-[24rpx] flex items-start gap-[20rpx]">
                                <view
                                    class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center flex-shrink-0 mt-[4rpx] bg-white shadow-[0_4rpx_12rpx_rgba(0,0,0,0.05)]">
                                    <image
                                        :src="platformIconMap[item.account_type]"
                                        class="w-[40rpx] h-[40rpx]"
                                        mode="aspectFit" />
                                </view>

                                <view class="flex flex-col gap-[16rpx] flex-1 min-w-0">
                                    <text class="text-[#333333] leading-relaxed font-medium break-all">{{
                                        item.task_detail_described
                                    }}</text>
                                    <view
                                        class="flex items-center gap-[4rpx] text-[#999999] active:opacity-70 w-fit"
                                        @click="handleDetail(item)">
                                        <text class="text-xs">查看详情</text>
                                        <u-icon name="arrow-right" size="22"></u-icon>
                                    </view>
                                </view>
                            </view>

                            <view
                                class="flex items-center justify-between pt-[24rpx] border-[0] border-t border-solid border-[#F5F5F5] mt-[24rpx] gap-[24rpx]">
                                <view class="flex items-center gap-[8rpx] flex-1 min-w-0">
                                    <text class="text-xs text-[#999999] shrink-0">执行:</text>
                                    <text class="text-xs text-[#666666] font-medium truncate"
                                        >{{ item.device_name }}({{ item.device_code }})</text
                                    >
                                </view>
                                <text class="text-[22rpx] text-[#B4B4B4] shrink-0">{{ item.create_time }}</text>
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
    <u-calendar v-model="showCalendar" mode="range" @change="handleCalendarChange"></u-calendar>
    <clue-detail-pop
        v-model="showTaskClueDetailPop"
        :detail-data="detailData"
        @close="showTaskClueDetailPop = false"></clue-detail-pop>
    <follow-detail-pop
        v-model="showTaskFollowDetailPop"
        :detail-data="detailData"
        @close="showTaskFollowDetailPop = false"></follow-detail-pop>
</template>

<script setup lang="ts">
import { getTaskClue } from "@/api/sph";
import { getTaskTouchFollowList } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import XhsIcon from "@/static/images/common/redbook_s.png";
import SphIcon from "@/static/images/common/sph_s.png";
import DyIcon from "@/static/images/common/douyin_s.png";
import KsIcon from "@/static/images/common/kuaishou_s.png";
import ClueDetailPop from "./components/clue-detail-pop.vue";
import FollowDetailPop from "./components/follow-detail-pop.vue";

const platformIconMap: any = {
    [AppTypeEnum.XHS]: XhsIcon,
    [AppTypeEnum.WECHAT]: SphIcon,
    [AppTypeEnum.DOUYIN]: DyIcon,
    [AppTypeEnum.KUAISHOU]: KsIcon,
};

const tabList = ref<any[]>([
    {
        name: "客资线索",
        options: [
            { label: "全部", value: "" },
            { label: "有效线索", value: 1 },
            { label: "无效线索", value: 2 },
            { label: "内含有效线索", value: 3 },
        ],
    },
    {
        name: "触达跟进",
        options: [
            { label: "全部", value: "" },
            { label: "成交意愿", value: 1 },
            { label: "询价意愿", value: 2 },
            { label: "想要加微信", value: 3 },
            { label: "一般意愿", value: 4 },
            { label: "明确拒绝", value: 5 },
        ],
    },
]);
const currTab = ref(0);
const currTabOption = ref("");
const pagingRef = ref<any>(null);
const showCalendar = ref(false);
const showTaskClueDetailPop = ref(false);
const showTaskFollowDetailPop = ref(false);
const detailData = ref<any>({});
const calendarRange = ref<any[]>([
    uni.$u.timeFormat(new Date(), "yyyy-mm-dd"),
    uni.$u.timeFormat(new Date(), "yyyy-mm-dd"),
]);
const handleTabChange = (index: number) => {
    currTab.value = index;
    currTabOption.value = "";
    pagingRef.value?.reload();
};

const handleTabOptionChange = () => {
    uni.showActionSheet({
        title: "选择",
        itemList: tabList.value[currTab.value].options.map((option: any) => option.label),
        success: (res: any) => {
            const index = res.tapIndex;
            const selectedOption = tabList.value[currTab.value].options[index];
            currTabOption.value = selectedOption.value;
            pagingRef.value?.reload();
        },
    });
};

const dataList = ref<any[]>([]);

const queryList = async (page_no: number, page_size: number) => {
    try {
        let resList = [];
        let [start_time, end_time] = calendarRange.value;
        start_time = `${start_time} 00:00:00`;
        end_time = `${end_time} 23:59:59`;
        if (currTab.value === 0) {
            const res = await getTaskClue({
                page_no,
                page_size,
                start_time,
                end_time,
                status: currTabOption.value,
            });
            resList = res.lists;
        } else if (currTab.value === 1) {
            const res = await getTaskTouchFollowList({
                page_no,
                page_size,
                start_time,
                end_time,
                intention_type: currTabOption.value,
            });
            resList = res.lists;
        }
        pagingRef.value?.complete(resList);
    } catch (e) {
        pagingRef.value?.complete(false);
    }
};

const handleCalendarChange = (e: any) => {
    calendarRange.value = [e.startDate, e.endDate];
    pagingRef.value?.reload();
};

const getTabText = (index: number) => {
    const tab = tabList.value[index];
    if (currTabOption.value === "") {
        return tab.options[0].label;
    }
    const option = tab.options.find((opt: any) => opt.value === currTabOption.value);
    return option ? option.label : tab.options[0].label;
};

const getClueStatusText = (status: number) => {
    switch (status) {
        case 1:
            return "有效线索";
        case 2:
            return "无效线索";
        case 3:
            return "内含有效线索";
        default:
            return "待处理";
    }
};

const getClueStatusClass = (status: number) => {
    switch (status) {
        case 1:
            return "bg-[#E6F6ED] text-[#00B578]";
        case 2:
            return "bg-[#FFEDED] text-[#FF3141]";
        case 3:
            return "bg-[#FFF4E5] text-[#FF8F1F]";
        default:
            return "bg-[#F5F5F5] text-[#999999]";
    }
};

const getFollowStatusStyle = (status: number) => {
    switch (status) {
        case 1:
            return "bg-[rgba(0,192,142,0.1)] text-[#00C08E]"; // 成交 - 绿
        case 2:
            return "bg-[rgba(0,101,251,0.08)] text-primary"; // 询价 - 蓝
        case 3:
            return "bg-[rgba(255,149,0,0.1)] text-[#FF9500]"; // 加微信 - 橙
        case 4:
            return "bg-[rgba(0,0,0,0.04)] text-[#00000066]"; // 一般 - 灰
        case 5:
            return "bg-[rgba(255,36,36,0.1)] text-[#FF2442]"; // 拒绝 - 红
        default:
            return "bg-[rgba(0,0,0,0.04)] text-[#00000066]";
    }
};

const getFollowStatusText = (status: number) => {
    switch (status) {
        case 1:
            return "成交意愿";
        case 2:
            return "询价意愿";
        case 3:
            return "想要加微信";
        case 4:
            return "一般意愿";
        case 5:
            return "明确拒绝";
        default:
            return "待处理";
    }
};

const handleDetail = (item: any) => {
    if (currTab.value === 0) {
        showTaskClueDetailPop.value = true;
    } else {
        showTaskFollowDetailPop.value = true;
    }
    detailData.value = item;
};
</script>
