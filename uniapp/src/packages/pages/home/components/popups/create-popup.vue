<template>
    <popup-bottom
        v-model="show"
        height="88%"
        custom-class="bg-[#f4f5f9]"
        :clearable="false"
        :mask-close-able="true"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="bg-white px-[40rpx] py-[24rpx] shadow-[0_4rpx_24rpx_rgba(0,0,0,0.06)]">
                <view class="w-[80rpx] h-[8rpx] mx-auto mb-[28rpx] bg-[#e5e7eb] rounded-full" />
                <view class="mb-[24rpx] flex items-center justify-between">
                    <text class="block text-[36rpx] font-bold text-[#1f2937]">多平台内容AI创作</text>
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-[#f3f4f6] flex items-center justify-center text-[44rpx] leading-none"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" color="#8B9199" :size="20"></u-icon>
                    </view>
                </view>
                <scroll-view v-if="tabs.length" scroll-x class="mt-[16rpx] whitespace-nowrap" show-scrollbar="false">
                    <view class="inline-flex gap-[16rpx]">
                        <view
                            v-for="tab in tabs"
                            :key="tab.platform"
                            class="flex-shrink-0 rounded-full px-[28rpx] py-[12rpx] text-xs font-bold whitespace-nowrap"
                            :class="
                                activeTab === tab.platform ? 'text-white bg-primary' : 'text-[#4b5563] bg-[#f3f4f6]'
                            "
                            @click="switchTab(tab)">
                            <text>{{ tab.platform_name }}（{{ tab.count }}）</text>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="p-[32rpx] pb-[80rpx] flex flex-col gap-[32rpx]">
                    <view v-if="loading" class="text-center text-xs text-[#9ca3af] py-[40rpx]"> 加载中... </view>
                    <view
                        v-else-if="!items.length"
                        class="min-h-[340rpx] bg-white rounded-[32rpx] flex flex-col items-center justify-center gap-[20rpx] text-xs">
                        <text
                            class="w-[88rpx] h-[88rpx] rounded-full bg-[#f3f4f6] flex items-center justify-center text-[40rpx] text-[#9ca3af]">
                            ▣
                        </text>
                        <text class="text-sm font-bold text-[#374151]">今日暂无发布计划</text>
                        <text class="text-xs text-[#9ca3af]">该平台今日暂无内容安排</text>
                    </view>
                    <view v-for="item in items" v-else :key="item.id" class="bg-white rounded-[32rpx] overflow-hidden">
                        <view
                            v-if="!isSocial(item) && item.publish_time"
                            class="flex items-center gap-[12rpx] px-[28rpx] pt-[24rpx] pb-[20rpx] border-[0] border-b-[2rpx] border-[#f9fafb]">
                            <text class="text-xs font-bold text-[#374151]">
                                {{ formatPublishTime(item.publish_time) }}
                            </text>
                            <text
                                class="px-[16rpx] py-[4rpx] rounded-full text-[20rpx] font-bold"
                                :class="
                                    item.media_type === VIDEO_MEDIA_TYPE
                                        ? 'text-[#9333ea] bg-[#faf5ff]'
                                        : 'text-primary bg-primary-light-9'
                                ">
                                {{ item.media_label || (item.media_type === VIDEO_MEDIA_TYPE ? "视频" : "图片") }}
                            </text>
                        </view>
                        <view
                            v-if="hasVideo(item)"
                            class="relative h-[340rpx] overflow-hidden bg-black"
                            @click="previewVideo(item)">
                            <image
                                v-if="item.cover_url"
                                :src="item.cover_url"
                                mode="aspectFill"
                                class="absolute inset-0 w-full h-full" />
                            <view
                                v-if="shanjianTag(item)"
                                class="absolute top-[20rpx] left-[20rpx] px-[18rpx] py-[6rpx] rounded-full text-[20rpx] font-bold"
                                :style="{
                                    background: shanjianTag(item).bg,
                                    color: shanjianTag(item).color,
                                }">
                                {{ shanjianTag(item).label }}
                            </view>
                            <view
                                v-if="durationText(item)"
                                class="absolute bottom-[20rpx] right-[20rpx] px-[14rpx] py-[4rpx] rounded-[12rpx] text-[18rpx] font-bold text-white bg-[rgba(0,0,0,0.55)]">
                                {{ durationText(item) }}
                            </view>
                            <view class="absolute inset-0 flex items-center justify-center">
                                <view
                                    class="w-[88rpx] h-[88rpx] rounded-full bg-[rgba(255,255,255,0.25)] flex items-center justify-center border-[2rpx] border-[rgba(255,255,255,0.5)]">
                                    <u-icon name="play-right-fill" color="#ffffff" :size="36"></u-icon>
                                </view>
                            </view>
                        </view>
                        <view
                            v-else-if="isVideoGenerating(item)"
                            class="relative h-[340rpx] overflow-hidden bg-[#f3f4f6]">
                            <image
                                v-if="item.cover_url"
                                :src="item.cover_url"
                                mode="aspectFill"
                                class="absolute inset-0 w-full h-full" />
                            <view class="absolute inset-0 flex items-center justify-center">
                                <view
                                    class="flex flex-col items-center gap-[8rpx] px-[28rpx] py-[20rpx] rounded-[20rpx] bg-[rgba(0,0,0,0.5)]">
                                    <text class="text-sm font-bold text-white">视频生成中…</text>
                                    <text class="text-xs text-[rgba(255,255,255,0.85)]">
                                        {{ item.shanjian_status_text || "请稍后查看" }}
                                    </text>
                                </view>
                            </view>
                        </view>
                        <view
                            v-else-if="hasImages(item)"
                            class="grid grid-cols-3 gap-[4rpx] rounded-[24rpx] overflow-hidden mx-[28rpx] mt-[28rpx]">
                            <view
                                v-for="(url, i) in item.media_urls.slice(0, IMAGE_GRID_LIMIT)"
                                :key="i"
                                class="relative w-full h-[192rpx] overflow-hidden"
                                @click="previewImages(item, i)">
                                <image :src="url" class="w-full h-full" mode="aspectFill" />
                                <view
                                    v-if="showImageMoreMask(item, i)"
                                    class="absolute inset-0 flex items-center justify-center bg-[rgba(0,0,0,0.5)]">
                                    <text class="text-[32rpx] font-bold text-white">
                                        +{{ item.media_urls.length - IMAGE_GRID_LIMIT }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view class="p-[28rpx] flex flex-col gap-[24rpx]">
                            <view
                                v-if="canRegenerate(item)"
                                class="flex gap-[16rpx] pb-[20rpx] border-[0] border-b-[2rpx] border-[#f9fafb]">
                                <view
                                    class="flex-1 flex items-center justify-center rounded-[24rpx] px-[24rpx] py-[20rpx] text-xs font-semibold text-primary bg-primary-light-9"
                                    @click="handleRegenerate(item)">
                                    重新生成
                                </view>
                            </view>
                            <editable-field
                                v-if="isSocial(item)"
                                label="发布标题"
                                v-model="item.title"
                                @save="handleSave(item)" />
                            <editable-field
                                :label="isSocial(item) ? '发布文案' : '朋友圈文案'"
                                v-model="item.content"
                                multiline
                                @save="handleSave(item)" />
                            <editable-tags v-if="isSocial(item)" v-model="item._topicTags" @save="handleSave(item)" />
                            <text v-if="item.poi || item.location" class="text-xs text-[#9ca3af]">
                                ⌖ {{ item.poi || item.location }}
                            </text>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>

    <confirm-dialog
        v-model="showRegenerateConfirm"
        content="检测到当前接近发布时间，由于生成的视频时间可能较长，如果在发布节点前未能成功生产视频将导致任务失败，是否确定重新生成"
        confirm-text="确定重新生成"
        @confirm="settleRegenerateConfirm(true)"
        @close="settleRegenerateConfirm(false)" />

    <video-preview v-model="showVideo" title="视频预览" :video-url="videoUrl" :poster="videoPoster" />
</template>

<script setup lang="ts">
import EditableField from "../editable-field.vue";
import EditableTags from "../editable-tags.vue";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import { getPublishContentList, updatePublishContent, regeneratePublishContentVideo } from "@/api/person";
import { PublishSourceEnum } from "@/enums/publishEnums";
import { formatAudioTime } from "@/utils/util";

interface PublishContentTab {
    platform: string | number;
    platform_name: string;
    source: PublishSourceEnum;
    count: number;
}
interface PublishContentItem {
    id: number | string;
    source: PublishSourceEnum;
    platform: string | number;
    platform_name: string;
    publish_time: string;
    media_type: number;
    media_label: string;
    media_urls: string[];
    duration?: number | string;
    shanjian_type?: number;
    account: string;
    title: string;
    content: string;
    topic: string;
    poi: string;
    location: string;
    _topicTags: string[];
    shanjian_video_task_id?: number;
    cover_url?: string;
    shanjian_status?: number;
    shanjian_status_text?: string;
    shanjian_video_url?: string;
    can_regenerate?: boolean;
}

const props = defineProps<{
    modelValue: boolean;
    personaId?: string | number;
    asset?: (name: string) => string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "toast", message: string): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v) => emit("update:modelValue", v),
});

