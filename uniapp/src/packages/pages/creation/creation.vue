<template>
    <view class="h-screen flex flex-col bg-white">
        <view class="px-4 flex justify-center my-2">
            <u-tabs
                :list="tabs"
                :current="currentTab"
                :bar-width="66"
                :font-size="26"
                @change="handleTabChange"></u-tabs>
        </view>
        <view class="px-4">
            <scroll-view id="type-scroll-view" scroll-x scroll-with-animation :scroll-left="scrollLeft">
                <view class="flex gap-x-1 py-1">
                    <view
                        v-for="(item, index) in typeList"
                        :id="`type${index}`"
                        :key="index"
                        class="px-1"
                        @click="handleType(item.key, index)">
                        <view
                            class="px-[24rpx] py-[10rpx] rounded-[10rpx] whitespace-nowrap"
                            :class="[
                                currentType == item.key ? 'bg-black text-white' : 'shadow-[0_0_0_2rpx_rgba(0,0,0,0.1)]',
                            ]">
                            {{ item.name }}
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="grow min-h-0 mt-[48rpx]">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                :default-page-size="20"
                @query="queryList">
                <view class="px-4">
                    <view v-if="currentTab == 0" class="grid grid-cols-2 gap-3">
                        <view class="" v-for="(item, index) in dataLists" :key="index">
                            <view class="h-[388rpx] rounded-lg overflow-hidden relative">
                                <image :src="item.pic" class="h-full w-full" mode="aspectFill"></image>
                                <view class="absolute bottom-1 px-2 text-[22rpx] text-white font-medium z-[33]">
                                    {{ item.create_time }}
                                </view>
                                <view
                                    class="text-[20rpx] text-white absolute top-2 left-2"
                                    v-if="item.automatic_clip == '1'"
                                    >AI剪辑</view
                                >
                                <view
                                    v-if="getStatus(item) == 1"
                                    class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center z-[22]"
                                    @click="handlePlayCheck(item)">
                                    <image src="/static/images/icons/play.svg" class="w-[58rpx] h-[58rpx]"></image>
                                    <view
                                        class="text-white text-center text-[22rpx] mt-[16rpx]"
                                        v-if="item.automatic_clip == '1'">
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
                                            class="text-white bg-[#FF2442] text-[22rpx] font-medium rounded-[10rpx] w-[120rpx] h-[50rpx] flex items-center justify-center mx-auto"
                                            >生成失败</view
                                        >
                                        <view class="mt-[16rpx] text-center text-[22rpx] text-white px-2 line-clamp-6">
                                            {{ item.remark }}
                                        </view>
                                    </template>
                                    <template v-else>
                                        <text class="rotation"></text>
                                        <text class="text-xs text-[#ffffff80]">正在生成中</text>
                                        <text class="text-[20rpx] text-[#ffffff80]">几分钟即可生成视频</text>
                                    </template>
                                </view>
                                <view
                                    v-if="isHandle"
                                    class="absolute top-0 left-0 w-full h-full z-[44]"
                                    :class="{ 'bg-[#0000004d]': isSelect(index) }"
                                    @click="handleSelect(index)">
                                    <view class="absolute top-2 right-2 z-[22] w-[32rpx] h-[32rpx]">
                                        <image
                                            v-if="isSelect(index)"
                                            src="/static/images/icons/success.svg"
                                            class="w-full h-full"></image>
                                        <view
                                            class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]"
                                            v-else>
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <view class="flex items-center justify-between gap-x-2 mt-1">
                                <view class="line-clamp-1 break-all">
                                    {{ item.name }}
                                </view>
                                <view class="p-1" @click="handleMore(item, index)">
                                    <u-icon name="more-dot-fill" color="#7F7F7F"></u-icon>
                                </view>
                            </view>
                            <view class="text-[#0000004d] text-[22rpx]"> {{ getTypeName(item.type) }} </view>
                        </view>
                    </view>
                    <view v-if="currentTab == 1" class="grid grid-cols-2 gap-3">
                        <view class="" v-for="(item, index) in dataLists" :key="index">
                            <view class="h-[400rpx] relative rounded-[20rpx] overflow-hidden">
                                <view class="w-full h-full relative" @click="handlePreview(item)">
                                    <image
                                        :src="item.image"
                                        lazy-load
                                        class="w-full h-full"
                                        mode="aspectFill"
                                        v-if="item.draw_type != 6"></image>
                                    <video
                                        v-else
                                        :src="item.video_url"
                                        class="w-full h-full"
                                        object-fit="cover"
                                        :autoplay="false"
                                        :show-loading="false"
                                        :controls="false"
                                        :show-fullscreen-btn="false"
                                        :show-center-play-btn="false"
                                        :show-play-btn="false"></video>
                                    <view
                                        class="absolute bottom-0 left-0 w-full h-full flex items-center justify-center"
                                        v-if="item.draw_type == 6">
                                        <image src="/static/images/icons/play.svg" class="w-[58rpx] h-[58rpx]"></image>
                                    </view>
                                </view>
                                <view
                                    v-if="isHandle"
                                    class="absolute top-0 left-0 w-full h-full z-[44]"
                                    :class="{ 'bg-[#0000004d]': isSelect(index) }"
                                    @click="handleSelect(index)">
                                    <view class="absolute top-2 right-2 z-[22] w-[32rpx] h-[32rpx]">
                                        <image
                                            v-if="isSelect(index)"
                                            src="/static/images/icons/success.svg"
                                            class="w-full h-full"
                                            lazy></image>
                                        <view
                                            class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]"
                                            v-else>
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view class="mt-[4rpx] flex justify-between">
                                <view>
                                    <view class="text-xs mt-[14rpx] break-all">
                                        {{ formatTime(item.create_time) }}
                                    </view>
                                    <view class="text-[22rpx] text-[#0000004d] mt-[4rpx]">
                                        {{ getDrawType(item.draw_type) }}
                                    </view>
                                </view>
                                <view class="p-1 mt-[4rpx]" @click="handleMore(item, index)">
                                    <u-icon name="more-dot-fill" color="#7F7F7F"></u-icon>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view v-if="currentTab == 2" class="flex flex-col gap-y-3">
                        <view
                            class="bg-white rounded-[20rpx] border-[0] border-b-[1rpx] border-solid border-[#F7F7F7] pb-2"
                            v-for="(item, index) in dataLists"
                            :key="index">
                            <puzzle-card :key="index" :item="item" @delete="reload"></puzzle-card>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
        <view class="bg-white pb-4 px-4 pt-2">
            <template v-if="currentTab == 0">
                <view
                    v-if="!isHandle"
                    class="bg-black rounded-[16rpx] h-[90rpx] flex items-center justify-center text-white font-medium"
                    @click="isHandle = true"
                    >创建发布任务</view
                >
                <view v-else class="flex items-center justify-between gap-x-2">
                    <view
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-md text-white"
                        :class="[handleList.length > 0 ? 'bg-black' : 'bg-[#787878CC]']">
                        <text class="font-medium text-[32rpx]">{{ handleList.length }}</text>
                        <text class="text-xs mt-1">已选</text>
                    </view>
                    <view class="flex items-center gap-x-2">
                        <view
                            class="bg-[#F3F3F3] px-[58rpx] h-[90rpx] flex items-center rounded-[16rpx]"
                            @click="
                                isHandle = false;
                                handleList = [];
                            "
                            >取消</view
                        >
                        <view
                            class="px-[58rpx] h-[90rpx] flex items-center rounded-[16rpx] text-white font-medium"
                            :class="[handleList.length > 0 ? 'bg-black' : 'bg-[#787878CC]']"
                            @click="toPublish"
                            >去发布</view
                        >
                    </view>
                </view>
            </template>
            <view
                v-if="currentTab == 1 && dataLists.length > 0"
                class="flex items-center"
                :class="[isHandle ? 'justify-between' : 'justify-end']">
                <view v-if="isHandle" class="flex items-center gap-x-4">
                    <view
                        class="bg-primary text-white w-[144rpx] h-[70rpx] flex items-center justify-center rounded-[10rpx]"
                        @click="
                            isHandle = false;
                            handleList = [];
                        "
                        >取消</view
                    >
                    <view class="flex items-center gap-x-2" @click="handleSelect(-1)">
                        <image
                            src="/static/images/icons/success.svg"
                            class="w-[32rpx] h-[32rpx]"
                            v-if="handleList.length > 0 && handleList.length == dataLists.length"></image>
                        <view
                            v-else
                            class="rounded-full w-[32rpx] h-[32rpx] shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]"></view>
                        全选
                    </view>
                </view>
                <view>
                    <view
                        v-if="isHandle"
                        class="text-white w-[170rpx] h-[70rpx] bg-[#FF2442] flex items-center justify-center rounded-[10rpx]"
                        @click="handleDelete(handleList)">
                        删除({{ handleList.length }})
                    </view>
                    <view
                        v-else
                        class="text-white w-[144rpx] h-[70rpx] bg-black flex items-center justify-center rounded-[10rpx]"
                        @click="isHandle = true">
                        管理
                    </view>
                </view>
            </view>
        </view>
    </view>
    <video-preview-v2
        v-model:show="showVideoPreview"
        :is-bar="false"
        :video-url="playData.url"
        :poster="playData.pic"
        @update:show="showVideoPreview = false"></video-preview-v2>
    <u-popup v-model="showEditPopup" mode="center" width="90%" :border-radius="20">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-medium text-center mt-2">编辑名称</view>
            <view class="mt-[48rpx] bg-[#F3F3F3] px-4 py-2 rounded-[16rpx]">
                <u-input
                    v-model="newName"
                    placeholder="请输入名称"
                    maxlength="30"
                    clearable
                    placeholder-style="color: #0000004d; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-medium text-[#000000b3]"
                    @click="showEditPopup = false">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-medium text-white"
                    @click="handleEditConfirm"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
    <u-popup v-model="showDownload" mode="center" width="90%" :border-radius="20">
        <view class="relative overflow-hidden bg-white w-full shadow-xl shadow-[#e2e8f0]/50">
            <view class="p-6">
                <view class="flex flex-col items-center gap-1 mb-6">
                    <view class="text-[32rpx] font-medium text-[#0f172a] tracking-wide">选择下载版本</view>
                    <view class="text-[24rpx] text-[#64748b]">请选择您需要保存到本地的视频</view>
                </view>

                <view class="flex gap-3">
                    <view
                        class="flex-1 py-4 px-2 flex flex-col items-center justify-center gap-1 rounded-[24rpx] bg-[#f1f5f9] active:bg-[#e2e8f0] transition-all border border-[transparent] active:border-[#cbd5e1]"
                        hover-class="opacity-80"
                        @click="handleDownload(1)">
                        <view class="w-8 h-8 rounded-full bg-white flex items-center justify-center mb-1 shadow-sm">
                            <text class="text-[#64748b] text-[24rpx] font-medium">原</text>
                        </view>
                        <text class="text-[#334155] font-medium text-[26rpx]">生成视频</text>
                        <text class="text-[#94a3b8] text-[20rpx] scale-90">原始版本</text>
                    </view>

                    <view
                        v-if="operateItem.clip_result_url && operateItem.automatic_clip"
                        class="flex-1 py-4 px-2 flex flex-col items-center justify-center gap-1 rounded-[24rpx] bg-primary active:bg-[#0055d4] transition-all shadow-lg shadow-[#3b82f6]/30"
                        hover-class="opacity-90"
                        @click="handleDownload(2)">
                        <view
                            class="absolute top-0 right-0 w-12 h-12 bg-[#ffffff]/10 rounded-bl-[32rpx] pointer-events-none"></view>

                        <view
                            class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center mb-1 backdrop-blur-sm border border-solid border-[#ffffff]/20">
                            <text class="text-white text-[24rpx] font-medium">AI</text>
                        </view>
                        <text class="text-white font-medium text-[26rpx]">剪辑视频</text>
                        <text class="text-[#dbeafe] text-[20rpx] scale-90">智能处理</text>
                    </view>
                </view>
            </view>
        </view>
    </u-popup>
    <view class="modal-container" v-if="showPlaySelection" :class="{ show: showPlaySelection }" @touchmove.stop.prevent>
        <view class="modal-mask" @tap="showPlaySelection = false"></view>
        <view class="modal-content">
            <view class="light-bar"></view>
            <view class="close-btn" @tap="showPlaySelection = false">
                <text class="close-icon">×</text>
            </view>
            <view class="modal-body">
                <view class="header-section">
                    <text class="modal-title">选择播放版本</text>
                    <text class="modal-subtitle">检测到该作品包含 AI 剪辑版本</text>
                </view>
                <view class="action-group">
                    <button
                        class="select-btn primary-btn"
                        hover-class="btn-hover"
                        @tap="triggerPlay(operateItem.clip_result_url)">
                        <view class="btn-left">
                            <view class="icon-box blue-icon">
                                <text class="icon-text">AI</text>
                            </view>
                            <view class="text-col">
                                <text class="btn-title text-white">播放剪辑视频</text>
                                <text class="btn-desc text-blue-light">AI 智能处理版本</text>
                            </view>
                        </view>
                        <view class="arrow-icon"></view>
                        <view class="shine-effect"></view>
                    </button>
                    <button
                        class="select-btn secondary-btn"
                        hover-class="btn-hover-dark"
                        @click="triggerPlay(operateItem.video_result_url)">
                        <view class="btn-left">
                            <view class="icon-box gray-icon">
                                <text class="icon-text">原</text>
                            </view>
                            <view class="text-col">
                                <text class="btn-title text-gray">播放数字人视频</text>
                                <text class="btn-desc text-gray-dark">原始生成版本</text>
                            </view>
                        </view>
                        <view class="arrow-icon gray-arrow"></view>
                    </button>
                </view>
            </view>
        </view>
    </view>
    <popup-bottom v-model="showPublishType" title="选择创建任务" height="30%">
        <template #content>
            <view class="space-y-4 p-4">
                <view
                    class="bg-black h-[90rpx] flex items-center justify-center text-white font-medium rounded-[16rpx]"
                    @click="toCreatePublishTask('timer')">
                    创建定时发布任务
                </view>
                <view
                    class="bg-black h-[90rpx] flex items-center justify-center text-white font-medium rounded-[16rpx]"
                    @click="toCreatePublishTask('qrcode')">
                    创建发布视频二维码
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getVideoCreationRecord, deleteVideoCreationRecord, updateVideoCreationRecord } from "@/api/app";
import { retrySoraTask } from "@/api/digital_human";
import { drawingRecord, drawingDeleteRecord, getPuzzleTaskList } from "@/api/drawing";
import { saveImageToPhotosAlbum, saveVideoToPhotosAlbum } from "@/utils/file";
import PuzzleCard from "@/packages/components/puzzle-card/puzzle-card.vue";

