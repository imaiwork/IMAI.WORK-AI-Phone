<template>
    <view class="record-page">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }"
            title="会议记录"
            title-bold>
        </u-navbar>

        <view class="grow min-h-0 mt-4 mb-4">
            <z-paging v-model="lists" ref="pagingRef" :fixed="false" :safe-area-inset-bottom="true" @query="queryList">
                <view class="flex flex-col gap-3 mx-4">
                    <view :index="index" v-for="(item, index) in lists" :key="index">
                        <record-card :item="item" @delete-success="reload" @again-success="reload"></record-card>
                    </view>
                </view>
                <template #empty>
                    <empty title="暂无会议记录" />
                </template>
            </z-paging>
        </view>
    </view>
</template>

<script setup lang="ts">
import Empty from "@/ai_modules/meeting_minutes/components/empty/empty.vue";
import RecordCard from "@/ai_modules/meeting_minutes/components/record-card/record-card.vue";
import { meetingMinutesLists } from "@/api/meeting_minutes";

const lists = ref<any[]>([]);

const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await meetingMinutesLists({
            page_no,
            page_size,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const reload = async () => {
    pagingRef.value?.reload();
};
</script>

<style scoped lang="scss">
.record-page {
    background-image: url("@/ai_modules/meeting_minutes/static/images/common/mask_bg.png");
    background-size: 100% 100%;
    background-repeat: no-repeat;
    background-position: center;
    @apply h-screen flex flex-col;
}
</style>