const tabs = ref<PublishContentTab[]>([]);
const activeTab = ref<string | number>("");
const activeSource = ref<PublishSourceEnum>(PublishSourceEnum.SOCIAL);
const date = ref("");
const items = ref<PublishContentItem[]>([]);
const loading = ref(false);

const VIDEO_MEDIA_TYPE = 1;
const IMAGE_GRID_LIMIT = 9;

const isSocial = (item: PublishContentItem) => item.source === PublishSourceEnum.SOCIAL;
const hasVideo = (item: PublishContentItem) =>
    item.media_type === VIDEO_MEDIA_TYPE && Array.isArray(item.media_urls) && item.media_urls.length > 0;
const hasImages = (item: PublishContentItem) =>
    item.media_type !== VIDEO_MEDIA_TYPE && Array.isArray(item.media_urls) && item.media_urls.length > 0;
/** 超过 9 张时，最后一格显示「+N」蒙版 */
const showImageMoreMask = (item: PublishContentItem, index: number) =>
    index === IMAGE_GRID_LIMIT - 1 && (item.media_urls?.length || 0) > IMAGE_GRID_LIMIT;
// 视频类内容但暂无可播放视频（重新生成中/待处理）：展示生成状态占位
const isVideoGenerating = (item: PublishContentItem) => item.media_type === VIDEO_MEDIA_TYPE && !hasVideo(item);
// 是否允许重新生成：后端给了 can_regenerate 以其为准，缺省时退回「有视频才可重新生成」
const canRegenerate = (item: PublishContentItem) => item.can_regenerate ?? hasVideo(item);