enum VideoType {
    ALL = 0,
    DIGITAL_HUMAN = 1,
    ORAL_MIX = 2,
    TRUE_HUMAN = 3,
    MATERIAL_MIX = 4,
    NEWS = 5,
    SENTENCE = 6,
    MONTAGE_STORYBOARD = 7,
    HOT_WRITE = 8,
}

enum DrawType {
    ALL = 0,
    GOODS = 1,
    FASHION = 2,
    TEXT_TO_IMAGE = 3,
    IMAGE_TO_IMAGE = 4,
    POSTER = 5,
    VIDEO = 6,
}

const tabs = [{ name: "视频创作" }, { name: "图片创作" }, { name: "拼图创作" }];
const currentTab = ref<number>(0);

const typeList = computed(() => {
    if (currentTab.value == 0) {
        return [
            { name: "全部", key: VideoType.ALL },
            { name: "数字人口播", key: VideoType.DIGITAL_HUMAN },
            { name: "口播混剪", key: VideoType.ORAL_MIX },
            { name: "真人口播", key: VideoType.TRUE_HUMAN },
            { name: "素材混剪", key: VideoType.MATERIAL_MIX },
            { name: "新闻体", key: VideoType.NEWS },
            { name: "一句话生成", key: VideoType.SENTENCE },
            { name: "分镜混剪", key: VideoType.MONTAGE_STORYBOARD },
            { name: "爆款仿写", key: VideoType.HOT_WRITE },
        ];
    } else if (currentTab.value == 1) {
        return [
            { name: "全部", key: DrawType.ALL },
            { name: "文生图", key: DrawType.TEXT_TO_IMAGE },
            { name: "图生图", key: DrawType.IMAGE_TO_IMAGE },
            { name: "商品图", key: DrawType.GOODS },
            { name: "海报图", key: DrawType.POSTER },
            { name: "服饰图", key: DrawType.FASHION },
            { name: "视频", key: DrawType.VIDEO },
        ];
    }
    return [];
});

