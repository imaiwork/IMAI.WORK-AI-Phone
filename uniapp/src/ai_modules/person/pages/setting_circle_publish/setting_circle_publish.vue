<template>
    <view class="min-h-screen bg-[#F4F7FA] pb-[250rpx]">
        <template v-if="loading">
            <view class="px-[30rpx] pt-2 animate-pulse">
                <view class="h-[32rpx] w-[160rpx] bg-[#E9EAEC] rounded-full mb-4"></view>
                <view class="flex gap-[12rpx] mb-6">
                    <view v-for="i in 3" :key="i" class="h-[64rpx] flex-1 bg-[#E9EAEC] rounded-[20rpx]"></view>
                </view>
                <view class="h-[32rpx] w-[160rpx] bg-[#E9EAEC] rounded-full mb-4"></view>
                <view class="bg-white rounded-[32rpx] p-5">
                    <view class="h-[28rpx] w-[180rpx] bg-[#F3F4F6] rounded-full mb-4"></view>
                    <view class="flex gap-[16rpx] mb-6">
                        <view
                            v-for="i in 4"
                            :key="i"
                            class="w-[140rpx] h-[140rpx] rounded-[16rpx] bg-[#F3F4F6] flex-shrink-0"></view>
                    </view>
                    <view class="h-[1rpx] bg-[#F3F4F6] mb-5"></view>
                    <view class="h-[28rpx] w-[180rpx] bg-[#F3F4F6] rounded-full mb-4"></view>
                    <view class="flex gap-[16rpx]">
                        <view
                            v-for="i in 4"
                            :key="i"
                            class="w-[140rpx] h-[140rpx] rounded-[16rpx] bg-[#F3F4F6] flex-shrink-0"></view>
                    </view>
                </view>
            </view>
        </template>

        <template v-else>
            <view class="px-[30rpx] pt-2">
                <view class="mb-3">
                    <text class="text-[28rpx] font-extrabold text-[#212121]">内容生成设置</text>
                </view>

                <scroll-view scroll-x :show-scrollbar="false" class="mb-5">
                    <view class="flex gap-[12rpx] pr-[4rpx]" style="width: max-content">
                        <view
                            v-for="tab in tabs"
                            :key="tab.value"
                            class="flex items-center gap-[8rpx] px-[24rpx] rounded-[20rpx] transition-all flex-shrink-0 h-[64rpx]"
                            :class="activeTab === tab.value ? 'bg-primary' : 'bg-white'"
                            @click="handleSelectTab(tab.value)">
                            <text
                                class="text-xs font-bold whitespace-nowrap"
                                :class="activeTab === tab.value ? 'text-white' : 'text-[#9CA3AF]'">
                                {{ tab.label }}
                            </text>
                        </view>
                    </view>
                </scroll-view>

                <view
                    v-show="activeTab === TabEnum.AI_AUTO"
                    class="mb-5 overflow-hidden transition-all duration-300"
                    :style="
                        activeTab === TabEnum.AI_AUTO
                            ? 'max-height: 300rpx; opacity: 1;'
                            : 'max-height: 0; opacity: 0; margin-bottom: 0;'
                    ">
                    <view class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                        <view class="flex items-center justify-between mb-2">
                            <text class="text-[28rpx] font-bold text-[#212121]">AI创作方向</text>
                            <view class="flex items-center gap-1">
                                <view
                                    class="w-[28rpx] h-[28rpx] rounded-full bg-[#E6F8F3] flex items-center justify-center">
                                    <u-icon name="account" color="#00C08E" size="16"></u-icon>
                                </view>
                                <text class="text-[22rpx] text-[#00C08E] font-bold">结合IP人设</text>
                            </view>
                        </view>
                        <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed block">
                            AI自动从素材库里抽取内容，并配上符合
                            <text class="text-primary font-bold">【{{ detail.persona_name }}】</text>
                            人设的文案，自动防折叠。
                        </text>
                    </view>
                </view>

                <view class="mb-3">
                    <text class="text-[28rpx] font-extrabold text-[#212121]">素材库内容</text>
                </view>

                <view class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)] mb-6">
                    <view v-show="aiIpLoading" class="animate-pulse">
                        <view class="h-[28rpx] w-[180rpx] bg-[#F3F4F6] rounded-full mb-4"></view>
                        <view class="flex gap-[16rpx] mb-6">
                            <view
                                v-for="i in 4"
                                :key="i"
                                class="w-[140rpx] h-[140rpx] rounded-[16rpx] bg-[#F3F4F6] flex-shrink-0"></view>
                        </view>
                        <view class="h-[1rpx] bg-[#F3F4F6] mb-5"></view>
                        <view class="h-[28rpx] w-[180rpx] bg-[#F3F4F6] rounded-full mb-4"></view>
                        <view class="flex gap-[16rpx]">
                            <view
                                v-for="i in 4"
                                :key="i"
                                class="w-[140rpx] h-[140rpx] rounded-[16rpx] bg-[#F3F4F6] flex-shrink-0"></view>
                        </view>
                    </view>

                    <view v-show="!aiIpLoading">
                        <view class="mb-5">
                            <view class="flex items-center justify-between mb-3">
                                <text class="text-[28rpx] font-bold text-[#212121]">
                                    视频素材 ({{ currentVideoList.length }})
                                </text>
                                <view
                                    v-if="activeTab === TabEnum.AI_IP"
                                    class="flex items-center gap-1"
                                    @click="handleMoreVideo">
                                    <text class="text-xs text-[#9CA3AF]">更多</text>
                                    <u-icon name="arrow-right" color="#9CA3AF" size="22"></u-icon>
                                </view>
                            </view>

                            <view v-if="activeTab === TabEnum.AI_IP">
                                <view
                                    v-if="currentVideoList.length === 0"
                                    class="flex flex-col items-center justify-center py-6 gap-2">
                                    <u-icon name="video-camera" color="#D0D5DD" size="48"></u-icon>
                                    <text class="text-xs text-[#9CA3AF]">暂无视频素材</text>
                                    <view
                                        class="mt-1 flex items-center gap-1 px-4 py-1.5 bg-[#EEF4FF] rounded-full"
                                        @click="handleMoreVideo">
                                        <text class="text-[22rpx] text-primary font-bold">前往素材库添加</text>
                                        <u-icon name="arrow-right" color="#0065fb" size="20"></u-icon>
                                    </view>
                                </view>
                                <view v-else>
                                    <view class="flex flex-wrap gap-[16rpx]">
                                        <view
                                            v-for="(item, index) in currentVideoList"
                                            :key="index"
                                            class="w-[140rpx] h-[140rpx] rounded-[16rpx] bg-[#E6F0FF] flex-shrink-0 relative overflow-hidden">
                                            <image
                                                v-if="item.pic"
                                                :src="item.pic"
                                                class="w-full h-full"
                                                lazy-load
                                                mode="aspectFill" />
                                            <view
                                                class="absolute bottom-2 right-2 w-[44rpx] h-[44rpx] rounded-full bg-[#ffffff]/30 flex items-center justify-center border border-solid border-[#ffffff]/40"
                                                @click.stop="handlePlayVideo(item.url)">
                                                <u-icon
                                                    name="play-right-fill"
                                                    color="#ffffff"
                                                    size="20"
                                                    class="ml-0.5"></u-icon>
                                            </view>
                                        </view>
                                    </view>
                                    <view
                                        class="mt-3 flex items-center gap-2 px-3 py-2.5 rounded-[16rpx] bg-[#F0F6FF] border border-solid border-[#D0E6FF]"
                                        @click="handleMoreVideo">
                                        <u-icon name="info-circle" color="#4A90E2" size="26"></u-icon>
                                        <text class="text-[22rpx] text-[#4A90E2] flex-1">
                                            当前只展示数据最新
                                            <text class="font-bold">{{ SCRIPT_COLLAPSE_THRESHOLD }}</text>
                                            条，点击
                                            <text class="font-bold underline">查看更多</text>
                                            可管理全部视频素材
                                        </text>
                                        <u-icon name="arrow-right" color="#4A90E2" size="22"></u-icon>
                                    </view>
                                </view>
                            </view>

                            <view v-else>
                                <view class="flex flex-wrap gap-[16rpx]">
                                    <view
                                        class="w-[140rpx] h-[140rpx] rounded-[16rpx] bg-[#F8F9FD] border border-dashed border-[#D0E6FF] flex flex-col items-center justify-center gap-1 flex-shrink-0"
                                        @click="handleAddVideo">
                                        <u-icon name="plus" color="#0065fb" size="32"></u-icon>
                                        <text class="text-[22rpx] text-primary">添加</text>
                                    </view>
                                    <view
                                        v-for="(item, index) in currentVideoList"
                                        :key="index"
                                        class="w-[140rpx] h-[140rpx] rounded-[16rpx] bg-[#E6F0FF] flex-shrink-0 relative overflow-hidden">
                                        <image
                                            v-if="item.pic"
                                            :src="item.pic"
                                            class="w-full h-full"
                                            mode="aspectFill" />
                                        <view
                                            class="absolute bottom-2 right-2 w-[44rpx] h-[44rpx] rounded-full bg-[#ffffff]/30 flex items-center justify-center border border-solid border-[#ffffff]/40"
                                            @click.stop="handlePlayVideo(item.url)">
                                            <u-icon
                                                name="play-right-fill"
                                                color="#ffffff"
                                                size="20"
                                                class="ml-0.5"></u-icon>
                                        </view>
                                        <view
                                            class="absolute top-[8rpx] right-[8rpx] w-[36rpx] h-[36rpx] rounded-full bg-[rgba(0,0,0,0.4)] flex items-center justify-center"
                                            @click.stop="handleDeleteVideo(index)">
                                            <u-icon name="close" color="#fff" size="18"></u-icon>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view class="border-[0] border-t border-solid border-[#F3F4F6]"></view>

                        <view class="mt-5">
                            <view class="flex items-center justify-between mb-3">
                                <text class="text-[28rpx] font-bold text-[#212121]">
                                    图片素材 ({{ currentImageList.length }})
                                </text>
                                <view
                                    v-if="activeTab === TabEnum.AI_IP"
                                    class="flex items-center gap-1"
                                    @click="handleMoreImage">
                                    <text class="text-xs text-[#9CA3AF]">更多</text>
                                    <u-icon name="arrow-right" color="#9CA3AF" size="22"></u-icon>
                                </view>
                            </view>

                            <view v-if="activeTab === TabEnum.AI_IP">
                                <view
                                    v-if="currentImageList.length === 0"
                                    class="flex flex-col items-center justify-center py-6 gap-2">
                                    <u-icon name="photo" color="#D0D5DD" size="48"></u-icon>
                                    <text class="text-xs text-[#9CA3AF]">暂无图片素材</text>
                                    <view
                                        class="mt-1 flex items-center gap-1 px-4 py-1.5 bg-[#EEF4FF] rounded-full"
                                        @click="handleMoreImage">
                                        <text class="text-[22rpx] text-primary font-bold">前往素材库添加</text>
                                        <u-icon name="arrow-right" color="#0065fb" size="20"></u-icon>
                                    </view>
                                </view>
                                <view v-else>
                                    <scroll-view scroll-x :show-scrollbar="false">
                                        <view class="flex gap-[16rpx]" style="width: max-content">
                                            <view
                                                v-for="(item, index) in currentImageList"
                                                :key="index"
                                                class="w-[140rpx] h-[140rpx] rounded-[16rpx] bg-[#E6F0FF] flex-shrink-0 relative overflow-hidden"
                                                @click="handlePreviewImage(index)">
                                                <image
                                                    v-if="item.url"
                                                    :src="item.url"
                                                    class="w-full h-full"
                                                    mode="aspectFill" />
                                            </view>
                                        </view>
                                    </scroll-view>
                                    <view
                                        class="mt-3 flex items-center gap-2 px-3 py-2.5 rounded-[16rpx] bg-[#F0F6FF] border border-solid border-[#D0E6FF]"
                                        @click="handleMoreImage">
                                        <u-icon name="info-circle" color="#4A90E2" size="26"></u-icon>
                                        <text class="text-[22rpx] text-[#4A90E2] flex-1">
                                            当前展示前
                                            <text class="font-bold">{{ SCRIPT_COLLAPSE_THRESHOLD }}</text>
                                            条，点击
                                            <text class="font-bold underline">查看更多</text>
                                            可管理全部图片素材
                                        </text>
                                        <u-icon name="arrow-right" color="#4A90E2" size="22"></u-icon>
                                    </view>
                                </view>
                            </view>

                            <view v-else>
                                <scroll-view scroll-x :show-scrollbar="false">
                                    <view class="flex gap-[16rpx]" style="width: max-content">
                                        <view
                                            class="w-[140rpx] h-[140rpx] rounded-[16rpx] bg-[#F8F9FD] border border-dashed border-[#D0E6FF] flex flex-col items-center justify-center gap-1 flex-shrink-0"
                                            @click="handleAddImage">
                                            <u-icon name="plus" color="#0065fb" size="32"></u-icon>
                                            <text class="text-[22rpx] text-primary">添加</text>
                                        </view>
                                        <view
                                            v-for="(item, index) in currentImageList"
                                            :key="index"
                                            class="w-[140rpx] h-[140rpx] rounded-[16rpx] bg-[#E6F0FF] flex-shrink-0 relative overflow-hidden"
                                            @click="handlePreviewImage(index)">
                                            <image
                                                v-if="item.url"
                                                :src="item.url"
                                                class="w-full h-full"
                                                mode="aspectFill" />
                                            <view v-else class="w-full h-full flex items-center justify-center">
                                                <u-icon name="photo" color="#0065fb" size="40"></u-icon>
                                            </view>
                                            <view
                                                class="absolute top-[8rpx] right-[8rpx] w-[36rpx] h-[36rpx] rounded-full bg-[rgba(0,0,0,0.4)] flex items-center justify-center"
                                                @click.stop="handleDeleteImage(index)">
                                                <u-icon name="close" color="#fff" size="18"></u-icon>
                                            </view>
                                        </view>
                                    </view>
                                </scroll-view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </template>
    </view>

    <view class="fixed bottom-0 left-0 right-0 bg-white px-5 pt-3 pb-4 z-50">
        <u-button
            type="primary"
            shape="circle"
            :ripple="true"
            :loading="saving"
            :custom-style="{
                height: '96rpx',
                fontSize: '30rpx',
                fontWeight: '900',
                border: 'none',
                boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
            }"
            @click="handleSave">
            确定保存
        </u-button>
    </view>

    <upload-category-panel
        v-model="showUploadCategoryPanel"
        :show-categories="[
            isVideo ? UploadAlbumTypeEnum.Video : UploadAlbumTypeEnum.Image,
            UploadCategoryEnum.Library,
            UploadCategoryEnum.Creation,
        ]"
        @select="handleSelectCategory" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <choose-material v-model="showChooseMaterial" :type="isVideo ? 'video' : 'image'" @select="handleChooseMaterial" />
    <choose-history v-model="showHistory" :type="isVideo ? 'video' : 'image'" @select="handleSelectHistory" />
    <video-preview-v2
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false" />
</template>

