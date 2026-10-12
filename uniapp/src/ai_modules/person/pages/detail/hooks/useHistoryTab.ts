import { deleteShanjianTaskRecord, downloadShanjianVideoTask } from "@/api/digital_human";
import { deleteImageRecord, getGenerateRecordList, getImageRecordList, retryGenerateRecord } from "@/api/person";
import { VideoDownloadStatusEnum, isCloudRunMode } from "@/ai_modules/person/enums";
import { AppTypeEnum } from "@/enums/appEnums";
import { useCopy } from "@/hooks/useCopy";
import { saveVideoToPhotosAlbum } from "@/utils/file";
import { reactive, ref, type Ref } from "vue";

export enum VideoStatus {
    pending = 0,
    videoQuery = 1,
    videoFailed = 2,
    videoSuccess = 3,
}

export enum HistoryTabEnum {
    VIDEOS = "videos",
    IMAGES = "images",
}

/** 图生图成功（与后端 IMAGE_REWRITE_STATUS_SUCCESS 一致） */
const IMAGE_REWRITE_STATUS_SUCCESS = 3;

interface TagConfig {
    label: string;
    bg: string;
    color: string;
}

interface PlatformBadge {
    label: string;
    bg: string;
}

const COVER_SOURCE_MAP: Record<number, TagConfig> = {
    2: { label: "AI封面", bg: "#E6EFFF", color: "#0065FB" },
    3: { label: "手动封面", bg: "#F2F3F5", color: "#999999" },
};

const MATERIAL_SOURCE_MAP: Record<number, TagConfig> = {
    1: { label: "纯AI", bg: "#F3F0FF", color: "#6B3EFF" },
    2: { label: "AI+素材库", bg: "#FFF5E6", color: "#D46B08" },
    3: { label: "素材库", bg: "#F0FBE8", color: "#389E0D" },
};

const COPYWRITING_SOURCE_MAP: Record<number, TagConfig> = {
    1: { label: "爆款仿写", bg: "#FFEAEC", color: "#F56C6C" },
    2: { label: "AI生成", bg: "#EEF2FF", color: "#4F46E5" },
    3: { label: "无文案", bg: "#F2F3F5", color: "#999999" },
    4: { label: "文案库", bg: "#F0FBE8", color: "#389E0D" },
    5: { label: "热点生成", bg: "#FFF7E8", color: "#D46B08" },
};

const SHANJIAN_TYPE_MAP: Record<number, TagConfig> = {
    1: { label: "数字人口播", bg: "#E6EFFF", color: "#0065FB" },
    2: { label: "真人口播", bg: "#E6EFFF", color: "#0065FB" },
    3: { label: "素材混剪", bg: "#E6EFFF", color: "#0065FB" },
    4: { label: "新闻体", bg: "#E6EFFF", color: "#0065FB" },
};

const AI_DOWNGRADE_TAG: TagConfig = {
    label: "AI降级",
    bg: "#FFF7ED",
    color: "#EA580C",
};

const PLATFORM_BADGE_MAP: Record<number, PlatformBadge> = {
    [AppTypeEnum.SPH]: { label: "视频号", bg: "#07C160" },
    [AppTypeEnum.XHS]: { label: "小红书", bg: "#FF2442" },
    [AppTypeEnum.DOUYIN]: { label: "抖音", bg: "#111827" },
    [AppTypeEnum.KUAISHOU]: { label: "快手", bg: "#FF6800" },
};

