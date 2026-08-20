<template>
    <view>
        <view
            class="rounded-[28rpx] px-[28rpx] py-[26rpx] mb-[22rpx]"
            style="background: #ebf2ff; border: 2rpx solid #bad4ff">
            <view class="flex items-center justify-between gap-[20rpx] mb-[18rpx]">
                <view class="flex items-center gap-[12rpx] min-w-0">
                    <u-icon name="star-fill" color="#2B6EFF" size="30" class="shrink-0"></u-icon>
                    <text class="text-sm font-bold text-[#1A56CC] line-clamp-1"> 每天 00:00-03:00 自动生成内容 </text>
                </view>
                <view
                    class="px-[22rpx] py-[10rpx] rounded-full bg-primary flex items-center gap-[8rpx] shrink-0"
                    @click="actions.refreshKeywords()">
                    <u-icon name="reload" color="#FFFFFF" size="22"></u-icon>
                    <text class="text-[22rpx] font-semibold text-white">换一批</text>
                </view>
            </view>
            <text class="text-xs leading-relaxed text-primary block mb-[20rpx]">
                AI 从词库中抽词 → 搜索各平台爆款 → 自动下载提取文案 → AI 分析结构 → 自动生成新内容。
            </text>
            <view class="flex flex-wrap gap-[10rpx]">
                <view
                    v-for="(keyword, index) in visibleKeywords"
                    :key="keyword"
                    class="flex items-center gap-[8rpx] bg-white pl-[18rpx] pr-[12rpx] py-[7rpx] rounded-full active:opacity-70"
                    style="border: 2rpx solid #bad4ff"
                    @click="actions.editKeywords(keyword, index)">
                    <text class="text-[22rpx] font-semibold text-primary">{{ keyword }}</text>
                    <view
                        class="w-[28rpx] h-[28rpx] rounded-full flex items-center justify-center active:opacity-60"
                        @click.stop="actions.removeKeyword(keyword, index)">
                        <u-icon name="close" color="#2B6EFF" size="14"></u-icon>
                    </view>
                </view>
                <view
                    v-if="hiddenKeywordCount > 0 && !keywordsExpanded"
                    class="px-[18rpx] py-[7rpx] rounded-full bg-white flex items-center gap-[4rpx] active:opacity-70"
                    style="border: 2rpx solid #bad4ff"
                    @click="keywordsExpanded = true">
                    <text class="text-[22rpx] font-semibold text-primary">+{{ hiddenKeywordCount }}</text>
                    <u-icon name="arrow-down" color="#2B6EFF" size="20"></u-icon>
                </view>
                <view
                    v-if="keywordsExpanded && state.viralKeywords.length > KEYWORD_VISIBLE_LIMIT"
                    class="px-[18rpx] py-[7rpx] rounded-full bg-white flex items-center gap-[4rpx] active:opacity-70"
                    style="border: 2rpx solid #bad4ff"
                    @click="keywordsExpanded = false">
                    <text class="text-[22rpx] font-semibold text-primary">收起</text>
                    <u-icon name="arrow-up" color="#2B6EFF" size="20"></u-icon>
                </view>
                <view
                    class="px-[18rpx] py-[7rpx] rounded-full"
                    style="border: 2rpx dashed #bad4ff"
                    @click="actions.editKeywords()">
                    <text class="text-[22rpx] font-semibold text-primary">+ 添加</text>
                </view>
            </view>
        </view>

        <scroll-view scroll-x class="mb-[20rpx]" :show-scrollbar="false">
            <view class="flex gap-[14rpx]" style="white-space: nowrap">
                <view
                    v-for="tab in state.viralPlatformTabs"
                    :key="tab.key"
                    class="h-[60rpx] px-[26rpx] rounded-full shrink-0 border border-solid flex items-center gap-[10rpx]"
                    :class="
                        state.activeViralPlatform === tab.key
                            ? 'bg-primary border-primary'
                            : 'bg-[#F4F6FA] border-[#E8ECF0]'
                    "
                    @click="actions.platform(tab.key)">
                    <view
                        v-if="tab.short"
                        class="w-[30rpx] h-[30rpx] rounded-full flex items-center justify-center"
                        :style="`background:${tab.color}`">
                        <text class="text-[16rpx] font-bold text-white">{{ tab.short }}</text>
                    </view>
                    <text
                        class="text-xs font-bold"
                        :class="state.activeViralPlatform === tab.key ? 'text-white' : 'text-[#888888]'">
                        {{ tab.label }} · {{ state.viralPlatformCounts[tab.key] || 0 }}
                    </text>
                </view>
            </view>
        </scroll-view>

        <view class="flex items-center flex-wrap gap-[12rpx] mb-[12rpx]">
            <text class="text-sm font-bold text-[#374151] flex-1 min-w-[100rpx]">爆款库</text>
            <view class="viral-action-pill" @click="actions.viewDismissed()">
                <u-icon name="eye-off" color="#6B7280" size="22"></u-icon>
                <text class="text-[22rpx] font-semibold text-[#6B7280]">不感兴趣</text>
            </view>
            <view class="viral-action-pill" @click="actions.history()">
                <u-icon name="clock" color="#6B7280" size="22"></u-icon>
                <text class="text-[22rpx] font-semibold text-[#6B7280]">历史仿写</text>
            </view>
            <view class="viral-manual-btn" @click="actions.openManualImport()">
                <u-icon name="plus" color="#2F73F6" size="22"></u-icon>
                <text class="text-[22rpx] font-semibold text-primary">手动入库</text>
            </view>
        </view>
        <text class="text-xs text-[#9CA3AF] block mb-[20rpx]"> 已预先找好素材，可提前移除不需要的视频。 </text>

        <view v-if="state.filteredViralList.length" class="flex flex-col gap-[22rpx]">
            <view
                v-for="item in state.filteredViralList"
                :key="item.listKey || item.id"
                class="bg-white rounded-[28rpx] overflow-hidden detail-card-shadow"
                :class="{ 'viral-card--pending': isPendingManual(item) }">
                <!-- 排队中的手动入库：等待态卡片，不套「就绪素材」双栏操作 -->
                <template v-if="isPendingManual(item)">
                    <view class="viral-pending relative">
                        <text class="viral-dismiss" @click="actions.dismiss(item)">不感兴趣</text>

                        <view class="viral-pending-meta">
                            <text
                                v-for="platform in item.platforms"
                                :key="platform"
                                class="viral-tag-platform"
                                :style="`background:${getPlatformMeta(platform).color}`">
                                {{ getPlatformMeta(platform).label }}
                            </text>
                            <text class="viral-tag-manual">手动入库</text>
                            <text class="viral-tag-queue">排队中</text>
                            <text v-if="item.date" class="viral-date">{{ item.date }}</text>
                        </view>

                        <text class="viral-pending-title">
                            {{ item.title || "已入库，等待解析原内容…" }}
                        </text>

                        <view class="viral-pending-status">
                            <view class="viral-pending-status-icon">
                                <u-icon name="clock" color="#2B6EFF" size="28"></u-icon>
                            </view>
                            <view class="viral-pending-status-body">
                                <text class="viral-pending-status-label">今晚 00:00–03:00 自动解析</text>
                                <text class="viral-pending-status-desc">
                                    {{ item.remark || "将按人设下全部设备解析入库，完成后可查看仿写文案" }}
                                </text>
                            </view>
                        </view>

                        <view class="viral-pending-actions">
                            <view class="viral-pending-copy-btn" @click="actions.copyText(item.link, '链接已复制')">
                                <u-icon name="attach" color="#2F73F6" size="24"></u-icon>
                                <text class="text-xs font-bold text-primary">复制原链接</text>
                            </view>
                        </view>
                    </view>
                </template>

                <!-- 已就绪：封面 + 仿写文案 + 双操作 -->
                <template v-else>
                    <view class="relative">
                        <view class="absolute top-[22rpx] right-[24rpx] z-10" @click="actions.dismiss(item)">
                            <text class="text-[22rpx] text-[#9CA3AF]">不感兴趣</text>
                        </view>

                        <view class="flex gap-[20rpx] p-[24rpx]">
                            <image
                                v-if="item.cover"
                                :src="item.cover"
                                mode="aspectFill"
                                lazy-load
                                class="w-[144rpx] h-[144rpx] rounded-[20rpx] shrink-0 bg-[#F3F4F6]"></image>
                            <view
                                v-else
                                class="viral-cover-ph"
                                :style="{
                                    background: `linear-gradient(145deg, ${getCoverTone(item).from}, ${
                                        getCoverTone(item).to
                                    })`,
                                }">
                                <view class="viral-cover-ph-badge" :style="{ background: getCoverTone(item).badge }">
                                    <text class="text-[20rpx] font-bold text-white">
                                        {{ getCoverTone(item).short }}
                                    </text>
                                </view>
                                <u-icon name="attach" color="#FFFFFF" size="36"></u-icon>
                                <text class="viral-cover-ph-text">{{ item.statusText || "无封面" }}</text>
                            </view>
                            <view class="flex-1 min-w-0">
                                <view class="flex flex-wrap items-center gap-[8rpx] mb-[10rpx] pr-[120rpx]">
                                    <text
                                        v-for="platform in item.platforms"
                                        :key="platform"
                                        class="text-[18rpx] font-bold text-white px-[12rpx] py-[5rpx] rounded-[8rpx]"
                                        :style="`background:${getPlatformMeta(platform).color}`">
                                        {{ getPlatformMeta(platform).label }}
                                    </text>
                                    <text
                                        v-if="item.isManualImport"
                                        class="text-[18rpx] font-semibold text-primary bg-[#EBF2FF] px-[12rpx] py-[5rpx] rounded-[8rpx]">
                                        手动入库
                                    </text>
                                    <text
                                        v-else
                                        class="text-[20rpx] font-semibold text-[#6B7280] bg-[#F4F6FA] px-[12rpx] py-[5rpx] rounded-[8rpx]">
                                        词条：{{ item.keyword }}
                                    </text>
                                    <text
                                        v-if="item.statusText"
                                        class="text-[18rpx] font-semibold text-[#F86E21] bg-[#FFF7E8] px-[12rpx] py-[5rpx] rounded-[8rpx]">
                                        {{ item.statusText }}
                                    </text>
                                    <text class="text-[20rpx] text-[#9CA3AF]">{{ item.date }}</text>
                                </view>
                                <text class="text-sm font-bold text-[#1F2937] leading-relaxed line-clamp-2">
                                    {{ item.title || "已入库，等待解析原视频信息…" }}
                                </text>
                                <view v-if="item.remark" class="viral-remark">
                                    <text>
                                        <u-icon name="info-circle" color="#5C7ECC" size="22" class="shrink-0"></u-icon>
                                    </text>
                                    <text class="viral-remark-text">{{ item.remark }}</text>
                                </view>
                            </view>
                        </view>

                        <view
                            v-if="isExpanded(item)"
                            class="mx-[24rpx] mb-[18rpx] rounded-[22rpx] bg-[#F8FAFC] p-[22rpx]"
                            style="border: 2rpx solid #eef2f7">
                            <view class="flex items-center gap-[8rpx] mb-[14rpx]">
                                <u-icon name="edit-pen" color="#2B6EFF" size="22"></u-icon>
                                <text class="text-xs font-bold text-[#374151] flex-1">AI 仿写文案</text>
                                <text
                                    class="text-[20rpx] font-semibold text-primary bg-[#EBF2FF] px-[12rpx] py-[4rpx] rounded-full">
                                    可编辑
                                </text>
                            </view>
                            <textarea
                                :value="item.copywriting"
                                class="w-full text-xs text-[#374151] leading-relaxed bg-white rounded-[18rpx] p-[18rpx] break-all"
                                style="min-height: 220rpx"
                                :maxlength="-1"
                                @input="handleCopywritingInput(item, $event)"></textarea>
                            <view class="flex gap-[14rpx] mt-[16rpx]">
                                <view
                                    class="flex-1 h-[70rpx] rounded-[18rpx] bg-primary flex items-center justify-center gap-[8rpx]"
                                    @click="actions.saveCopy(item)">
                                    <u-icon name="checkmark" color="#FFFFFF" size="22"></u-icon>
                                    <text class="text-xs font-semibold text-white">保存</text>
                                </view>
                                <view
                                    class="px-[28rpx] h-[70rpx] rounded-[18rpx] bg-[#F1F5F9] flex items-center justify-center gap-[8rpx]"
                                    @click="actions.copyText(item.copywriting, '文案已复制')">
                                    <u-icon name="file-text" color="#6B7280" size="22"></u-icon>
                                    <text class="text-xs font-semibold text-[#6B7280]">复制</text>
                                </view>
                            </view>
                        </view>

                        <view class="h-[78rpx] border-0 border-t border-solid border-[#F3F4F6] flex">
                            <view
                                class="flex-1 flex items-center justify-center gap-[8rpx] border-0 border-r border-solid border-[#F3F4F6]"
                                @click="actions.toggleCopy(item)">
                                <u-icon
                                    :name="isExpanded(item) ? 'arrow-up' : 'file-text'"
                                    color="#2B6EFF"
                                    size="24"></u-icon>
                                <text class="text-xs font-bold text-primary">
                                    {{ isExpanded(item) ? "收起文案" : "查看仿写文案" }}
                                </text>
                            </view>
                            <view
                                class="flex-1 flex items-center justify-center gap-[8rpx]"
                                @click="actions.copyText(item.link, '链接已复制')">
                                <u-icon name="attach" color="#6B7280" size="24"></u-icon>
                                <text class="text-xs font-bold text-[#6B7280]">复制链接</text>
                            </view>
                        </view>
                    </view>
                </template>
            </view>
        </view>

        <empty v-else text="暂无爆款内容" />
    </view>
