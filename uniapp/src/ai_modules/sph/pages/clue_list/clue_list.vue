<template>
    <view class="h-screen flex flex-col bg-[#F6F6F6]">
        <u-navbar title="线索词" :background="{ backgroundColor: '#f6f6f6' }" />
        <view class="grow min-h-0 mt-[32rpx]">
            <view class="px-[32rpx] pb-[48rpx] flex flex-col gap-[24rpx]">
                <view
                    class="bg-white rounded-[40rpx] px-[32rpx]"
                    v-for="(item, index) in dataLists"
                    :key="index"
                    @click="handleClick(item)">
                    <view
                        class="flex justify-between items-center h-[100rpx] border-solid border-[0] border-b-[1rpx] border-[#0000000d] gap-x-2">
                        <view> 线索词： </view>
                        <view
                            class="px-[28rpx] py-[10rpx] rounded text-[22rpx] font-bold"
                            :class="getTagClass(item.status)"
                            >#{{ item.keyword }}</view
                        >
                    </view>
                    <view class="py-[36rpx] flex items-center justify-between gap-x-2">
                        <view>当前获客数：{{ item.count || "-" }}  / 人</view>
                        <view>
                            当前状态：<text :class="getTagClass(item.status)" class="!bg-[transparent]">{{
                                statusMap[item.status]
                            }}</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getTaskClueKeywords } from "@/api/sph";
const taskId = ref("");

const dataLists = ref<any[]>([]);

const getLists = async () => {
    const data = await getTaskClueKeywords({ id: taskId.value });
    dataLists.value = data;
};
const statusMap: any = {
    0: "未执行",
    1: "执行中",
    2: "已完成",
    3: "已完成",
    4: "已结束",
};

const getTagClass = (status: number) => {
    if (status == 1) {
        return "bg-[#FFF0E5] text-[#FB6500]";
    } else if (status == 2 || status == 0) {
        return "bg-[#F6F6F6] text-[#00000080]";
    } else if (status == 3) {
        return "bg-[#EAF8E5] text-[#2DBC00]";
    } else if (status == 4) {
        return "bg-[#F6F6F6] text-[#00000080]";
    }
};
const handleClick = (item: any) => {
    uni.navigateTo({
        url: "/ai_modules/sph/pages/clue_detail/clue_detail",
        query: {
            task_id: taskId.value,
            keyword: item.keyword,
        },
    });
};

onLoad(({ task_id }: any) => {
    taskId.value = task_id;
    getLists();
});
</script>

<style scoped></style>
