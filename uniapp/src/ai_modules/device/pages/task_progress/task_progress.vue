<template>
    <view class="px-[26rpx] pt-4" v-if="!loading">
        <view class="bg-white rounded-[30rpx] p-[30rpx] flex items-center gap-x-2">
            <view class="w-[80rpx] h-[80rpx] rounded-[20rpx] flex items-center justify-center bg-[#F6F6F6]">
                <image src="@/ai_modules/device/static/icons/device.svg" class="w-[40rpx] h-[40rpx]" />
            </view>
            <view>
                <view class="text-[34rpx] font-medium line-clamp-1">
                    {{ detail.device_name }}
                </view>
                <view class="text-[22rpx] text-[#000000]/30 font-medium">
                    当前：{{ detail.auto_type === 0 ? "手动任务" : "24h自动" }}
                </view>
            </view>
        </view>
        <view class="mt-[34rpx] bg-white rounded-[30rpx] px-[30rpx] pt-[20rpx] pb-[30rpx]">
            <calendar-simple v-model="selectedDate" @change="reset" />
            <view class="text-[30rpx] font-medium">任务总览</view>
            <view class="grid grid-cols-5 gap-x-[20rpx] mt-4">
                <view class="flex flex-col items-center justify-center" v-for="item in taskStatistics" :key="item.key">
                    <text
                        class="text-[40rpx] font-medium"
                        :class="[
                            item.key == 'failure'
                                ? 'text-[#FF2442]'
                                : item.key == 'completed'
                                ? 'text-primary'
                                : 'text-black',
                        ]"
                        >{{ formatNumberToWanOrYi(item.value) }}</text
                    >
                    <text class="text-xs font-medium text-[#00000066] mt-[6rpx]">{{ item.title }}</text>
                </view>
            </view>
        </view>
        <view class="mt-[30rpx]">
            <view class="mb-[26rpx] flex items-center justify-between">
                <view class="flex items-center gap-x-1">
                    <view class="text-[30rpx] font-medium"> 任务列表（{{ taskList.length }}） </view>
                    <text
                        v-if="detail.auto_type === 1"
                        class="text-[22rpx] text-[#000000]/30 font-medium"
                        @click="handleHowToCharge">
                        <u-icon name="question-circle"></u-icon>
                        如何扣费
                    </text>
                </view>
                <navigator
                    v-if="detail.auto_type === 1"
                    :url="`/ai_modules/device/pages/task_tutorial/task_tutorial?device_code=${deviceCode}&person_type=${detail.persona_info?.persona_type}`"
                    hover-class="none"
                    class="bg-white rounded-[100rpx] px-[22rpx] py-[12rpx] flex items-center gap-x-1">
                    <u-icon name="play-circle" color="#AAAAAA" size="24"></u-icon>
                    <text class="text-[22rpx] text-[#000000]/50 font-medium">演示流程</text>
                </navigator>
            </view>
            <task-list
                v-if="taskList.length > 0"
                :list="taskList"
                @handle-detail="handleDetail"
                @update-name="handleUpdateTaskName" />
            <view class="mt-[10vh]" v-else>
                <empty text="暂无任务，或如果是首次24h任务，需要等待30分钟左右生成才会生成" :size="250" />
            </view>
        </view>
    </view>
    <popup-bottom
        v-model="showFeePopup"
        title="24小时自动任务算力消耗明细"
        custom-class="bg-[#F7F8FA]"
        :is-disabled-touch="true">
        <template #content>
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 py-4 space-y-4">
                    <view
                        v-for="(item, index) in getTaskCostConfig"
                        :key="index"
                        class="bg-white rounded-[24rpx] px-5 py-5 shadow-sm flex justify-between items-center">
                        <view class="flex flex-col gap-[10rpx]">
                            <text class="text-[32rpx] font-bold text-[#1e293b]">{{
                                item.name.replace("(自动化)", "")
                            }}</text>
                            <text class="text-[24rpx] text-[#94a3b8]">单次调用消耗</text>
                        </view>

                        <view class="bg-[#f8fafc] px-4 py-[18rpx] rounded-[16rpx] flex items-baseline gap-1 shrink-0">
                            <text class="text-[40rpx] font-bold text-[#1e293b]">-{{ item.score }}</text>
                            <text class="text-[24rpx] text-[#64748b] ml-1">{{ item.unit }}</text>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
    <task-detail-pop ref="taskDetailRef" v-model="showDetailPop" @delete="reset" />
    <task-edit-name-pop ref="taskEditNameRef" v-model="showEditNamePop" @success="handleConfirmUpdateTaskName" />
