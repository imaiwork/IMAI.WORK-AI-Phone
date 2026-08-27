<template>
    <view
        class="flex flex-col min-h-screen"
        style="background: linear-gradient(180deg, #c9def8 0%, #ddeafa 400rpx, #f3f5fa 800rpx)">
        <u-navbar title="" :border-bottom="false" :background="{ background: 'transparent' }"></u-navbar>

        <!-- 页头 -->
        <view class="mx-4 mt-4 flex items-center gap-[28rpx]">
            <view
                class="w-[120rpx] h-[120rpx] rounded-[28rpx] flex items-center justify-center flex-shrink-0"
                style="
                    background: linear-gradient(135deg, #5b9bf8 0%, #3b82f6 100%);
                    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
                ">
                <image
                    src="@/ai_modules/hotspot/static/icons/flame_white.svg"
                    mode="aspectFit"
                    class="w-[60rpx] h-[60rpx]" />
            </view>
            <view class="flex-1 min-w-0">
                <text class="block text-[40rpx] font-bold text-[#111827] leading-tight">追热点做视频</text>
                <text class="block text-[24rpx] text-[#9CA3AF] mt-[8rpx]">抖音热榜实时监测，结合人设一键成片</text>
            </view>
        </view>

        <!-- 平台切换（仅一个平台时整条隐藏） -->
        <view class="mx-4 mt-[40rpx]">
            <view
                v-if="platformOptions.length > 1"
                class="inline-flex items-center rounded-full p-[6rpx] mb-[20rpx]"
                style="background: rgba(255, 255, 255, 0.7); box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04)">
                <view
                    v-for="platform in platformOptions"
                    :key="platform.key"
                    class="flex items-center gap-[8rpx] px-[26rpx] py-[14rpx] rounded-full"
                    :style="currentPlatform === platform.key ? `background:${platform.activeBg}` : ''"
                    @click="pickPlatform(platform.key)">
                    <image
                        :src="currentPlatform === platform.key ? platform.iconWhite : platform.iconGray"
                        mode="aspectFit"
                        class="w-[26rpx] h-[26rpx]" />
                    <text
                        class="text-[24rpx] leading-none"
                        :class="currentPlatform === platform.key ? 'text-white font-semibold' : 'text-[#4B5563]'">
                        {{ platform.label }}
                    </text>
                </view>
            </view>

            <!-- 热榜卡 -->
            <view class="bg-white rounded-[24rpx] p-[28rpx]" style="box-shadow: 0 6px 22px rgba(99, 120, 200, 0.1)">
                <view class="flex items-center justify-between">
                    <text class="text-base font-bold text-[#111827]">{{ hotHeading }}</text>
                    <view
                        class="flex items-center gap-[6rpx] bg-[#F3F4F6] rounded-full px-[20rpx] py-[10rpx] active:opacity-70"
                        @click="loadHot(hotExpanded)">
                        <u-icon name="reload" color="#6B7280" :size="20"></u-icon>
                        <text class="text-xs text-[#6B7280]">刷新</text>
                    </view>
                </view>

                <!-- 周期切换 + 历史 -->
                <view class="flex items-center gap-[16rpx] mt-[20rpx]">
                    <view class="flex items-center bg-[#F3F4F6] rounded-full p-[6rpx] flex-1">
                        <text
                            v-for="period in visiblePeriods"
                            :key="period.key"
                            class="flex-1 py-[10rpx] rounded-full text-[22rpx] text-center"
                            :class="
                                currentPeriod === period.key ? 'bg-white text-primary font-semibold' : 'text-[#9CA3AF]'
                            "
                            @click="pickPeriod(period.key)">
                            {{ period.label }}
                        </text>
                    </view>
                    <view
                        v-if="currentPeriod !== HotspotPeriod.RISE"
                        class="flex items-center gap-[6rpx] rounded-full px-[20rpx] py-[12rpx] flex-shrink-0 active:opacity-70"
                        :class="showHistory ? 'bg-primary' : 'bg-[#F3F4F6]'"
                        @click="toggleHistory">
                        <image
                            :src="showHistory ? CalendarWhite : CalendarGray"
                            mode="aspectFit"
                            class="w-[24rpx] h-[24rpx]" />
                        <text class="text-[22rpx]" :class="showHistory ? 'text-white font-semibold' : 'text-[#6B7280]'">
                            历史
                        </text>
                    </view>
                </view>

                <!-- 历史日期（飙升榜为实时数据，不支持历史） -->
                <view v-if="showHistory && currentPeriod !== HotspotPeriod.RISE" class="mt-[16rpx]">
                    <view
                        v-if="!histDates.length"
                        class="flex items-start gap-[10rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] bg-[#F9FAFB] border-[2rpx] border-solid border-[#F3F4F6]">
                        <u-icon name="info-circle" color="#9CA3AF" :size="24" class="flex-shrink-0"></u-icon>
                        <text class="flex-1 text-[22rpx] text-[#6B7280] leading-relaxed">
                            还没有历史快照。平台不提供往期热榜，历史是本服务每天自动存档累积的，明天起就能往回翻。
                        </text>
                    </view>
                    <scroll-view v-else scroll-x class="w-full" :show-scrollbar="false">
                        <view class="inline-flex gap-[12rpx] pb-[4rpx]">
                            <text
                                v-for="date in histDates.slice(0, 14)"
                                :key="date"
                                class="px-[22rpx] py-[10rpx] rounded-[12rpx] text-[22rpx] flex-shrink-0"
                                :class="
                                    (currentDay || todayDate) === date
                                        ? 'bg-primary text-white font-semibold'
                                        : 'bg-[#F3F4F6] text-[#6B7280]'
                                "
                                @click="pickDay(date)">
                                {{ date === todayDate ? "今天" : date.slice(5) }}
                            </text>
                        </view>
                    </scroll-view>
                </view>

                <text class="block text-[22rpx] text-[#9CA3AF] mt-[16rpx]">{{ hotMeta }}</text>

                <view
                    v-if="hotNotice"
                    class="flex items-start gap-[10rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] bg-[#F9FAFB] border-[2rpx] border-solid border-[#F3F4F6] mt-[16rpx]">
                    <u-icon name="info-circle" color="#9CA3AF" :size="24" class="flex-shrink-0"></u-icon>
                    <text class="flex-1 text-[22rpx] text-[#6B7280] leading-relaxed">{{ hotNotice }}</text>
                </view>

                <!-- 热榜列表：加载中显示与列表行同构的骨架屏，高度稳定不闪 -->
                <view v-if="hotLoading" class="mt-[8rpx]">
                    <view
                        v-for="i in skeletonRows"
                        :key="'skel-' + i"
                        class="flex items-start gap-[20rpx] py-[22rpx]"
                        :class="i < skeletonRows ? 'border-[0] border-b-[2rpx] border-solid border-[#F9FAFB]' : ''">
                        <view class="skel w-[36rpx] h-[36rpx] flex-shrink-0 mt-[2rpx]"></view>
                        <view class="flex-1 min-w-0">
                            <view class="skel h-[28rpx]" :style="`width:${52 + ((i * 17) % 34)}%`"></view>
                            <view class="skel h-[22rpx] mt-[14rpx]" style="width: 32%"></view>
                        </view>
                    </view>
                </view>
                <view v-else-if="!topics.length" class="py-[48rpx] flex items-center justify-center">
                    <text class="text-xs text-[#9CA3AF]">暂无热点数据</text>
                </view>
                <view v-else class="mt-[8rpx]">
                    <view
                        v-for="(topic, index) in topics"
                        :key="topic.id || index"
                        class="flex items-start gap-[20rpx] py-[22rpx] active:bg-[#F9FAFB]"
                        :class="
                            index < topics.length - 1 ? 'border-[0] border-b-[2rpx] border-solid border-[#F9FAFB]' : ''
                        "
                        @click="openFlow(topic)">
                        <view
                            class="w-[36rpx] h-[36rpx] rounded-[8rpx] flex items-center justify-center flex-shrink-0 mt-[2rpx]"
                            :class="rankClass(topic.rank)">
                            <text
                                class="text-[20rpx] font-bold"
                                :class="topic.rank <= 3 ? 'text-white' : 'text-[#9CA3AF]'">
                                {{ topic.rank }}
                            </text>
                        </view>
                        <view class="flex-1 min-w-0">
                            <text class="block text-[28rpx] font-semibold text-[#111827] leading-snug">
                                {{ topic.title }}
                            </text>
                            <view class="flex items-center gap-[10rpx] mt-[10rpx] flex-wrap">
                                <text
                                    v-if="topic.analyzed"
                                    class="px-[10rpx] py-[2rpx] rounded-[8rpx] bg-[#ECFDF5] text-[#059669] text-[18rpx] font-medium">
                                    ✓ 已分析
                                </text>
                                <text
                                    v-if="topic.category"
                                    class="px-[10rpx] py-[2rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[18rpx]">
                                    {{ topic.category }}
                                </text>
                                <text
                                    v-if="topic.rank_diff"
                                    class="px-[10rpx] py-[2rpx] rounded-[8rpx] bg-[#FEF2F2] text-[#EF4444] text-[18rpx] font-bold">
                                    ↑ {{ topic.rank_diff }}
                                </text>
                                <template v-else-if="topic.days_on_board">
                                    <text
                                        class="px-[10rpx] py-[2rpx] rounded-[8rpx] text-[18rpx] font-medium"
                                        :class="daysOnBoardClass(topic.days_on_board)">
                                        上榜 {{ topic.days_on_board }} 天
                                    </text>
                                    <text
                                        v-if="topic.best_rank"
                                        class="px-[10rpx] py-[2rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[18rpx]">
                                        最高 {{ topic.best_rank }} 名
                                    </text>
                                </template>
                                <text
                                    v-else-if="topic.trend === 'new'"
                                    class="px-[10rpx] py-[2rpx] rounded-[8rpx] bg-[#F0FDF4] text-[#16A34A] text-[18rpx] font-medium">
                                    新
                                </text>
                                <text
                                    v-else-if="topic.rank <= 3"
                                    class="px-[10rpx] py-[2rpx] rounded-[8rpx] bg-[#FEF2F2] text-[#EF4444] text-[18rpx] font-medium">
                                    热
                                </text>
                                <view v-if="topic.heat_text" class="flex items-center gap-[4rpx]">
                                    <image :src="TrendingGray" mode="aspectFit" class="w-[22rpx] h-[22rpx]" />
                                    <text class="text-[20rpx] text-[#9CA3AF]">{{ topic.heat_text }}</text>
                                </view>
                            </view>
                        </view>
                        <u-icon
                            name="arrow-right"
                            color="#D1D5DB"
                            :size="24"
                            class="self-center flex-shrink-0"></u-icon>
                    </view>

                    <view
                        v-if="topics.length >= HOT_INIT"
                        class="w-full mt-[12rpx] py-[18rpx] rounded-[16rpx] bg-[#F9FAFB] flex items-center justify-center gap-[6rpx] active:opacity-70"
                        @click="loadHot(!hotExpanded)">
                        <text class="text-[24rpx] text-[#9CA3AF] font-medium">
                            {{ hotExpanded ? "收起" : "查看更多热点" }}
                        </text>
                        <u-icon :name="hotExpanded ? 'arrow-up' : 'arrow-down'" color="#9CA3AF" :size="24"></u-icon>
                    </view>
                </view>
            </view>
        </view>

        <!-- 创作队列 -->
        <view class="mx-4 mt-[40rpx]">
            <view class="flex items-center justify-between mb-[20rpx]">
                <view class="flex items-center gap-[12rpx]">
                    <text class="text-[30rpx] font-bold text-[#1F2937]">创作队列</text>
                    <view class="px-[16rpx] py-[4rpx] rounded-full bg-[#EFF6FF]">
                        <text class="text-[22rpx] text-primary font-medium">{{ taskTotal }}</text>
                    </view>
                </view>
                <view
                    class="flex items-center bg-white rounded-full p-[6rpx]"
                    style="box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06)">
                    <text
                        v-for="tab in taskTabs"
                        :key="tab.key"
                        class="px-[22rpx] py-[10rpx] rounded-full text-xs font-medium"
                        :class="currentTab === tab.key ? 'bg-primary text-white' : 'text-[#4B5563]'"
                        @click="pickTab(tab.key)">
                        {{ tab.label }}
                    </text>
                </view>
            </view>

            <view class="flex flex-col gap-[20rpx] pb-[48rpx]">
                <queue-task-card
                    v-for="task in taskList"
                    :key="task.id"
                    :task="task"
                    :goal-label-map="goalLabelMap"
                    :video-type-label-map="videoTypeLabelMap"
                    @detail="toDetail(task)"
                    @publish="onPublish(task)"
                    @remove="onDelete(task)" />

                <view v-if="!taskLoading && !taskList.length" class="flex flex-col items-center py-[60rpx]">
                    <empty text="还没有任务，挑一个热点开始吧" />
                </view>
                <view v-else class="flex items-center justify-center py-[16rpx] gap-[12rpx]">
                    <block v-if="taskLoading">
                        <u-loading mode="circle" size="28" color="#0065fb"></u-loading>
                        <text class="text-xs text-[#9ca3af]">加载中...</text>
                    </block>
                    <block v-else-if="taskFinished && taskList.length > 0">
                        <view class="h-[2rpx] w-[100rpx] bg-[#E5E7EB]"></view>
                        <text class="text-xs text-[#9CA3AF]">已加载全部</text>
                        <view class="h-[2rpx] w-[100rpx] bg-[#E5E7EB]"></view>
                    </block>
                </view>
            </view>
        </view>
    </view>

    <flow-popup
        v-model="showFlow"
        :topic="currentTopic"
        :period-label="flowPeriodLabel"
        :personas="personas"
        :options-data="optionsData"
        @analyzed="onTopicAnalyzed"
        @created="onTaskCreated" />
</template>

<script setup lang="ts">
import {
    getHotspotHot,
    getHotspotHistoryDates,
    getHotspotOptions,
    getHotspotPersonas,
    getHotspotTasks,
    deleteHotspotTask,
} from "@/api/hotspot";
import usePolling from "@/hooks/usePolling";
import {
    HOTSPOT_DEFAULT_OPTIONS,
    HOTSPOT_PERIOD_OPTIONS,
    HOTSPOT_VISIBLE_PLATFORMS,
    HotspotPeriod,
    HotspotPlatform,
    HotspotTaskStatus,
    type HotspotOptionsData,
} from "@/ai_modules/hotspot/enums";
import QueueTaskCard from "./components/queue-task-card.vue";
import FlowPopup from "./components/flow-popup.vue";
import CalendarGray from "@/ai_modules/hotspot/static/icons/calendar_gray.svg";
import CalendarWhite from "@/ai_modules/hotspot/static/icons/calendar_white.svg";
import TrendingGray from "@/ai_modules/hotspot/static/icons/trending_gray.svg";

const HOT_INIT = 5;
const HOT_FULL = 30;

const platformOptions = HOTSPOT_VISIBLE_PLATFORMS;

// ────────── 热榜状态 ──────────
const currentPlatform = ref<string>(HotspotPlatform.DOUYIN);
const currentPeriod = ref<string>(HotspotPeriod.DAY);
const currentDay = ref("");
const showHistory = ref(false);
const histDates = ref<string[]>([]);
const topics = ref<Record<string, any>[]>([]);
const hotLoading = ref(false);
const hotExpanded = ref(false);
const hotMeta = ref("—");
const hotNotice = ref("");

const todayDate = computed(() => {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, "0");
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
});

