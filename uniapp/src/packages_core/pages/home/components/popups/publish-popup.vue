<template>
    <popup-bottom
        v-model="show"
        height="82%"
        custom-class="bg-[#f4f5f9]"
        :clearable="false"
        :mask-close-able="true"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="bg-white px-[40rpx] py-[24rpx] shadow-[0_4rpx_24rpx_rgba(0,0,0,0.06)]">
                <view class="w-[80rpx] h-[8rpx] mx-auto mb-[28rpx] bg-[#e5e7eb] rounded-full" />
                <view class="mb-[24rpx] flex items-center justify-between">
                    <text class="block text-[36rpx] font-bold text-[#1f2937]">全网发布监控</text>
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-[#f3f4f6] text-[#6b7280] flex items-center justify-center text-[44rpx] leading-none"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" color="#8B9199" :size="20"></u-icon>
                    </view>
                </view>
                <scroll-view v-if="tabs.length" scroll-x class="mt-[16rpx] whitespace-nowrap" show-scrollbar="false">
                    <view class="inline-flex gap-[16rpx]">
                        <view
                            v-for="(tab, index) in tabs"
                            :key="tab.slot_key"
                            class="min-w-[216rpx] border-[4rpx] rounded-[32rpx] px-[24rpx] py-[16rpx] flex flex-col items-center gap-[6rpx] text-[20rpx] font-bold"
                            :class="
                                activeTab === index
                                    ? 'bg-white border-solid border-[#bfdbfe] text-primary'
                                    : 'bg-[#f9fafb] border-[transparent] text-[#9ca3af]'
                            "
                            @click="activeTab = index">
                            <text :class="tabStatusClass(tab.status)">● {{ tab.status_text }}</text>
                            <text>{{ tab.time_range }}</text>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
        <template #content>
            <z-paging
                ref="pagingRef"
                v-model="lists"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view v-if="currentSlot" class="p-[32rpx] pb-[40rpx] flex flex-col gap-[24rpx]">
                    <view class="flex items-center justify-between px-[8rpx]">
                        <text class="text-xs text-[#9ca3af]">
                            共 {{ currentSlot.total_count }} 项发布任务 · {{ currentSlot.devices.length }} 台设备
                        </text>
                        <text class="rounded-full px-[20rpx] py-[8rpx] text-xs font-bold" :class="summaryBadgeClass">
                            {{ summaryBadgeText }}
                        </text>
                    </view>

                    <view
                        v-for="device in currentSlot.devices"
                        :key="device.device_code"
                        class="bg-white rounded-[32rpx] overflow-hidden">
                        <view
                            class="px-[28rpx] py-[20rpx] flex items-center gap-[16rpx] border-[0] border-b-[2rpx] border-[#f9fafb]">
                            <image
                                :src="device.avatar || defaultAvatar"
                                mode="aspectFill"
                                class="w-[56rpx] h-[56rpx] rounded-[16rpx] bg-[#f3f4f6] flex-shrink-0" />
                            <text class="flex-1 min-w-0 text-sm font-bold text-[#1f2937] line-clamp-1">
                                {{ device.nickname || device.account || "未命名设备" }}
                            </text>
                            <text
                                class="flex-shrink-0 text-[20rpx] text-[#9ca3af] bg-[#f3f4f6] rounded-full px-[16rpx] py-[4rpx]">
                                设备 {{ device.device_code }}
                            </text>
                        </view>

                        <view
                            v-for="item in device.items"
                            :key="item.task_id"
                            class="px-[28rpx] py-[24rpx] flex items-center justify-between gap-[16rpx] border-[0] border-b-[2rpx] border-[#f9fafb] last:border-b-0 active:bg-[#f9fafb]"
                            @click="openDetail(item)">
                            <view class="flex items-center gap-[16rpx] flex-1 min-w-0">
                                <text
                                    class="w-[56rpx] h-[56rpx] rounded-[16rpx] flex items-center justify-center text-[24rpx] font-bold flex-shrink-0"
                                    :style="platformBadgeStyle(item.platform)">
                                    {{ platformShort(item.platform_name) }}
                                </text>
                                <view class="flex-1 min-w-0">
                                    <text class="block text-sm text-[#374151] line-clamp-1">
                                        {{ item.platform_name }} · {{ item.media_type_text }}
                                    </text>
                                    <text class="block mt-[2rpx] text-[20rpx] text-[#9ca3af]">
                                        {{ item.publish_time }}
                                    </text>
                                </view>
                            </view>
                            <view class="flex items-center gap-[12rpx] flex-shrink-0">
                                <view
                                    v-if="item.can_resend"
                                    class="rounded-full bg-error px-[20rpx] py-[8rpx] active:opacity-80"
                                    @click.stop="openResend(item)">
                                    <text class="text-[20rpx] font-bold text-white">重新发送</text>
                                </view>
                                <view class="flex items-center gap-[6rpx]" :class="tabStatusClass(item.task_status)">
                                    <text class="text-xs font-semibold whitespace-nowrap">
                                        {{ item.task_status_text }}
                                    </text>
                                    <u-icon name="arrow-right" :size="20" color="#c0c4cc"></u-icon>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </template>
    </popup-bottom>

    <publish-detail-popup v-model="detailVisible" :item="detailItem" @resend="openResend(detailItem)" />
    <publish-resend-popup v-model="resendVisible" :item="resendItem" @success="handleResendSuccess" />
</template>

<script setup lang="ts">
import PublishDetailPopup from "./publish-detail-popup.vue";
import PublishResendPopup from "./publish-resend-popup.vue";
import { getPublishTaskList } from "@/api/person";
import { AppTypeEnum } from "@/enums/appEnums";
import config from "@/config";
import type { PublishItem, PublishSlot, PublishTab } from "./publish-types";

const props = defineProps<{
    modelValue: boolean;
    personaId: string | number;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v) => emit("update:modelValue", v),
});