</template>

<script setup lang="ts">
type ViralPlatformKey = "all" | "douyin" | "xiaohongshu" | "kuaishou" | "shipinhao";
type ViralRealPlatformKey = Exclude<ViralPlatformKey, "all">;

interface ViralPlatformTab {
    key: ViralPlatformKey;
    label: string;
    short: string;
    color: string;
}

interface ViralItem {
    id: string;
    listKey?: string;
    title: string;
    keyword: string;
    date: string;
    cover: string;
    platforms: ViralRealPlatformKey[];
    likes: string;
    comments: string;
    link: string;
    copywriting: string;
    isManualImport?: boolean;
    remark?: string;
    statusText?: string;
    source?: string;
    image_rewrite_status?: number;
}

const props = defineProps<{
    state: {
        viralKeywords: string[];
        viralPlatformTabs: ViralPlatformTab[];
        viralPlatformCounts: Record<ViralPlatformKey, number>;
        activeViralPlatform: ViralPlatformKey;
        viralList: ViralItem[];
        filteredViralList: ViralItem[];
        expandedViralIds: string[];
    };
    actions: {
        refresh: () => void | Promise<void>;
        editKeywords: (keyword?: string, index?: number) => void;
        removeKeyword: (keyword: string, index: number) => void;
        platform: (key: ViralPlatformKey) => void;
        history: () => void;
        dismiss: (item: ViralItem) => void;
        toggleCopy: (item: ViralItem) => void;
        updateCopy: (item: ViralItem, value: string) => void;
        saveCopy: (item?: ViralItem) => void | Promise<void>;
        copyText: (data: string, title: string) => void;
        viewDismissed: () => void;
        refreshKeywords: () => void | Promise<void>;
        openManualImport: () => void;
    };
}>();

