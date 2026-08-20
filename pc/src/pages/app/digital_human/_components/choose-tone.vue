<template>
    <popup
        ref="popupRef"
        width="560px"
        top="10vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-Microphone" :size="18" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight">请选择音色</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.1em] mt-1"
                            >Select Tone</span
                        >
                    </div>
                </div>
                <div class="w-9 h-9" @click="close">
                    <close-btn />
                </div>
            </div>

            <div v-if="getTabsList.length > 1" class="px-6 pt-4 shrink-0">
                <div class="flex gap-1 p-1 bg-slate-50 rounded-xl">
                    <button
                        v-for="(tab, i) in getTabsList"
                        :key="tab.value"
                        :class="[
                            'flex-1 h-9 rounded-lg text-sm font-[1000] transition-all',
                            current === i ? 'bg-white text-primary' : 'text-slate-400 hover:text-slate-600',
                        ]"
                        @click="handleChange(i)">
                        {{ tab.name }}
                    </button>
                </div>
            </div>

            <div v-if="showOriginalTone" class="px-6 pt-4 shrink-0">
                <div
                    :class="[
                        'flex items-center gap-3 px-5 py-3.5 rounded-2xl border-2 transition-all cursor-pointer group',
                        originalSelected
                            ? 'border-solid border-[#16A34A] bg-[#F0FDF4] shadow-[0_0_0_2px_rgba(22,163,74,0.15)]'
                            : 'border-dashed border-[#16A34A]/30 bg-[#F0FDF4]/50 hover:border-[#16A34A]/60 hover:bg-[#F0FDF4]',
                    ]"
                    @click="handleSelectOriginal">
                    <div
                        class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-white"
                        style="background: linear-gradient(135deg, #4ade80 0%, #16a34a 100%)">
                        <Icon name="el-icon-Microphone" :size="18" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-[1000] text-[#16A34A]">{{ originalToneTitle }}</div>
                        <div class="text-[11px] mt-0.5" :class="originalSelected ? 'text-[#16A34A]' : 'text-[#4ADE80]'">
                            {{ originalSelected ? "当前使用中" : originalToneDesc }}
                        </div>
                    </div>
                    <div
                        v-if="originalSelected"
                        class="w-5 h-5 rounded-full bg-[#16A34A] flex items-center justify-center shrink-0">
                        <Icon name="el-icon-Check" color="#fff" :size="11" />
                    </div>
                    <Icon v-else name="el-icon-ArrowRight" color="#4ADE80" :size="16" />
                </div>
            </div>

            <div class="px-6 pt-4 shrink-0">
                <router-link
                    :to="`/app/digital_human?type=${SidebarTypeEnum.VOICE_CLONE}`"
                    class="flex items-center gap-3 px-5 py-3.5 rounded-2xl border-2 border-dashed border-slate-100 bg-[#f8fafc]/50 hover:border-[#0065fb]/30 hover:bg-[#0065fb]/5 transition-all cursor-pointer group">
                    <div
                        class="w-9 h-9 rounded-xl bg-white border border-slate-100 flex items-center justify-center group-hover:border-[#0065fb]/20 group-hover:text-primary transition-all text-slate-400 shrink-0">
                        <Icon name="el-icon-Plus" :size="18" />
                    </div>
                    <span class="text-sm font-[1000] text-slate-600 group-hover:text-primary transition-colors">
                        去克隆音色
                    </span>
                </router-link>
            </div>

            <div class="flex-1 min-h-0 overflow-hidden pt-4">
                <div v-if="pager.loading && pager.lists.length === 0" class="flex flex-col gap-2.5 px-6">
                    <div v-for="i in 4" :key="i" class="h-[68px] rounded-2xl bg-slate-100 animate-pulse" />
                </div>

                <div
                    v-else-if="!pager.loading && pager.lists.length === 0"
                    class="flex flex-col items-center justify-center py-14 gap-4">
                    <div
                        class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100">
                        <Icon name="el-icon-Microphone" color="#cbd5e1" :size="32" />
                    </div>
                    <span class="text-sm font-black text-slate-300 uppercase tracking-wider">暂无可用音色</span>
                </div>

                <ElScrollbar v-else class="h-full" max-height="420px" :distance="20" @end-reached="loadMore">
                    <div class="flex flex-col gap-2.5 pb-4 px-6">
                        <div
                            v-for="(item, index) in pager.lists"
                            :key="item.voice_id ?? index"
                            :class="[
                                'group flex items-center gap-4 p-3.5 rounded-2xl border-2 transition-all',
                                isSelected(item.voice_id)
                                    ? 'bg-[#0065fb]/5 border-primary  shadow-[#0065fb]/10 cursor-pointer'
                                    : isAtLimit
                                    ? 'bg-white border-slate-50 opacity-50 cursor-not-allowed'
                                    : 'bg-white border-slate-50 hover:border-slate-200 cursor-pointer',
                            ]"
                            @click="chooseTone(item)">
                            <div
                                :class="[
                                    'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 transition-all',
                                    isSelected(item.voice_id)
                                        ? 'bg-[#0065fb]/10 text-primary'
                                        : 'bg-slate-50 text-slate-400 group-hover:bg-[#0065fb]/5 group-hover:text-primary',
                                ]">
                                <Icon :name="item.type === 0 ? 'el-icon-User' : 'el-icon-Service'" :size="20" />
                            </div>

                            <div class="flex-1 min-w-0 flex items-center gap-2">
                                <span class="text-sm font-[1000] text-slate-800 truncate">{{ item.name }}</span>
                                <span
                                    :class="[
                                        'shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider',
                                        item.type === 0 ? 'bg-[#f5f3ff] text-[#a78bfa]' : 'bg-[#0065fb]/8 text-primary',
                                    ]">
                                    {{ item.type === 0 ? "用户" : "系统" }}
                                </span>
                            </div>

                            <button
                                :class="[
                                    'shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-xl border transition-all text-xs font-[1000]',
                                    isPlayingItem(item.voice_id)
                                        ? 'bg-primary border-primary text-white shadow-[#0065fb]/20'
                                        : 'bg-slate-50 border-slate-100 text-slate-500 hover:border-[#0065fb]/30 hover:text-primary hover:bg-[#0065fb]/5',
                                ]"
                                @click.stop="toggleAudioPlayback(item)">
                                <Icon
                                    :name="isPlayingItem(item.voice_id) ? 'el-icon-VideoPause' : 'el-icon-VideoPlay'"
                                    :size="14" />
                                <span>{{ isPlayingItem(item.voice_id) ? "暂停" : "试听" }}</span>
                            </button>

                            <div
                                v-if="isSelected(item.voice_id)"
                                class="w-5 h-5 rounded-full bg-primary flex items-center justify-center shrink-0">
                                <Icon name="el-icon-Check" color="#fff" :size="11" />
                            </div>
                            <div
                                v-else
                                class="w-5 h-5 rounded-full border-2 border-slate-200 shrink-0 group-hover:border-[#0065fb]/40 transition-colors" />
                        </div>
                    </div>
                    <load-text :is-load="pager.isLoad" />
                </ElScrollbar>
            </div>

            <div
                class="px-6 py-4 border-t border-slate-50 flex items-center justify-between shrink-0 bg-white shadow-[0_-8px_16px_rgba(0,0,0,0.02)]">
                <span class="text-[12px] font-black text-slate-300 uppercase tracking-wider">
                    <template v-if="isMultiple">
                        已选
                        <span :class="chooseToneItems.length > 0 ? 'text-primary' : ''">
                            {{ chooseToneItems.length }}
                        </span>
                        / {{ limit }} 个音色
                    </template>
                    <template v-else> 共 {{ pager.count ?? pager.lists.length }} 个音色 </template>
                </span>
                <div class="flex items-center gap-3">
                    <button
                        @click="close"
                        class="px-6 h-10 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all active:scale-95">
                        取消
                    </button>
                    <button
                        @click="handleConfirm"
                        class="px-8 h-10 rounded-xl bg-primary text-white text-sm font-[1000] shadow-[#0065fb]/20 hover:bg-[#0056d6] hover:scale-[1.02] active:scale-95 transition-all">
                        确定选择
                    </button>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getVoiceList } from "@/api/digital_human";
