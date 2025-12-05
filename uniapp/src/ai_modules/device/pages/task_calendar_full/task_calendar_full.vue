<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title="完整日历"
            title-bold
            :border-bottom="false"
            :background="{
                background: '#DDEAFB',
            }">
        </u-navbar>
        <view class="bg-white">
            <calendar v-model="selectedDate" @select-date="handleSelectDate" />
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
    <task-detail-pop ref="taskDetailRef" v-model="showDetailPop" @delete="reload" />
    <task-edit-name-pop ref="taskEditNameRef" v-model="showEditNamePop" @success="handleConfirmUpdateTaskName" />
</template>

<script setup lang="ts">
import { getDeviceTaskCalendarList } from "@/api/device";
import Calendar from "@/ai_modules/device/components/calendar/calendar.vue";
import TaskList from "@/ai_modules/device/components/task-list/task-list.vue";
import TaskDetailPop from "@/ai_modules/device/components/task-detail-pop/task-detail-pop.vue";
import TaskEditNamePop from "@/ai_modules/device/components/task-edit-name/task-edit-name.vue";

const pagingRef = ref<any>(null);
const taskList = ref<any[]>([]);

const selectedDate = ref<string>("");

const taskDetailRef = shallowRef<any>(null);
const taskEditNameRef = shallowRef<any>(null);
const showDetailPop = ref<boolean>(false);
const showEditNamePop = ref<boolean>(false);

const queryTaskList = async (page: number, pageSize: number) => {
    try {
        const { lists } = await getDeviceTaskCalendarList({
            page_no: page,
            page_size: pageSize,
            day: selectedDate.value,
        });
        pagingRef.value.complete(lists);
    } catch (error) {
        pagingRef.value.complete([]);
    }
};

const handleSelectDate = (date: any) => {
    selectedDate.value = date;
    pagingRef.value?.reload();
};

const reload = () => {
    pagingRef.value?.reload();
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
</script>

<style scoped></style>
