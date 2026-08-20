<template>
    <view class="h-screen flex flex-col bg-[#F4F6FA]">
        <u-navbar
            title="工作安排"
            title-bold
            :border-bottom="false"
            :background="{
                background: '#ffffff',
            }">
        </u-navbar>

        <calendar v-model="selectedDate" @select-date="handleSelectDate" />

        <view class="px-[32rpx] pt-[24rpx]">
            <view class="bg-white rounded-[32rpx] p-[32rpx]">
                <view class="flex items-start justify-between mb-[24rpx]">
                    <view>
                        <view class="text-sm font-bold text-[#1F2937]">今日任务进度</view>
                        <view class="text-xs text-[#9CA3AF] mt-[4rpx]">{{ selectedDateLabel }}</view>
                    </view>
                    <view class="text-right">
                        <view class="text-[48rpx] leading-none font-bold text-[#2B6EFF]">
                            {{ completedCount }} / {{ totalCount }}
                        </view>
                        <view class="text-[20rpx] text-[#9CA3AF] mt-[8rpx]">项已完成</view>
                    </view>
                </view>

                <view class="w-full h-[12rpx] bg-[#F3F4F6] rounded-full overflow-hidden mb-[24rpx]">
                    <view
                        class="h-full bg-[#2B6EFF] rounded-full transition-all duration-500"
                        :style="{ width: `${progressPercent}%` }">
                    </view>
                </view>

                <view class="flex gap-[16rpx]">
                    <view
                        class="flex-1 flex items-center gap-[10rpx] bg-[#EFF6FF] rounded-[24rpx] px-[20rpx] py-[16rpx]">
                        <view class="w-[14rpx] h-[14rpx] bg-[#2B6EFF] rounded-full animate-pulse shrink-0"></view>
                        <text class="text-xs font-semibold text-[#2563EB]">执行中 {{ runningCount }}</text>
                    </view>
                    <view
                        class="flex-1 flex items-center gap-[10rpx] bg-[#F0FDF4] rounded-[24rpx] px-[20rpx] py-[16rpx]">
                        <u-icon name="checkmark" color="#22C55E" size="22"></u-icon>
                        <text class="text-xs font-semibold text-[#16A34A]">已完成 {{ completedCount }}</text>
                    </view>
                    <view
                        class="flex-1 flex items-center gap-[10rpx] bg-[#F9FAFB] rounded-[24rpx] px-[20rpx] py-[16rpx]">
                        <u-icon name="clock" color="#9CA3AF" size="22"></u-icon>
                        <text class="text-xs font-semibold text-[#6B7280]">待执行 {{ waitingCount }}</text>
                    </view>
                </view>
            </view>
        </view>

        <view class="px-[32rpx] pt-[24rpx] pb-[20rpx] flex items-center justify-between">
            <view>
                <view class="text-[30rpx] font-bold text-[#1F2937]">任务列表({{ taskList.length }})</view>
                <view class="text-[22rpx] text-[#9CA3AF] mt-[4rpx]">按执行时间自动排序</view>
            </view>
            <view class="flex items-center bg-white rounded-full p-[6rpx] shadow-[0_4rpx_24rpx_rgba(15,23,42,0.06)]">
                <view
                    v-for="tab in tabs"
                    :key="tab.key"
                    class="px-[22rpx] py-[10rpx] rounded-full text-[22rpx] font-semibold"
                    :class="currentTab === tab.key ? 'bg-[#2B6EFF] text-white' : 'text-[#6B7280]'"
                    @click="handleTab(tab.key)">
                    {{ tab.label }}
                </view>
            </view>
        </view>

        <view class="grow min-h-0">
            <z-paging ref="pagingRef" v-model="taskList" :fixed="false" @query="queryTaskList">
                <view class="pb-[32rpx] px-[32rpx]">
                    <view
                        v-for="(item, index) in sortedTaskList"
                        :key="item.id || index"
                        class="flex items-stretch gap-0 py-[12rpx]"
                        @click="handleDetail(item)">
                        <view class="w-[96rpx] shrink-0 text-right pr-[24rpx] pt-[16rpx]">
                            <text class="text-[20rpx] font-semibold text-[#9CA3AF] leading-none whitespace-nowrap">
                                {{ getTaskStartTime(item) }}
                            </text>
                        </view>

                        <view class="w-[32rpx] shrink-0 flex flex-col items-center">
                            <view
                                class="w-[20rpx] h-[20rpx] rounded-full shrink-0 mt-[14rpx]"
                                :style="{ background: getStatusColor(item.status) }">
                            </view>
                            <view
                                class="w-[2rpx] flex-1 mt-[8rpx]"
                                :class="index === sortedTaskList.length - 1 ? 'opacity-0' : 'bg-[#E5E7EB]'">
                            </view>
                        </view>

                        <view class="flex-1 min-w-0 ml-[20rpx] pb-[8rpx]">
                            <view class="bg-white rounded-[24rpx] p-[24rpx] active:bg-[#F9FAFB]">
                                <view class="flex items-center gap-[12rpx] mb-[12rpx]">
                                    <text
                                        class="text-[18rpx] font-bold px-[12rpx] py-[4rpx] rounded-full shrink-0"
                                        :class="getCategoryClass(item)">
                                        {{ getTaskCategory(item) }}
                                    </text>
                                    <text
                                        class="text-[18rpx] text-[#9CA3AF] px-[12rpx] py-[4rpx] rounded-full bg-[#F3F4F6] shrink-0">
                                        {{ getDeviceName(item) }}
                                    </text>
                                </view>

                                <view class="flex items-start justify-between gap-[16rpx]">
                                    <text
                                        class="text-sm leading-snug font-semibold flex-1 min-w-0"
                                        :class="getTitleClass(item.status)">
                                        {{ item.name || "未命名任务" }}
                                    </text>
                                    <view
                                        class="px-[16rpx] py-[4rpx] rounded-full shrink-0"
                                        :class="getStatusBadgeClass(item.status)">
                                        <text class="text-[20rpx] font-semibold">{{ getStatusText(item.status) }}</text>
                                    </view>
                                </view>
                                <view class="mt-[14rpx] flex items-center justify-between">
                                    <text class="text-xs text-[#2B6EFF] font-medium">查看详情 ›</text>
                                    <view
                                        class="w-[48rpx] h-[48rpx] rounded-full bg-[#F9FAFB] flex items-center justify-center"
                                        @click.stop="handleUpdateTaskName(item)">
                                        <u-icon name="edit-pen" color="#9CA3AF" size="24"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
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
import { getDeviceTaskCalendarList, getDeviceTaskCalendarStatistics } from "@/api/device";
import Calendar from "@/ai_modules/device/components/calendar/calendar.vue";
import TaskDetailPop from "@/ai_modules/device/components/task-detail-pop/task-detail-pop.vue";
import TaskEditNamePop from "@/ai_modules/device/components/task-edit-name/task-edit-name.vue";