<script setup lang="ts">
import {
    getPersonDetail,
    updateCirclePublishMode,
    getMaterialLibraryList,
    batchAddMaterial,
    deleteMaterial,
} from "@/api/person";
import useUpload from "@/hooks/useUpload";
import { UploadCategoryEnum, UploadAlbumTypeEnum } from "@/enums/appEnums";

// ─── Enums & Constants ───────────────────────────────────────────
enum TabEnum {
    AI_AUTO = 1,
    MANUAL = 2,
    AI_IP = 3,
}

const tabs = [
    { label: "AI自动创作", value: TabEnum.AI_AUTO },
    { label: "指定素材", value: TabEnum.MANUAL },
    { label: "AI创作（IP素材库）", value: TabEnum.AI_IP },
];

// ─── Types ───────────────────────────────────────────────────────
interface MediaItem {
    id: number;
    url: string;
    pic: string;
    duration?: string;
}

interface TabMaterial {
    videoList: MediaItem[];
    imageList: MediaItem[];
}

// ─── State ───────────────────────────────────────────────────────
const personId = ref<string>("");
const loading = ref<boolean>(true);
const saving = ref<boolean>(false);
const aiIpLoading = ref<boolean>(false);
const detail = ref<any>({});
const activeTab = ref<TabEnum>(TabEnum.AI_AUTO);

