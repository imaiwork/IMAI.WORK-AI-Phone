<template>
    <view class="h-screen flex flex-col">
        <u-navbar title="设备管理" title-bold :border-bottom="false" :background="{ background: 'transparent' }">
        </u-navbar>
        <view class="px-[26rpx] flex items-center justify-between mt-[16rpx]">
            <view class="flex items-center gap-x-[32rpx]">
                <view class="flex items-center gap-x-[8rpx]" v-for="item in getDeviceStatusCount" :key="item.status">
                    <view
                        class="w-[14rpx] h-[14rpx] rounded-full"
                        :style="{ backgroundColor: getDeviceStatusInfo(item.status).textColor }"></view>
                    <text class="text-[22rpx] text-[#000000b3] font-medium">
                        {{ getDeviceStatusInfo(item.status).text }} {{ item.count }}
                    </text>
                </view>
            </view>
            <view
                class="flex items-center gap-x-[8rpx] bg-white rounded-[20rpx] px-[28rpx] h-[64rpx]"
                @click="toPage('/ai_modules/device/pages/rpa_code/rpa_code')">
                <u-icon name="plus-circle"></u-icon>
                <text class="text-[26rpx] font-medium">新增设备</text>
            </view>
        </view>

        <view class="grow min-h-0 mt-[20rpx]">
            <z-paging
                ref="pagingRef"
                v-model="deviceList"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-[26rpx] pb-[20rpx]">
                    <view class="flex flex-col gap-y-[20rpx]">
                        <view
                            v-for="(item, index) in deviceList"
                            :key="index"
                            class="bg-white rounded-[24rpx] overflow-hidden"
                            style="box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06)">
                            <view class="px-[32rpx] pt-[30rpx] pb-[24rpx]">
                                <view class="flex items-center justify-between">
                                    <view class="flex items-center gap-x-[24rpx] flex-1 min-w-0">
                                        <view
                                            class="w-[80rpx] h-[80rpx] rounded-[20rpx] flex items-center justify-center flex-shrink-0"
                                            :style="{ backgroundColor: getCardStatusStyle(item).iconBgColor }">
                                            <view class="w-[40rpx] h-[40rpx]">
                                                <image
                                                    src="/static/images/icons/device_error.svg"
                                                    class="w-full h-full"
                                                    v-if="item.status == TaskStatusEnum.OFFLINE"></image>
                                                <image
                                                    src="/static/images/icons/device_success.svg"
                                                    class="w-full h-full"
                                                    v-else-if="item.status == TaskStatusEnum.IDLE"></image>
                                                <image
                                                    src="/static/images/icons/device_primary.svg"
                                                    class="w-full h-full"
                                                    v-else-if="item.status == TaskStatusEnum.WORKING"></image>
                                            </view>
                                        </view>
                                        <view class="flex-1 min-w-0">
                                            <view class="text-[30rpx] font-bold text-[#1a1a2e] line-clamp-1 break-all">
                                                {{ item.device_name || "-" }}
                                            </view>
                                            <view class="flex items-center gap-x-2 mt-[8rpx]">
                                                <text class="text-[22rpx] text-[#000000]/30 font-medium">
                                                    当前：{{ item.auto_type === 0 ? "手动任务" : "24h自动" }}
                                                </text>
                                                <text class="w-[1rpx] h-[20rpx] bg-[#000000]/20"></text>
                                                <view class="flex items-center gap-x-[6rpx]">
                                                    <view
                                                        class="w-[12rpx] h-[12rpx] rounded-full"
                                                        :style="{
                                                            backgroundColor: getCardStatusStyle(item).dotColor,
                                                        }"></view>
                                                    <text
                                                        class="text-[22rpx] font-medium"
                                                        :style="{ color: getCardStatusStyle(item).dotColor }">
                                                        {{ getCardStatusStyle(item).label }}
                                                    </text>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <view class="mt-[34rpx]">
                                    <view v-if="getDeviceWarning(item)">
                                        <view
                                            class="bg-[#FF2442]/5 px-[32rpx] py-[22rpx] rounded-[20rpx] flex justify-between items-center gap-x-4">
                                            <view class="flex items-center gap-x-2">
                                                <image
                                                    src="@/ai_modules/device/static/icons/error.svg"
                                                    class="w-[24rpx] h-[24rpx]" />
                                                <text class="text-[#FF2442] font-medium">{{
                                                    getDeviceWarning(item)?.text
                                                }}</text>
                                            </view>
                                            <view
                                                v-if="getDeviceWarning(item)?.action"
                                                class="flex items-center gap-x-1"
                                                @click.stop="getDeviceWarning(item)?.action?.()">
                                                <text class="text-xs text-[#FF2442]">{{
                                                    getDeviceWarning(item)?.actionText
                                                }}</text>
                                                <u-icon name="arrow-right" color="#FDB5BF" size="16"></u-icon>
                                            </view>
                                        </view>
                                    </view>
                                    <view
                                        v-else-if="!item.tasks?.length"
                                        class="bg-[#00C08E]/5 px-[32rpx] py-[22rpx] rounded-[20rpx]">
                                        <view class="flex items-center gap-x-2">
                                            <image
                                                src="@/ai_modules/device/static/icons/time.svg"
                                                class="w-[24rpx] h-[24rpx]" />
                                            <text class="text-[#00C08E] font-medium">等待任务执行</text>
                                        </view>
                                    </view>
                                    <!-- 任务执行中 -->
                                    <view v-else class="bg-[#F2F7FF] px-[32rpx] py-[22rpx] rounded-[20rpx]">
                                        <view class="flex justify-between items-center gap-x-1">
                                            <view class="flex items-center gap-x-2">
                                                <image
                                                    src="@/ai_modules/device/static/icons/reload.svg"
                                                    class="w-[24rpx] h-[24rpx]" />
                                                <text class="font-medium text-primary break-all line-clamp-1">{{
                                                    item.tasks[0].task_name
                                                }}</text>
                                            </view>
                                            <view class="text-primary font-medium"
                                                >{{ getTaskStatusPercent(item) }}%</view
                                            >
                                        </view>
                                        <view class="mt-[12rpx]">
                                            <u-line-progress
                                                active-color="#0065FB"
                                                inactive-color="#EDF4FB"
                                                height="12"
                                                :percent="getTaskStatusPercent(item)"
                                                :show-percent="false"></u-line-progress>
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view class="flex items-center px-5">
                                <view
                                    class="flex-1 flex items-center justify-center gap-x-2 py-[28rpx]"
                                    @click.stop="
                                        toPage('/ai_modules/device/pages/task_progress/task_progress', {
                                            device_code: item.device_code,
                                        })
                                    ">
                                    <image
                                        src="@/ai_modules/device/static/icons/progress.svg"
                                        class="w-[28rpx] h-[28rpx]" />
                                    <text class="text-[26rpx] font-medium text-[#333]">进度</text>
                                </view>
                                <view class="w-[1rpx] h-[24rpx] bg-[#0000000a] my-[16rpx]" />
                                <view
                                    class="flex-1 flex items-center justify-center gap-x-2 py-[28rpx]"
                                    @click.stop="
                                        toPage('/ai_modules/device/pages/task_statement/task_statement', {
                                            device_code: item.device_code,
                                        })
                                    ">
                                    <image
                                        src="@/ai_modules/device/static/icons/statement.svg"
                                        class="w-[28rpx] h-[28rpx]" />
                                    <text class="text-[26rpx] font-medium text-[#333]">报表</text>
                                </view>
                                <view class="w-[1rpx] h-[24rpx] bg-[#0000000a] my-[16rpx]" />
                                <view
                                    class="flex-1 flex items-center justify-center gap-x-2 py-[28rpx]"
                                    @click.stop="
                                        toPage('/ai_modules/device/pages/setting/setting', {
                                            device_code: item.device_code,
                                        })
                                    ">
                                    <image
                                        src="@/ai_modules/device/static/icons/setting.svg"
                                        class="w-[28rpx] h-[28rpx]" />
                                    <text class="text-[26rpx] font-medium text-[#333]">设置</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <template #empty>
                    <view class="w-full h-full pt-10 flex flex-col items-center">
                        <image
                            :src="`${config.baseUrl}static/images/device_empty.png`"
                            class="w-[442rpx] h-[492rpx] mx-auto" />
                        <view
                            class="mx-auto mt-4 w-[300rpx] h-[84rpx] rounded-[16rpx] bg-black flex items-center justify-center gap-x-1 text-white font-medium text-[30rpx]"
                            @click="toPage('/ai_modules/device/pages/rpa_code/rpa_code')">
                            即刻新增设备
                            <u-icon name="arrow-right" color="#ffffff" size="16"></u-icon>
                        </view>
                    </view>
                </template>
            </z-paging>
        </view>

        <dragon-button :x-edge="-20" :y-edge="100" v-if="deviceList.length > 0">
            <view
                class="w-[100rpx] h-[100rpx] rounded-full flex items-center justify-center"
                style="background: linear-gradient(180deg, rgba(77, 163, 255, 1) 0%, rgba(0, 122, 255, 1) 100%)"
                @click="toPage('/ai_modules/device/pages/choose_task_type/choose_task_type')">
                <u-icon name="plus" color="#ffffff" size="34"></u-icon>
            </view>
        </dragon-button>

        <tabbar />
    </view>

    <confirm-dialog
        v-model="showConfirmDialog"
        title="提示"
        content="有任务正在执行中，切换模式会导致任务终止，是否确认切换"
        confirm-btn-text="确认切换"
        @confirm="handleTaskConfirm" />
