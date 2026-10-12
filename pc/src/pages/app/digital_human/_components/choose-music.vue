<template>
    <popup
        ref="popupRef"
        width="520px"
        top="10vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-Headset" :size="20" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight leading-none">选择背景音乐</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.1em] mt-1"
                            >Select Background Music</span
                        >
                    </div>
                </div>
                <div class="w-9 h-9" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="flex flex-col p-6 gap-6 overflow-hidden">
                <upload
                    class="w-full"
                    type="file"
                    accept=".mp3,.wav"
                    show-progress
                    :max-size="20"
                    :show-file-list="false"
                    @change="handleUploadSuccess">
                    <div
                        class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl border-2 border-dashed border-slate-100 bg-slate-50/50 hover:border-[#0065fb]/30 hover:bg-[#0065fb]/5 transition-all cursor-pointer group text-left">
                        <div
                            class="w-11 h-11 rounded-xl bg-white border border-slate-100 flex items-center justify-center group-hover:border-[#0065fb]/20 group-hover:text-primary transition-all text-slate-400 shrink-0">
                            <Icon name="el-icon-UploadFilled" :size="22" />
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span
                                class="text-[14px] font-[1000] text-slate-700 group-hover:text-primary transition-colors"
                                >上传本地音频</span
                            >
                            <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider"
                                >支持 MP3, WAV (Max 20MB)</span
                            >
                        </div>
                    </div>
                </upload>

                <ElScrollbar max-height="420px" :distance="20" @end-reached="load">
                    <div class="flex flex-col gap-2.5 pb-2 text-left">
                        <div
                            class="music-row cursor-pointer"
                            :class="isAiMusic ? 'border-primary bg-[#0065fb]/5' : 'border-slate-100'"
                            @click="pickRandom">
                            <div
                                class="w-10 h-10 rounded-full bg-white flex items-center justify-center shrink-0"
                                :class="isAiMusic ? 'text-primary' : 'text-slate-400'">
                                <Icon name="el-icon-Cpu" :size="18" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-[1000] text-slate-800">系统随机</div>
                                <div class="text-[11px] text-slate-400 font-bold">每次从系统曲库随机一首</div>
                            </div>
                            <Icon v-if="isAiMusic" name="el-icon-Check" color="var(--el-color-primary)" :size="16" />
                        </div>

                        <div v-if="systemMusics.length" class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] px-1 pt-1">
                            系统音乐
                        </div>
                        <div
                            v-for="item in systemMusics"
                            :key="item.id"
                            class="music-row cursor-pointer"
                            :class="systemMusicId === item.id ? 'border-primary bg-[#0065fb]/5' : 'border-slate-100'"
                            @click="pickSystem(item)">
                            <button
                                type="button"
                                class="w-10 h-10 rounded-lg shrink-0 flex items-center justify-center text-white"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                @click.stop="playSystem(item)">
                                <Icon
                                    :name="isTrackPlaying(systemPlayId(item.id)) ? 'el-icon-VideoPause' : 'el-icon-VideoPlay'"
                                    color="#fff"
                                    :size="18" />
                            </button>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-slate-800 truncate">{{ item.name }}</div>
                                <div class="text-[11px] text-slate-400">{{ formatDuration(item.duration_sec) }}</div>
                            </div>
                            <Icon
                                v-if="systemMusicId === item.id"
                                name="el-icon-Check"
                                color="var(--el-color-primary)"
                                :size="16" />
                        </div>
                        <div v-if="systemMusicLoaded && !systemMusics.length" class="text-[12px] text-slate-400 text-center py-3">
                            暂无上架曲目，将使用系统随机
                        </div>

                        <div class="flex items-center justify-between px-1 pt-1">
                            <span class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">我上传的</span>
                            <span class="text-[11px] text-slate-300 font-bold tracking-tight">{{ userMusicCount }} 首可用</span>
                        </div>
                        <div
                            v-for="item in pager.lists"
                            :key="item.id"
                            class="music-row cursor-pointer"
                            :class="isSelected(item.id) ? 'border-primary bg-[#0065fb]/5' : 'border-slate-100'"
                            @click="handleSelect(item)">
                                <button
                                    type="button"
                                    class="relative w-10 h-10 shrink-0 overflow-hidden rounded-lg flex items-center justify-center"
                                    style="background: linear-gradient(180deg, rgb(162, 227, 223) 0%, rgb(120, 201, 196) 100%)"
                                    @click.stop="playMusic(item)">
                                    <Icon
                                        :name="isTrackPlaying(userPlayId(item.id)) ? 'el-icon-VideoPause' : 'el-icon-VideoPlay'"
                                        color="#fff"
                                        :size="18" />
                                </button>

                                <div class="flex-1 min-w-0">
                                    <div
                                        class="text-sm font-medium truncate"
                                        :class="isSelected(item.id) ? 'text-primary' : 'text-slate-800'">
                                        {{ item.name }}
                                    </div>
                                </div>

                                <Icon
                                    v-if="isSelected(item.id)"
                                    name="el-icon-Check"
                                    color="var(--el-color-primary)"
                                    :size="16" />
                            </div>
                        <load-text :is-load="pager.isLoad"></load-text>
                    </div>
                </ElScrollbar>
            </div>

            <div
                class="px-8 py-6 border-t border-slate-50 flex items-center justify-between shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.01)]">
                <div class="text-[12px] font-black text-slate-400 min-w-0 truncate">
                    <span v-if="systemMusicId" class="text-primary">{{ systemMusicName || "已指定" }}</span>
                    <span v-else-if="isAiMusic" class="text-primary">系统随机</span>
                    <span v-else-if="chooseList.length">
                        已选 <span class="text-primary">{{ chooseList.length }}</span> 首
                    </span>
                    <span v-else>未选择</span>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="close"
                        class="px-6 h-11 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all active:scale-95">
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
</template>

