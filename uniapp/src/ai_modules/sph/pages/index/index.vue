<template>
    <view class="min-h-screen flex flex-col pb-[100rpx]">
        <view class="fixed top-0 left-0 right-0 z-50" :style="{ backgroundColor: headerStyle.background }">
            <u-navbar
                :is-fixed="false"
                :border-bottom="false"
                :background="{
                    background: headerStyle.background,
                }"
                title="AI自动获客"
                :title-color="headerStyle.titleColor"
                title-bold
                :back-icon-color="headerStyle.iconColor">
            </u-navbar>
        </view>
        <view class="relative w-full h-[804rpx] flex-shrink-0">
            <image src="@/ai_modules/sph/static/images/home/bg.png" class="w-full h-full"></image>
            <view class="px-[56rpx] absolute top-[380rpx] left-0 right-0">
                <view class="text-white text-[40rpx] font-bold">AI极速自动获客</view>
                <view class="text-[22rpx] text-[#ffffff80] mt-2">
                    您无需人工干预，即可实现线索自动化获取。系统会在视频号内容中智能筛选目标，并通过私信引导用户进入后续互动，实现从“曝光”到“深度交流”的全流程自动化。这样既节省人力，又能显著提升转化效率，帮助您高效触达并锁定潜在客户群体。
                </view>
            </view>
        </view>
        <view
            class="grow min-h-0 relative z-10 bg-[#F6F6F6] rounded-tl-[48rpx] rounded-tr-[48rpx] -mt-[110rpx] flex flex-col">
            <view class="px-[32rpx] -mt-[50rpx]">
                <view class="flex gap-x-[40rpx]">
                    <view
                        class="h-[161rpx] w-full rounded-lg overflow-hidden relative"
                        @click="handleTabClick(TabKey.VIDEO)">
                        <image
                            src="@/ai_modules/sph/static/images/home/tab_video.png"
                            class="w-full h-full"
                            mode="aspectFill"></image>
                        <view class="absolute bottom-2 left-3 rounded-full bg-white px-3 py-1 text-xs text-[#7283FA]">
                            立即创建
                        </view>
                    </view>
                    <view
                        class="h-[161rpx] w-full rounded-lg overflow-hidden relative"
                        @click="handleTabClick(TabKey.ACCOUNT)">
                        <image
                            src="@/ai_modules/sph/static/images/home/tab_account.png"
                            class="w-full h-full"
                            mode="aspectFill"></image>
                        <view class="absolute bottom-2 left-3 rounded-full bg-white px-3 py-1 text-xs text-[#7283FA]">
                            立即创建
                        </view>
                    </view>
                </view>
            </view>
            <view class="flex justify-between items-center px-[32rpx] mb-[32rpx] mt-[48rpx]">
                <view class="">任务列表</view>
                <navigator class="text-[#0000004d]" hover-class="none" url="/ai_modules/sph/pages/task_list/task_list"
                    >查看全部</navigator
                >
            </view>
            <view class="grow min-h-0">
                <scroll-view class="h-full" scroll-y>
                    <view class="flex flex-col gap-[24rpx] px-[32rpx]" v-if="pager.lists.length">
                        <view v-for="(item, index) in pager.lists" :key="index">
                            <task-card
                                :item="item"
                                @changeStatus="handleChangeStatus"
                                @detail="handleDetail"
                                @edit="handleEditTask" />
                        </view>
                    </view>
                    <view class="flex justify-center items-center h-full" v-else>
                        <empty />
                    </view>
                </scroll-view>
            </view>
        </view>
    </view>
    <task-edit
        v-if="showEditPopup"
        ref="taskEditRef"
        @close="showEditPopup = false"
        @success="checkTaskStatus()"></task-edit>
</template>

<script setup lang="ts">
import { getTaskList } from "@/api/sph";
import { usePaging } from "@/hooks/usePaging";
import TaskCard from "@/ai_modules/sph/components/task-card/task-card.vue";
import TaskEdit from "@/ai_modules/sph/components/task-edit/task-edit.vue";
enum TabKey {
    VIDEO = 0,
    ACCOUNT = 1,
}

const headerStyle = reactive({
    iconColor: "#ffffff",
    titleColor: "#ffffff",
    background: "transparent",
});

const { pager, getLists } = usePaging({
    fetchFun: getTaskList,
    params: { page_size: 20 },
});

const handleTabClick = (key: TabKey) => {
    uni.navigateTo({
        url: `/ai_modules/sph/pages/create_task/create_task?type=${key}`,
    });
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

const handleChangeStatus = async (item: any) => {
    clearTimeout(timer);
    checkTaskStatus();
};

const handleDetail = (id: string) => {
    uni.navigateTo({
        url: `/ai_modules/sph/pages/task_detail/task_detail?task_id=${id}`,
    });
};

/**
 * 检查任务列表是不是有在进行中的数据，如果有则轮询getLists，直到数据都是已完成为止
 */
let timer: any;
const checkTaskStatus = async () => {
    await getLists();
    const { lists } = pager;
    const isRunning = lists.some((item: any) => item.status == 1 || item.status == 0);
    if (isRunning) {
        timer = setTimeout(checkTaskStatus, 3000);
    }
};

onShow(checkTaskStatus);

onUnload(() => {
    clearTimeout(timer);
});

onPageScroll((e) => {
    const { scrollTop } = e;
    if (scrollTop > 200) {
        headerStyle.iconColor = "#000000";
        headerStyle.titleColor = "#000000";
        headerStyle.background = "#ffffff";
    } else {
        headerStyle.iconColor = "#ffffff";
        headerStyle.titleColor = "#ffffff";
        headerStyle.background = "transparent";
    }
});
</script>

<style scoped lang="scss">
.tab-item {
    @apply flex-1 rounded-full h-[161rpx] flex items-center justify-center font-bold bg-[#00000008];
}
</style>