const SCRIPT_COLLAPSE_THRESHOLD = 8;

const needRefreshOnShow = ref<boolean>(false);

const materialMap = reactive<Record<TabEnum, TabMaterial>>({
    [TabEnum.AI_AUTO]: { videoList: [], imageList: [] },
    [TabEnum.MANUAL]: { videoList: [], imageList: [] },
    [TabEnum.AI_IP]: { videoList: [], imageList: [] },
});

/** 当前 tab 对应的视频列表（计算属性，模板直接绑定） */
const currentVideoList = computed(() => materialMap[activeTab.value].videoList);
/** 当前 tab 对应的图片列表 */
const currentImageList = computed(() => materialMap[activeTab.value].imageList);

// ─── Upload ──────────────────────────────────────────────────────
const showUploadCategoryPanel = ref<boolean>(false);
const showChooseMaterial = ref<boolean>(false);
const showHistory = ref<boolean>(false);
const isVideo = ref<boolean>(false);
const playItem = reactive<any>({ url: "", pic: "" });
const showVideoPreview = ref<boolean>(false);

const { showUploadProgress, uploadAndProcessFiles, uploadMaterialList } = useUpload({
    isTranscode: true,
    isFetchVideoInfo: true,
    videoDuration: [1, 59],
    onSuccess: (res: any[]) => {
        addMaterialToCurrentTab(res, isVideo.value ? "video" : "image");
    },
});

