<template>
    <popup-bottom
        v-model="show"
        height="88%"
        custom-class="bg-[#f4f5f9]"
        :mask-close-able="true"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="bg-white px-[40rpx] py-[24rpx] shadow-[0_4rpx_24rpx_rgba(0,0,0,0.06)]">
                <view class="w-[80rpx] h-[8rpx] mx-auto mb-[28rpx] bg-[#e5e7eb] rounded-full" />
                <view v-if="timeRange || statusText" class="flex items-center justify-between mb-[20rpx] pt-3">
                    <text
                        v-if="timeRange"
                        class="text-primary font-bold text-xs bg-primary-light-9 px-[20rpx] py-[8rpx] rounded-[12rpx]">
                        {{ timeRange }}
                    </text>
                    <view
                        v-if="statusText"
                        class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-[12rpx] text-xs font-medium"
                        :class="headerStatusStyle.cls">
                        <u-icon :name="headerStatusStyle.icon" :size="24"></u-icon>
                        <text>{{ statusText }}</text>
                    </view>
                </view>
                <view class="flex items-end justify-between gap-[24rpx]">
                    <view class="min-w-0 flex-1">
                        <text class="block text-[36rpx] font-bold text-[#1f2937]">今日爆款速递</text>
                        <view class="flex flex-wrap items-center gap-[8rpx] mt-[4rpx] text-xs text-[#9ca3af]">
                            <text>今日计划 {{ lists.length }} 条</text>
                            <text>·</text>
                            <text class="text-success font-semibold">成功 {{ successCount }} 条</text>
                            <text>·</text>
                            <text class="text-error font-semibold">未能仿写成功 {{ failCount }} 条</text>
                        </view>
                    </view>
                    <view
                        class="rounded-[16rpx] px-[20rpx] py-[8rpx] text-xs font-bold text-[#6b7280] bg-[#f3f4f6] whitespace-nowrap"
                        @click="goHistory">
                        历史爆款
                    </view>
                </view>
                <!-- 平台切换：抖音 / 小红书（account_type 4 / 3） -->
                <view class="flex gap-[16rpx] mt-[24rpx]">
                    <view
                        v-for="tab in platformTabs"
                        :key="tab.key"
                        class="flex-1 flex items-center justify-center gap-[12rpx] py-[20rpx] rounded-[20rpx] text-[26rpx] font-bold"
                        :style="platformTabStyle(tab)"
                        @click="switchPlatform(tab.accountType)">
                        <view
                            class="w-[32rpx] h-[32rpx] rounded-full flex items-center justify-center"
                            :style="{ background: tab.iconBg }">
                            <text class="text-[16rpx] font-extrabold" :style="{ color: tab.iconColor }">
                                {{ tab.short }}
                            </text>
                        </view>
                        <text>{{ tab.label }}</text>
                    </view>
                </view>
                <scroll-view
                    v-if="keywordTabs.length > 1"
                    scroll-x
                    class="mt-[24rpx] whitespace-nowrap"
                    show-scrollbar="false">
                    <view class="inline-flex gap-[16rpx]">
                        <view
                            v-for="tab in keywordTabs"
                            :key="tab.keyword"
                            class="flex-shrink-0 rounded-full px-[28rpx] py-[12rpx] text-xs font-bold whitespace-nowrap"
                            :class="keywordTabClass(tab.keyword)"
                            @click="switchTab(tab.keyword)">
                            <text>{{ tab.label }}</text>
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
                <view class="p-[32rpx] pb-[40rpx] flex flex-col gap-[32rpx]">
                    <view
                        v-for="item in lists"
                        :key="item.id"
                        class="bg-white rounded-[32rpx] overflow-hidden shadow-[0_4rpx_24rpx_rgba(0,0,0,0.05)]"
                        :class="
                            isViralFailed(item) ? 'border-[2rpx] border-[#fecaca]' : 'border-[2rpx] border-[#f3f4f6]'
                        ">
                        <!-- 未能仿写成功：展示状态 + remark，不提供重新生成 -->
                        <view v-if="isViralFailed(item)" class="p-[28rpx]">
                            <view class="flex items-center justify-between mb-[24rpx] gap-[16rpx]">
                                <text
                                    class="text-white rounded-full px-[16rpx] py-[4rpx] text-[20rpx] font-bold shrink-0"
                                    :style="{ background: platformColor(item.platform ?? item.account_type) }">
                                    {{ item.keyword || item.platform_name || "热点" }}
                                </text>
                                <view
                                    class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-full text-[22rpx] font-bold text-error bg-error-light-9 shrink-0">
                                    <u-icon name="close-circle" color="#EF4444" :size="28"></u-icon>
                                    <text>{{ getViralStatusLabel(item) }}</text>
                                </view>
                            </view>
                            <view
                                class="flex items-start gap-[20rpx] bg-[#fef2f2] border-[2rpx] border-[#fecaca] rounded-[24rpx] p-[24rpx]">
                                <u-icon
                                    name="error-circle"
                                    color="#F87171"
                                    :size="32"
                                    class="mt-[4rpx] shrink-0"></u-icon>
                                <view class="min-w-0 flex-1">
                                    <text class="block text-xs font-semibold text-error mb-[8rpx]">失败原因</text>
                                    <text class="text-[22rpx] text-[#dc2626] leading-[36rpx] break-all">
                                        {{
                                            item.remark ||
                                            getViralStatusLabel(item) ||
                                            "仿写任务异常中断，未生成仿写文案"
                                        }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <template v-else>
                            <view
                                v-if="item.image"
                                class="relative h-[320rpx] overflow-hidden"
                                @click="previewImage(item.image)">
                                <image :src="item.image" class="w-full h-full" mode="aspectFill" />
                                <view class="absolute inset-0 mask-bg" />
                                <view
                                    class="absolute left-[24rpx] right-[24rpx] bottom-[20rpx] flex items-center justify-between">
                                    <text
                                        class="text-white rounded-full px-[16rpx] py-[4rpx] text-[20rpx] font-bold"
                                        :style="{
                                            background: platformColor(item.platform ?? item.account_type),
                                        }">
                                        {{ item.keyword || item.platform_name }}
                                    </text>
                                </view>
                            </view>
                            <view class="p-[28rpx]">
                                <view v-if="item.keyword && !item.image" class="flex flex-wrap gap-[12rpx] mb-[16rpx]">
                                    <text
                                        class="text-[20rpx] font-bold rounded-full px-[16rpx] py-[4rpx]"
                                        :style="{
                                            background: platformBg(item.platform ?? item.account_type),
                                            color: platformColor(item.platform ?? item.account_type),
                                        }">
                                        {{ item.keyword }}
                                    </text>
                                </view>
                                <text class="block text-sm text-[#1f2937] font-bold leading-[40rpx] mb-[24rpx]">
                                    {{ item.title || item.copywriting?.title }}
                                </text>
                                <view
                                    v-if="item.rewritten_text || item.copywriting?.rewritten_text"
                                    class="bg-[#f9fafb] rounded-[24rpx] p-[24rpx] mb-[24rpx]">
                                    <text class="block text-[#9333ea] text-[22rpx] font-bold mb-[12rpx]">
                                        ✦ AI 仿写文案
                                    </text>
                                    <text class="text-xs text-[#4b5563] leading-[40rpx]">
                                        {{ item.rewritten_text || item.copywriting?.rewritten_text }}
                                    </text>
                                </view>
                                <view class="flex gap-[16rpx]">
                                    <view
                                        class="flex-1 flex items-center justify-center rounded-[24rpx] px-[24rpx] py-[20rpx] text-xs font-semibold text-[#4b5563] bg-[#f9fafb] border-[2rpx] border-[#f3f4f6]"
                                        @click="handleOpenScript(item)">
                                        查看原文案
                                    </view>
                                    <view
                                        class="flex-1 flex items-center justify-center rounded-[24rpx] px-[24rpx] py-[20rpx] text-xs font-semibold"
                                        :class="
                                            isXhs ? 'text-[#FF2E4D] bg-[#FFF1F3]' : 'text-primary bg-primary-light-9'
                                        "
                                        @click="copyLink(item)">
                                        {{ isXhs ? "复制原笔记链接" : "复制视频链接" }}
                                    </view>
                                </view>
                            </view>
                        </template>
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
import { getDeviceViralRecordList } from "@/api/person";
import { AppTypeEnum } from "@/enums/appEnums";

