<template>
    <view class="mt-[24rpx]">
        <view class="flex items-center gap-[8rpx] flex-wrap">
            <image :src="CornerDownRightIcon" mode="aspectFit" class="w-[26rpx] h-[26rpx] shrink-0" />
            <text class="text-[24rpx] font-semibold text-main">热点库</text>
            <text class="text-[22rpx] text-muted">·</text>
            <text class="text-[22rpx]" :class="pendingCount ? 'text-primary font-semibold' : 'text-muted'">
                {{ pendingCount }}{{ HOTSPOT_STOCK_PENDING_COUNT_SUFFIX }}
            </text>
            <text class="text-[22rpx] text-muted">·</text>
            <text class="text-[22rpx] text-muted">
                {{ usedCount }}{{ HOTSPOT_STOCK_USED_COUNT_SUFFIX }}
            </text>
        </view>
        <text class="block mt-[8rpx] text-[22rpx] leading-[34rpx] text-content">
            在「{{ TRACK_PAGE_NAME }}」里分析热点后点「入库」，这里就能取用
        </text>

        <view v-if="items.length" class="mt-[16rpx] h-[360rpx] rounded-[20rpx] bg-[#F7F9FC] overflow-hidden">
            <scroll-view scroll-y class="h-full w-full">
                <view>
                    <view
                        v-for="(item, index) in items"
                        :key="item.id"
                        class="flex items-center gap-[8rpx] px-[20rpx] py-[16rpx]"
                        :class="[
                            index > 0 ? 'border-[0] border-t-[2rpx] border-solid border-[#EEF2F8]' : '',
                            item.useStatus === HotspotStockUseStatus.Used ? 'opacity-60' : '',
                        ]">
                        <image :src="FlameOrangeIcon" mode="aspectFit" class="w-[28rpx] h-[28rpx] shrink-0" />
                        <text class="grow min-w-0 text-[26rpx] font-semibold text-main line-clamp-1 break-all">
                            {{ item.title }}
                        </text>
                        <view class="shrink-0 flex items-center gap-[4rpx]">
                            <text
                                class="text-[20rpx] leading-[28rpx] px-[8rpx] py-[2rpx] rounded-[8rpx]"
                                :class="useStatusClass(item.useStatus)">
                                {{ HOTSPOT_STOCK_USE_STATUS_LABEL[item.useStatus] }}
                            </text>
                            <text
                                class="text-[20rpx] leading-[28rpx] px-[8rpx] py-[2rpx] rounded-[8rpx]"
                                :class="kindClass(item.kind)">
                                {{ KIND_LABEL[item.kind] }}
                            </text>
                            <text class="text-[20rpx] text-muted">
                                {{ item.platform }}{{ item.date }}
                            </text>
                        </view>
                        <view
                            v-if="item.id"
                            class="shrink-0 w-[48rpx] h-[48rpx] flex items-center justify-center active:opacity-70"
                            @click.stop="handleAskDelete(item)">
                            <u-icon name="trash" color="#C0C8D8" size="18"></u-icon>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <text class="block mt-[28rpx] text-[24rpx] font-semibold text-[#4B5563]">取用规则</text>
        <view class="mt-[16rpx] flex bg-[#F3F4F6] rounded-[20rpx] p-[6rpx] gap-[6rpx]">
            <view
                v-for="opt in TAKE_MODE_OPTIONS"
                :key="opt.val"
                class="take-btn"
                :class="{ 'take-btn-sel': takeMode === opt.val }"
                @click="emit('update:takeMode', opt.val)">
                <text>{{ opt.label }}</text>
            </view>
        </view>
        <view
            class="mt-[20rpx] bg-[#F7F9FC] border-[2rpx] border-solid border-[#EEF2F8] rounded-[24rpx] px-[28rpx] py-[24rpx]">
            <text class="block text-[24rpx] leading-[36rpx] text-[#6B7280]">{{ currentTakeNote }}</text>
        </view>

        <view
            class="mt-[24rpx] h-[80rpx] rounded-[20rpx] bg-[#EEF4FF] flex items-center justify-center gap-[10rpx] active:opacity-80"
            @click="emit('goTrack')">
            <image :src="FlamePrimaryIcon" mode="aspectFit" class="w-[28rpx] h-[28rpx] shrink-0" />
            <text class="text-[26rpx] font-semibold text-primary">去「{{ TRACK_PAGE_NAME }}」分析并入库</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { deleteHotspotStock, getHotspotStock } from "@/api/hotspot";
