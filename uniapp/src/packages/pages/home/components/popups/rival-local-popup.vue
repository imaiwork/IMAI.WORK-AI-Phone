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
                        <view class="w-[64rpx] h-[64rpx] rounded-[20rpx] bg-[#faf5ff] flex items-center justify-center">
                            <image :src="mapPinIcon" mode="aspectFit" class="w-[32rpx] h-[32rpx]" />
                        </view>
                        <view>
                            <text class="block text-sm font-bold text-[#1f2937]">
                                {{ overview.title || "找附近的客户" }}
                            </text>
                            <text class="block mt-[4rpx] text-xs text-[#9ca3af]">
                                {{ overview.subtitle || "同城曝光/电子传单找客户" }}
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
                        <!-- 执行时间（time_tabs 带状态色，缺省回退 execute_times）+ 前往修改 -->
                        <view class="flex items-start justify-between gap-[24rpx]">
                            <view class="flex-1 min-w-0">
                                <text class="cfg-label">执行时间</text>
                                <view v-if="timeSlots.length" class="flex flex-wrap gap-[12rpx]">
                                    <view
                                        v-for="slot in timeSlots"
                                        :key="slot.slot_key"
                                        class="cfg-chip"
                                        :class="timeSlotColor(slot.status)">
                                        <text>{{ slot.time_range }}</text>
                                        <text v-if="slot.status_text" class="ml-[8rpx] opacity-70">
                                            {{ slot.status_text }}
                                        </text>
                                    </view>
                                </view>
                                <view v-else-if="config.execute_times.length" class="flex flex-wrap gap-[12rpx]">
                                    <text
                                        v-for="time in config.execute_times"
                                        :key="time"
                                        class="cfg-chip opt-chip--off">
                                        {{ time }}
                                    </text>
                                </view>
                                <text v-else class="text-xs text-[#c0c4cc]">暂无执行时间</text>
                            </view>
                            <view
                                class="flex-shrink-0 flex items-center gap-[8rpx] text-xs font-medium text-primary bg-primary-light-9 px-[20rpx] py-[12rpx] rounded-full"
                                @click="emit('edit-tracking')">
                                <image :src="settingsIcon" mode="aspectFit" class="w-[24rpx] h-[24rpx]" />
                                <text>前往修改</text>
                            </view>
                        </view>

                        <!-- 距离范围：后端给整组选项，高亮当前生效项（只读） -->
                        <view v-if="config.distance.options.length">
                            <text class="cfg-label">距离范围</text>
                            <view class="flex flex-wrap gap-[12rpx]">
                                <view
                                    v-for="opt in config.distance.options"
                                    :key="opt.value"
                                    class="opt-chip"
                                    :class="opt.value === config.distance.value ? 'opt-chip--on' : 'opt-chip--off'">
                                    {{ opt.label }}
                                </view>
                            </view>
                        </view>

                        <!-- 性别筛选 -->
                        <view v-if="config.gender.options.length">
                            <text class="cfg-label">性别筛选</text>
                            <view class="flex flex-wrap gap-[12rpx]">
                                <view
                                    v-for="opt in config.gender.options"
                                    :key="opt.value"
                                    class="opt-chip"
                                    :class="opt.value === config.gender.value ? 'opt-chip--on' : 'opt-chip--off'">
                                    {{ opt.label }}
                                </view>
                            </view>
                        </view>

                        <!-- 目标用户名不包含 -->
                        <view v-if="config.filter_nickname.length">
                            <text class="cfg-label">目标用户名不包含</text>
                            <view class="flex flex-wrap gap-[16rpx]">
                                <text
                                    v-for="word in config.filter_nickname"
                                    :key="word"
                                    class="cfg-chip cfg-chip--error">
                                    {{ word }}
                                </text>
                            </view>
                        </view>

                        <!-- 评论命中关键词 -->
                        <view v-if="config.comment_hit_keywords.length">
                            <text class="cfg-label">评论命中关键词</text>
                            <view class="flex flex-wrap gap-[16rpx]">
                                <text
                                    v-for="keyword in config.comment_hit_keywords"
                                    :key="keyword"
                                    class="cfg-chip cfg-chip--warning">
                                    {{ keyword }}
                                </text>
                            </view>
                        </view>
                    </view>

                    <view class="bg-white rounded-[32rpx] overflow-hidden">
                        <view
                            class="px-[32rpx] py-[24rpx] border-b-[2rpx] border-[#f9fafb] flex items-center gap-[12rpx]">
                            <text class="text-sm font-bold text-[#1f2937]">当天找到的客户</text>
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
import { getSameCityTouch } from "@/api/person";

interface PlatformTab {
    platform_type: number | "all";
    platform_name: string;
    count: number;
}

interface TimeSlot {
    slot_key: string;
    time_range: string;
    status: number;
    status_text: string;
}

interface ConfigOption {
    value: number;
    label: string;
}

interface OptionGroup {
    value: number;
    options: ConfigOption[];
}