const currentType = ref<number>(VideoType.ALL);

const dataLists = ref<any[]>([]);
const pagingRef = shallowRef();

const scrollLeft = ref(0);
const typeItemsLayout = ref<any[]>([]);
const scrollViewLayout = ref<any>(null);

// 操作的数据
const operateItem = ref<any>({});
const operateIndex = ref<number>(-1);
const newName = ref<string>("");
const showEditPopup = ref(false);
const showDownload = ref(false);
const showVideoPreview = ref(false);
const playData = reactive({
    url: "",
    pic: "",
});

const isHandle = ref(false);
// 发布数据
const handleList = ref<any[]>([]);
const showPublishType = ref(false);
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

const getTypeName = (type: number) => {
    return typeList.value.find((item: any) => item.key == type)?.name;
};

const handleTabChange = async (index: number) => {
    currentTab.value = index;
    currentType.value = VideoType.ALL;
    scrollLeft.value = 0;
    await nextTick();
    getTypesLayout();
    isHandle.value = false;
    handleList.value = [];
    pagingRef.value?.reload();
};

const handleType = (type: VideoType | DrawType, index: number) => {
    currentType.value = type;
    if (!scrollViewLayout.value || !typeItemsLayout.value[index] || !typeItemsLayout.value[index].width) {
        reload();
        return;
    }

    const scrollViewInfo = scrollViewLayout.value;
    const itemInfo = typeItemsLayout.value[index];

    const targetScrollLeft = itemInfo.offsetLeft + itemInfo.width / 2 - scrollViewInfo.width / 2;

    scrollLeft.value = targetScrollLeft;

    reload();
};