// ─── AI_IP 素材加载 ──────────────────────────────────────────────
const loadAiIpMaterial = async (): Promise<void> => {
    aiIpLoading.value = true;
    try {
        const { lists } = await getMaterialLibraryList({
            persona_id: personId.value,
            page_size: 25000,
            publish_mode: activeTab.value === TabEnum.AI_AUTO || activeTab.value === TabEnum.AI_IP ? 1 : 2,
            is_wechat: activeTab.value === TabEnum.AI_IP ? 0 : 1,
        });
        if (activeTab.value === TabEnum.AI_AUTO || activeTab.value === TabEnum.MANUAL) {
            materialMap[activeTab.value].videoList = lists
                .filter((item: any) => item.material_type === 1)
                .map((item: any) => ({ id: item.id, url: item.file_url, pic: item.thumbnail_url }));
            materialMap[activeTab.value].imageList = lists
                .filter((item: any) => item.material_type === 2)
                .map((item: any) => ({ id: item.id, url: item.file_url, pic: item.thumbnail_url }));
        }
        if (activeTab.value === TabEnum.AI_IP) {
            materialMap[TabEnum.AI_IP].videoList = lists
                .filter((item: any) => item.material_type === 1)
                .slice(0, SCRIPT_COLLAPSE_THRESHOLD)
                .map((item: any) => ({ id: item.id, url: item.file_url, pic: item.thumbnail_url }));
            materialMap[TabEnum.AI_IP].imageList = lists
                .filter((item: any) => item.material_type === 2)
                .slice(0, SCRIPT_COLLAPSE_THRESHOLD)
                .map((item: any) => ({ id: item.id, url: item.file_url, pic: item.thumbnail_url }));
        }
    } finally {
        aiIpLoading.value = false;
    }
};

