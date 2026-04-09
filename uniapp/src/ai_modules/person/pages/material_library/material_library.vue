<template>
    <view class="h-screen bg-[#F4F7FA] relative flex flex-col">
        <view
            class="absolute top-0 left-0 right-0 h-[500rpx] z-0"
            style="background: linear-gradient(180deg, #dcebff 0%, #f4f7fa 100%)"></view>

        <u-navbar :border-bottom="false" :background="{ background: 'transparent' }" title="配置素材库" title-bold>
        </u-navbar>

        <template v-if="loading">
            <view class="relative z-10 px-[30rpx] pt-2 animate-pulse">
                <view class="bg-white rounded-[32rpx] p-4">
                    <view class="h-[32rpx] w-1/3 bg-[#F3F4F6] rounded-full mb-3"></view>
                    <view class="flex items-center gap-3">
                        <view class="w-[72rpx] h-[72rpx] bg-[#F3F4F6] rounded-[20rpx] flex-shrink-0"></view>
                        <view class="flex flex-col gap-2 flex-1">
                            <view class="h-[28rpx] w-1/2 bg-[#F3F4F6] rounded-full"></view>
                            <view class="h-[22rpx] w-2/3 bg-[#F3F4F6] rounded-full"></view>
                        </view>
                    </view>
                </view>
            </view>
            <view class="grow min-h-0 relative z-10 bg-white mt-5 rounded-t-[48rpx] pt-6 px-[30rpx] animate-pulse">
                <view class="flex gap-2.5 mb-5">
                    <view v-for="i in 3" :key="i" class="h-[52rpx] w-[100rpx] bg-[#F3F4F6] rounded-full"></view>
                </view>
                <view class="grid grid-cols-2 gap-3.5">
                    <view v-for="i in 6" :key="i" class="bg-[#F3F4F6] rounded-[24rpx] overflow-hidden">
                        <view class="aspect-[4/3] bg-[#E9EAEC]"></view>
                        <view class="p-3 flex flex-col gap-2">
                            <view class="h-[28rpx] w-3/4 bg-[#E9EAEC] rounded-full"></view>
                            <view class="h-[22rpx] w-1/2 bg-[#E9EAEC] rounded-full"></view>
                        </view>
                    </view>
                </view>
            </view>
        </template>

        <template v-else>
            <view class="relative z-10 px-[30rpx] pt-2">
                <view class="px-2 py-3">
                    <view class="flex items-center gap-3">
                        <view
                            class="w-[72rpx] h-[72rpx] bg-white rounded-[20rpx] flex items-center justify-center flex-shrink-0"
                            style="box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.06)">
                            <u-icon name="bookmark" size="36" color="#8a9ab5"></u-icon>
                        </view>
                        <view class="flex flex-col justify-center">
                            <text class="text-[28rpx] font-extrabold text-[#1A1A1A] mb-0.5">{{
                                personDetail.publish_mode === 1 ? "自动生成内容" : "指定素材内容"
                            }}</text>
                            <text class="text-[22rpx] text-[#666666]">{{
                                personDetail.publish_mode === 1
                                    ? "素材池里的任务内容，AI自动生成"
                                    : "手动指定素材内容进行生成"
                            }}</text>
                        </view>
                    </view>
                </view>
                <view
                    class="mt-2 bg-[#ffffff]/80 rounded-[32rpx] p-4 shadow-[0_8rpx_24rpx_rgba(0,101,251,0.04)] border border-white">
                    <view class="flex items-center border-[0] border-b border-solid border-[#ececec]/80 pb-3 mb-3">
                        <text class="text-[26rpx] text-[#999999]">当前IP：</text>
                        <text class="text-[26rpx] text-primary font-bold">{{ personDetail.persona_name }}</text>
                    </view>
                    <view class="flex items-center justify-between">
                        <view class="flex items-center gap-3">
                            <view
                                class="w-[72rpx] h-[72rpx] bg-[#F6F6F6] rounded-[20rpx] flex items-center justify-center">
                                <image src="@/ai_modules/person/static/icons/device.svg" class="w-[36rpx] h-[36rpx]" />
                            </view>
                            <view class="flex flex-col justify-center">
                                <view class="text-[28rpx] font-extrabold text-[#1A1A1A] mb-0.5">
                                    关联设备：<text class="text-primary">{{ deviceCount }}台</text>
                                </view>
                                <view class="text-[22rpx] text-[#999999]">素材池自动分发给关联的设备</view>
                            </view>
                        </view>
                        <view class="flex items-center text-[#999999]" @click="handleRule">
                            <text class="text-[24rpx] mr-0.5">规则</text>
                            <u-icon name="arrow-right" size="22"></u-icon>
                        </view>
                    </view>
                </view>
            </view>

            <view class="grow min-h-0 relative z-10 bg-white mt-5 rounded-t-[48rpx] pt-6 pb-4 flex flex-col">
                <view class="flex items-center justify-between mb-5 px-[30rpx]">
                    <view class="flex items-center gap-2.5">
                        <view
                            v-for="(filter, index) in filters"
                            :key="index"
                            class="px-4 py-1.5 rounded-full text-[24rpx] transition-all duration-300 active:scale-95"
                            :class="
                                activeFilter === index
                                    ? 'bg-[#1A1A1A] text-white font-bold'
                                    : 'bg-[#F4F5F7] text-[#666666]'
                            "
                            @click="handleFilter(index)">
                            {{ filter.name }}
                        </view>
                    </view>
                    <view
                        class="flex items-center gap-1 px-3 py-1.5 bg-primary rounded-full active:scale-95 transition-all"
                        @click="chooseUploadType">
                        <u-icon name="plus" color="#ffffff" size="24"></u-icon>
                        <text class="text-[24rpx] text-white font-bold">上传</text>
                    </view>
                </view>
                <view class="grow min-h-0">
                    <z-paging ref="pagingRef" v-model="materialList" :fixed="false" @query="handleQuery">
                        <view class="grid grid-cols-2 gap-3.5 px-[30rpx]">
                            <view
                                v-for="item in materialList"
                                :key="item.id"
                                class="bg-white rounded-[24rpx] shadow-[0_4rpx_20rpx_rgba(0,0,0,0.03)] border border-solid border-[#ececec] overflow-hidden">
                                <view
                                    class="relative aspect-[4/3] bg-[#F6F6F6]"
                                    @click="previewImage(item.thumbnail_url)">
                                    <view class="w-full h-full leading-[0]">
                                        <image
                                            :src="item.thumbnail_url"
                                            class="w-full h-full"
                                            mode="aspectFill"></image>
                                    </view>
                                    <text
                                        v-if="item.duration"
                                        class="absolute bottom-2 left-2.5 text-white text-[22rpx] font-mono font-medium">
                                        {{ formatAudioTime(item.duration) }}
                                    </text>
                                    <view
                                        v-if="item.material_type === 1"
                                        class="absolute bottom-2 right-2 w-[44rpx] h-[44rpx] rounded-full bg-[#ffffff]/30 flex items-center justify-center border border-solid border-[#ffffff]/40"
                                        @click.stop="handlePlay(item)">
                                        <u-icon
                                            name="play-right-fill"
                                            color="#ffffff"
                                            size="20"
                                            class="ml-0.5"></u-icon>
                                    </view>
                                </view>
                                <view class="p-3">
                                    <view class="flex items-center justify-between mb-2.5">
                                        <text class="text-[26rpx] font-extrabold text-[#1A1A1A] truncate pr-2">
                                            {{ item.material_name }}
                                        </text>
                                        <view
                                            class="p-1 -mr-1 text-[#999999] rounded-full"
                                            @click.stop="handleMore(item)">
                                            <u-icon name="more-dot-fill" size="28"></u-icon>
                                        </view>
                                    </view>
                                    <view class="flex items-center justify-between">
                                        <view
                                            class="px-1.5 py-0.5 rounded-[8rpx] text-[20rpx] font-medium"
                                            :class="
                                                item.use_status === 2
                                                    ? 'bg-[#FFF0F0] text-[#FF4D4F]'
                                                    : 'bg-[#E6F8F3] text-[#00C08E]'
                                            ">
                                            {{ item.use_status === 2 ? "已停用" : "已用" + item.use_num + "次" }}
                                        </view>
                                        <text class="text-[22rpx] text-[#999999]">{{
                                            formatTime(item.create_time)
                                        }}</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>
            </view>
        </template>
    </view>

    <choose-history v-model="showHistory" :limit="1" @select="handleSelectHistory" />

    <choose-material
        v-model="showMaterialLibrary"
        type="all"
        :limit="replaceMaterialIndex === -1 ? 9 : 1"
        @select="handleSelectMaterial" />

    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />

    <video-preview-v2
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false" />
    <rule-pop v-model="showRulePop" />