// 飙升榜仅抖音有数据
const visiblePeriods = computed(() =>
    HOTSPOT_PERIOD_OPTIONS.filter(
        (p) => p.key !== HotspotPeriod.RISE || currentPlatform.value === HotspotPlatform.DOUYIN,
    ),
);

const hotHeading = computed(() => {
    if (currentPeriod.value === HotspotPeriod.RISE) return "飙升热榜";
    if (currentPeriod.value === HotspotPeriod.WEEK) return "本周热榜";
    return currentDay.value ? "历史热榜" : "今日热榜";
});

const flowPeriodLabel = computed(() => {
    if (currentPeriod.value === HotspotPeriod.WEEK) return "周榜";
    if (currentPeriod.value === HotspotPeriod.RISE) return "飙升榜";
    return currentDay.value ? `${currentDay.value} 日榜` : "日榜";
});

const rankClass = (rank: number) => {
    if (rank === 1) return "bg-[#EF4444]";
    if (rank === 2) return "bg-[#FB923C]";
    if (rank === 3) return "bg-[#FBBF24]";
    return "bg-[#F3F4F6]";
};

const daysOnBoardClass = (days: number) => {
    if (days >= 5) return "bg-[#FEF2F2] text-[#EF4444]";
    if (days >= 3) return "bg-[#FFFBEB] text-[#D97706]";
    return "bg-[#F3F4F6] text-[#6B7280]";
};

