<template>
    <view class="min-h-screen bg-[#F4F6FB] relative overflow-x-hidden">
        <view class="w-full h-[580rpx] absolute top-0 left-0">
            <image
                :src="`${config.baseUrl}static/images/mp/create_page_bg.png`"
                class="w-full h-full"
                mode="aspectFill" />
            <view
                class="absolute -right-[60rpx] -top-[60rpx] w-[280rpx] h-[280rpx] rounded-full bg-[#ffffff]/10 pointer-events-none" />
            <view
                class="absolute -left-[40rpx] bottom-[-20rpx] w-[200rpx] h-[200rpx] rounded-full bg-[#ffffff]/5 pointer-events-none" />
        </view>
        <view class="px-[32rpx] pt-[180rpx] pb-[200rpx] relative">
            <view
                class="bg-white rounded-[40rpx] px-[40rpx] pt-[40rpx] pb-[36rpx] shadow-sm border border-solid border-[#f9f9f9] mb-[24rpx]">
                <view class="flex items-start justify-between mb-[32rpx]">
                    <view>
                        <view class="flex items-center gap-x-[10rpx] mb-[8rpx]">
                            <view class="w-[8rpx] h-[36rpx] bg-primary rounded-full"></view>
                            <text class="text-[40rpx] font-extrabold text-[#212121] tracking-tight">AI视频创作</text>
                        </view>
                        <text class="text-[24rpx] text-[#676767] ml-[18rpx]">0基础高效创作</text>
                    </view>
                    <view class="w-[80rpx] h-[80rpx] rounded-[24rpx] bg-[#EEF4FF] flex items-center justify-center">
                        <image
                            src="@/ai_modules/digital_human/static/images/common/add.png"
                            class="w-[36rpx] h-[36rpx]" />
                    </view>
                </view>

                <view
                    class="w-full h-[96rpx] rounded-full flex items-center justify-center gap-x-[12rpx] shadow-md active:opacity-90 relative overflow-hidden"
                    style="background: linear-gradient(to right, #367af7 0%, #04eefb 100%)"
                    @click="toPage(MenuKey.CHOOSE_CREATE_TYPE)">
                    <image src="@/ai_modules/digital_human/static/images/common/add.png" class="w-[28rpx] h-[28rpx]" />
                    <text class="text-[30rpx] font-bold text-white">开始创作</text>
                    <view class="absolute left-[-20rpx] bottom-[-15rpx] opacity-20 pointer-events-none">
                        <image
                            src="@/ai_modules/digital_human/static/images/home/ai_tag.png"
                            class="w-[104rpx] h-[80rpx]" />
                    </view>
                </view>
            </view>

            <view class="grid grid-cols-2 gap-[20rpx] mb-[24rpx]">
                <view
                    class="bg-white rounded-[28rpx] p-[32rpx] shadow-sm border border-solid border-[#f9f9f9] relative overflow-hidden active:opacity-90"
                    @click="toPage(MenuKey.SORA)">
                    <view class="flex items-center gap-x-[10rpx] mb-[8rpx]">
                        <view class="w-[6rpx] h-[24rpx] bg-primary rounded-full"></view>
                        <text class="text-[28rpx] font-bold text-[#212121]">一句话创作</text>
                    </view>
                    <text class="text-[22rpx] text-[#676767] ml-[16rpx]">一语生成视频</text>
                    <image
                        src="@/ai_modules/digital_human/static/images/home/mask_bg1.png"
                        class="w-[90rpx] h-[90rpx] absolute bottom-[8rpx] right-[8rpx] opacity-20" />
                </view>
                <view
                    class="bg-white rounded-[28rpx] p-[32rpx] shadow-sm border border-solid border-[#f9f9f9] relative overflow-hidden active:opacity-90"
                    @click="toPage(MenuKey.MONTAGE_MIX)">
                    <view class="flex items-center gap-x-[10rpx] mb-[8rpx]">
                        <view class="w-[6rpx] h-[24rpx] bg-[#10B981] rounded-full"></view>
                        <text class="text-[28rpx] font-bold text-[#212121]">数字人混剪</text>
                    </view>
                    <text class="text-[22rpx] text-[#676767] ml-[16rpx]">智剪爆款视频</text>
                    <image
                        src="@/ai_modules/digital_human/static/images/home/mask_bg2.png"
                        class="w-[90rpx] h-[90rpx] absolute bottom-[8rpx] right-[8rpx] opacity-20" />
                </view>
            </view>

            <view
                class="bg-white rounded-[32rpx] px-[24rpx] py-[32rpx] shadow-sm border border-solid border-[#f9f9f9] mb-[40rpx]">
                <view class="grid grid-cols-4 gap-y-[36rpx]">
                    <view
                        v-for="(menu, index) in showUtils"
                        :key="index"
                        class="flex flex-col items-center gap-y-[12rpx] active:opacity-70"
                        @click="toPage(menu.key)">
                        <view class="w-[88rpx] h-[88rpx] bg-[#F4F6FB] rounded-[24rpx] flex items-center justify-center">
                            <image :src="menu.icon" class="w-[44rpx] h-[44rpx]" />
                        </view>
                        <text class="text-[22rpx] font-medium text-gray-600 text-center">{{ menu.label }}</text>
                    </view>
                </view>
            </view>

            <view class="mb-[40rpx]">
                <view class="flex items-center justify-between mb-[20rpx]">
                    <view class="flex items-center gap-x-[10rpx]">
                        <view class="w-[6rpx] h-[28rpx] bg-primary rounded-full"></view>
                        <text class="text-[28rpx] font-bold text-[#212121]">我的创作库</text>
                    </view>
                    <view
                        class="flex items-center gap-x-[6rpx] bg-white px-[20rpx] py-[8rpx] rounded-full shadow-sm border border-solid border-[#f9f9f9] active:opacity-70"
                        @click="toPage(MenuKey.ME_CREATE)">
                        <text class="text-[22rpx] text-[#676767]">更多</text>
                        <u-icon name="arrow-right" size="20" color="#CBD5E1" />
                    </view>
                </view>

                <view class="relative">
                    <scroll-view scroll-x v-if="worksLists.length > 0">
                        <view class="flex whitespace-nowrap gap-x-[16rpx]">
                            <view
                                v-for="(item, index) in worksLists"
                                :key="index"
                                class="shrink-0 w-[224rpx] h-[288rpx] rounded-[24rpx] overflow-hidden shadow-sm">
                                <view class="h-full w-full relative">
                                    <image :src="item.pic" class="h-full w-full" mode="aspectFill" />

                                    <view
                                        v-if="item.automatic_clip == 1"
                                        class="absolute top-[12rpx] left-[12rpx] bg-primary px-[12rpx] py-[4rpx] rounded-full">
                                        <text class="text-[18rpx] text-white font-medium">AI剪辑</text>
                                    </view>

                                    <view
                                        v-if="getStatus(item) == 1"
                                        class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center z-10"
                                        @click="handlePlayCheck(item)">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/video_play.svg"
                                            class="w-[64rpx] h-[64rpx]" />
                                        <view
                                            v-if="item.automatic_clip"
                                            class="mt-[12rpx] px-[16rpx] py-[6rpx] bg-[#000000]/30 rounded-full">
                                            <template v-if="item.clip_status == 1 || item.clip_status == 2">
                                                <text class="text-[20rpx] text-white">AI智能剪辑中...</text>
                                            </template>
                                            <template v-if="item.clip_status == 3">
                                                <text class="text-[20rpx] text-white">AI智能剪辑完成</text>
                                            </template>
                                            <template v-if="item.clip_status == 4">
                                                <text class="text-[20rpx] text-white">AI智能剪辑失败</text>
                                            </template>
                                        </view>

                                        <view
                                            v-if="showDownloadStatusUi(item)"
                                            class="absolute inset-0 flex flex-col items-center justify-center bg-[#000000]/40 z-20 px-[12rpx]"
                                            @click.stop>
                                            <template v-if="isDownloadFailed(item)">
                                                <view
                                                    class="bg-[#EF4444] px-[16rpx] py-[6rpx] rounded-full mb-[8rpx]">
                                                    <text class="text-[20rpx] text-white font-medium">下载失败</text>
                                                </view>
                                                <view
                                                    class="px-[20rpx] py-[8rpx] rounded-full bg-primary active:opacity-80"
                                                    @click.stop="handleRedownload(item)">
                                                    <text class="text-[20rpx] text-white font-medium">
                                                        {{
                                                            redownloadId === item.id ? "下载中..." : "重新下载"
                                                        }}
                                                    </text>
                                                </view>
                                            </template>
                                            <template v-else>
                                                <text class="rotation mb-[8rpx]"></text>
                                                <text class="text-[20rpx] text-[#ffffff]/80">下载中...</text>
                                            </template>
                                        </view>
                                    </view>

                                    <view
                                        v-else
                                        class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center bg-[#000000]/40 z-10">
                                        <template v-if="getStatus(item) == 2">
                                            <view class="bg-[#EF4444] px-[20rpx] py-[8rpx] rounded-full mb-[12rpx]">
                                                <text class="text-[22rpx] text-white font-medium">生成失败</text>
                                            </view>
                                            <text
                                                class="text-[20rpx] text-[#ffffff]/80 text-center px-[16rpx] whitespace-pre-line"
                                                >{{ item.remark }}</text
                                            >
                                        </template>
                                        <template v-else>
                                            <text class="rotation mb-[8rpx]"></text>
                                            <text class="text-[22rpx] text-[#ffffff]/80">正在生成中</text>
                                            <text class="text-[20rpx] text-[#ffffff]/60 mt-[4rpx]"
                                                >几分钟即可生成视频</text
                                            >
                                        </template>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                    <view v-else class="my-4">
                        <empty :size="250" />
                    </view>
                    <view v-if="worksLists.length >= 3" class="fade-mask-right"></view>
                </view>
            </view>

            <view class="mb-[40rpx]">
                <view class="flex items-center justify-between mb-[20rpx]">
                    <view class="flex items-center gap-x-[10rpx]">
                        <view class="w-[6rpx] h-[28rpx] bg-[#10B981] rounded-full"></view>
                        <text class="text-[28rpx] font-bold text-[#212121]">数字人形象</text>
                    </view>
                    <view
                        v-if="isReleaseVersion()"
                        class="flex items-center gap-x-[6rpx] bg-white px-[20rpx] py-[8rpx] rounded-full shadow-sm border border-solid border-[#f9f9f9] active:opacity-70"
                        @click="toPage(MenuKey.ME_CLONE)">
                        <text class="text-[22rpx] text-[#676767]">更多</text>
                        <u-icon name="arrow-right" size="20" color="#CBD5E1" />
                    </view>
                </view>

                <view class="relative">
                    <scroll-view scroll-x v-if="anchorLists.length > 0">
                        <view class="flex whitespace-nowrap gap-x-[16rpx]">
                            <view
                                v-for="(item, index) in anchorLists"
                                :key="index"
                                class="shrink-0 w-[224rpx] h-[288rpx] rounded-[24rpx] overflow-hidden shadow-sm">
                                <anchor-video
                                    :show-name="false"
                                    :show-more="false"
                                    :item="item"
                                    @play="handlePlay($event, item.pic)" />
                            </view>
                        </view>
                    </scroll-view>
                    <view v-else class="my-4">
                        <empty :size="250" />
                    </view>
                    <view v-if="anchorLists.length >= 3" class="fade-mask-right"></view>
                </view>
            </view>
        </view>

        <tabbar />
    </view>

    <video-preview-v2
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false" />
</template>

<script setup lang="ts">
import config from "@/config";
import { getVideoCreationRecord } from "@/api/app";
import { getPublicAnchorList, downloadShanjianVideoTask } from "@/api/digital_human";
import { isReleaseVersion } from "@/utils/env";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { ModeTypeEnum, VideoDownloadStatusEnum } from "@/ai_modules/digital_human/enums";
import AnchorCloneIcon from "@/ai_modules/digital_human/static/icons/anchor_clone.svg";
import ToneCloneIcon from "@/ai_modules/digital_human/static/icons/tone_clone.svg";
import MeCreateIcon from "@/ai_modules/digital_human/static/icons/me_create.svg";
import MontageScanIcon from "@/ai_modules/digital_human/static/icons/scan.svg";
import MaterialLibraryIcon from "@/ai_modules/digital_human/static/icons/material_library.svg";
import ImageCreateIcon from "@/ai_modules/digital_human/static/icons/image_create.svg";
import StoryboardIcon from "@/ai_modules/digital_human/static/icons/storyboard.svg";
import HotWriteIcon from "@/ai_modules/digital_human/static/icons/video3.svg";
import AnchorVideo from "@/ai_modules/digital_human/components/anchor-video/anchor-video.vue";

enum MenuKey {
    CHOOSE_CREATE_TYPE = "choose_create_type",
    VIDEO_MIX = "video_mix",
    ANCHOR = "anchor_clone",
    TONE = "tone_clone",
    TEXT_EXTRACT = "text_extract",
    ME_CLONE = "ME_CLONE",
    ME_CREATE = "me_create",
    MONTAGE_RECORD = "montage_record",
    MONTAGE_BATCH = "montage_batch",
    SORA = "sora",
    MATERIAL_LIBRARY = "material_library",
    PLATFORM_PUBLISH = "platform_publish",
    IMAGE_CREATE = "image_create",
    BOMB_COPY = "bomb_copy",
    FRAGMENT_MIX = "fragment_mix",
    SCAN_PUBLISH_WORKS = "scan_publish_works",
    MONTAGE_MIX = "montage_mix",
    HOT_WRITE = "hot_write",
}

const worksLists = ref<any[]>([]);
const anchorLists = ref<any[]>([]);
const redownloadId = ref<number | null>(null);

const playItem = reactive<any>({
    url: "",
    pic: "",
});
const showVideoPreview = ref(false);

const getDownloadStatus = (item: any) => Number(item?.download_status ?? VideoDownloadStatusEnum.SUCCESS);
const isDownloadFailed = (item: any) => getDownloadStatus(item) === VideoDownloadStatusEnum.FAILED;
const isDownloading = (item: any) => getDownloadStatus(item) === VideoDownloadStatusEnum.DOWNLOADING;
const showDownloadStatusUi = (item: any) => isDownloadFailed(item) || isDownloading(item);

const utils_2 = [
    { label: "我的创作", key: MenuKey.ME_CREATE, icon: MeCreateIcon },
    { label: "素材库", key: MenuKey.MATERIAL_LIBRARY, icon: MaterialLibraryIcon },
    { label: "数字人形象", key: MenuKey.ANCHOR, icon: AnchorCloneIcon },
    { label: "数字人声音", key: MenuKey.TONE, icon: ToneCloneIcon },
    { label: "图片创作", key: MenuKey.IMAGE_CREATE, icon: ImageCreateIcon },
    { label: "分镜混剪", key: MenuKey.FRAGMENT_MIX, icon: StoryboardIcon },
    // { label: "爆款仿写", key: MenuKey.BOMB_COPY, icon: BombCopyIcon },
    // { label: "文案提取", key: MenuKey.TEXT_EXTRACT, icon: TextExtractIcon },
    { label: "扫码发布", key: MenuKey.SCAN_PUBLISH_WORKS, icon: MontageScanIcon },
    { label: "爆款复刻", key: MenuKey.HOT_WRITE, icon: HotWriteIcon },
];

// 根据是否是正式版本，显示不同的菜单
const showUtils = computed(() => {
    return isReleaseVersion() ? utils_2 : utils_2.filter((item) => ![MenuKey.ANCHOR, MenuKey.TONE].includes(item.key));
});

const pageMap: Record<string, string | (() => void)> = {
    [MenuKey.CHOOSE_CREATE_TYPE]: "/ai_modules/digital_human/pages/choose_create_type/choose_create_type",
    [MenuKey.VIDEO_MIX]: "/ai_modules/digital_human/pages/montage_create/montage_create",
    [MenuKey.ANCHOR]: `/ai_modules/digital_human/pages/clone_manage/clone_manage?type=${ModeTypeEnum.ANCHOR}&model_version=${DigitalHumanModelVersionEnum.CHANJING}`,
    [MenuKey.TONE]: `/ai_modules/digital_human/pages/clone_manage/clone_manage?type=${ModeTypeEnum.TONE}`,
    [MenuKey.TEXT_EXTRACT]: () => uni.$u.toast("敬请期待"),
    [MenuKey.ME_CLONE]: "/ai_modules/digital_human/pages/clone_manage/clone_manage",
    [MenuKey.ME_CREATE]: "/packages/pages/creation/creation",
    [MenuKey.MONTAGE_RECORD]: "/packages/pages/creation/creation",
    [MenuKey.PLATFORM_PUBLISH]: "/ai_modules/digital_human/pages/platform_publish_works/platform_publish_works",
    [MenuKey.SORA]: "/ai_modules/digital_human/pages/sora_create/sora_create",
    [MenuKey.MATERIAL_LIBRARY]: "/packages/pages/material_library/material_library",
    [MenuKey.FRAGMENT_MIX]: "/ai_modules/digital_human/pages/montage_storyboard_create/montage_storyboard_create",
    [MenuKey.BOMB_COPY]: () => uni.$u.toast("敬请期待"),
    [MenuKey.IMAGE_CREATE]: "/ai_modules/drawing/pages/create_task/create_task",
    [MenuKey.SCAN_PUBLISH_WORKS]: "/ai_modules/digital_human/pages/platform_publish_works/platform_publish_works",
    [MenuKey.MONTAGE_MIX]: "/ai_modules/digital_human/pages/montage_create/montage_create",
    [MenuKey.HOT_WRITE]: "/ai_modules/hot_write/pages/index/index",
};

// 根据不同的类型获取不同的status值
const getStatus = (item: any) => {
    const { type, status } = item || {};

    if (type === 1) {
        if (status === 0 || status === 1 || status === 2) {
            return status;
        }
        return 3;
    } else if (type === 8) {
        if (status == 4) {
            return 2;
        }
        if (status == 3) {
            return 1;
        }
        return 0;
    } else {
        if (status === 0) {
            return 0;
        }
        if (status === 3) {
            return 1;
        }
        if (status === 2) {
            return 2;
        }
        return 3;
    }
};

const toPage = (key: string) => {
    const target = pageMap[key];
    if (!target) return;

    if (typeof target === "function") {
        target();
    } else {
        uni.$u.route({ url: target });
    }
};

const handlePlay = (url: string, pic: string) => {
    playItem.url = url;
    playItem.pic = pic;
    showVideoPreview.value = true;
};

const handlePlayCheck = (item: any) => {
    if (showDownloadStatusUi(item)) return;
    handlePlay(item.clip_result_url || item.video_result_url, item.pic);
};

const handleRedownload = async (item: any) => {
    if (!item?.id || redownloadId.value === item.id) return;
    redownloadId.value = item.id;
    item.download_status = VideoDownloadStatusEnum.DOWNLOADING;
    try {
        const res = await downloadShanjianVideoTask({ id: item.id });
        item.download_status = Number(res?.download_status ?? VideoDownloadStatusEnum.SUCCESS);
        if (res?.video_result_url) {
            item.video_result_url = res.video_result_url;
        }
        if (item.download_status === VideoDownloadStatusEnum.SUCCESS) {
            uni.$u.toast("下载成功");
        }
    } catch (error: any) {
        const msg = String(error || "");
        if (!msg.includes("正在下载中")) {
            item.download_status = VideoDownloadStatusEnum.FAILED;
        }
        uni.$u.toast(error || "重新下载失败");
    } finally {
        redownloadId.value = null;
    }
};

const getWorksLists = async () => {
    const { lists } = await getVideoCreationRecord({ page_size: 10, page_no: 1 });
    worksLists.value = lists;
};

const getAnchorLists = async () => {
    const { lists } = await getPublicAnchorList({
        page_size: 10,
        page_no: 1,
    });
    anchorLists.value = lists;
};

onShow(() => {
    getWorksLists();
    getAnchorLists();
});

onLoad(() => {
    uni.setNavigationBarColor({
        frontColor: "#ffffff",
        backgroundColor: "#333",
        animation: {
            duration: 400,
            timingFunc: "easeIn",
        },
    });
});
</script>

<style scoped lang="scss">
.fade-mask-right {
    @apply pointer-events-none absolute top-0 right-0 h-full;
    width: 80rpx;
    background: linear-gradient(to right, rgba(244, 246, 251, 0) 0%, rgba(244, 246, 251, 1) 100%);
    z-index: 10;
}
</style>