const defaultAvatar = `${config.baseUrl}static/images/mp/person_default.png`;

const lists = ref<PublishSlot[]>([]);
const tabs = ref<PublishTab[]>([]);
const activeTab = ref(0);
const pagingRef = shallowRef<any>();

const detailVisible = ref(false);
const detailItem = ref<PublishItem | null>(null);
const openDetail = (item: PublishItem) => {
    detailItem.value = item;
    detailVisible.value = true;
};

const resendVisible = ref(false);
const resendItem = ref<PublishItem | null>(null);
const openResend = (item: PublishItem | null) => {
    if (!item) return;
    resendItem.value = item;
    resendVisible.value = true;
};

// 重发下发成功后任务回到执行中，关闭详情并刷新列表状态
const handleResendSuccess = () => {
    detailVisible.value = false;
    pagingRef.value?.reload();
};

const currentSlot = computed<PublishSlot | undefined>(() => {
    const tab = tabs.value[activeTab.value];
    if (!tab) return lists.value[activeTab.value];
    return lists.value.find((slot) => slot.slot_key === tab.slot_key);
});

// 汇总徽标：有失败优先报失败，其次执行中 / 待执行，否则全部完成
const summaryBadgeText = computed(() => {
    const s = currentSlot.value;
    if (!s) return "";
    if (s.failed_count > 0) return `${s.failed_count} 项失败`;
    if (s.running_count > 0) return "执行中";
    if (s.waiting_count > 0) return "待执行";
    return "全部完成";
});
const summaryBadgeClass = computed(() => {
    const s = currentSlot.value;
    if (!s) return tabBadgeClass(0);
    if (s.failed_count > 0) return tabBadgeClass(3);
    if (s.running_count > 0) return tabBadgeClass(1);
    if (s.waiting_count > 0) return tabBadgeClass(0);
    return tabBadgeClass(2);
});

const todayDate = () => {
    const d = new Date();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${d.getFullYear()}-${mm}-${dd}`;
};

const queryList = async (page_no: number, page_size: number) => {
    if (!props.personaId) {
        pagingRef.value?.complete([]);
        return;
    }
    try {
        const data: any = await getPublishTaskList({
            persona_id: props.personaId,
            date: todayDate(),
            page_no,
            page_size,
        });
        if (Array.isArray(data?.extend?.tabs)) tabs.value = data.extend.tabs;
        pagingRef.value?.complete(Array.isArray(data?.lists) ? data.lists : []);
    } catch (error) {
        console.warn("getPublishTaskList failed", error);
        pagingRef.value?.complete(false);
    }
};

watch(
    () => props.modelValue,
    (v) => {
        if (v) {
            activeTab.value = 0;
            nextTick(() => pagingRef.value?.reload());
        } else {
            activeTab.value = 0;
            detailVisible.value = false;
            resendVisible.value = false;
        }
    },
);

// 状态：0=待执行 1=执行中 2=已完成 3=失败
const STATUS_TEXT_CLASS: Record<number, string> = {
    0: "text-[#9ca3af]",
    1: "text-primary",
    2: "text-success",
    3: "text-error",
};
const tabStatusClass = (status: number) => STATUS_TEXT_CLASS[status] || STATUS_TEXT_CLASS[0];

const TAB_BADGE_CLASS: Record<number, string> = {
    0: "text-[#9ca3af] bg-[#f9fafb]",
    1: "text-primary bg-primary-light-9",
    2: "text-success bg-success-light-9",
    3: "text-error bg-error-light-9",
};
const tabBadgeClass = (status: number) => TAB_BADGE_CLASS[status] || TAB_BADGE_CLASS[0];

const PLATFORM_STYLE: Record<number, { bg: string; color: string }> = {
    [AppTypeEnum.SPH]: { bg: "#f0fdfa", color: "#0d9488" },
    [AppTypeEnum.XHS]: { bg: "#fdf2f8", color: "#db2777" },
    [AppTypeEnum.DOUYIN]: { bg: "#eff6ff", color: "#2563eb" },
    [AppTypeEnum.KUAISHOU]: { bg: "#fff7ed", color: "#f97316" },
};
const DEFAULT_PLATFORM_STYLE = { bg: "#f3f4f6", color: "#6b7280" };
const platformBadgeStyle = (platform: number) => {
    const s = PLATFORM_STYLE[platform] || DEFAULT_PLATFORM_STYLE;
    return { background: s.bg, color: s.color };
};

const platformShort = (name: string) => (name ? name.slice(0, 1) : "?");

const shortDeviceCode = (code: string) => (code ? code.slice(0, 8) : "");
</script>
