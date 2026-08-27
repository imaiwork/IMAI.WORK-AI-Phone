<template>
    <popup-bottom v-model="show" title="创作历史" height="85%" :is-disabled-touch="true" custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full flex flex-col">
                <template v-if="type === 'all'">
                    <view class="flex-shrink-0 mt-2 px-[30rpx]">
                        <view class="flex bg-[#F3F4F6] rounded-[12rpx] p-[4rpx]">
                            <view
                                v-for="tab in allTabList"
                                :key="tab.key"
                                class="flex-1 flex items-center justify-center py-[10rpx] rounded-[10rpx] transition-all"
                                :class="
                                    currentAllTab === tab.key
                                        ? 'bg-white text-[#111827] font-medium shadow-sm'
                                        : 'text-[#6B7280]'
                                "
                                @click="handleAllTab(tab.key)">
                                {{ tab.name }}
                            </view>
                        </view>
                    </view>
                </template>
                <template v-if="type === 'video' || (currentAllTab === 'video' && type !== 'image')">
                    <scroll-view scroll-x scroll-with-animation class="flex-shrink-0 mt-2">
                        <view class="flex gap-x-2 py-1 px-[30rpx]">
                            <view v-for="(item, index) in typeList" :key="index" @click="handleType(item.key)">
                                <view
                                    class="px-[24rpx] py-[10rpx] rounded-[10rpx] whitespace-nowrap text-xs"
                                    :class="[
                                        currentType === item.key
                                            ? 'bg-primary text-white'
                                            : 'shadow-[0_0_0_2rpx_rgba(0,0,0,0.1)] text-[#374151]',
                                    ]">
                                    {{ item.name }}
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </template>

                <view class="flex items-center justify-between mt-2 px-[30rpx]" @click="toggleChoosePanel">
                    <view class="flex items-center gap-[10rpx]">
                        <text class="text-xs text-[#00000080]">
                            已选：<text
                                class="font-semibold"
                                :class="chooseLists.length >= limit ? 'text-[#EF4444]' : 'text-primary'"
                                >{{ chooseLists.length }}</text
                            >
                        </text>
                        <view
                            v-if="limit && limit > 1"
                            class="flex items-center gap-[4rpx] px-[12rpx] h-[36rpx] rounded-full"
                            :class="chooseLists.length >= limit ? 'bg-[#FEF2F2]' : 'bg-[#F0F2F5]'">
                            <u-icon
                                :name="chooseLists.length >= limit ? 'info-circle-fill' : 'info-circle'"
                                :color="chooseLists.length >= limit ? '#EF4444' : '#9CA3AF'"
                                size="18" />
                            <text
                                class="text-[20rpx] font-medium"
                                :class="chooseLists.length >= limit ? 'text-[#EF4444]' : 'text-[#9CA3AF]'">
                                最多 {{ limit }} 个
                            </text>
                        </view>
                    </view>
                    <view v-if="chooseLists.length > 0" class="flex items-center gap-1">
                        <text class="text-xs text-[#6B7280]">{{ showChoosePanel ? "收起" : "查看已选" }}</text>
                        <u-icon :name="showChoosePanel ? 'arrow-up' : 'arrow-down'" size="12" color="#6B7280" />
                    </view>
                </view>
                <view
                    v-if="showChoosePanel && chooseLists.length > 0"
                    class="mx-[30rpx] mt-2 mb-1 bg-white rounded-[16rpx] border border-[#F1F5F9] shadow-sm overflow-hidden flex-shrink-0">
                    <view class="flex items-center justify-between px-3 py-2 border-b border-[#F1F5F9]">
                        <text class="text-xs font-medium text-[#374151]">已选（{{ chooseLists.length }}）</text>
                        <text class="text-[22rpx] text-[#EF4444]" @click.stop="clearAll">清空</text>
                    </view>
                    <view class="max-h-[400rpx] overflow-y-auto">
                        <view v-for="(group, gIndex) in unifiedChooseGroups" :key="gIndex">
                            <view
                                class="flex items-center justify-between px-3 py-[10rpx] bg-[#F9FAFB] border-b border-[#F1F5F9]">
                                <view class="flex items-center gap-1">
                                    <view class="w-[6rpx] h-[24rpx] rounded-full bg-primary"></view>
                                    <text class="text-[22rpx] font-medium text-[#374151]">{{ group.name }}</text>
                                    <text class="text-[20rpx] text-[#9CA3AF]">（{{ group.items.length }}）</text>
                                </view>
                            </view>
                            <scroll-view scroll-x class="w-full" :show-scrollbar="false">
                                <view class="flex gap-2 px-3 py-2" style="width: max-content">
                                    <view
                                        v-for="item in group.items"
                                        :key="recordKey(item)"
                                        class="relative flex-shrink-0 w-[120rpx] h-[160rpx] rounded-[12rpx] overflow-hidden">
                                        <image
                                            :src="item.image || item.pic"
                                            class="w-full h-full"
                                            mode="aspectFill"
                                            lazy-load />
                                        <view
                                            v-if="group.key === 'video' && item.duration > 0"
                                            class="absolute top-1 left-1 px-[8rpx] py-[2rpx] rounded-[6rpx] bg-[#00000080]">
                                            <text class="text-[18rpx] text-white">{{
                                                formatAudioTime(item.duration)
                                            }}</text>
                                        </view>
                                        <view
                                            class="absolute bottom-1 right-1 w-[32rpx] h-[32rpx] rounded-full bg-primary flex items-center justify-center">
                                            <text class="text-[18rpx] text-white font-medium">{{
                                                getChooseIndex(item)
                                            }}</text>
                                        </view>
                                        <view
                                            class="absolute top-1 right-1 w-[36rpx] h-[36rpx] rounded-full bg-[#00000080] flex items-center justify-center"
                                            @click.stop="handleSelect(item)">
                                            <u-icon name="close" size="12" color="#fff" />
                                        </view>
                                    </view>
                                </view>
                            </scroll-view>
                        </view>
                    </view>
                </view>
                <view class="grow min-h-0 mt-[20rpx] relative">
                    <view v-if="isLoading" class="absolute inset-0 z-10 bg-[#F9FAFB] px-[30rpx] pt-1">
                        <view class="grid grid-cols-3 gap-2">
                            <view v-for="i in 9" :key="i" class="h-[288rpx] rounded-xl skeleton" />
                        </view>
                    </view>

                    <z-paging
                        class="h-full"
                        ref="pagingRef"
                        v-model="dataLists"
                        :fixed="false"
                        :auto="false"
                        :default-page-size="100"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="grid grid-cols-3 gap-2 px-[30rpx]">
                            <view
                                class="rounded-xl relative overflow-hidden"
                                :class="type === 'video' ? 'h-[288rpx]' : 'aspect-[3/4]'"
                                v-for="(item, index) in displayLists"
                                :key="index"
                                @click="handleSelect(item)">
                                <image
                                    :src="item.image || item.pic"
                                    class="w-full h-full rounded-xl"
                                    mode="aspectFill" />
                                <view
                                    v-if="
                                        (type === 'video' || (type === 'all' && currentAllTab === 'video')) &&
                                        item.duration > 0
                                    "
                                    class="absolute top-2 left-2 px-[10rpx] py-[4rpx] rounded-[8rpx] bg-[#00000066] flex items-center gap-[4rpx]">
                                    <u-icon name="play-right-fill" size="10" color="#fff" />
                                    <text class="text-[20rpx] text-white leading-none">{{
                                        formatAudioTime(item.duration)
                                    }}</text>
                                </view>
                                <view class="absolute top-0 left-0 w-full h-full bg-[#00000080]" v-if="isChoose(item)">
                                    <view class="absolute top-2 right-2">
                                        <image src="/static/images/icons/success.svg" class="w-[28rpx] h-[28rpx]" />
                                    </view>
                                    <view
                                        v-if="type === 'video' || (type === 'all' && currentAllTab === 'video')"
                                        class="absolute bottom-2 right-2 w-[36rpx] h-[36rpx] rounded-full bg-primary flex items-center justify-center">
                                        <text class="text-[20rpx] text-white font-medium">{{
                                            getChooseIndex(item)
                                        }}</text>
                                    </view>
                                </view>
                                <view
                                    class="absolute top-2 right-2 w-[28rpx] h-[28rpx] rounded-full bg-white opacity-80"
                                    v-else></view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>

                <view class="relative z-20 flex items-center justify-between gap-2 mt-[20rpx] mb-4 px-[30rpx]">
                    <view v-if="limit && limit > 1" class="flex flex-col gap-[6rpx]">
                        <view class="flex items-center gap-x-2" @click="toggleSelect">
                            <view class="w-[32rpx] h-[32rpx]">
                                <image
                                    v-if="isCurrentPageAllSelected"
                                    src="/static/images/icons/success.svg"
                                    class="w-full h-full" />
                                <view
                                    class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]"
                                    v-else></view>
                            </view>
                            <text class="text-[#374151]">全选</text>
                        </view>
                    </view>

                    <view
                        class="flex-1 text-white font-medium text-[30rpx] rounded-[20rpx] h-[90rpx] flex items-center justify-center relative overflow-hidden"
                        :class="chooseLists.length > 0 ? 'bg-primary' : 'bg-[#C0C4CC]'"
                        @click="confirm">
                        <text class="relative z-10">确定选择</text>
                        <text v-if="chooseLists.length > 0" class="text-xs opacity-80 ml-[6rpx] relative z-10">
                            ({{ chooseLists.length }}/{{ limit }})
                        </text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getVideoCreationRecord } from "@/api/app";
