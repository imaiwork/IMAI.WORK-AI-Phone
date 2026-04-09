<template>
    <popup-bottom v-model="show" title="素材选择" :is-disabled-touch="true" height="80%" custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="flex items-center justify-between px-4 pt-2 pb-1">
                    <view class="flex bg-[#F1F5F9] rounded-[12rpx] p-[4rpx]">
                        <view
                            v-for="(item, index) in ['全部', '分组']"
                            :key="index"
                            class="px-[24rpx] py-[8rpx] rounded-[8rpx] text-[24rpx] font-medium transition-all"
                            :class="currShowType === index ? 'bg-white text-[#374151] shadow-sm' : 'text-[#6B7280]'"
                            @click="handleShowType(index)">
                            {{ item }}
                        </view>
                    </view>
                    <view class="text-xs text-[#00000080]"> 已选：{{ chooseLists.length }} </view>
                </view>

                <view
                    v-if="currShowType === ShowType.GROUP && currentGroupItem.id"
                    class="flex items-center gap-2 px-4 py-2 border-b border-[#F1F5F9]"
                    @click="backToGroupList">
                    <u-icon name="arrow-left" size="16" color="#6B7280"></u-icon>
                    <text class="text-[24rpx] text-[#6B7280]">返回分组</text>
                    <text class="text-[24rpx] text-[#374151] font-medium">/ {{ currentGroupItem.name }}</text>
                </view>

                <view class="grow min-h-0 mt-[10rpx]">
                    <z-paging
                        class="h-full"
                        ref="pagingRef"
                        v-model="dataLists"
                        :fixed="false"
                        :auto="false"
                        :default-page-size="20"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view
                            v-if="currShowType === ShowType.GROUP && !currentGroupItem.id"
                            class="px-4 flex flex-col gap-y-3">
                            <view
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="bg-white rounded-[16rpx] p-3 flex items-center shadow-sm border border-[#F1F5F9]"
                                @click="handleGroupItem(item)">
                                <view
                                    class="flex-shrink-0 flex items-center justify-center w-[100rpx] h-[100rpx] rounded-[12rpx]"
                                    style="background-image: linear-gradient(to bottom right, #f1f5f9, #e5e7eb)">
                                    <view class="text-[32rpx] opacity-60">📁</view>
                                </view>
                                <view class="flex-1 ml-3">
                                    <text class="text-[28rpx] font-medium text-[#1F2937] line-clamp-1">
                                        {{ item.name }}
                                    </text>
                                </view>
                                <u-icon name="arrow-right" size="16" color="#9CA3AF"></u-icon>
                            </view>
                        </view>

                        <view
                            v-else-if="
                                currShowType === ShowType.ALL ||
                                (currShowType === ShowType.GROUP && currentGroupItem.id)
                            "
                            class="grid gap-2 px-4"
                            :class="[props.type === 'video' ? 'grid-cols-2' : 'grid-cols-3']">
                            <view
                                class="rounded-xl relative overflow-hidden aspect-[3/4]"
                                v-for="(item, index) in dataLists"
                                :key="index"
                                @click="handleSelect(item)">
                                <template v-if="isImage(item)">
                                    <image
                                        :src="item.pic || item.content"
                                        class="w-full h-full rounded-xl"
                                        lazy-load
                                        mode="aspectFill" />
                                    <view
                                        class="absolute bottom-2 left-2 flex items-center gap-1 bg-[#00000066] rounded-full px-[10rpx] py-[4rpx]">
                                        <u-icon name="photo" size="12" color="#fff" />
                                        <text class="text-[20rpx] text-white">图片</text>
                                    </view>
                                </template>

                                <template v-else-if="isVideo(item)">
                                    <video
                                        :src="item.content"
                                        class="w-full h-full"
                                        object-fit="cover"
                                        :autoplay="false"
                                        :show-loading="false"
                                        :controls="false"
                                        :show-fullscreen-btn="false"
                                        :show-center-play-btn="false"
                                        :show-play-btn="false" />
                                    <image
                                        v-if="item.pic"
                                        :src="item.pic"
                                        class="absolute inset-0 w-full h-full rounded-xl"
                                        mode="aspectFill" />
                                    <view
                                        class="absolute bottom-2 left-2 flex items-center gap-1 bg-[#00000066] rounded-full px-[10rpx] py-[4rpx]">
                                        <u-icon name="play-right-fill" size="12" color="#fff" />
                                        <text class="text-[20rpx] text-white">视频</text>
                                    </view>
                                </template>

                                <view class="absolute top-0 left-0 w-full h-full bg-[#00000080]" v-if="isChoose(item)">
                                    <view class="absolute top-2 right-2">
                                        <image src="/static/images/icons/success.svg" class="w-[28rpx] h-[28rpx]" />
                                    </view>
                                </view>
                                <view class="absolute top-2 right-2 w-[28rpx] h-[28rpx] rounded-full bg-white" v-else />
                            </view>
                        </view>

                        <template #empty>
                            <view class="flex flex-col items-center justify-center py-[80rpx]">
                                <text class="text-[60rpx] mb-3">📂</text>
                                <text class="text-[26rpx] text-[#6B7280]">
                                    {{
                                        currShowType === ShowType.GROUP && !currentGroupItem.id
                                            ? "暂无分组"
                                            : "暂无素材"
                                    }}
                                </text>
                            </view>
                        </template>
                    </z-paging>
                </view>

                <view class="flex items-center justify-between gap-2 mt-[20rpx] mb-4 px-4">
                    <view
                        class="flex items-center gap-x-2"
                        @click="toggleSelect"
                        v-if="multiple && limit && limit > 1 && isShowMaterial">
                        <view class="w-[32rpx] h-[32rpx]">
                            <image
                                v-if="chooseLists.length > 0 && isAllSelected"
                                src="/static/images/icons/success.svg"
                                class="w-full h-full" />
                            <view class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]" v-else />
                        </view>
                        <view>全选</view>
                    </view>
                    <view
                        class="flex-1 text-white font-medium text-[30rpx] rounded-[20rpx] bg-primary h-[90rpx] flex items-center justify-center"
                        :class="[!props.multiple ? 'w-full' : 'w-[460rpx]']"
                        @click="confirm">
                        确定选择
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getMaterialLibraryList, getMaterialLibraryGroupList } from "@/api/material";

