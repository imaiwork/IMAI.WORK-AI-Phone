<template>
    <view class="min-h-screen device-bg pb-[100rpx]">
        <u-navbar
            title="设备详情"
            title-bold
            :border-bottom="false"
            :background="{
                background: '#DDEAFB',
            }">
        </u-navbar>
        <view class="px-4">
            <view class="py-[58rpx]">
                <view class="flex items-center justify-center gap-x-2">
                    <view
                        class="flex-shrink-0 px-[20rpx] py-[6rpx] rounded-[12rpx] font-bold"
                        :class="getDeviceStatusStyle">
                        {{ detail.status === 0 ? "离线" : detail.status === 1 ? "在线" : "工作中" }}
                    </view>
                    <view class="text-[40rpx] font-bold text-center line-clamp-1">{{ detail.device_name || "-" }}</view>
                    <view class="flex-shrink-0" @click="handleEditDevice">
                        <image src="/static/images/icons/edit_pen.svg" class="w-[40rpx] h-[40rpx]"></image>
                    </view>
                </view>
                <view class="text-xs text-[#0000004d] text-center mt-[14rpx]"> 设备码：{{ detail.device_code }} </view>
                <view class="text-xs text-[#0000004d] text-center mt-[14rpx]">
                    绑定时间：{{ detail.create_time }}
                </view>
            </view>
            <view class="bg-white rounded-[20rpx] px-[32rpx]">
                <view class="h-[116rpx] flex items-center justify-between gap-x-2">
                    <view class="text-[30rpx] font-bold">平台账号激活</view>
                    <view class="flex-1 flex items-center justify-end gap-x-[7rpx]">
                        <navigator
                            v-for="(account, index) in detail.accounts"
                            :key="index"
                            :url="`/ai_modules/device/pages/platform_detail/platform_detail?device_code=${deviceCode}&app_type=${account.type}`"
                            hover-class="none">
                            <image
                                :src="platformLogo[account.type as keyof typeof platformLogo].activeIcon"
                                class="w-[40rpx] h-[40rpx]"></image>
                        </navigator>
                    </view>
                    <navigator
                        :class="{ 'flex-1 flex justify-end': detail.accounts?.length == 0 }"
                        :url="`/ai_modules/device/pages/platform_detail/platform_detail?device_code=${deviceCode}`"
                        hover-class="none">
                        <u-icon name="arrow-right" color="#0000004d"></u-icon>
                    </navigator>
                </view>
                <view class="h-[2rpx] bg-[#00000008]"></view>
                <view class="h-[116rpx] flex items-center justify-between gap-x-2" @click="handleUnbindDevice">
                    <view class="text-[30rpx] font-bold">解除设备绑定</view>
                    <u-icon name="arrow-right" color="#0000004d"></u-icon>
                </view>
            </view>
            <view class="bg-white rounded-[20rpx] px-[32rpx] mt-[20rpx] pb-5">
                <calendar-simple v-model="selectedDate" @change="reset" />
                <view class="mt-[50rpx]">
                    <view class="text-[30rpx] font-bold">今日任务</view>
                    <view class="mt-[30rpx]">
                        <semi-circle-progress :progress="getProgress">
                            <view class="text-primary font-bold text-[56rpx]"> {{ getProgress }}% </view>
                        </semi-circle-progress>
                    </view>
                    <view class="grid grid-cols-5 gap-x-[20rpx] mt-4">
                        <view
                            class="flex flex-col items-center justify-center"
                            v-for="item in taskStatistics"
                            :key="item.key">
                            <text class="text-[40rpx] font-bold">{{ formatNumberToWanOrYi(item.value) }}</text>
                            <text class="text-xs font-bold text-[#00000066] mt-[6rpx]">{{ item.title }}</text>
                        </view>
                    </view>
                </view>
            </view>
            <view class="mt-[50rpx]">
                <view class="text-[30rpx] font-bold mb-[26rpx]"> 任务列表（{{ taskList.length }}） </view>
                <task-list
                    v-if="taskList.length > 0"
                    :list="taskList"
                    @handle-detail="handleDetail"
                    @update-name="handleUpdateTaskName" />
                <view v-else>
                    <empty text="暂无任务" />
                </view>
            </view>
        </view>
    </view>
    <u-popup v-model="showEditDevicePopup" mode="center" width="90%" :border-radius="20">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-bold text-center mt-2">编辑设备</view>
            <view class="mt-[48rpx] bg-[#F3F3F3] px-4 py-2 rounded-[16rpx]">
                <u-input
                    v-model="editDeviceName"
                    placeholder="请输入设备名称"
                    maxlength="30"
                    placeholder-style="color: #0000004d; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold text-[#000000b3]"
                    @click="showEditDevicePopup = false">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-bold text-white"
                    @click="handleEditDeviceConfirm"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
    <task-detail-pop ref="taskDetailRef" v-model="showDetailPop" @delete="reset" />
    <task-edit-name-pop ref="taskEditNameRef" v-model="showEditNamePop" @success="handleConfirmUpdateTaskName" />
