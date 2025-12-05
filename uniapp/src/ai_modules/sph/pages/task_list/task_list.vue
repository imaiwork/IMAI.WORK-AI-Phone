<template>
    <view class="h-screen flex flex-col bg-[#F6F6F6]">
        <u-navbar title="线索词" :background="{ backgroundColor: '#f6f6f6' }" />
        <view class="grow min-h-0 mt-[32rpx]">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-[32rpx] pb-[48rpx] flex flex-col gap-[24rpx]">
                    <view class="" v-for="(item, index) in dataLists" :key="index">
                        <task-card :item="item" @detail="handleDetail" @changeStatus="handleChangeStatus" />
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getTaskList } from "@/api/sph";
import TaskCard from "../../components/task-card/task-card.vue";

const dataLists = ref([]);
const pagingRef = ref();

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getTaskList({ page_no, page_size });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleChangeStatus = async (item: any) => {
    pagingRef.value?.reload();
};

const handleDetail = (id: string) => {
    uni.navigateTo({
        url: `/ai_modules/sph/pages/task_detail/task_detail?task_id=${id}`,
    });
};
</script>

<style scoped></style>