<script setup lang="ts">
import { getMaterialLibraryList, addMaterialLibrary } from "@/api/material";
import { getSystemMusicList } from "@/api/digital_human";
import { getValidUploadFileData } from "@/pages/app/digital_human/_hooks/useUpload";

const props = defineProps({
    multiple: {
        type: Boolean,
        default: true,
    },
});
const emit = defineEmits<{
    (e: "confirm", data: { music: any[]; ai_music: boolean; system_music_id: string; system_music_name: string }): void;
    (e: "close"): void;
}>();

const popupRef = shallowRef();
const isAiMusic = ref(true);
const chooseList = ref<any[]>([]);
const systemMusics = ref<any[]>([]);
const systemMusicLoaded = ref(false);
const systemMusicId = ref("");
const systemMusicName = ref("");

const audioInstance = new Audio();
const currPlayingId = ref<string | number | null>(null);
const isPaused = ref(true);

const queryParams = reactive({
    page_no: 1,
    m_type: 6,
});
const isM4a = (item: any) => String(item?.content || "").toLowerCase().includes(".m4a");

const { getLists, pager, resetPage } = usePaging({
    fetchFun: getMaterialLibraryList,
    params: queryParams,
    isScroll: true,
});

const stripM4a = () => {
    pager.lists = pager.lists.filter((item: any) => !isM4a(item));
};

const userMusicCount = computed(() => pager.count || 0);

const load = async (e: string) => {
    if (e == "bottom" && pager.isLoad && !pager.loading) {
        queryParams.page_no++;
        await getLists();
        stripM4a();
    }
};

const isSelected = (id: any) => chooseList.value.some((item) => item.id === id);

const handleSelect = (item: any) => {
    // 选择普通音乐时关闭 AI 模式（与小程序互斥逻辑一致）
    if (isAiMusic.value) {
        isAiMusic.value = false;
    }
    systemMusicId.value = "";
    systemMusicName.value = "";
    const index = chooseList.value.findIndex((i) => i.id === item.id);
    if (props.multiple) {
        // 多选：已选则取消，未选则追加
        if (index > -1) {
            chooseList.value.splice(index, 1);
        } else {
            chooseList.value.push(item);
        }
    } else {
        // 单选：已选则取消，未选则替换
        if (index > -1) {
            chooseList.value = [];
        } else {
            chooseList.value = [item];
        }
    }
};