// publish_time 形如 2026-06-17 07:52:00，仅展示时分
const formatPublishTime = (time: string) => {
    const m = String(time || "").match(/(\d{1,2}):(\d{2})/);
    return m ? `${m[1].padStart(2, "0")}:${m[2]}` : time || "";
};

// duration 为秒，统一用 formatAudioTime 格式化为 mm:ss；为 0 / 空时不展示
const durationText = (item: PublishContentItem) => {
    const sec = Number(item.duration) || 0;
    return sec ? formatAudioTime(sec) : "";
};

// shanjian_type：1=数字人口播 2=真人口播 3=素材 4=新闻体
interface TagConfig {
    label: string;
    bg: string;
    color: string;
}
const SHANJIAN_TYPE_MAP: Record<number, TagConfig> = {
    1: { label: "数字人口播", bg: "rgba(0,0,0,0.45)", color: "#ffffff" },
    2: { label: "真人口播", bg: "rgba(0,0,0,0.45)", color: "#ffffff" },
    3: { label: "素材", bg: "rgba(0,0,0,0.45)", color: "#ffffff" },
    4: { label: "新闻体", bg: "rgba(0,0,0,0.45)", color: "#ffffff" },
};
const shanjianTag = (item: PublishContentItem): TagConfig | null =>
    SHANJIAN_TYPE_MAP[Number(item.shanjian_type)] || null;

const showVideo = ref(false);
const videoUrl = ref("");
const videoPoster = ref("");
const previewVideo = (item: PublishContentItem) => {
    videoUrl.value = item.media_urls?.[0] || "";
    videoPoster.value = item.cover_url || "";
    if (videoUrl.value) showVideo.value = true;
};

const previewImages = (item: PublishContentItem, index: number) => {
    const urls = (item.media_urls || []).filter(Boolean);
    if (!urls.length) return;
    uni.previewImage({
        urls,
        current: urls[Math.min(index, urls.length - 1)],
    });
};