enum ViralRecordStatusEnum {
    START = 0,
    NO_COPY_VIDEO = 1,
    COPY_MISMATCH = 2,
    COZE_AI = 3,
    MATCHED = 4,
    ABNORMAL = 5,
    FALLBACK = 6,
    ERROR = 7,
}

const props = defineProps<{
    modelValue: boolean;
    personaId: string | number;
    timeRange?: string;
    taskStatus?: number;
    statusText?: string;
}>();

// 头部状态徽标：0=待执行 1=执行中 2=已完成 3=失败（数据由首页工作流任务传入）
const HOT_STATUS_STYLE: Record<number, { cls: string; icon: string }> = {
    0: { cls: "text-[#9ca3af] bg-[#f3f4f6]", icon: "clock" },
    1: { cls: "text-primary bg-primary-light-9", icon: "clock" },
    2: { cls: "text-success bg-success-light-9", icon: "checkmark-circle" },
    3: { cls: "text-error bg-error-light-9", icon: "close-circle" },
};
const headerStatusStyle = computed(() => HOT_STATUS_STYLE[Number(props.taskStatus)] || HOT_STATUS_STYLE[0]);

const VIRAL_RECORD_STATUS_FAIL = new Set<number>([
    ViralRecordStatusEnum.NO_COPY_VIDEO,
    ViralRecordStatusEnum.COPY_MISMATCH,
    ViralRecordStatusEnum.ABNORMAL,
    ViralRecordStatusEnum.ERROR,
]);

