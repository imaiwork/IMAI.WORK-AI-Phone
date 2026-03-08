<template>
    <popup
        ref="popupRef"
        :width="dialogWidth"
        top="6vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col" style="max-height: 82vh">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon :name="headerIcon" :size="18" />
                    </div>
                    <span class="text-gray-950 text-lg font-[1000] tracking-tight">{{ dialogTitle }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <div
                        v-if="chooseLists.length > 0"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-[#0065fb]/8 rounded-full">
                        <Icon name="el-icon-Check" color="var(--primary)" :size="11" />
                        <span class="text-[12px] font-black text-primary">
                            已选 {{ chooseLists.length }}
                            <template v-if="limit"> / {{ limit }}</template>
                        </span>
                    </div>
                    <div class="w-9 h-9" @click="close">
                        <close-btn />
                    </div>
                </div>
            </div>

            <div class="flex-1 min-h-0 overflow-hidden py-6">
                <div
                    v-if="!pager.loading && getDataLists.length === 0 && !pager.isLoad"
                    class="flex flex-col items-center justify-center py-20 gap-4">
                    <div
                        class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100">
                        <Icon :name="headerIcon" color="var(--slate-300)" :size="32" />
                    </div>
                    <span class="text-sm font-black text-slate-300 uppercase tracking-wider">
                        {{ mode === "history" ? "暂无创作历史" : "暂无可用素材" }}
                    </span>
                </div>

                <div v-else-if="pager.loading && pager.lists.length === 0" :class="['grid gap-3', gridClass]">
                    <div v-for="i in 9" :key="i" class="aspect-[3/4] rounded-2xl bg-slate-100 animate-pulse"></div>
                </div>

                <div
                    v-else-if="isAutoFilling && getDataLists.length === 0"
                    class="flex flex-col items-center justify-center py-20 gap-4">
                    <div class="w-10 h-10 rounded-full border-2 border-primary border-t-transparent animate-spin"></div>
                    <span class="text-sm font-black text-slate-300">加载中...</span>
                </div>

                <ElScrollbar v-else class="h-full" max-height="500px" :distance="20" @end-reached="loadMore">
                    <div class="px-6" :class="['grid gap-3 pb-4', gridClass]">
                        <div
                            v-for="(item, index) in getDataLists"
                            :key="item.id ?? index"
                            :class="[
                                'relative rounded-2xl overflow-hidden aspect-[3/4] cursor-pointer group border-2 group',
                                isChoose(item)
                                    ? 'border-primary shadow-[#0065fb]/15 scale-[0.97]'
                                    : 'border-[transparent] hover:border-slate-200',
                            ]"
                            @click="handleSelect(item)">
                            <ElImage :src="item.pic || item.content" fit="cover" lazy class="w-full h-full" />

                            <video
                                v-if="isVideo(item)"
                                :src="item.content"
                                class="w-full h-full object-cover absolute inset-0 -z-[1]"
                                :autoplay="false"
                                :controls="false"
                                preload="metadata" />

                            <div
                                v-if="isChoose(item)"
                                class="absolute inset-0 bg-[#0065fb]/20 flex items-start justify-end p-2">
                                <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center shadow-md">
                                    <Icon name="el-icon-Check" color="#fff" :size="12" />
                                </div>
                            </div>

                            <div
                                v-else
                                class="absolute top-2 right-2 w-6 h-6 rounded-full bg-[#000000]/20 border-2 border-white opacity-0 group-hover:opacity-100 transition-opacity" />

                            <div
                                v-if="isVideo(item)"
                                class="absolute inset-0 bg-[#000000]/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-10">
                                <div class="w-12 h-12" @click.stop="handlePreviewVideo(item)">
                                    <play-btn />
                                </div>
                            </div>
                            <div class="absolute bottom-2 left-2">
                                <div
                                    v-if="isVideo(item)"
                                    class="inline-flex items-center px-2 py-1 bg-[#000000]/40 backdrop-blur-sm rounded-md">
                                    <span
                                        class="text-[9px] text-white font-black uppercase tracking-wider leading-none">
                                        VIDEO
                                    </span>
                                </div>
                                <div
                                    v-if="isImage(item)"
                                    class="inline-flex items-center px-2 py-1 bg-[#000000]/40 backdrop-blur-sm rounded-md">
                                    <span
                                        class="text-[9px] text-white font-black uppercase tracking-wider leading-none">
                                        IMAGE
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="isAutoFilling" class="flex justify-center py-4">
                        <div class="flex items-center gap-2 text-slate-400">
                            <div
                                class="w-4 h-4 rounded-full border-2 border-slate-300 border-t-primary animate-spin"></div>
                            <span class="text-xs font-black">自动加载更多...</span>
                        </div>
                    </div>
                    <load-text :is-load="pager.isLoad && !isAutoFilling" />
                </ElScrollbar>
            </div>

            <div
                class="px-8 py-5 border-t border-slate-50 flex items-center justify-between shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.01)]">
                <div class="flex items-center gap-4">
                    <div
                        v-if="limit && limit > 1"
                        class="flex items-center gap-2 cursor-pointer group"
                        @click="toggleSelectAll">
                        <div
                            :class="[
                                'w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all',
                                isAllSelected
                                    ? 'bg-primary border-primary'
                                    : 'border-slate-200 group-hover:border-[#0065fb]/50',
                            ]">
                            <Icon v-if="isAllSelected" name="el-icon-Check" color="#fff" :size="11" />
                        </div>
                        <span
                            class="text-[13px] font-black text-slate-500 group-hover:text-slate-700 transition-colors">
                            全选
                        </span>
                    </div>
                    <span class="text-[12px] font-black text-slate-400">
                        已选 <span class="text-primary">{{ chooseLists.length }}</span>
                        <template v-if="limit"> / {{ limit }}</template> 个
                        {{ mode === "history" ? "视频" : "素材" }}
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        v-if="chooseLists.length > 0"
                        @click="chooseLists = []"
                        class="px-4 h-10 rounded-xl text-xs font-black text-slate-400 hover:text-red-400 hover:bg-red-50 transition-all">
                        清空
                    </button>
                    <button
                        @click="close"
                        class="px-6 h-11 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all active:scale-95">
                        取消
                    </button>
                    <button
                        @click="handleConfirm"
                        class="px-10 h-11 rounded-xl bg-primary text-white text-sm font-[1000] shadow-lg shadow-[#0065fb]/20 hover:bg-[#0056d6] hover:scale-[1.02] active:scale-95 transition-all">
                        确定选择
                    </button>
                </div>
            </div>
        </div>
    </popup>
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />
</template>