// ─── Tab 切换 ────────────────────────────────────────────────────
const handleSelectTab = async (value: TabEnum): Promise<void> => {
    if (activeTab.value === value) return;
    activeTab.value = value;
    await loadAiIpMaterial();
};

// ─── 公共：添加素材到当前 tab ────────────────────────────────────
const addMaterialToCurrentTab = async (items: any[], type: "video" | "image"): Promise<void> => {
    const listKey = type === "video" ? "videoList" : "imageList";

    if (activeTab.value === TabEnum.AI_AUTO || activeTab.value === TabEnum.MANUAL) {
        uni.showLoading({ title: "添加中...", mask: true });
        try {
            await batchAddMaterial({
                persona_id: personId.value,
                items: items.map((item: any) => ({
                    file_url: item.url,
                    material_type: item.type === "image" ? 2 : 1,
                    material_name: item.name,
                    duration: item.duration ?? 0,
                    thumbnail_url: item.pic,
                    publish_mode: activeTab.value === TabEnum.AI_AUTO ? 1 : 2,
                    is_wechat: 1,
                })),
            });
            materialMap[activeTab.value][listKey].push(...items);
            uni.hideLoading();
            uni.showToast({ title: "添加成功", icon: "none", duration: 3000 });
        } catch (error: any) {
            uni.hideLoading();
            uni.showToast({ title: error, icon: "none", duration: 3000 });
        }
    } else {
        materialMap[activeTab.value][listKey].push(...items);
    }
};

