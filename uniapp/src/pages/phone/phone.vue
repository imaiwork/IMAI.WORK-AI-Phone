<template>
    <view class="h-screen flex flex-col">
        <view class="px-[26rpx] mt-2 flex justify-between items-center gap-x-2">
            <view>
                <view class="text-[40rpx] font-bold">设备管理</view>
                <view class="text-xs text-[#0000004d] font-bold">{{ getDate }}</view>
            </view>
            <view class="flex items-center gap-x-2">
                <view
                    class="flex items-center px-[24rpx] gap-x-2 bg-white h-[70rpx] rounded-[20rpx]"
                    @click="toPage('/ai_modules/device/pages/task_calendar/task_calendar')">
                    <image src="/static/images/icons/calendar.svg" class="w-[24rpx] h-[24rpx]"></image>
                    <text class="font-bold">任务日历</text>
                </view>
                <view
                    class="flex items-center px-[24rpx] gap-x-2 bg-white h-[70rpx] rounded-[20rpx]"
                    @click="toPage('/packages/pages/rpa_code/rpa_code')">
                    <image src="/static/images/icons/device.svg" class="w-[24rpx] h-[24rpx]"></image>
                    <text class="font-bold">新增设备</text>
                </view>
            </view>
        </view>
        <view class="px-4 flex items-center gap-x-4 mt-4">
            <view class="flex items-center gap-x-2" v-for="item in getDeviceStatusCount" :key="item.status">
                <view
                    class="w-[10rpx] h-[10rpx] rounded-full"
                    :style="{ backgroundColor: getDeviceStatusInfo(item.status).textColor }"></view>
                <text class="text-[22rpx] text-[#000000b3] font-bold"
                    >{{ getDeviceStatusInfo(item.status).text }} {{ item.count }}
                </text>
            </view>
        </view>
        <view class="grow min-h-0 mt-5">
            <z-paging
                ref="pagingRef"
                v-model="deviceList"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-[26rpx]">
                    <view class="flex flex-col gap-y-[20rpx]">
                        <view
                            v-for="(item, index) in deviceList"
                            :key="index"
                            class="bg-white rounded-[20rpx] px-5 py-[28rpx] relative"
                            @click="
                                toPage('/ai_modules/device/pages/detail/detail', { device_code: item.device_code })
                            ">
                            <view class="absolute right-2 top-1 p-2" @click.stop>
                                <switch
                                    style="transform: scale(0.7)"
                                    color="#0065FB"
                                    :checked="item.auto_type == 1"
                                    @change="switchTaskMode(item)"></switch>
                            </view>
                            <view class="flex items-center gap-x-[30rpx]">
                                <view
                                    class="w-[80rpx] h-[80rpx] rounded-[20rpx] flex items-center justify-center flex-shrink-0"
                                    :style="{
                                        backgroundColor: getDeviceStatusInfo(item.status).iconBgColor,
                                    }">
                                    <view class="w-[40rpx] h-[40rpx]">
                                        <image
                                            src="/static/images/icons/device_error.svg"
                                            class="w-full h-full"
                                            v-if="item.status == 0"></image>
                                        <image
                                            src="/static/images/icons/device_success.svg"
                                            class="w-full h-full"
                                            v-if="item.status == 1"></image>
                                        <image
                                            src="/static/images/icons/device_primary.svg"
                                            class="w-full h-full"
                                            v-if="item.status == 2"></image>
                                    </view>
                                </view>
                                <view class="mr-4">
                                    <view class="line-clamp-1 text-[30rpx] font-bold break-all"
                                        >{{ item.device_name || "-" }}
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="text-xs font-bold text-[#000000b3]">{{
                                            item.auto_type === 0 ? "手动任务" : "24h自动"
                                        }}</view>
                                        <view class="flex items-center gap-x-1 mt-[4rpx]"
                                            ><view
                                                class="w-[10rpx] h-[10rpx] rounded-full"
                                                :style="{
                                                    backgroundColor: getDeviceStatusInfo(item.status).textColor,
                                                }"></view>
                                            <view
                                                class="font-bold"
                                                :style="{ color: getDeviceStatusInfo(item.status).textColor }"
                                                >{{ getDeviceStatusInfo(item.status).text }}</view
                                            >
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <template v-if="item.auto_type === 0">
                                <view
                                    class="mt-[34rpx] flex items-center border-[0] border-b-[2rpx] border-solid border-[#0000000d] pb-4">
                                    <view class="font-bold">账号：</view>
                                    <view class="flex flex-wrap items-center gap-1" v-if="item.accounts.length > 0">
                                        <image
                                            :src="getDeviceIcon(account.type)"
                                            class="w-[40rpx] h-[40rpx]"
                                            v-for="(account, index) in item.accounts"
                                            :key="index"></image>
                                    </view>
                                    <view class="text-[#ff2442] font-bold" v-else> 未获取社媒账号 </view>
                                </view>
                                <view class="mt-[26rpx]">
                                    <view
                                        class="flex items-center gap-x-2 mt-[24rpx] font-bold"
                                        :style="{ color: getDeviceStatusInfo(item.status).textColor }"
                                        v-if="item.status == TaskStatusEnum.OFFLINE">
                                        设备已离线，请检查
                                    </view>
                                    <view
                                        class="h-[80rpx] flex items-center justify-center gap-x-1 bg-[#ff2442] rounded-[10rpx] text-white font-bold"
                                        v-else-if="item.accounts.length == 0"
                                        @click.stop="
                                            toPage('/ai_modules/device/pages/platform_detail/platform_detail', {
                                                device_code: item.device_code,
                                            })
                                        ">
                                        去获取<u-icon name="arrow-right" color="#ffffff" size="16"></u-icon>
                                    </view>
                                    <view v-else-if="item.status == TaskStatusEnum.IDLE">
                                        <view class="font-bold">当前任务（{{ item.task_count }}）</view>
                                        <view class="flex gap-x-10 items-center" v-if="item.tasks?.length > 0">
                                            <view class="flex-1">
                                                <view
                                                    class="flex items-center gap-x-2 mt-[20rpx]"
                                                    :class="{
                                                        'text-[#FF2442]': [3, 4].includes(task.status),
                                                        'text-primary': [1].includes(task.status),
                                                        'text-[#0000004d]': [0].includes(task.status),
                                                        'text-[#00C08E]': [2].includes(task.status),
                                                    }"
                                                    v-for="task in item.tasks"
                                                    :key="task.id">
                                                    <view class="line-clamp-1 text-xs w-[60%]"
                                                        ><text>•</text> {{ task.task_name }}</view
                                                    >
                                                    <view
                                                        class="text-xs flex-shrink-0"
                                                        :class="{
                                                            'text-[#FF2442]': [3, 4].includes(task.status),
                                                            'text-primary': [1].includes(task.status),
                                                            'text-[#0000004d]': [0].includes(task.status),
                                                            'text-[#00C08E]': [2].includes(task.status),
                                                        }">
                                                        {{ getTaskStatusText(task.status) }}
                                                    </view>
                                                </view>
                                            </view>
                                            <view class="flex-shrink-0">
                                                <circle-progress
                                                    :percent="getTaskStatusPercent(item)"
                                                    borderWidth="10rpx"
                                                    width="136rpx"
                                                    progressColor="#467EF9"
                                                    notProgressColor="#F1F1F1"></circle-progress>
                                            </view>
                                        </view>
                                    </view>
                                    <view
                                        v-if="item.accounts.length > 0"
                                        class="h-[80rpx] flex items-center justify-center gap-x-1 border border-solid border-[#0065fb33] rounded-[10rpx] mt-[30rpx]"
                                        @click.stop="
                                            toPage('/ai_modules/device/pages/choose_task_type/choose_task_type', {
                                                device_code: item.device_code,
                                            })
                                        ">
                                        <u-icon name="plus" color="#0065FB" size="16"></u-icon>
                                        <text class="text-xs text-primary font-bold">创建新任务</text>
                                    </view>
                                </view>
                            </template>
                            <template v-else>
                                <view class="mt-[34rpx]">
                                    <view class="flex items-center justify-between">
                                        <template v-if="item.is_auto_setting === 0">
                                            <view
                                                class="text-[#FF2442] font-bold"
                                                @click.stop="
                                                    toPage(
                                                        '/ai_modules/device/pages/create_auto_task/create_auto_task',
                                                        {
                                                            device_code: item.device_code,
                                                        }
                                                    )
                                                "
                                                >请先完善设置</view
                                            >
                                        </template>
                                        <template v-else-if="item.tasks.length > 0">
                                            <view class="text-primary font-bold">{{ item.tasks[0].task_name }}</view>
                                            <view class="text-primary font-bold">
                                                {{ getTaskStatusPercent(item) }}%
                                            </view>
                                        </template>
                                        <template v-else>
                                            <view class="text-[#0000004d] font-bold">等待执行</view>
                                        </template>
                                    </view>
                                    <view class="mt-[4rpx]">
                                        <u-line-progress
                                            activeColor="#0065FB"
                                            bgColor="#F2F2F2"
                                            height="12"
                                            :percent="getTaskStatusPercent(item)"
                                            :show-percent="false"></u-line-progress>
                                    </view>
                                </view>
                                <view class="flex gap-x-[14rpx] mt-[36rpx]">
                                    <template v-if="item.status === TaskStatusEnum.OFFLINE">
                                        <view
                                            class="flex-1 h-[80rpx] border border-solid border-[#ff244233] rounded-[16rpx] flex items-center justify-center gap-x-2">
                                            <text class="text-[#FF2442] font-bold">设备已离线</text>
                                        </view>
                                    </template>
                                    <template v-else>
                                        <view
                                            v-if="item.is_auto_setting === 0 || item.is_empty === 1"
                                            class="flex-1 h-[80rpx] bg-[#FF2442] rounded-[16rpx] flex items-center justify-center gap-x-2"
                                            @click.stop="
                                                toPage('/ai_modules/device/pages/create_auto_task/create_auto_task', {
                                                    device_code: item.device_code,
                                                })
                                            ">
                                            <text class="text-white font-bold">去设置</text>
                                            <u-icon name="arrow-right" color="#ffffff" size="20"></u-icon>
                                        </view>
                                        <view
                                            v-else
                                            class="flex-1 h-[80rpx] flex items-center justify-center bg-[#0065fb0d] rounded-[16rpx] font-bold"
                                            :style="{
                                                color: getDeviceStatusInfo(item.status).textColor,
                                                backgroundColor: getDeviceStatusInfo(item.status).iconBgColor,
                                            }">
                                            {{ item.status == TaskStatusEnum.WORKING ? "24h自动工作中" : "空闲中" }}
                                        </view>
                                    </template>
                                    <template v-if="[1, 2].includes(item.is_auto_setting)">
                                        <view
                                            class="h-[80rpx] w-[144rpx] border border-solid border-[#0000001a] rounded-[16rpx] flex items-center justify-center gap-x-2"
                                            @click.stop="toPage()">
                                            <image
                                                src="/static/images/icons/statement.svg"
                                                class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-bold">报表</text>
                                        </view>
                                        <view
                                            class="h-[80rpx] w-[144rpx] border border-solid border-[#0000001a] rounded-[16rpx] flex items-center justify-center gap-x-2"
                                            @click.stop="
                                                toPage('/ai_modules/device/pages/auto_task/auto_task', {
                                                    device_code: item.device_code,
                                                })
                                            ">
                                            <image
                                                src="/static/images/icons/setting2.svg"
                                                class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="font-bold">配置</text>
                                        </view>
                                    </template>
                                </view>
                            </template>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <view class="w-full h-full pt-10 flex flex-col items-center">
                        <image
                            :src="`${config.baseUrl}static/images/device_empty.png`"
                            class="w-[442rpx] h-[492rpx] mx-auto"></image>
                        <view
                            class="mx-auto mt-4 w-[300rpx] h-[84rpx] rounded-[16rpx] bg-black flex items-center justify-center gap-x-1 text-white font-bold text-[30rpx]"
                            @click="toPage('/packages/pages/rpa_code/rpa_code')">
                            即刻新增设备<u-icon name="arrow-right" color="#ffffff" size="16"></u-icon>
                        </view>
                    </view>
                </template>
            </z-paging>
        </view>
        <tabbar />
    </view>
    <confirm-dialog
        v-model="showConfirmDialog"
        title="提示"
        content="有任务正在执行中，切换模式会导致任务终止，是否确认切换"
        confirm-btn-text="确认切换"
        @confirm="handleTaskConfirm" />
    <popup-bottom
        v-model="showAutoSettingPopup"
        title="选择任务模式"
        height="35%"
        @close="showAutoSettingPopup = false">
        <template #content>
            <view class="h-full bg-[#F7F7F7] flex flex-col">
                <view class="grow min-h-0 bg-white flex flex-col justify-center py-2">
                    <view
                        v-for="(item, index) in taskModeList"
                        class="text-center flex-1 flex items-center justify-center gap-x-2 relative text-[30rpx] font-bold"
                        :key="item.value"
                        :class="{
                            'text-primary': currentDevice.auto_type == item.value,
                            'border-[0] border-b-[1rpx] border-solid border-[#00000008]':
                                index !== taskModeList.length - 1,
                        }"
                        @click.stop="handleTaskModeChange(item.value)">
                        <image
                            v-if="currentDevice.auto_type == item.value"
                            src="/static/images/icons/success.svg"
                            class="w-[32rpx] h-[32rpx]"></image>
                        {{ item.name }}
                    </view>
                </view>
                <view class="py-4 text-center mt-2 bg-white text-[30rpx] font-bold">取消</view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getDeviceList, updateDevice } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import config from "@/config";
