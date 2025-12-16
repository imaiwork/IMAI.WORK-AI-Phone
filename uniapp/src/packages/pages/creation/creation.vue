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
                                <image :src="item.pic || DefaultBg" class="h-full w-full" mode="aspectFill"></image>
                                <view class="absolute bottom-1 px-2 text-[22rpx] text-white font-bold z-[33]">
                                    {{ item.create_time }}
                                </view>
                                <view class="text-[20rpx] text-white absolute top-2 left-2" v-if="item.clip_status != 0"
                                    >AI剪辑</view
                                >
                                <view
                                    v-if="getStatus(item) == 1"
                                    class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center z-[22]"
                                    @click="handlePlay(item)">
                                    <image src="/static/images/icons/play.svg" class="w-[58rpx] h-[58rpx]"></image>
                                    <view
                                        class="text-white text-center text-[22rpx] mt-[16rpx]"
                                        v-if="item.clip_status != 0">
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
                                        <view class="mt-[16rpx] text-center text-[22rpx] text-white">
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
                                            class="w-full h-full"
                                            lazy></image>
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
                    <view v-if="currentTab == 1" class="grid grid-cols-3 gap-3">
                        <view class="" v-for="(item, index) in dataLists" :key="index">
                            <view class="h-[220rpx] relative rounded-[20rpx] overflow-hidden">
                                <view class="w-full h-full relative" @click="handlePreview(item)">
                                    <image
                                        :src="item.image"
                                        lazy
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
                            <view class="text-xs mt-[14rpx] break-all">
                                {{ formatTime(item.create_time) }}
                            </view>
                            <view class="mt-1 flex items-center justify-between">
                                <view class="text-[22rpx] text-[#0000004d]">
                                    {{ getDrawType(item.draw_type) }}
                                </view>
                                <view class="p-1" @click="handleMore(item, index)">
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
                    class="bg-black rounded-[16rpx] h-[90rpx] flex items-center justify-center text-white font-bold"
                    @click="isHandle = true"
                    >创建发布任务</view
                >
                <view v-else class="flex items-center justify-between gap-x-2">
                    <view
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-md text-white"
                        :class="[handleList.length > 0 ? 'bg-black' : 'bg-[#787878CC]']">
                        <text class="font-bold text-[32rpx]">{{ handleList.length }}</text>
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
                            class="px-[58rpx] h-[90rpx] flex items-center rounded-[16rpx] text-white font-bold"
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
        :video-url="operateItem.url"
        :poster="operateItem.pic"
        @update:show="showVideoPreview = false"></video-preview-v2>
    <u-popup v-model="showEditPopup" mode="center" width="90%" :border-radius="20">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-bold text-center mt-2">编辑名称</view>
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
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold text-[#000000b3]"
                    @click="showEditPopup = false">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-bold text-white"
                    @click="handleEditConfirm"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
    <u-popup v-model="showDownload" mode="center" width="90%" :border-radius="20">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-bold text-center mt-2">选择下载类型</view>
            <view class="flex gap-2 mt-5">
                <view
                    class="flex-1 py-3 text-center bg-[#F3F3F3] rounded-[20rpx] font-bold text-[30rpx]"
                    @click="handleDownload(1)"
                    >下载原视频</view
                >
                <view
                    v-if="operateItem.clip_result_url"
                    class="flex-1 py-3 text-center bg-black rounded-[20rpx] text-white font-bold text-[30rpx]"
                    @click="handleDownload(2)"
                    >下载剪辑视频</view
                >
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { getVideoCreationRecord, deleteVideoCreationRecord, updateVideoCreationRecord } from "@/api/app";
import { drawingRecord, drawingDeleteRecord, getPuzzleTaskList } from "@/api/drawing";
import { saveImageToPhotosAlbum, saveVideoToPhotosAlbum } from "@/utils/file";
import PuzzleCard from "@/packages/components/puzzle-card/puzzle-card.vue";
import DefaultBg from "@/packages/static/images/common/default_bg.jpg";

enum VideoType {
    ALL = 0,
    DIGITAL_HUMAN = 1,
    ORAL_MIX = 2,
    TRUE_HUMAN = 3,
    MATERIAL_MIX = 4,
    NEWS = 5,
    SENTENCE = 6,
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

const isHandle = ref(false);
// 发布数据
const handleList = ref<any[]>([]);

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

const showVideoActions = (item: any, index: number) => {
    uni.showActionSheet({
        itemList: ["修改名称", "下载视频", "删除"],
        success: (res) => {
            if (res.tapIndex === 0) {
                handleEdit(dataLists.value.findIndex((item: any) => item.id == operateItem.value.id));
            } else if (res.tapIndex === 1) {
                if (!item.video_result_url && !item.clip_result_url) {
                    uni.$u.toast("视频未生成");
                    return;
                }
                if (item.clip_result_url) {
                    showDownload.value = true;
                } else {
                    handleDownload(1);
                }
            } else if (res.tapIndex === 2) {
                handleDelete(index);
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
    const { video_result_url, clip_result_url } = operateItem.value;
    const urlToSave = type === 1 ? video_result_url : clip_result_url;
    try {
        await saveVideoToPhotosAlbum(urlToSave);
    } catch (error) {
    } finally {
        showDownload.value = false;
    }
};

const handlePlay = (item: any) => {
    const { video_result_url, clip_result_url, pic } = item;
    operateItem.value.url = clip_result_url || video_result_url;
    operateItem.value.pic = pic;
    showVideoPreview.value = true;
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

const handlePreview = (item: any) => {
    const { draw_type, image, video_url } = item;
    if (draw_type == 6) {
        operateItem.value.url = video_url;
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
    const ids = handleList.value.map((index: number) => dataLists.value[index].task_id);
    uni.$u.route({
        url: "/ai_modules/device/pages/create_task/create_task?type=1",
        params: {
            source: "creation_video",
            ids: JSON.stringify(ids),
        },
    });
    handleList.value = [];
    isHandle.value = false;
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
});

onShow(async () => {
    await nextTick();
    pagingRef.value?.reload();
});
</script>

<style scoped lang="scss">
:deep(.u-tabs) {
    .u-tab-item {
        @apply font-bold;
    }
}
.radio-wrap {
    @apply w-[32rpx] h-[32rpx] rounded-full border border-solid border-[#c8c9cc];
}
.radio-wrap-active {
    @apply bg-primary border-primary;
}
</style>
