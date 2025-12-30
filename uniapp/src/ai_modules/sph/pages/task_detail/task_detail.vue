<template>
    <view class="h-screen flex flex-col bg-[#F6F6F6]" v-if="!loading">
        <u-navbar title="获客详情" :background="{ backgroundColor: '#f6f6f6' }" :custom-back="handleBack" />
        <view class="flex-shrink-0 px-[32rpx] mt-[32rpx]">
            <task-card :item="detail" @changeStatus="handleChangeStatus" @edit="handleEditTask"></task-card>
        </view>
        <view class="flex-shrink-0 flex items-center justify-between px-[32rpx] my-[32rpx] text-[26rpx]">
            <view class="text-[#000000cc] font-bold">获客线索</view>
            <navigator
                :url="`/ai_modules/sph/pages/clue_list/clue_list?task_id=${detail.id}`"
                hover-class="none"
                class="text-primary font-bold"
                >查看线索词</navigator
            >
        </view>
        <view class="px-[32rpx]">
            <u-tabs
                :list="tabList"
                :current="currentTab"
                bg-color="transparent"
                font-size="26rpx"
                @change="handleTabChange"></u-tabs>
        </view>
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList"
                @onRefresh="handleRefresh">
                <view class="px-[32rpx] pb-[48rpx] flex flex-col gap-[24rpx]">
                    <view class="" v-for="(item, index) in dataLists" :key="index">
                        <clue-card :item="item"></clue-card>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
        <view class="fixed bottom-[5vh] left-0 flex justify-center w-full z-[888]">
            <view
                class="w-[280rpx] h-[80rpx] bg-[#FF4D4F] text-white text-[26rpx] font-bold rounded-[12rpx] flex items-center justify-center"
                @click="handleDeleteClue"
                >删除线索记录</view
            >
        </view>
    </view>
    <task-edit
        v-if="showEditPopup"
        ref="taskEditRef"
        @close="showEditPopup = false"
        @success="handleRefresh()"></task-edit>
</template>

<script setup lang="ts">
import { getTaskDetail, getTaskClue, deleteTask } from "@/api/sph";
import TaskCard from "@/ai_modules/sph/components/task-card/task-card.vue";
import ClueCard from "@/ai_modules/sph/components/clue-card/clue-card.vue";
import TaskEdit from "@/ai_modules/sph/components/task-edit/task-edit.vue";
const detail = ref<any>({});
const loading = ref(true);
const tabList = ref([
    {
        name: "全部",
        value: null,
    },
    {
        name: "有效线索",
        value: 1,
    },
    {
        name: "内含有效线索",
        value: 3,
    },
    {
        name: "无效线索",
        value: 2,
    },
]);
const currentTab = ref(0);

const isCreate = ref(false);

const dataLists = ref([]);
const pagingRef = ref();

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getTaskClue({
            task_id: detail.value.id,
            page_no,
            page_size,
            status: tabList.value[currentTab.value].value || "",
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleTabChange = (index: number) => {
    currentTab.value = index;
    pagingRef.value?.reload();
};

const handleChangeStatus = async (item: any) => {
    await getDetail(detail.value.id);
    pagingRef.value?.reload();
};

const showEditPopup = ref(false);
const taskEditRef = shallowRef<InstanceType<typeof TaskEdit>>();

const handleEditTask = async (data: any) => {
    showEditPopup.value = true;
    await nextTick();
    setTimeout(() => {
        taskEditRef.value?.open();
        taskEditRef.value?.setFormData(data);
    }, 100);
};

const handleDeleteClue = async () => {
    uni.showModal({
        title: "提示",
        content: "确定删除所有线索记录吗？",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "删除中...",
                    mask: true,
                });
                try {
                    await deleteTask({ id: detail.value.id });
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除成功",
                        icon: "none",
                        duration: 3000,
                    });
                    uni.navigateBack();
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({
                        title: error,
                        icon: "none",
                        duration: 3000,
                    });
                }
            }
        },
    });
};

const handleBack = () => {
    if (isCreate.value) {
        uni.$u.route({
            url: "/ai_modules/sph/pages/index/index",
            type: "reLaunch",
        });
    } else {
        uni.navigateBack();
    }
};

const handleRefresh = () => {
    clearTimeout(timer);
    checkTaskStatus(detail.value.id);
};

const getDetail = async (id: string) => {
    try {
        const data = await getTaskDetail({ id });
        detail.value = data;
        await nextTick();
    } finally {
        loading.value = false;
    }
};

let timer: any;
const checkTaskStatus = async (id: string) => {
    await getDetail(id);
    const isRunning = detail.value.status == 1 || detail.value.status == 0;
    if (isRunning) {
        timer = setTimeout(() => {
            checkTaskStatus(id);
        }, 3000);
    }
};

onLoad(({ task_id, is_create }: any) => {
    isCreate.value = is_create == 1;
    clearTimeout(timer);
    checkTaskStatus(task_id);
});

onHide(() => {
    clearTimeout(timer);
});

onBeforeUnmount(() => {
    clearTimeout(timer);
});
</script>

<style scoped></style>
