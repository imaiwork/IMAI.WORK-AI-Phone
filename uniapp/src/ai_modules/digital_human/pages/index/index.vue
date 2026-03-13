<template>
    <view class="min-h-screen relative">
        <view class="w-full h-[580rpx] absolute top-0 left-0">
            <image
                :src="`${config.baseUrl}static/images/mp/create_page_bg.png`"
                class="w-full h-full"
                mode="aspectFill"></image>
        </view>
        <view class="px-[30rpx] pt-[210rpx] pb-[200rpx] relative">
            <view class="create-card">
                <view class="px-1">
                    <view class="text-[50rpx] text-[#000000]/80 font-medium pt-[52rpx]"> AI视频创作 </view>
                    <view class="text-[#000000]/50 mt-[8rpx]"> 0基础高效创作 </view>
                </view>
                <view class="create-btn" @click="toPage(MenuKey.CHOOSE_CREATE_TYPE)">
                    <image src="@/ai_modules/digital_human/static/images/common/add.png" class="w-[32rpx] h-[32rpx]">
                    </image>
                    <text class="relative z-[22] text-[30rpx] text-white ml-[20rpx] font-bold">开始创作</text>
                    <view class="absolute left-[-20rpx] bottom-[-15rpx] opacity-20">
                        <image
                            src="@/ai_modules/digital_human/static/images/home/ai_tag.png"
                            class="w-[104rpx] h-[80rpx]"></image>
                    </view>
                </view>
            </view>
            <view class="grid grid-cols-2 gap-[20rpx] mt-[20rpx]">
                <view class="menu-card" @click="toPage(MenuKey.SORA)">
                    <view class="w-full h-full p-[38rpx]">
                        <view class="text-[30rpx] font-medium">一句话创作</view>
                        <view class="text-[22rpx] text-[#000000]/50 mt-[8rpx]"> 一语生成视频 </view>
                    </view>
                    <image
                        src="@/ai_modules/digital_human/static/images/home/mask_bg1.png"
                        class="w-[100rpx] h-[100rpx] absolute bottom-1 right-2"></image>
                </view>
                <view class="menu-card" @click="toPage(MenuKey.MONTAGE_MIX)">
                    <view class="w-full h-full p-[38rpx]">
                        <view class="text-[30rpx] font-medium">数字人口播混剪</view>
                        <view class="text-[22rpx] text-[#000000]/50 mt-[8rpx]"> 智剪爆款视频 </view>
                    </view>
                    <image
                        src="@/ai_modules/digital_human/static/images/home/mask_bg2.png"
                        class="w-[100rpx] h-[100rpx] absolute bottom-1 right-2"></image>
                </view>
            </view>
            <view class="grid grid-cols-4 gap-x-[15rpx] gap-y-[46rpx] mt-[50rpx]">
                <view v-for="(menu, index) in utils_2" :key="index" class="menu2-card" @click="toPage(menu.key)">
                    <view class="flex flex-col items-center gap-y-[12rpx]">
                        <view class="w-[80rpx] h-[80rpx] bg-white rounded-[30rpx] flex items-center justify-center">
                            <image :src="menu.icon" class="w-[40rpx] h-[40rpx]"></image>
                        </view>
                        <view class="font-medium">{{ menu.label }}</view>
                    </view>
                </view>
            </view>
            <view class="mt-[60rpx]">
                <view class="flex items-center justify-between">
                    <view class="text-[30rpx] font-medium">我的创作库</view>
                    <view class="flex items-center gap-x-1" @click="toPage(MenuKey.ME_CREATE)">
                        <text class="text-xs text-[#000000]/50">更多</text>
                        <u-icon name="arrow-right" size="20" color="#00000050"></u-icon>
                    </view>
                </view>
                <view class="mt-[22rpx] relative">
                    <scroll-view scroll-x v-if="worksLists.length > 0">
                        <view class="flex whitespace-nowrap gap-x-[14rpx]">
                            <view
                                v-for="(item, index) in worksLists"
                                :key="index"
                                class="shrink-0 w-[224rpx] h-[288rpx] rounded-[20rpx]">
                                <view class="h-[288rpx] rounded-lg overflow-hidden relative">
                                    <image :src="item.pic" class="h-full w-full" mode="aspectFill"></image>
                                    <view
                                        class="text-[20rpx] text-white absolute top-2 left-2"
                                        v-if="item.automatic_clip == 1">
                                        AI剪辑</view
                                    >
                                    <view
                                        v-if="getStatus(item) == 1"
                                        class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center z-[22]"
                                        @click="handlePlay(item.clip_result_url || item.video_result_url, item.pic)">
                                        <image src="/static/images/icons/play.svg" class="w-[58rpx] h-[58rpx]"></image>
                                        <view
                                            class="text-white text-center text-[22rpx] mt-[16rpx]"
                                            v-if="item.automatic_clip">
                                            <template v-if="item.clip_status == 1 || item.clip_status == 2">
                                                AI智能剪辑中...
                                            </template>
                                            <template v-if="item.clip_status == 3">AI智能剪辑完成</template>
                                            <template v-if="item.clip_status == 4">AI智能剪辑失败</template>
                                        </view>
                                    </view>
                                    <view
                                        v-else
                                        class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center bg-[#0000004d] z-[22]">
                                        <template v-if="getStatus(item) == 2">
                                            <view
                                                class="text-white bg-[#FF2442] text-[22rpx] font-medium rounded-[10rpx] w-[120rpx] h-[50rpx] flex items-center justify-center mx-auto">
                                                生成失败</view
                                            >
                                            <view class="mt-[16rpx] text-center text-[22rpx] text-white px-2">
                                                {{ item.remark }}
                                            </view>
                                        </template>
                                        <template v-else>
                                            <text class="rotation"></text>
                                            <text class="text-xs text-[#ffffff80]">正在生成中</text>
                                            <text class="text-[20rpx] text-[#ffffff80]">几分钟即可生成视频</text>
                                        </template>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                    <view v-else class="my-4">
                        <empty :size="250" />
                    </view>
                    <view v-if="worksLists.length >= 3" class="fade-mask-right"> </view>
                </view>
            </view>
            <view class="mt-[60rpx]">
                <view class="flex items-center justify-between">
                    <view class="text-[30rpx] font-medium">数字人形象</view>
                    <view class="flex items-center gap-x-1" @click="toPage(MenuKey.ME_CLONE)">
                        <text class="text-xs text-[#000000]/50">更多</text>
                        <u-icon name="arrow-right" size="20" color="#00000050"></u-icon>
                    </view>
                </view>
                <view class="mt-[22rpx] relative">
                    <scroll-view scroll-x v-if="anchorLists.length > 0">
                        <view class="flex whitespace-nowrap gap-x-[14rpx]">
                            <view
                                v-for="(item, index) in anchorLists"
                                :key="index"
                                class="shrink-0 w-[224rpx] h-[288rpx] rounded-[20rpx]">
                                <anchor-video
                                    :show-name="false"
                                    :show-more="false"
                                    :item="item"
                                    @play="handlePlay($event, item.pic)"></anchor-video>
                            </view>
                        </view>
                    </scroll-view>
                    <view v-else class="my-4">
                        <empty :size="220" />
                    </view>
                    <view v-if="anchorLists.length >= 3" class="fade-mask-right"> </view>
                </view>
            </view>
        </view>
        <tabbar />
    </view>
    <choose-model v-model="showChooseModel" @confirm="handleChooseModel" />
    <video-preview-v2
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false"></video-preview-v2>
</template>

<script setup lang="ts">
import config from "@/config";
import { useAppStore } from "@/stores/app";
import { getVideoCreationRecord } from "@/api/app";
import { getPublicAnchorList } from "@/api/digital_human";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { ModeTypeEnum } from "@/ai_modules/digital_human/enums";
import ChooseModel from "@/ai_modules/digital_human/components/choose-model/choose-model.vue";
import VideoMixIcon from "@/ai_modules/digital_human/static/icons/video_mix.svg";
import AnchorCloneIcon from "@/ai_modules/digital_human/static/icons/anchor_clone.svg";
import ToneCloneIcon from "@/ai_modules/digital_human/static/icons/tone_clone.svg";
import TextExtractIcon from "@/ai_modules/digital_human/static/icons/text_extract.svg";
import MeCloneIcon from "@/ai_modules/digital_human/static/icons/me_clone.svg";
import MeCreateIcon from "@/ai_modules/digital_human/static/icons/me_create.svg";
import MontageRecordIcon from "@/ai_modules/digital_human/static/icons/montage_record.svg";
import MontageScanIcon from "@/ai_modules/digital_human/static/icons/scan.svg";
import MaterialLibraryIcon from "@/ai_modules/digital_human/static/icons/material_library.svg";
import ImageCreateIcon from "@/ai_modules/digital_human/static/icons/image_create.svg";
import BombCopyIcon from "@/ai_modules/digital_human/static/icons/bomb_copy.svg";
import StoryboardIcon from "@/ai_modules/digital_human/static/icons/storyboard.svg";
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
}