import { SidebarTypeEnum, DigitalHumanModelVersionEnum } from "@/pages/app/digital_human/_enums";

// ─── Props & Emits ────────────────────────────────────────────────────────────

const props = withDefaults(
    defineProps<{
        modelVersion?: string | number;
        activeTone?: any;
        showOriginalTone?: boolean;
        originalToneTitle?: string;
        originalToneDesc?: string;
        originalSelected?: boolean;
        showFreeTone?: boolean;
        showUserTone?: boolean;
        limit?: number;
        type?: number;
    }>(),
    {
        modelVersion: "",
        activeTone: null,
        showOriginalTone: false,
        originalToneTitle: "使用形象原声",
        originalToneDesc: "使用当前形象的原始声音",
        originalSelected: false,
        showFreeTone: true,
        showUserTone: true,
        limit: 1,
        type: 1,
    },
);

const emit = defineEmits<{
    (e: "confirm", value: any): void;
    (e: "original"): void;
    (e: "close"): void;
}>();

// ─── Constants ────────────────────────────────────────────────────────────────

const ALL_TABS = [
    { name: "系统音色", value: 0 },
    { name: "用户音色", value: 1 },
];

// ─── Refs & State ─────────────────────────────────────────────────────────────

const popupRef = shallowRef();
const current = ref(0);
const chooseToneItems = ref<any[]>([]);
const currVoiceId = ref<string | null>(null);