import wechatActiveIcon from "@/static/images/common/wechat_s.png";
import redbookActiveIcon from "@/static/images/common/redbook_s.png";
import douyinActiveIcon from "@/static/images/common/douyin_s.png";
import kuaishouActiveIcon from "@/static/images/common/kuaishou_s.png";

enum TaskStatusEnum {
    // 工作中
    WORKING = 2,
    // 空闲
    IDLE = 1,
    // 离线
    OFFLINE = 0,
}

const deviceList = ref<any[]>([]);
const currentDevice = ref<any>({});
const pagingRef = shallowRef();
const showConfirmDialog = ref(false);
const showAutoSettingPopup = ref(false);

// 任务模式
const taskModeList = [
    {
        name: "24h自动任务",
        value: 1,
    },
    {
        name: "手动任务",
        value: 0,
    },
];
const currentTaskMode = ref(0);

const getDate = computed(() => {
    // 获取当前日期对应的星期和月份
    const date = new Date();
    const week = date.getDay();
    const month = date.getMonth() + 1;
    const weekMap: Record<number, string> = {
        0: "星期日",
        1: "星期一",
        2: "星期二",
        3: "星期三",
        4: "星期四",
        5: "星期五",
        6: "星期六",
    };
    return `${weekMap[week]}，${month}月${date.getDate()}日`;
});