import { drawingRecord } from "@/api/drawing";
import { formatAudioTime } from "@/utils/util";

enum VideoType {
    ALL = 0,
    DIGITAL_HUMAN = 1,
    ORAL_MIX = 2,
    TRUE_HUMAN = 3,
    MATERIAL_MIX = 4,
    NEWS = 5,
    SENTENCE = 6,
    MONTAGE_STORYBOARD = 7,
    /** 闪剪数字人纯口播，展示归「数字人口播」 */
    DIGITAL_HUMAN_SHANJIAN = 9,
}

const typeList = [
    { name: "全部", key: VideoType.ALL },
    { name: "数字人口播", key: VideoType.DIGITAL_HUMAN },
    { name: "口播混剪", key: VideoType.ORAL_MIX },
    { name: "真人口播", key: VideoType.TRUE_HUMAN },
    { name: "素材混剪", key: VideoType.MATERIAL_MIX },
    { name: "新闻体", key: VideoType.NEWS },
    { name: "一句话生成", key: VideoType.SENTENCE },
    { name: "分镜混剪", key: VideoType.MONTAGE_STORYBOARD },
];

const typeNameMap: Record<number, string> = {
    [VideoType.DIGITAL_HUMAN]: "数字人口播",
    [VideoType.DIGITAL_HUMAN_SHANJIAN]: "数字人口播",
    [VideoType.ORAL_MIX]: "口播混剪",
    [VideoType.TRUE_HUMAN]: "真人口播",
    [VideoType.MATERIAL_MIX]: "素材混剪",
    [VideoType.NEWS]: "新闻体",
    [VideoType.SENTENCE]: "一句话生成",
    [VideoType.MONTAGE_STORYBOARD]: "分镜混剪",
};

