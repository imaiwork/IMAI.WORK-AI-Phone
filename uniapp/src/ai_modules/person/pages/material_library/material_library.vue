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
                <view
                    class="mt-2 bg-[#ffffff]/80 rounded-[32rpx] p-4 shadow-[0_8rpx_24rpx_rgba(0,101,251,0.04)] border border-white">
                    <view class="flex items-center">
                        <text class="text-[#999999]">当前IP：</text>
                        <text class="text-primary font-bold">{{ personDetail.persona_name }}</text>
                    </view>
                </view>
                <template v-if="personDetail.publish_mode === 1">
                    <view
                        class="mt-2 bg-[#ffffff]/80 rounded-[32rpx] p-4 shadow-[0_8rpx_24rpx_rgba(0,101,251,0.04)] border border-white active:bg-[#f9f9f9] transition-all"
                        @click="showRuleSettingPop = true">
                        <view class="flex items-center justify-between">
                            <view class="flex items-center gap-3">
                                <view
                                    class="shrink-0 w-[72rpx] h-[72rpx] bg-[#EEF4FF] rounded-[20rpx] flex items-center justify-center">
                                    <u-icon name="setting" color="#4A90E2" size="36"></u-icon>
                                </view>
                                <view class="flex flex-col justify-center">
                                    <text class="text-[28rpx] font-extrabold text-[#1A1A1A] mb-0.5"
                                        >AI合成规则配置</text
                                    >
                                    <text class="text-[22rpx] text-[#999999] line-clamp-1">{{ ruleSummary }}</text>
                                </view>
                            </view>
                            <u-icon name="arrow-right" size="22" color="#999999"></u-icon>
                        </view>
                    </view>
                    <view
                        v-if="isMaterialConflict"
                        class="mt-3 flex items-start gap-2 px-3 py-3 rounded-[20rpx] border border-solid border-[#FCA5A5]/50 bg-[#FEF2F2]">
                        <view class="flex-shrink-0 mt-[2rpx]">
                            <u-icon name="close-circle" color="#EF4444" size="26"></u-icon>
                        </view>
                        <text class="text-xs leading-relaxed text-[#DC2626]">
                            <text class="font-bold">任务挂起风险：</text>
                            素材库已空，且当前规则为【纯素材库】，自动发布任务将生成失败，请及时上传新素材。
                        </text>
                    </view>
                    <view
                        v-else-if="hasOverusedMaterial"
                        class="mt-3 flex items-start gap-2 px-3 py-3 rounded-[20rpx] border border-solid border-[#FED7AA]/50 bg-[#FFF7ED]">
                        <view class="flex-shrink-0 mt-[2rpx]">
                            <u-icon name="warning" color="#F97316" size="26"></u-icon>
                        </view>
                        <text class="text-xs leading-relaxed text-[#EA580C]">
                            <text class="font-bold">提醒：</text>
                            您有部分素材使用已达3次，重复抽取可能造成视频流量下降，建议及时删除或补充。
                        </text>
                    </view>
                    <view
                        v-else
                        class="mt-3 flex items-start gap-2 px-3 py-3 rounded-[20rpx] border border-solid border-[#4A90E2]/30 bg-[#EEF4FF]">
                        <view class="flex-shrink-0 mt-[2rpx]">
                            <u-icon name="info-circle" color="#4A90E2" size="26"></u-icon>
                        </view>
                        <view class="text-xs leading-relaxed text-primary">
                            温馨提示：为了获得更好的AI自动生成效果，建议视频长度保持在
                            <text class="font-bold">3-5秒</text>
                            ，视频数量
                            <text class="font-bold">尽量多</text>
                            ，且素材类型尽量
                            <text class="font-bold">通用</text>
                            。
                        </view>
                    </view>
                </template>
            </view>

            <view class="grow min-h-0 relative z-10 rounded-t-[48rpx] pt-6 pb-4 flex flex-col">
                <view class="flex items-center justify-between mb-5 px-[30rpx]">
                    <view class="flex items-center gap-2.5">
                        <view
                            v-for="(filter, index) in filters"
                            :key="index"
                            class="px-4 py-1.5 rounded-full text-xs transition-all duration-300 active:scale-95"
                            :class="
                                activeFilter === index ? 'bg-[#1A1A1A] text-white font-bold' : 'bg-white text-[#666666]'
                            "
                            @click="handleFilter(index)">
                            {{ filter.name }}
                        </view>
                    </view>
                    <view
                        class="flex items-center gap-1 px-3 py-1.5 bg-primary rounded-full active:scale-95 transition-all"
                        :class="isMaterialConflict ? 'ring-4 ring-primary/30 animate-pulse' : ''"
                        @click="showUploadCategoryPanel = true">
                        <u-icon name="plus" color="#ffffff" size="24"></u-icon>
                        <text class="text-xs text-white font-bold">上传</text>
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

                                    <view
                                        v-if="item.grab_type === 1"
                                        class="absolute top-2 left-2 flex items-center gap-[6rpx] px-[10rpx] py-[4rpx] rounded-[10rpx]"
                                        style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)">
                                        <text class="text-white text-[18rpx] leading-none">✦</text>
                                        <text class="text-white text-[18rpx] font-bold leading-none tracking-wide"
                                            >AI抓取</text
                                        >
                                    </view>
                                </view>

                                <view class="p-3">
                                    <view class="flex items-center justify-between mb-2.5">
                                        <text class="font-extrabold text-[#1A1A1A] truncate pr-2">
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
                                            class="px-1.5 py-0.5 rounded-[8rpx] text-[20rpx] font-medium border border-solid"
                                            :class="
                                                item.use_num > 3
                                                    ? 'bg-[#FEF7EE] text-[#DA6630] border-[#F8D9B0]'
                                                    : 'bg-[#F3FDF5] text-[#67B06E] border-[#E2FBE9]'
                                            ">
                                            {{ "已用" + item.use_num + "次" }}
                                        </view>
                                        <text class="text-[20rpx] text-[#999999]">{{
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

    <upload-category-panel v-model="showUploadCategoryPanel" @select="handleSelectCategory" />
    <choose-history v-model="showHistory" :limit="9" @select="handleSelectHistory" />
    <choose-material
        v-model="showMaterialLibrary"
        :mode="materialType"
        :limit="replaceMaterialIndex === -1 ? 9 : 1"
        @select="handleSelectMaterial" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <video-preview-v2
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false" />
    <rule-pop v-model="showRulePop" />
    <rule-setting-pop
        ref="ruleSettingPopRef"
        v-model="showRuleSettingPop"
        :is-material-empty="isMaterialEmpty"
        :person-id="personId"
        @confirm="handleRulesConfirm" />
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
import { UploadAlbumTypeEnum, UploadCategoryEnum } from "@/enums/appEnums";
import useUpload from "@/hooks/useUpload";
import { formatAudioTime, setFormData } from "@/utils/util";
import { useMaterial } from "@/ai_modules/person/hooks/useMaterial";
import { saveImageToPhotosAlbum, saveVideoToPhotosAlbum } from "@/utils/file";
import { MaterialSourceEnum } from "./enums";
import RulePop from "@/ai_modules/person/pages/material_library/components/rule-pop.vue";
import RuleSettingPop from "@/ai_modules/person/pages/material_library/components/rule-setting-pop.vue";

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
    grab_type: number;
}

interface PersonDetail {
    persona_name: string;
    publish_mode: number;
}

interface PlayItem {
    url: string;
    pic: string;
}

interface MaterialPayload {
    file_url: string;
    material_type: number;
    material_name: string;
    duration: number;
    thumbnail_url: string;
}

interface RulesState {
    synthTypes: string[];
    materialSource: string;
    copySource: string;
    copyStyle: string;
    coverSource: string;
    coverImage: string;
}

// ─── 页面基础状态 ──────────────────────────────────────────────────
const loading = ref<boolean>(true);
const initialized = ref<boolean>(false);
const adding = ref<boolean>(false);
const deleting = ref<boolean>(false);
const personId = ref<string>("");
// 从朋友圈发布页（setting_circle_publish）跳转进来时为 1，上传素材需标记为微信素材
const isWechat = ref<0 | 1>(0);
const deviceCount = ref<number>(0);
const activeFilter = ref<number>(0);
const pagingRef = ref();
const materialList = ref<MaterialItem[]>([]);

const personDetail = reactive<PersonDetail>({ persona_name: "", publish_mode: 1 });
const showVideoPreview = ref<boolean>(false);
const playItem = ref<PlayItem>({ url: "", pic: "" });
const showRulePop = ref<boolean>(false);
const filters = [
    { name: "全部", value: "1,2" },
    { name: "视频", value: 1 },
    { name: "图片", value: 2 },
] as const;

// ─── 上传 & 选择相关状态 ───────────────────────────────────────────
const showUploadCategoryPanel = ref<boolean>(false);
const showHistory = ref<boolean>(false);
const showMaterialLibrary = ref<boolean>(false);
const materialType = ref<string>("");
const replaceMaterialIndex = ref<number>(-1);

// ─── AI合成规则相关状态 ────────────────────────────────────────────
const showRuleSettingPop = ref<boolean>(false);
const ruleSummary = ref<string>("数字人口播 · AI+素材库 · 找爆款仿写");
const currentRulesState = ref<RulesState | null>(null);
const ruleSettingPopRef = ref<InstanceType<typeof RuleSettingPop>>();
const pendingOpenRule = ref<boolean>(false);

// ─── 计算属性 ─────────────────────────────────────────────────────
const isMaterialEmpty = computed(() => materialList.value.length === 0);

const isMaterialConflict = computed(
    () => currentRulesState.value?.materialSource === MaterialSourceEnum.PureLib && isMaterialEmpty.value,
);

const hasOverusedMaterial = computed(() => materialList.value.some((m) => m.use_num >= 3));

// ─── AI合成规则确认回调 ───────────────────────────────────────────
const handleRulesConfirm = (state: RulesState, summary: string): void => {
    currentRulesState.value = state;
    ruleSummary.value = summary;
};

// ─── useMaterial ──────────────────────────────────────────────────
const { processAndAppend } = useMaterial(toRef(materialList, "value"));

// ─── useUpload ────────────────────────────────────────────────────
const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    isTranscode: true,
    isFetchVideoInfo: true,
    videoDuration: [1, personDetail.publish_mode === 1 ? 59 : 300],
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

// ─── 选择分类 ─────────────────────────────────────────────────────
const handleSelectCategory = (category: UploadAlbumTypeEnum | UploadCategoryEnum): void => {
    if (
        UploadAlbumTypeEnum.File == category ||
        UploadAlbumTypeEnum.Image == category ||
        UploadAlbumTypeEnum.Video == category
    ) {
        uploadAndProcessFiles(category, { persona_id: personId.value });
    } else if (category === "library" || category === "group") {
        materialType.value = category === "library" ? "all" : "group";
        showMaterialLibrary.value = true;
    } else if (category === "creation") {
        showHistory.value = true;
    }
};

// ─── 从创作库选择回调 ─────────────────────────────────────────────
const handleSelectHistory = (selected: any[]): void => {
    processAndAppend({
        rawList: selected,
        urlField: "url",
        maxDuration: 59,
        replaceIndex: replaceMaterialIndex.value,
        onSuccess: (formatted) => {
            showHistory.value = false;
            const items: MaterialPayload[] = formatted.map((item) => ({
                file_url: item.url,
                thumbnail_url: item.pic,
                material_type: item.type === "video" ? 1 : 2,
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
        maxDuration: 59,
        onSuccess: () => {
            showMaterialLibrary.value = false;
            const items: MaterialPayload[] = selected.map((item) => ({
                file_url: item.url,
                material_type: item.type === "image" ? 2 : 1,
                material_name: item.name,
                duration: item.duration ?? 0,
                thumbnail_url: item.pic,
            }));
            handleAddMaterial(items);
        },
    });
};

// ─── 添加素材 ─────────────────────────────────────────────────────
const handleAddMaterial = async (items: MaterialPayload[]): Promise<void> => {
    if (adding.value) return;
    uni.showLoading({ title: "添加中...", mask: true });
    try {
        adding.value = true;
        await batchAddMaterial({
            persona_id: personId.value,
            items: items.map((item: any) => ({
                ...item,
                publish_mode: isWechat.value ? 1 : personDetail.publish_mode,
            })),
        });
        uni.hideLoading();
        uni.showToast({ title: "添加成功", icon: "none", duration: 3000 });
        pagingRef.value?.reload();
    } catch (error: unknown) {
        uni.hideLoading();
        const msg = typeof error === "string" ? error : "添加失败，请重试";
        uni.showToast({ title: msg, icon: "none", duration: 3000 });
    } finally {
        adding.value = false;
    }
};

// ─── 删除素材 ─────────────────────────────────────────────────────
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
        itemList: ["查看详情", "删除", "下载"],
        success: ({ tapIndex }) => {
            if (tapIndex === 0) {
                uni.navigateTo({
                    url: `/ai_modules/person/pages/material_detail/material_detail?id=${item.id}&persona_id=${personId.value}`,
                });
            } else if (tapIndex === 1) {
                handleDeleteMaterial(item);
            } else if (tapIndex === 2) {
                item.material_type === 1
                    ? saveVideoToPhotosAlbum(item.file_url)
                    : saveImageToPhotosAlbum(item.file_url);
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

// ─── 播放 & 预览 ──────────────────────────────────────────────────
const handlePlay = (item: MaterialItem): void => {
    playItem.value = { url: item.file_url, pic: item.thumbnail_url };
    showVideoPreview.value = true;
};

const previewImage = (url: string): void => {
    uni.previewImage({ urls: [url] });
};

// ─── 分页查询 ─────────────────────────────────────────────────────
const handleQuery = async (page_no: number, page_size: number): Promise<void> => {
    try {
        const { lists } = await getMaterialLibraryList({
            persona_id: personId.value,
            page_no,
            page_size,
            material_type: filters[activeFilter.value].value,
            publish_mode: isWechat.value ? 1 : personDetail.publish_mode,
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

// ─── 初始化 ───────────────────────────────────────────────────────
const init = async (): Promise<void> => {
    loading.value = true;
    try {
        const [detailResult, deviceResult] = await Promise.allSettled([
            getPersonDetail({ id: personId.value }),
            getPersonDeviceList({ persona_id: personId.value }),
        ]);
        if (detailResult.status === "fulfilled") setFormData(detailResult.value, personDetail);
        if (deviceResult.status === "fulfilled") {
            deviceCount.value = deviceResult.value?.devices?.length ?? 0;
        }
        await ruleSettingPopRef.value?.getDetail();

        setTimeout(() => {
            ruleSummary.value = ruleSettingPopRef.value?.buildSummary() ?? "";
        }, 100);
        if (pendingOpenRule.value) {
            await nextTick();
            showRuleSettingPop.value = true;
            pendingOpenRule.value = false;
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
});

onLoad((options: any) => {
    personId.value = options.id ?? "";
    pendingOpenRule.value = options.open === "rule";
    isWechat.value = Number(options.is_wechat) === 1 ? 1 : 0;
    init();
});
</script>