import { useLockFn } from "@/hooks/useLockFn";
import {
    HOTSPOT_PLATFORM_LABELS,
    HOTSPOT_STOCK_DELETE_CONFIRM,
    HOTSPOT_STOCK_PENDING_COUNT_SUFFIX,
    HOTSPOT_STOCK_USED_COUNT_SUFFIX,
    HOTSPOT_STOCK_USE_STATUS_LABEL,
    HotspotStockHasScript,
    HotspotStockUseStatus,
    HotspotTakeModeEnum,
} from "../enums";
import CornerDownRightIcon from "@/ai_modules/person/static/icons/employee/corner-down-right-gray.svg";
import FlameOrangeIcon from "@/ai_modules/person/static/icons/employee/flame-orange.svg";
import FlamePrimaryIcon from "@/ai_modules/person/static/icons/employee/flame-primary.svg";

enum HotspotLibItemKind {
    TitleOnly = "title_only",
    WithScript = "with_script",
}

interface HotspotLibItem {
    id: number;
    title: string;
    platform: string;
    date: string;
    kind: HotspotLibItemKind;
    useStatus: HotspotStockUseStatus;
}

interface HotspotStockRow {
    id?: number;
    topic?: string;
    title?: string;
    platform?: string;
    has_script?: number;
    use_status?: number;
    use_count?: number;
    create_time?: number;
}

const TRACK_PAGE_NAME = "热点追踪";
const UNIX_SEC_MIN = 1e9;
const UNIX_MS_MIN = 1e12;
const DELETE_SUCCESS_MSG = "已移出热点库";
const DELETE_FAIL_MSG = "移出失败";

const KIND_LABEL: Record<HotspotLibItemKind, string> = {
    [HotspotLibItemKind.TitleOnly]: "仅标题",
    [HotspotLibItemKind.WithScript]: "已带文案",
};

const TAKE_MODE_OPTIONS = [
    {
        val: HotspotTakeModeEnum.Latest,
        label: "最新入库优先",
        note: "每次生成取最新入库的热点，追热点时效最好。",
    },
    {
        val: HotspotTakeModeEnum.Once,
        label: "每条只用一次",
        note: "按入库顺序取用，每条用完即标记，不重复消费同一热点。",
    },
] as const;

const props = defineProps<{
    takeMode: HotspotTakeModeEnum;
    personaId: string;
}>();

const emit = defineEmits<{
    (e: "update:takeMode", value: HotspotTakeModeEnum): void;
    (e: "goTrack"): void;
    (e: "askDelete", content: string): void;
}>();

const items = ref<HotspotLibItem[]>([]);
const pendingCount = ref(0);
const usedCount = ref(0);
let requestToken = 0;

const currentTakeNote = computed(
    () => TAKE_MODE_OPTIONS.find((opt) => opt.val === props.takeMode)?.note ?? ""
);

const useStatusClass = (status: HotspotStockUseStatus): string =>
    status === HotspotStockUseStatus.Pending ? "bg-[#EEF4FF] text-primary" : "bg-[#F3F4F6] text-[#9CA3AF]";

const kindClass = (kind: HotspotLibItemKind): string =>
    kind === HotspotLibItemKind.WithScript ? "bg-[#ECFDF5] text-[#10B981]" : "bg-[#F3F4F6] text-[#9CA3AF]";

