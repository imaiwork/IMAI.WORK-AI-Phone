<template>
    <div class="w-full h-full bg-white rounded-[20px] border border-br flex flex-col overflow-hidden">
        <div class="px-6 py-4 bg-[#f8fafc]/80 border-b border-slate-50">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div
                        class="w-1.5 h-6 rounded-full bg-orange-400 shadow-[0_0_12px_rgba(251,146,60,0.4)] shrink-0"></div>
                    <h3 class="text-[16px] font-black text-slate-700 whitespace-nowrap">参考素材</h3>
                    <div class="flex items-center bg-orange-50 px-3 py-1 rounded-full whitespace-nowrap shrink-0">
                        <span class="text-orange-500 text-[11px] font-medium tracking-wider">
                            {{ groups.length }} 个分组 · {{ totalMaterialCount }} 个素材
                        </span>
                    </div>
                </div>
                <button
                    @click="handleAddGroup"
                    class="h-9 px-4 bg-primary text-white rounded-xl text-xs font-medium flex items-center gap-2 shadow-light shadow-[#0065fb]/20 hover:scale-105 transition-all whitespace-nowrap shrink-0">
                    <Icon name="el-icon-Plus" :size="14" />
                    新建分组
                </button>
            </div>
            <div v-if="!isStoryboard" class="flex items-center justify-between gap-3 mt-3">
                <div class="text-[11px] text-slate-400 font-medium whitespace-nowrap shrink-0">
                    总时长 ≤ {{ montageUploadConfig.materialTotalDuration }} 分钟（图片按
                    {{ montageUploadConfig.imageDuration }} 秒/张）
                </div>
                <div class="flex-1 min-w-0 flex justify-end">
                    <material-duration-bar
                        v-if="totalMaterialCount > 0"
                        :used="calcTotalDuration()"
                        :max="montageUploadConfig.materialTotalDuration * 60" />
                </div>
            </div>
        </div>

        <div class="flex-1 min-h-0">
            <ElScrollbar v-if="groups.length > 0" ref="groupScrollbarRef">
                <div class="p-4 space-y-3">
                    <div
                        v-for="(group, groupIndex) in groups"
                        :key="group.id"
                        :data-group-id="group.id"
                        class="rounded-[20px] border overflow-hidden transition-all duration-300"
                        :class="[
                            expandedGroups[group.id] ? 'shadow-sm' : '',
                            errorGroupId === group.id
                                ? 'border-red-300 shadow-[0_0_0_3px_rgba(248,113,113,0.15)]'
                                : 'border-slate-100 bg-[#f8fafc]/50',
                        ]">
                        <div
                            class="px-5 py-3 flex items-center gap-3 cursor-pointer hover:bg-[#f8fafc] transition-colors group"
                            :class="errorGroupId === group.id ? 'bg-red-50/60' : ''"
                            @click="toggleGroup(group.id)">
                            <div
                                class="w-7 h-7 rounded-lg bg-white border border-slate-100 flex items-center justify-center transition-transform duration-300"
                                :class="{ 'rotate-90': expandedGroups[group.id] }">
                                <Icon name="el-icon-ArrowRight" color="var(--slate-400)" :size="14" />
                            </div>
                            <div class="flex-1 flex items-center gap-2 min-w-0">
                                <span class="text-[14px] font-black text-slate-700 truncate max-w-[200px]">
                                    {{ group.name }}
                                </span>
                                <span
                                    :class="[
                                        'text-[10px] font-medium px-2 py-0.5 rounded-md border transition-colors',
                                        errorGroupId === group.id && group.materialList.length === 0
                                            ? 'text-red-400 bg-red-50 border-red-200'
                                            : 'text-slate-400 bg-white border-slate-100',
                                    ]">
                                    {{ group.materialList.length }} 项
                                    <span
                                        v-if="errorGroupId === group.id && group.materialList.length === 0"
                                        class="ml-0.5"
                                        >· 请添加素材</span
                                    >
                                </span>
                                <span
                                    v-if="!isStoryboard && group.materialList.length > 0"
                                    class="text-[10px] font-medium text-orange-500 bg-orange-50 px-2 py-0.5 rounded-md">
                                    {{ formatDuration(calcGroupDuration(group)) }}
                                </span>
                                <div v-if="isStoryboard" class="flex items-center gap-1.5 ml-1" @click.stop>
                                    <span class="text-[10px] font-medium text-slate-400 whitespace-nowrap"
                                        >素材原声</span
                                    >
                                    <button
                                        class="relative inline-flex items-center rounded-full transition-colors duration-200 focus:outline-none shrink-0"
                                        :class="group.useOriginalAudio ? 'bg-primary w-8 h-4' : 'bg-slate-200 w-8 h-4'"
                                        @click="toggleOriginalAudio(group.id)">
                                        <span
                                            class="inline-block rounded-full bg-white shadow transition-transform duration-200"
                                            :class="
                                                group.useOriginalAudio
                                                    ? 'translate-x-4 w-3 h-3'
                                                    : 'translate-x-0.5 w-3 h-3'
                                            ">
                                        </span>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <ElPopover
                                    :ref="(el) => setGroupPopoverRef(el, group.id)"
                                    trigger="click"
                                    :width="260"
                                    popper-class="!p-2 !rounded-[20px] border-[rgba(0,101,251,0.1)] shadow-[0_10px_40px_-10px_rgba(0,101,251,0.2)]">
                                    <template #reference>
                                        <button
                                            class="h-8 px-3 rounded-lg bg-white border border-slate-100 text-primary text-[12px] font-medium hover:border-[#0065fb]/30 transition-all flex items-center gap-1"
                                            @click.stop>
                                            <Icon name="el-icon-Plus" :size="12" />
                                            添加素材
                                        </button>
                                    </template>
                                    <material-menu-content
                                        @action="handleMaterialAction($event as MaterialAction, group.id)" />
                                </ElPopover>
                                <button
                                    class="w-8 h-8 rounded-lg bg-white border border-slate-100 hover:border-red-200 hover:bg-red-50 text-slate-400 hover:text-red-500 flex items-center justify-center transition-all"
                                    @click.stop="handleRemoveGroup(groupIndex)">
                                    <Icon name="el-icon-Delete" :size="14" />
                                </button>
                            </div>
                        </div>

                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 max-h-0"
                            enter-to-class="opacity-100 max-h-[2000px]"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 max-h-[2000px]"
                            leave-to-class="opacity-0 max-h-0">
                            <div v-show="expandedGroups[group.id]" class="overflow-hidden">
                                <div class="px-5 pb-5 pt-1 border-t border-[#f1f5f9]/80">
                                    <div
                                        v-if="group.materialList.length > 0"
                                        class="grid grid-cols-4 xl:grid-cols-5 gap-3 mt-3">
                                        <div
                                            v-for="(item, index) in group.materialList"
                                            :key="index"
                                            class="aspect-square shrink-0 rounded-[20px] relative group overflow-hidden border border-slate-100 transition-transform hover:scale-105">
                                            <img :src="item.pic" class="w-full h-full object-cover" />
                                            <div
                                                class="absolute inset-0 bg-[#000000]/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-10">
                                                <div
                                                    class="w-8 h-8 flex items-center justify-center cursor-pointer"
                                                    @click.stop="previewMaterial(item)">
                                                    <play-btn :icon-size="24" v-if="item.type === 'video'" />
                                                    <Icon
                                                        name="el-icon-View"
                                                        color="var(--color-white)"
                                                        v-if="item.type === 'image'" />
                                                </div>
                                            </div>
                                            <div
                                                class="absolute inset-0 bg-[#000000]/20 group-hover:bg-[#000000]/40 transition-colors"></div>
                                            <button
                                                @click.stop="handleDeleteMaterial(group.id, index)"
                                                class="z-[777] absolute top-2 right-2 w-7 h-7 rounded-xl bg-[#ef4444]/90 backdrop-blur text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600">
                                                <Icon name="el-icon-Close" :size="12" />
                                            </button>
                                            <div
                                                class="absolute bottom-2 left-2 px-2 py-1 bg-[#ffffff]/20 backdrop-blur-md rounded-lg text-[9px] text-white font-black border border-[#ffffff]/20">
                                                {{ item.type === "image" ? "图片" : "视频" }}
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="flex flex-col items-center justify-center py-8 gap-3">
                                        <div
                                            class="w-14 h-14 rounded-2xl bg-white border border-slate-100 flex items-center justify-center">
                                            <Icon name="el-icon-Files" color="var(--slate-300)" :size="24" />
                                        </div>
                                        <div class="text-[12px] font-medium text-slate-400">
                                            该分组暂无素材，点击右上角「添加素材」
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </ElScrollbar>

            <div v-else class="flex flex-col justify-center items-center h-full py-12">
                <div class="relative mb-6">
                    <div class="absolute inset-0 bg-[#0065fb]/10 blur-[60px] rounded-full animate-pulse"></div>
                    <div
                        class="relative w-24 h-24 bg-slate-50 rounded-[32px] flex items-center justify-center border border-slate-100">
                        <Icon name="el-icon-Folder" color="var(--slate-300)" :size="40" />
                    </div>
                </div>
                <div class="text-[15px] font-[1000] text-slate-400 mb-8 tracking-wider uppercase">还没有任何分组</div>
                <button @click="handleAddGroup" class="add-material-btn group">
                    <div
                        class="absolute inset-0 bg-primary opacity-0 group-hover:opacity-100 transition-opacity duration-500 blur-xl"></div>
                    <div
                        class="relative flex items-center gap-3 px-8 py-4 bg-primary rounded-[22px] shadow-lg shadow-[#0065fb]/30 group-hover:scale-105 group-hover:shadow-[#0065fb]/50 transition-all duration-300 active:scale-95">
                        <div
                            class="w-6 h-6 rounded-lg bg-[#ffffff]/20 flex items-center justify-center group-hover:rotate-90 transition-transform duration-500">
                            <Icon name="el-icon-Plus" color="#ffffff" :size="18" />
                        </div>
                        <span class="text-white font-[1000] text-base tracking-wide mr-1">创建第一个分组</span>
                    </div>
                    <div
                        class="absolute -inset-1 border-2 border-[#0065fb]/30 rounded-[24px] animate-ping opacity-20 group-hover:hidden"></div>
                </button>
            </div>
        </div>
    </div>

    <choose-history
        v-if="showChooseHistory"
        ref="chooseHistoryRef"
        :type="chooseHistoryType"
        :multiple="true"
        :limit="9"
        @select="handleSelectHistory"
        @close="showChooseHistory = false" />
    <choose-material
        v-if="showChooseMaterial"
        ref="chooseMaterialRef"
        :type="chooseMaterialType"
        :limit="9"
        @select="handleSelectMaterial"
        @close="showChooseMaterial = false" />
    <preview-video v-if="showVideoPreview" ref="videoPreviewPlayerRef" @close="showVideoPreview = false" />
    <ElImageViewer v-if="showImagePreview" :url-list="[imagePreviewUrl]" @close="showImagePreview = false" />
