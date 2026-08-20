<template>
    <popup-bottom
        v-model="show"
        height="88%"
        custom-class="bg-[#f4f5f9]"
        :clearable="false"
        :mask-close-able="true"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view
                class="bg-white rounded-t-[32rpx] pt-[24rpx] pb-[32rpx] px-[40rpx] shadow-[0_4rpx_24rpx_rgba(0,0,0,0.06)]">
                <view class="w-[80rpx] h-[8rpx] mx-auto mb-[28rpx] bg-[#e5e7eb] rounded-full" />
                <view class="flex items-center justify-between">
                    <view class="flex items-center gap-[16rpx]">
                        <view
                            class="w-[64rpx] h-[64rpx] rounded-[20rpx] bg-primary-light-9 flex items-center justify-center">
                            <image :src="headerIcon" mode="aspectFit" class="w-[32rpx] h-[32rpx]" />
                        </view>
                        <view>
                            <text class="block text-sm font-bold text-[#1f2937]">找全网同行的客户</text>
                            <text class="block mt-[4rpx] text-xs text-[#9ca3af]">跨平台找客户</text>
                        </view>
                    </view>
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-[#f3f4f6] text-[#6b7280] flex items-center justify-center"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" size="28" color="#6b7280"></u-icon>
                    </view>
                </view>
                <scroll-view
                    v-if="tabs.length"
                    scroll-x
                    class="mt-[24rpx] whitespace-nowrap"
                    show-scrollbar="false">
                    <view class="inline-flex gap-[16rpx]">
                        <view
                            v-for="tab in tabs"
                            :key="String(tab.platform_type)"
                            class="flex-shrink-0 rounded-full px-[28rpx] py-[12rpx] text-xs font-bold whitespace-nowrap"
                            :class="
                                activePlatform === tab.platform_type
                                    ? 'text-white bg-primary'
                                    : 'text-[#4b5563] bg-[#f3f4f6]'
                            "
                            @click="switchPlatform(tab.platform_type)">
                            <text>{{ tab.platform_name }}（{{ tab.count }}）</text>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
        <template #content>
            <z-paging
                ref="pagingRef"
                v-model="customers"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="p-[32rpx] pb-[40rpx] flex flex-col gap-[24rpx]">
                    <view class="bg-white rounded-[32rpx] p-[32rpx] flex flex-col gap-[28rpx]">
                        <view class="flex items-start justify-between gap-[24rpx]">
                            <view class="flex-1 min-w-0">
                                <text class="block text-xs text-[#9ca3af] mb-[12rpx]">检索词</text>
                                <view class="flex flex-wrap gap-[16rpx]">
                                    <text
                                        v-for="term in config.search_keywords"
                                        :key="term"
                                        class="rounded-[16rpx] px-[20rpx] py-[8rpx] text-xs text-primary bg-primary-light-9">
                                        {{ term }}
                                    </text>
                                    <text
                                        v-if="!config.search_keywords.length"
                                        class="text-[22rpx] text-[#9ca3af]">
                                        暂无
                                    </text>
                                </view>
                            </view>
                            <view
                                class="flex-shrink-0 flex items-center gap-[8rpx] text-xs font-medium text-primary bg-primary-light-9 px-[20rpx] py-[12rpx] rounded-full"
                                @click="emit('edit-tracking')">
                                <image
                                    :src="settingsIcon"
                                    mode="aspectFit"
                                    class="w-[24rpx] h-[24rpx]" />
                                <text>前往修改</text>
                            </view>
                        </view>
                        <view>
                            <text class="block text-xs text-[#9ca3af] mb-[12rpx]">执行时间</text>
                            <view class="flex flex-wrap gap-[16rpx]">
                                <text
                                    v-for="time in config.execute_times"
                                    :key="time"
                                    class="rounded-[16rpx] px-[20rpx] py-[8rpx] text-xs font-medium text-[#4b5563] bg-[#f3f4f6]">
                                    {{ time }}
                                </text>
                                <text
                                    v-if="!config.execute_times.length"
                                    class="text-[22rpx] text-[#9ca3af]">
                                    未配置
                                </text>
                            </view>
                        </view>
                        <view>
                            <text class="block text-xs text-[#9ca3af] mb-[12rpx]">命中关键词</text>
                            <view class="flex flex-wrap gap-[16rpx]">
                                <text
                                    v-for="kw in config.hit_keywords"
                                    :key="kw"
                                    class="rounded-[16rpx] px-[20rpx] py-[8rpx] text-xs text-warning bg-warning-light-9">
                                    {{ kw }}
                                </text>
                                <text
                                    v-if="!config.hit_keywords.length"
                                    class="text-[22rpx] text-[#9ca3af]">
                                    暂无
                                </text>
                            </view>
                        </view>
                    </view>

                    <view class="bg-white rounded-[32rpx] overflow-hidden">
                        <view
                            class="px-[32rpx] py-[24rpx] border-b-[2rpx] border-[#f9fafb] flex items-center gap-[12rpx]">
                            <text class="text-sm font-bold text-[#1f2937]">客户列表</text>
                            <text class="text-xs text-[#9ca3af]">{{ customers.length }} 条</text>
                        </view>
                        <rival-customer-row
                            v-for="customer in customers"
                            :key="customer.id"
                            :customer="customer"
                            :expanded="expandedCustomerId === customer.id"
                            :badge="badgeOf(customer.action)"
                            @toggle="emit('toggle-customer', $event)"
                            @copy="emit('copy', $event)"
                            @preview="emit('preview', $event)" />
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import RivalCustomerRow from "../rival-customer-row.vue";
import { getLeadScrapingReport } from "@/api/person";

export type RivalCustomerAction = "private" | "comment" | "like" | "flyer";

export interface RivalInternetCustomer {
    id: string;
    avatar: string;
    nickname: string;
    action: RivalCustomerAction;
    douyin_id: string;
    screenshot_url: string;
    matched_keyword: string;
    content_label?: string;
    content_text?: string;
    time: string;
    platform_name?: string;
}

export interface RivalActionBadge {
    label: string;
    cls: string;
}

interface PlatformTab {
    platform_type: number | "all";
    platform_name: string;
    count: number;
}

const props = defineProps<{
    modelValue: boolean;
    personaId: string | number;
    headerIcon: string;
    settingsIcon: string;
    badgeOf: (action: RivalCustomerAction) => RivalActionBadge;
    expandedCustomerId: string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "edit-tracking"): void;
    (e: "toggle-customer", id: string): void;
    (e: "copy", text: string): void;
    (e: "preview", url: string): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v) => emit("update:modelValue", v),
});