const getPlatformMeta = (key: ViralPlatformKey) =>
    props.state.viralPlatformTabs.find((item) => item.key === key) ?? props.state.viralPlatformTabs[0];

const COVER_TONE: Record<ViralRealPlatformKey | "default", { from: string; to: string; badge: string; short: string }> =
    {
        douyin: { from: "#3A3A3C", to: "#111827", badge: "#111827", short: "抖" },
        xiaohongshu: { from: "#F87171", to: "#EF4444", badge: "#DC2626", short: "红" },
        kuaishou: { from: "#FF9A4D", to: "#FF6800", badge: "#FF6800", short: "快" },
        shipinhao: { from: "#4ADE80", to: "#22C55E", badge: "#16A34A", short: "视" },
        default: { from: "#7AA7F5", to: "#2F73F6", badge: "#2F73F6", short: "链" },
    };

const getCoverTone = (item: ViralItem) => COVER_TONE[item.platforms[0] || "default"] || COVER_TONE.default;

const isExpanded = (item: ViralItem) => props.state.expandedViralIds.includes(item.listKey || item.id);

/** source=manual 且 image_rewrite_status=0：排队等待解析，走独立等待态卡片 */
const isPendingManual = (item: ViralItem) => item.source === "manual" && Number(item.image_rewrite_status ?? 0) === 0;