</template>

<script setup lang="ts">
import { montageUploadConfig } from "@/pages/app/digital_human/_config";
import { useMaterial } from "@/pages/app/digital_human/_hooks/useMaterial";
import { getValidUploadFileData } from "@/pages/app/digital_human/_hooks/useUpload";
import ChooseHistory from "@/pages/app/digital_human/_components/choose-history.vue";
import ChooseMaterial from "@/pages/app/digital_human/_components/choose-material.vue";
import MaterialMenuContent from "@/pages/app/digital_human/_components/material-menu-content.vue";
import MaterialDurationBar from "@/pages/app/digital_human/_components/material-duration-bar.vue";

export interface MaterialItem {
    url: string;
    type: string;
    pic: string;
    duration: number;
}
export interface MaterialGroup {
    id: string;
    name: string;
    materialList: MaterialItem[];
    useOriginalAudio?: boolean;
}
type MaterialAction =
    | { type: "upload-image" | "upload-video"; event: any }
    | { type: "library-image" | "library-video" | "history" };

const props = defineProps<{
    modelValue: MaterialGroup[];
    isStoryboard?: boolean;
}>();
const emit = defineEmits<{
    (e: "update:modelValue", val: MaterialGroup[]): void;
}>();

const groups = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});

// ──────────────────────────────────────────────────────────────
// 滚动容器 ref
// ──────────────────────────────────────────────────────────────
const groupScrollbarRef = shallowRef();