</template>

<script setup lang="ts">
import {
    getPersonDetail,
    getPersonDeviceList,
    getMaterialLibraryList,
    batchAddMaterial,
    deleteMaterial,
    updateMaterial,
} from "@/api/person";
import useUpload from "@/hooks/useUpload";
import { formatAudioTime, setFormData } from "@/utils/util";
import { useMaterial } from "@/ai_modules/person/hooks/useMaterial";
import ChooseHistory from "@/ai_modules/person/components/choose-history/choose-history.vue";
import RulePop from "@/ai_modules/person/pages/material_library/components/rule-pop.vue";
import { saveImageToPhotosAlbum, saveVideoToPhotosAlbum } from "@/utils/file";

// ─── 类型定义 ─────────────────────────────────────────────────────
interface MaterialItem {
    id: string;
    material_name: string;
    thumbnail_url: string;
    file_url: string;
    duration: number;
    use_status: number;
    create_time: string;
    material_type: number;
    use_num: number;
}

interface PersonDetail {
    persona_name: string;
    publish_mode: number;
}

interface PlayItem {
    url: string;
    pic: string;
}

/** 提交给接口的素材结构 */
interface MaterialPayload {
    file_url: string;
    material_type: number;
    material_name: string;
    duration: number;
    thumbnail_url: string;
}

