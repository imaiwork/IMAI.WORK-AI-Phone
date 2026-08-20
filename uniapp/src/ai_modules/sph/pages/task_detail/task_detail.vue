<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]" v-if="loading">
        <view class="flex items-center px-4 h-[88rpx] mt-[44rpx]">
            <view class="skeleton w-[48rpx] h-[48rpx] rounded-full mr-[24rpx]" />
            <view class="skeleton h-[40rpx] w-[180rpx] rounded-full" />
        </view>

        <view class="px-4 pt-[16rpx]">
            <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)] p-[28rpx]">
                <view class="flex items-center justify-between mb-[24rpx]">
                    <view class="skeleton h-[40rpx] w-[200rpx] rounded-full" />
                    <view class="skeleton h-[48rpx] w-[120rpx] rounded-full" />
                </view>
                <view class="space-y-[20rpx]">
                    <view v-for="i in 3" :key="i" class="flex items-center justify-between">
                        <view class="skeleton h-[32rpx] w-[140rpx] rounded-full" />
                        <view class="skeleton h-[32rpx] w-[160rpx] rounded-full" />
                    </view>
                </view>
                <view class="skeleton h-[72rpx] w-full rounded-[20rpx] mt-[28rpx]" />
            </view>

            <view class="flex items-center justify-between mt-[28rpx] mb-[16rpx]">
                <view class="flex items-center gap-[10rpx]">
                    <view class="skeleton w-[6rpx] h-[32rpx] rounded-full" />
                    <view class="skeleton h-[36rpx] w-[140rpx] rounded-full" />
                </view>
                <view class="skeleton h-[52rpx] w-[160rpx] rounded-full" />
            </view>

            <view class="flex bg-[#F0F2F5] rounded-[20rpx] p-[6rpx] gap-[6rpx]">
                <view v-for="i in 4" :key="i" class="flex-1 h-[68rpx] rounded-[16rpx] skeleton" />
            </view>
        </view>

        <view class="flex-1 px-4 mt-[24rpx]">
            <view class="flex flex-col gap-[16rpx]">
                <view
                    v-for="i in 4"
                    :key="i"
                    class="bg-white rounded-[24rpx] p-[28rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                    <view class="flex items-center justify-between mb-[20rpx]">
                        <view class="skeleton h-[36rpx] w-[180rpx] rounded-full" />
                        <view class="skeleton h-[44rpx] w-[100rpx] rounded-full" />
                    </view>
                    <view class="space-y-[16rpx]">
                        <view class="skeleton h-[28rpx] w-full rounded-full" />
                        <view class="skeleton h-[28rpx] w-[70%] rounded-full" />
                    </view>
                </view>
            </view>
        </view>
    </view>

    <view class="flex flex-col h-screen bg-[#F7F9FC]" v-else>
        <u-navbar
            title="获客详情"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: 'transparent' }"
            :custom-back="handleBack" />
        <view class="px-4 pt-[16rpx]">
            <view>
                <view
                    class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                    <task-card :item="detail" @changeStatus="handleChangeStatus" @edit="handleEditTask" />
                </view>
            </view>
            <view class="flex items-center justify-between mt-[28rpx] mb-[16rpx]">
                <view class="flex items-center gap-[10rpx]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[30rpx] font-extrabold text-[#0D1117]">获客线索</text>
                </view>
                <navigator
                    :url="`/ai_modules/sph/pages/clue_list/clue_list?task_id=${detail.id}`"
                    hover-class="none"
                    class="flex items-center gap-[6rpx] bg-[#EBF2FF] px-[20rpx] py-[10rpx] rounded-full">
                    <text class="text-xs font-semibold text-primary">查看线索词</text>
                    <u-icon name="arrow-right" size="18" color="#0065fb" />
                </navigator>
            </view>
            <view>
                <view class="flex bg-[#F0F2F5] rounded-[20rpx] p-[6rpx] gap-[6rpx]">
                    <view
                        v-for="(tab, index) in tabList"
                        :key="index"
                        class="flex-1 h-[68rpx] rounded-[16rpx] flex items-center justify-center text-xs font-semibold transition-all duration-200"
                        :class="
                            currentTab === index
                                ? 'bg-white text-primary shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                : 'text-[#9CA3AF]'
                        "
                        @click="handleTabChange(index)">
                        {{ tab.name }}
                    </view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList"
                @onRefresh="handleRefresh">
                <view class="flex flex-col gap-[16rpx]">
                    <view
                        v-for="(item, index) in dataLists"
                        :key="index"
                        class="bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <clue-card :item="item" />
                    </view>
                </view>
                <template #empty>
                    <view class="flex flex-col items-center justify-center py-[80rpx]">
                        <view class="relative mb-[40rpx]">
                            <view
                                class="w-[240rpx] h-[240rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                <view
                                    class="w-[170rpx] h-[170rpx] rounded-full bg-[#DBEAFE] flex items-center justify-center">
                                    <view
                                        class="w-[100rpx] h-[100rpx] rounded-[28rpx] flex items-center justify-center shadow-[0_6rpx_20rpx_rgba(0,101,251,0.25)]"
                                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                        <u-icon name="search" color="#fff" size="38" />
                                    </view>
                                </view>
                            </view>
                        </view>
                        <text class="text-[30rpx] font-extrabold text-[#0D1117] mb-[12rpx]">暂无线索数据</text>
                        <text class="text-[#9CA3AF] text-center">任务执行后线索将自动展示在这里</text>
                    </view>
                </template>
            </z-paging>
        </view>

        <view
            v-if="detail.auto_type === 0"
            class="z-[8888] fixed bottom-0 left-0 right-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))]">
            <view
                class="h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] border border-solid border-[#FECACA] bg-[#FFF2F2]"
                @click="handleDeleteClue">
                <u-icon name="trash" size="22" color="#EF4444" />
                <text class="text-[28rpx] font-bold text-[#EF4444]">删除线索记录</text>
            </view>
        </view>
    </view>

    <task-edit v-if="showEditPopup" ref="taskEditRef" @close="showEditPopup = false" @success="handleRefresh()" />
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

<style scoped lang="scss">
.skeleton {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 400% 100%;
    animation: skeleton-shimmer 1.5s ease-in-out infinite;
}
@keyframes skeleton-shimmer {
    0% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}
</style>