const formatStockDate = (value: unknown): string => {
    const date = toStockDate(value);
    if (!date) return "";
    const pad = (num: number) => String(num).padStart(2, "0");
    return `${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};

const toStockDate = (value: unknown): Date | null => {
    if (value == null || value === "") return null;
    if (typeof value === "string" && !/^\d+(\.\d+)?$/.test(value.trim())) {
        const date = new Date(value.replace(/-/g, "/"));
        return Number.isNaN(date.getTime()) ? null : date;
    }
    const stamp = Number(value);
    if (!Number.isFinite(stamp) || stamp < UNIX_SEC_MIN) return null;
    const date = new Date(stamp >= UNIX_MS_MIN ? stamp : stamp * 1000);
    return Number.isNaN(date.getTime()) ? null : date;
};

const toCount = (value: unknown, fallback: number): number => {
    const num = Number(value);
    return Number.isFinite(num) && num >= 0 ? num : fallback;
};

const normalizeUseStatus = (row: HotspotStockRow): HotspotStockUseStatus => {
    const status = Number(row.use_status);
    if (status === HotspotStockUseStatus.Used) return HotspotStockUseStatus.Used;
    if (status === HotspotStockUseStatus.Pending) return HotspotStockUseStatus.Pending;
    return Number(row.use_count) > 0 ? HotspotStockUseStatus.Used : HotspotStockUseStatus.Pending;
};

const normalizeStockItem = (row: HotspotStockRow): HotspotLibItem => {
    const hasScript = Number(row.has_script) === HotspotStockHasScript.WithScript;
    return {
        id: Number(row.id) || 0,
        title: String(row.topic || row.title || "").trim(),
        platform: HOTSPOT_PLATFORM_LABELS[String(row.platform || "")] || String(row.platform || ""),
        date: formatStockDate(row.create_time),
        kind: hasScript ? HotspotLibItemKind.WithScript : HotspotLibItemKind.TitleOnly,
        useStatus: normalizeUseStatus(row),
    };
};

const countByStatus = (rows: HotspotLibItem[], status: HotspotStockUseStatus): number =>
    rows.filter((row) => row.useStatus === status).length;

const resetStockState = (): void => {
    items.value = [];
    pendingCount.value = 0;
    usedCount.value = 0;
};

const applyStockResult = (res: { lists?: HotspotStockRow[]; pending_count?: number; used_count?: number }): void => {
    const lists = Array.isArray(res?.lists) ? res.lists : [];
    const nextItems = lists.map((row) => normalizeStockItem(row));
    items.value = nextItems;
    pendingCount.value = toCount(
        res?.pending_count,
        countByStatus(nextItems, HotspotStockUseStatus.Pending)
    );
    usedCount.value = toCount(res?.used_count, countByStatus(nextItems, HotspotStockUseStatus.Used));
};

const queryHotspotStock = async (personaId: string, token: number): Promise<void> => {
    if (!personaId) {
        if (token === requestToken) resetStockState();
        return;
    }
    try {
        const res = await getHotspotStock({ persona_id: personaId });
        if (token !== requestToken) return;
        applyStockResult(res || {});
    } catch {
        if (token !== requestToken) return;
        resetStockState();
    }
};

watch(
    () => props.personaId,
    (personaId) => {
        requestToken += 1;
        queryHotspotStock(personaId, requestToken);
    },
    { immediate: true }
);

const pendingDeleteId = ref(0);
const pendingDeleteStatus = ref<HotspotStockUseStatus>(HotspotStockUseStatus.Pending);

const handleAskDelete = (item: HotspotLibItem): void => {
    if (!item.id) return;
    pendingDeleteId.value = item.id;
    pendingDeleteStatus.value = item.useStatus;
    emit("askDelete", HOTSPOT_STOCK_DELETE_CONFIRM);
};

const { lockFn: confirmDelete } = useLockFn(async () => {
    const id = pendingDeleteId.value;
    if (!id) return;
    try {
        await deleteHotspotStock({ id });
        items.value = items.value.filter((row) => row.id !== id);
        if (pendingDeleteStatus.value === HotspotStockUseStatus.Used) {
            usedCount.value = Math.max(0, usedCount.value - 1);
        } else {
            pendingCount.value = Math.max(0, pendingCount.value - 1);
        }
        uni.$u.toast(DELETE_SUCCESS_MSG);
    } catch (error: any) {
        uni.$u.toast(error?.message || error || DELETE_FAIL_MSG);
    } finally {
        pendingDeleteId.value = 0;
        pendingDeleteStatus.value = HotspotStockUseStatus.Pending;
    }
});

defineExpose({
    confirmDelete,
});
</script>

<style lang="scss" scoped>
.take-btn {
    @apply flex-1 min-w-0 min-h-[64rpx] rounded-[16rpx] flex items-center justify-center text-[24rpx] font-bold text-[#9CA3AF];
}

.take-btn-sel {
    @apply bg-white text-primary;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
}
</style>
