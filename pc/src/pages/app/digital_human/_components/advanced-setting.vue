<template>
    <div>
        <div class="flex items-center gap-3 py-3 cursor-pointer select-none" @click="expanded = !expanded">
            <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                <span class="text-[13px] font-medium text-slate-700">高级设置</span>
                <span class="text-[10px] text-slate-400 truncate">{{ summary }}</span>
            </div>
            <Icon
                :name="expanded ? 'el-icon-ArrowUp' : 'el-icon-ArrowDown'"
                color="var(--slate-300)"
                :size="14" />
        </div>

        <div v-if="expanded">
            <div
                v-for="item in PACK_ITEMS"
                :key="item.key"
                class="flex items-center justify-between gap-3 py-3 border-t border-slate-100">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[13px] font-medium text-slate-700">{{ item.label }}</span>
                    <span class="text-[10px] text-slate-400">{{ item.desc }}</span>
                </div>
                <ElSwitch
                    :model-value="isOn(item.key)"
                    inactive-color="#e2e8f0"
                    @update:model-value="handleSwitch(item.key, $event as boolean)" />
            </div>

            <div v-if="showMusic" class="py-3 border-t border-slate-100">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[13px] font-medium text-slate-700">背景音乐</span>
                        <span class="text-[10px] text-slate-400">{{ musicTip }}</span>
                    </div>
                    <div class="flex items-center bg-white border border-slate-200 rounded-xl p-1 gap-1 shrink-0">
                        <button
                            v-for="opt in MUSIC_SOURCE_OPTIONS"
                            :key="opt.value"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-black transition-all duration-200',
                                musicSource === opt.value
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'text-slate-400 hover:text-slate-600',
                            ]"
                            @click="handleMusicSource(opt.value)">
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <div
                    v-if="musicSource === MusicSourceEnum.SYSTEM"
                    class="mt-2 h-[44px] rounded-xl bg-white border border-slate-200 flex items-center px-3">
                    <span class="text-[12px] text-slate-600 flex-1 truncate">
                        {{ modelValue.system_music_name || "当前：系统随机" }}
                    </span>
                    <button class="text-[12px] font-black text-primary" @click="showSystemMusic = true">换一首</button>
                </div>

                <upload
                    v-if="musicSource === MusicSourceEnum.CUSTOM"
                    class="w-full mt-2"
                    type="file"
                    :accept="musicAccept"
                    :multiple="false"
                    :max-size="ADVANCED_MUSIC_SIZE"
                    :show-file-list="false"
                    show-progress
                    @change="handleUploadSuccess">
                    <div
                        class="w-full h-[44px] rounded-xl bg-white border border-dashed border-slate-200 flex items-center px-3 cursor-pointer hover:border-primary transition-all group">
                        <span class="text-primary mr-2 leading-[0] group-hover:scale-110 transition-transform">
                            <Icon name="el-icon-UploadFilled" :size="16" />
                        </span>
                        <span class="text-[12px] font-medium text-slate-600 flex-1 truncate text-left">
                            {{ modelValue.music_name || `选择音乐文件（${musicAcceptText}，${ADVANCED_MUSIC_SIZE}MB 以内）` }}
                        </span>
                    </div>
                </upload>

                <div v-if="musicSource !== MusicSourceEnum.NONE" class="mt-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] text-slate-400">音乐音量</span>
                        <span class="text-[13px] font-black text-primary">{{ musicVolumePercent }}%</span>
                    </div>
                    <ElSlider
                        :model-value="musicVolume"
                        :min="0.1"
                        :max="1"
                        :step="0.1"
                        :show-tooltip="false"
                        @update:model-value="handleMusicVolume($event as number)" />
                </div>
            </div>
        </div>
        <ElDialog v-model="showSystemMusic" title="系统音乐" width="420px" append-to-body @closed="stopPreview">
            <div class="flex flex-col gap-2 max-h-[420px] overflow-y-auto">
                <button
                    type="button"
                    class="system-music-row"
                    :class="!modelValue.system_music_id ? 'border-primary text-primary bg-[#0065fb]/5' : 'border-slate-200 text-slate-700'"
                    @click="onSystemMusic('', '')">
                    <span class="min-w-0 flex-1 truncate text-left">系统随机</span>
                </button>
                <div
                    v-for="item in systemMusics"
                    :key="item.id"
                    class="system-music-row cursor-pointer"
                    :class="modelValue.system_music_id === item.id ? 'border-primary text-primary bg-[#0065fb]/5' : 'border-slate-200 text-slate-700'"
                    @click="pickSystemTrack(item)">
                    <button
                        type="button"
                        class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center bg-primary"
                        @click.stop="playSystem(item)">
                        <Icon
                            :name="playingId === item.id && !previewPaused ? 'el-icon-VideoPause' : 'el-icon-VideoPlay'"
                            color="#fff"
                            :size="16" />
                    </button>
                    <div class="min-w-0 flex-1 text-left">
                        <span class="block truncate text-[13px]">{{ item.name }}</span>
                        <span class="block text-[11px] text-slate-400">{{ formatDuration(item.duration_sec) }}</span>
                    </div>
                </div>
                <div v-if="systemMusicLoaded && !systemMusics.length" class="text-[12px] text-slate-400 py-6 text-center">
                    暂无上架曲目，将使用系统随机
                </div>
            </div>
        </ElDialog>
    </div>