// ─── 公共：删除素材（视频 & 图片复用） ──────────────────────────
const handleDeleteMaterial = (index: number, type: "video" | "image"): void => {
    const listKey = type === "video" ? "videoList" : "imageList";
    const label = type === "video" ? "视频" : "图片";

    uni.showModal({
        title: "提示",
        content: `确定删除该${label}素材吗？`,
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    if (activeTab.value === TabEnum.AI_AUTO || activeTab.value === TabEnum.MANUAL) {
                        const material: any = materialMap[activeTab.value][listKey][index];
                        await deleteMaterial({ id: material.id });
                    }
                    materialMap[activeTab.value][listKey].splice(index, 1);
                    uni.hideLoading();
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({ title: error, icon: "none", duration: 3000 });
                }
            }
        },
    });
};

// ─── 公共：跳转素材库管理页（视频 & 图片复用） ──────────────────
const handleMoreMaterial = (): void => {
    needRefreshOnShow.value = true;
    uni.navigateTo({
        url: `/ai_modules/person/pages/material_library/material_library?id=${personId.value}&is_wechat=1`,
    });
};

// ─── Upload / Choose ─────────────────────────────────────────────
const handleSelectCategory = (category: UploadCategoryEnum | UploadAlbumTypeEnum): void => {
    if (category === UploadAlbumTypeEnum.Video || category === UploadAlbumTypeEnum.Image) {
        isVideo.value = category === UploadAlbumTypeEnum.Video;
        uploadAndProcessFiles(category as any);
    } else if (category === UploadCategoryEnum.Library) {
        showChooseMaterial.value = true;
    } else if (category === UploadCategoryEnum.Creation) {
        showHistory.value = true;
    }
};

const handleChooseMaterial = (material: any[]): Promise<void> =>
    addMaterialToCurrentTab(material, isVideo.value ? "video" : "image");

const handleSelectHistory = (history: any[]): Promise<void> =>
    addMaterialToCurrentTab(history, isVideo.value ? "video" : "image");

// ─── Video ───────────────────────────────────────────────────────
const handleAddVideo = (): void => {
    isVideo.value = true;
    showUploadCategoryPanel.value = true;
};

/** 语义包装，模板调用无需改动 */
const handleDeleteVideo = (index: number): void => handleDeleteMaterial(index, "video");

const handleMoreVideo = (): void => handleMoreMaterial();

const handlePlayVideo = (url: string): void => {
    playItem.url = url;
    showVideoPreview.value = true;
};

// ─── Image ───────────────────────────────────────────────────────
const handleAddImage = (): void => {
    isVideo.value = false;
    showUploadCategoryPanel.value = true;
};

/** 语义包装，模板调用无需改动 */
const handleDeleteImage = (index: number): void => handleDeleteMaterial(index, "image");

const handleMoreImage = (): void => handleMoreMaterial();

const handlePreviewImage = (index: number): void => {
    uni.previewImage({ urls: currentImageList.value.map((item) => item.url ?? ""), current: index });
};

// ─── Save ────────────────────────────────────────────────────────
const handleSave = async (): Promise<void> => {
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        await updateCirclePublishMode({ id: personId.value, wechat_publish_mode: activeTab.value });
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        setTimeout(() => uni.navigateBack(), 1500);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

// ─── Init ────────────────────────────────────────────────────────
const getDetail = async (): Promise<void> => {
    loading.value = true;
    try {
        const [detailResult] = await Promise.allSettled([getPersonDetail({ id: personId.value })]);
        if (detailResult.status === "fulfilled") {
            detail.value = detailResult.value ?? {};
            const tabFromDetail = detail.value.wechat_publish_mode as TabEnum | undefined;
            activeTab.value =
                tabFromDetail && Object.values(TabEnum).includes(tabFromDetail) ? tabFromDetail : TabEnum.AI_AUTO;
        }
    } finally {
        loading.value = false;
        await loadAiIpMaterial();
    }
};

// ─── 页面显示时按需刷新（从素材库子页面返回后触发） ──────────────
onShow(async () => {
    if (needRefreshOnShow.value) {
        needRefreshOnShow.value = false;
        await loadAiIpMaterial();
    }
});

onLoad((options: any) => {
    personId.value = options.person_id;
    getDetail();
});
</script>

<style scoped></style>