const getDeviceStatusCount = ref<any[]>([
    {
        status: TaskStatusEnum.WORKING,
        count: 0,
    },
    {
        status: TaskStatusEnum.IDLE,
        count: 0,
    },
    {
        status: TaskStatusEnum.OFFLINE,
        count: 0,
    },
]);

const queryList = async (page_no: number, page_size: number) => {
    try {
        const {
            lists,
            extend: { statistics },
        } = await getDeviceList({
            page_no,
            page_size,
        });
        getDeviceStatusCount.value.forEach((item) => {
            item.count = statistics.find((st: any) => st.status == item.status)?.num ?? 0;
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const getDeviceIcon = (type: number) => {
    switch (type) {
        case AppTypeEnum.WECHAT:
            return wechatActiveIcon;
        case AppTypeEnum.XHS:
            return redbookActiveIcon;
        case AppTypeEnum.DOUYIN:
            return douyinActiveIcon;
        case AppTypeEnum.KUAISHOU:
            return kuaishouActiveIcon;
    }
};

// 获取设备状态样式
const getDeviceStatusInfo = (status: number) => {
    switch (status) {
        case TaskStatusEnum.OFFLINE:
            return {
                iconBgColor: "rgba(255, 36, 66, 0.05)",
                textColor: "#FF2442",
                borderColor: "rgba(255, 36, 66, 0.2)",
                text: "离线",
            };
        case TaskStatusEnum.IDLE:
            return {
                iconBgColor: "rgba(0, 192, 142, 0.05)",
                textColor: "#00C08E",
                borderColor: "rgba(0, 192, 142, 0.2)",
                text: "空闲",
            };
        case TaskStatusEnum.WORKING:
        default:
            return {
                iconBgColor: "rgba(0, 101, 251, 0.05)",
                textColor: "#0065FB",
                borderColor: "rgba(0, 101, 251, 0.2)",
                text: "工作",
            };
    }
};

const getTaskStatusText = (status: number) => {
    switch (status) {
        case 0:
            return "等待中";
        case 1:
            return "执行中";
        case 2:
            return "执行完成";
        case 3:
            return "执行失败";
        case 4:
            return "中断";
        default:
            return "-";
    }
};

const getTaskStatusPercent = (item: any) => {
    const { task_count, task_complete } = item;
    if (task_count == 0) return 0;
    return Math.round((task_complete / task_count) * 100);
};

const handleTaskModeChange = async (item: number) => {
    if (currentDevice.value.auto_type == item) {
        showAutoSettingPopup.value = false;
        return;
    }
    currentTaskMode.value = item;
    showConfirmDialog.value = true;
};

const handleTaskConfirm = async () => {
    uni.showLoading({
        title: "修改中...",
        mask: true,
    });
    try {
        await updateDevice({
            device_code: currentDevice.value.device_code,
            auto_type: currentTaskMode.value,
        });
        currentDevice.value.loading = false;
        uni.hideLoading();
        uni.showToast({
            title: "修改成功",
            icon: "none",
            duration: 3000,
        });
        pagingRef.value?.reload();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "修改失败",
            icon: "none",
            duration: 3000,
        });
    } finally {
        showAutoSettingPopup.value = false;
    }
};

const switchTaskMode = (item: any) => {
    currentDevice.value = item;
    currentTaskMode.value = item.auto_type == 1 ? 0 : 1;
    handleTaskConfirm();
};

const toPage = (url?: string, params?: Record<string, any>) => {
    if (!url) {
        uni.showToast({
            title: "敬请期待~",
        });
        return;
    }
    uni.$u.route({
        url,
        params,
    });
};

onShow(async () => {
    await nextTick();
    pagingRef.value?.reload();
});
</script>

<style scoped></style>