const VIRAL_RECORD_STATUS_LABEL: Record<number, string> = {
    [ViralRecordStatusEnum.START]: "开始",
    [ViralRecordStatusEnum.NO_COPY_VIDEO]: "无文案视频",
    [ViralRecordStatusEnum.COPY_MISMATCH]: "文案不符合",
    [ViralRecordStatusEnum.COZE_AI]: "直接由coze纯ai生成",
    [ViralRecordStatusEnum.MATCHED]: "符合条件",
    [ViralRecordStatusEnum.ABNORMAL]: "异常",
    [ViralRecordStatusEnum.FALLBACK]: "兜底",
    [ViralRecordStatusEnum.ERROR]: "错误记录",
};

const getViralRecordStatusLabel = (status: number): string => VIRAL_RECORD_STATUS_LABEL[status] || "未知状态";

const isViralRecordFailed = (status: number): boolean => VIRAL_RECORD_STATUS_FAIL.has(status);

// 爆款记录 status：0开始 1无文案视频 2文案不符合 3纯ai 4符合 5异常 6兜底 7错误记录
const getViralStatusLabel = (item: any) => getViralRecordStatusLabel(Number(item?.status));
const isViralFailed = (item: any) => isViralRecordFailed(Number(item?.status));

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "open-script", scriptText: string): void;
    (e: "toast", message: string): void;
    (e: "go", path: string): void;
}>();

// 历史爆款：跳人设详情页「内容记录」tab；无人设 id（演示）回退到人设列表
const goHistory = () => {
    if (props.personaId) {
        emit("go", `/ai_modules/person/pages/detail/detail?id=${props.personaId}&tab=history`);
    } else {
        emit("go", "/ai_modules/person/pages/index/index");
    }
};

const show = computed({
    get: () => props.modelValue,
    set: (v) => emit("update:modelValue", v),
});

/** 账号类型：0未知 1视频号 3小红书 4抖音 5快手 */
interface HotPlatformTab {
    key: string;
    label: string;
    short: string;
    accountType: AppTypeEnum;
    activeBg: string;
    iconBg: string;
    iconColor: string;
}

const platformTabs: HotPlatformTab[] = [
    {
        key: "douyin",
        label: "抖音",
        short: "抖",
        accountType: AppTypeEnum.DOUYIN,
        activeBg: "#111827",
        iconBg: "#ffffff",
        iconColor: "#111827",
    },
    {
        key: "xhs",
        label: "小红书",
        short: "红",
        accountType: AppTypeEnum.XHS,
        activeBg: "#FF2E4D",
        iconBg: "#FF2E4D",
        iconColor: "#ffffff",
    },
];

const activePlatform = ref<AppTypeEnum>(AppTypeEnum.DOUYIN);
const isXhs = computed(() => activePlatform.value === AppTypeEnum.XHS);

const platformTabStyle = (tab: HotPlatformTab) => {
    const active = activePlatform.value === tab.accountType;
    return {
        background: active ? tab.activeBg : "#F3F4F6",
        color: active ? "#ffffff" : "#9CA3AF",
    };
};

// 关键词 tab 选中色：小红书用品牌红，抖音用主色
const keywordTabClass = (keyword: string) => {
    if (activeKeyword.value !== keyword) return "text-[#4b5563] bg-[#f3f4f6]";
    return isXhs.value ? "text-white bg-[#FF2E4D]" : "text-white bg-primary";
};