const handleCopywritingInput = (item: ViralItem, event: any) => {
    props.actions.updateCopy(item, event.detail?.value ?? "");
};

// 追踪关键词折叠展示：默认显示前 N 项，超过即折叠为 "+M" 入口；UI 紧凑、不挤压"添加"按钮
const KEYWORD_VISIBLE_LIMIT = 8;
const keywordsExpanded = ref(false);

const visibleKeywords = computed(() =>
    keywordsExpanded.value ? props.state.viralKeywords : props.state.viralKeywords.slice(0, KEYWORD_VISIBLE_LIMIT),
);

const hiddenKeywordCount = computed(() => Math.max(0, props.state.viralKeywords.length - KEYWORD_VISIBLE_LIMIT));

// 关键词数据变化（切换人设 / 增删）后，自动收回展开态，避免视觉残留
watch(
    () => props.state.viralKeywords.length,
    () => {
        keywordsExpanded.value = false;
    },
);
</script>

<style scoped lang="scss">
.detail-card-shadow {
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
}

// 爆款库板块标题右侧的紧凑管理入口（不感兴趣 / 历史仿写）
.viral-action-pill {
    @apply px-[18rpx] py-[10rpx] rounded-[16rpx] flex items-center gap-[8rpx] active:opacity-70;
    background: #f4f6fa;
    border: 2rpx solid #e8ecf0;
}

