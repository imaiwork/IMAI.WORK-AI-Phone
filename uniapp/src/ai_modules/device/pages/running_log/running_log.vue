<template>
    <view class="h-screen bg-[#F4F6FA]">
        <z-paging ref="pagingRef" v-model="logList" :fixed="false" :safe-area-inset-bottom="true" @query="queryLogList">
            <template v-if="displayRows.length > 0">
                <template v-for="row in displayRows" :key="row.key">
                    <view v-if="row.type === 'group'" class="group-header">
                        {{ row.title }}
                    </view>

                    <template v-else>
                        <view
                            class="log-row"
                            :class="`level-${getLogLevel(row.data)}`"
                            @click="handleToggleLog(row.data)">
                            <text class="log-time">{{ getLogTime(row.data) }}</text>
                            <text class="log-level">{{ getLevelLabel(row.data) }}</text>
                            <text class="log-module">[{{ getLogModule(row.data) }}]</text>
                            <text class="log-message">{{ getLogMessage(row.data) }}</text>
                        </view>

                        <view v-if="isLogExpanded(row.data)" class="log-detail">
                            <view class="flex gap-x-[18rpx] items-start">
                                <image
                                    v-if="getLogImage(row.data)"
                                    :src="getLogImage(row.data)"
                                    mode="aspectFill"
                                    class="w-[120rpx] h-[120rpx] rounded-[12rpx] bg-[#EEF2FF] shrink-0"
                                    @click.stop="handlePreviewImage(getLogImage(row.data))" />
                                <view class="flex-1 min-w-0">
                                    <view
                                        v-for="detail in getLogDetailList(row.data)"
                                        :key="detail.label"
                                        class="detail-line">
                                        <text class="detail-label">{{ detail.label }}</text>
                                        <text class="detail-value">{{ detail.value }}</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>
                </template>
            </template>

            <view v-else-if="logList.length > 0" class="pt-[160rpx] flex flex-col items-center">
                <text class="text-[28rpx] font-semibold text-[#1A1A1A]">当前筛选暂无日志</text>
                <text class="text-[22rpx] text-[#888888] mt-[8rpx]">切换日志级别查看更多记录</text>
            </view>

            <template #empty>
                <view class="w-full h-full pt-[180rpx] flex flex-col items-center">
                    <image
                        src="@/ai_modules/device/static/icons/statement.svg"
                        mode="aspectFit"
                        class="w-[120rpx] h-[120rpx] opacity-50" />
                    <text class="text-[28rpx] font-semibold text-[#1A1A1A] mt-[24rpx]">暂无运行日志</text>
                    <text class="text-[22rpx] text-[#888888] mt-[8rpx]">设备产生运行记录后会展示在这里</text>
                </view>
            </template>
        </z-paging>
    </view>
</template>

<script setup lang="ts">
import { getDeviceRunningLogList } from "@/api/device";

type LogLevel = "all" | "info" | "ok" | "warn" | "error" | "debug";

interface RunningLogPayload {
    msg?: string;
    status?: number | string;
    imageUrl?: string;
    material_id?: number | string;
    publish_platform?: number | string;
    [key: string]: any;
}

interface RunningLogItem {
    id: number | string;
    device_code?: string;
    app_type?: number | string;
    app_type_desc?: string;
    app_version?: string;
    day?: string;
    log?: RunningLogPayload | string;
    tag?: string;
    image?: string;
    create_time?: string;
    [key: string]: any;
}

type DisplayRow =
    | {
          type: "group";
          key: string;
          title: string;
      }
    | {
          type: "log";
          key: string;
          data: RunningLogItem;
      };

const levelTabs: { label: string; value: LogLevel }[] = [
    { label: "ALL", value: "all" },
    { label: "INFO", value: "info" },
    { label: "OK", value: "ok" },
    { label: "WARN", value: "warn" },
    { label: "ERROR", value: "error" },
    { label: "DEBUG", value: "debug" },
];

const pagingRef = shallowRef();
const logList = ref<RunningLogItem[]>([]);
const activeLevel = ref<LogLevel>("all");
const deviceCode = ref("");
const totalCount = ref(0);
const expandedLogIds = ref<Array<string | number>>([]);

const filteredLogList = computed(() => {
    if (activeLevel.value === "all") return logList.value;
    return logList.value.filter((item) => getLogLevel(item) === activeLevel.value);
});

const displayRows = computed<DisplayRow[]>(() => {
    const rows: DisplayRow[] = [];
    let prevGroupTitle = "";
    filteredLogList.value.forEach((item, index) => {
        const groupTitle = getLogGroupTitle(item);
        if (groupTitle !== prevGroupTitle) {
            rows.push({
                type: "group",
                key: `group-${groupTitle}-${index}`,
                title: groupTitle,
            });
            prevGroupTitle = groupTitle;
        }
        rows.push({
            type: "log",
            key: `log-${getLogId(item)}-${index}`,
            data: item,
        });
    });
    return rows;
});

const getLogPayload = (item: RunningLogItem): RunningLogPayload => {
    if (!item.log) return {};
    if (typeof item.log === "string") {
        try {
            return JSON.parse(item.log);
        } catch {
            return { msg: item.log };
        }
    }
    return item.log;
};

const getLogLevel = (item: RunningLogItem): Exclude<LogLevel, "all"> => {
    const payload = getLogPayload(item);
    const status = Number(payload.status);
    const message = `${payload.msg ?? ""}`;
    const lowerMessage = message.toLowerCase();
    if (/debug|调试/.test(lowerMessage)) return "debug";
    if (/失败|异常|错误|error|fail/.test(lowerMessage) || status === 0 || status === 2 || status === -1) {
        return "error";
    }
    if (/超时|过期|重试|警告|等待|warn|pending/.test(lowerMessage) || status === 4) {
        return "warn";
    }
    if (/成功|完成|通过|success|ok/.test(lowerMessage) || status === 1) {
        return "ok";
    }
    return "info";
};