/** 骨架行数跟随当前列表长度，展开态刷新时高度不塌陷 */
const skeletonRows = computed(() => Math.min(Math.max(topics.value.length, HOT_INIT), 10));

let hotToken = 0;
const loadHot = async (expand = false) => {
    const token = ++hotToken;
    hotLoading.value = true;
    hotNotice.value = "";
    hotExpanded.value = expand;
    try {
        // GET 参数不放 undefined 键：小程序端 wx.request 会把 undefined 序列化成字符串 "undefined"
        const data = await getHotspotHot({
            platform: currentPlatform.value,
            period: currentPeriod.value,
            limit: expand ? HOT_FULL : HOT_INIT,
            ...(currentDay.value ? { day: currentDay.value } : {}),
        });
        if (token !== hotToken) return;
        topics.value = Array.isArray(data?.topics) ? data.topics : [];
        if (currentPeriod.value === HotspotPeriod.RISE) {
            hotMeta.value = "实时飙升 · 按排名上升幅度排序，适合抢在冲顶前进场" + (data?.cached ? " · 缓存" : "");
        } else if (currentPeriod.value === HotspotPeriod.WEEK) {
            const covered: string[] = data?.covered_dates || [];
            hotMeta.value = covered.length
                ? `近 7 天聚合 · 实际覆盖 ${covered.length} 天（${covered[covered.length - 1]} 至 ${covered[0]}）`
                : "还没有足够的快照做周榜";
            if (!topics.value.length) {
                hotNotice.value = "周榜由每天的热榜快照聚合而成，平台不提供往期数据，所以要连续运行几天才有内容。";
            }
        } else {
            hotMeta.value = (data?.date || "") + (data?.cached ? " · 缓存" : "") + (data?.live ? "" : " · 历史快照");
        }
    } catch (error: any) {
        if (token !== hotToken) return;
        topics.value = [];
        hotMeta.value = todayDate.value;
        hotNotice.value = String(error?.message || error || "热榜加载失败，请稍后重试");
    } finally {
        if (token === hotToken) hotLoading.value = false;
    }
};

