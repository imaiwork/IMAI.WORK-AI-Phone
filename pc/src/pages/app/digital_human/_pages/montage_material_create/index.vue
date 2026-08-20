<template>
    <div class="flex gap-x-3 h-full min-w-[1000px] overflow-hidden">
        <div class="flex-1 h-full">
            <material-group-panel ref="materialGroupPanelRef" v-model="formData.materialGroups" />
        </div>

        <div class="basis-[43%] bg-white flex flex-col relative flex-shrink-0 rounded-[20px] p-6 border border-br">
            <header class="mb-5">
                <h2 class="text-[24px] font-medium text-slate-800 tracking-tight">生成设置</h2>
                <div class="h-1 w-12 bg-primary rounded-full mt-2"></div>
            </header>
            <ElScrollbar class="flex-1 -mr-4 pr-4">
                <div class="flex flex-col gap-3">
                    <div class="px-5 py-2 rounded-2xl flex items-center gap-x-3 bg-slate-50 border border-br">
                        <div class="text-[13px] font-black text-[#64748B]">视频名称</div>
                        <div class="w-[1px] h-3 bg-[#E2E8F0]"></div>
                        <div class="flex-1">
                            <ElInput
                                v-model="formData.name"
                                class="custom-input"
                                placeholder="请输入名称"
                                maxlength="20"
                                :input-style="{
                                    textAlign: 'right',
                                    fontSize: '15px',
                                    fontWeight: '900',
                                    color: '#1E293B',
                                }"
                                clearable />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider ml-1">
                            口播来源
                        </label>
                        <div class="grid grid-cols-2 gap-2 p-1 bg-white border border-slate-200 rounded-xl">
                            <button
                                v-for="item in copywriterTypeIndexList"
                                :key="item.type"
                                type="button"
                                :class="[
                                    'h-8 rounded-lg text-xs font-black transition-all flex items-center justify-center gap-1.5',
                                    copywritingMode === item.type
                                        ? 'bg-primary text-white'
                                        : 'text-slate-400 hover:text-slate-600',
                                ]"
                                @click="handleCopywritingModeChange(item.type)">
                                <Icon :name="item.icon" :size="12" />
                                {{ item.name }}
                            </button>
                        </div>
                    </div>

                    <!-- 文案合成 -->
                    <section
                        v-if="copywritingMode === CopywritingTypeEnum.TEXT"
                        class="bg-slate-50 rounded-[24px] border border-br overflow-hidden flex flex-col">
                        <montage-copywriting-editor
                            ref="copywritingEditorRef"
                            v-model="formData.copywriting"
                            :prompt-type="CreateVideoTypeEnum.MATERIAL_MIX" />
                    </section>

                    <!-- 上传音频 -->
                    <section v-else class="bg-slate-50 rounded-[24px] border border-br overflow-hidden flex flex-col">
                        <div
                            class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-[#f8fafc]/80">
                            <span class="text-[13px] font-black text-slate-500 uppercase tracking-wider">口播音频</span>
                            <span class="text-[10px] text-slate-300">mp3 / wav / m4a · 最大 50MB</span>
                        </div>

                        <ElScrollbar v-if="formData.audio.length" max-height="300px">
                            <div class="flex flex-col gap-2 p-3 pb-0">
                                <div
                                    v-for="(item, index) in formData.audio"
                                    :key="item.id"
                                    class="relative flex items-center gap-3 px-3 py-2.5 rounded-2xl bg-white border border-slate-100 group">
                                    <div
                                        class="relative shrink-0 w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400">
                                        <Icon name="el-icon-Headset" :size="16" />
                                        <div
                                            v-if="audioPlayingIndex === index"
                                            class="absolute -bottom-1 -right-1 flex gap-[2px] items-end bg-white rounded-md px-1 py-0.5 shadow-sm border border-slate-100">
                                            <span class="audio-bar bar1"></span>
                                            <span class="audio-bar bar2"></span>
                                            <span class="audio-bar bar3"></span>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-[10px] text-slate-300 mt-0.5">
                                            #{{ String(index + 1).padStart(2, "0") }}
                                        </div>
                                        <div class="text-xs font-black text-slate-700 line-clamp-1 break-all">
                                            {{ item.name }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button
                                            class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center transition-all"
                                            :class="
                                                audioPlayingIndex === index
                                                    ? 'bg-slate-500 text-white'
                                                    : 'bg-slate-50 hover:bg-slate-100 text-slate-400'
                                            "
                                            @click="toggleAudioPlay(index)">
                                            <Icon
                                                :name="
                                                    audioPlayingIndex === index
                                                        ? 'el-icon-VideoPause'
                                                        : 'el-icon-VideoPlay'
                                                "
                                                :size="12" />
                                        </button>
                                        <upload
                                            type="file"
                                            show-progress
                                            :accept="UPLOAD_AUDIO_ACCEPT"
                                            :show-file-list="false"
                                            :max-size="UPLOAD_AUDIO_MAX_SIZE"
                                            @success="handleAudioReplace($event, index)">
                                            <button
                                                type="button"
                                                class="w-7 h-7 rounded-lg bg-slate-50 hover:bg-blue-50 hover:text-blue-500 flex items-center justify-center text-slate-400 transition-all"
                                                title="替换">
                                                <Icon name="el-icon-Refresh" :size="12" />
                                            </button>
                                        </upload>
                                        <button
                                            class="shrink-0 w-7 h-7 rounded-lg bg-slate-50 hover:bg-red-50 hover:text-red-500 flex items-center justify-center text-slate-400 transition-all"
                                            @click="handleRemoveAudio(index)">
                                            <Icon name="el-icon-Delete" :size="12" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </ElScrollbar>

                        <div class="p-3">
                            <upload
                                class="w-full"
                                type="file"
                                drag
                                show-progress
                                :accept="UPLOAD_AUDIO_ACCEPT"
                                :show-file-list="false"
                                :max-size="UPLOAD_AUDIO_MAX_SIZE"
                                @success="handleAudioUploadSuccess">
                                <div
                                    class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl border-2 border-dashed border-slate-200 bg-white cursor-pointer transition-all duration-200 group hover:border-primary hover:bg-[#0065fb]/5">
                                    <div
                                        class="w-8 h-8 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-300 group-hover:text-primary group-hover:bg-[#0065fb]/10 group-hover:border-[#0065fb]/20 transition-all duration-200 shrink-0">
                                        <Icon name="el-icon-Plus" :size="16" />
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <span
                                            class="text-[13px] font-black text-slate-400 group-hover:text-primary transition-colors duration-200">
                                            {{ formData.audio.length ? "继续添加音频" : "点击上传口播音频" }}
                                        </span>
                                        <span class="text-[10px] text-slate-300 font-medium"
                                            >支持 MP3 / WAV / M4A · 最大 50MB</span
                                        >
                                    </div>
                                </div>
                            </upload>
                        </div>
                    </section>

                    <video-cover-upload v-model="formData.cover" />

                    <section class="space-y-3">
                        <div class="bg-slate-50 rounded-[20px] border border-br overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 bg-[#f8fafc]/80">
                                <span class="text-[13px] font-black text-slate-500 uppercase tracking-wider"
                                    >使用设置</span
                                >
                            </div>

                            <div
                                v-for="row in usageToggleRows"
                                :key="row.key"
                                class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[13px] font-medium text-slate-700">{{ row.label }}</span>
                                    <span class="text-[10px] text-slate-400">{{ row.desc }}</span>
                                </div>
                                <div class="flex items-center bg-white border border-slate-200 rounded-xl p-1 gap-1">
                                    <button
                                        v-for="(btn, idx) in row.options"
                                        :key="idx"
                                        @click="(formData.extra as any)[row.key] = idx"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-xs font-black transition-all duration-200',
                                            (formData.extra as any)[row.key] === idx
                                                ? 'bg-primary text-white shadow-sm'
                                                : 'text-slate-400 hover:text-slate-600',
                                        ]">
                                        {{ btn }}
                                    </button>
                                </div>
                            </div>

                            <!-- 视频风格 -->
                            <div class="px-4 py-3 border-b border-slate-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[13px] font-medium text-slate-700">视频风格</span>
                                        <span class="text-[10px] text-slate-400">混剪时使用的剪辑风格</span>
                                    </div>
                                    <div
                                        class="flex items-center bg-white border border-slate-200 rounded-xl p-1 gap-1">
                                        <button
                                            v-for="(label, idx) in ['随机', '手动选择']"
                                            :key="idx"
                                            @click="formData.extra.clip = idx"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-xs font-black transition-all duration-200',
                                                formData.extra.clip === idx
                                                    ? 'bg-primary text-white shadow-sm'
                                                    : 'text-slate-400 hover:text-slate-600',
                                            ]">
                                            {{ label }}
                                        </button>
                                    </div>
                                </div>
                                <div
                                    v-if="formData.extra.clip === 1"
                                    class="mt-2 h-[44px] rounded-xl bg-white border border-slate-200 flex items-center px-3 cursor-pointer hover:border-primary transition-all group"
                                    @click="openClipStyleDialog">
                                    <span
                                        class="text-primary mr-2 group-hover:scale-110 transition-transform leading-[0]">
                                        <Icon name="el-icon-Film" :size="16" />
                                    </span>
                                    <span class="text-[12px] font-medium text-slate-600 flex-1">
                                        <template v-if="formData.clip.length > 0">
                                            已选
                                            <span class="text-primary font-black">{{ formData.clip.length }}</span>
                                            个风格
                                        </template>
                                        <template v-else>点击选择视频风格</template>
                                    </span>
                                    <Icon name="el-icon-ArrowRight" color="var(--slate-300)" :size="14" />
                                </div>
                            </div>

                            <!-- BGM -->
                            <div class="px-4 py-3 border-b border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[13px] font-medium text-slate-700">背景音乐 (BGM)</span>
                                        <span class="text-[10px] text-slate-400">为视频添加背景配乐</span>
                                    </div>
                                </div>
                                <div
                                    class="h-[44px] rounded-xl bg-white border border-slate-200 flex items-center px-3 cursor-pointer hover:border-primary transition-all group"
                                    @click="openMusicDialog">
                                    <span
                                        class="text-primary mr-2 group-hover:scale-110 transition-transform leading-[0]">
                                        <Icon name="el-icon-Headset" :size="16" />
                                    </span>
                                    <span class="text-[12px] font-medium text-slate-600 flex-1 truncate">
                                        {{
                                            formData.music.length > 0
                                                ? `已选 ${formData.music.length} 首音乐`
                                                : formData.extra.ai_music
                                                  ? "AI音乐库"
                                                  : "无"
                                        }}
                                    </span>
                                    <Icon name="el-icon-ArrowRight" color="var(--slate-300)" :size="14" />
                                </div>
                            </div>

                            <div class="px-4 py-3 border-b border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[13px] font-medium text-slate-700">口播音色</span>
                                        <span class="text-[10px] text-slate-400">数字人口播时使用的音色</span>
                                    </div>
                                </div>
                                <div
                                    class="h-[44px] rounded-xl bg-white border border-slate-200 flex items-center px-3 cursor-pointer hover:border-primary transition-all group"
                                    @click="openToneDialog">
                                    <span
                                        class="text-primary mr-2 group-hover:scale-110 transition-transform leading-[0]">
                                        <Icon name="el-icon-Microphone" :size="16" />
                                    </span>
                                    <span class="flex-1">
                                        <span
                                            class="text-[12px] font-medium text-slate-600 truncate"
                                            v-if="voiceList.length > 0">
                                            {{ voiceList[0].name }}
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-1.5 rounded-lg bg-[#0065fb]/10 text-primary text-[10px] font-medium">
                                            请选择音色
                                        </span>
                                    </span>
                                    <template v-if="voiceList.length > 0">
                                        <button
                                            class="w-5 h-5 rounded-full hover:bg-red-50 flex items-center justify-center text-slate-300 hover:text-red-400 transition-all mr-1"
                                            @click.stop="voiceList = []">
                                            <Icon name="el-icon-Close" :size="10" />
                                        </button>
                                    </template>
                                    <Icon name="el-icon-ArrowRight" color="var(--slate-300)" :size="14" />
                                </div>
                            </div>

                            <div class="px-4 py-3 border-b border-slate-100">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[13px] font-medium text-slate-700">BGM 音量</span>
                                        <span class="text-[10px] text-slate-400">背景音乐的音量大小</span>
                                    </div>
                                    <span class="text-[13px] font-black text-primary">
                                        {{ Math.round(formData.extra.volume * 100) }}%
                                    </span>
                                </div>
                                <ElSlider
                                    v-model="formData.extra.volume"
                                    :min="0"
                                    :max="1"
                                    :step="0.01"
                                    :show-tooltip="false" />
                            </div>

                            <div class="flex items-center justify-between px-4 py-3">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[13px] font-medium text-slate-700">素材原声</span>
                                    <span class="text-[10px] text-slate-400">是否保留参考素材的原始声音</span>
                                </div>
                                <ElSwitch v-model="formData.extra.soundSwitch" inactive-color="#e2e8f0" />
                            </div>
                        </div>
                    </section>

                    <div
                        class="bg-slate-50 rounded-[20px] border border-br px-4 py-3 flex items-center justify-between">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[13px] font-medium text-slate-700">生成数量</span>
                            <span class="text-[10px] text-slate-400">每次任务生成的视频数量</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="handleMinusVideoCount('minus')"
                                :disabled="formData.extra.video_count <= 1"
                                class="w-8 h-8 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:border-primary hover:text-primary transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                <Icon name="el-icon-Minus" :size="14" />
                            </button>
                            <input
                                v-model="formData.extra.video_count"
                                v-number-input="{ min: 1, max: 99, decimal: 0 }"
                                type="number"
                                class="w-12 h-8 text-center text-[15px] font-black text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-[#0065fb]/10 transition-all [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                            <button
                                @click="handleMinusVideoCount('add')"
                                :disabled="formData.extra.video_count >= 99"
                                class="w-8 h-8 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:border-primary hover:text-primary transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                <Icon name="el-icon-Plus" :size="14" />
                            </button>
                        </div>
                    </div>
                </div>
            </ElScrollbar>

            <div class="mt-3 flex items-stretch gap-2.5 p-3 bg-slate-50 rounded-[20px] border border-br">
                <div
                    class="flex items-center gap-2.5 bg-white border border-br rounded-[14px] px-2.5 cursor-pointer hover:border-[#0065fb]/30 transition shrink-0 h-full"
                    @click="openTokensCostDialog">
                    <div
                        class="w-[30px] h-[30px] rounded-[10px] bg-gradient-to-br from-[#0065fb] to-[#0ea5e9] flex items-center justify-center shrink-0">
                        <Icon name="el-icon-StarFilled" color="#fff" :size="16" />
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[12px] font-medium text-slate-700 leading-none">算力消耗</span>
                        <span class="text-[10px] text-slate-400">点击查看详细计费</span>
                    </div>
                    <Icon name="el-icon-ArrowRight" color="#94a3b8" :size="13" />
                </div>

                <div class="w-px bg-slate-200 self-stretch shrink-0"></div>

                <ElButton
                    class="flex-1 !rounded-[14px] !border-0 self-stretch !h-auto"
                    type="primary"
                    size="large"
                    :loading="isSubmitting"
                    :disabled="isSubmitting"
                    @click="handleCreateVideo">
                    <template v-if="isSubmitting">
                        <span class="text-white font-[1000] text-[15px] tracking-wide">生成中...</span>
                    </template>
                    <template v-else>
                        <Icon name="el-icon-VideoCamera" color="#fff" :size="20" />
                        <span class="text-white font-[1000] text-[15px] tracking-wide ml-2">
                            生成视频（{{ formData.extra.video_count }}个）
                        </span>
                    </template>
                </ElButton>
            </div>
        </div>
    </div>

    <choose-tone
        v-if="showToneDialog"
        ref="chooseToneRef"
        :limit="1"
        :model-version="toneModelVersion"
        :active-tone="voiceList.length > 0 ? voiceList[0] : null"
        :show-free-tone="false"
        @confirm="handleToneConfirm"
        @close="showToneDialog = false" />

    <montage-styles-choose
        v-if="showClipStyleDialog"
        ref="clipStyleDialogRef"
        :type="MontageStylesType.MATERIAL"
        :selected="formData.clip"
        @confirm="handleClipStyleConfirm"
        @close="showClipStyleDialog = false" />

    <choose-music
        v-if="showMusicDialog"
        ref="chooseMusicRef"
        @confirm="handleMusicConfirm"
        @close="showMusicDialog = false" />

    <cost-pop
        ref="costPopRef"
        v-if="showTokensCost"
        :type="MontageTypeEnum.MATERIAL_MIX"
        @close="showTokensCost = false" />
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { useUserStore } from "@/stores/user";
import { createShanjianTask } from "@/api/digital_human";
import {
    DigitalHumanModelVersionEnum,
    MontageTypeEnum,
    SidebarTypeEnum,
    CreateVideoTypeEnum,
    MontageStylesType,
} from "@/pages/app/digital_human/_enums";
import { montageUploadConfig } from "@/pages/app/digital_human/_config";
import MaterialGroupPanel from "@/pages/app/digital_human/_components/material-group-panel.vue";
import MontageCopywritingEditor from "@/pages/app/digital_human/_components/montage-copywriting-editor.vue";
import MontageStylesChoose from "@/pages/app/digital_human/_components/montage-styles-choose.vue";
import ChooseMusic from "@/pages/app/digital_human/_components/choose-music.vue";
import ChooseTone from "@/pages/app/digital_human/_components/choose-tone.vue";
import VideoCoverUpload from "@/pages/app/digital_human/_components/video-cover-upload.vue";
import CostPop from "@/pages/app/digital_human/_components/cost-pop.vue";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

// ──────────────────────────────────────────────────────────────
// 类型定义
// ──────────────────────────────────────────────────────────────
interface MaterialItem {
    url: string;
    type: string;
    pic: string;
    duration: number;
}
interface MaterialGroup {
    id: string;
    name: string;
    materialList: MaterialItem[];
}
interface CopywritingAudio {
    id: number;
    url: string;
    name: string;
}

enum CopywritingTypeEnum {
    TEXT = "text",
    AUDIO = "audio",
}

// ──────────────────────────────────────────────────────────────
// 表单数据
// ──────────────────────────────────────────────────────────────
const formData = reactive<{
    materialGroups: MaterialGroup[];
    copywriting: any[];
    name: string;
    shanjian_type: MontageTypeEnum;
    voice: any[];
    music: any[];
    clip: any[];
    extra: {
        ai_music: boolean;
        volume: number;
        soundSwitch: boolean;
        human: number;
        music: number;
        clip: number;
        video_count: number;
    };
    audio: CopywritingAudio[];
    cover: string;
}>({
    name: dayjs().format("YYYYMMDDHHmm") + "素材混剪",
    materialGroups: [],
    copywriting: [],
    shanjian_type: MontageTypeEnum.MATERIAL_MIX,
    voice: [],
    music: [],
    clip: [],
    extra: { ai_music: true, volume: 0.5, soundSwitch: false, human: 0, music: 0, clip: 0, video_count: 1 },
    audio: [],
    cover: "",
});

const copywritingMode = ref<CopywritingTypeEnum>(CopywritingTypeEnum.TEXT);
const audioPlayingIndex = ref<number>(-1);

const copywriterTypeIndexList = [
    { name: "文案合成", icon: "el-icon-Document", type: CopywritingTypeEnum.TEXT },
    { name: "上传音频", icon: "el-icon-Microphone", type: CopywritingTypeEnum.AUDIO },
];

const voiceList = ref<any[]>([]);

const usageToggleRows = [
    { key: "music", label: "背景音乐使用", desc: "多首音乐时的播放顺序", options: ["按顺序", "随机"] },
];

const copywritingEditorRef = shallowRef<InstanceType<typeof MontageCopywritingEditor>>();

const materialGroupPanelRef = shallowRef<InstanceType<typeof MaterialGroupPanel>>();

const toneModelVersion = computed(() =>
    copywritingMode.value === CopywritingTypeEnum.AUDIO
        ? DigitalHumanModelVersionEnum.SHANJIAN
        : `${DigitalHumanModelVersionEnum.SHANJIAN},${DigitalHumanModelVersionEnum.MINIMAX_HD},${DigitalHumanModelVersionEnum.MINIMAX_TURBO}`,
);

const handleCopywritingModeChange = (mode: CopywritingTypeEnum) => {
    if (mode === copywritingMode.value) return;

    if (mode === CopywritingTypeEnum.AUDIO) {
        // 当前音色不是 SHANJIAN 则清空
        if (voiceList.value.length > 0 && voiceList.value[0].model_version !== DigitalHumanModelVersionEnum.SHANJIAN) {
            voiceList.value = [];
        }
    }

    copywritingMode.value = mode;
};

// ──────────────────────────────────────────────────────────────
// 口播音频上传
// ──────────────────────────────────────────────────────────────
const UPLOAD_AUDIO_ACCEPT = ".mp3,.wav,.m4a,.MP3,.WAV,.M4A";
const UPLOAD_AUDIO_MAX_SIZE = 50 * 1024 * 1024;

const { play, pause, setUrl, pauseAll, isPlaying } = useAudio();

const handleAudioUploadSuccess = (res: any) => {
    const { uri, name, id } = res.data || {};
    if (!uri) return;
    formData.audio.push({ id, url: uri, name });
};

const handleAudioReplace = (res: any, index: number) => {
    const { uri, name, id } = res.data || {};
    if (!uri) return;
    formData.audio[index] = { id, url: uri, name };
};

const toggleAudioPlay = (index: number) => {
    const audio = formData.audio[index];
    if (isPlaying.value && audioPlayingIndex.value !== index) pauseAll();
    if (!isPlaying.value) {
        if (audioPlayingIndex.value !== index) setUrl(audio.url);
        play();
        audioPlayingIndex.value = index;
    } else {
        pause();
        audioPlayingIndex.value = -1;
    }
};

const handleRemoveAudio = (index: number) => {
    formData.audio.splice(index, 1);
    pauseAll();
};

// ──────────────────────────────────────────────────────────────
// 背景音乐
// ──────────────────────────────────────────────────────────────
const showMusicDialog = ref(false);
const chooseMusicRef = shallowRef<InstanceType<typeof ChooseMusic>>();
const openMusicDialog = async () => {
    showMusicDialog.value = true;
    await nextTick();
    chooseMusicRef.value?.open();
    chooseMusicRef.value?.setSelected(formData.music, formData.extra.ai_music);
};
const handleMusicConfirm = (data: { music: any[]; ai_music: boolean }) => {
    formData.music = data.music;
    formData.extra.ai_music = data.ai_music;
};

// ──────────────────────────────────────────────────────────────
// 口播音色
// ──────────────────────────────────────────────────────────────
const showToneDialog = ref(false);
const chooseToneRef = shallowRef<InstanceType<typeof ChooseTone>>();
const openToneDialog = async () => {
    showToneDialog.value = true;
    await nextTick();
    chooseToneRef.value?.open();
};
const handleToneConfirm = (tone: any) => {
    if (!tone) {
        voiceList.value = [];
    } else {
        voiceList.value = [
            { voice_id: tone.voice_id, voice_url: tone.voice_urls, name: tone.name, model_version: tone.model_version },
        ];
    }
};

// ──────────────────────────────────────────────────────────────
// 视频风格
// ──────────────────────────────────────────────────────────────
const showClipStyleDialog = ref(false);
const clipStyleDialogRef = shallowRef<InstanceType<typeof MontageStylesChoose>>();
const openClipStyleDialog = () => {
    showClipStyleDialog.value = true;
    nextTick(() => clipStyleDialogRef.value?.open());
};
const handleClipStyleConfirm = (data: string[]) => {
    if (data.length === 0) return;
    formData.clip = data;
    showClipStyleDialog.value = false;
};

// ──────────────────────────────────────────────────────────────
// 生成数量
// ──────────────────────────────────────────────────────────────
const handleMinusVideoCount = (type: "minus" | "add") => {
    if (type === "minus" && formData.extra.video_count > 1) formData.extra.video_count--;
    if (type === "add" && formData.extra.video_count < 99) formData.extra.video_count++;
};

// ──────────────────────────────────────────────────────────────
// 算力消耗
// ──────────────────────────────────────────────────────────────
const showTokensCost = ref(false);
const costPopRef = shallowRef<InstanceType<typeof CostPop>>();
const openTokensCostDialog = () => {
    showTokensCost.value = true;
    nextTick(() => costPopRef.value?.open());
};

// ──────────────────────────────────────────────────────────────
// 提交校验（含素材为空精准定位）
// ──────────────────────────────────────────────────────────────
const validateBeforeCreate = async (): Promise<boolean> => {
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return false;
    }

    if (!formData.materialGroups.length) {
        feedback.msgWarning("请至少创建一个素材分组");
        return false;
    }

    for (let i = 0; i < formData.materialGroups.length; i++) {
        const group = formData.materialGroups[i];
        if (group.materialList.length === 0) {
            feedback.msgWarning(`第 ${i + 1} 组「${group.name}」还没有添加素材`);
            materialGroupPanelRef.value?.scrollToGroup(group.id);
            return false;
        }
    }

    if (copywritingMode.value === CopywritingTypeEnum.TEXT) {
        const result = await copywritingEditorRef.value?.validate();
        if (!result?.valid) {
            feedback.msgWarning(result?.firstErrorMsg ?? "请完善口播文案");
            return false;
        }
    }

    if (copywritingMode.value === CopywritingTypeEnum.AUDIO && !formData.audio.length) {
        feedback.msgWarning("请至少上传一条口播音频");
        return false;
    }

    if (voiceList.value.length === 0) {
        feedback.msgWarning("请选择口播音色");
        openToneDialog();
        return false;
    }

    if (formData.extra.clip === 1 && formData.clip.length === 0) {
        feedback.msgWarning("已选择手动指定风格，请至少选择一个视频风格");
        openClipStyleDialog();
        return false;
    }

    return true;
};

// ──────────────────────────────────────────────────────────────
// 生成视频
// ──────────────────────────────────────────────────────────────
const isSubmitting = ref(false);

const handleCreateVideo = async () => {
    if (!(await validateBeforeCreate())) return;
    try {
        isSubmitting.value = true;
        const params = {
            name: formData.name,
            material: formData.materialGroups.map((group) =>
                group.materialList.map((item) => ({
                    fileUrl: item.url,
                    type: item.type,
                    cover: item.pic,
                    duration: item.type === "image" ? montageUploadConfig.imageDuration : item.duration,
                })),
            ),
            model_version: voiceList.value[0]?.model_version || DigitalHumanModelVersionEnum.SHANJIAN,
            voice: voiceList.value[0]?.voice_id,
            copywriting:
                copywritingMode.value === CopywritingTypeEnum.TEXT
                    ? formData.copywriting.map(({ title, content }) => ({ title, content }))
                    : [],
            audio: copywritingMode.value === CopywritingTypeEnum.AUDIO ? formData.audio.map((item) => item.url) : [],
            music: formData.music.map((item: any) => item.content),
            clip: formData.clip.map((item: any) => ({ clip_template_id: item })),
            shanjian_type: formData.shanjian_type,
            extra: {
                ...formData.extra,
                volume: formData.extra.volume.toFixed(1),
            },
            pic: formData.cover,
        };
        await createShanjianTask(params);
        handleCreateSuccess();
    } catch (error: any) {
        feedback.msgError(error || "提交失败，请重试");
    } finally {
        isSubmitting.value = false;
    }
};

const handleCreateSuccess = () => {
    useNuxtApp().$confirm({
        title: "任务已提交",
        message: "创建成功，请在历史记录查看",
        confirmButtonText: "前往查看",
        cancelButtonText: "取消",
        onConfirm: () => {
            navigateTo(`/app/digital_human?type=${SidebarTypeEnum.MY_WORKS}`);
        },
        onCancel: () => {
            window.location.reload();
        },
    });
};
</script>

<style lang="scss" scoped>
:deep(.el-upload),
:deep(.el-upload-dragger) {
    width: 100%;
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    border-radius: 16px !important;
}
:deep(.el-upload-dragger:hover) {
    background: transparent !important;
}

.add-material-btn {
    position: relative;
    border: none;
    background: transparent;
    cursor: pointer;
    outline: none;
}

.error-textarea {
    :deep(.el-textarea__inner) {
        border-color: #f87171 !important;
        box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.15) !important;
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.audio-bar {
    display: inline-block;
    width: 2px;
    background: linear-gradient(to top, #7c3aed, #a78bfa);
    border-radius: 1px;
    animation: audio-wave 0.9s ease-in-out infinite;
}
.audio-bar.bar1 {
    height: 4px;
    animation-delay: 0s;
}
.audio-bar.bar2 {
    height: 7px;
    animation-delay: 0.15s;
}
.audio-bar.bar3 {
    height: 5px;
    animation-delay: 0.3s;
}
@keyframes audio-wave {
    0%,
    100% {
        transform: scaleY(0.5);
    }
    50% {
        transform: scaleY(1.2);
    }
}
</style>
