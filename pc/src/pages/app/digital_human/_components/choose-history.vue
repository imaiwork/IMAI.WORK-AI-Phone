<template>
    <popup
        ref="popupRef"
        width="780px"
        top="6vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col h-[65vh]">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-Clock" :size="18" />
                    </div>
                    <span class="text-gray-950 text-lg font-[1000] tracking-tight">创作历史</span>
                </div>
                <div class="flex items-center gap-3">
                    <div
                        v-if="totalChooseCount > 0"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full cursor-pointer transition-all"
                        :class="showChoosePanel ? 'bg-[#0065fb]/10' : 'bg-slate-100 hover:bg-slate-200'"
                        @click="toggleChoosePanel">
                        <Icon name="el-icon-Check" color="var(--color-primary)" :size="11" />
                        <span class="text-[12px] font-black text-primary">
                            已选 {{ totalChooseCount }}
                            <template v-if="limit"> / {{ limit }}</template>
                        </span>
                        <Icon
                            :name="showChoosePanel ? 'el-icon-ArrowUp' : 'el-icon-ArrowDown'"
                            :size="11"
                            color="var(--color-primary)" />
                    </div>

                    <div
                        v-if="isMultiple"
                        class="flex items-center gap-1 px-3 py-1.5 rounded-full"
                        :class="
                            limit && totalChooseCount >= limit
                                ? 'bg-red-50 text-red-400'
                                : 'bg-slate-100 text-slate-400'
                        ">
                        <Icon
                            :name="totalChooseCount >= limit ? 'el-icon-WarningFilled' : 'el-icon-InfoFilled'"
                            :size="13"
                            :color="totalChooseCount >= limit ? '#f87171' : '#94a3b8'" />
                        <span class="text-[11px] font-bold">最多 {{ limit }} 个</span>
                    </div>
                    <div class="w-9 h-9 cursor-pointer" @click="close">
                        <close-btn />
                    </div>
                </div>
            </div>

            <div
                class="px-6 flex items-center justify-between gap-x-4 border-b border-slate-50 shrink-0 bg-[#f8fafc]/50">
                <div class="shrink-0 py-3">
                    <div
                        v-if="props.type === 'all'"
                        class="flex items-center gap-1 p-1 bg-white rounded-xl border border-slate-100 shadow-sm">
                        <button
                            v-for="tab in allTabList"
                            :key="tab.key"
                            class="flex items-center gap-1.5 px-3 h-7 rounded-lg text-xs font-black transition-all"
                            :class="
                                currentAllTab === tab.key
                                    ? 'bg-primary text-white shadow-sm shadow-[#0065fb]/20'
                                    : 'text-slate-400 hover:text-slate-600 hover:bg-slate-50'
                            "
                            @click="handleAllTab(tab.key)">
                            <Icon :name="tab.icon" :size="12" />
                            {{ tab.name }}
                        </button>
                    </div>
                    <div v-else class="flex items-center gap-2">
                        <Icon
                            :name="props.type === 'video' ? 'el-icon-VideoCamera' : 'el-icon-Picture'"
                            color="#94a3b8"
                            :size="14" />
                        <span class="text-xs font-black text-slate-400">
                            {{ props.type === "video" ? "视频历史" : "图片历史" }}
                        </span>
                    </div>
                </div>

                <div v-if="showTypeFilter">
                    <ElScrollbar>
                        <div class="flex items-center gap-1.5 py-3">
                            <button
                                v-for="item in typeList"
                                :key="item.key"
                                class="shrink-0 px-3 h-7 rounded-lg text-xs font-black border transition-all"
                                :class="
                                    currentType === item.key
                                        ? 'bg-primary border-primary text-white shadow-sm shadow-[#0065fb]/20'
                                        : 'bg-white border-slate-200 text-slate-500 hover:border-primary hover:text-primary'
                                "
                                @click="handleType(item.key)">
                                {{ item.name }}
                            </button>
                        </div>
                    </ElScrollbar>
                </div>
            </div>

            <transition name="panel-slide">
                <div
                    v-if="showChoosePanel && chooseLists.length > 0"
                    class="mx-6 mt-3 mb-1 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden shrink-0">
                    <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-50">
                        <span class="text-xs font-black text-slate-700">已选（{{ chooseLists.length }}）</span>
                        <button
                            class="text-xs font-black text-red-400 hover:text-red-500 transition-colors"
                            @click.stop="clearAll">
                            清空
                        </button>
                    </div>
                    <div class="max-h-48 overflow-y-auto">
                        <div v-for="(group, gi) in unifiedChooseGroups" :key="gi">
                            <div
                                v-if="unifiedChooseGroups.length > 1 || props.type === 'all'"
                                class="flex items-center gap-2 px-4 py-2 bg-slate-50 border-b border-slate-50">
                                <div class="w-1 h-4 rounded-full bg-primary shrink-0" />
                                <span class="text-xs font-black text-slate-700">{{ group.name }}</span>
                                <span class="text-[11px] text-slate-400">（{{ group.items.length }}）</span>
                            </div>
                            <div class="flex gap-2 px-4 py-2 overflow-x-auto no-scrollbar">
                                <div
                                    v-for="item in group.items"
                                    :key="item.id"
                                    class="relative shrink-0 w-16 rounded-xl overflow-hidden aspect-[3/4]">
                                    <ElImage :src="item.image || item.pic" fit="cover" class="w-full h-full" lazy />
                                    <div
                                        v-if="group.key === 'video' && item.duration > 0"
                                        class="absolute top-1 left-1 px-1.5 py-0.5 rounded-md bg-[#000000]/50">
                                        <span class="text-[9px] text-white font-black">{{
                                            formatDuration(item.duration)
                                        }}</span>
                                    </div>
                                    <div
                                        class="absolute bottom-1 right-1 w-5 h-5 rounded-full bg-primary flex items-center justify-center">
                                        <span class="text-[9px] text-white font-black">{{ getChooseIndex(item) }}</span>
                                    </div>
                                    <button
                                        class="absolute top-1 right-1 w-5 h-5 rounded-full bg-[#000000]/50 flex items-center justify-center hover:bg-[#000000]/70 transition-colors"
                                        @click.stop="handleSelect(item)">
                                        <Icon name="el-icon-Close" color="#fff" :size="10" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <div class="flex-1 min-h-0 overflow-hidden py-4">
                <!-- 加载骨架屏：使用 pager.loading 替代 isLoading -->
                <div v-if="pager.loading && pager.lists.length === 0" :class="['grid gap-3 px-6', gridClass]">
                    <div v-for="i in 8" :key="i" class="aspect-[3/4] rounded-2xl bg-slate-100 animate-pulse" />
                </div>

                <div
                    v-else-if="!pager.loading && displayLists.length === 0"
                    class="flex flex-col items-center justify-center py-20 gap-4">
                    <div
                        class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100">
                        <Icon name="el-icon-Clock" color="#cbd5e1" :size="32" />
                    </div>
                    <span class="text-sm font-black text-slate-300 uppercase tracking-wider">暂无创作历史</span>
                </div>

                <ElScrollbar v-else class="h-full" max-height="500px" :distance="20" @end-reached="loadMore">
                    <div :class="['grid gap-3 px-6 pb-4', gridClass]">
                        <div
                            v-for="(item, index) in displayLists"
                            :key="item.id ?? index"
                            :class="[
                                'relative aspect-[3/4] rounded-2xl overflow-hidden cursor-pointer group border-2 transition-all',
                                isChoose(item)
                                    ? 'border-primary shadow-[#0065fb]/15 scale-[0.97]'
                                    : 'border-[transparent] hover:border-slate-200',
                            ]"
                            @click="handleSelect(item)">
                            <ElImage :src="item.image || item.pic" fit="cover" lazy class="w-full h-full" />

                            <div
                                v-if="
                                    (props.type === 'video' || (props.type === 'all' && currentAllTab === 'video')) &&
                                    item.duration > 0
                                "
                                class="absolute top-2 left-2 z-10">
                                <div
                                    class="inline-flex items-center gap-1 px-2 py-1 bg-[#000000]/40 backdrop-blur-sm rounded-md">
                                    <Icon name="el-icon-VideoPlay" color="#fff" :size="9" />
                                    <span class="text-[9px] text-white font-black tracking-wider leading-none">
                                        {{ formatDuration(item.duration) }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="isChoose(item)"
                                class="absolute inset-0 bg-[#0065fb]/20 flex items-start justify-end p-2 z-10">
                                <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center shadow-md">
                                    <Icon name="el-icon-Check" color="#fff" :size="12" />
                                </div>
                                <div
                                    v-if="props.type === 'video' || (props.type === 'all' && currentAllTab === 'video')"
                                    class="absolute bottom-2 right-2 w-6 h-6 rounded-full bg-primary flex items-center justify-center shadow-md">
                                    <span class="text-[10px] text-white font-black">{{ getChooseIndex(item) }}</span>
                                </div>
                            </div>
                            <div
                                v-else
                                class="absolute top-2 right-2 w-6 h-6 rounded-full bg-[#000000]/20 border-2 border-white opacity-0 group-hover:opacity-100 transition-opacity z-10" />

                            <div class="absolute bottom-2 left-2 z-10">
                                <div
                                    class="inline-flex items-center px-2 py-1 bg-[#000000]/40 backdrop-blur-sm rounded-md">
                                    <span
                                        class="text-[9px] text-white font-black uppercase tracking-wider leading-none"
                                        >{{
                                            props.type === "video" ||
                                            (props.type === "all" && currentAllTab === "video")
                                                ? "视频"
                                                : "图片"
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <load-text :is-load="pager.isLoad" />
                </ElScrollbar>
            </div>

            <div
                class="px-8 py-5 border-t border-slate-50 flex items-center justify-between shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.01)]">
                <div class="flex items-center gap-4">
                    <div
                        v-if="limit && limit > 1"
                        class="flex items-center gap-2 cursor-pointer group"
                        @click="toggleSelect">
                        <div
                            :class="[
                                'w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all',
                                isCurrentPageAllSelected
                                    ? 'bg-primary border-primary'
                                    : 'border-slate-200 group-hover:border-[#0065fb]/50',
                            ]">
                            <Icon v-if="isCurrentPageAllSelected" name="el-icon-Check" color="#fff" :size="11" />
                        </div>
                        <span
                            class="text-[13px] font-black text-slate-500 group-hover:text-slate-700 transition-colors">
                            全选
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        v-if="chooseLists.length > 0"
                        class="px-4 h-10 rounded-xl text-xs font-black text-slate-400 hover:text-red-400 hover:bg-red-50 transition-all"
                        @click="clearAll">
                        清空
                    </button>
                    <button
                        class="px-6 h-11 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all active:scale-95"
                        @click="close">
                        取消
                    </button>
                    <button
                        class="px-10 h-11 rounded-xl text-white text-sm font-[1000] shadow-lg transition-all active:scale-95"
                        :class="
                            chooseLists.length > 0
                                ? 'bg-primary shadow-[#0065fb]/20 hover:bg-[#0056d6] hover:scale-[1.02]'
                                : 'bg-slate-300 cursor-not-allowed shadow-none'
                        "
                        @click="confirm">
                        确定选择
                        <span v-if="chooseLists.length > 0" class="text-xs opacity-80 ml-1">
                            ({{ chooseLists.length }}/{{ limit }})
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getVideoCreationRecord } from "@/api/app";
import { drawingRecord } from "@/api/drawing";

// ── 枚举 & 常量 ────────────────────────────────────────────────────────────
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

type AllTabKey = "video" | "image";

const allTabList = [
    { name: "视频", key: "video" as AllTabKey, icon: "el-icon-VideoCamera" },
    { name: "图片", key: "image" as AllTabKey, icon: "el-icon-Picture" },
];

// ── Props / Emits ──────────────────────────────────────────────────────────
const props = withDefaults(
    defineProps<{
        type?: "video" | "image" | "all";
        limit?: number;
    }>(),
    {
        type: "all",
        limit: 9,
    },
);

const emit = defineEmits<{
    (e: "select", value: any[]): void;
    (e: "close"): void;
}>();

// ── 状态 ───────────────────────────────────────────────────────────────────
const popupRef = shallowRef();
const chooseLists = ref<any[]>([]);
const currentType = ref<VideoType>(VideoType.ALL);
const currentAllTab = ref<AllTabKey>("video");
const showChoosePanel = ref(false);
// ✅ 移除独立的 isLoading，统一使用 pager.loading

// ── 计算：是否显示视频类型筛选 ────────────────────────────────────────────
const showTypeFilter = computed(
    () => props.type === "video" || (props.type === "all" && currentAllTab.value === "video"),
);

const gridClass = computed(() => {
    return "grid-cols-4";
});

// ── 分页 ───────────────────────────────────────────────────────────────────
const commonParams = reactive({ page_no: 1, page_size: 100 });

const { getLists, pager } = usePaging({
    fetchFun: async (params: any) => {
        if (props.type === "image") {
            return fetchImageList(params.page_no, params.page_size);
        }
        if (props.type === "all") {
            return currentAllTab.value === "video"
                ? fetchVideoList(params.page_no, currentType.value)
                : fetchImageList(params.page_no, params.page_size);
        }
        // video
        const t = currentType.value === VideoType.ALL ? "" : currentType.value;
        return fetchVideoList(params.page_no, t as any);
    },
    params: commonParams,
    isScroll: true,
});

// ── 数据获取 ───────────────────────────────────────────────────────────────
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

const fetchVideoList = async (page_no: number, type: VideoType | "" = "") => {
    const params: Record<string, any> = { page_no, page_size: 100 };
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

// ── 展示列表 ───────────────────────────────────────────────────────────────
const displayLists = computed(() => {
    return pager.lists;
});

// ── 已选分组（统一按媒体类型分组）────────────────────────────────────────
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

const isMultiple = computed(() => props.limit && props.limit > 1);
const totalChooseCount = computed(() => chooseLists.value.length);

// ── 全选逻辑 ───────────────────────────────────────────────────────────────
const isCurrentPageAllSelected = computed(() => {
    if (displayLists.value.length === 0) return false;
    const unselected = displayLists.value.filter((item) => !isChoose(item));
    if (unselected.length === 0) return true;
    const remaining = (props.limit ?? Infinity) - chooseLists.value.length;
    return remaining <= 0;
});

const toggleSelect = () => {
    if (isCurrentPageAllSelected.value) {
        const currentIds = new Set(displayLists.value.map((i: any) => i.id));
        chooseLists.value = chooseLists.value.filter((i) => !currentIds.has(i.id));
        if (chooseLists.value.length === 0) showChoosePanel.value = false;
    } else {
        for (const item of displayLists.value) {
            if (chooseLists.value.length >= (props.limit ?? Infinity)) break;
            if (!isChoose(item)) chooseLists.value.push(item);
        }
    }
};

// ── 选择逻辑 ───────────────────────────────────────────────────────────────
const isChoose = (data: any) => chooseLists.value.some((item) => item.id === data.id);
const getChooseIndex = (data: any) => chooseLists.value.findIndex((item) => item.id === data.id) + 1;

const handleSelect = (data: any) => {
    if (isChoose(data)) {
        chooseLists.value = chooseLists.value.filter((item) => item.id !== data.id);
        if (chooseLists.value.length === 0) showChoosePanel.value = false;
        return;
    }
    if (props.limit === 1) {
        chooseLists.value = [data];
        return;
    }
    if (chooseLists.value.length >= (props.limit ?? Infinity)) {
        feedback.msgWarning(`最多选择 ${props.limit} 个素材`);
        return;
    }
    chooseLists.value.push(data);
};

// ── Tab / 类型切换 ─────────────────────────────────────────────────────────
const triggerReload = () => {
    commonParams.page_no = 1;
    pager.lists = [];
    getLists();
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

// ── 加载更多 ───────────────────────────────────────────────────────────────
const loadMore = (e: string) => {
    if (e === "bottom" && pager.isLoad && !pager.loading) {
        commonParams.page_no++;
        getLists();
    }
};

// ── 已选面板 ───────────────────────────────────────────────────────────────
const toggleChoosePanel = () => {
    if (chooseLists.value.length === 0) return;
    showChoosePanel.value = !showChoosePanel.value;
};

const clearAll = () => {
    chooseLists.value = [];
    showChoosePanel.value = false;
};

// ── 工具函数 ───────────────────────────────────────────────────────────────
const formatDuration = (seconds: number): string => {
    if (!seconds || seconds <= 0) return "";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    if (m === 0) return `${s}s`;
    return `${m}分${s > 0 ? s + "秒" : ""}`;
};

// ── 确认 ───────────────────────────────────────────────────────────────────
const confirm = () => {
    if (chooseLists.value.length === 0) {
        feedback.msgWarning("至少选择一个素材");
        return;
    }
    const formatted = chooseLists.value.map((item) => ({
        id: item.id,
        name: item.media_type === "video" ? item.name : (item.image ?? "").split("/").at(-1),
        type: item.media_type,
        url: item.media_type === "video" ? item.clip_result_url || item.video_result_url : item.image,
        pic: item.media_type === "video" ? item.pic : item.image,
        duration: item.duration || 0,
    }));
    emit("select", formatted);
    close();
};

// ── open / close ───────────────────────────────────────────────────────────
const open = () => {
    chooseLists.value = [];
    currentType.value = VideoType.ALL;
    currentAllTab.value = "video";
    showChoosePanel.value = false;
    commonParams.page_no = 1;
    pager.lists = [];
    popupRef.value?.open();
    getLists();
};

const close = () => {
    emit("close");
};

defineExpose({ open, close });
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.2s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(6px);
}

.panel-slide-enter-active,
.panel-slide-leave-active {
    transition: all 0.25s ease;
    overflow: hidden;
}
.panel-slide-enter-from,
.panel-slide-leave-to {
    opacity: 0;
    max-height: 0;
}
.panel-slide-enter-to,
.panel-slide-leave-from {
    opacity: 1;
    max-height: 300px;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