const allTabList = [
    { name: "视频", key: "video" },
    { name: "图片", key: "image" },
] as const;

type AllTabKey = "video" | "image";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        type?: "video" | "image" | "all";
        limit?: number;
    }>(),
    {
        modelValue: false,
        type: "all",
        limit: 9,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "select", value: any[]): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const dataLists = ref<any[]>([]);
const pagingRef = ref<any>(null);
const chooseLists = ref<any[]>([]);
const currentType = ref<VideoType>(VideoType.ALL);
const currentAllTab = ref<AllTabKey>("video");
const showChoosePanel = ref(false);
const isLoading = ref(false);

const displayLists = computed(() => {
    if (props.type === "image") {
        return dataLists.value.filter((item: any) => item.draw_type != "6");
    }
    return dataLists.value;
});

const unifiedChooseGroups = computed(() => {
    const videoItems: any[] = [];
    const imageItems: any[] = [];
    for (const item of chooseLists.value) {
        const isVideo = item.media_type === "video" || (!item.media_type && props.type === "video");
        if (isVideo) videoItems.push(item);
        else imageItems.push(item);
    }
    const result: { name: string; key: "video" | "image"; items: any[] }[] = [];
    if (videoItems.length > 0) result.push({ name: "视频", key: "video", items: videoItems });
    if (imageItems.length > 0) result.push({ name: "图片", key: "image", items: imageItems });
    return result;
});

// 修正版 - 考虑 limit 约束
const isCurrentPageAllSelected = computed(() => {
    if (displayLists.value.length === 0) return false;
    const selectedInPage = displayLists.value.filter((item) => isChoose(item));
    const unselectedInPage = displayLists.value.filter((item) => !isChoose(item));
    if (unselectedInPage.length === 0) return true;
    const remaining = (props.limit || Infinity) - chooseLists.value.length;
    if (remaining <= 0) return true;

    return false;
});

const triggerReload = () => {
    isLoading.value = true;
    pagingRef.value?.reload();
};

const toggleChoosePanel = () => {
    if (chooseLists.value.length === 0) return;
    showChoosePanel.value = !showChoosePanel.value;
};

const clearAll = () => {
    chooseLists.value = [];
    showChoosePanel.value = false;
};

const handleType = (key: VideoType) => {
    if (currentType.value === key) return;
    currentType.value = key;
    triggerReload();
};