const fetchVideos = async (page_no: number, page_size: number) => {
    const params: { page_no: number; page_size: number; type?: number } = { page_no, page_size };
    if (currentType.value !== VideoType.ALL) {
        params.type = currentType.value;
    }
    const res = await getVideoCreationRecord(params);
    return res.lists;
};

const fetchImages = async (page_no: number, page_size: number) => {
    const params: { page_no: number; page_size: number; draw_type?: number; type?: number | string } = {
        page_no,
        page_size,
    };
    if (currentType.value !== VideoType.ALL) {
        params.draw_type = currentType.value;
        if (currentType.value == VideoType.SENTENCE) {
            params.type = "";
        } else {
            params.type = currentType.value;
        }
    }
    const res = await drawingRecord(params);
    return res.lists;
};

const fetchPuzzles = async (page_no: number, page_size: number) => {
    const res = await getPuzzleTaskList({ page_no, page_size });
    return res.lists;
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const fetcher = {
            0: fetchVideos,
            1: fetchImages,
            2: fetchPuzzles,
        }[currentTab.value];

        const lists = (await fetcher?.(page_no, page_size)) || [];
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const reload = () => {
    nextTick(() => {
        pagingRef.value?.reload();
    });
};

const drawTypeNames: Record<number, string> = {
    1: "商品图",
    2: "服饰图",
    3: "文生图",
    4: "图生图",
    5: "海报图",
    6: "视频",
};
const getDrawType = (type: string) => {
    return drawTypeNames[parseInt(type)];
};

const formatTime = (time: string) => {
    return uni.$u.timeFormat(time, "yyyy-mm-dd hh:MM");
};

const showVideo = ref(false);
const showPlaySelection = ref(false);

const handlePlayCheck = (item: any) => {
    const { automatic_clip, clip_status, clip_result_url, video_result_url, pic } = item;

    const hasClipVideo = automatic_clip == 1 && clip_status == 3 && clip_result_url;
    operateItem.value = item;
    playData.pic = pic;
    if (hasClipVideo) {
        showPlaySelection.value = true;
    } else {
        triggerPlay(video_result_url);
    }
};

const triggerPlay = (url: string) => {
    playData.url = url;
    showPlaySelection.value = false;
    if (url) {
        showVideoPreview.value = true;
    } else {
        uni.$u.toast("视频未生成");
    }
};

const showVideoActions = (item: any, index: number) => {
    const itemList = ["修改名称", "下载视频", "删除"];
    if (item.type == 6 && [1, 2].includes(getStatus(item))) {
        itemList.push("重试");
    }
    uni.showActionSheet({
        itemList,
        success: (res) => {
            if (res.tapIndex === 0) {
                handleEdit(dataLists.value.findIndex((item: any) => item.id == operateItem.value.id));
            } else if (res.tapIndex === 1) {
                if (!item.video_result_url && !item.clip_result_url) {
                    uni.$u.toast("视频未生成");
                    return;
                }
                if (item.clip_result_url && item.automatic_clip) {
                    showDownload.value = true;
                } else {
                    handleDownload(1);
                }
            } else if (res.tapIndex === 2) {
                handleDelete(index);
            } else if (res.tapIndex === 3) {
                handleRetry(index);
            }
        },
    });
};

const showImageActions = (item: any, index: number) => {
    const { draw_type } = item;
    const isVideo = draw_type == 6;
    uni.showActionSheet({
        itemList: [isVideo ? "保存视频" : "保存图片", "删除"],
        success: (res) => {
            if (res.tapIndex === 0) {
                isVideo ? saveVideoToPhotosAlbum(item.video_url) : saveImageToPhotosAlbum(item.image);
            } else if (res.tapIndex === 1) {
                handleDelete(index);
            }
        },
    });
};

const handleMore = (data: any, index: number) => {
    operateItem.value = data;
    operateIndex.value = index;
    if (currentTab.value === 0) {
        showVideoActions(data, index);
    } else if (currentTab.value === 1) {
        showImageActions(data, index);
    }
};

const handleEdit = (index: number) => {
    operateIndex.value = index;
    newName.value = dataLists.value[index].name;
    showEditPopup.value = true;
};

const handleEditConfirm = async () => {
    if (!newName.value) {
        uni.$u.toast("请输入名称");
        return;
    }
    showEditPopup.value = false;
    uni.showLoading({
        title: "修改中...",
        mask: true,
    });
    try {
        await updateVideoCreationRecord({
            id: operateItem.value.id,
            name: newName.value,
            task_id: operateItem.value.task_id,
            type: operateItem.value.type,
        });
        uni.hideLoading();
        uni.showToast({
            title: "修改成功",
            icon: "none",
            duration: 3000,
        });
        const index = dataLists.value.findIndex((item: any) => item.id == operateItem.value.id);
        if (index != -1) {
            dataLists.value[index].name = newName.value;
        }
    } catch (error: any) {
        uni.showToast({
            title: error || "修改失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const handleDownload = async (type: 1 | 2) => {
    const { video_result_url, clip_result_url, automatic_clip } = operateItem.value;
    const urlToSave = type === 1 ? video_result_url : clip_result_url;
    try {
        await saveVideoToPhotosAlbum(urlToSave);
    } finally {
        showDownload.value = false;
    }
};

const handleDelete = async (index: number | number[]) => {
    uni.showModal({
        title: "您真的要删除吗？",
        content: "删除后将无法找回，且该操作不可逆！",
        success: async (res) => {
            if (!res.confirm) return;

            uni.showLoading({
                title: "删除中...",
                mask: true,
            });
            try {
                if (currentTab.value === 0 && typeof index == "number") {
                    const { task_id, type, id } = dataLists.value[index];
                    await deleteVideoCreationRecord({ id: id, task_id, type });
                    dataLists.value.splice(index, 1);
                } else if (currentTab.value === 1) {
                    let deleteIndex: any[] = [];
                    const imageIds: any[] = [];
                    const videoIds: any[] = [];
                    if (typeof index == "number") {
                        deleteIndex = [index];
                    } else {
                        deleteIndex = index;
                    }
                    dataLists.value.forEach((item: any, index) => {
                        if (deleteIndex.includes(index)) {
                            if (item.draw_type == 6) {
                                videoIds.push(item.id);
                            } else {
                                imageIds.push(item.log_id);
                            }
                        }
                    });
                    await drawingDeleteRecord({ log_id: imageIds, video_id: videoIds });
                    dataLists.value = dataLists.value.filter(
                        (item: any, index: number) => !deleteIndex.includes(index)
                    );
                }
                uni.showToast({
                    title: "删除成功",
                    icon: "none",
                    duration: 3000,
                });
                handleList.value = [];
                isHandle.value = false;
            } catch (error: any) {
                uni.showToast({
                    title: error || "删除失败",
                    icon: "none",
                    duration: 3000,
                });
            } finally {
                uni.hideLoading();
            }
        },
    });
};

const handleRetry = async (index: number) => {
    const { id, type } = dataLists.value[index];
    uni.showModal({
        title: "您真的要重试吗？",
        content: "重试后将重新生成视频，且该操作不可逆！",
        success: async (res) => {
            if (!res.confirm) return;
            uni.showLoading({
                title: "重试中...",
                mask: true,
            });
            try {
                await retrySoraTask({ id });
                uni.hideLoading();
                uni.showToast({
                    title: "重试成功",
                    icon: "none",
                    duration: 3000,
                });
                pagingRef.value?.reload();
            } catch (error) {
                uni.hideLoading();
                uni.showToast({
                    title: "重试失败",
                    icon: "none",
                    duration: 3000,
                });
            }
        },
    });
};

const handlePreview = (item: any) => {
    const { draw_type, image, video_url } = item;
    if (draw_type == 6) {
        playData.url = video_url;
        showVideoPreview.value = true;
    } else {
        uni.previewImage({
            urls: [image],
        });
    }
};

const isSelect = (index: number) => {
    return handleList.value.includes(index);
};

const handleSelect = (index: number) => {
    const item = dataLists.value[index];
    // 要排除生成中的
    if (getStatus(item) !== 1 && currentTab.value == 0) {
        uni.$u.toast("生成中、生成失败的视频不能选择哦~");
        return;
    }
    const ids = dataLists.value.map((item: any, index: number) => index);
    if (index == -1) {
        if (handleList.value.length == ids.length) {
            handleList.value = [];
        } else {
            handleList.value = ids;
        }
        return;
    }

    if (isSelect(index)) {
        handleList.value.splice(handleList.value.indexOf(index), 1);
        return;
    }
    handleList.value.push(index);
};

const toPublish = () => {
    if (handleList.value.length == 0) {
        uni.$u.toast("请选择发布内容");
        return;
    }
    if (currentTab.value === 0 && handleList.value.length > 99) {
        uni.$u.toast("最多只能选择99个视频哦~");
        return;
    }
    showPublishType.value = true;
};

const toCreatePublishTask = (type: "timer" | "qrcode") => {
    const ids = handleList.value.map((index: number) => dataLists.value[index].task_id);
    uni.$u.route({
        url:
            type == "timer"
                ? "/ai_modules/device/pages/create_task/create_task?type=1"
                : "/ai_modules/digital_human/pages/platform_publish/platform_publish",
        params: {
            source: "creation_video",
            ids: JSON.stringify(ids),
        },
    });
    handleList.value = [];
    isHandle.value = false;
    showPublishType.value = false;
};

// 获取types对应元素内容
const getTypesLayout = () => {
    const instance = getCurrentInstance();
    if (!instance) return;

    const query = uni.createSelectorQuery().in(instance);
    query.select("#type-scroll-view").fields({ rect: true, size: true }, () => {});
    typeList.value.forEach((_, index) => {
        query.select(`#type${index}`).fields({ rect: true, size: true }, () => {});
    });

    query.exec((res) => {
        if (!res || !res[0]) return;
        const [svLayout, ...itemsLayout] = res;
        scrollViewLayout.value = svLayout;
        typeItemsLayout.value = itemsLayout.map((item: any) => {
            if (!item || !svLayout) return {};
            return {
                ...item,
                offsetLeft: item.left - svLayout.left,
            };
        });
    });
};

onMounted(() => {
    getTypesLayout();
});

onLoad((options: any) => {
    if (options?.tab) {
        currentTab.value = parseInt(options?.tab);
    }
    if (options?.source == "1") {
        currentType.value = parseInt(options?.type);
    }
    if (options?.source == "2") {
        currentType.value = parseInt(options?.type);
    }
});

onShow(async () => {
    await nextTick();
    pagingRef.value?.reload();
});
</script>

<style scoped lang="scss">
:deep(.u-tabs) {
    .u-tab-item {
        @apply font-medium;
    }
}
.radio-wrap {
    @apply w-[32rpx] h-[32rpx] rounded-full border border-solid border-[#c8c9cc];
}
.radio-wrap-active {
    @apply bg-primary border-primary;
}

.modal-container {
    position: fixed;
    inset: 0;
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    visibility: hidden;

    &.show {
        visibility: visible;

        .modal-mask {
            opacity: 1;
        }
        .modal-content {
            transform: scale(1);
            opacity: 1;
        }
    }
}

/* 遮罩 */
.modal-mask {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.7); // slate-900 / 0.7
    backdrop-filter: blur(3px);
    opacity: 0;
    transition: opacity 0.3s ease;
}

/* 弹窗主体 */
.modal-content {
    position: relative;
    width: 600rpx; // 小程序常用宽度单位
    background: #0f172a; // slate-900
    border: 1px solid rgba(51, 65, 85, 0.5); // slate-700
    border-radius: 32rpx;
    overflow: hidden;
    transform: scale(0.95);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 20rpx 50rpx -12rpx rgba(0, 0, 0, 0.5);
}

/* 顶部光效 */
.light-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(0, 101, 251, 0.6), transparent);
}

/* 关闭按钮 */
.close-btn {
    position: absolute;
    top: 20rpx;
    right: 20rpx;
    width: 60rpx;
    height: 60rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;

    .close-icon {
        font-size: 40rpx;
        color: #64748b;
        line-height: 1;
    }
}

.modal-body {
    padding: 48rpx 40rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* 文本区域 */
.header-section {
    text-align: center;
    margin-bottom: 40rpx;
    display: flex;
    flex-direction: column;
    gap: 10rpx;

    .modal-title {
        font-size: 36rpx;
        font-weight: bold;
        color: #ffffff;
        letter-spacing: 1px;
    }

    .modal-subtitle {
        font-size: 24rpx;
        color: #94a3b8; // slate-400
    }
}

/* 按钮组 */
.action-group {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

/* 通用按钮样式 */
.select-btn {
    position: relative;
    width: 100%;
    height: 120rpx;
    padding: 0 30rpx;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0;
    line-height: normal;
    overflow: hidden;

    &::after {
        border: none;
    } // 去除小程序默认边框

    .btn-left {
        display: flex;
        align-items: center;
        gap: 24rpx;
        z-index: 2;
    }

    .text-col {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 4rpx;
    }

    .btn-title {
        font-size: 28rpx;
        font-weight: bold;
    }

    .btn-desc {
        font-size: 20rpx;
    }

    .icon-box {
        width: 64rpx;
        height: 64rpx;
        border-radius: 16rpx;
        display: flex;
        align-items: center;
        justify-content: center;

        .icon-text {
            font-size: 24rpx;
            font-weight: 900;
        }
    }
}

/* 样式1：Primary (蓝色) */
.primary-btn {
    background-color: #0065fb;

    .blue-icon {
        background-color: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .text-white {
        color: #ffffff;
    }
    .text-blue-light {
        color: rgba(255, 255, 255, 0.7);
    }

    .arrow-icon {
        width: 16rpx;
        height: 16rpx;
        border-top: 4rpx solid rgba(255, 255, 255, 0.8);
        border-right: 4rpx solid rgba(255, 255, 255, 0.8);
        transform: rotate(45deg);
    }

    /* 扫光动画 */
    .shine-effect {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transform: translateX(-100%);
        animation: shine 3s infinite;
    }
}
</style>
