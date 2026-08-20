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
                            class="w-[64rpx] h-[64rpx] rounded-[20rpx] bg-warning-light-9 flex items-center justify-center">
                            <image :src="headerIcon" mode="aspectFit" class="w-[32rpx] h-[32rpx]" />
                        </view>
                        <view>
                            <text class="block text-sm font-bold text-[#1f2937]">
                                {{ overview.title || "去同行门店附近找客户" }}
                            </text>
                            <text class="block mt-[4rpx] text-xs text-[#9ca3af]">
                                {{ overview.subtitle || "门店附近找客户" }}
                            </text>
                        </view>
                    </view>
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-[#f3f4f6] text-[#6b7280] flex items-center justify-center"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" size="28" color="#6b7280"></u-icon>
                    </view>
                </view>
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
                                <text class="block text-xs text-[#9ca3af] mb-[12rpx]"> 团购检索词 </text>
                                <view class="flex flex-wrap gap-[16rpx]">
                                    <text
                                        v-if="config.group_buy_keyword"
                                        class="rounded-[16rpx] px-[20rpx] py-[8rpx] text-xs text-primary bg-primary-light-9">
                                        {{ config.group_buy_keyword }}
                                    </text>
                                    <text v-else class="text-[22rpx] text-[#9ca3af]"> 未配置 </text>
                                </view>
                            </view>
                            <view
                                class="flex-shrink-0 flex items-center gap-[8rpx] text-xs font-medium text-primary bg-primary-light-9 px-[20rpx] py-[12rpx] rounded-full"
                                @click="emit('edit-tracking')">
                                <image :src="settingsIcon" mode="aspectFit" class="w-[24rpx] h-[24rpx]" />
                                <text>前往修改</text>
                            </view>
                        </view>
                        <view v-if="config.comment_keywords.length">
                            <text class="block text-xs text-[#9ca3af] mb-[12rpx]"> 评论命中关键词 </text>
                            <view class="flex flex-wrap gap-[16rpx]">
                                <text
                                    v-for="kw in config.comment_keywords"
                                    :key="kw"
                                    class="rounded-[16rpx] px-[20rpx] py-[8rpx] text-xs text-warning bg-warning-light-9">
                                    {{ kw }}
                                </text>
                            </view>
                        </view>
                        <view v-if="actionLabels.length">
                            <text class="block text-xs text-[#9ca3af] mb-[12rpx]"> 对用户进行动作 </text>
                            <view class="flex flex-wrap gap-[16rpx]">
                                <text
                                    v-for="label in actionLabels"
                                    :key="label"
                                    class="rounded-[16rpx] px-[20rpx] py-[8rpx] text-xs font-medium text-primary bg-primary-light-9">
                                    {{ label }}
                                </text>
                            </view>
                        </view>
                        <view class="flex flex-wrap gap-[16rpx]">
                            <view
                                v-for="item in summaryChips"
                                :key="item.label"
                                class="bg-[#f3f4f6] rounded-[16rpx] px-[16rpx] py-[8rpx]">
                                <text class="block text-[18rpx] text-[#9ca3af]">
                                    {{ item.label }}
                                </text>
                                <text class="block text-xs font-bold text-[#1f2937]">
                                    {{ item.value }}
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
import type { RivalCustomerAction, RivalInternetCustomer, RivalActionBadge } from "./rival-internet-popup.vue";
import { getGroupBuyReport } from "@/api/person";

const props = defineProps<{
    modelValue: boolean;
    personaId: string | number;
    expandedCustomerId: string;
    headerIcon: string;
    settingsIcon: string;
    badgeOf: (action: RivalCustomerAction) => RivalActionBadge;
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

const customers = ref<RivalInternetCustomer[]>([]);
const overview = reactive({ title: "", subtitle: "" });
const config = reactive({
    group_buy_keyword: "",
    comment_keywords: [] as string[],
    interactive_action: [] as string[],
});
const summary = reactive({
    private_message_count: 0,
    like_count: 0,
    comment_count: 0,
});
const pagingRef = shallowRef<any>();

const summaryChips = computed(() => [
    { label: "私信", value: summary.private_message_count },
    { label: "点赞", value: summary.like_count },
    { label: "评论", value: summary.comment_count },
]);

const ACTION_LABEL: Record<string, string> = {
    "1": "评论",
    "2": "私信",
    "3": "点赞",
};
const actionLabels = computed(() => config.interactive_action.map((k) => ACTION_LABEL[String(k)]).filter(Boolean));

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
    matched_keyword: raw.hit_keyword || raw.filter_keyword || raw.industry_keyword || "",
    content_text: raw.comment_content || raw.message_content || raw.content || "",
    time: raw.exec_time || raw.create_time || "",
    platform_name: raw.platform_name || "",
});

const queryList = async (page_no: number, page_size: number) => {
    if (!props.personaId) {
        pagingRef.value?.complete([]);
        return;
    }
    try {
        const data: any = await getGroupBuyReport({
            persona_id: props.personaId,
            page_no,
            page_size,
        });
        const ext = data?.extend || {};
        overview.title = ext.title || "";
        overview.subtitle = ext.subtitle || "";
        if (ext.config) {
            config.group_buy_keyword = String(ext.config.group_buy_keyword || "");
            config.comment_keywords = Array.isArray(ext.config.comment_keywords) ? ext.config.comment_keywords : [];
            config.interactive_action = Array.isArray(ext.config.interactive_action)
                ? ext.config.interactive_action.map(String)
                : [];
        }
        if (ext.summary) {
            summary.private_message_count = Number(ext.summary.private_message_count) || 0;
            summary.like_count = Number(ext.summary.like_count) || 0;
            summary.comment_count = Number(ext.summary.comment_count) || 0;
        }
        const raw = Array.isArray(data?.lists) ? data.lists : [];
        pagingRef.value?.complete(raw.map(normalizeCustomer));
    } catch (error) {
        console.warn("getGroupBuyReport failed", error);
        pagingRef.value?.complete(false);
    }
};

watch(
    () => props.modelValue,
    (v) => {
        if (v) nextTick(() => pagingRef.value?.reload());
    },
);
</script>