<script setup lang="ts">
import { getMaterialLibraryList } from "@/api/material";
import { getVideoCreationRecord } from "@/api/app";

const props = withDefaults(
    defineProps<{
        mode?: "material" | "history";
        type?: "video" | "image" | "all";
        historyType?: number;
        multiple?: boolean;
        limit?: number;
    }>(),
    {
        mode: "material",
        type: "all",
        multiple: true,
        limit: 9,
    }
);

const emit = defineEmits<{
    (e: "select", value: any[]): void;
    (e: "close"): void;
}>();

const showPreviewVideo = ref(false);
const previewVideoRef = shallowRef();

const isHistory = computed(() => props.mode === "history");

const dialogWidth = computed(() => {
    if (isHistory.value) return "780px";
    return props.type === "video" ? "780px" : "680px";
});

const dialogTitle = computed(() => {
    if (props.mode === "history") return "创作历史";
    if (props.type === "video") return "选择视频素材";
    if (props.type === "image") return "选择图片素材";
    return "选择素材";
});

const headerIcon = computed(() => {
    if (props.mode === "history") return "el-icon-Clock";
    if (props.type === "video") return "el-icon-VideoCamera";
    return "el-icon-Picture";
});

const gridClass = computed(() => {
    if (props.mode === "history") return "grid-cols-4";
    return props.type === "video" ? "grid-cols-3" : "grid-cols-4";
});

