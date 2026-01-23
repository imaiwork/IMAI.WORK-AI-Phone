<template>
    <view class="min-h-screen bg-black relative">
        <u-navbar
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }"
            back-icon-color="#ffffff">
        </u-navbar>
        <view class="w-full h-[460rpx] absolute top-0 left-0">
            <image :src="banner" class="w-full h-full" mode="aspectFill"></image>
        </view>
        <view class="px-[30rpx] pb-[100rpx] pt-[236rpx] relative">
            <view class="grid grid-cols-3 gap-[15rpx]">
                <view class="create-card" @click="toPage(MenuKey.CHOOSE_CREATE_TYPE)">
                    <text class="relative z-[22]">创作6种视频</text>
                    <view class="absolute left-[-70rpx] bottom-[-50rpx] opacity-20">
                        <image
                            src="@/ai_modules/digital_human/static/images/home/ai_tag.png"
                            class="w-[232rpx] h-[151rpx]"></image>
                    </view>
                </view>
                <view v-for="(menu, index) in utils_1" :key="index" class="menu-card" @click="toPage(menu.key)">
                    <view class="flex flex-col items-center gap-y-[12rpx]">
                        <image :src="menu.icon" class="w-[48rpx] h-[48rpx]"></image>
                        <view class="text-[28rpx] text-white font-bold">{{ menu.label }}</view>
                    </view>
                    <view v-if="menu.key == MenuKey.SORA" class="badge">全新体验</view>
                    <view v-if="menu.disabled" class="badge">待上线</view>
                </view>
            </view>
            <view class="grid grid-cols-4 gap-[15rpx] mt-4">
                <view v-for="(menu, index) in utils_2" :key="index" class="menu2-card" @click="toPage(menu.key)">
                    <view class="flex flex-col items-center gap-y-[12rpx]">
                        <image :src="menu.icon" class="w-[40rpx] h-[40rpx]"></image>
                        <view class="text-white font-bold">{{ menu.label }}</view>
                    </view>
                    <view v-if="menu.disabled" class="badge">待上线</view>
                </view>
            </view>
            <view class="mt-[60rpx]">
                <view class="flex items-center justify-between">
                    <view class="text-[30rpx] font-bold text-white">我的创作</view>
                    <view class="flex items-center gap-x-1" @click="toPage(MenuKey.ME_CREATE)">
                        <text class="text-xs text-[#ffffffb3]">全部</text>
                        <u-icon name="arrow-right" color="#ffffffb3"></u-icon>
                    </view>
                </view>
                <view class="mt-[22rpx]">
                    <view class="grid grid-cols-3 gap-x-[20rpx]" v-if="worksLists.length > 0">
                        <view v-for="(item, index) in worksLists" :key="index" class="h-[288rpx] rounded-[20rpx]">
                            <view class="h-[288rpx] rounded-lg overflow-hidden relative">
                                <image :src="item.pic" class="h-full w-full" mode="aspectFill"></image>
                                <view class="text-[20rpx] text-white absolute top-2 left-2" v-if="item.automatic_clip"
                                    >AI剪辑</view
                                >
                                <view
                                    v-if="getStatus(item) == 1"
                                    class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center z-[22]"
                                    @click="handlePlay(item.clip_result_url || item.clip_result_url, item.pic)">
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
                                            class="text-white bg-[#FF2442] text-[22rpx] font-bold rounded-[10rpx] w-[120rpx] h-[50rpx] flex items-center justify-center mx-auto"
                                            >生成失败</view
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
                    <view v-else class="my-4">
                        <empty :size="250" />
                    </view>
                </view>
            </view>
            <view class="mt-[60rpx]">
                <view class="flex items-center justify-between">
                    <view class="text-[30rpx] font-bold text-white">形象克隆</view>
                    <view class="flex items-center gap-x-1" @click="toPage(MenuKey.ME_CLONE)">
                        <text class="text-xs text-[#ffffffb3]">全部</text>
                        <u-icon name="arrow-right" color="#ffffffb3"></u-icon>
                    </view>
                </view>
                <view class="mt-[22rpx]">
                    <view class="grid grid-cols-3 gap-x-[20rpx]" v-if="anchorLists.length > 0">
                        <view v-for="(item, index) in anchorLists" :key="index" class="h-[288rpx] rounded-[20rpx]">
                            <anchor-video
                                :show-name="false"
                                :show-more="false"
                                :item="item"
                                @play="handlePlay($event, item.pic)"></anchor-video>
                        </view>
                    </view>
                    <view v-else class="my-4">
                        <empty :size="250" />
                    </view>
                </view>
            </view>
        </view>
    </view>
    <choose-model v-model="showChooseModel" @confirm="handleChooseModel" />
    <video-preview-v2
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false"></video-preview-v2>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { getVideoCreationRecord } from "@/api/app";
import { digitalHumanLists, getPublicAnchorList } from "@/api/digital_human";
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
import MontageBatchIcon from "@/ai_modules/digital_human/static/icons/montage_batch.svg";
import VideoItem from "@/ai_modules/digital_human/components/video-item/video-item.vue";
import AnchorVideo from "@/ai_modules/digital_human/components/anchor-video/anchor-video.vue";