</template>

<script setup lang="ts">
import { getDeviceDetail, getDeviceTaskList, getDeviceTaskCalendarStatistics } from "@/api/device";
import { useUserStore } from "@/stores/user";
import { formatNumberToWanOrYi } from "@/utils/util";
import CalendarSimple from "@/ai_modules/device/components/calendar-simple/calendar-simple.vue";
import TaskList from "@/ai_modules/device/components/task-list/task-list.vue";
import TaskDetailPop from "@/ai_modules/device/components/task-detail-pop/task-detail-pop.vue";
import TaskEditNamePop from "@/ai_modules/device/components/task-edit-name/task-edit-name.vue";

const userStore = useUserStore();

const loading = ref<boolean>(true);
const deviceCode = ref<string>("");
const detail = ref<any>({});

const selectedDate = ref(uni.$u.timeFormat(new Date(), "yyyy-mm-dd"));

const taskList = ref<any[]>([]);
const taskTotal = ref(0);
const isLoading = ref(false);
const isFinished = ref(false);
const taskQuery = {
    page_no: 1,
    page_size: 10,
};

// 任务统计
const taskStatistics = ref([
    {
        title: "总任务",
        value: 0,
        key: "all",
    },

    {
        title: "待开始",
        value: 0,
        key: "waiting",
    },
    {
        title: "执行中",
        value: 0,
        key: "execution",
    },
    {
        title: "已完成",
        value: 0,
        key: "completed",
    },
    {
        title: "已失败",
        value: 0,
        key: "failure",
    },
]);

const taskDetailRef = shallowRef<any>(null);
const taskEditNameRef = shallowRef<any>(null);
const showDetailPop = ref<boolean>(false);
const showEditNamePop = ref<boolean>(false);

const getTaskCostConfig = computed(() => {
    const getScene = (scene: string) => {
        return userStore.getTokenByScene(scene);
    };
    return [
        "automation_social_media_released",
        "automation_shut_off_comments",
        "automation_shut_off_obtain",
        "automation_shut_off_private_letter",
        "automation_friends_circle_comments",
        "automation_friends_circle_released",
        "automation_friends_circle_praise",
        "automation_wechat_add_friend",
        "automation_social_media_obtain",
        "automation_social_media_nursing",
        "automation_ocr_local",
        "automation_ocr_img",
    ].map(getScene);
});

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

const reset = () => {
    taskQuery.page_no = 1;
    // 重置任务列表
    taskList.value = [];
    isFinished.value = false;
    isLoading.value = false;
    getTaskList();
    getStatistics();
};

const showFeePopup = ref<boolean>(false);
const handleHowToCharge = () => {
    showFeePopup.value = true;
};

const getStatistics = async () => {
    const data = await getDeviceTaskCalendarStatistics({
        day: selectedDate.value,
        device_code: deviceCode.value,
    });
    taskStatistics.value.forEach((item) => {
        item.value = data[item.key];
    });
};

const getTaskList = async () => {
    isLoading.value = true;
    try {
        const { lists, count } = await getDeviceTaskList({
            device_code: deviceCode.value,
            date: selectedDate.value,
            ...taskQuery,
        });
        isFinished.value = !(lists.length < (taskQuery.page_size || count));
        taskList.value = taskList.value.concat(lists);
        taskTotal.value = count;
    } finally {
        isLoading.value = false;
    }
};

const taskLoad = () => {
    if (isLoading.value || !isFinished.value) return;
    taskQuery.page_no++;
    getTaskList();
};

const getDetail = async () => {
    const res = await getDeviceDetail({
        device_code: deviceCode.value,
    });
    detail.value = res;
};

const init = async () => {
    try {
        await getDetail();
        reset();
    } finally {
        loading.value = false;
    }
};

onReachBottom(() => {
    taskLoad();
});

onLoad((options: any) => {
    deviceCode.value = options.device_code;
    init();
});
</script>

<style scoped></style>