const loadHistDates = async () => {
    const platform = currentPlatform.value;
    try {
        const res = await getHotspotHistoryDates({ platform });
        if (platform !== currentPlatform.value) return;
        histDates.value = Array.isArray(res?.dates) ? res.dates : [];
    } catch {
        if (platform !== currentPlatform.value) return;
        histDates.value = [];
    }
};

const pickPlatform = (key: string) => {
    if (currentPlatform.value === key) return;
    currentPlatform.value = key;
    currentDay.value = "";
    if (currentPeriod.value === HotspotPeriod.RISE && key !== HotspotPlatform.DOUYIN) {
        currentPeriod.value = HotspotPeriod.DAY;
    }
    loadHistDates();
    loadHot();
};

const pickPeriod = (period: string) => {
    if (currentPeriod.value === period) return;
    currentPeriod.value = period;
    if (period === HotspotPeriod.RISE) currentDay.value = "";
    loadHot();
};

const toggleHistory = () => {
    showHistory.value = !showHistory.value;
};

const pickDay = (date: string) => {
    currentDay.value = date === todayDate.value ? "" : date;
    loadHot();
};

// ────────── 制作流程 ──────────
const personas = ref<Record<string, any>[]>([]);
const optionsData = ref<HotspotOptionsData>(HOTSPOT_DEFAULT_OPTIONS);
const showFlow = ref(false);
const currentTopic = ref<Record<string, any> | null>(null);

