<template>
    <view class="h-screen flex flex-col bg-[#F4F6FA]">
        <view class="effect-hero shrink-0">
            <u-navbar
                title="效果统计"
                title-bold
                title-color="#FFFFFF"
                back-icon-color="#FFFFFF"
                :border-bottom="false"
                :background="{ background: 'transparent' }"
                :is-fixed="false">
            </u-navbar>

            <view v-if="pageLoading" class="px-[32rpx] pt-[18rpx] pb-[72rpx]">
                <view class="flex items-center">
                    <view v-for="item in 3" :key="item" class="flex items-center flex-1">
                        <view class="flex-1 flex flex-col items-center py-[26rpx]">
                            <view class="skeleton-hero-block w-[88rpx] h-[52rpx]"></view>
                            <view class="skeleton-hero-block w-[104rpx] h-[24rpx] mt-[16rpx]"></view>
                        </view>
                        <view v-if="item < 3" class="w-[2rpx] h-[88rpx] bg-white opacity-20"></view>
                    </view>
                </view>
            </view>

            <view v-else class="px-[32rpx] pt-[18rpx] pb-[72rpx]">
                <view class="flex items-center">
                    <view v-for="(item, index) in heroStats" :key="item.label" class="flex items-center flex-1">
                        <view class="flex-1 flex flex-col items-center py-[26rpx]">
                            <view class="flex items-baseline">
                                <text class="text-[56rpx] font-bold text-white leading-none">{{ item.value }}</text>
                                <text v-if="item.unit" class="text-[22rpx] text-white opacity-70 ml-[4rpx]">{{
                                    item.unit
                                }}</text>
                            </view>
                            <text class="text-[22rpx] mt-[12rpx] text-white opacity-60">{{ item.label }}</text>
                        </view>
                        <view v-if="index < heroStats.length - 1" class="w-[2rpx] h-[88rpx] bg-white opacity-20"></view>
                    </view>
                </view>
            </view>
        </view>

        <view class="grow min-h-0 -mt-[44rpx] relative z-10">
            <scroll-view
                scroll-y
                class="h-full"
                :refresher-enabled="!pageLoading && !dateSwitchLoading"
                :refresher-triggered="refreshing"
                @refresherrefresh="handlePullRefresh">
                <effect-statistics-skeleton v-if="pageLoading" />

                <view v-else class="px-[32rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                    <view
                        class="bg-white rounded-[36rpx] px-[40rpx] py-[32rpx] flex items-center justify-between effect-card-shadow">
                        <view class="flex-1 min-w-0">
                            <view class="flex items-center gap-[16rpx] mb-[20rpx]">
                                <view
                                    class="text-[22rpx] font-semibold px-[20rpx] py-[8rpx] rounded-[18rpx] bg-[#EEF1FF] text-[#2845F5]">
                                    工作日志
                                </view>
                                <text class="text-[22rpx] text-[#9CA3AF]">按执行过程统计</text>
                            </view>
                            <view class="flex items-center gap-[12rpx]">
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center active:bg-[#F3F4F6]"
                                    :class="{ 'opacity-60': dateSwitchLoading }"
                                    @click="handleDateStep(-1)">
                                    <u-icon name="arrow-left" color="#9CA3AF" size="24"></u-icon>
                                </view>
                                <text class="text-[48rpx] font-bold text-[#111827] leading-none">{{ dateLabel }}</text>
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center active:bg-[#F3F4F6]"
                                    :class="{ 'opacity-60': dateSwitchLoading }"
                                    @click="handleDateStep(1)">
                                    <u-icon name="arrow-right" color="#9CA3AF" size="24"></u-icon>
                                </view>
                            </view>
                        </view>
                        <view
                            class="w-[96rpx] h-[96rpx] rounded-[28rpx] bg-[#2845F5] flex items-center justify-center shrink-0"
                            @click="handleRefresh">
                            <image :src="ActivityWhiteIcon" class="w-[48rpx] h-[48rpx]"></image>
                        </view>
                    </view>

                    <view class="mt-[24rpx] flex flex-col gap-[24rpx]">
                        <section-card title="核心任务执行">
                            <view class="grid grid-cols-3 gap-[16rpx]">
                                <metric-tile
                                    v-for="item in coreTasks"
                                    :key="item.label"
                                    :icon="item.icon"
                                    :label="item.label"
                                    :value="item.value"
                                    :unit="item.unit"
                                    :tag="item.tag"
                                    :tag-type="item.tagType" />
                            </view>
                        </section-card>

                        <section-card title="同行获客" :badge="peerBadge.text" :badge-type="peerBadge.type">
                            <view class="grid grid-cols-2 gap-[20rpx]">
                                <lead-tile
                                    v-for="item in leadStats"
                                    :key="item.label"
                                    :icon="item.icon"
                                    :label="item.label"
                                    :value="item.value"
                                    :unit="item.unit"
                                    :desc="item.desc" />
                            </view>
                        </section-card>

                        <section-card title="私信与社群值守">
                            <view class="flex flex-col gap-[12rpx]">
                                <guard-row
                                    v-for="item in guardRows"
                                    :key="item.label"
                                    :icon="item.icon"
                                    :label="item.label"
                                    :desc="item.desc"
                                    :value="item.value"
                                    :unit="item.unit"
                                    :tag="item.tag"
                                    :tag-type="item.tagType" />
                            </view>
                        </section-card>

                        <section-card title="朋友圈日常维护">
                            <view class="grid grid-cols-2 gap-[20rpx]">
                                <metric-tile
                                    v-for="item in circleStats"
                                    :key="item.label"
                                    :icon="item.icon"
                                    :label="item.label"
                                    :value="item.value"
                                    :unit="item.unit"
                                    :tag="item.tag"
                                    :tag-type="item.tagType"
                                    size="large" />
                            </view>
                        </section-card>

                        <view class="text-center text-[22rpx] text-[#9CA3AF] pt-[8rpx]">
                            · 数据每日零点自动刷新 ·
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view
            v-if="dateSwitchLoading"
            class="fixed inset-0 z-[999] flex items-center justify-center bg-[rgba(17,24,39,0.28)]">
            <view
                class="w-[300rpx] rounded-[32rpx] bg-white px-[32rpx] py-[36rpx] flex flex-col items-center effect-loading-shadow">
                <view class="w-[84rpx] h-[84rpx] rounded-full bg-[#EEF2FF] flex items-center justify-center">
                    <u-loading mode="circle" size="42" color="#2845F5"></u-loading>
                </view>
                <text class="text-[28rpx] font-semibold text-[#111827] mt-[22rpx]"> 切换日期中 </text>
                <text class="text-[22rpx] text-[#9CA3AF] mt-[10rpx]">{{ dateSwitchText }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getEffectStatistics } from "@/api/app";
import ActivityWhiteIcon from "@/packages/static/icons/effect-statistics/activity-white.svg";
import CalendarCheckIcon from "@/packages/static/icons/effect-statistics/calendar-check.svg";
import CrosshairIcon from "@/packages/static/icons/effect-statistics/crosshair.svg";
import HeartIcon from "@/packages/static/icons/effect-statistics/heart.svg";
import LayersIcon from "@/packages/static/icons/effect-statistics/layers.svg";
import MessageCircleIcon from "@/packages/static/icons/effect-statistics/message-circle.svg";
import MessageSquareIcon from "@/packages/static/icons/effect-statistics/message-square.svg";
import SendIcon from "@/packages/static/icons/effect-statistics/send.svg";
import SmartphoneIcon from "@/packages/static/icons/effect-statistics/smartphone.svg";
import TrendingUpIcon from "@/packages/static/icons/effect-statistics/trending-up.svg";
import UsersIcon from "@/packages/static/icons/effect-statistics/users.svg";
import EffectStatisticsSkeleton from "./components/effect-statistics-skeleton.vue";
import GuardRow from "./components/guard-row.vue";
import LeadTile from "./components/lead-tile.vue";
import MetricTile from "./components/metric-tile.vue";
import SectionCard from "./components/section-card.vue";

type TagType = "primary" | "success" | "warning";

interface StatisticsData {
    exposure: number;
    leads: number;
    rate: number;
    peerHasOutput: boolean;
    coreTasks: Array<MetricItem>;
    leadStats: Array<LeadItem>;
    guardRows: Array<GuardItem>;
    circleStats: Array<MetricItem>;
}

interface MetricItem {
    label: string;
    value: number;
    unit: string;
    tag: string;
    tagType?: TagType;
    icon: string;
}

interface LeadItem {
    label: string;
    value: number;
    unit: string;
    desc: string;
    icon: string;
}

interface GuardItem {
    label: string;
    desc: string;
    value: number;
    unit: string;
    tag: string;
    tagType?: TagType;
    icon: string;
}

interface EffectStatisticsResponse {
    date?: string;
    date_text?: string;
    top?: {
        today_expose_number?: number;
        today_clue_number?: number;
        today_rate?: number;
    };
    core_tasks?: {
        material_count?: number;
        viral_count?: number;
        viral_hit_count?: number;
        video_publish_count?: number;
        video_publish_success_count?: number;
        video_publish_all_success?: boolean;
    };
    peer_acquisition?: {
        clue_count?: number;
        customer_asset_count?: number;
        has_output?: boolean;
    };
    guard?: {
        platform_private_reply_count?: number;
        platform_private_ai_count?: number;
        platform_comment_reply_count?: number;
        platform_comment_ai_count?: number;
        wechat_reply_count?: number;
        wechat_reply_user_count?: number;
        create_group_count?: number;
    };
    circle_maintenance?: {
        comment_count?: number;
        like_count?: number;
    };
}

const selectedDate = ref(new Date());
const statistics = ref<StatisticsData>(createEmptyStatisticsData());
const dateText = ref(formatDateText(selectedDate.value));
const pageLoading = ref(true);
const dateSwitchLoading = ref(false);
const refreshing = ref(false);
let fetchRequestId = 0;

const dateLabel = computed(() => dateText.value);
const dateSwitchText = computed(() => `正在加载 ${dateLabel.value} 数据`);

const heroStats = computed(() => [
    { label: "今日曝光", value: formatWan(statistics.value.exposure), unit: "" },
    { label: "获客线索", value: String(statistics.value.leads), unit: "" },
    { label: "完成率", value: String(statistics.value.rate), unit: "%" },
]);

const coreTasks = computed(() => statistics.value.coreTasks);
const leadStats = computed(() => statistics.value.leadStats);
const guardRows = computed(() => statistics.value.guardRows);
const circleStats = computed(() => statistics.value.circleStats);
const peerBadge = computed<{ text: string; type: TagType }>(() => {
    if (statistics.value.peerHasOutput) {
        return { text: "今日有产出", type: "warning" };
    }
    return { text: "暂无产出", type: "primary" };
});

const handleDateStep = (step: number) => {
    if (pageLoading.value || dateSwitchLoading.value || refreshing.value) return;

    const nextDate = new Date(selectedDate.value);
    nextDate.setDate(nextDate.getDate() + step);
    selectedDate.value = nextDate;
    dateText.value = formatDateText(nextDate);
    fetchStatistics({ dateSwitch: true });
};

const handleRefresh = () => {
    fetchStatistics();
};

const handlePullRefresh = () => {
    fetchStatistics({ refresher: true });
};

const fetchStatistics = async (options: { refresher?: boolean; dateSwitch?: boolean } = {}) => {
    const requestId = ++fetchRequestId;
    const queryDate = formatDateParam(selectedDate.value);
    if (options.refresher) {
        refreshing.value = true;
    }
    if (options.dateSwitch) {
        dateSwitchLoading.value = true;
    }

    try {
        const data = await getEffectStatistics({ date: queryDate });
        if (requestId !== fetchRequestId) return;

        const responseData = (data || {}) as EffectStatisticsResponse;
        dateText.value = responseData.date_text || formatDateText(selectedDate.value);
        statistics.value = createStatisticsData(responseData);
    } catch (error) {
        if (requestId === fetchRequestId) {
            uni.$u.toast("获取效果统计失败");
        }
    } finally {
        if (requestId === fetchRequestId) {
            await nextTick();
            refreshing.value = false;
            pageLoading.value = false;
            dateSwitchLoading.value = false;
        }
    }
};

const formatWan = (value: number) => {
    if (value >= 10000) {
        const num = value / 10000;
        return `${Number.isInteger(num) ? num : num.toFixed(1)}w`;
    }
    return String(value);
};

const formatDateParam = (date: Date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};

function formatDateText(date: Date) {
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${month}月${day}日`;
}

const toNumber = (value: unknown) => {
    const numberValue = Number(value);
    return Number.isFinite(numberValue) ? numberValue : 0;
};

const normalizeRate = (value: unknown) => {
    const rate = toNumber(value);
    if (rate > 0 && rate <= 1) {
        return Math.round(rate * 100);
    }
    return Math.round(rate);
};

function createEmptyStatisticsData(): StatisticsData {
    return {
        exposure: 0,
        leads: 0,
        rate: 0,
        peerHasOutput: false,
        coreTasks: [],
        leadStats: [],
        guardRows: [],
        circleStats: [],
    };
}

function createStatisticsData(data: EffectStatisticsResponse): StatisticsData {
    const top = data.top || {};
    const coreTasks = data.core_tasks || {};
    const peerAcquisition = data.peer_acquisition || {};
    const guard = data.guard || {};
    const circleMaintenance = data.circle_maintenance || {};
    const videoPublishAllSuccess = Boolean(coreTasks.video_publish_all_success);

    return {
        exposure: toNumber(top.today_expose_number),
        leads: toNumber(top.today_clue_number),
        rate: normalizeRate(top.today_rate),
        peerHasOutput: Boolean(peerAcquisition.has_output),
        coreTasks: [
            {
                label: "自动找素材",
                value: toNumber(coreTasks.material_count),
                unit: "条",
                tag: "今日新增",
                tagType: "primary",
                icon: LayersIcon,
            },
            {
                label: "自动找爆款",
                value: toNumber(coreTasks.viral_count),
                unit: "个",
                tag: `命中 ${toNumber(coreTasks.viral_hit_count)} 条`,
                tagType: "primary",
                icon: TrendingUpIcon,
            },
            {
                label: "视频发布",
                value: toNumber(coreTasks.video_publish_count),
                unit: "次",
                tag: videoPublishAllSuccess ? "全部成功" : `成功 ${toNumber(coreTasks.video_publish_success_count)} 次`,
                tagType: videoPublishAllSuccess ? "success" : "warning",
                icon: SendIcon,
            },
        ],
        leadStats: [
            {
                label: "找到线索数",
                value: toNumber(peerAcquisition.clue_count),
                unit: "人",
                desc: "今日意向客户数",
                icon: CrosshairIcon,
            },
            {
                label: "获取客资数",
                value: toNumber(peerAcquisition.customer_asset_count),
                unit: "条",
                desc: "今日获取",
                icon: CalendarCheckIcon,
            },
        ],
        guardRows: [
            {
                label: "平台私信回复",
                desc: "抖音 · 快手 · 小红书",
                value: toNumber(guard.platform_private_reply_count),
                unit: "条",
                tag: `AI 处理 ${toNumber(guard.platform_private_ai_count)} 条`,
                tagType: "primary",
                icon: MessageSquareIcon,
            },
            {
                label: "平台评论回复",
                desc: "多平台评论区管理",
                value: toNumber(guard.platform_comment_reply_count),
                unit: "条",
                tag: `AI 处理 ${toNumber(guard.platform_comment_ai_count)} 条`,
                tagType: "primary",
                icon: MessageCircleIcon,
            },
            {
                label: "微信聊天回复",
                desc: "全天候值守",
                value: toNumber(guard.wechat_reply_count),
                unit: "条",
                tag: `涉及用户 ${toNumber(guard.wechat_reply_user_count)} 人`,
                tagType: "success",
                icon: SmartphoneIcon,
            },
            {
                label: "自动拉群",
                desc: "精准客户建群",
                value: toNumber(guard.create_group_count),
                unit: "个",
                tag: `今日建群 ${toNumber(guard.create_group_count)} 个`,
                tagType: "success",
                icon: UsersIcon,
            },
        ],
        circleStats: [
            {
                label: "评论数量",
                value: toNumber(circleMaintenance.comment_count),
                unit: "条",
                tag: "今日评论",
                tagType: "primary",
                icon: MessageCircleIcon,
            },
            {
                label: "点赞数量",
                value: toNumber(circleMaintenance.like_count),
                unit: "次",
                tag: "今日点赞",
                tagType: "primary",
                icon: HeartIcon,
            },
        ],
    };
}

onLoad((options: any) => {
    if (options?.date) {
        const date = new Date(String(options.date).replace(/-/g, "/"));
        if (!Number.isNaN(date.getTime())) {
            selectedDate.value = date;
            dateText.value = formatDateText(date);
        }
    }
    fetchStatistics();
});
</script>

<style scoped lang="scss">
.effect-hero {
    background: linear-gradient(150deg, #2d52f8 0%, #1e3cf0 55%, #1428d8 100%);
}

.effect-card-shadow {
    box-shadow: 0 8rpx 40rpx rgba(24, 50, 238, 0.1);
}

.effect-loading-shadow {
    box-shadow: 0 20rpx 70rpx rgba(17, 24, 39, 0.18);
}

.skeleton-hero-block {
    border-radius: 12rpx;
    background: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0.2) 25%,
        rgba(255, 255, 255, 0.38) 50%,
        rgba(255, 255, 255, 0.2) 75%
    );
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

@keyframes skeleton-loading {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}
</style>
