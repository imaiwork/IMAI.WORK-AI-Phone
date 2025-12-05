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
        <view class="px-[30rpx] pb-[100rpx] pt-[236rpx]">
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
                            <video-item
                                :show-name="false"
                                :item="{
                                    name: item.name,
                                    pic: item.pic,
                                    status: item.status,
                                    video_url: item.result_url,
                                    clip_video_url: item.clip_result_url,
                                    model_version: item.model_version,
                                    remark: item.remark,
                                }"
                                @play="handlePlay($event, item.pic)"></video-item>
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
                            <video-item
                                :show-name="false"
                                :item="{
                                    name: item.name,
                                    pic: item.pic,
                                    status: item.status,
                                    video_url: item.url,
                                    model_version: item.model_version,
                                    remark: item.remark,
                                }"
                                @play="handlePlay($event, item.pic)"></video-item>
                        </view>
                    </view>
                    <view v-else class="my-4">
                        <empty :size="250" />
                    </view>
                </view>
            </view>
        </view>
    </view>
    <choose-model v-model:show="showChooseModel" @confirm="handleChooseModel" />
    <video-preview-v2
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false"></video-preview-v2>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { digitalHumanLists, getAnchorList } from "@/api/digital_human";
import { DigitalHumanModelVersionEnum, ModeTypeEnum } from "@/ai_modules/digital_human/enums";
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
import VideoPreviewV2 from "@/ai_modules/digital_human/components/video-preview-v2/video-preview-v2.vue";

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
    { label: "AI矩阵", key: MenuKey.VIDEO_MIX, icon: VideoMixIcon },
    { label: "数字人克隆", key: MenuKey.ANCHOR_CLONE, icon: AnchorCloneIcon },
    { label: "音色克隆", key: MenuKey.TONE_CLONE, icon: ToneCloneIcon },
    { label: "文案提取", key: MenuKey.TEXT_EXTRACT, icon: TextExtractIcon, disabled: true },
];

const utils_2 = [
    { label: "我的克隆", key: MenuKey.ME_CLONE, icon: MeCloneIcon },
    { label: "我的创作", key: MenuKey.ME_CREATE, icon: MeCreateIcon },
    { label: "混剪记录", key: MenuKey.MONTAGE_RECORD, icon: MontageRecordIcon },
    { label: "批量智剪", key: MenuKey.MONTAGE_BATCH, disabled: true, icon: MontageBatchIcon },
];

const pageMap: Record<string, string | (() => void)> = {
    [MenuKey.CHOOSE_CREATE_TYPE]: "/ai_modules/digital_human/pages/choose_create_type/choose_create_type",
    [MenuKey.VIDEO_MIX]: "/ai_modules/digital_human/pages/montage_create/montage_create",
    [MenuKey.ANCHOR_CLONE]: `/ai_modules/digital_human/pages/anchor_create/anchor_create?type=${ModeTypeEnum.ANCHOR}&model_version=${DigitalHumanModelVersionEnum.CHANJING}`,
    [MenuKey.TONE_CLONE]: "/ai_modules/digital_human/pages/tone_clone/tone_clone",
    [MenuKey.TEXT_EXTRACT]: () => uni.$u.toast("开发中..."),
    [MenuKey.ME_CLONE]: "/ai_modules/digital_human/pages/clone_manage/clone_manage",
    [MenuKey.ME_CREATE]: "/ai_modules/digital_human/pages/video_works/video_works",
    [MenuKey.MONTAGE_RECORD]: "/ai_modules/digital_human/pages/montage_works/montage_works",
    [MenuKey.MONTAGE_BATCH]: () => uni.$u.toast("开发中..."),
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
    const { lists } = await digitalHumanLists({ page_size: 3, page_no: 1 });
    worksLists.value = lists;
};

const getAnchorLists = async () => {
    const { lists } = await getAnchorList({
        page_size: 3,
        page_no: 1,
        type: 0,
        model_version: DigitalHumanModelVersionEnum.CHANJING,
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
    @apply h-[190rpx] rounded-[20rpx] text-[36rpx] flex items-center justify-center relative overflow-hidden;
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
