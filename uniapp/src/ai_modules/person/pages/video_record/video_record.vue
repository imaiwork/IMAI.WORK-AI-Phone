<template>
    <view class="min-h-screen bg-[#F6F7F9] pb-[env(safe-area-inset-bottom)]">
        <view class="flex flex-col pb-6">
            <view v-for="(group, groupIndex) in videoList" :key="groupIndex" class="mb-3">
                <view class="flex items-center gap-[12rpx] px-[30rpx] py-[20rpx]">
                    <view class="w-[6rpx] h-[22rpx] rounded-full bg-[#D1D5DB]"></view>
                    <text class="text-xs text-[#9CA3AF] font-medium">{{ group.date }}</text>
                    <view class="flex-1 h-[1rpx] bg-[#EEEEEE] ml-1"></view>
                </view>

                <view class="grid grid-cols-3 gap-[3rpx] px-[3rpx]">
                    <view
                        v-for="item in group.items"
                        :key="item.id"
                        class="relative aspect-[3/4] bg-[#F4F5F7] overflow-hidden rounded-[4rpx]">
                        <template v-if="item.status === VideoStatus.videoSuccess">
                            <image
                                :src="item.pic"
                                class="w-full h-full object-cover"
                                mode="aspectFill"
                                :lazy-load="true">
                            </image>

                            <view
                                class="absolute bottom-0 left-0 w-full h-[120rpx]"
                                style="background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent)"></view>

                            <view
                                v-if="getShanjianTypeTag(item.shanjian_type)"
                                class="absolute bottom-[44rpx] left-[14rpx] z-10 flex items-center gap-[4rpx] px-[8rpx] py-[3rpx] rounded-[4rpx]"
                                :style="`background: ${getShanjianTypeTag(item.shanjian_type)!.bg};`">
                                <text
                                    class="text-[18rpx] font-medium leading-none"
                                    :style="`color: ${getShanjianTypeTag(item.shanjian_type)!.color};`">
                                    {{ getShanjianTypeTag(item.shanjian_type)!.label }}
                                </text>
                            </view>

                            <text
                                class="absolute bottom-[14rpx] left-[14rpx] text-white text-[22rpx] font-medium drop-shadow z-10">
                                {{ formatAudioTime(item.duration) }}
                            </text>

                            <view
                                class="absolute inset-0 flex items-center justify-center z-10"
                                @click.stop="handleVideoClick(item)">
                                <view
                                    class="w-[72rpx] h-[72rpx] rounded-full bg-[#ffffff]/25 border border-solid border-[#ffffff]/50 flex items-center justify-center pl-[4rpx]">
                                    <u-icon name="play-right-fill" color="#ffffff" size="30"></u-icon>
                                </view>
                            </view>

                            <view
                                class="absolute bottom-[10rpx] right-[10rpx] z-40 w-[44rpx] h-[44rpx] rounded-full bg-[#000000]/40 flex items-center justify-center"
                                @click.stop="handleDelete(item.video_setting_id)">
                                <u-icon name="trash" color="#ffffff" size="22"></u-icon>
                            </view>
                        </template>

                        <template v-else-if="item.status === VideoStatus.videoFailed">
                            <view
                                class="w-full h-full flex flex-col items-center justify-center bg-[#FFF5F5] gap-y-[12rpx] p-[20rpx]">
                                <view class="relative">
                                    <view
                                        class="w-[72rpx] h-[72rpx] rounded-full bg-[#FEE2E2] flex items-center justify-center">
                                        <u-icon name="close-circle-fill" color="#F87171" size="40"></u-icon>
                                    </view>
                                    <view
                                        class="absolute inset-[-6rpx] rounded-full border-[2rpx] border-[#FCA5A5]/50"></view>
                                </view>
                                <text class="text-[22rpx] text-[#EF4444] font-semibold">生成失败</text>
                                <view class="flex flex-col items-center gap-[10rpx] z-40">
                                    <view
                                        class="flex items-center gap-[6rpx] bg-[#DBEAFE] px-[16rpx] py-[6rpx] rounded-full active:bg-[#BFDBFE]"
                                        @click.stop="handleRetry(item)">
                                        <u-icon name="reload" color="#0065fb" size="18"></u-icon>
                                        <text class="text-[20rpx] text-primary font-medium">重试生成</text>
                                    </view>
                                    <view
                                        class="flex items-center gap-[6rpx] bg-[#FEE2E2] px-[16rpx] py-[6rpx] rounded-full active:bg-[#FECACA]"
                                        @click.stop="handleViewFailReason(item)">
                                        <u-icon name="info-circle" color="#EF4444" size="18"></u-icon>
                                        <text class="text-[20rpx] text-[#EF4444] font-medium">查看原因</text>
                                    </view>
                                    <view
                                        class="flex items-center gap-[6rpx] bg-[#FEE2E2] px-[16rpx] py-[6rpx] rounded-full active:bg-[#FECACA]"
                                        @click.stop="handleDelete(item.video_setting_id)">
                                        <u-icon name="trash" color="#EF4444" size="18"></u-icon>
                                        <text class="text-[20rpx] text-[#EF4444] font-medium">删除</text>
                                    </view>
                                </view>
                            </view>
                        </template>

                        <template v-else-if="item.status === VideoStatus.pending">
                            <view
                                class="w-full h-full bg-[#F8F9FB] flex flex-col items-center justify-center gap-y-[14rpx]">
                                <view
                                    class="w-[68rpx] h-[68rpx] rounded-full bg-[#E9EBF0] flex items-center justify-center">
                                    <u-icon name="clock" color="#9CA3AF" size="32"></u-icon>
                                </view>
                                <text class="text-[22rpx] text-[#9CA3AF]">等待生成</text>
                                <view
                                    class="absolute top-[10rpx] right-[10rpx] z-40 w-[40rpx] h-[40rpx] rounded-full bg-[#00000010] flex items-center justify-center active:bg-[#00000020]"
                                    @click.stop="handleDelete(item.video_setting_id)">
                                    <u-icon name="trash" color="#9CA3AF" size="20"></u-icon>
                                </view>
                            </view>
                        </template>

                        <template v-else-if="item.status === VideoStatus.videoQuery">
                            <view
                                class="w-full h-full bg-[#EEF3FF] flex flex-col items-center justify-center gap-y-[16rpx]">
                                <view class="relative w-[72rpx] h-[72rpx]">
                                    <view
                                        class="absolute inset-0 rounded-full border-[4rpx] border-solid border-[#D0DEFF]"></view>
                                    <view
                                        class="absolute inset-0 rounded-full border-[4rpx] border-solid border-[transparent] border-t-primary animate-spin"></view>
                                    <view class="absolute inset-0 flex items-center justify-center">
                                        <u-icon name="video-camera" color="#0065fb" size="26"></u-icon>
                                    </view>
                                </view>
                                <text class="text-[22rpx] text-primary font-medium">生成中...</text>
                                <view class="flex items-center gap-[8rpx]">
                                    <view
                                        class="w-[10rpx] h-[10rpx] rounded-full bg-primary opacity-100 animate-bounce"
                                        style="animation-delay: 0ms"></view>
                                    <view
                                        class="w-[10rpx] h-[10rpx] rounded-full bg-primary opacity-70 animate-bounce"
                                        style="animation-delay: 150ms"></view>
                                    <view
                                        class="w-[10rpx] h-[10rpx] rounded-full bg-primary opacity-40 animate-bounce"
                                        style="animation-delay: 300ms"></view>
                                </view>
                            </view>
                        </template>

                        <view
                            v-if="getTagList(item).length > 0"
                            class="absolute top-0 left-0 right-0 z-30 flex flex-row flex-wrap gap-[6rpx] p-[10rpx] pr-[60rpx]"
                            style="background: linear-gradient(to bottom, rgba(0, 0, 0, 0.45) 0%, transparent 100%)">
                            <view
                                v-for="tag in getTagList(item)"
                                :key="tag.label"
                                class="flex items-center px-[8rpx] py-[4rpx] rounded-[4rpx]"
                                :style="`background:${tag.bg};`">
                                <text class="text-[16rpx] font-medium leading-none" :style="`color:${tag.color};`">
                                    {{ tag.label }}1
                                </text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view
                v-if="!loading && videoList.length === 0"
                class="flex flex-col items-center justify-center py-[160rpx] gap-[24rpx]">
                <view class="w-[120rpx] h-[120rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center">
                    <u-icon name="video-camera" color="#D1D5DB" size="56"></u-icon>
                </view>
                <text class="text-[#9CA3AF]">暂无生成记录</text>
            </view>

            <view class="flex items-center justify-center py-[24rpx] gap-[12rpx]">
                <block v-if="loading">
                    <u-loading mode="circle" size="28" color="#999999"></u-loading>
                    <text class="text-xs text-[#9ca3af] ml-2">加载中...</text>
                </block>
                <block v-else-if="finished && videoList.length > 0">
                    <view class="h-[2rpx] w-[80rpx] bg-[#E5E7EB]"></view>
                    <text class="text-[22rpx] text-[#C4C9D4] mx-3">已加载全部</text>
                    <view class="h-[2rpx] w-[80rpx] bg-[#E5E7EB]"></view>
                </block>
            </view>
        </view>
    </view>

    <video-preview-v2 :show="showVideoPreview" :video-url="videoUrl" @update:show="showVideoPreview = false" />