const tabs = ref<PlatformTab[]>([]);
const activePlatform = ref<number | "all">("all");
const customers = ref<RivalInternetCustomer[]>([]);
const config = reactive({
    search_keywords: [] as string[],
    hit_keywords: [] as string[],
    execute_times: [] as string[],
});
const pagingRef = shallowRef<any>();

// action_type: 1=评论 2=私信 3=点赞，其他归为传单
const ACTION_TYPE_TO_KEY: Record<number, RivalCustomerAction> = {
    1: "comment",
    2: "private",
    3: "like",
};
const normalizeCustomer = (raw: any): RivalInternetCustomer => ({
    id: String(raw.id ?? raw.record_id ?? ""),
    avatar: raw.avatar || "",
    nickname: raw.account_name || raw.nickname || "未知客户",
    action: ACTION_TYPE_TO_KEY[Number(raw.action_type)] || "flyer",
    douyin_id: raw.account || "",
    screenshot_url: raw.image || raw.screenshot_url || "",
    matched_keyword: raw.filter_keyword || raw.industry_keyword || raw.hit_keyword || "",
    content_text: raw.comment_content || raw.content || raw.touch_content || "",
    time: raw.exec_time || raw.create_time || "",
    platform_name: raw.platform_name || "",
});

const queryList = async (page_no: number, page_size: number) => {
    if (!props.personaId) {
        pagingRef.value?.complete([]);
        return;
    }
    try {
        const data: any = await getLeadScrapingReport({
            persona_id: props.personaId,
            platform_type:
                activePlatform.value === "all" ? "" : String(activePlatform.value),
            date: "",
            page_no,
            page_size,
        });
        const ext = data?.extend || {};
        if (ext.config) {
            config.search_keywords = Array.isArray(ext.config.search_keywords)
                ? ext.config.search_keywords
                : [];
            config.hit_keywords = Array.isArray(ext.config.hit_keywords)
                ? ext.config.hit_keywords
                : [];
            config.execute_times = Array.isArray(ext.config.execute_times)
                ? ext.config.execute_times
                : [];
        }
        if (Array.isArray(ext.tabs)) tabs.value = ext.tabs;
        const raw = Array.isArray(data?.lists) ? data.lists : [];
        pagingRef.value?.complete(raw.map(normalizeCustomer));
    } catch (error) {
        console.warn("getLeadScrapingReport failed", error);
        pagingRef.value?.complete(false);
    }
};

const switchPlatform = (key: number | "all") => {
    if (activePlatform.value === key) return;
    activePlatform.value = key;
    pagingRef.value?.reload();
};

watch(
    () => props.modelValue,
    (v) => {
        if (v) {
            activePlatform.value = "all";
            nextTick(() => pagingRef.value?.reload());
        }
    },
);
</script>