</template>

<script setup lang="ts">
import { getDeviceList, updateDevice } from "@/api/device";
import config from "@/config";

enum TaskStatusEnum {
    OFFLINE = 0,
    IDLE = 1,
    WORKING = 2,
}

// ─── 状态样式映射表（替代 switch/if-else 链） ───────────────────────────────
const STATUS_STYLE_MAP: Record<number, { dotColor: string; label: string; iconBgColor: string }> = {
    [TaskStatusEnum.OFFLINE]: { dotColor: "#FF2442", label: "已离线", iconBgColor: "rgba(255,36,66,0.06)" },
    [TaskStatusEnum.IDLE]: { dotColor: "#00C08E", label: "空闲", iconBgColor: "rgba(0,192,142,0.06)" },
    [TaskStatusEnum.WORKING]: { dotColor: "#0065FB", label: "执行中", iconBgColor: "rgba(0,101,251,0.06)" },
};

const STATUS_INFO_MAP: Record<number, { textColor: string; text: string }> = {
    [TaskStatusEnum.WORKING]: { textColor: "#0065FB", text: "工作" },
    [TaskStatusEnum.IDLE]: { textColor: "#00C08E", text: "空闲" },
    [TaskStatusEnum.OFFLINE]: { textColor: "#FF2442", text: "离线" },
};

