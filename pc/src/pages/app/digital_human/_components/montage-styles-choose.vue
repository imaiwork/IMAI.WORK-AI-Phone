<template>
    <popup
        ref="popupRef"
        width="960px"
        top="5vh"
        cancel-button-text=""
        confirm-button-text=""
        style="padding: 0"
        header-class="!p-0"
        footer-class="!p-0"
        :show-close="false">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="px-8 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 flex items-center justify-center text-primary">
                        <Icon name="el-icon-Collection" :size="22" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight leading-none">{{
                            dialogTitle
                        }}</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.1em] mt-1"
                            >Select Video Style Template</span
                        >
                    </div>
                </div>

                <div class="flex items-center gap-1 bg-[#f1f5f9]/80 p-1 rounded-2xl border border-[#e2e8f0]/50">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        @click="current = tab.value"
                        :class="[
                            'px-5 py-2 rounded-xl text-xs font-[1000] transition-all duration-300',
                            current === tab.value
                                ? 'bg-white text-primary shadow-sm scale-100'
                                : 'text-slate-400 hover:text-slate-600 scale-95 hover:bg-white/50',
                        ]">
                        {{ tab.name }}
                    </button>
                </div>
                <div class="w-9 h-9" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="h-[500px] min-h-0 relative bg-[#f8fafc]/50">
                <ElScrollbar>
                    <div v-if="currentTemplateList.length > 0" class="grid grid-cols-4 gap-6 p-6">
                        <div
                            v-for="template in currentTemplateList"
                            :key="template.templateID"
                            @click="toggleSelect(template)"
                            class="group relative flex flex-col bg-white rounded-[28px] cursor-pointer transition-all duration-500 border-2 overflow-hidden hover:-translate-y-1.5"
                            :class="isSelected(template.templateID) ? 'border-primary' : 'border-[transparent]'">
                            <div class="relative aspect-[3/4.2] overflow-hidden m-2 rounded-[22px] bg-slate-100">
                                <ElImage
                                    :src="getImageUrl(template.pic)"
                                    fit="cover"
                                    lazy
                                    class="w-full h-full transition-transform duration-1000 group-hover:scale-110" />

                                <div class="absolute top-3 right-3 z-20">
                                    <div
                                        :class="[
                                            'w-7 h-7 rounded-full flex items-center justify-center border-2 transition-all duration-300',
                                            isSelected(template.templateID)
                                                ? 'bg-primary border-primary scale-110'
                                                : 'bg-[#000000]/20 border-[#ffffff]/40 backdrop-blur-md opacity-0 group-hover:opacity-100',
                                        ]">
                                        <Icon name="el-icon-Check" color="#fff" :size="14" />
                                    </div>
                                </div>

                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-[#000000]/60 via-[transparent] to-[transparent] opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-10"></div>

                                <div
                                    class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 scale-90 group-hover:scale-100 z-20">
                                    <button
                                        @click.stop="previewTemplate(template)"
                                        class="px-5 py-2.5 bg-[#ffffff]/90 backdrop-blur-md rounded-full text-[13px] font-[1000] text-slate-800 hover:bg-white flex items-center gap-2 active:scale-90 transition-transform">
                                        <Icon name="el-icon-View" :size="16" color="var(--color-primary)" />
                                        <span>快速预览</span>
                                    </button>
                                </div>
                            </div>

                            <div class="px-5 py-4 bg-white">
                                <div class="text-[14px] font-[1000] text-slate-800 truncate">{{ template.name }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center py-32">
                        <div
                            class="w-24 h-24 bg-slate-50 rounded-[40px] flex items-center justify-center mb-6 border border-slate-100 group">
                            <Icon
                                name="el-icon-Files"
                                class="text-slate-200 group-hover:text-slate-300 transition-colors"
                                :size="40" />
                        </div>
                        <span class="text-xs font-black text-slate-300 uppercase tracking-[0.4em]">暂无数据</span>
                    </div>
                </ElScrollbar>
            </div>

            <div class="px-8 py-6 border-t border-slate-50 flex items-center justify-between shrink-0 bg-white">
                <div class="flex items-center gap-4 bg-[#f8fafc]/80 h-[46px] px-4 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="flex -space-x-2">
                            <div
                                v-for="i in Math.min(selectedIds.length, 3)"
                                :key="i"
                                class="w-6 h-6 rounded-full border-2 border-white bg-[#0065fb]/20 flex items-center justify-center">
                                <div
                                    class="w-2 h-2 rounded-full bg-primary"
                                    :class="{ 'animate-pulse': i === 1 }"></div>
                            </div>
                        </div>
                        <span class="text-[13px] font-black text-slate-700">
                            已选择 <span class="text-primary mx-0.5">{{ selectedIds.length }}</span> 个模版
                        </span>
                    </div>
                    <div v-if="selectedIds.length > 0" class="w-px h-4 bg-slate-200"></div>
                    <button
                        v-if="selectedIds.length > 0"
                        @click="selectedIds = []"
                        class="text-[11px] font-black text-slate-400 hover:text-red-500 transition-colors uppercase tracking-widest">
                        重置
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="close"
                        class="px-8 h-11 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all">
                        取消
                    </button>
                    <button
                        @click="handleConfirm"
                        class="px-10 h-11 rounded-xl bg-primary text-white text-sm font-[1000] shadow-[#0065fb]/20 hover:bg-[#0056d6] hover:scale-[1.02] active:scale-95 transition-all">
                        确定使用
                    </button>
                </div>
            </div>
        </div>
    </popup>

    <ElImageViewer
        v-if="showPreview && previewType === 'image'"
        :url-list="[previewUrl]"
        @close="showPreview = false" />
    <preview-video v-if="showPreview && previewType === 'video'" ref="previewVideoRef" @close="showPreview = false" />
</template>
<script setup lang="ts">
import { MontageStylesChooseType, MontageStylesType } from "../_enums";
import { digitalPersonTemplates, realPersonTemplates, newsTemplates, materialTemplates } from "../_config";

const props = defineProps<{
    type?: MontageStylesType;
    selected?: string[];
}>();

const emit = defineEmits<{
    (e: "confirm", data: string[]): void;
    (e: "close"): void;
}>();

const popupRef = shallowRef();
const previewVideoRef = shallowRef();
const tabs = [
    { name: "全部", value: MontageStylesChooseType.ALL },
    { name: "高级感", value: MontageStylesChooseType.HIGH },
    { name: "综艺感", value: MontageStylesChooseType.VARIETY },
    { name: "热门", value: MontageStylesChooseType.HOT },
    { name: "简约", value: MontageStylesChooseType.SIMPLE },
    { name: "本地引流", value: MontageStylesChooseType.LOCAL },
];

const current = ref<MontageStylesChooseType>(MontageStylesChooseType.ALL);

const currentTemp = computed(() => props.type ?? MontageStylesType.DIGITAL_PERSON);

const dialogTitle = computed(() => {
    const titles: Record<MontageStylesType, string> = {
        [MontageStylesType.DIGITAL_PERSON]: "数字人口播模板",
        [MontageStylesType.REAL_PERSON]: "真人口播模板",
        [MontageStylesType.NEWS]: "新闻体模板",
        [MontageStylesType.MATERIAL]: "素材混剪模板",
    };
    return titles[currentTemp.value] ?? "选择视频风格";
});

const selectedIds = ref<string[]>([...(props.selected ?? [])]);

const allTemplatesMap = {
    [MontageStylesType.DIGITAL_PERSON]: digitalPersonTemplates,
    [MontageStylesType.REAL_PERSON]: realPersonTemplates,
    [MontageStylesType.NEWS]: newsTemplates,
    [MontageStylesType.MATERIAL]: materialTemplates,
};

const currentTemplateList = computed<any[]>(() => {
    const templates = allTemplatesMap[currentTemp.value as keyof typeof allTemplatesMap];
    if (!templates) return [];
    if (current.value === MontageStylesChooseType.ALL) {
        return Object.values(templates).flat();
    }
    return (templates as any)[current.value] ?? [];
});

const toggleSelect = (template: any) => {
    const id = template.templateID;
    const index = selectedIds.value.indexOf(id);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const isSelected = (id: string) => selectedIds.value.includes(id);

// ---- URL 工具 ----
const getImageUrl = (pic: string) => {
    if (currentTemp.value === MontageStylesType.REAL_PERSON) {
        return `/static/videos/montage_template/${pic}`;
    }
    return `/static/images/montage_template/${pic}`;
};

const getVideoUrl = (link: string) => `/static/videos/montage_template/${link}`;

const showPreview = ref(false);
const previewUrl = ref("");
const previewType = ref<"video" | "image">("image");
const previewName = ref("");

const previewTemplate = async (template: any) => {
    previewName.value = template.name;
    if (currentTemp.value === MontageStylesType.REAL_PERSON) {
        previewUrl.value = getVideoUrl(template.link);
        showPreview.value = true;
        await nextTick();
        previewVideoRef.value.open();
        previewVideoRef.value.setUrl(previewUrl.value);
    } else {
        previewUrl.value = getImageUrl(template.pic);
        previewType.value = "image";
        showPreview.value = true;
    }
};

const open = () => {
    popupRef.value?.open();
    console.log(props.selected);
};

const close = () => {
    emit("close");
};

const handleConfirm = () => {
    if (selectedIds.value.length === 0) return;
    emit("confirm", [...selectedIds.value]);
    close();
};

watch(
    () => props.selected,
    (val) => {
        selectedIds.value = [...(val ?? [])];
    }
);

defineExpose({
    open,
    close,
});
</script>
<style scoped lang="scss">
.custom-scrollbar {
    :deep(.el-scrollbar__thumb) {
        background-color: #e2e8f0;
        &:hover {
            background-color: #cbd5e1;
        }
    }
}

/* 弹窗圆角与平滑度补全 */
:deep(.popup-content) {
    border-radius: 32px !important;
    overflow: hidden;
}

/* 按钮点击波纹简约实现 */
button:active {
    transform: scale(0.96);
}
</style>