const props = defineProps<{
    modelValue: boolean;
    personaId: string | number;
    expandedCustomerId: string;
    mapPinIcon: string;
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

// 时间段状态：0=待执行 1=执行中 2=已完成 3=失败 4=中断
enum TimeSlotStatus {
    WAITING = 0,
    RUNNING = 1,
    DONE = 2,
    FAILED = 3,
    INTERRUPTED = 4,
}
const TIME_SLOT_COLOR: Record<number, string> = {
    [TimeSlotStatus.WAITING]: "text-[#6b7280] bg-[#f3f4f6]",
    [TimeSlotStatus.RUNNING]: "text-primary bg-primary-light-9",
    [TimeSlotStatus.DONE]: "text-success bg-success-light-9",
    [TimeSlotStatus.FAILED]: "text-error bg-error-light-9",
    [TimeSlotStatus.INTERRUPTED]: "text-warning bg-warning-light-9",
};
const timeSlotColor = (status: number) => TIME_SLOT_COLOR[status] ?? TIME_SLOT_COLOR[TimeSlotStatus.WAITING];

// 记录动作：优先 action_key，回退 action_type（1=评论 2=私信 3=点赞 4=电子传单）
const ACTION_KEY_TO_KEY: Record<string, RivalCustomerAction> = {
    comment: "comment",
    private_message: "private",
    like: "like",
    electronic_flyer: "flyer",
};
const ACTION_TYPE_TO_KEY: Record<number, RivalCustomerAction> = {
    1: "comment",
    2: "private",
    3: "like",
    4: "flyer",
};

const tabs = ref<PlatformTab[]>([]);
const activePlatform = ref<number | "all">("all");
const customers = ref<RivalInternetCustomer[]>([]);
const timeSlots = ref<TimeSlot[]>([]);
const overview = reactive({ title: "", subtitle: "" });
const config = reactive({
    distance: { value: 0, options: [] as ConfigOption[] } as OptionGroup,
    gender: { value: 0, options: [] as ConfigOption[] } as OptionGroup,
    filter_nickname: [] as string[],
    comment_hit_keywords: [] as string[],
    execute_times: [] as string[],
});
const pagingRef = shallowRef<any>();

// 后端返回的「当前值 + 选项组」结构归一化；当前值不在选项里时补一个高亮项，保证可见
const normalizeOptionGroup = (raw: any): OptionGroup => {
    const options: ConfigOption[] = Array.isArray(raw?.options)
        ? raw.options.map((o: any) => ({ value: Number(o.value), label: String(o.label ?? "") }))
        : [];
    const value = Number(raw?.value) || 0;
    if (raw?.label && !options.some((o) => o.value === value)) {
        options.unshift({ value, label: String(raw.label) });
    }
    return { value, options };
};

const normalizeCustomer = (raw: any): RivalInternetCustomer => ({
    id: String(raw.id ?? raw.record_id ?? ""),
    avatar: raw.avatar || "",
    nickname: raw.account_name || raw.nickname || "未知客户",
    action: ACTION_KEY_TO_KEY[raw.action_key] || ACTION_TYPE_TO_KEY[Number(raw.action_type)] || "flyer",
    douyin_id: raw.account || "",
    screenshot_url: raw.image || raw.screenshot_url || "",
    matched_keyword: raw.hit_keyword || raw.filter_keyword || raw.industry_keyword || "",
    content_text: raw.message_content || raw.comment_content || raw.touch_content || raw.source_content || "",
    time: raw.exec_time || raw.send_time || raw.create_time || "",
    platform_name: raw.platform_name || "",
});

const applyExtend = (ext: any) => {
    overview.title = ext.title || "";
    overview.subtitle = ext.subtitle || "";
    const c = ext.config || {};
    Object.assign(config.distance, normalizeOptionGroup(c.distance_range));
    Object.assign(config.gender, normalizeOptionGroup(c.gender_filter));
    config.filter_nickname = Array.isArray(c.filter_nickname) ? c.filter_nickname : [];
    config.comment_hit_keywords = Array.isArray(c.comment_hit_keywords) ? c.comment_hit_keywords : [];
    config.execute_times = Array.isArray(c.execute_times) ? c.execute_times : [];
    timeSlots.value = Array.isArray(ext.time_tabs)
        ? ext.time_tabs.map((slot: any) => ({
              slot_key: String(slot.slot_key ?? slot.time_range ?? ""),
              time_range: slot.time_range || "",
              status: Number(slot.status) || 0,
              status_text: slot.status_text || "",
          }))
        : [];
    if (Array.isArray(ext.tabs)) tabs.value = ext.tabs;
};

const queryList = async (page_no: number, page_size: number) => {
    if (!props.personaId) {
        pagingRef.value?.complete([]);
        return;
    }
    try {
        const data: any = await getSameCityTouch({
            persona_id: props.personaId,
            platform_type: activePlatform.value === "all" ? "" : String(activePlatform.value),
            date: "",
            page_no,
            page_size,
        });
        applyExtend(data?.extend || {});
        const raw = Array.isArray(data?.lists) ? data.lists : [];
        pagingRef.value?.complete(raw.map(normalizeCustomer));
    } catch (error) {
        console.warn("getSameCityTouch failed", error);
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

<style lang="scss" scoped>
.cfg-label {
    @apply block text-xs text-[#9ca3af] mb-[12rpx];
}

.cfg-chip {
    @apply inline-block rounded-[12rpx] px-[20rpx] py-[8rpx] text-xs font-medium;
}

.cfg-chip--error {
    @apply text-error bg-error-light-9;
}

.cfg-chip--warning {
    @apply text-warning bg-warning-light-9;
}

// 配置选项（距离/性别）：整组展示，高亮当前生效项，只读
.opt-chip {
    @apply inline-block rounded-[12rpx] px-[20rpx] py-[8rpx] text-xs font-medium;
}

.opt-chip--on {
    @apply text-white bg-primary;
}

.opt-chip--off {
    @apply text-[#6b7280] bg-[#f3f4f6];
}
</style>