enum ShowType {
    ALL = 0,
    GROUP = 1,
}

const props = withDefaults(
    defineProps<{ modelValue: boolean; limit?: number; type: "video" | "image" | "all"; multiple?: boolean }>(),
    {
        multiple: true,
    }
);

const emit = defineEmits<{ (e: "update:modelValue", value: boolean): void; (e: "select", value: any[]): void }>();

const show = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit("update:modelValue", value);
    },
});

const currShowType = ref<ShowType>(ShowType.ALL);

const dataLists = ref<any[]>([]);
const pagingRef = ref<any>(null);
const chooseLists = ref<any[]>([]);

// 当前选中的分组
const currentGroupItem = reactive<any>({
    id: "",
    name: "",
});

const isImage = (item: any) => item.m_type === 1;

const isVideo = (item: any) => item.m_type === 2;

// 是否正在展示素材列表（全部模式 或 分组内素材）
const isShowMaterial = computed(() => {
    return currShowType.value === ShowType.ALL || (currShowType.value === ShowType.GROUP && !!currentGroupItem.id);
});

const isAllSelected = computed(() => {
    return chooseLists.value.length === dataLists.value.slice(0, props.limit || dataLists.value.length).length;
});

// 切换全部/分组
const handleShowType = (type: ShowType) => {
    if (currShowType.value === type) return;
    currShowType.value = type;
    currentGroupItem.id = "";
    currentGroupItem.name = "";
    pagingRef.value?.reload();
};

// 点击分组，进入分组内素材
const handleGroupItem = (item: any) => {
    currentGroupItem.id = item.id;
    currentGroupItem.name = item.name;
    pagingRef.value?.reload();
};

// 返回分组列表
const backToGroupList = () => {
    currentGroupItem.id = "";
    currentGroupItem.name = "";
    pagingRef.value?.reload();
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        if (currShowType.value === ShowType.GROUP && !currentGroupItem.id) {
            const { lists } = await getMaterialLibraryGroupList({ page_no, page_size });
            pagingRef.value?.complete(lists);
            return;
        }
        const { lists } = await getMaterialLibraryList({
            page_no,
            page_size,
            m_type: props.type === "all" ? "" : props.type === "video" ? 2 : 1,
            group_id: currentGroupItem.id || "",
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        console.error("查询失败:", error);
        pagingRef.value?.complete([]);
    }
};

const isChoose = (data: any) => {
    return chooseLists.value.some((item) => item.id === data.id);
};

const handleSelect = (data: any) => {
    const isSelected = isChoose(data);

    if (isSelected) {
        chooseLists.value = chooseLists.value.filter((item) => item.id !== data.id);
        return;
    }

    const isSingleMode = !props.multiple || props.limit === 1;

    if (isSingleMode) {
        chooseLists.value = [data];
    } else {
        if (props.limit && chooseLists.value.length >= props.limit) {
            uni.$u.toast(`最多选择${props.limit}个素材`);
            return;
        }
        chooseLists.value.push(data);
    }
};

const toggleSelect = () => {
    if (isAllSelected.value) {
        chooseLists.value = [];
    } else {
        chooseLists.value = dataLists.value.slice(0, props.limit || dataLists.value.length);
    }
};

const confirm = () => {
    if (chooseLists.value.length === 0) {
        uni.$u.toast(`至少选择一个${props.type === "video" ? "视频" : "图片"}`);
        return;
    }
    show.value = false;
    emit(
        "select",
        chooseLists.value.map((item: any) => ({
            ...item,
            url: item.content,
            pic: isImage(item) ? item.pic || item.content : item.pic,
        }))
    );
    chooseLists.value = [];
};

watch(
    () => props.modelValue,
    async (newVal) => {
        if (newVal) {
            currShowType.value = ShowType.ALL;
            currentGroupItem.id = "";
            currentGroupItem.name = "";
            await nextTick();
            pagingRef.value?.reload();
        }
    },
    {
        immediate: true,
        deep: true,
    }
);
</script>
