<template>
    <view>
        <view v-if="isCloudMode" class="viral-cloud-tip">
            <u-icon name="info-circle" color="#536A87" size="34" class="shrink-0 mt-[4rpx]"></u-icon>
            <view class="flex-1 min-w-0 flex flex-col">
                <text class="text-xs font-bold">无手机模式 · 手动准备参考作品</text>
                <text class="text-xs leading-[1.65]">
                    当前不会自动采集爆款。复制想参考的作品链接，手动加入这个人设。
                </text>
            </view>
        </view>

        <view class="viral-keywords">
            <view class="viral-heading mb-[24rpx]">
                <view class="viral-heading-copy">
                    <text class="viral-heading-title">爆款追踪词</text>
                    <text class="viral-meta">{{ state.viralKeywords.length }} 个追踪词</text>
                </view>
                <view class="flex flex-wrap items-center gap-[8rpx]">
                    <view class="viral-text-btn active:opacity-70" @click="actions.refreshKeywords()">
                        <u-icon name="reload" color="#0761D9" size="24"></u-icon>
                        <text>换一批</text>
                    </view>
                    <view class="viral-text-btn active:opacity-70" @click="actions.editKeywords()">
                        <text>＋ 新增</text>
                    </view>
                </view>
            </view>
            <text class="viral-meta block">
                {{
                    isCloudMode
                        ? "保留当前内容方向，自动采集需切换手机模式并关联设备。"
                        : "关联 AI 手机后，按这些关键词自动采集参考作品。"
                }}
            </text>

            <view class="flex flex-wrap gap-x-[12rpx] mt-[12rpx]">
                <view
                    v-for="(keyword, index) in visibleKeywords"
                    :key="keyword"
                    class="viral-word active:opacity-70"
                    @click="actions.editKeywords(keyword, index)">
                    <text class="viral-word-label">{{ keyword }}</text>
                    <view class="viral-word-x" @click.stop="actions.removeKeyword(keyword, index)">
                        <u-icon name="close" color="#075ECF" size="16"></u-icon>
                    </view>
                </view>
                <view
                    v-if="hiddenKeywordCount > 0 && !keywordsExpanded"
                    class="viral-word viral-word--ghost active:opacity-70"
                    @click="keywordsExpanded = true">
                    <text class="viral-word-label">+{{ hiddenKeywordCount }}</text>
                </view>
                <view
                    v-if="keywordsExpanded && state.viralKeywords.length > KEYWORD_VISIBLE_LIMIT"
                    class="viral-word viral-word--ghost active:opacity-70"
                    @click="keywordsExpanded = false">
                    <text class="viral-word-label">收起</text>
                </view>
            </view>
        </view>

        <view class="mb-[24rpx]">
            <scroll-view scroll-x class="w-full" :show-scrollbar="false">
                <view class="flex gap-[8rpx] whitespace-nowrap">
                    <view
                        v-for="tab in state.viralPlatformTabs"
                        :key="tab.key"
                        class="viral-plat"
                        :class="state.activeViralPlatform === tab.key ? 'viral-plat--on' : ''"
                        @click="actions.platform(tab.key)">
                        <text class="viral-plat-label">
                            {{ tab.label }} {{ state.viralPlatformCounts[tab.key] || 0 }}
                        </text>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view class="flex flex-col gap-[14rpx] mb-[24rpx]">
            <view class="viral-heading">
                <view class="viral-heading-copy">
                    <text class="viral-heading-title">参考作品</text>
                    <text class="viral-meta">{{ state.filteredViralList.length }} 条</text>
                </view>
                <view class="viral-import-btn active:opacity-70" @click="actions.openManualImport()">
                    <text class="viral-control-content">手动入库</text>
                </view>
            </view>

            <text class="viral-meta">
                {{
                    isCloudMode
                        ? "手动加入的作品与解析进度会显示在这里。"
                        : "设备每晚 00:00 开始采集，可提前移除不需要的参考作品。"
                }}
            </text>
            <view class="flex items-center gap-[24rpx]">
                <view class="viral-text-btn active:opacity-70" @click="actions.viewDismissed()">
                    <u-icon name="eye-off" color="#0761D9" size="24"></u-icon>
                    <text>已忽略作品</text>
                </view>
                <view class="viral-text-btn active:opacity-70" @click="actions.history()">
                    <u-icon name="clock" color="#0761D9" size="24"></u-icon>
                    <text>内容记录</text>
                </view>
            </view>
        </view>

        <view v-if="state.filteredViralList.length" class="flex flex-col gap-[24rpx]">
            <view v-for="item in state.filteredViralList" :key="item.listKey || item.id" class="viral-card">
                <!-- 排队中的手动入库：等待态卡片，不套「就绪素材」双栏操作 -->
                <template v-if="isPendingManual(item)">
                    <view class="viral-pending">
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
                                <u-icon name="clock" color="#0065FB" size="28"></u-icon>
                            </view>
                            <view class="viral-pending-status-body">
                                <text class="viral-pending-status-label">{{
                                    isCloudMode ? "等待解析原内容" : "今晚 00:00–03:00 自动解析"
                                }}</text>
                                <text class="viral-pending-status-desc">
                                    {{
                                        item.remark ||
                                        (isCloudMode
                                            ? "链接已登记，解析完成后可查看仿写文案。"
                                            : "将按人设下全部设备解析入库，完成后可查看仿写文案")
                                    }}
                                </text>
                            </view>
                        </view>

                        <view class="viral-pending-actions">
                            <view class="viral-pending-copy-btn" @click="actions.copyText(item.link, '链接已复制')">
                                <u-icon name="attach" color="#0065FB" size="24"></u-icon>
                                <text>复制原链接</text>
                            </view>
                            <view class="viral-foot-ghost active:opacity-60" @click="actions.dismiss(item)">
                                <text>不感兴趣</text>
                            </view>
                        </view>
                    </view>
                </template>

                <!-- 已就绪：封面 + 仿写文案 + 双操作 -->
                <template v-else>
                    <view class="relative">
                        <view class="flex gap-[22rpx] p-[32rpx]">
                            <image
                                v-if="item.cover"
                                :src="item.cover"
                                mode="aspectFill"
                                lazy-load
                                class="w-[144rpx] h-[144rpx] rounded-[20rpx] shrink-0 bg-[#F2F3F5]"></image>
                            <view v-else class="viral-cover-ph">
                                <view class="viral-cover-ph-badge" :style="{ background: getCoverTone(item).badge }">
                                    <text class="text-[20rpx] font-bold text-white">
                                        {{ getCoverTone(item).short }}
                                    </text>
                                </view>
                                <u-icon name="attach" color="#0065FB" size="36"></u-icon>
                                <text class="viral-cover-ph-text">{{ item.statusText || "无封面" }}</text>
                            </view>
                            <view class="flex-1 min-w-0">
                                <text class="viral-item-title line-clamp-2 mb-[10rpx]">
                                    {{ item.title || "已入库，等待解析原视频信息…" }}
                                </text>
                                <view class="flex flex-wrap items-center gap-[8rpx]">
                                    <text
                                        v-for="platform in item.platforms"
                                        :key="platform"
                                        class="text-[18rpx] font-bold text-white px-[12rpx] py-[5rpx] rounded-[8rpx]"
                                        :style="`background:${getPlatformMeta(platform).color}`">
                                        {{ getPlatformMeta(platform).label }}
                                    </text>
                                    <text
                                        v-if="item.isManualImport"
                                        class="text-[18rpx] font-semibold text-primary bg-[#E6EFFF] px-[12rpx] py-[5rpx] rounded-[8rpx]">
                                        手动入库
                                    </text>
                                    <text
                                        v-else
                                        class="text-[20rpx] font-semibold text-[#999999] bg-[#F4F6FA] px-[12rpx] py-[5rpx] rounded-[8rpx]">
                                        词条：{{ item.keyword }}
                                    </text>
                                    <text
                                        v-if="item.statusText"
                                        class="text-[18rpx] font-semibold text-[#F86E21] bg-[#FFF7E8] px-[12rpx] py-[5rpx] rounded-[8rpx]">
                                        {{ item.statusText }}
                                    </text>
                                    <text class="viral-meta">{{ item.date }}</text>
                                </view>
                                <view v-if="item.remark" class="viral-remark">
                                    <text>
                                        <u-icon name="info-circle" color="#666666" size="22" class="shrink-0"></u-icon>
                                    </text>
                                    <text class="viral-remark-text">{{ item.remark }}</text>
                                </view>
                            </view>
                        </view>

                        <view v-if="isExpanded(item)" class="viral-copy-editor">
                            <view class="flex items-center gap-[8rpx] mb-[14rpx]">
                                <u-icon name="edit-pen" color="#0065FB" size="22"></u-icon>
                                <text class="text-xs font-bold text-[#666666] flex-1">AI 仿写文案</text>
                                <text
                                    class="text-[20rpx] font-semibold text-primary bg-[#E6EFFF] px-[12rpx] py-[4rpx] rounded-full">
                                    可编辑
                                </text>
                            </view>
                            <textarea
                                :value="item.copywriting"
                                class="w-full min-h-[220rpx] box-border text-sm text-content leading-relaxed bg-white rounded-[18rpx] p-[18rpx] break-all"
                                :maxlength="-1"
                                @input="handleCopywritingInput(item, $event)"></textarea>
                            <view class="flex gap-[14rpx] mt-[16rpx]">
                                <view class="viral-save-btn" @click="actions.saveCopy(item)">
                                    <u-icon class="viral-control-content" name="checkmark" color="#FFFFFF" size="22"></u-icon>
                                    <text class="viral-control-content">保存</text>
                                </view>
                                <view class="viral-copy-btn" @click="actions.copyText(item.copywriting, '文案已复制')">
                                    <u-icon class="viral-control-content" name="file-text" color="#075ECF" size="22"></u-icon>
                                    <text class="viral-control-content">复制</text>
                                </view>
                            </view>
                        </view>

                        <view class="viral-foot">
                            <view class="viral-foot-ghost active:opacity-60" @click="actions.dismiss(item)">
                                <text>不感兴趣</text>
                            </view>
                            <view class="flex-1"></view>
                            <view
                                class="viral-foot-btn active:opacity-70"
                                @click="actions.copyText(item.link, '链接已复制')">
                                <u-icon name="attach" color="#0761D9" size="24"></u-icon>
                                <text>复制链接</text>
                            </view>
                            <view class="viral-foot-btn active:opacity-70" @click="actions.toggleCopy(item)">
                                <u-icon
                                    :name="isExpanded(item) ? 'arrow-up' : 'file-text'"
                                    color="#0065FB"
                                    size="24"></u-icon>
                                <text>
                                    {{ isExpanded(item) ? "收起文案" : "查看仿写文案" }}
                                </text>
                            </view>
                        </view>
                    </view>
                </template>
            </view>
        </view>

        <view v-else class="viral-empty">
            <view class="viral-empty-icon">
                <u-icon name="attach" color="#1764CD" size="46"></u-icon>
            </view>
            <text class="viral-empty-title">{{ isCloudMode ? "先放入一条参考作品" : "还没有参考作品" }}</text>
            <text class="viral-empty-description">
                {{
                    isCloudMode
                        ? "复制抖音、小红书等平台的作品分享链接，手动加入此人设。"
                        : "配置追踪词并关联设备后，可自动采集。也可以先手动加入想参考的作品。"
                }}
            </text>
            <view class="viral-empty-btn active:opacity-70" @click="actions.openManualImport()">
                <text>粘贴作品链接</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { isCloudRunMode } from "@/ai_modules/person/enums";
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
        runMode: number;
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