const deviceList = ref<any[]>([]);
const currentDevice = ref<any>({});
const pagingRef = shallowRef();
const showConfirmDialog = ref(false);
const currentTaskMode = ref(0);

const getDeviceStatusCount = ref<any[]>([
    { status: TaskStatusEnum.WORKING, count: 0 },
    { status: TaskStatusEnum.IDLE, count: 0 },
    { status: TaskStatusEnum.OFFLINE, count: 0 },
]);

const getDeviceStatusInfo = (status: number) => STATUS_INFO_MAP[status] ?? STATUS_INFO_MAP[TaskStatusEnum.OFFLINE];

const getCardStatusStyle = (item: any) => STATUS_STYLE_MAP[item.status] ?? STATUS_STYLE_MAP[TaskStatusEnum.IDLE];

const getDeviceWarning = (item: any) => {
    const isOffline = item.status === TaskStatusEnum.OFFLINE;
    const noAccounts = item.accounts.length === 0;
    const notConfigured = item.auto_type === 1 && item.persona_id === 0;

    if (isOffline) {
        return { text: "设备断开连接，请检查", actionText: null, action: null };
    }
    if (noAccounts) {
        return {
            text: "未获取社媒账号",
            actionText: "去获取",
            action: () => toPage("/ai_modules/device/pages/setting/setting", { device_code: item.device_code }),
        };
    }
    if (notConfigured) {
        return {
            text: "IP人设未设置，无法执行",
            actionText: "去配置",
            action: () =>
                toPage("/ai_modules/device/pages/setting_person/setting_person", { device_code: item.device_code }),
        };
    }
    return null;
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const {
            lists,
            extend: { statistics },
        } = await getDeviceList({ page_no, page_size });
        getDeviceStatusCount.value.forEach((item) => {
            item.count = statistics.find((st: any) => st.status == item.status)?.num ?? 0;
        });
        pagingRef.value?.complete(lists);
    } catch {
        pagingRef.value?.complete([]);
    }
};

const getTaskStatusPercent = (item: any) => {
    const { task_count, task_complete } = item;
    if (!task_count) return 0;
    return Math.round((task_complete / task_count) * 100);
};

const handleTaskConfirm = async () => {
    uni.showLoading({ title: "修改中...", mask: true });
    try {
        await updateDevice({ device_code: currentDevice.value.device_code, auto_type: currentTaskMode.value });
        uni.hideLoading();
        uni.showToast({ title: "修改成功", icon: "none", duration: 3000 });
        pagingRef.value?.reload();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "修改失败", icon: "none", duration: 3000 });
    }
};

const toPage = (url?: string, params?: Record<string, any>) => {
    if (!url) {
        uni.showToast({ title: "敬请期待~" });
        return;
    }
    uni.$u.route({ url, params });
};

onShow(async () => {
    await nextTick();
    pagingRef.value?.reload();
});
</script>

<style scoped></style>