</template>

<script setup lang="ts">
import { getGenerateRecordList, retryGenerateRecord } from "@/api/person";
import { deleteShanjianTaskRecord } from "@/api/digital_human";
import { formatAudioTime } from "@/utils/util";

enum VideoStatus {
    pending = 0,
    videoQuery = 1,
    videoFailed = 2,
    videoSuccess = 3,
}

// ────────────────────────────────────────────────
// 标签配置
// ────────────────────────────────────────────────
interface TagConfig {
    label: string;
    bg: string;
    color: string;
}

// video_cover_source：2=AI自动  3=手动
const COVER_SOURCE_MAP: Record<number, TagConfig> = {
    2: { label: "AI封面", bg: "rgba(0,101,251,0.75)", color: "#ffffff" },
    3: { label: "手动封面", bg: "rgba(0,0,0,0.55)", color: "#ffffff" },
};

// visual_material_source：1=纯AI  2=AI+素材库  3=素材库
const MATERIAL_SOURCE_MAP: Record<number, TagConfig> = {
    1: { label: "纯AI", bg: "rgba(139,92,246,0.75)", color: "#ffffff" },
    2: { label: "AI+素材库", bg: "rgba(245,158,11,0.75)", color: "#ffffff" },
    3: { label: "素材库", bg: "rgba(16,185,129,0.75)", color: "#ffffff" },
};

