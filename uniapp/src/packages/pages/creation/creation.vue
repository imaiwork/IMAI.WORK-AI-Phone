<template>
    <view class="h-screen flex flex-col bg-[#F4F6FB]">
        <view class="bg-white px-[32rpx] flex justify-center border-b border-solid border-[#F4F6FB]">
            <u-tabs :list="tabs" :current="currentTab" :bar-width="66" :font-size="26" @change="handleTabChange" />
        </view>

        <view class="bg-white px-[32rpx] pb-[16rpx]">
            <scroll-view id="type-scroll-view" scroll-x scroll-with-animation :scroll-left="scrollLeft">
                <view class="flex gap-x-[8rpx] py-[8rpx]">
                    <view
                        v-for="(item, index) in typeList"
                        :id="`type${index}`"
                        :key="index"
                        class="px-[8rpx]"
                        @click="handleType(item.key, index)">
                        <view
                            class="px-[24rpx] py-[10rpx] rounded-full whitespace-nowrap text-[26rpx] font-medium transition-all"
                            :class="currentType == item.key ? 'bg-primary text-white' : 'bg-[#F4F6FB] text-[#676767]'">
                            {{ item.name }}
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view class="grow min-h-0 mt-[8rpx]">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                :default-page-size="20"
                @query="queryList">
                <view class="px-[32rpx] pb-[32rpx]">
                    <view v-if="currentTab == 0" class="grid grid-cols-2 gap-[20rpx]">
                        <view v-for="(item, index) in dataLists" :key="index">
                            <view class="h-[388rpx] rounded-[24rpx] overflow-hidden relative shadow-sm">
                                <image :src="item.pic" class="h-full w-full" mode="aspectFill" lazy-load />

                                <view
                                    class="absolute bottom-[12rpx] left-[12rpx] bg-[#000000]/30 px-[12rpx] py-[4rpx] rounded-full z-[33]">
                                    <text class="text-[20rpx] text-white">{{ item.create_time }}</text>
                                </view>

                                <view
                                    v-if="item.automatic_clip == '1'"
                                    class="absolute top-[12rpx] left-[12rpx] bg-primary px-[12rpx] py-[4rpx] rounded-full z-[33]">
                                    <text class="text-[18rpx] text-white font-medium">AI剪辑</text>
                                </view>

                                <view
                                    v-if="getStatus(item) == 1"
                                    class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center z-[22]"
                                    @click="handlePlayCheck(item)">
                                    <image src="/static/images/icons/play.svg" class="w-[72rpx] h-[72rpx]" />
                                    <view
                                        v-if="item.automatic_clip == '1'"
                                        class="mt-[12rpx] px-[16rpx] py-[6rpx] bg-[#000000]/30 rounded-full">
                                        <template v-if="item.clip_status == 1 || item.clip_status == 2">
                                            <text class="text-[20rpx] text-white">AI智能剪辑中...</text>
                                        </template>
                                        <template v-if="item.clip_status == 3">
                                            <text class="text-[20rpx] text-white">AI智能剪辑完成</text>
                                        </template>
                                        <template v-if="item.clip_status == 4">
                                            <text class="text-[20rpx] text-white">{{
                                                item.remark || "AI智能剪辑失败"
                                            }}</text>
                                        </template>
                                    </view>

                                    <!-- 成片下载中 / 下载失败（成功不展示） -->
                                    <view
                                        v-if="showDownloadStatusUi(item)"
                                        class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center bg-[#000000]/50 z-[23]"
                                        @click.stop>
                                        <template v-if="isDownloadFailed(item)">
                                            <view class="bg-[#EF4444] px-[20rpx] py-[8rpx] rounded-full mb-[12rpx]">
                                                <text class="text-[22rpx] text-white font-medium">下载失败</text>
                                            </view>
                                            <text class="text-[20rpx] text-[#ffffff]/70 text-center px-[24rpx]">
                                                成片转存失败，请重试
                                            </text>
                                            <view
                                                class="mt-[16rpx] px-[28rpx] py-[10rpx] rounded-full bg-primary active:opacity-80"
                                                @click.stop="handleRedownload(item)">
                                                <text class="text-[22rpx] text-white font-medium">
                                                    {{ redownloadId === item.id ? "重新下载中..." : "重新下载" }}
                                                </text>
                                            </view>
                                        </template>
                                        <template v-else>
                                            <text class="rotation mb-[8rpx]"></text>
                                            <text class="text-[22rpx] text-[#ffffff]/80">下载中...</text>
                                            <text class="text-[20rpx] text-[#ffffff]/60 mt-[4rpx]"
                                                >成片转存中，请稍候</text
                                            >
                                        </template>
                                    </view>
                                </view>

                                <view
                                    v-else
                                    class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center bg-[#000000]/50 z-[22]">
                                    <template v-if="getStatus(item) == 2">
                                        <view class="bg-[#EF4444] px-[20rpx] py-[8rpx] rounded-full mb-[12rpx]">
                                            <text class="text-[22rpx] text-white font-medium">生成失败</text>
                                        </view>
                                        <text
                                            class="text-[20rpx] text-[#ffffff]/70 text-center px-[24rpx] line-clamp-3">
                                            {{ item.remark }}
                                        </text>
                                    </template>
                                    <template v-else-if="isQueueWaiting(item)">
                                        <view class="queue-waiting-card">
                                            <view class="queue-waiting-pill">
                                                <view class="queue-waiting-dot"></view>
                                                <text class="text-[22rpx] text-[#92400E] font-semibold">排队中...</text>
                                            </view>
                                            <text class="queue-waiting-pos"> 当前第 {{ item.queue_position }} 位 </text>
                                        </view>
                                    </template>
                                    <template v-else>
                                        <text class="rotation mb-[8rpx]"></text>
                                        <text class="text-[22rpx] text-[#ffffff]/80">正在生成中</text>
                                        <text class="text-[20rpx] text-[#ffffff]/60 mt-[4rpx]">几分钟即可生成视频</text>
                                    </template>
                                </view>

                                <view
                                    v-if="isHandle"
                                    class="absolute top-0 left-0 w-full h-full z-[44] rounded-[24rpx]"
                                    :class="{ 'bg-[#0065FB]/30': isSelect(index) }"
                                    @click="handleSelect(index)">
                                    <view class="absolute top-[12rpx] right-[12rpx]">
                                        <view
                                            class="w-[44rpx] h-[44rpx] rounded-full border-2 border-solid flex items-center justify-center transition-all duration-200"
                                            :class="
                                                isSelect(index)
                                                    ? 'bg-primary border-primary'
                                                    : 'border-white bg-[#ffffff]/20'
                                            ">
                                            <u-icon v-if="isSelect(index)" name="checkmark" color="#fff" size="22" />
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view class="flex items-center justify-between gap-x-[8rpx] mt-[12rpx] px-[4rpx]">
                                <text class="text-[26rpx] font-medium text-[#212121] line-clamp-1 break-all flex-1">{{
                                    item.name
                                }}</text>
                                <view
                                    class="w-[48rpx] h-[48rpx] rounded-full bg-[#F4F6FB] flex items-center justify-center active:opacity-70"
                                    @click="handleMore(item, index)">
                                    <u-icon name="more-dot-fill" color="#676767" size="22" />
                                </view>
                            </view>
                            <text class="text-[22rpx] text-[#676767] px-[4rpx]">{{ getTypeName(item.type) }}</text>
                        </view>
                    </view>

                    <view v-if="currentTab == 1" class="grid grid-cols-2 gap-[20rpx]">
                        <view v-for="(item, index) in dataLists" :key="index">
                            <view class="h-[400rpx] relative rounded-[24rpx] overflow-hidden shadow-sm">
                                <view class="w-full h-full relative" @click="handlePreview(item)">
                                    <image
                                        v-if="item.draw_type != 6"
                                        :src="item.image"
                                        lazy-load
                                        class="w-full h-full"
                                        mode="aspectFill" />
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
                                        :show-play-btn="false" />
                                    <view
                                        v-if="item.draw_type == 6"
                                        class="absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                        <view
                                            class="w-[72rpx] h-[72rpx] rounded-full bg-[#000000]/30 border border-solid border-[#ffffff]/40 flex items-center justify-center">
                                            <image src="/static/images/icons/play.svg" class="w-[32rpx] h-[32rpx]" />
                                        </view>
                                    </view>
                                </view>

                                <view
                                    v-if="isHandle"
                                    class="absolute top-0 left-0 w-full h-full z-[44] rounded-[24rpx]"
                                    :class="{ 'bg-[#0065FB]/30': isSelect(index) }"
                                    @click="handleSelect(index)">
                                    <view class="absolute top-[12rpx] right-[12rpx]">
                                        <view
                                            class="w-[44rpx] h-[44rpx] rounded-full border-2 border-solid flex items-center justify-center transition-all duration-200"
                                            :class="
                                                isSelect(index)
                                                    ? 'bg-primary border-primary'
                                                    : 'border-white bg-[#ffffff]/20'
                                            ">
                                            <u-icon v-if="isSelect(index)" name="checkmark" color="#fff" size="22" />
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view class="mt-[12rpx] flex justify-between items-start px-[4rpx]">
                                <view>
                                    <text class="text-[24rpx] font-medium text-[#212121] block">{{
                                        formatTime(item.create_time)
                                    }}</text>
                                    <text class="text-[22rpx] text-[#676767] mt-[4rpx] block">{{
                                        getDrawType(item.draw_type)
                                    }}</text>
                                </view>
                                <view
                                    class="w-[48rpx] h-[48rpx] rounded-full bg-[#F4F6FB] flex items-center justify-center active:opacity-70 mt-[4rpx]"
                                    @click="handleMore(item, index)">
                                    <u-icon name="more-dot-fill" color="#676767" size="22" />
                                </view>
                            </view>
                        </view>
                    </view>

                    <view v-if="currentTab == 2" class="flex flex-col gap-[16rpx]">
                        <view
                            class="bg-white rounded-[24rpx] overflow-hidden shadow-sm border border-solid border-[#E5E7EB] px-[16rpx] py-[20rpx]"
                            v-for="(item, index) in dataLists"
                            :key="index">
                            <puzzle-card :key="index" :item="item" @delete="reload" />
                        </view>
                    </view>
                </view>

                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>

        <view class="bg-white border-t border-solid border-[#F4F6FB] px-[32rpx] pt-[20rpx] pb-[50rpx]">
            <template v-if="currentTab == 0">
                <view
                    v-if="!isHandle"
                    class="w-full h-[96rpx] rounded-full bg-primary flex items-center justify-center shadow-md active:opacity-90"
                    @click="isHandle = true">
                    <text class="text-[30rpx] font-bold text-white">创建发布任务</text>
                </view>
                <view v-else class="flex items-center justify-between gap-[16rpx]">
                    <view
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-[24rpx]"
                        :class="handleList.length > 0 ? 'bg-primary' : 'bg-[#E5E7EB]'">
                        <text class="font-bold text-[32rpx] text-white">{{ handleList.length }}</text>
                        <text class="text-[20rpx] text-white mt-[4rpx]">已选</text>
                    </view>
                    <view class="flex items-center gap-[12rpx] flex-1 justify-end">
                        <view
                            class="h-[88rpx] px-[40rpx] flex items-center rounded-full bg-[#F4F6FB] active:opacity-70"
                            @click="
                                isHandle = false;
                                handleList = [];
                            ">
                            <text class="text-[28rpx] font-semibold text-[#676767]">取消</text>
                        </view>
                        <view
                            class="h-[88rpx] px-[40rpx] flex items-center rounded-full active:opacity-90"
                            :class="handleList.length > 0 ? 'bg-primary' : 'bg-[#E5E7EB]'"
                            @click="toPublish">
                            <text class="text-[28rpx] font-bold text-white">去发布</text>
                        </view>
                    </view>
                </view>
            </template>

            <view
                v-if="currentTab == 1 && dataLists.length > 0"
                class="flex items-center"
                :class="isHandle ? 'justify-between' : 'justify-end'">
                <view v-if="isHandle" class="flex items-center gap-[16rpx]">
                    <view
                        class="h-[80rpx] px-[32rpx] flex items-center rounded-full bg-[#EEF4FF] active:opacity-70"
                        @click="
                            isHandle = false;
                            handleList = [];
                        ">
                        <text class="text-[26rpx] font-semibold text-primary">取消</text>
                    </view>
                    <view class="flex items-center gap-[10rpx]" @click="handleSelect(-1)">
                        <view
                            class="w-[36rpx] h-[36rpx] rounded-full border-2 border-solid flex items-center justify-center transition-all"
                            :class="
                                handleList.length > 0 && handleList.length == dataLists.length
                                    ? 'bg-primary border-primary'
                                    : 'border-[#E5E7EB]'
                            ">
                            <u-icon
                                v-if="handleList.length > 0 && handleList.length == dataLists.length"
                                name="checkmark"
                                color="#fff"
                                size="20" />
                        </view>
                        <text class="text-[26rpx] text-[#676767]">全选</text>
                    </view>
                </view>
                <view>
                    <view
                        v-if="isHandle"
                        class="h-[80rpx] px-[32rpx] flex items-center rounded-full bg-[#FEF2F2] active:opacity-70"
                        @click="handleDelete(handleList)">
                        <text class="text-[26rpx] font-bold text-[#EF4444]">删除({{ handleList.length }})</text>
                    </view>
                    <view
                        v-else
                        class="h-[80rpx] px-[32rpx] flex items-center rounded-full bg-[#F4F6FB] active:opacity-70"
                        @click="isHandle = true">
                        <text class="text-[26rpx] font-semibold text-gray-600">管理</text>
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
        @update:show="showVideoPreview = false" />

    <EditNamePopup v-model="showEditPopup" v-model:name="newName" @confirm="handleEditConfirm" />

    <DownloadPopup v-model="showDownload" :operate-item="operateItem" @download="handleDownload" />

    <PlaySelectionModal v-model="showPlaySelection" :operate-item="operateItem" @play="triggerPlay" />

    <PublishTypePopup v-model="showPublishType" @select="toCreatePublishTask" />
</template>

<script setup lang="ts">
import { getVideoCreationRecord, deleteVideoCreationRecord, updateVideoCreationRecord } from "@/api/app";
import { retrySoraTask, downloadShanjianVideoTask } from "@/api/digital_human";
import { drawingRecord, drawingDeleteRecord, getPuzzleTaskList } from "@/api/drawing";
import { saveImageToPhotosAlbum, saveVideoToPhotosAlbum } from "@/utils/file";
import PuzzleCard from "@/packages/components/puzzle-card/puzzle-card.vue";
import EditNamePopup from "./components/popups/edit-name-popup.vue";
import DownloadPopup from "./components/popups/download-popup.vue";
import PlaySelectionModal from "./components/popups/play-selection-modal.vue";
import PublishTypePopup from "./components/popups/publish-type-popup.vue";

enum VideoDownloadStatusEnum {
    PENDING = 0,
    DOWNLOADING = 1,
    SUCCESS = 2,
    FAILED = 3,
}

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
    /** 闪剪数字人纯口播，展示归「数字人口播」 */
    DIGITAL_HUMAN_SHANJIAN = 9,
    /** 热点追踪（闪剪任务按 extra.source=hotspot 区分） */
    HOTSPOT = 10,
}

/** 视频创作队列状态 */
enum QueueStatus {
    WAITING = "waiting",
    SUBMITTED = "submitted",
    FAILED = "failed",
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
            { name: "热点追踪", key: VideoType.HOTSPOT },
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
const redownloadId = ref<number | null>(null);

const getDownloadStatus = (item: any) => Number(item?.download_status ?? VideoDownloadStatusEnum.SUCCESS);
const isDownloadFailed = (item: any) => getDownloadStatus(item) === VideoDownloadStatusEnum.FAILED;
const isDownloading = (item: any) => getDownloadStatus(item) === VideoDownloadStatusEnum.DOWNLOADING;
const showDownloadStatusUi = (item: any) => isDownloadFailed(item) || isDownloading(item);

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

/** 是否处于排队等待中 */
const isQueueWaiting = (item: any) => {
    const status = item?.queue_status;
    return status !== "" && status === QueueStatus.WAITING;
};

const getTypeName = (type: number) => {
    if (type === VideoType.DIGITAL_HUMAN_SHANJIAN) {
        return "数字人口播";
    }
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
    const params: { page_no: number; page_size: number; type?: number | string } = { page_no, page_size };
    // 数字人口播需同时查蝉镜(1)与闪剪纯口播(9)
    if (currentType.value === VideoType.DIGITAL_HUMAN) {
        params.type = `${VideoType.DIGITAL_HUMAN},${VideoType.DIGITAL_HUMAN_SHANJIAN}`;
    } else if (currentType.value !== VideoType.ALL) {
        params.type = currentType.value;
    }
    const res = await getVideoCreationRecord(params);
    return res.lists;
};

const fetchImages = async (page_no: number, page_size: number) => {
    const params: {
        page_no: number;
        page_size: number;
        draw_type?: number;
        type?: number | string;
    } = {
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
    if (showDownloadStatusUi(item)) return;

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

const showVideoActions = (item: any, index: number) => {
    const itemList = ["修改名称", "下载视频", "删除"];
    if (item.type == 6 && [1, 2].includes(getStatus(item))) {
        itemList.push("重试");
    }
    uni.showActionSheet({
        itemList,
        success: (res) => {
            if (res.tapIndex === 0) {
                // 多表 UNION 数据 id 会重复，须同时比对 type 定位
                handleEdit(
                    dataLists.value.findIndex(
                        (item: any) => item.id == operateItem.value.id && item.type == operateItem.value.type,
                    ),
                );
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
        const index = dataLists.value.findIndex(
            (item: any) => item.id == operateItem.value.id && item.type == operateItem.value.type,
        );
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
                        (item: any, index: number) => !deleteIndex.includes(index),
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

.queue-waiting-card {
    @apply flex flex-col items-center px-[24rpx];
}
.queue-waiting-pill {
    @apply inline-flex items-center gap-x-[10rpx] px-[20rpx] py-[8rpx] rounded-full mb-[12rpx];
    background: rgba(254, 243, 199, 0.92);
}
.queue-waiting-dot {
    @apply w-[12rpx] h-[12rpx] rounded-full flex-shrink-0;
    background: #f59e0b;
    animation: queuePulse 1.4s ease-in-out infinite;
}
.queue-waiting-pos {
    @apply text-[24rpx] text-white font-medium;
    text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.35);
}
@keyframes queuePulse {
    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.45;
        transform: scale(0.85);
    }
}
</style>