const pagingRef = ref<any>(null);
const taskList = ref<any[]>([]);

// 0等待中1执行中2执行完成3执行失败
const tabs = [
    { key: "", label: "全部" },
    { key: "1", label: "执行中" },
    { key: "2", label: "已完成" },
    { key: "3", label: "已失败" },
];
const currentTab = ref("");

const selectedDate = ref<string>(uni.$u.timeFormat(new Date(), "yyyy-mm-dd"));
const deviceCode = ref("");
const taskStatistics = ref([
    { title: "总任务", value: 0, key: "all" },
    { title: "已完成", value: 0, key: "completed" },
    { title: "待开始", value: 0, key: "waiting" },
    { title: "执行中", value: 0, key: "execution" },
    { title: "已失败", value: 0, key: "failure" },
]);

const taskDetailRef = shallowRef<any>(null);
const taskEditNameRef = shallowRef<any>(null);
const showDetailPop = ref<boolean>(false);
const showEditNamePop = ref<boolean>(false);

const getStatisticValue = (key: string) => taskStatistics.value.find((item) => item.key === key)?.value || 0;
const totalCount = computed(() => getStatisticValue("all"));
const completedCount = computed(() => getStatisticValue("completed"));
const runningCount = computed(() => getStatisticValue("execution"));
const waitingCount = computed(() => getStatisticValue("waiting"));
const progressPercent = computed(() => {
    if (!totalCount.value) return 0;
    return Math.min(100, Math.round((completedCount.value / totalCount.value) * 100));
});