const worksLists = ref<any[]>([]);
const anchorLists = ref<any[]>([]);

const playItem = reactive<any>({
    url: "",
    pic: "",
});
const showVideoPreview = ref(false);

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
];

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
};

// 根据不同的类型获取不同的status值
const getStatus = (item: any) => {
    const { type, status } = item || {};

    if (type === 1) {
        if (status === 0 || status === 1 || status === 2) {
            return status;
        }
        return 3;
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

const showChooseModel = ref(false);
const handleChooseModel = (id: string) => {
    showChooseModel.value = false;
    uni.$u.route({
        url: `/ai_modules/digital_human/pages/video_upload/video_upload?type=${ModeTypeEnum.ANCHOR}&model_version=${id}`,
    });
};

const handlePlay = (url: string, pic: string) => {
    playItem.url = url;
    playItem.pic = pic;
    showVideoPreview.value = true;
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

onLoad(() => {
    uni.setNavigationBarColor({
        frontColor: "#ffffff",
        backgroundColor: "#333",
        animation: {
            duration: 400,
            timingFunc: "easeIn",
        },
    });
    getWorksLists();
    getAnchorLists();
});
</script>

<style scoped lang="scss">
@mixin gradient-bg {
    background: linear-gradient(to right, rgba(54, 122, 247, 1) 0%, rgba(4, 238, 251, 1) 100%);
}

.create-card {
    grid-column-start: span 3;
    @apply h-[350rpx] rounded-[40rpx] bg-white px-[52rpx] relative;

    > * {
        position: relative;
        z-index: 1;
    }

    &:after {
        @apply content-[''] absolute top-[0rpx] left-[10rpx] w-full h-[274rpx] bg-[#ffffff]/30 rounded-[40rpx] border-[2rpx] border-solid border-[#ffffff]/20;
        transform: rotate(5deg);
        z-index: 0;
    }

    .create-btn {
        @apply mt-[50rpx] h-[90rpx] rounded-[20rpx] text-[40rpx] font-medium flex items-center justify-center relative overflow-hidden;
        @include gradient-bg;
    }
}

.menu-card {
    @apply bg-white rounded-[20rpx] flex flex-col gap-4 items-center justify-center h-[190rpx] relative;

    .badge {
        @apply text-[20rpx] rounded-tr-[12rpx] rounded-bl-[12rpx] absolute top-0 right-0 px-[12rpx];
        @include gradient-bg;
    }
}

.menu2-card {
    @apply flex flex-col gap-4 items-center justify-center h-[130rpx] relative;

    .badge {
        @apply text-[14rpx] rounded-[50rpx] absolute top-2 right-2 px-[6rpx];
        @include gradient-bg;
    }
}

.fade-mask-right {
    @apply pointer-events-none absolute top-0 right-0 h-full;
    width: 80rpx;
    background: linear-gradient(to right, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 1) 100%);
    z-index: 10;
}
</style>