// ─── Computed ─────────────────────────────────────────────────────────────────

const isMultiple = computed(() => props.limit > 1);

const isAtLimit = computed(() => isMultiple.value && chooseToneItems.value.length >= props.limit);

const getTabsList = computed(() => {
    if (!props.showFreeTone && !props.showUserTone) return [];
    if (!props.showFreeTone) return ALL_TABS.filter((item) => item.value !== 0);
    if (!props.showUserTone) return ALL_TABS.filter((item) => item.value !== 1);
    return ALL_TABS;
});

// ─── Audio ────────────────────────────────────────────────────────────────────

const { isPlaying, play, pause, pauseAll, destroy } = useAudio();

const isPlayingItem = (voice_id: string) => isPlaying.value && currVoiceId.value === voice_id;

const getToneAudioUrl = (item: any) => {
    const url = item?.builtin === 1 ? item?.voice_urls : item?.url;
    return typeof url === "string" ? url.trim() : "";
};

const toggleAudioPlayback = (item: any) => {
    const isSameItem = currVoiceId.value === item.voice_id;

    if (isPlaying.value && isSameItem) {
        pause();
        return;
    }

    const audioUrl = getToneAudioUrl(item);
    if (!audioUrl) {
        // 无音频时必须停掉上一条，避免继续播旧源
        pauseAll();
        currVoiceId.value = null;
        feedback.msgWarning("暂无试听音频");
        return;
    }

    if (isPlaying.value && !isSameItem) pauseAll();
    play(audioUrl);
    currVoiceId.value = item.voice_id;
};

// ─── Paging & Fetch ───────────────────────────────────────────────────────────

const commonParams = reactive({ page_no: 1, page_size: 15 });

const fetchFun = (params: any) => {
    const isSystemTab = props.showFreeTone && current.value === 0;
    const type = current.value === 1 || props.type == 0 ? "" : props.type;

    return getVoiceList({
        ...params,
        model_version: props.modelVersion || "",
        status: 1,
        builtin: isSystemTab ? 0 : 1,
        type,
    }).then(({ lists }) => {
        if (isSystemTab) {
            lists.forEach((item: any) => (item.voice_id = item.code));
        }
        return { lists };
    });
};

const { getLists, pager, resetPage } = usePaging({
    fetchFun,
    params: commonParams,
    isScroll: true,
});

const loadMore = (e: string) => {
    if (e === "bottom" && pager.isLoad && !pager.loading) {
        commonParams.page_no++;
        getLists();
    }
};

// ─── Selection ────────────────────────────────────────────────────────────────

const isSelected = (voice_id: string) => chooseToneItems.value.some((t) => t.voice_id === voice_id);

const initSelected = () => {
    current.value = getTabsList.value[0].value;
    if (!props.activeTone) {
        chooseToneItems.value = [];
        return;
    }
    chooseToneItems.value = Array.isArray(props.activeTone)
        ? [...props.activeTone]
        : props.activeTone?.voice_id
        ? [props.activeTone]
        : [];
};

const chooseTone = (item: any) => {
    const idx = chooseToneItems.value.findIndex((t) => t.voice_id === item.voice_id);

    if (idx > -1) {
        chooseToneItems.value.splice(idx, 1);
        return;
    }
    if (!isMultiple.value) {
        chooseToneItems.value = [item];
        return;
    }
    if (isAtLimit.value) {
        feedback.msgWarning(`最多选择 ${props.limit} 个音色`);
        return;
    }

    chooseToneItems.value.push(item);
};

// ─── Actions ──────────────────────────────────────────────────────────────────

const handleChange = (index: number) => {
    current.value = index;
    resetPage();
};

const handleSelectOriginal = () => {
    destroy();
    emit("original");
    close();
};

const handleConfirm = () => {
    destroy();
    const result = isMultiple.value ? chooseToneItems.value : chooseToneItems.value[0] ?? null;
    emit("confirm", result);
    close();
};

const open = () => {
    initSelected();
    popupRef.value?.open();
    getLists();
};

const close = () => {
    destroy();
    emit("close");
};

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onUnmounted(() => {
    pauseAll();
    destroy();
});

defineExpose({ open, close });
</script>