const topicToTags = (topic: string): string[] =>
    String(topic || "")
        .split(/[#\s]+/)
        .filter(Boolean);
const tagsToTopic = (tags: string[]): string => tags.map((t) => `#${t}`).join(" ");

const loadList = async (platform?: string | number, source?: PublishSourceEnum) => {
    loading.value = true;
    try {
        const data: any = await getPublishContentList({
            persona_id: props.personaId,
            platform: (platform ?? activeTab.value ?? "") as any,
        });
        date.value = String(data?.date || "");
        if (Array.isArray(data?.tabs)) tabs.value = data.tabs;
        // 首次打开（未指定 platform）：默认选中第一个 tab，并按其平台参数请求对应内容
        if (!platform) {
            const first = tabs.value[0];
            if (first) {
                activeTab.value = first.platform;
                activeSource.value = first.source;
                if (first.platform !== data?.platform) return loadList(first.platform, first.source);
            }
        }
        const raw = Array.isArray(data?.lists) ? data.lists : [];
        items.value = raw.map((r: any) => ({
            ...r,
            _topicTags: topicToTags(r.topic),
        }));
    } catch (error) {
        items.value = [];
    } finally {
        loading.value = false;
    }
};

const switchTab = (tab: PublishContentTab) => {
    if (activeTab.value === tab.platform) return;
    activeTab.value = tab.platform;
    activeSource.value = tab.source;
    loadList(tab.platform, tab.source);
};

const handleSave = async (item: PublishContentItem) => {
    try {
        await updatePublishContent({
            id: item.id,
            source: item.source,
            title: item.source === PublishSourceEnum.SOCIAL ? item.title : undefined,
            content: item.content,
            topic: item.source === PublishSourceEnum.SOCIAL ? tagsToTopic(item._topicTags) : undefined,
        });
        emit("toast", "已保存");
    } catch (error) {
        console.warn("updatePublishContent failed", error);
        emit("toast", "保存失败");
    }
};

const ONE_HOUR = 60 * 60 * 1000;

// publish_time 可能是完整日期时间，也可能只有时分，后者需结合当日 date 还原为时间戳；无法解析返回 null 不拦截
const resolvePublishTimestamp = (item: PublishContentItem): number | null => {
    const raw = String(item.publish_time || "").trim();
    if (!raw) return null;
    const hasDate = /\d{4}\D\d{1,2}\D\d{1,2}/.test(raw);
    const source = hasDate ? raw : `${date.value} ${raw}`.trim();
    const nums = source.match(/\d+/g);
    if (!nums || nums.length < 5) return null;
    const [y, mo, d, h, mi, s = "0"] = nums;
    const ts = new Date(Number(y), Number(mo) - 1, Number(d), Number(h), Number(mi), Number(s)).getTime();
    return Number.isNaN(ts) ? null : ts;
};

// 临近发布时间（距发布不足 1 小时）时二次确认，避免视频未生成完成导致任务失败。
// 用 confirm-dialog 组件替代 uni.showModal（小程序端弹窗内的 showModal 不稳定）。
const showRegenerateConfirm = ref(false);
let regenerateResolver: ((confirmed: boolean) => void) | null = null;

const settleRegenerateConfirm = (confirmed: boolean) => {
    regenerateResolver?.(confirmed);
    regenerateResolver = null;
};

const confirmRegenerateNearPublish = (item: PublishContentItem): Promise<boolean> => {
    const ts = resolvePublishTimestamp(item);
    if (ts === null || ts - Date.now() > ONE_HOUR) return Promise.resolve(true);
    return new Promise((resolve) => {
        regenerateResolver = resolve;
        showRegenerateConfirm.value = true;
    });
};

const handleRegenerate = async (item: PublishContentItem) => {
    if (!(await confirmRegenerateNearPublish(item))) return;
    try {
        const res: any = await regeneratePublishContentVideo({
            id: item.id,
            shanjian_video_task_id: item.shanjian_video_task_id || 0,
            date: date.value,
        });
        // 用返回值回填视频/生成状态（media_urls 已清空、can_regenerate 变更、状态更新），
        // 同时保留用户当前未保存的标题/文案编辑；接口异常返回时兜底清空旧视频
        if (res && typeof res === "object") {
            Object.assign(item, res, {
                title: item.title,
                content: item.content,
                _topicTags: item._topicTags,
            });
        } else {
            item.media_urls = [];
        }
        emit("toast", "正在重新生成...");
    } catch (error: any) {
        emit("toast", error || "重新生成失败");
    }
};

watch(
    () => props.modelValue,
    (v) => {
        if (v) loadList();
    },
);
</script>