const selectedDateLabel = computed(() => {
    const [year, month, day] = selectedDate.value.split("-").map(Number);
    const date = new Date(year, month - 1, day);
    if (Number.isNaN(date.getTime())) return "";
    const week = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"][date.getDay()];
    return `${year}年${month}月${day}日 ${week}`;
});

const sortedTaskList = computed(() => {
    return [...taskList.value].sort((a, b) => getSortTime(a).localeCompare(getSortTime(b)));
});

const queryTaskList = async (page: number, pageSize: number) => {
    try {
        if (page === 1) await getStatistics();
        const { lists } = await getDeviceTaskCalendarList({
            page_no: page,
            page_size: pageSize,
            day: selectedDate.value,
            status: currentTab.value,
            device_code: deviceCode.value || "",
        });
        pagingRef.value.complete(lists);
    } catch (error) {
        pagingRef.value.complete([]);
    }
};

const getStatistics = async () => {
    const data = await getDeviceTaskCalendarStatistics({
        day: selectedDate.value,
        device_code: deviceCode.value || "",
    });
    taskStatistics.value.forEach((item) => {
        item.value = Number(data?.[item.key] || 0);
    });
};

const handleTab = (key: string) => {
    currentTab.value = key;
    reload();
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

const getSortTime = (item: any) => {
    const value = item.start_time || "";
    if (!value) return "99:99";
    if (value.includes(" ")) return value.split(" ")[1] || "99:99";
    return value;
};

const getTaskStartTime = (item: any) => {
    const value = getSortTime(item);
    if (!value || value === "99:99") return "全天";
    return value.slice(0, 5);
};

const getTaskCategory = (item: any) => {
    return item.persona_name || item.persona_info?.persona_name || item.task_category || "AI员工";
};

const getDeviceName = (item: any) => {
    return item.device_name || item.device_code || "设备";
};

const normalizeStatus = (status: unknown) => Number(status);

const getStatusText = (status: number | string) => {
    const statusValue = normalizeStatus(status);
    const map: Record<number, string> = {
        0: "待执行",
        1: "执行中",
        2: "已完成",
        3: "执行失败",
        4: "已中断",
    };
    return map[statusValue] || "-";
};

const getStatusColor = (status: number | string) => {
    const statusValue = normalizeStatus(status);
    if (statusValue === 1) return "#2B6EFF";
    if (statusValue === 2) return "#22C55E";
    if (statusValue === 3 || statusValue === 4) return "#F87171";
    return "#D1D5DB";
};

const isFailedTask = (status: number | string) => {
    const statusValue = normalizeStatus(status);
    return statusValue === 3 || statusValue === 4;
};

const getFailureReason = (item: any) => {
    return item.remark || item.fail_reason || item.error_msg || item.reason || "";
};

const getStatusBadgeClass = (status: number | string) => {
    const statusValue = normalizeStatus(status);
    if (statusValue === 1) return "text-[#2563EB] bg-[#DBEAFE]";
    if (statusValue === 2) return "text-[#16A34A] bg-[#F0FDF4]";
    if (statusValue === 3 || statusValue === 4) return "text-[#EF4444] bg-[#FEF2F2]";
    return "text-[#9CA3AF] bg-[#F3F4F6]";
};

const getTitleClass = (status: number | string) => {
    const statusValue = normalizeStatus(status);
    if (statusValue === 1) return "text-[#1D4ED8] font-bold";
    if (statusValue === 0) return "text-[#6B7280]";
    return "text-[#1F2937]";
};

const getCategoryClass = (item: any) => {
    const text = getTaskCategory(item);
    if (/美食|餐饮|探店/.test(text)) return "text-[#F97316] bg-[#FFF7ED]";
    if (/私域|客户|微信/.test(text)) return "text-[#0D9488] bg-[#F0FDFA]";
    return "text-[#2563EB] bg-[#EFF6FF]";
};

onLoad((options: any) => {
    deviceCode.value = options.device_code;
});
</script>

<style scoped></style>