// ─── 页面基础状态 ──────────────────────────────────────────────────
const loading = ref<boolean>(true);
const initialized = ref<boolean>(false); // 标记首次初始化完成
const adding = ref<boolean>(false); // 防重：添加素材
const deleting = ref<boolean>(false); // 防重：删除素材
const personId = ref<string>("");
const deviceCount = ref<number>(0);
const activeFilter = ref<number>(0);
const pagingRef = ref();
const materialList = ref<MaterialItem[]>([]);

const personDetail = reactive<PersonDetail>({ persona_name: "", publish_mode: 1 });
const showVideoPreview = ref<boolean>(false);
const playItem = ref<PlayItem>({ url: "", pic: "" });
const showRulePop = ref<boolean>(false);
const filters = [
    { name: "全部", value: "" },
    { name: "视频", value: 1 },
    { name: "图片", value: 2 },
] as const;

// ─── 上传 & 选择相关状态 ───────────────────────────────────────────
const showHistory = ref<boolean>(false);
const showMaterialLibrary = ref<boolean>(false);
const uploadMaterialType = ref<"image" | "video">("image");
const replaceMaterialIndex = ref<number>(-1);

// ─── useMaterial ──────────────────────────────────────────────────
const { processAndAppend } = useMaterial(toRef(materialList, "value"));

// ─── useUpload ────────────────────────────────────────────────────
const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    isTranscode: true,
    isFetchVideoInfo: true,
    videoDuration: [1, 59],
    onSuccess: (res: any[]) => {
        const items: MaterialPayload[] = res.map((item) => ({
            file_url: item.url,
            material_type: item.type == "image" ? 2 : 1,
            material_name: item.name,
            duration: item.duration ?? 0,
            thumbnail_url: item.pic,
        }));
        handleAddMaterial(items);
    },
});

// ─── 选择上传方式 ─────────────────────────────────────────────────
const chooseUploadType = (): void => {
    uni.showActionSheet({
        itemList: ["从图片相册选择", "从视频相册选择", "从素材库选择", "从创作库选择"],
        success: ({ tapIndex }) => {
            if (tapIndex === 0) {
                uploadAndProcessFiles("image");
            } else if (tapIndex === 1) {
                uploadAndProcessFiles("video");
            } else if (tapIndex === 2) {
                showMaterialLibrary.value = true;
            } else {
                uploadMaterialType.value = "video";
                showHistory.value = true;
            }
        },
    });
};

// ─── 从创作库选择回调 ─────────────────────────────────────────────
const handleSelectHistory = (selected: any[]): void => {
    selected.forEach((item) => {
        item.url = item.clip_result_url || item.video_result_url;
    });
    processAndAppend({
        rawList: selected,
        urlField: "url",
        type: uploadMaterialType.value,
        maxDuration: 59,
        replaceIndex: replaceMaterialIndex.value,
        onSuccess: () => {
            showHistory.value = false;
            const items: MaterialPayload[] = selected.map((item) => ({
                file_url: item.url,
                thumbnail_url: item.pic,
                material_type: 1,
                material_name: item.name,
                duration: Number(item.duration),
            }));
            handleAddMaterial(items);
        },
    });
};

// ─── 从素材库选择回调 ─────────────────────────────────────────────
const handleSelectMaterial = (selected: any[]): void => {
    processAndAppend({
        rawList: selected,
        urlField: "url",
        type: uploadMaterialType.value,
        maxDuration: 59,
        onSuccess: () => {
            showMaterialLibrary.value = false;
            const items: MaterialPayload[] = selected.map((item) => ({
                file_url: item.url,
                material_type: item.m_type === 1 ? 2 : 1,
                material_name: item.name,
                duration: item.duration ?? 0,
                thumbnail_url: item.pic,
            }));
            handleAddMaterial(items);
        },
    });
};

