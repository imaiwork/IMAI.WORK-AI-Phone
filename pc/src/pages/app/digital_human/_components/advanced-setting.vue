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
    </div>
</template>

<script setup lang="ts">
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
    [MusicSourceEnum.SYSTEM]: "按文案内容自动匹配曲风",
    [MusicSourceEnum.CUSTOM]: "上传一首自己的音乐",
    [MusicSourceEnum.NONE]: "成片只有口播，没有背景音乐",
};

const expanded = ref(false);

const musicAccept = ADVANCED_MUSIC_ACCEPT.map((item) => `.${item}`).join(",");
const musicAcceptText = ADVANCED_MUSIC_ACCEPT.map((item) => item.toUpperCase()).join(" / ");

const musicSource = computed(() => props.modelValue.music_source ?? MusicSourceEnum.SYSTEM);
const musicVolume = computed(() => props.modelValue.music_volume ?? ADVANCED_MUSIC_VOLUME_DEFAULT);
const musicVolumePercent = computed(() => Math.round(musicVolume.value * 100));

const musicTip = computed(() => {
    if (musicSource.value === MusicSourceEnum.CUSTOM && props.modelValue.music_name) {
        return `已选：${props.modelValue.music_name}`;
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
    patch({ music_source: value });
};

const handleMusicVolume = (value: number): void => {
    patch({ music_volume: Number(value.toFixed(1)) });
};

const handleUploadSuccess = (res: any): void => {
    const data = getValidUploadFileData(res);
    if (!data) return;
    patch({ music_url: data.uri || data.url, music_name: data.name || res?.name || "" });
};
</script>
