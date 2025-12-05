<template>
    <view class="h-screen flex flex-col bg-[#F6F6F6]">
        <u-navbar title="查看线索词" :background="{ backgroundColor: '#f6f6f6' }" />
        <view class="px-[32rpx] mt-[32rpx] font-bold"> #{{ keywordVal }} </view>
        <view class="grow min-h-0 mt-[32rpx]">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-[32rpx] pb-[48rpx] flex flex-col gap-[24rpx]">
                    <view class="" v-for="(item, index) in dataLists" :key="index">
                        <clue-card :item="item" />
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
import { getTaskClue } from "@/api/sph";
import ClueCard from "../../components/clue-card/clue-card.vue";

const taskId = ref("");
const keywordVal = ref("");

const dataLists = ref([]);
const pagingRef = ref();

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getTaskClue({
            task_id: taskId.value,
            exec_keyword: keywordVal.value,
            page_no,
            page_size,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

onLoad(({ task_id, keyword }: any) => {
    taskId.value = task_id;
    keywordVal.value = keyword;
});
</script>

<style scoped></style>