</template>

<script setup lang="ts">
import {
    getDeviceDetail,
    getDeviceTaskList,
    updateDevice,
    unbindDevice,
    getDeviceTaskCalendarStatistics,
} from "@/api/device";
import { formatNumberToWanOrYi } from "@/utils/util";
import CalendarSimple from "@/ai_modules/device/components/calendar-simple/calendar-simple.vue";
import TaskList from "@/ai_modules/device/components/task-list/task-list.vue";
import SemiCircleProgress from "@/ai_modules/device/components/semi-circle-progress/semi-circle-progress.vue";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import TaskDetailPop from "@/ai_modules/device/components/task-detail-pop/task-detail-pop.vue";
import TaskEditNamePop from "@/ai_modules/device/components/task-edit-name/task-edit-name.vue";

const deviceCode = ref("");
const detail = ref<any>({});

const selectedDate = ref(uni.$u.timeFormat(new Date(), "yyyy-mm-dd"));

const showEditDevicePopup = ref(false);
const editDeviceName = ref("");

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
        title: "已完成",
        value: 0,
        key: "completed",
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
        title: "已失败",
        value: 0,
        key: "failure",
    },
]);

const taskDetailRef = shallowRef<any>(null);
const taskEditNameRef = shallowRef<any>(null);
const showDetailPop = ref<boolean>(false);
const showEditNamePop = ref<boolean>(false);

const { platformLogo } = useDevice();

// 获取设备状态样式
const getDeviceStatusStyle = computed(() => {
    switch (detail.value.status) {
        case 0:
            return "text-[#FF2442] bg-[rgba(255,36,66,0.1)]";
        case 1:
            return "text-[#00C08E] bg-[rgba(0,192,142,0.1)]";
        case 2:
        default:
            return "text-primary bg-primary-light-9";
    }
});

const getProgress = computed(() => {
    const total = taskStatistics.value.find((item) => item.key === "all")?.value || 0;
    const completed = taskStatistics.value.find((item) => item.key === "completed")?.value || 0;
    const error = taskStatistics.value.find((item) => item.key === "failure")?.value || 0;
    if (total == 0) return 0;
    return Math.round(((completed + error) / total) * 100);
});

const handleEditDevice = () => {
    showEditDevicePopup.value = true;
    editDeviceName.value = detail.value.device_name;
};

const handleEditDeviceConfirm = async () => {
    uni.showLoading({
        title: "修改中...",
        mask: true,
    });
    try {
        await updateDevice({
            device_code: deviceCode.value,
            device_name: editDeviceName.value,
        });
        uni.hideLoading();
        uni.showToast({
            title: "修改成功",
            icon: "none",
            duration: 3000,
        });
        detail.value.device_name = editDeviceName.value;
        showEditDevicePopup.value = false;
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

const handleUnbindDevice = () => {
    uni.showModal({
        title: "解除设备绑定",
        content: "解除设备绑定后，设备将无法使用",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "解除中...",
                    mask: true,
                });
                try {
                    await unbindDevice({
                        device_code: deviceCode.value,
                    });
                    uni.hideLoading();
                    uni.showToast({
                        title: "解除成功",
                        icon: "none",
                        duration: 3000,
                    });
                    uni.$u.route({
                        url: "/pages/phone/phone",
                        type: "reLaunch",
                    });
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

const reset = () => {
    // 重置分页
    taskQuery.page_no = 1;
    // 重置任务列表
    taskList.value = [];
    isFinished.value = false;
    isLoading.value = false;
    getTaskList();
    getStatistics();
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
    const data = await getDeviceDetail({
        device_code: deviceCode.value,
    });
    detail.value = data;
};

const init = async () => {
    await getDetail();
    reset();
};

onShow(() => {
    init();
});

onLoad((options: any) => {
    deviceCode.value = options.device_code;
});

onReachBottom(() => {
    taskLoad();
});
</script>

<style scoped></style>