</template>

<script setup lang="ts">
import { getSystemMusicList } from "@/api/digital_human";
import { MusicSourceEnum } from "@/pages/app/digital_human/_enums";
import { getValidUploadFileData } from "@/pages/app/digital_human/_hooks/useUpload";
import {
    ADVANCED_MUSIC_ACCEPT,
    ADVANCED_MUSIC_SIZE,
    ADVANCED_MUSIC_VOLUME_DEFAULT,
    type AdvancedSetting,
    type PackSwitchSetting,
} from "@/pages/app/digital_human/_config/advanced-setting";

type PackSwitchKey = keyof PackSwitchSetting;
/** 宿主可以是混剪的 extra，也可以是纯口播的 packaging，组件只认里面这几个键 */
type SettingValue = PackSwitchSetting & Partial<AdvancedSetting> & Record<string, any>;

const props = withDefaults(
    defineProps<{
        modelValue: SettingValue;
        /** 背景音乐只在没有独立音乐入口的页面显示（目前只有数字人纯口播） */
        showMusic?: boolean;
    }>(),
    {
        showMusic: false,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: SettingValue): void;
}>();

const PACK_ITEMS: Array<{ key: PackSwitchKey; label: string; desc: string }> = [
    { key: "header_switch", label: "标题包装", desc: "画面顶部大字标题，带底色块或花字" },
    { key: "material_switch", label: "素材包装", desc: "转场、运镜特效、滤镜色调" },
    { key: "subtitle_switch", label: "字幕包装", desc: "口播字幕的字体、描边与底色条" },
    { key: "keyword_switch", label: "关键词包装", desc: "念到重点字时放大弹出，配音效" },
];

const PACK_SHORT_LABEL: Record<PackSwitchKey, string> = {
    header_switch: "标题",
    material_switch: "素材",
    subtitle_switch: "字幕",
    keyword_switch: "关键词",
};

const MUSIC_SOURCE_OPTIONS = [
    { value: MusicSourceEnum.SYSTEM, label: "系统推荐" },
    { value: MusicSourceEnum.CUSTOM, label: "自己上传" },
    { value: MusicSourceEnum.NONE, label: "不要音乐" },
];

const MUSIC_SOURCE_TIPS: Record<MusicSourceEnum, string> = {
    [MusicSourceEnum.SYSTEM]: "每次从系统曲库随机一首",
    [MusicSourceEnum.CUSTOM]: "上传一首自己的音乐",
    [MusicSourceEnum.NONE]: "成片只有口播，没有背景音乐",
};

const expanded = ref(false);

const musicAccept = ADVANCED_MUSIC_ACCEPT.map((item) => `.${item}`).join(",");
const musicAcceptText = ADVANCED_MUSIC_ACCEPT.map((item) => item.toUpperCase()).join(" / ");

const musicSource = computed(() => props.modelValue.music_source ?? MusicSourceEnum.SYSTEM);
const musicVolume = computed(() => props.modelValue.music_volume ?? ADVANCED_MUSIC_VOLUME_DEFAULT);
const musicVolumePercent = computed(() => Math.round(musicVolume.value * 100));

const showSystemMusic = ref(false);
const systemMusics = ref<any[]>([]);
const systemMusicLoaded = ref(false);

const musicTip = computed(() => {
    if (musicSource.value === MusicSourceEnum.CUSTOM && props.modelValue.music_name) {
        return `已选：${props.modelValue.music_name}`;
    }
    if (musicSource.value === MusicSourceEnum.SYSTEM && props.modelValue.system_music_name) {
        return `已指定：${props.modelValue.system_music_name}`;
    }
    return MUSIC_SOURCE_TIPS[musicSource.value];
});