const COVER_TONE: Record<ViralRealPlatformKey | "default", { badge: string; short: string }> = {
    douyin: { badge: "#111827", short: "抖" },
    xiaohongshu: { badge: "#CC2330", short: "红" },
    kuaishou: { badge: "#FF6800", short: "快" },
    shipinhao: { badge: "#19BE6B", short: "视" },
    default: { badge: "#0065FB", short: "链" },
};

const getCoverTone = (item: ViralItem) => COVER_TONE[item.platforms[0] || "default"] || COVER_TONE.default;

const isExpanded = (item: ViralItem) => props.state.expandedViralIds.includes(item.listKey || item.id);

/** source=manual 且 image_rewrite_status=0：排队等待解析，走独立等待态卡片 */
const isPendingManual = (item: ViralItem) => item.source === "manual" && Number(item.image_rewrite_status ?? 0) === 0;

const handleCopywritingInput = (item: ViralItem, event: any) => {
    props.actions.updateCopy(item, event.detail?.value ?? "");
};

// 追踪词折叠展示：默认只显示前 3 个，超出折叠成 "+M" 入口，展开后可收起，
const isCloudMode = computed(() => isCloudRunMode(props.state.runMode));

const KEYWORD_VISIBLE_LIMIT = 3;
const keywordsExpanded = ref(false);