// copywriting_source：1=爆款仿写  2=纯AI  3=无文案
const COPYWRITING_SOURCE_MAP: Record<number, TagConfig> = {
    1: { label: "爆款仿写", bg: "rgba(239,68,68,0.75)", color: "#ffffff" },
    2: { label: "纯AI文案", bg: "rgba(99,102,241,0.75)", color: "#ffffff" },
    3: { label: "无文案", bg: "rgba(107,114,128,0.7)", color: "#ffffff" },
};

// is_downgrade === 1 时显示的降级标签
const AI_DOWNGRADE_TAG: TagConfig = {
    label: "AI降级",
    bg: "rgba(234,88,12,0.75)",
    color: "#ffffff",
};

// shanjian_type：1=数字人口播  2=真人口播  3=素材  4=新闻体
const SHANJIAN_TYPE_MAP: Record<number, TagConfig> = {
    1: { label: "数字人口播", bg: "rgba(0,0,0,0.45)", color: "#ffffff" },
    2: { label: "真人口播", bg: "rgba(0,0,0,0.45)", color: "#ffffff" },
    3: { label: "素材", bg: "rgba(0,0,0,0.45)", color: "#ffffff" },
    4: { label: "新闻体", bg: "rgba(0,0,0,0.45)", color: "#ffffff" },
};

/**
 * 获取创作类型标签，未命中返回 undefined
 */
const getShanjianTypeTag = (type: number): TagConfig | undefined => {
    return SHANJIAN_TYPE_MAP[type];
};

/**
 * 将 item 上的三个 source 字段统一收集为标签数组
 * is_downgrade === 1：隐藏 MATERIAL_SOURCE_MAP 标签，改显示 AI降级 标签
 * 未命中 Map 的值返回 undefined，通过 filter(Boolean) 剔除
 */
const getTagList = (item: any): TagConfig[] => {
    const materialTag =
        item.is_downgrade === 1 ? AI_DOWNGRADE_TAG : MATERIAL_SOURCE_MAP[item.visual_material_source as number];
    // 朋友圈视频不展示文案来源标签（尤其是「爆款仿写」）
    const copyTag =
        Number(item.wechat_type) === 1 ? undefined : COPYWRITING_SOURCE_MAP[item.copywriting_source as number];

    return [COVER_SOURCE_MAP[item.video_cover_source as number], materialTag, copyTag].filter(Boolean) as TagConfig[];
};