// ──────────────────────────────────────────────────────────────
// 错误高亮状态（由 scrollToGroup 驱动）
// ──────────────────────────────────────────────────────────────
const errorGroupId = ref<string | null>(null);
let errorClearTimer: ReturnType<typeof setTimeout> | null = null;

const setErrorGroup = (groupId: string) => {
    if (errorClearTimer) clearTimeout(errorClearTimer);
    errorGroupId.value = groupId;
    // 3 秒后自动清除高亮
    errorClearTimer = setTimeout(() => {
        errorGroupId.value = null;
    }, 3000);
};

// 素材添加后清除对应分组的错误状态
watch(
    () => groups.value.map((g) => g.materialList.length),
    () => {
        if (!errorGroupId.value) return;
        const group = groups.value.find((g) => g.id === errorGroupId.value);
        if (group && group.materialList.length > 0) {
            errorGroupId.value = null;
            if (errorClearTimer) clearTimeout(errorClearTimer);
        }
    },
);

// ──────────────────────────────────────────────────────────────
// 分组逻辑
// ──────────────────────────────────────────────────────────────
const expandedGroups = reactive<Record<string, boolean>>({});
const groupPopoverRefs = new Map<string, any>();

const setGroupPopoverRef = (el: any, id: string) => {
    if (el) groupPopoverRefs.set(id, el);
    else groupPopoverRefs.delete(id);
};