const visibleKeywords = computed(() =>
    keywordsExpanded.value ? props.state.viralKeywords : props.state.viralKeywords.slice(0, KEYWORD_VISIBLE_LIMIT),
);

const hiddenKeywordCount = computed(() => Math.max(0, props.state.viralKeywords.length - KEYWORD_VISIBLE_LIMIT));

// 关键词数据变化（切换人设 / 增删）后，自动收回展开态，避免视觉残留
// 只在「已经没有可展开的了」时收起。原来一变长度就收，
// 导致展开后删掉任意一个词，整个列表就被折回去
watch(
    () => props.state.viralKeywords.length,
    (count) => {
        if (count <= KEYWORD_VISIBLE_LIMIT) keywordsExpanded.value = false;
    },
);
</script>

<style scoped lang="scss">
.viral-empty {
    @apply px-[40rpx] py-[60rpx] bg-white rounded-[36rpx] text-center;
}

.viral-empty-icon {
    @apply w-[96rpx] h-[96rpx] mx-auto rounded-[28rpx] bg-[#EDF3FF] flex items-center justify-center;
}

.viral-empty-title {
    @apply block text-[34rpx] font-bold leading-[1.55] text-[#233047] mt-[32rpx] mb-[18rpx];
}

.viral-empty-description {
    @apply block max-w-[600rpx] mx-auto mb-[32rpx] text-sm leading-[1.8] text-[#647184];
}

.viral-card {
    @apply bg-white rounded-[36rpx] overflow-hidden;
}

.viral-keywords {
    @apply mb-[24rpx] p-[32rpx] bg-white rounded-[36rpx];
}

.viral-heading {
    @apply flex flex-wrap items-center justify-between gap-[16rpx];
}

.viral-heading-copy {
    @apply flex-1 min-w-[220rpx] flex flex-col gap-[8rpx];
}

.viral-heading-title {
    @apply text-base font-[650] leading-[1.55] text-[#202937];
}

.viral-meta {
    @apply text-xs leading-[1.65] text-[#647184];
}

.viral-item-title {
    @apply text-base font-[620] leading-[1.55] text-[#26364E];
}

.viral-foot {
    @apply flex flex-wrap items-center gap-[8rpx] mx-[32rpx] pb-[32rpx]
        border-[0] border-t-[2rpx] border-solid border-[#E7ECF2];
}

.viral-cloud-tip {
    @apply box-border flex items-start gap-[16rpx] mb-[24rpx] p-[24rpx] rounded-[24rpx]
        bg-[#EDF3FC] text-[#536A87] text-xs leading-[1.7] border-[2rpx] border-solid border-[#E0E9F7];
}

.viral-word {
    @apply relative box-border max-w-full min-h-[88rpx] inline-flex items-center justify-center pl-[20rpx];
}

// 标签底色收紧，编辑和删除仍保留完整的触控高度。
.viral-word::before {
    content: "";
    @apply absolute inset-x-[0] top-[12rpx] bottom-[12rpx] rounded-[16rpx] bg-[#EDF4FF] pointer-events-none;
}

.viral-word-label {
    @apply relative min-w-0 py-[20rpx] text-[26rpx] font-medium leading-[1.4] text-[#075ECF] break-all;
}

.viral-word-x {
    @apply relative w-[88rpx] h-[88rpx] shrink-0 rounded-full flex items-center justify-center;
}

.viral-word--ghost {
    @apply min-w-[88rpx] px-[16rpx];
}

.viral-word--ghost::before {
    @apply hidden;
}

.viral-word--ghost .viral-word-label {
    @apply text-[#0761D9] font-medium;
}

.viral-plat {
    @apply relative box-border shrink-0 min-h-[88rpx] min-w-[88rpx] px-[20rpx]
        flex items-center justify-center active:opacity-70;
}

.viral-plat-label {
    @apply relative text-xs font-medium leading-[1.4] text-[#627085];
}

.viral-plat--on::before {
    content: "";
    @apply absolute inset-x-0 top-[14rpx] bottom-[14rpx] rounded-[12rpx] bg-[#E8F1FF] pointer-events-none;
}

.viral-plat--on .viral-plat-label {
    @apply text-[#075ECF];
}

.viral-empty-btn {
    @apply box-border inline-flex items-center justify-center gap-[14rpx] min-h-[88rpx]
        px-[32rpx] py-[20rpx] rounded-[24rpx] border-[2rpx] border-solid border-[transparent]
        text-base font-semibold leading-[1.4] bg-[#EDF4FF] text-[#075ECF] active:opacity-70;
}

.viral-import-btn,
.viral-save-btn,
.viral-copy-btn {
    @apply relative box-border inline-flex items-center justify-center gap-[8rpx] min-h-[88rpx] min-w-[88rpx]
        px-[16rpx] text-xs font-medium leading-[1.4] text-[#075ECF] active:opacity-70;
}

.viral-import-btn::before,
.viral-save-btn::before,
.viral-copy-btn::before {
    content: "";
    @apply absolute inset-x-0 top-[14rpx] bottom-[14rpx] rounded-[12rpx] border-[2rpx] border-solid border-[transparent] pointer-events-none;
}

.viral-copy-btn::before {
    @apply bg-[#EDF4FF];
}

.viral-import-btn {
    @apply text-[#36516F];
}

.viral-import-btn::before {
    @apply bg-white border-[#D8E2ED];
}

.viral-save-btn {
    @apply text-white;
}

.viral-save-btn::before {
    @apply bg-primary;
}

.viral-control-content {
    @apply relative;
}

.viral-text-btn,
.viral-foot-btn,
.viral-foot-ghost,
.viral-pending-copy-btn {
    @apply box-border shrink-0 min-h-[88rpx] min-w-[88rpx] inline-flex items-center justify-center gap-[8rpx]
        px-[10rpx] text-xs font-medium leading-[1.4] text-[#0761D9] active:opacity-70;
}

.viral-copy-editor {
    @apply mx-[32rpx] mb-[24rpx] rounded-[28rpx] p-[28rpx] bg-[#F7F9FC]
        border-[2rpx] border-solid border-[#E5EBF3];
}

.viral-cover-ph {
    @apply w-[144rpx] h-[144rpx] rounded-[20rpx] shrink-0 flex flex-col items-center justify-center gap-[8rpx] relative overflow-hidden bg-primary-light-9;
}

.viral-cover-ph-badge {
    @apply absolute top-[10rpx] left-[10rpx] w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center;
}

.viral-cover-ph-text {
    @apply text-[20rpx] font-medium text-primary-dark-3;
}

.viral-remark {
    @apply flex items-start gap-[8rpx] mt-[12rpx] rounded-[14rpx];
}

.viral-remark-text {
    @apply flex-1 min-w-0 text-xs text-[#647184] leading-[1.65] break-all;
}

.viral-pending {
    @apply p-[32rpx];
}

.viral-pending-meta {
    @apply flex flex-wrap items-center gap-[8rpx] min-w-0 mb-[14rpx];
}

.viral-tag-platform {
    @apply text-[18rpx] font-bold text-white px-[12rpx] py-[5rpx] rounded-[8rpx];
}

.viral-tag-manual {
    @apply text-[18rpx] font-semibold text-primary bg-[#E6EFFF] px-[12rpx] py-[5rpx] rounded-[8rpx];
}

.viral-tag-queue {
    @apply text-[18rpx] font-semibold px-[12rpx] py-[5rpx] rounded-[8rpx];
    color: #c2410c;
    background: #fff7ed;
}

.viral-date {
    @apply text-xs leading-[1.65] text-[#647184];
}

.viral-pending-title {
    @apply text-base font-[620] text-[#26364E] leading-[1.55] line-clamp-2 block mb-[16rpx];
}

.viral-pending-status {
    @apply flex items-start gap-[14rpx] py-[16rpx] mb-[16rpx];
}

.viral-pending-status-icon {
    @apply w-[48rpx] h-[48rpx] rounded-[14rpx] flex items-center justify-center shrink-0 bg-primary-light-9;
}

.viral-pending-status-body {
    @apply flex-1 min-w-0 flex flex-col gap-[4rpx];
}

.viral-pending-status-label {
    @apply text-xs font-medium text-[#647184] leading-[1.65] block;
}

.viral-pending-status-desc {
    @apply text-xs text-[#647184] leading-[1.65] block;
}

.viral-pending-actions {
    @apply flex flex-wrap items-center justify-between gap-[24rpx];
}
</style>