const isOn = (key: PackSwitchKey): boolean => props.modelValue[key] !== false;

// 折叠时也要一眼看出配了什么，摘要跟着开关和音乐来源走
const summary = computed(() => {
    const off = PACK_ITEMS.filter((item) => !isOn(item.key));
    const packText =
        off.length === 0
            ? `${PACK_ITEMS.length} 项包装全开`
            : off.length === PACK_ITEMS.length
              ? "包装项已全部关闭"
              : `已关闭：${off.map((item) => PACK_SHORT_LABEL[item.key]).join("、")}`;
    if (!props.showMusic) return packText;
    const musicText =
        musicSource.value === MusicSourceEnum.CUSTOM
            ? props.modelValue.music_name || "自己上传"
            : musicSource.value === MusicSourceEnum.SYSTEM && props.modelValue.system_music_name
              ? props.modelValue.system_music_name
              : MUSIC_SOURCE_OPTIONS.find((item) => item.value === musicSource.value)?.label;
    return `${packText} · 背景音乐：${musicText}`;
});

const patch = (value: Partial<SettingValue>): void => {
    emit("update:modelValue", { ...props.modelValue, ...value });
};

const handleSwitch = (key: PackSwitchKey, value: boolean): void => {
    patch({ [key]: value } as Partial<SettingValue>);
};

const handleMusicSource = (value: MusicSourceEnum): void => {
    if (musicSource.value === value) return;
    patch({
        music_source: value,
        ...(value === MusicSourceEnum.SYSTEM
            ? {}
            : { system_music_id: "", system_music_name: "", system_music_url: "" }),
    });
};

const onSystemMusic = (id: string, name: string, audioUrl = ""): void => {
    patch({
        music_source: MusicSourceEnum.SYSTEM,
        system_music_id: id,
        system_music_name: name,
        system_music_url: id ? audioUrl : "",
    });
    showSystemMusic.value = false;
};

const pickSystemTrack = (item: { id: string; name?: string; audio_url?: string }): void => {
    if (props.modelValue.system_music_id === item.id) {
        onSystemMusic("", "");
        return;
    }
    onSystemMusic(String(item.id), String(item.name || ""), String(item.audio_url || ""));
};

const formatDuration = (sec: number): string => {
    const total = Math.max(0, Math.floor(Number(sec) || 0));
    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, "0")}`;
};

const playingId = ref("");
const previewPaused = ref(true);
let previewAudio: HTMLAudioElement | null = null;

const getPreviewAudio = (): HTMLAudioElement | null => {
    if (!import.meta.client) return null;
    if (!previewAudio) {
        previewAudio = new Audio();
        previewAudio.onended = () => {
            playingId.value = "";
            previewPaused.value = true;
        };
    }
    return previewAudio;
};

const stopPreview = (): void => {
    previewAudio?.pause();
    previewPaused.value = true;
};

const playSystem = (item: { id: string; audio_url?: string }): void => {
    const audio = getPreviewAudio();
    const url = item.audio_url || "";
    if (!audio || !url) return;
    if (playingId.value === item.id) {
        if (audio.paused) {
            audio.play();
            previewPaused.value = false;
        } else {
            audio.pause();
            previewPaused.value = true;
        }
        return;
    }
    playingId.value = item.id;
    audio.src = url;
    audio.play();
    previewPaused.value = false;
};

watch(showSystemMusic, async (open) => {
    if (!open) {
        stopPreview();
        return;
    }
    if (systemMusicLoaded.value) return;
    try {
        const res = await getSystemMusicList();
        systemMusics.value = Array.isArray(res) ? res : [];
    } catch {
        systemMusics.value = [];
    } finally {
        systemMusicLoaded.value = true;
    }
});

onUnmounted(() => {
    stopPreview();
    if (previewAudio) previewAudio.src = "";
});

const handleMusicVolume = (value: number): void => {
    patch({ music_volume: Number(value.toFixed(1)) });
};

const handleUploadSuccess = (res: any): void => {
    const data = getValidUploadFileData(res);
    if (!data) return;
    patch({ music_url: data.uri || data.url, music_name: data.name || res?.name || "" });
};
</script>

<style scoped lang="scss">
.system-music-row {
    @apply h-14 shrink-0 rounded-xl border px-3 flex items-center gap-3 text-[13px];
}
</style>