const genGroupId = () => `g_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;

const handleAddGroup = () => {
    const newGroup: MaterialGroup = {
        id: genGroupId(),
        name: `分组 ${groups.value.length + 1}`,
        materialList: [],
        ...(props.isStoryboard ? { useOriginalAudio: true } : {}),
    };
    emit("update:modelValue", [...groups.value, newGroup]);
    expandedGroups[newGroup.id] = true;
};

const handleRemoveGroup = (index: number) => {
    const group = groups.value[index];
    const doRemove = () => {
        delete expandedGroups[group.id];
        if (errorGroupId.value === group.id) errorGroupId.value = null;
        const next = [...groups.value];
        next.splice(index, 1);
        emit("update:modelValue", next);
    };
    if (group.materialList.length === 0) {
        doRemove();
        return;
    }
    useNuxtApp().$confirm({
        title: "删除分组",
        message: `「${group.name}」内有 ${group.materialList.length} 个素材，确认删除？`,
        confirmButtonText: "删除",
        cancelButtonText: "取消",
        onConfirm: doRemove,
    });
};

const toggleGroup = (id: string) => {
    expandedGroups[id] = !expandedGroups[id];
};

const toggleOriginalAudio = (groupId: string) => {
    const next = groups.value.map((g) => (g.id === groupId ? { ...g, useOriginalAudio: !g.useOriginalAudio } : g));
    emit("update:modelValue", next);
};

// ──────────────────────────────────────────────────────────────
// 时长计算
// ──────────────────────────────────────────────────────────────
const totalMaterialCount = computed(() => groups.value.reduce((acc, g) => acc + g.materialList.length, 0));

const calcGroupDuration = (group: MaterialGroup) => {
    const videoDur = group.materialList.reduce(
        (acc, item) => (item.type === "video" ? acc + (item.duration ?? 0) : acc),
        0,
    );
    const imageDur =
        group.materialList.filter((item) => item.type === "image").length * montageUploadConfig.imageDuration;
    return videoDur + imageDur;
};
const calcTotalDuration = () => groups.value.reduce((acc, g) => acc + calcGroupDuration(g), 0);
const formatDuration = (sec: number) => {
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    return m > 0 ? `${m}分${s}秒` : `${s}秒`;
};

// ──────────────────────────────────────────────────────────────
// 素材删除
// ──────────────────────────────────────────────────────────────
const handleDeleteMaterial = (groupId: string, index: number) => {
    const next = groups.value.map((g) => {
        if (g.id !== groupId) return g;
        const list = [...g.materialList];
        list.splice(index, 1);
        return { ...g, materialList: list };
    });
    emit("update:modelValue", next);
};

// ──────────────────────────────────────────────────────────────
// 素材弹窗
// ──────────────────────────────────────────────────────────────
const showChooseHistory = ref(false);
const chooseHistoryRef = shallowRef<InstanceType<typeof ChooseHistory>>();
const chooseHistoryType = ref<"image" | "video" | "all">("all");
const showChooseMaterial = ref(false);
const chooseMaterialRef = shallowRef<InstanceType<typeof ChooseMaterial>>();
const chooseMaterialType = ref<"image" | "video">("image");
const activeGroupId = ref<string | null>(null);

// ──────────────────────────────────────────────────────────────
// 预览
// ──────────────────────────────────────────────────────────────
const showVideoPreview = ref(false);
const videoPreviewPlayerRef = shallowRef();
const showImagePreview = ref(false);
const imagePreviewUrl = ref("");

const handleVideoPlay = async (url: string) => {
    showVideoPreview.value = true;
    await nextTick();
    videoPreviewPlayerRef.value?.open();
    videoPreviewPlayerRef.value?.setUrl(url);
};
const previewMaterial = (item: MaterialItem) => {
    if (item.type === "video") handleVideoPlay(item.url);
    else {
        showImagePreview.value = true;
        imagePreviewUrl.value = item.url;
    }
};

// ──────────────────────────────────────────────────────────────
// 素材添加动作
// ──────────────────────────────────────────────────────────────
const handleMaterialAction = async (action: MaterialAction, groupId: string) => {
    activeGroupId.value = groupId;
    groupPopoverRefs.get(groupId)?.hide?.();
    if (action.type === "library-image" || action.type === "library-video") {
        chooseMaterialType.value = action.type === "library-image" ? "image" : "video";
        showChooseMaterial.value = true;
        await nextTick();
        chooseMaterialRef.value?.open();
    } else if (action.type === "history") {
        showChooseHistory.value = true;
        await nextTick();
        chooseHistoryRef.value?.open();
    } else if (action.type === "upload-image" || action.type === "upload-video") {
        chooseMaterialType.value = action.type === "upload-image" ? "image" : "video";
        const data = getValidUploadFileData(action.event);
        if (!data) return;
        let pic = "";
        let duration = 0;
        if (chooseMaterialType.value === "image") {
            pic = data.uri;
            duration = montageUploadConfig.imageDuration;
        } else {
            pic = data.thumbnail_path;
            duration = data.duration;
        }
        appendMaterials([{ url: data.uri, type: chooseMaterialType.value, pic, duration }], true);
    }
};
const handleSelectMaterial = (list: any[]) => {
    appendMaterials(list.map((item) => ({ url: item.url, type: item.type, pic: item.pic, duration: item.duration })));
};
const handleSelectHistory = (list: any[]) => {
    appendMaterials(list.map((item) => ({ url: item.url, type: item.type, pic: item.pic, duration: item.duration })));
};

const appendMaterials = async (list: MaterialItem[], skipProcess = false) => {
    const targetGroup = groups.value.find((g) => g.id === activeGroupId.value);
    if (!targetGroup) {
        feedback.msgWarning("未找到目标分组");
        return;
    }
    let filteredList = list;
    if (!props.isStoryboard) {
        const maxSeconds = montageUploadConfig.materialTotalDuration * 60;
        let skippedCount = 0;
        let currentDuration = calcTotalDuration();
        filteredList = list.filter((item) => {
            const itemDuration = item.type === "image" ? montageUploadConfig.imageDuration : item.duration ?? 0;
            if (currentDuration + itemDuration > maxSeconds) {
                skippedCount++;
                return false;
            }
            currentDuration += itemDuration;
            return true;
        });
        if (skippedCount > 0) feedback.msgWarning(`${skippedCount} 个素材超出总时长限制已过滤`);
    }
    if (filteredList.length === 0) return;
    if (skipProcess) {
        targetGroup.materialList.push(...filteredList);
        return;
    }
    const tempList = ref([...targetGroup.materialList]);
    const { processAndAppend } = useMaterial(tempList);
    await processAndAppend({ rawList: filteredList, urlField: "url", maxDuration: 59 });
    const next = groups.value.map((g) => (g.id === targetGroup.id ? { ...g, materialList: tempList.value } : g));
    emit("update:modelValue", next);
};

// ──────────────────────────────────────────────────────────────
// 暴露给父组件
// ──────────────────────────────────────────────────────────────
/**
 * 滚动到指定分组并高亮错误边框
 * @param groupId  目标分组 id
 * @param highlight 是否显示红色错误高亮，默认 true
 */
const scrollToGroup = async (groupId: string, highlight = true) => {
    // 1. 强制展开目标分组
    expandedGroups[groupId] = true;

    // 2. 等 DOM 更新
    await nextTick();

    // 3. 在滚动容器的 wrapRef 内查找目标节点
    const wrapEl = groupScrollbarRef.value?.wrapRef;
    if (!wrapEl) return;

    const target = wrapEl.querySelector(`[data-group-id="${groupId}"]`) as HTMLElement | null;
    if (!target) return;

    // 4. 计算相对于滚动容器的 offsetTop 并滚动
    const offsetTop = target.offsetTop - 12; // 留 12px 上边距
    groupScrollbarRef.value?.setScrollTop(offsetTop);

    // 5. 标记错误高亮
    if (highlight) setErrorGroup(groupId);
};

defineExpose({ calcTotalDuration, scrollToGroup });
</script>

<style lang="scss" scoped>
.add-material-btn {
    position: relative;
    border: none;
    background: transparent;
    cursor: pointer;
    outline: none;
}
</style>