const getLevelLabel = (item: RunningLogItem) => {
    const level = getLogLevel(item);
    if (level === "ok") return "OK";
    return level.toUpperCase();
};

const getLogId = (item: RunningLogItem) => item.id ?? `${item.create_time}-${item.tag}`;

const getLogTime = (item: RunningLogItem) => {
    const time = item.create_time || "";
    const timeText = time.includes(" ") ? time.split(" ")[1] : time;
    return timeText || "--:--:--";
};

const getLogGroupTitle = (item: RunningLogItem) => {
    const day = item.day || item.create_time?.slice(0, 10) || "未知日期";
    return `${day} · 运行日志`;
};

const getLogModule = (item: RunningLogItem) => item.tag || item.app_type_desc || "system";

const getLogMessage = (item: RunningLogItem) => getLogPayload(item).msg || item.tag || "-";

const getLogImage = (item: RunningLogItem) => item.image || getLogPayload(item).imageUrl || "";

const isLogExpanded = (item: RunningLogItem) => expandedLogIds.value.includes(getLogId(item));

const handleToggleLog = (item: RunningLogItem) => {
    const id = getLogId(item);
    if (expandedLogIds.value.includes(id)) {
        expandedLogIds.value = expandedLogIds.value.filter((itemId) => itemId !== id);
        return;
    }
    expandedLogIds.value = [...expandedLogIds.value, id];
};

const getLogDetailList = (item: RunningLogItem) => {
    const payload = getLogPayload(item);
    return [
        { label: "device_code", value: item.device_code || "-" },
        { label: "app", value: `${item.app_type_desc || "-"} ${item.app_version || ""}` },
        { label: "tag", value: item.tag || "-" },
        { label: "status", value: payload.status ?? "-" },
        { label: "material_id", value: payload.material_id ?? "-" },
        { label: "publish_platform", value: payload.publish_platform ?? item.app_type ?? "-" },
        { label: "image", value: getLogImage(item) || "-" },
    ];
};

const handlePreviewImage = (url: string) => {
    if (!url) return;
    uni.previewImage({
        urls: [url],
        current: url,
    });
};

const queryLogList = async (page_no: number, page_size: number) => {
    try {
        const data = await getDeviceRunningLogList({
            device_code: deviceCode.value,
            page_no,
            page_size,
        });
        const lists = data?.lists ?? [];
        totalCount.value = Number(data?.count ?? lists.length);
        pagingRef.value?.complete(lists);
    } catch {
        pagingRef.value?.complete([]);
    }
};

onLoad((options: any) => {
    deviceCode.value = options?.device_code ?? "";
});
</script>

<style lang="scss" scoped>
.log-row {
    @apply bg-white flex items-baseline px-[32rpx] py-[12rpx] border-b border-solid border-[#F0F0F0];

    &:active {
        background: #f7f9ff;
    }
}

.log-time {
    @apply text-[20rpx] text-[#BBBBBB] shrink-0 pr-[12rpx];
    font-family: Menlo, Consolas, monospace;
}

.log-level {
    @apply text-[18rpx] font-extrabold px-[10rpx] py-[2rpx] rounded-[6rpx] shrink-0 mr-[12rpx];
    font-family: Menlo, Consolas, monospace;
}

.log-module {
    @apply text-[20rpx] shrink-0 pr-[10rpx];
    font-family: Menlo, Consolas, monospace;
}

.log-message {
    @apply text-[22rpx] text-[#1A1A1A] leading-[36rpx] flex-1 min-w-0 break-all;
    font-family: Menlo, Consolas, monospace;
}

.log-detail {
    @apply text-[20rpx] text-[#888888] bg-[#F7F9FF] mx-[32rpx] mb-[8rpx] px-[20rpx] py-[12rpx] rounded-r-[12rpx] leading-[34rpx] break-all;
    font-family: Menlo, Consolas, monospace;
    border-left: 4rpx solid #c7deff;
}

.detail-line {
    @apply flex gap-x-[10rpx] leading-[34rpx];
}

.detail-label {
    @apply w-[168rpx] shrink-0 text-[#BBBBBB] line-clamp-1;
}

.detail-value {
    @apply flex-1 min-w-0 text-[#676767] break-all;
}

.level-info {
    .log-level {
        @apply bg-[#EBF2FF] text-primary;
    }

    .log-module {
        @apply text-primary;
    }
}

.level-ok {
    .log-level {
        @apply bg-[#F0FBE8] text-[#52C41A];
    }

    .log-module {
        @apply text-[#52C41A];
    }
}

.level-warn {
    .log-level {
        @apply bg-[#FFF7E6] text-[#FA8C16];
    }

    .log-module,
    .log-message {
        @apply text-[#FA8C16];
    }
}

.level-error {
    .log-level {
        @apply bg-[#FFF1F0] text-[#FF4D4F];
    }

    .log-module,
    .log-message {
        @apply text-[#FF4D4F];
    }
}

.level-debug {
    .log-level {
        @apply bg-[#F4F6FA] text-[#BBBBBB];
    }

    .log-module,
    .log-message {
        @apply text-[#BBBBBB];
    }
}

.live-dot {
    width: 12rpx;
    height: 12rpx;
    border-radius: 999rpx;
    background: #52c41a;
    animation: blink 1.4s infinite;
}

@keyframes blink {
    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.2;
    }
}
</style>
