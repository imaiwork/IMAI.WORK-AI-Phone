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

                <div
                    class="flex items-center justify-between p-4 rounded-2xl border border-slate-100 transition-all text-left"
                    :class="isAiMusic ? 'bg-[#0065fb]/5 border-[#0065fb]/20' : 'bg-[#f8fafc]/30 border-[transparent]'">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-full bg-white flex items-center justify-center"
                            :class="isAiMusic ? 'text-primary' : 'text-slate-400'">
                            <Icon name="el-icon-Cpu" :size="20" />
                        </div>
                        <div>
                            <div class="text-sm font-[1000] text-slate-800">AI 智能配乐模式</div>
                            <div class="text-[11px] text-slate-400 font-bold">开启后将根据文案意境自动生成配乐</div>
                        </div>
                    </div>
                    <ElSwitch v-model="isAiMusic" />
                </div>

                <div class="flex-1 min-h-0 flex flex-col overflow-hidden text-left" v-if="!isAiMusic">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <span class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">音乐库</span>
                        <span class="text-[11px] text-slate-300 font-bold tracking-tight"
                            >{{ pager.count }} 首可用</span
                        >
                    </div>

                    <ElScrollbar class="flex-1 pr-2" max-height="400px" :distance="20" @end-reached="load">
                        <div class="flex flex-col gap-2.5 pb-4">
                            <div
                                v-for="item in pager.lists"
                                :key="item.id"
                                @click="handleSelect(item)"
                                :class="[
                                    'group flex items-center gap-4 p-3 rounded-xl border-2 transition-all cursor-pointer relative overflow-hidden',
                                    isSelected(item.id)
                                        ? 'bg-[#0065fb]/5 border-primary'
                                        : 'bg-white border-slate-50 hover:border-slate-200',
                                ]">
                                <div
                                    class="relative w-12 h-12 shrink-0 overflow-hidden rounded-lg flex items-center justify-center group"
                                    style="
                                        background: linear-gradient(
                                            180deg,
                                            rgb(162, 227, 223) 0%,
                                            rgb(120, 201, 196) 100%
                                        );
                                    ">
                                    <span
                                        class="text-white transition-opacity leading-[0]"
                                        v-if="currPlayingId !== item.id || isPaused">
                                        <Icon name="el-icon-Microphone" :size="22" />
                                    </span>

                                    <div v-if="currPlayingId === item.id && !isPaused" class="playing-bars">
                                        <span></span><span></span><span></span>
                                    </div>

                                    <div
                                        class="absolute inset-0 bg-[#000000]/40 flex items-center justify-center transition-opacity cursor-pointer"
                                        :class="
                                            currPlayingId === item.id
                                                ? 'opacity-100'
                                                : 'opacity-0 group-hover:opacity-100'
                                        "
                                        @click.stop="playMusic(item)">
                                        <Icon
                                            :name="
                                                currPlayingId === item.id && !isPaused
                                                    ? 'el-icon-VideoPause'
                                                    : 'el-icon-VideoPlay'
                                            "
                                            color="#fff"
                                            :size="22" />
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div
                                        class="text-sm font-medium truncate"
                                        :class="isSelected(item.id) ? 'text-primary' : 'text-slate-800'">
                                        {{ item.name }}
                                    </div>
                                </div>

                                <div
                                    class="w-6 h-6 rounded-full flex items-center justify-center transition-all border shrink-0"
                                    :class="
                                        isSelected(item.id)
                                            ? 'bg-primary border-primary scale-100'
                                            : 'bg-white border-slate-100 opacity-0 group-hover:opacity-100 scale-90'
                                    ">
                                    <Icon name="el-icon-Check" color="#fff" :size="12" />
                                </div>
                            </div>
                        </div>
                        <load-text :is-load="pager.isLoad"></load-text>
                    </ElScrollbar>
                </div>
            </div>

            <div
                class="px-8 py-6 border-t border-slate-50 flex items-center justify-between shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.01)]">
                <div class="text-[12px] font-black text-slate-400">
                    <span v-if="!isAiMusic">
                        已选 <span class="text-primary">{{ chooseList.length }}</span> 首内容
                    </span>
                    <span v-else class="text-primary flex items-center gap-1.5 font-[1000]">
                        <Icon name="el-icon-MagicStick" /> 智能模式已激活
                    </span>
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

const props = defineProps({
    multiple: {
        type: Boolean,
        default: true,
    },
});
const emit = defineEmits<{
    (e: "confirm", data: { music: any[] }): void;
    (e: "close"): void;
}>();

const popupRef = shallowRef();
const isAiMusic = ref(true);
const chooseList = ref<any[]>([]);

const audioInstance = new Audio();
const currPlayingId = ref<string | number | null>(null);
const isPaused = ref(true);

const queryParams = reactive({
    page_no: 1,
    m_type: 6,
});
const { getLists, pager, resetPage } = usePaging({
    fetchFun: getMaterialLibraryList,
    params: queryParams,
    isScroll: true,
});

const load = async (e: string) => {
    if (e == "bottom" && pager.isLoad && !pager.loading) {
        queryParams.page_no++;
        await getLists();
    }
};

const isSelected = (id: any) => chooseList.value.some((item) => item.id === id);

const handleSelect = (item: any) => {
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

const playMusic = (item: any) => {
    if (currPlayingId.value === item.id) {
        if (audioInstance.paused) {
            audioInstance.play();
            isPaused.value = false;
        } else {
            audioInstance.pause();
            isPaused.value = true;
        }
    } else {
        currPlayingId.value = item.id;
        audioInstance.src = item.content;
        audioInstance.play();
        isPaused.value = false;
    }
};

audioInstance.onended = () => {
    currPlayingId.value = null;
    isPaused.value = true;
};

const handleUploadSuccess = async (res: any) => {
    const {
        size,
        response: {
            data: { name, uri },
        },
    } = res || {};
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
        resetPage();
    } catch (error) {
        feedback.msgError(error);
    }
};

const open = () => {
    popupRef.value?.open();
    getLists();
};

const close = () => {
    audioInstance.pause();
    isPaused.value = true;
    emit("close");
};

const handleConfirm = () => {
    if (!isAiMusic.value && chooseList.value.length === 0) {
        feedback.msgWarning("请至少选择一首音乐");
        return;
    }
    emit("confirm", {
        music: isAiMusic.value ? [] : chooseList.value,
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
    setSelected: (val: any[]) => {
        if (val.length == 0) {
            isAiMusic.value = true;
            return;
        }
        chooseList.value = [...(val ?? [])];
        isAiMusic.value = false;
    },
});
</script>

<style scoped lang="scss">
.playing-bars {
    @apply flex items-end gap-[2px] h-[14px];
    span {
        @apply w-[3px] h-1 bg-primary rounded-[1px];
        animation: music-bar 0.6s infinite alternate;
        &:nth-child(2) {
            animation-delay: 0.2s;
        }
        &:nth-child(3) {
            animation-delay: 0.4s;
        }
    }
}

@keyframes music-bar {
    0% {
        height: 4px;
    }
    100% {
        height: 14px;
    }
}
</style>