// ─── 添加素材（局部变量传参，消除 ref 中转的并发隐患） ────────────
const handleAddMaterial = async (items: MaterialPayload[]): Promise<void> => {
    if (adding.value) return;
    try {
        adding.value = true;
        await batchAddMaterial({
            persona_id: personId.value,
            items: items.map((item: any) => ({ ...item, publish_mode: personDetail.publish_mode })),
        });
        uni.showToast({ title: "添加成功", icon: "none", duration: 3000 });
        pagingRef.value?.reload();
    } catch (error: unknown) {
        const msg = typeof error === "string" ? error : "添加失败，请重试";
        uni.showToast({ title: msg, icon: "none", duration: 3000 });
    } finally {
        adding.value = false;
    }
};

// ─── 删除素材（抽出独立函数，消除嵌套） ──────────────────────────
const handleDeleteMaterial = async (item: MaterialItem): Promise<void> => {
    if (deleting.value) return;
    uni.showModal({
        title: "提示",
        content: "确定删除该素材吗？",
        success: async ({ confirm }) => {
            if (!confirm) return;
            try {
                deleting.value = true;
                await deleteMaterial({ id: item.id });
                uni.showToast({ title: "删除成功", icon: "success", duration: 2000 });
                pagingRef.value?.reload();
            } catch (error: any) {
                uni.showToast({ title: error, icon: "none", duration: 3000 });
            } finally {
                deleting.value = false;
            }
        },
    });
};

// ─── 更多操作 ─────────────────────────────────────────────────────
const handleMore = (item: MaterialItem): void => {
    const isStop = item.use_status === 2;
    uni.showActionSheet({
        itemList: ["查看详情", "删除", isStop ? "启用" : "停用", "下载"],
        success: ({ tapIndex }) => {
            if (tapIndex === 0) {
                uni.navigateTo({
                    url: `/ai_modules/person/pages/material_detail/material_detail?id=${item.id}&persona_id=${personId.value}`,
                });
            } else if (tapIndex === 1) {
                handleDeleteMaterial(item);
            } else if (tapIndex === 2) {
                handleUpdateMaterialStatus(item);
            } else if (tapIndex === 3) {
                if (item.material_type === 1) {
                    saveVideoToPhotosAlbum(item.file_url);
                } else {
                    saveImageToPhotosAlbum(item.file_url);
                }
            }
        },
    });
};

const handleUpdateMaterialStatus = async (item: MaterialItem): Promise<void> => {
    uni.showLoading({ title: "操作中...", mask: true });
    try {
        await updateMaterial({
            id: item.id,
            persona_id: personId.value,
            use_status: item.use_status === 1 ? 2 : 1,
        });
        pagingRef.value?.reload();
        uni.hideLoading();
        uni.showToast({ title: "操作成功", icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

// ─── 播放预览 ─────────────────────────────────────────────────────
const handlePlay = (item: MaterialItem): void => {
    playItem.value = { url: item.file_url, pic: item.thumbnail_url };
    showVideoPreview.value = true;
};

// ─── 图片预览 ─────────────────────────────────────────────────────
const previewImage = (url: string): void => {
    uni.previewImage({
        urls: [url],
    });
};

// ─── 分页查询 ─────────────────────────────────────────────────────
const handleQuery = async (page_no: number, page_size: number): Promise<void> => {
    try {
        const { lists } = await getMaterialLibraryList({
            persona_id: personId.value,
            page_no,
            page_size,
            material_type: filters[activeFilter.value].value,
            publish_mode: personDetail.publish_mode,
        });
        pagingRef.value?.complete(lists);
    } catch {
        pagingRef.value?.complete([]);
    }
};

// ─── 筛选切换 ─────────────────────────────────────────────────────
const handleFilter = (index: number): void => {
    activeFilter.value = index;
    pagingRef.value?.reload();
};

// ─── 规则跳转 ─────────────────────────────────────────────────────
const handleRule = (): void => {
    showRulePop.value = true;
};

// ─── 时间格式化 ───────────────────────────────────────────────────
const formatTime = (time: string): string => uni.$u.timeFormat(time, "yyyy-mm-dd hh:MM");

// ─── 初始化数据 ───────────────────────────────────────────────────
const init = async (): Promise<void> => {
    loading.value = true;
    try {
        const [detailResult, deviceResult] = await Promise.allSettled([
            getPersonDetail({ id: personId.value }),
            getPersonDeviceList({ persona_id: personId.value }),
        ]);

        if (detailResult.status === "fulfilled") {
            setFormData(detailResult.value, personDetail);
        }

        if (deviceResult.status === "fulfilled") {
            deviceCount.value = deviceResult.value?.devices?.length ?? 0;
        }
    } finally {
        loading.value = false;
        initialized.value = true;
    }
};

// ─── 生命周期 ─────────────────────────────────────────────────────

onShow(async () => {
    if (!initialized.value) return;
    await nextTick();
    pagingRef.value?.reload();
});

onLoad((options: any) => {
    personId.value = options.id ?? "";
    init();
});
</script>