const formatDateLabel = (dateStr: string): string => {
    if (!dateStr) return "未知日期";
    const today = new Date();
    const target = new Date(String(dateStr).replace(/-/g, "/"));
    if (Number.isNaN(target.getTime())) return "未知日期";
    const toDay = (date: Date) => new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
    const diffDays = Math.round((toDay(today) - toDay(target)) / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return "今天";
    if (diffDays === 1) return "昨天";
    if (diffDays === 2) return "前天";
    const month = target.getMonth() + 1;
    const day = target.getDate();
    return `${month}月${day}日`;
};

const mergeIntoGroups = (groups: Ref<{ date: string; items: any[] }[]>, lists: any[]) => {
    lists.forEach((item) => {
        const label = formatDateLabel(item.create_time || "");
        const existing = groups.value.find((group) => group.date === label);
        if (existing) {
            existing.items.push(item);
        } else {
            groups.value.push({ date: label, items: [item] });
        }
    });
};

export const useHistoryTab = (
    personId: Ref<string>,
    playItem: Ref<{ url: string; pic: string }>,
    showVideoPreview: Ref<boolean>,
    /** 人设运行模式，用于区分重试后的后续说明；不传按手机模式 */
    runMode?: Ref<number>,
) => {
    const historyTabs = [
        { key: HistoryTabEnum.VIDEOS, label: "自动生成的视频" },
        { key: HistoryTabEnum.IMAGES, label: "自动生成的图片" },
    ] as const;
    const activeHistoryTab = ref<HistoryTabEnum>(HistoryTabEnum.VIDEOS);
    const videoList = ref<{ date: string; items: any[] }[]>([]);
    const videoTotal = ref(0);
    const videoLoading = ref(false);
    const videoFinished = ref(false);
    const imageList = ref<{ date: string; items: any[] }[]>([]);
    const imageTotal = ref(0);
    const imageLoading = ref(false);
    const imageFinished = ref(false);
    const videoParams = reactive({
        page_no: 1,
        page_size: 20,
        persona_id: "",
        auto_type: 1,
    });
    const imageParams = reactive({
        page_no: 1,
        page_size: 20,
        persona_id: "",
        image_rewrite_status: IMAGE_REWRITE_STATUS_SUCCESS,
    });

    const formatRecordTime = (time: string) => {
        if (!time) return "";
        return uni.$u.timeFormat(String(time).replace(/-/g, "/"), "hh:MM");
    };

    const getVideoTagList = (item: any): TagConfig[] => {
        const materialTag =
            item.is_downgrade === 1 ? AI_DOWNGRADE_TAG : MATERIAL_SOURCE_MAP[item.visual_material_source as number];
        // 朋友圈视频不展示文案来源标签（尤其是「爆款仿写」）
        const copyTag =
            Number(item.wechat_type) === 1 ? undefined : COPYWRITING_SOURCE_MAP[item.copywriting_source as number];

        return [
            SHANJIAN_TYPE_MAP[item.shanjian_type as number],
            COVER_SOURCE_MAP[item.video_cover_source as number],
            materialTag,
            copyTag,
        ].filter(Boolean) as TagConfig[];
    };

    const getImageUrls = (item: any): string[] => {
        const rawImages = item.images ?? item.rewritten_images ?? item.original_images ?? [];
        const images = Array.isArray(rawImages) ? rawImages : [rawImages];
        return images
            .map((image) => (typeof image === "string" ? image : image?.url || image?.image_url))
            .map((url) => String(url || "").trim())
            .filter(Boolean);
    };

    const getImageTags = (item: any): string[] => {
        const raw = item.copywriting?.analysis_tags ?? item.analysis_tags ?? item.tags ?? item.copywriting?.tags ?? [];
        const list = Array.isArray(raw) ? raw : typeof raw === "string" ? raw.split(/[,，\s]+/) : [];
        return list
            .map((tag) => {
                const text = String(typeof tag === "string" ? tag : tag?.name || tag?.tag || tag?.label || "").trim();
                if (!text) return "";
                return text.startsWith("#") ? text : `#${text}`;
            })
            .filter(Boolean);
    };

    const getImagePlatformBadge = (item: any): PlatformBadge | null => {
        const platform = Number(item.publish_platform || item.platform || item.account_type || 0);
        if (PLATFORM_BADGE_MAP[platform]) return PLATFORM_BADGE_MAP[platform];
        const name = String(item.platform_name || "").trim();
        if (!name) return null;
        return { label: name, bg: "#999999" };
    };

    const getImageTitle = (item: any): string =>
        String(item.title || item.copywriting?.title || item.keyword || "AI自动生成图片").trim();

    const getImageRecordTime = (item: any): string => item.update_time || item.create_time || item.day || "";

    const getImageGroupDate = (item: any): string =>
        formatDateLabel(item.day || item.update_time || item.create_time || "");

    const getRecordStatusLabel = (status: VideoStatus) => {
        const map = {
            [VideoStatus.pending]: "等待生成",
            [VideoStatus.videoQuery]: "生成中",
            [VideoStatus.videoFailed]: "生成失败",
            [VideoStatus.videoSuccess]: "生成成功",
        };
        return map[status] || "未知状态";
    };

    const getRecordStatusClass = (status: VideoStatus) => {
        if (status === VideoStatus.videoSuccess) return "text-[#19BE6B] bg-[#E8F9F0]";
        if (status === VideoStatus.videoFailed) return "text-[#F56C6C] bg-[#FFEAEC]";
        if (status === VideoStatus.videoQuery) return "text-primary bg-[#E6EFFF]";
        return "text-[#999999] bg-[#F2F3F5]";
    };

    const queryVideoList = async () => {
        if (videoLoading.value || videoFinished.value) return;
        try {
            videoLoading.value = true;
            const { lists, count } = await getGenerateRecordList(videoParams);
            mergeIntoGroups(videoList, lists || []);
            videoTotal.value = count || 0;
            const loadedCount = videoList.value.reduce((sum, group) => sum + group.items.length, 0);
            if (loadedCount >= videoTotal.value) videoFinished.value = true;
        } catch {
            videoFinished.value = true;
        } finally {
            videoLoading.value = false;
        }
    };

    const resetVideoList = async () => {
        videoParams.page_no = 1;
        videoParams.persona_id = personId.value;
        videoList.value = [];
        videoFinished.value = false;
        await queryVideoList();
    };

    const loadNextVideoPage = () => {
        if (videoLoading.value || videoFinished.value) return;
        videoParams.page_no += 1;
        queryVideoList();
    };

    const queryImageList = async () => {
        if (imageLoading.value || imageFinished.value) return;
        try {
            imageLoading.value = true;
            const { lists, count } = await getImageRecordList(imageParams);
            (lists || []).forEach((item: any) => {
                const label = getImageGroupDate(item);
                const existing = imageList.value.find((group) => group.date === label);
                if (existing) {
                    existing.items.push(item);
                } else {
                    imageList.value.push({ date: label, items: [item] });
                }
            });
            imageTotal.value = count || 0;
            const loadedCount = imageList.value.reduce((sum, group) => sum + group.items.length, 0);
            if (loadedCount >= imageTotal.value) imageFinished.value = true;
        } catch {
            imageFinished.value = true;
        } finally {
            imageLoading.value = false;
        }
    };

    const resetImageList = () => {
        imageParams.page_no = 1;
        imageParams.persona_id = personId.value;
        imageParams.image_rewrite_status = IMAGE_REWRITE_STATUS_SUCCESS;
        imageList.value = [];
        imageFinished.value = false;
        queryImageList();
    };

    const loadNextImagePage = () => {
        if (imageLoading.value || imageFinished.value) return;
        imageParams.page_no += 1;
        queryImageList();
    };

    const handleSwitchHistoryTab = (tab: HistoryTabEnum | string) => {
        const next = tab === HistoryTabEnum.IMAGES ? HistoryTabEnum.IMAGES : HistoryTabEnum.VIDEOS;
        if (activeHistoryTab.value === next) return;
        activeHistoryTab.value = next;
        if (next === HistoryTabEnum.IMAGES && !imageList.value.length && !imageLoading.value) {
            resetImageList();
        }
        if (next === HistoryTabEnum.VIDEOS && !videoList.value.length && !videoLoading.value) {
            resetVideoList();
        }
    };

    const showVideoDetail = ref(false);
    const showImageDetail = ref(false);
    const currentVideoDetail = ref<Record<string, any> | null>(null);
    const currentImageDetail = ref<Record<string, any> | null>(null);

    const handleVideoClick = (item: any) => {
        if (!item) return;
        currentVideoDetail.value = item;
        showVideoDetail.value = true;
    };

    const handlePlayVideoDetail = () => {
        const item = currentVideoDetail.value;
        if (!item || item.status !== VideoStatus.videoSuccess) return;
        const url = String(item.video_result_url || "").trim();
        if (!url) {
            uni.showToast({ title: "暂无可播放视频", icon: "none" });
            return;
        }
        playItem.value = { url, pic: item.pic || "" };
        showVideoPreview.value = true;
    };

    /**
     * 下载成片到相册。无手机模式下用户要自己去平台发，这是拿到视频的唯一途径。
     * 成片没转存回本站前 video_result_url 还是上游的远端链接，小程序 downloadFile 会被
     * 域名白名单拦掉，所以先调转存接口把它落到本站，再保存。
     */
    const downloadingId = ref<string | number | null>(null);
    const handleDownloadVideo = async (item: any) => {
        if (!item || Number(item.status) !== VideoStatus.videoSuccess) return;
        if (downloadingId.value === item.id) return;
        downloadingId.value = item.id;
        try {
            if (Number(item.download_status) !== VideoDownloadStatusEnum.SUCCESS) {
                uni.showLoading({ title: "准备中...", mask: true });
                try {
                    const res: any = await downloadShanjianVideoTask({ id: item.id });
                    item.download_status = Number(res?.download_status ?? VideoDownloadStatusEnum.SUCCESS);
                    if (res?.video_result_url) item.video_result_url = res.video_result_url;
                } finally {
                    uni.hideLoading();
                }
            }
            const url = String(item.video_result_url || "").trim();
            if (!url || Number(item.download_status) !== VideoDownloadStatusEnum.SUCCESS) {
                uni.showToast({ title: "视频还没准备好，稍后再试", icon: "none" });
                return;
            }
            await saveVideoToPhotosAlbum(url);
        } catch (error: any) {
            uni.showToast({ title: String(error || "下载失败，请重试"), icon: "none", duration: 3000 });
        } finally {
            downloadingId.value = null;
        }
    };

    /** 复制口播文案，口径与视频详情弹窗的文案块一致 */
    const handleCopyVideoText = (item: any) => {
        const text = String(item?.msg || item?.title || "").trim();
        if (!text) {
            uni.showToast({ title: "这条视频没有文案", icon: "none" });
            return;
        }
        const { copy } = useCopy();
        copy(text);
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
    // 手机模式重试后由绑定手机到点发布；无手机模式压根不发布，讲发布时间会误导
    const retryConfirmContent = (): string =>
        isCloudRunMode(runMode?.value)
            ? "重试成功后成片会重新出现在内容记录里，可自行下载视频、复制文案去平台发布"
            : "生成成功且未到发布时间：到点自动发布，无需操作；生成成功但已过发布时间：需到「发布记录」手动重新发送";

    const handleRetryRecord = (item: any) => {
        if (!item) return;
        if (item.can_retry === false) {
            uni.showToast({ title: item.retry_disabled_reason || "当前视频不可重试", icon: "none", duration: 3000 });
            return;
        }
        uni.showModal({
            title: "重试生成视频",
            content: retryConfirmContent(),
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
                    uni.showToast({
                        title: error?.message || error || "重试失败",
                        icon: "none",
                        duration: 3000,
                    });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    const handleImageClick = (item: any) => {
        if (!item) return;
        currentImageDetail.value = item;
        showImageDetail.value = true;
    };

    const handleDeleteVideoRecord = (id: string) => {
        if (!id) return;
        uni.showModal({
            title: "删除记录",
            content: "确定删除该生成记录吗？删除后无法恢复",
            confirmColor: "#F56C6C",
            confirmText: "删除",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    await deleteShanjianTaskRecord({ id });
                    videoList.value = videoList.value
                        .map((group) => ({
                            ...group,
                            items: group.items.filter((item) => item.video_setting_id !== id),
                        }))
                        .filter((group) => group.items.length > 0);
                    videoTotal.value = Math.max(0, videoTotal.value - 1);
                    uni.showToast({ title: "删除成功", icon: "none", duration: 3000 });
                } catch (error: any) {
                    uni.showToast({
                        title: error?.message || "删除失败",
                        icon: "none",
                        duration: 3000,
                    });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    const removeImageRecordLocally = (id: number | string) => {
        imageList.value = imageList.value
            .map((group) => ({
                ...group,
                items: group.items.filter((item) => String(item.id) !== String(id)),
            }))
            .filter((group) => group.items.length > 0);
        imageTotal.value = Math.max(0, imageTotal.value - 1);
        if (currentImageDetail.value && String(currentImageDetail.value.id) === String(id)) {
            showImageDetail.value = false;
            currentImageDetail.value = null;
        }
    };

    const handleDeleteImageRecord = (id: number | string) => {
        if (id === undefined || id === null || id === "") return;
        uni.showModal({
            title: "删除记录",
            content: "确定删除该图片记录吗？删除后无法恢复",
            confirmColor: "#F56C6C",
            confirmText: "删除",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    await deleteImageRecord({ ids: [id] });
                    uni.hideLoading();
                    uni.showToast({ title: "删除成功", icon: "none", duration: 3000 });
                    removeImageRecordLocally(id);
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({
                        title: error || "删除失败",
                        icon: "none",
                        duration: 3000,
                    });
                }
            },
        });
    };

    return {
        activeHistoryTab,
        currentImageDetail,
        currentVideoDetail,
        formatRecordTime,
        getImagePlatformBadge,
        getImageRecordTime,
        getImageTags,
        getImageTitle,
        getRecordStatusClass,
        getRecordStatusLabel,
        getVideoTagList,
        getImageUrls,
        handleCopyVideoText,
        handleDeleteImageRecord,
        handleDeleteVideoRecord,
        handleDownloadVideo,
        handleImageClick,
        handlePlayVideoDetail,
        handleRetryRecord,
        handleSwitchHistoryTab,
        handleVideoClick,
        handleViewFailReason,
        historyTabs,
        imageFinished,
        imageList,
        imageLoading,
        imageTotal,
        loadNextImagePage,
        loadNextVideoPage,
        queryVideoList,
        resetImageList,
        resetVideoList,
        showImageDetail,
        showVideoDetail,
        videoFinished,
        videoList,
        videoLoading,
        videoParams,
        videoTotal,
    };
};