const popupRef = shallowRef();
const chooseLists = ref<any[]>([]);

const commonParams = reactive({ page_no: 1, page_size: 20 });

const materialParams = reactive({
    m_type: computed(() => (props.type === "all" ? undefined : props.type === "video" ? 2 : 1)),
});

const historyParams = reactive({
    type: computed(() => props.historyType ?? ""),
});

const { getLists, pager, resetPage } = usePaging({
    fetchFun: (params: any) => {
        return isHistory.value
            ? getVideoCreationRecord({ ...params, ...historyParams })
            : getMaterialLibraryList({ ...params, ...materialParams });
    },
    params: commonParams,
    isScroll: true,
});

// ---- 过滤后的列表 ----
const getDataLists = computed(() => {
    return isHistory.value ? pager.lists.filter((item: any) => item.status === 3) : pager.lists;
});

const MIN_VISIBLE_COUNT = 8;
const isAutoFilling = ref(false);

const loadMore = (e: string) => {
    if (e === "bottom" && pager.isLoad && !pager.loading && !isAutoFilling.value) {
        commonParams.page_no++;
        getLists();
    }
};
const isVideo = (item: any) => isHistory.value || item.m_type == 2;
const isImage = (item: any) => !isHistory.value && item.m_type == 1;

const isChoose = (item: any) => chooseLists.value.some((i) => i.id === item.id);

const handlePreviewVideo = async (item: any) => {
    const url = isHistory.value ? item.clip_result_url || item.video_result_url : item.content;
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value?.setUrl(url);
};

const isAllSelected = computed(() => {
    const cap = props.limit ?? getDataLists.value.length;
    return chooseLists.value.length > 0 && chooseLists.value.length === Math.min(getDataLists.value.length, cap);
});

const handleSelect = (item: any) => {
    const index = chooseLists.value.findIndex((i) => i.id === item.id);
    const isSingle = !props.multiple || props.limit === 1;

    if (isSingle) {
        chooseLists.value = index > -1 ? [] : [item];
        return;
    }
    if (isChoose(item)) {
        chooseLists.value.splice(index, 1);
    } else {
        if (props.limit && chooseLists.value.length >= props.limit) {
            feedback.msgWarning(`最多选择 ${props.limit} 个${isHistory.value ? "视频" : "素材"}`);
            return;
        }
        chooseLists.value.push(item);
    }
};

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        chooseLists.value = [];
    } else {
        chooseLists.value = getDataLists.value.slice(0, props.limit ?? getDataLists.value.length);
    }
};

const handleConfirm = () => {
    if (chooseLists.value.length === 0) {
        const label = isHistory.value ? "视频" : props.type === "video" ? "视频" : "图片";
        feedback.msgWarning(`请至少选择一个${label}`);
        return;
    }
    emit(
        "select",
        chooseLists.value.map((item: any) => ({
            ...item,
            url: isHistory.value ? item.clip_result_url || item.video_result_url : item.content,
            pic: item.m_type == 1 ? item.pic || item.content : item.pic,
        }))
    );
    close();
};

const open = () => {
    chooseLists.value = [];
    isAutoFilling.value = false;
    commonParams.page_no = 1;
    popupRef.value?.open();
    getLists();
};

const close = () => {
    isAutoFilling.value = false;
    emit("close");
};

watch(
    () => pager.loading,
    (loading) => {
        if (loading) return;
        if (!isHistory.value) return;
        if (!pager.isLoad) {
            isAutoFilling.value = false;
            return;
        }
        if (getDataLists.value.length >= MIN_VISIBLE_COUNT) {
            isAutoFilling.value = false;
            return;
        }
        isAutoFilling.value = true;
        commonParams.page_no++;
        getLists();
    }
);

defineExpose({ open, close });
</script>