const goalLabelMap = computed(() => Object.fromEntries(optionsData.value.goals.map((g: any) => [g.key, g.label])));
const videoTypeLabelMap = computed(() =>
    Object.fromEntries(optionsData.value.video_types.map((v: any) => [v.key, v.label])),
);

const openFlow = (topic: Record<string, any>) => {
    currentTopic.value = {
        ...topic,
        // 后端 topic 校验上限 120 字
        title: String(topic.title || "").slice(0, 120),
        platform: topic.platform || currentPlatform.value,
    };
    showFlow.value = true;
};

/** 面板内完成结合分析后，点亮热榜对应条目的「已分析」标记（无需整榜刷新） */
const onTopicAnalyzed = (topic: Record<string, any>) => {
    const hit = topics.value.find(
        (t) => t.title === topic?.title && (t.platform || currentPlatform.value) === topic?.platform,
    );
    if (hit) hit.analyzed = true;
};

const onTaskCreated = (task: Record<string, any>, navigate = true) => {
    if (navigate && task?.id) {
        // 立即进创作详情，避免「先回到列表、随后又突然弹出详情」的闪跳；详情页本身即创建成功反馈
        toDetail(task);
    } else {
        uni.showToast({ title: "任务已创建，开始合成视频", icon: "none", duration: 2500 });
    }
    resetTasks();
};

