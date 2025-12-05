<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title="任务日历"
            title-bold
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="bg-white px-2">
            <calendar-simple v-model="taskDay" @change="handleSelectDate" />
        </view>
        <view class="px-[26rpx] mt-[24rpx]">
            <view class="bg-white rounded-[20rpx] px-[34rpx] py-[20rpx] flex justify-between gap-x-4">
                <view>
                    <view class="font-bold text-[30rpx]">今日任务</view>
                    <view class="mt-[48rpx]">
                        <text class="text-[34rpx] font-bold">{{ statistics.completed + statistics.failure }}</text
                        ><text class="text-[#0000004d]"> / {{ statistics.all }}</text>
                    </view>
                    <view class="mt-5">
                        <navigator
                            url="/ai_modules/device/pages/choose_task_type/choose_task_type"
                            hover-class="none"
                            class="w-[200rpx] h-[70rpx] rounded-full bg-black text-white font-bold text-[28rpx] flex items-center justify-center gap-x-1">
                            <u-icon name="plus" size="24"></u-icon>
                            新增任务
                        </navigator>
                    </view>
                </view>
                <view class="">
                    <view class="mt-4">
                        <semi-circle-progress :progress="getProgress" :size="120" :strokeWidth="8">
                            <view class="text-[34rpx] font-bold text-primary">{{ getProgress }}%</view>
                        </semi-circle-progress>
                    </view>
                    <view class="grid grid-cols-2 gap-3 mt-5">
                        <view class="flex items-center gap-x-1 text-[20rpx]">
                            <text class="w-[6rpx] h-[6rpx] rounded-full bg-[#0000004d]"></text>
                            <text class="text-[18rpx] text-[#0000004d]">总任务:</text>
                            <text class="text-[20rpx]">{{ formatNumberToWanOrYi(statistics.all) }}</text>
                        </view>
                        <view class="flex items-center gap-x-1 text-[20rpx]">
                            <text class="w-[6rpx] h-[6rpx] rounded-full bg-[#0000004d]"></text>
                            <text class="text-[18rpx] text-[#0000004d]">已完成:</text>
                            <text class="text-[20rpx]">{{ formatNumberToWanOrYi(statistics.completed) }}</text>
                        </view>
                        <view class="flex items-center gap-x-1 text-[20rpx]">
                            <text class="w-[6rpx] h-[6rpx] rounded-full bg-[#0000004d]"></text>
                            <text class="text-[18rpx] text-[#0000004d]">待开始:</text>
                            <text class="text-[20rpx]">{{ formatNumberToWanOrYi(statistics.waiting) }}</text>
                        </view>
                        <view class="flex items-center gap-x-1 text-[20rpx]">
                            <text class="w-[6rpx] h-[6rpx] rounded-full bg-[#0000004d]"></text>
                            <text class="text-[18rpx] text-[#0000004d]">已失败:</text>
                            <text class="text-[20rpx]">{{ formatNumberToWanOrYi(statistics.failure) }}</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <view class="mt-5 px-[26rpx] text-[30rpx] font-bold">任务列表({{ taskList.length }})</view>
        <view class="grow min-h-0 px-[26rpx] mt-5">
            <z-paging ref="pagingRef" v-model="taskList" :fixed="false" @query="queryTaskList">
                <view>
                    <task-list :list="taskList" @handle-detail="handleDetail" @update-name="handleUpdateTaskName" />
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
    </view>
    <task-detail-pop ref="taskDetailRef" v-model="showDetailPop" @delete="init" />
    <task-edit-name-pop ref="taskEditNameRef" v-model="showEditNamePop" @success="handleConfirmUpdateTaskName" />
</template>

<script setup lang="ts">
import { getDeviceTaskCalendarStatistics, getDeviceTaskCalendarList } from "@/api/device";
import { formatNumberToWanOrYi } from "@/utils/util";
import CalendarSimple from "@/ai_modules/device/components/calendar-simple/calendar-simple.vue";
import SemiCircleProgress from "@/ai_modules/device/components/semi-circle-progress/semi-circle-progress.vue";
import TaskList from "@/ai_modules/device/components/task-list/task-list.vue";
import TaskDetailPop from "@/ai_modules/device/components/task-detail-pop/task-detail-pop.vue";
import TaskEditNamePop from "@/ai_modules/device/components/task-edit-name/task-edit-name.vue";

const statistics = reactive<any>({
    all: 0,
    all_completed: 0,
    completed: 0,
    failure: 0,
    interrupt: 0,
    execution: 0,
    waiting: 0,
});

const taskDay = ref(uni.$u.timeFormat(new Date(), "yyyy-mm-dd"));

const pagingRef = ref<any>(null);
const taskList = ref<any[]>([]);

const taskDetailRef = shallowRef<any>(null);
const taskEditNameRef = shallowRef<any>(null);
const showDetailPop = ref<boolean>(false);
const showEditNamePop = ref<boolean>(false);

const getProgress = computed(() => {
    if (statistics.all === 0) return 0;
    return Math.round(((statistics.completed + statistics.failure) / statistics.all) * 100);
});

const getStatistics = async () => {
    const data = await getDeviceTaskCalendarStatistics({
        day: taskDay.value,
    });
    statistics.all = data.all;
    statistics.all_completed = data.all_completed;
    statistics.completed = data.completed;
    statistics.failure = data.failure;
    statistics.interrupt = data.interrupt;
    statistics.execution = data.execution;
    statistics.waiting = data.waiting;
};

const queryTaskList = async (page: number, pageSize: number) => {
    try {
        const { lists } = await getDeviceTaskCalendarList({
            page_no: page,
            page_size: pageSize,
            day: taskDay.value,
        });
        pagingRef.value.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const reload = () => {
    pagingRef.value?.reload();
};

const handleSelectDate = (date: string) => {
    init();
};

const handleDetail = async (row: any) => {
    showDetailPop.value = true;
    await nextTick();
    taskDetailRef.value?.getDetail(row);
};

const handleUpdateTaskName = async (data: any) => {
    showEditNamePop.value = true;
    await nextTick();
    taskEditNameRef.value?.setFormData(data);
};

const handleConfirmUpdateTaskName = (data: any) => {
    taskList.value.forEach((item) => {
        if (item.id == data.id) {
            item.name = data.name;
        }
    });
};

const init = () => {
    getStatistics();
    reload();
};

onShow(() => {
    init();
});
</script>

<style scoped></style>