const systemPlayId = (id: string | number) => `sys-${id}`;
const userPlayId = (id: string | number) => `user-${id}`;

const formatDuration = (sec: number) => {
    const total = Math.max(0, Math.floor(Number(sec) || 0));
    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, "0")}`;
};

const isTrackPlaying = (id: string) => currPlayingId.value === id && !isPaused.value;

const togglePlay = (id: string, url: string) => {
    if (!url) return;
    if (currPlayingId.value === id) {
        if (audioInstance.paused) {
            audioInstance.play();
            isPaused.value = false;
        } else {
            audioInstance.pause();
            isPaused.value = true;
        }
        return;
    }
    currPlayingId.value = id;
    audioInstance.src = url;
    audioInstance.play();
    isPaused.value = false;
};

const playMusic = (item: any) => {
    togglePlay(userPlayId(item.id), item.content);
};

const playSystem = (item: any) => {
    togglePlay(systemPlayId(item.id), item.audio_url);
};

const pickRandom = () => {
    if (isAiMusic.value) {
        isAiMusic.value = false;
        return;
    }
    isAiMusic.value = true;
    chooseList.value = [];
    systemMusicId.value = "";
    systemMusicName.value = "";
};

audioInstance.onended = () => {
    currPlayingId.value = null;
    isPaused.value = true;
};

const handleUploadSuccess = async (res: any) => {
    const data = getValidUploadFileData(res);
    if (!data) return;
    const { size } = res || {};
    const { name, uri } = data;
    try {
        await addMaterialLibrary({
            m_type: 6,
            name: name?.split(".")[0],
            content: uri,
            size: size,
            duration: 0,
            pic: "",
            sort: 0,
            type: 3,
        });
        await resetPage();
        stripM4a();
    } catch (error) {
        feedback.msgError(error);
    }
};

const pickSystem = (item: any) => {
    if (systemMusicId.value === item.id) {
        systemMusicId.value = "";
        systemMusicName.value = "";
        isAiMusic.value = true;
        return;
    }
    systemMusicId.value = item.id;
    systemMusicName.value = item.name || "";
    isAiMusic.value = false;
    chooseList.value = [];
};

const open = async () => {
    popupRef.value?.open();
    getLists().then(stripM4a);
    if (systemMusicLoaded.value) return;
    try {
        const res = await getSystemMusicList();
        systemMusics.value = Array.isArray(res) ? res : [];
    } catch {
        systemMusics.value = [];
    } finally {
        systemMusicLoaded.value = true;
    }
};

const close = () => {
    audioInstance.pause();
    isPaused.value = true;
    emit("close");
};

const handleConfirm = () => {
    // 允许不选择音乐直接确定（与小程序一致）：可清空已选，或切回 AI 配乐
    emit("confirm", {
        music: isAiMusic.value || systemMusicId.value ? [] : chooseList.value,
        ai_music: isAiMusic.value && !systemMusicId.value,
        system_music_id: systemMusicId.value,
        system_music_name: systemMusicName.value,
    });
    close();
};

onUnmounted(() => {
    audioInstance.pause();
    audioInstance.src = "";
});

defineExpose({
    open,
    close,
    setSelected: (val: any[], aiMusic?: boolean, pinnedId = "", pinnedName = "") => {
        const list = [...(val ?? [])];
        chooseList.value = pinnedId ? [] : list;
        systemMusicId.value = pinnedId;
        systemMusicName.value = pinnedName;
        isAiMusic.value = pinnedId ? false : typeof aiMusic === "boolean" ? aiMusic : list.length === 0;
    },
});
</script>

<style scoped lang="scss">
.music-row {
    @apply h-14 shrink-0 rounded-xl border px-3 flex items-center gap-3;
}
</style>
