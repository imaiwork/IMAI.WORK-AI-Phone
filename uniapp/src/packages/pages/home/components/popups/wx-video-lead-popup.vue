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
                            class="w-[64rpx] h-[64rpx] rounded-[20rpx] bg-success-light-9 flex items-center justify-center">
                            <image :src="videoIcon" mode="aspectFit" class="w-[32rpx] h-[32rpx]" />
                        </view>
                        <view>
                            <text class="block text-sm font-bold text-[#1f2937]">
                                {{ overview.title || "B端招商获客" }}
                            </text>
                            <text class="block mt-[4rpx] text-xs text-[#9ca3af]">
                                {{ overview.subtitle || "视频号引流招商" }}
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
                v-model="leads"
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
                                        v-for="term in config.clue_keywords"
                                        :key="term"
                                        class="rounded-[16rpx] px-[20rpx] py-[8rpx] text-xs text-primary bg-primary-light-9">
                                        {{ term }}
                                    </text>
                                    <text
                                        v-if="!config.clue_keywords.length"
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
                        <view v-if="config.execute_times.length">
                            <text class="block text-xs text-[#9ca3af] mb-[12rpx]">执行时间</text>
                            <view class="flex flex-wrap gap-[16rpx]">
                                <text
                                    v-for="time in config.execute_times"
                                    :key="time"
                                    class="rounded-[16rpx] px-[20rpx] py-[8rpx] text-xs font-medium text-[#4b5563] bg-[#f3f4f6]">
                                    {{ time }}
                                </text>
                            </view>
                        </view>
                        <view v-if="summary.today_count" class="flex flex-wrap gap-[16rpx]">
                            <view class="bg-[#f3f4f6] rounded-[16rpx] px-[16rpx] py-[8rpx]">
                                <text class="block text-[18rpx] text-[#9ca3af]">今日触达</text>
                                <text class="block text-xs font-bold text-[#1f2937]">
                                    {{ summary.today_count }}
                                </text>
                            </view>
                        </view>
                    </view>

                    <view class="bg-white rounded-[32rpx] overflow-hidden">
                        <view
                            class="px-[32rpx] py-[24rpx] border-b-[2rpx] border-[#f9fafb] flex items-center gap-[12rpx]">
                            <text class="text-sm font-bold text-[#1f2937]">招商客户列表</text>
                            <text class="text-xs text-[#9ca3af]">{{ leads.length }} 个</text>
                        </view>
                        <view
                            v-for="lead in leads"
                            :key="lead.id"
                            class="px-[28rpx] py-[24rpx] border-b-[2rpx] border-[#f9fafb] last:border-b-0">
                            <view class="flex items-center justify-between mb-[12rpx]">
                                <text class="text-sm font-bold text-[#1f2937] line-clamp-1">
                                    {{ lead.account }}
                                </text>
                                <text
                                    v-if="lead.fans"
                                    class="text-xs text-[#9ca3af] bg-[#f9fafb] rounded-full px-[16rpx] py-[4rpx]">
                                    {{ lead.fans }}
                                </text>
                            </view>
                            <view v-if="lead.ip" class="flex items-center gap-[8rpx] mb-[20rpx]">
                                <image
                                    :src="mapPinIcon"
                                    mode="aspectFit"
                                    class="w-[22rpx] h-[22rpx] flex-shrink-0" />
                                <text class="text-[22rpx] text-[#9ca3af]">地区：</text>
                                <text class="text-[22rpx] text-[#4b5563] font-medium">
                                    {{ lead.ip }}
                                </text>
                            </view>
                            <view
                                v-if="lead.contacts.length"
                                class="bg-[#f9fafb] rounded-[20rpx] px-[24rpx] py-[8rpx] mb-[20rpx] flex flex-col">
                                <view
                                    v-for="contact in lead.contacts"
                                    :key="contact.type + contact.value"
                                    class="flex items-center gap-[16rpx] py-[12rpx] border-b-[2rpx] border-[#f3f4f6] last:border-b-0">
                                    <text
                                        class="rounded-full px-[16rpx] py-[4rpx] text-[20rpx] font-semibold flex-shrink-0"
                                        :class="contactBadge(contact.type).cls">
                                        {{ contactBadge(contact.type).label }}
                                    </text>
                                    <text
                                        class="flex-1 text-xs text-[#374151] font-medium line-clamp-1">
                                        {{ contact.value }}
                                    </text>
                                    <view
                                        class="flex items-center gap-[6rpx] text-[20rpx] text-primary bg-white border-[2rpx] border-[#dbeafe] px-[12rpx] py-[6rpx] rounded-[10rpx]"
                                        @click="emit('copy', contact.value)">
                                        <image
                                            :src="copyIcon"
                                            mode="aspectFit"
                                            class="w-[22rpx] h-[22rpx]" />
                                        <text>复制</text>
                                    </view>
                                </view>
                            </view>
                            <view class="flex items-center justify-between">
                                <view class="flex items-center gap-[8rpx]">
                                    <image
                                        :src="clockIcon"
                                        mode="aspectFit"
                                        class="w-[22rpx] h-[22rpx] flex-shrink-0" />
                                    <text class="text-[22rpx] text-[#9ca3af]">{{ lead.time }}</text>
                                </view>
                                <view
                                    v-if="lead.screenshot_url"
                                    class="flex items-center gap-[6rpx] text-[22rpx] text-primary bg-primary-light-9 px-[16rpx] py-[6rpx] rounded-full"
                                    @click="emit('preview', lead.screenshot_url)">
                                    <image
                                        :src="imageIcon"
                                        mode="aspectFit"
                                        class="w-[22rpx] h-[22rpx]" />
                                    <text>查看截图</text>
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
</template>