const handleAllTab = (key: AllTabKey) => {
    if (currentAllTab.value === key) return;
    currentAllTab.value = key;
    triggerReload();
};

const fetchVideoList = async (page_no: number, page_size: number, type: VideoType | "" = "") => {
    const params: Record<string, any> = { page_no, page_size };
    // 数字人口播需同时查蝉镜(1)与闪剪纯口播(9)
    if (type === VideoType.DIGITAL_HUMAN) {
        params.type = `${VideoType.DIGITAL_HUMAN},${VideoType.DIGITAL_HUMAN_SHANJIAN}`;
    } else if (type !== "" && type !== VideoType.ALL) {
        params.type = type;
    }
    const { lists } = await getVideoCreationRecord(params);
    return lists.filter((item: any) => getStatus(item) === 1).map((item: any) => ({ ...item, media_type: "video" }));
};

const fetchImageList = async (page_no: number, page_size: number) => {
    const { lists } = await drawingRecord({ page_no, page_size });
    return lists.filter((item: any) => item.draw_type != "6").map((item: any) => ({ ...item, media_type: "image" }));
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        let result: any[] = [];
        if (props.type === "image") {
            result = await fetchImageList(page_no, page_size);
        } else if (props.type === "all") {
            result =
                currentAllTab.value === "video"
                    ? await fetchVideoList(page_no, page_size, currentType.value)
                    : await fetchImageList(page_no, page_size);
        } else {
            const type = currentType.value === VideoType.ALL ? "" : currentType.value;
            result = await fetchVideoList(page_no, page_size, type);
        }
        pagingRef.value?.complete(result);
    } catch {
        pagingRef.value?.complete([]);
    } finally {
        isLoading.value = false;
    }
};

const getStatus = (item: any) => {
    const { type, status } = item || {};
    if (type === 1) {
        if (status === 0 || status === 1 || status === 2) return status;
        return 3;
    } else {
        if (status === 0) return 0;
        if (status === 3) return 1;
        if (status === 2) return 2;
        return 3;
    }
};

// 创作记录是多张表 UNION，id 跨表会重复，须用 媒体类型+type/draw_type+id 复合键判重
const recordKey = (item: any) => `${item.media_type || ""}_${item.type ?? item.draw_type ?? ""}_${item.id}`;

const isChoose = (data: any) => chooseLists.value.some((item) => recordKey(item) === recordKey(data));

const getChooseIndex = (data: any) => chooseLists.value.findIndex((item) => recordKey(item) === recordKey(data)) + 1;

const handleSelect = (data: any) => {
    if (isChoose(data)) {
        chooseLists.value = chooseLists.value.filter((item) => recordKey(item) !== recordKey(data));
        if (chooseLists.value.length === 0) showChoosePanel.value = false;
        return;
    }
    if (props.limit === 1) {
        chooseLists.value = [data];
        return;
    }
    if (chooseLists.value.length >= props.limit) {
        uni.$u.toast(`最多选择${props.limit}个素材`);
        return;
    }
    chooseLists.value.push(data);
};

const toggleSelect = () => {
    if (isCurrentPageAllSelected.value) {
        const currentKeys = new Set(displayLists.value.map((i) => recordKey(i)));
        chooseLists.value = chooseLists.value.filter((i) => !currentKeys.has(recordKey(i)));
        if (chooseLists.value.length === 0) showChoosePanel.value = false;
    } else {
        for (const item of displayLists.value) {
            if (chooseLists.value.length >= (props.limit || Infinity)) break;
            if (!isChoose(item)) chooseLists.value.push(item);
        }
    }
};

const confirm = () => {
    if (chooseLists.value.length === 0) {
        uni.$u.toast("至少选择一个素材");
        return;
    }
    show.value = false;
    const formatted = chooseLists.value.map((item) => ({
        id: item.id,
        name: item.media_type === "video" ? item.name : item.image.split("/").at(-1),
        type: item.media_type,
        url: item.media_type === "video" ? item.clip_result_url || item.video_result_url : item.image,
        pic: item.media_type === "video" ? item.pic : item.image,
        duration: item.duration || 0,
    }));
    emit("select", formatted);
    chooseLists.value = [];
};

watch(
    () => props.modelValue,
    async (newVal) => {
        if (newVal) {
            currentType.value = VideoType.ALL;
            currentAllTab.value = "video";
            chooseLists.value = [];
            showChoosePanel.value = false;
            await nextTick();
            triggerReload();
        }
    },
    { immediate: true },
);
</script>

<style scoped></style>