enum MenuKey {
    CHOOSE_CREATE_TYPE = "choose_create_type",
    VIDEO_MIX = "video_mix",
    ANCHOR_CLONE = "anchor_clone",
    TONE_CLONE = "tone_clone",
    TEXT_EXTRACT = "text_extract",
    ME_CLONE = "ME_CLONE",
    ME_CREATE = "me_create",
    MONTAGE_RECORD = "montage_record",
    MONTAGE_BATCH = "montage_batch",
    SORA = "sora",
    MATERIAL_LIBRARY = "material_library",
}

const appStore = useAppStore();
const { config } = toRefs(appStore);
const banner = computed(() => config.value?.digital_human?.banner);

const worksLists = ref<any[]>([]);
const anchorLists = ref<any[]>([]);

const playItem = reactive<any>({
    url: "",
    pic: "",
});
const showVideoPreview = ref(false);

const utils_1 = [
    { label: "一句话生成", key: MenuKey.SORA, icon: VideoMixIcon },
    { label: "数字人克隆", key: MenuKey.ANCHOR_CLONE, icon: AnchorCloneIcon },
    { label: "我的克隆", key: MenuKey.TONE_CLONE, icon: ToneCloneIcon },
    { label: "文案提取", key: MenuKey.TEXT_EXTRACT, icon: TextExtractIcon, disabled: true },
];

const utils_2 = [
    { label: "我的克隆", key: MenuKey.ME_CLONE, icon: MeCloneIcon },
    { label: "我的创作", key: MenuKey.ME_CREATE, icon: MeCreateIcon },
    { label: "素材库", key: MenuKey.MATERIAL_LIBRARY, icon: MontageRecordIcon },
    { label: "批量智剪", key: MenuKey.MONTAGE_BATCH, disabled: true, icon: MontageBatchIcon },
];

const pageMap: Record<string, string | (() => void)> = {
    [MenuKey.CHOOSE_CREATE_TYPE]: "/ai_modules/digital_human/pages/choose_create_type/choose_create_type",
    [MenuKey.VIDEO_MIX]: "/ai_modules/digital_human/pages/montage_create/montage_create",
    [MenuKey.ANCHOR_CLONE]: `/ai_modules/digital_human/pages/anchor_create/anchor_create?type=${ModeTypeEnum.ANCHOR}&model_version=${DigitalHumanModelVersionEnum.CHANJING}`,
    [MenuKey.TONE_CLONE]: "/ai_modules/digital_human/pages/tone_clone/tone_clone",
    [MenuKey.TEXT_EXTRACT]: () => uni.$u.toast("开发中..."),
    [MenuKey.ME_CLONE]: "/ai_modules/digital_human/pages/clone_manage/clone_manage",
    [MenuKey.ME_CREATE]: "/packages/pages/creation/creation",
    [MenuKey.MONTAGE_RECORD]: "/packages/pages/creation/creation",
    [MenuKey.MONTAGE_BATCH]: () => uni.$u.toast("开发中..."),
    [MenuKey.SORA]: "/ai_modules/digital_human/pages/sora_create/sora_create",
    [MenuKey.MATERIAL_LIBRARY]: "/packages/pages/material_library/material_library",
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
    const { lists } = await getVideoCreationRecord({ page_size: 3, page_no: 1 });
    worksLists.value = lists;
};

const getAnchorLists = async () => {
    const { lists } = await getPublicAnchorList({
        page_size: 3,
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
    background: linear-gradient(
        90deg,
        rgba(8, 131, 254, 1) 0%,
        rgba(24, 237, 245, 1) 50.35%,
        rgba(89, 255, 167, 1) 100%
    );
}

.create-card {
    grid-column-start: span 2;
    @include gradient-bg;
    @apply h-[190rpx] rounded-[20rpx] text-[40rpx] font-bold flex items-center justify-center relative overflow-hidden;
}
.menu-card {
    @apply bg-[#202328] rounded-[20rpx] flex flex-col gap-4 items-center justify-center h-[190rpx] relative;
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
</style>