// ────────── 创作队列 ──────────
const taskTabs = [
    { key: "", label: "全部" },
    { key: HotspotTaskStatus.RUNNING, label: "执行中" },
    { key: HotspotTaskStatus.DONE, label: "已完成" },
    { key: HotspotTaskStatus.FAIL, label: "失败" },
];
const currentTab = ref("");
const taskList = ref<Record<string, any>[]>([]);
const taskTotal = ref(0);
const taskLoading = ref(false);
const taskFinished = ref(false);
const taskQuery = reactive({ page_no: 1, page_size: 10 });

// 任务列表请求代际：切 tab / 重置时递增，旧响应一律丢弃，避免旧 tab 数据污染新 tab
let taskToken = 0;

const buildTaskQuery = (pageNo: number, pageSize: number) => ({
    page_no: pageNo,
    page_size: pageSize,
    // GET 参数不放 undefined 键：小程序端会序列化成字符串 "undefined" 触发后端校验失败
    ...(currentTab.value ? { status: currentTab.value } : {}),
});

const loadTasks = async () => {
    if (taskLoading.value || taskFinished.value) return;
    const token = taskToken;
    const pageNo = taskQuery.page_no;
    taskLoading.value = true;
    try {
        const res = await getHotspotTasks(buildTaskQuery(pageNo, taskQuery.page_size));
        if (token !== taskToken) return;
        const lists = res?.lists || [];
        taskList.value = [...taskList.value, ...lists];
        taskTotal.value = Number(res?.count || 0);
        if (taskList.value.length >= taskTotal.value) taskFinished.value = true;
        checkAndStartPolling();
    } catch {
        if (token !== taskToken) return;
        // 失败（含请求被取消）不视为「已加载全部」；翻页请求失败时回退页码，触底可重试
        if (pageNo > 1 && taskQuery.page_no === pageNo) {
            taskQuery.page_no = pageNo - 1;
        }
    } finally {
        if (token === taskToken) taskLoading.value = false;
    }
};

const resetTasks = () => {
    taskToken += 1;
    taskQuery.page_no = 1;
    taskList.value = [];
    taskTotal.value = 0;
    taskFinished.value = false;
    taskLoading.value = false;
    loadTasks();
};

const pickTab = (key: string) => {
    if (currentTab.value === key) return;
    currentTab.value = key;
    resetTasks();
};

const taskMatchesTab = (task: Record<string, any>) => !currentTab.value || task.status === currentTab.value;

const checkAndStartPolling = () => {
    const hasRunning = taskList.value.some(
        (t) => t.status === HotspotTaskStatus.RUNNING || t.status === HotspotTaskStatus.WAIT,
    );
    if (hasRunning) start();
    else end();
};