.viral-manual-btn {
    @apply px-[18rpx] py-[10rpx] rounded-[16rpx] shrink-0 flex items-center gap-[8rpx] active:opacity-70;
    background: #ffffff;
    border: 2rpx dashed #2f73f6;
}

// 手动入库无封面：平台色渐变占位，状态可读
.viral-cover-ph {
    @apply w-[144rpx] h-[144rpx] rounded-[20rpx] shrink-0 flex flex-col items-center justify-center gap-[8rpx] relative overflow-hidden;
}

.viral-cover-ph-badge {
    @apply absolute top-[10rpx] left-[10rpx] w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center;
}

.viral-cover-ph-text {
    @apply text-[18rpx] font-semibold text-white opacity-90;
}

.viral-remark {
    @apply w-fit flex items-start gap-[8rpx] mt-[12rpx] px-[14rpx] py-[12rpx] rounded-[14rpx];
    background: #f3f7ff;
}

.viral-remark-text {
    @apply flex-1 text-[20rpx] text-[#5C7ECC] leading-relaxed;
}

// ===== 手动入库排队：等待态卡片 =====
.viral-card--pending {
    background: linear-gradient(180deg, #f7f9ff 0%, #ffffff 40%);
    border: 2rpx solid #e4ecff;
    box-shadow: 0 8rpx 28rpx rgba(47, 115, 246, 0.06);
}

.viral-pending {
    @apply px-[24rpx] pt-[22rpx] pb-[20rpx];
}

.viral-pending-meta {
    // 给右上角绝对定位「不感兴趣」留位，避免标签/日期被挡住
    @apply flex flex-wrap items-center gap-[8rpx] min-w-0 mb-[14rpx] pr-[120rpx];
}

.viral-tag-platform {
    @apply text-[18rpx] font-bold text-white px-[12rpx] py-[5rpx] rounded-[8rpx];
}

.viral-tag-manual {
    @apply text-[18rpx] font-semibold text-primary bg-[#EBF2FF] px-[12rpx] py-[5rpx] rounded-[8rpx];
}

.viral-tag-queue {
    @apply text-[18rpx] font-semibold px-[12rpx] py-[5rpx] rounded-[8rpx];
    color: #c2410c;
    background: #fff7ed;
}

.viral-date {
    @apply text-[20rpx] text-[#9CA3AF];
}

.viral-dismiss {
    @apply absolute top-[22rpx] right-[24rpx] z-10 text-[22rpx] text-[#9CA3AF] active:opacity-60;
}

.viral-pending-title {
    @apply text-sm font-bold text-[#1F2937] leading-relaxed line-clamp-2 block mb-[16rpx];
}

.viral-pending-status {
    @apply flex items-start gap-[14rpx] px-[18rpx] py-[16rpx] rounded-[18rpx] mb-[16rpx];
    background: #eef4ff;
    border: 2rpx solid #d7e5ff;
}

.viral-pending-status-icon {
    @apply w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center shrink-0;
    background: #ffffff;
}

.viral-pending-status-body {
    @apply flex-1 min-w-0 flex flex-col gap-[4rpx];
}

.viral-pending-status-label {
    @apply text-[24rpx] font-bold text-[#1A56CC] block;
}

.viral-pending-status-desc {
    @apply text-[20rpx] text-[#5C7ECC] leading-relaxed block;
}

.viral-pending-actions {
    @apply pt-[2rpx];
}

.viral-pending-copy-btn {
    @apply h-[72rpx] rounded-[18rpx] flex items-center justify-center gap-[8rpx] active:opacity-70;
    background: #ffffff;
    border: 2rpx solid #c9dbff;
}
</style>