// ────────────────────────────────────────────────
// 业务逻辑
// ────────────────────────────────────────────────
const personId = ref<string>("");
const videoList = ref<{ date: string; items: any[] }[]>([]);
const total = ref(0);
const loading = ref(false);
const finished = ref(false);

const queryParams = reactive({
    page_no: 1,
    page_size: 20,
    persona_id: "",
    auto_type: 1,
});

const showVideoPreview = ref<boolean>(false);
const videoUrl = ref<string>("");

const formatDateLabel = (dateStr: string): string => {
    if (!dateStr) return "未知日期";
    const today = new Date();
    const target = new Date(dateStr);
    const toDay = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
    const diffDays = Math.round((toDay(today) - toDay(target)) / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return "今天";
    if (diffDays === 1) return "昨天";
    if (diffDays === 2) return "前天";
    const y = target.getFullYear();
    const m = String(target.getMonth() + 1).padStart(2, "0");
    const d = String(target.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
};

const mergeIntoGroups = (lists: any[]) => {
    lists.forEach((item) => {
        const rawDate = item.update_time || "";
        const label = formatDateLabel(rawDate);
        const existing = videoList.value.find((g) => g.date === label);
        if (existing) {
            existing.items.push(item);
        } else {
            videoList.value.push({ date: label, items: [item] });
        }
    });
};

const getLists = async () => {
    if (loading.value || finished.value) return;
    try {
        loading.value = true;
        const { lists, count } = await getGenerateRecordList(queryParams);
        mergeIntoGroups(lists || []);
        total.value = count || 0;
        const loadedCount = videoList.value.reduce((sum, g) => sum + g.items.length, 0);
        if (loadedCount >= total.value) finished.value = true;
    } catch (error) {
        finished.value = true;
    } finally {
        loading.value = false;
    }
};

const handleVideoClick = (item: any) => {
    if (item.status !== VideoStatus.videoSuccess) return;
    showVideoPreview.value = true;
    videoUrl.value = item.video_result_url;
};

const handleViewFailReason = (item: any) => {
    uni.showModal({
        title: "失败原因",
        content: String(item?.remark || "").trim() || "暂无失败原因",
        showCancel: false,
        confirmText: "知道了",
    });
};

// 重试规则与后端一致：重试成功后按发布时间自动处理，已过时段需到发布记录手动重发
const RETRY_CONFIRM_CONTENT =
    "生成成功且未到发布时间：到点自动发布，无需操作；生成成功但已过发布时间：需到「发布记录」手动重新发送";

const handleRetry = (item: any) => {
    if (item.can_retry === false) {
        uni.showToast({ title: item.retry_disabled_reason || "当前视频不可重试", icon: "none", duration: 3000 });
        return;
    }
    uni.showModal({
        title: "重试生成视频",
        content: RETRY_CONFIRM_CONTENT,
        confirmText: "开始重试",
        cancelText: "取消",
        success: async ({ confirm }) => {
            if (!confirm) return;
            uni.showLoading({ title: "提交中...", mask: true });
            try {
                await retryGenerateRecord({ id: item.id });
                item.status = VideoStatus.pending;
                item.remark = "";
                uni.showToast({ title: "已开始重试", icon: "none", duration: 3000 });
            } catch (error: any) {
                uni.showToast({ title: error?.message || error || "重试失败", icon: "none", duration: 3000 });
            } finally {
                uni.hideLoading();
            }
        },
    });
};

const handleDelete = (id: string) => {
    uni.showModal({
        title: "删除记录",
        content: "确定删除该生成记录吗？删除后无法恢复",
        confirmColor: "#EF4444",
        confirmText: "删除",
        cancelText: "取消",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    await deleteShanjianTaskRecord({ id });
                    videoList.value = videoList.value
                        .map((group) => ({
                            ...group,
                            items: group.items.filter((item) => item.video_setting_id !== id),
                        }))
                        .filter((group) => group.items.length > 0);
                    total.value = Math.max(0, total.value - 1);
                    uni.hideLoading();
                    uni.showToast({ title: "删除成功", icon: "none", duration: 3000 });
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({
                        title: error?.message || "删除失败",
                        icon: "none",
                        duration: 3000,
                    });
                }
            }
        },
    });
};

onLoad((options: any) => {
    personId.value = options?.id || "";
    queryParams.persona_id = personId.value;
    getLists();
});

onReachBottom(() => {
    if (loading.value || finished.value) return;
    queryParams.page_no += 1;
    getLists();
});
</script>