<script setup lang="ts">
import { getClueCustomer } from "@/api/person";

export type WxLeadContactType = "wechat" | "phone" | "work_wechat";

export interface WxLeadContact {
    type: WxLeadContactType;
    value: string;
}

export interface WxVideoLead {
    id: string;
    account: string;
    ip: string;
    fans: string;
    contacts: WxLeadContact[];
    time: string;
    screenshot_url?: string;
}

export interface WxLeadContactBadge {
    label: string;
    cls: string;
}

const props = defineProps<{
    modelValue: boolean;
    personaId: string | number;
    videoIcon: string;
    settingsIcon: string;
    mapPinIcon: string;
    copyIcon: string;
    clockIcon: string;
    imageIcon: string;
    contactBadge: (type: WxLeadContactType) => WxLeadContactBadge;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "edit-tracking"): void;
    (e: "copy", text: string): void;
    (e: "preview", url: string): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v) => emit("update:modelValue", v),
});

const overview = reactive({ title: "", subtitle: "" });
const config = reactive({
    clue_keywords: [] as string[],
    execute_times: [] as string[],
});
const summary = reactive({ today_count: 0 });
const leads = ref<WxVideoLead[]>([]);
const pagingRef = shallowRef<any>();

const normalizeLead = (raw: any): WxVideoLead => {
    const contacts: WxLeadContact[] = [];
    if (raw.wechat) contacts.push({ type: "wechat", value: String(raw.wechat) });
    if (raw.phone) contacts.push({ type: "phone", value: String(raw.phone) });
    return {
        id: String(raw.id ?? ""),
        account: raw.username || raw.task_name || "未知客户",
        ip: raw.address || "",
        fans: raw.fans_text || "",
        contacts,
        time: raw.exec_time || "",
        screenshot_url: raw.image || "",
    };
};

const queryList = async (page_no: number, page_size: number) => {
    if (!props.personaId) {
        pagingRef.value?.complete([]);
        return;
    }
    try {
        const data: any = await getClueCustomer({
            persona_id: props.personaId,
            page_no,
            page_size,
        });
        const ext = data?.extend || {};
        overview.title = ext.title || "";
        overview.subtitle = ext.subtitle || "";
        if (ext.config) {
            config.clue_keywords = Array.isArray(ext.config.clue_keywords)
                ? ext.config.clue_keywords
                : [];
            config.execute_times = Array.isArray(ext.config.execute_times)
                ? ext.config.execute_times
                : [];
        }
        if (ext.summary) {
            summary.today_count = Number(ext.summary.today_count) || 0;
        }
        const raw = Array.isArray(data?.lists) ? data.lists : [];
        pagingRef.value?.complete(raw.map(normalizeLead));
    } catch (error) {
        console.warn("getClueCustomer failed", error);
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