// tab 来自接口 extend.keyword_list（关键词字符串数组），切换按 keyword 查询
const keywords = ref<string[]>([]);
const activeKeyword = ref("");
const lists = ref<any[]>([]);
const pagingRef = shallowRef<any>();

const failCount = computed(() => lists.value.filter((item) => isViralFailed(item)).length);
const successCount = computed(() => Math.max(lists.value.length - failCount.value, 0));

const keywordTabs = computed(() => [
    { label: "全部", keyword: "" },
    ...keywords.value.map((k) => ({ label: k, keyword: k })),
]);

const queryList = async (page_no: number, page_size: number) => {
    try {
        const data: any = await getDeviceViralRecordList({
            persona_id: props.personaId,
            is_interested: 1,
            keyword: activeKeyword.value,
            account_type: activePlatform.value,
            day: "",
            page_no,
            page_size,
        });
        keywords.value = Array.isArray(data?.extend?.keyword_list) ? data.extend.keyword_list : [];
        const items = Array.isArray(data?.lists) ? data.lists : [];
        pagingRef.value?.complete(items);
    } catch (error) {
        console.warn("getDeviceViralRecordList failed", error);
        pagingRef.value?.complete(false);
    }
};

const switchPlatform = (accountType: AppTypeEnum) => {
    if (activePlatform.value === accountType) return;
    activePlatform.value = accountType;
    activeKeyword.value = "";
    pagingRef.value?.reload();
};

const switchTab = (keyword: string) => {
    if (activeKeyword.value === keyword) return;
    activeKeyword.value = keyword;
    pagingRef.value?.reload();
};

watch(
    () => props.modelValue,
    (v) => {
        if (v) {
            activePlatform.value = AppTypeEnum.DOUYIN;
            activeKeyword.value = "";
            nextTick(() => pagingRef.value?.reload());
        } else {
            activeKeyword.value = "";
        }
    },
);

const PLATFORM_COLOR: Record<number, string> = {
    [AppTypeEnum.SPH]: "#16a34a",
    [AppTypeEnum.XHS]: "#ec4899",
    [AppTypeEnum.DOUYIN]: "#3b82f6",
    [AppTypeEnum.KUAISHOU]: "#f97316",
};
const PLATFORM_BG: Record<number, string> = {
    [AppTypeEnum.SPH]: "#f0fdfa",
    [AppTypeEnum.XHS]: "#fdf2f8",
    [AppTypeEnum.DOUYIN]: "#eff6ff",
    [AppTypeEnum.KUAISHOU]: "#fff7ed",
};
const platformColor = (p: number) => PLATFORM_COLOR[Number(p)] || (isXhs.value ? "#FF2E4D" : "#6b7280");
const platformBg = (p: number) => PLATFORM_BG[Number(p)] || (isXhs.value ? "#FFF1F3" : "#f3f4f6");

// 点击封面放大查看完整截图
const previewImage = (url: string) => {
    if (!url) return;
    uni.previewImage({ urls: [url], current: url });
};

/** 原文案：优先 original_text；小红书常见为空，回退 content / copywriting */
const getOriginalScript = (item: any): string => {
    const pick = (value: unknown) => String(value ?? "").trim();
    const fromOriginal = pick(item?.original_text);
    if (fromOriginal) return fromOriginal;

    const cw = item?.copywriting;
    if (cw && typeof cw === "object" && !Array.isArray(cw)) {
        const fromCw = pick(cw.original_text) || pick(cw.content) || pick(cw.text);
        if (fromCw) return fromCw;
    }

    const fromContent = pick(item?.content);
    if (fromContent) return fromContent;

    return "";
};

const handleOpenScript = (item: any) => {
    const script = getOriginalScript(item);
    if (!script) {
        emit("toast", "暂无原文案");
        return;
    }
    emit("open-script", script);
};

const copyLink = (item: any) => {
    const text = item.link || item.material_url || item.share_url || item.url || "";
    const emptyTip = isXhs.value ? "暂无笔记链接" : "暂无视频链接";
    const successTip = isXhs.value ? "原笔记链接已复制" : "视频链接已复制";
    if (!text) {
        emit("toast", emptyTip);
        return;
    }
    uni.setClipboardData({
        data: text,
        success: () => emit("toast", successTip),
    });
};
</script>

<style lang="scss" scoped>
.mask-bg {
    background: linear-gradient(to top, rgba(0, 0, 0, 0.65), rgba(0, 0, 0, 0.1), transparent);
}
</style>