const silentRefresh = async () => {
    const token = taskToken;
    try {
        const loadedSize = taskQuery.page_no * taskQuery.page_size;
        // 轮询不带 status 过滤：否则筛选 tab 下任务状态迁移后（running→done）不在返回集里，
        // 旧状态卡片会永远保留、轮询也永不停止
        const res = await getHotspotTasks({
            page_no: 1,
            page_size: Math.min(50, loadedSize),
        });
        if (token !== taskToken) return;
        const lists: any[] = res?.lists || [];
        const updatedMap = new Map(lists.map((item) => [item.id, item]));
        taskList.value = taskList.value
            .map((task) => updatedMap.get(task.id) || task)
            .filter((task) => taskMatchesTab(task));
        // 轮询回调内只做停止判断，start 由用户驱动的加载触发，避免双重调度
        const hasRunning = taskList.value.some(
            (t) => t.status === HotspotTaskStatus.RUNNING || t.status === HotspotTaskStatus.WAIT,
        );
        if (!hasRunning) end();
    } catch {
        end();
    }
};

const { start, end } = usePolling(silentRefresh, { time: 15000 });

const toDetail = (task: Record<string, any>) => {
    uni.navigateTo({
        url: `/ai_modules/hotspot/pages/detail/detail?id=${encodeURIComponent(task.id)}`,
    });
};

/** 一键发布：带成片与文案跳矩阵发布任务创建页（source=hotspot 分支回填素材与文案） */
const onPublish = (task: Record<string, any>) => {
    if (!task.video_url) {
        uni.$u.toast("视频还未生成");
        return;
    }
    uni.$u.route({
        url: "/ai_modules/device/pages/create_task/create_task",
        params: {
            type: 1,
            source: "hotspot",
            data: JSON.stringify({ id: task.id }),
        },
    });
};

const onDelete = (task: Record<string, any>) => {
    const unfinished = task.status !== HotspotTaskStatus.DONE && task.status !== HotspotTaskStatus.FAIL;
    uni.showModal({
        title: "删除任务",
        content: unfinished
            ? "任务生成中，删除后不再保留生成结果，已消耗的算力不会退回。确定删除吗？"
            : "确定删除这条任务吗？",
        confirmColor: "#EF4444",
        confirmText: "删除",
        success: async ({ confirm }) => {
            if (!confirm) return;
            uni.showLoading({ title: "删除中...", mask: true });
            let failed = false;
            let error: any = null;
            try {
                await deleteHotspotTask({ id: task.id });
            } catch (e: any) {
                failed = true;
                error = e;
            }
            // 必须先关 loading 再弹 toast：小程序里 showToast 与 showLoading 共用同一层，
            // 放在 finally 里 hideLoading 会把刚弹出的 toast 一起关掉，表现成「点了没任何反应」
            uni.hideLoading();
            if (failed) {
                uni.showToast({ title: error?.message || error || "删除失败", icon: "none", duration: 3000 });
                return;
            }
            taskList.value = taskList.value.filter((t) => t.id !== task.id);
            taskTotal.value = Math.max(0, taskTotal.value - 1);
            // 删掉的可能是最后一条生成中任务，重算一次轮询开关，避免空转
            checkAndStartPolling();
            uni.showToast({ title: "删除成功", icon: "none", duration: 2500 });
        },
    });
};

// ────────── 启动 ──────────
const boot = async () => {
    try {
        const personaList = await getHotspotPersonas();
        personas.value = Array.isArray(personaList) ? personaList : [];
    } catch {
        personas.value = [];
    }
    try {
        const options = await getHotspotOptions();
        if (options?.goals?.length) optionsData.value = options;
    } catch {
        // 用内置兜底
    }
};

onLoad(() => {
    boot();
    loadHistDates();
    loadHot();
    resetTasks();
});

onShow(async () => {
    // 从详情页返回 / 小程序切回前台：onHide 已停轮询，这里刷新一次并按需重启
    if (taskList.value.length) {
        await silentRefresh();
        checkAndStartPolling();
    }
});

onReachBottom(() => {
    if (taskLoading.value || taskFinished.value) return;
    taskQuery.page_no += 1;
    loadTasks();
});

onUnmounted(() => end());
onHide(() => end());
onUnload(() => end());
</script>

<style lang="scss" scoped>
.skel {
    background: linear-gradient(90deg, #eff2f7 0%, #e4e9f2 50%, #eff2f7 100%);
    background-size: 200% 100%;
    animation: hotspot-shimmer 1.4s infinite;
    border-radius: 8rpx;
}

@keyframes hotspot-shimmer {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .skel {
        animation: none;
    }
}
</style>
