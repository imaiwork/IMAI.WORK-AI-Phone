<template>
    <popup
        ref="popupRef"
        title="视频剪辑"
        :async="true"
        width="680px"
        :confirm-loading="isLock"
        :confirm-button-text="templatePickerVisible ? false : '确定'"
        :cancel-button-text="templatePickerVisible ? false : '取消'"
        @confirm="lockFn"
        @close="close">
        <div class="py-2" v-loading="loading">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-[#EAF2FF] flex items-center justify-center flex-shrink-0">
                    <el-icon class="text-primary" :size="20"><VideoCamera /></el-icon>
                </div>
                <div>
                    <div class="text-base font-extrabold text-[#1D2129]">视频剪辑</div>
                    <div class="text-xs text-[#86909C] mt-0.5">视频剪辑将按此规则自动生成内容</div>
                </div>
            </div>

            <!-- ⓪ 工作方式 -->
            <div class="rule-sec">
                <div class="rule-sec-hd">
                    <div class="rule-sec-title">工作方式</div>
                </div>
                <div class="rule-seg">
                    <div
                        v-for="opt in WORK_MODE_OPTIONS"
                        :key="opt.val"
                        class="rule-seg-btn"
                        :class="{ sel: innerState.workMode === opt.val }"
                        @click="innerState.workMode = opt.val">
                        <span>{{ opt.label }}</span>
                    </div>
                </div>
                <div class="rule-note">
                    <div class="rule-note-text">{{ currentWorkModeNote }}</div>
                </div>
            </div>

            <!-- 成品库直发设置 -->
            <template v-if="innerState.workMode === WorkModeEnum.Lib">
                <div class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">使用方式</div>
                    </div>
                    <div class="rule-seg">
                        <div
                            v-for="opt in LIB_USE_MODE_OPTIONS"
                            :key="opt.val"
                            class="rule-seg-btn"
                            :class="{ sel: innerState.libUseMode === opt.val }"
                            @click="innerState.libUseMode = opt.val">
                            <span>{{ opt.label }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="innerState.libUseMode === LibUseModeEnum.Random" class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">随机规则</div>
                    </div>
                    <div class="rule-seg">
                        <div
                            v-for="opt in EDITOR_LIB_RULE_OPTIONS"
                            :key="opt.val"
                            class="rule-seg-btn"
                            :class="{ sel: innerState.libRandomRule === opt.val }"
                            @click="innerState.libRandomRule = opt.val">
                            <span>{{ opt.label }}</span>
                        </div>
                    </div>
                    <div class="rule-note">
                        <div class="rule-note-text">{{ currentEditorLibRuleNote }}</div>
                    </div>
                </div>
            </template>

            <!-- AI 合成模式 -->
            <template v-else>
                <!-- ① 生成类型（多选） -->
                <div class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">生成类型</div>
                        <div class="rule-sec-hint">可多选，将根据每日任务数按顺序轮流生成</div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div
                            v-for="item in SYNTH_TYPES"
                            :key="item.id"
                            class="relative rule-type-card"
                            :class="{ sel: innerState.synthTypes.includes(item.id) }"
                            @click="toggleSynthType(item.id)">
                            <div v-if="innerState.synthTypes.includes(item.id)" class="rule-type-check">
                                <el-icon :size="12" class="text-white"><Check /></el-icon>
                            </div>
                            <el-icon
                                :size="28"
                                :class="innerState.synthTypes.includes(item.id) ? 'text-primary' : 'text-[#9CA3AF]'"
                                class="mb-2">
                                <component :is="item.icon" />
                            </el-icon>
                            <div class="text-sm font-bold">{{ item.label }}</div>
                        </div>
                    </div>
                </div>

                <!-- 视频模板 -->
                <div class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">视频模板</div>
                        <div class="rule-sec-hint">每个生成类型可独立配置，支持多选</div>
                    </div>
                    <div class="rule-tpl-entry" @click="handleOpenTemplatePicker">
                        <div class="rule-tpl-ic">
                            <el-icon :size="18" class="text-primary"><Grid /></el-icon>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-[#1D2129]">模板选择</div>
                            <div class="text-xs text-[#86909C] mt-0.5 truncate">{{ templateSummary }}</div>
                        </div>
                        <el-icon :size="14" class="text-[#C9CDD4]"><ArrowRight /></el-icon>
                    </div>
                </div>

                <!-- 新闻体时长（选中「新闻体混剪」时显示） -->
                <div v-if="hasNews" class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">新闻体时长</div>
                        <div class="rule-sec-hint">范围 {{ NEWS_DURATION.min }}–{{ NEWS_DURATION.max }} 秒</div>
                    </div>
                    <el-input-number
                        v-model="innerState.newsDuration"
                        :min="NEWS_DURATION.min"
                        :max="NEWS_DURATION.max"
                        :step="NEWS_DURATION.step"
                        step-strictly
                        controls-position="right" />
                </div>

                <!-- ② 画面素材（数字人专属） -->
                <div v-if="hasDigitalHuman" class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">
                            画面素材
                            <span class="ml-2 text-xs font-normal text-[#86909C]">(数字人专属)</span>
                        </div>
                    </div>
                    <div class="rule-seg">
                        <div
                            v-for="opt in MATERIAL_SOURCE_OPTIONS"
                            :key="opt.val"
                            class="rule-seg-btn"
                            :class="{ sel: innerState.materialSource === opt.val }"
                            @click="innerState.materialSource = opt.val">
                            <span>{{ opt.label }}</span>
                        </div>
                    </div>
                    <div v-if="isMaterialConflict" class="rule-note rule-note-error">
                        当前素材库为空，使用「纯素材库」将导致任务生成失败。请先上传素材。
                    </div>
                    <div v-else class="rule-note">
                        <div class="rule-note-text">{{ currentMaterialHint.effect }}</div>
                        <div class="rule-note-extra" :class="{ free: currentMaterialHint.costType === 'free' }">
                            {{ currentMaterialHint.cost }}
                        </div>
                    </div>
                </div>

                <!-- ③ 文案来源 -->
                <div class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">文案来源</div>
                    </div>
                    <div class="rule-seg">
                        <div
                            v-for="opt in copySourceOptions"
                            :key="opt.val"
                            class="rule-seg-btn"
                            :class="{
                                sel: innerState.copySource === opt.val,
                                locked: opt.locked,
                            }"
                            @click="!opt.locked && (innerState.copySource = opt.val)">
                            <el-icon v-if="opt.locked" :size="14" class="mr-1 text-[#C0C8D8]"><Lock /></el-icon>
                            <span>{{ opt.label }}</span>
                        </div>
                    </div>
                    <div class="rule-note">
                        <div class="rule-note-text">{{ currentCopyHint }}</div>
                    </div>

                    <!-- 文案库子设置 -->
                    <div v-if="hasCopyLib" class="mt-3">
                        <div class="text-xs font-semibold text-[#4B5563] mb-2">使用方式</div>
                        <div class="rule-seg">
                            <div
                                v-for="opt in LIB_USE_MODE_OPTIONS"
                                :key="opt.val"
                                class="rule-seg-btn"
                                :class="{ sel: innerState.copyLibUseMode === opt.val }"
                                @click="innerState.copyLibUseMode = opt.val">
                                <span>{{ opt.label }}</span>
                            </div>
                        </div>

                        <div v-if="innerState.copyLibUseMode === LibUseModeEnum.Random" class="mt-3">
                            <div class="text-xs font-semibold text-[#4B5563] mb-2">随机规则</div>
                            <div class="rule-seg">
                                <div
                                    v-for="opt in COPY_LIB_RULE_OPTIONS"
                                    :key="opt.val"
                                    class="rule-seg-btn"
                                    :class="{ sel: innerState.copyLibRandomRule === opt.val }"
                                    @click="innerState.copyLibRandomRule = opt.val">
                                    <span>{{ opt.label }}</span>
                                </div>
                            </div>
                            <div class="rule-note">
                                <div class="rule-note-text">{{ currentCopyLibRuleNote }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ④ 视频封面 -->
                <div class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">视频封面</div>
                    </div>
                    <div class="rule-seg">
                        <div
                            v-for="opt in COVER_SOURCE_OPTIONS"
                            :key="opt.val"
                            class="rule-seg-btn"
                            :class="{ sel: innerState.coverSource === opt.val }"
                            @click="innerState.coverSource = opt.val">
                            <span>{{ opt.label }}</span>
                        </div>
                    </div>
                    <div class="rule-note">
                        <div class="rule-note-text">{{ currentCoverHint }}</div>
                    </div>

                    <!-- 手动封面：上传/预览 -->
                    <div v-if="innerState.coverSource === CoverSourceEnum.Manual" class="mt-3">
                        <div v-if="!innerState.coverImage">
                            <material-picker v-model="innerState.coverImage" :limit="1" />
                            <div class="text-xs text-[#86909C] mt-2">支持 JPG、PNG 格式</div>
                        </div>
                        <div v-else class="rule-cover-preview">
                            <el-image :src="innerState.coverImage" fit="cover" class="w-full !h-[200px] rounded-2xl" />
                            <div class="flex items-center justify-center gap-3 mt-2">
                                <el-button size="small" @click="handleReupload">
                                    <el-icon class="mr-1"><Refresh /></el-icon>重新选择
                                </el-button>
                                <el-button size="small" type="danger" plain @click="handleDeleteCover">
                                    <el-icon class="mr-1"><Delete /></el-icon>删除
                                </el-button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ⑤ 剪辑视频音乐来源 -->
                <div class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">剪辑视频音乐来源</div>
                    </div>
                    <div class="rule-seg">
                        <div
                            v-for="opt in MUSIC_SOURCE_OPTIONS"
                            :key="opt.val"
                            class="rule-seg-btn"
                            :class="{ sel: innerState.musicSource === opt.val }"
                            @click="innerState.musicSource = opt.val">
                            <span>{{ opt.label }}</span>
                        </div>
                    </div>
                    <div class="rule-note">
                        <div class="rule-note-text">{{ currentMusicSourceHint }}</div>
                    </div>
                </div>

                <!-- ⑥ 音乐音量（无背景音乐时隐藏） -->
                <div v-if="innerState.musicSource !== MusicSourceEnum.None" class="rule-sec">
                    <div class="rule-sec-hd rule-slider-hd">
                        <div class="rule-sec-title">音乐音量</div>
                        <div class="rule-slider-val">{{ musicVolumePercent }}%</div>
                    </div>
                    <el-slider
                        v-model="musicVolumeSlider"
                        :min="0"
                        :max="10"
                        :step="1"
                        :show-tooltip="false" />
                    <div class="rule-slider-labels">
                        <span v-for="label in MUSIC_VOLUME_LABELS" :key="label" class="rule-slider-label">
                            {{ label }}
                        </span>
                    </div>
                </div>

                <!-- ⑦ 口播语速 -->
                <div class="rule-sec">
                    <div class="rule-sec-hd rule-slider-hd">
                        <div class="rule-sec-title">口播语速</div>
                        <div class="rule-slider-val">{{ speechRateLabel }}</div>
                    </div>
                    <el-slider
                        v-model="speechRateSlider"
                        :min="5"
                        :max="20"
                        :step="1"
                        :show-tooltip="false" />
                    <div class="rule-slider-labels">
                        <span v-for="label in SPEECH_RATE_LABELS" :key="label" class="rule-slider-label">
                            {{ label }}
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </popup>

    <video-template-choose-pop
        v-model="templatePickerVisible"
        :types="activeSynthApiTypes"
        :config="innerState.templateConfig"
        @confirm="handleTemplateConfirm" />
</template>

<script setup lang="ts">
import { computed, reactive, ref, shallowRef, watch } from "vue";
import {
    VideoCamera,
    Check,
    Lock,
    User,
    Tickets,
    Picture,
    Refresh,
    Delete,
    Grid,
    ArrowRight,
} from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import {
    addAiSynthesisRule,
    updateAiSynthesisRule,
    getAiSynthesisRuleDetail,
    getAvatarList,
} from "@/api/ai_application/device/person";
import Popup from "@/components/popup/index.vue";
import feedback from "@/utils/feedback";
import { useLockFn } from "@/hooks/useLockFn";
import VideoTemplateChoosePop from "./video-template-choose-pop.vue";
import { TemplateConfigMap } from "../enums/template";
import {
    ALL_SYNTH_API_TYPES,
    buildTemplateConfigForTypes,
    buildTemplateSummary,
    createDefaultTemplateItem,
    findEmptyCustomType,
    getSynthApiFullLabel,
    normalizeTemplateItem,
} from "../utils/template-config";

// ─── 枚举（与 uniapp material_library/enums 对齐）────────────────────
enum SynthTypeEnum {
    DigitalHuman = "DigitalHuman",
    News = "News",
    Material = "Material",
}
enum MaterialSourceEnum {
    PureAI = "PureAI",
    AILib = "AILib",
    PureLib = "PureLib",
}
enum CopySourceEnum {
    Rewrite = "Rewrite",
    AIGen = "AIGen",
    None = "None",
    Lib = "Lib",
}
enum CoverSourceEnum {
    Default = "Default",
    AI = "AI",
    Manual = "Manual",
}
// ─── UI 联动枚举（工作方式 / 成品库 / 文案库使用方式与重复规则）──────
enum WorkModeEnum {
    Ai = "ai",
    Lib = "lib",
}
enum LibUseModeEnum {
    Random = "random",
    Sequence = "sequence",
}
enum LibRuleEnum {
    Once = "once",
    Repeat = "repeat",
}
/** 背景音乐来源：1 系统 / 2 人设音乐库 / 3 无 */
enum MusicSourceEnum {
    System = 1,
    Persona = 2,
    None = 3,
}

const NEWS_DURATION = { min: 5, max: 300, step: 5, default: 60 } as const;
const MUSIC_VOLUME_DEFAULT = 0.3;
const SPEECH_RATE_DEFAULT = 1.0;
const MUSIC_VOLUME_LABELS = ["静音", "较轻", "适中", "较响", "最大"] as const;
const SPEECH_RATE_LABELS = ["0.5x", "较慢", "标准", "较快", "2x"] as const;

const roundToOne = (value: number): number => Math.round(value * 10) / 10;

const normalizeMusicVolume = (value: unknown): number => {
    const num = Number(value);
    if (!Number.isFinite(num)) return MUSIC_VOLUME_DEFAULT;
    return Math.min(1, Math.max(0, roundToOne(num)));
};

const normalizeSpeechRate = (value: unknown): number => {
    const num = Number(value);
    if (!Number.isFinite(num)) return SPEECH_RATE_DEFAULT;
    return Math.min(2, Math.max(0.5, roundToOne(num)));
};

const normalizeMusicSource = (value: unknown): MusicSourceEnum => {
    const num = Number(value);
    if (num === MusicSourceEnum.Persona || num === MusicSourceEnum.None) return num;
    return MusicSourceEnum.System;
};

const SYNTH_TYPES = [
    { id: SynthTypeEnum.DigitalHuman, label: "数字人口播", icon: User },
    { id: SynthTypeEnum.News, label: "新闻体混剪", icon: Tickets },
    { id: SynthTypeEnum.Material, label: "纯素材混剪", icon: Picture },
];

const MATERIAL_SOURCE_OPTIONS = [
    { val: MaterialSourceEnum.PureAI, label: "纯AI找素材" },
    { val: MaterialSourceEnum.AILib, label: "AI+素材库" },
    { val: MaterialSourceEnum.PureLib, label: "纯素材库" },
];

const COVER_SOURCE_OPTIONS = [
    { val: CoverSourceEnum.Default, label: "默认封面" },
    { val: CoverSourceEnum.AI, label: "AI自动封面" },
    { val: CoverSourceEnum.Manual, label: "手动封面" },
];

const WORK_MODE_OPTIONS = [
    { val: WorkModeEnum.Ai, label: "AI 合成视频" },
    { val: WorkModeEnum.Lib, label: "成品库直发" },
];

const WORK_MODE_NOTES: Record<string, string> = {
    [WorkModeEnum.Ai]: "AI 自动合成数字人口播 / 新闻体混剪 / 纯素材混剪短视频。",
    [WorkModeEnum.Lib]: "从「素材库 · 成品直发库」按规则取用已上传的成品视频，跳过 AI 合成，不消耗算力。",
};

const LIB_USE_MODE_OPTIONS = [
    { val: LibUseModeEnum.Random, label: "随机使用" },
    { val: LibUseModeEnum.Sequence, label: "顺序使用" },
];

const EDITOR_LIB_RULE_OPTIONS = [
    { val: LibRuleEnum.Once, label: "每个成品只用一次" },
    { val: LibRuleEnum.Repeat, label: "可重复使用" },
];

const EDITOR_LIB_RULE_NOTES: Record<string, string> = {
    [LibRuleEnum.Once]: "用完即跳过，避免平台查重。成品用完后该 AI 员工将自动暂停。",
    [LibRuleEnum.Repeat]: "所有成品均参与抽取，成品不会耗尽。",
};

const COPY_LIB_RULE_OPTIONS = [
    { val: LibRuleEnum.Once, label: "每个文案只用一次" },
    { val: LibRuleEnum.Repeat, label: "可重复使用" },
];

const COPY_LIB_RULE_NOTES: Record<string, string> = {
    [LibRuleEnum.Once]: "用完即跳过，避免重复内容触发平台查重。",
    [LibRuleEnum.Repeat]: "所有文案均参与抽取，文案不会耗尽。",
};

const MATERIAL_HINTS: Record<string, { effect: string; cost: string; costType: string }> = {
    [MaterialSourceEnum.PureAI]: {
        effect: "AI全网智能匹配高清素材，画面丰富度最高。",
        cost: "需额外消耗素材搜索算力",
        costType: "warn",
    },
    [MaterialSourceEnum.AILib]: {
        effect: "优先使用您的素材，不足部分由AI智能补充。",
        cost: "需额外消耗少量补充算力",
        costType: "warn",
    },
    [MaterialSourceEnum.PureLib]: {
        effect: "仅使用您上传的素材库，画面内容完全可控。",
        cost: "无额外费用",
        costType: "free",
    },
};

const COPY_HINTS: Record<string, string> = {
    [CopySourceEnum.Rewrite]: "智能提取爆款视频框架，大幅提升上热门概率。",
    [CopySourceEnum.AIGen]: "根据核心词快速裂变，保证极高原创度和产出效率。",
    [CopySourceEnum.None]: "纯画面与音乐展示，不生成任何口播或字幕，适合风景、沉浸式空镜。",
    [CopySourceEnum.Lib]: "从该人设的文案库中按规则取用，已审过的内容直接发，更稳。",
};

const COVER_HINTS: Record<string, string> = {
    [CoverSourceEnum.Default]: "截取视频首帧作为封面，简单直接。",
    [CoverSourceEnum.AI]: "AI 根据视频内容自动生成更适合推荐流的封面。",
    [CoverSourceEnum.Manual]: "上传固定封面图，适合统一账号视觉风格。",
};

const MUSIC_SOURCE_OPTIONS = [
    { val: MusicSourceEnum.System, label: "系统音乐库" },
    { val: MusicSourceEnum.Persona, label: "人设音乐库" },
    { val: MusicSourceEnum.None, label: "无背景音乐" },
];

const MUSIC_SOURCE_HINTS: Record<number, string> = {
    [MusicSourceEnum.System]: "从系统内置的海量正版 BGM 中随机挑选配乐。",
    [MusicSourceEnum.Persona]: "从该人设「素材库 · 音乐库」中随机取用自定义配乐。",
    [MusicSourceEnum.None]: "生成视频时不添加背景音乐，仅保留口播与环境声。",
};

// ─── API 映射 ───────────────────────────────────────────────────────
const SYNTH_TYPE_TO_API: Record<string, number> = {
    [SynthTypeEnum.DigitalHuman]: 1,
    [SynthTypeEnum.Material]: 3,
    [SynthTypeEnum.News]: 4,
};
const MATERIAL_SOURCE_TO_API: Record<string, number> = {
    [MaterialSourceEnum.PureAI]: 1,
    [MaterialSourceEnum.AILib]: 2,
    [MaterialSourceEnum.PureLib]: 3,
};
const COPY_SOURCE_TO_API: Record<string, number> = {
    [CopySourceEnum.Rewrite]: 1,
    [CopySourceEnum.AIGen]: 2,
    [CopySourceEnum.None]: 3,
    [CopySourceEnum.Lib]: 4,
};
const COVER_SOURCE_TO_API: Record<string, number> = {
    [CoverSourceEnum.Default]: 1,
    [CoverSourceEnum.AI]: 2,
    [CoverSourceEnum.Manual]: 3,
};
const LIB_USE_MODE_TO_API: Record<string, number> = {
    [LibUseModeEnum.Random]: 1,
    [LibUseModeEnum.Sequence]: 2,
};
const LIB_RULE_TO_API: Record<string, number> = {
    [LibRuleEnum.Once]: 1,
    [LibRuleEnum.Repeat]: 2,
};

const invertMap = <K extends string, V extends string | number>(map: Record<K, V>) =>
    Object.fromEntries(Object.entries(map).map(([k, v]) => [String(v), k])) as Record<string, K>;

const API_TO_SYNTH_TYPE = invertMap(SYNTH_TYPE_TO_API);
const API_TO_MATERIAL_SOURCE = invertMap(MATERIAL_SOURCE_TO_API);
const API_TO_COPY_SOURCE = invertMap(COPY_SOURCE_TO_API);
const API_TO_COVER_SOURCE = invertMap(COVER_SOURCE_TO_API);
const API_TO_LIB_USE_MODE = invertMap(LIB_USE_MODE_TO_API);
const API_TO_LIB_RULE = invertMap(LIB_RULE_TO_API);

// ─── State ─────────────────────────────────────────────────────────
interface InnerState {
    id: string | number;
    synthTypes: SynthTypeEnum[];
    materialSource: MaterialSourceEnum;
    copySource: CopySourceEnum;
    coverSource: CoverSourceEnum;
    coverImage: string;
    // 工作方式 / 成品库为本地 UI 态；文案库规则走 library_* 接口字段
    workMode: WorkModeEnum;
    libUseMode: LibUseModeEnum;
    libRandomRule: LibRuleEnum;
    newsDuration: number;
    copyLibUseMode: LibUseModeEnum;
    copyLibRandomRule: LibRuleEnum;
    /** 视频模板：key 为 generation_types 数字（1/3/4） */
    templateConfig: TemplateConfigMap;
    /** 背景音乐来源：1 系统 / 2 人设 / 3 无 */
    musicSource: MusicSourceEnum;
    /** 音乐音量 0~1，一位小数 */
    musicVolume: number;
    /** 口播语速 0.5~2，一位小数 */
    speechRate: number;
}

const DEFAULT_INNER_STATE = (): InnerState => ({
    id: "",
    synthTypes: [SynthTypeEnum.DigitalHuman],
    materialSource: MaterialSourceEnum.AILib,
    copySource: CopySourceEnum.Rewrite,
    coverSource: CoverSourceEnum.AI,
    coverImage: "",
    workMode: WorkModeEnum.Ai,
    libUseMode: LibUseModeEnum.Random,
    libRandomRule: LibRuleEnum.Once,
    newsDuration: NEWS_DURATION.default,
    copyLibUseMode: LibUseModeEnum.Random,
    copyLibRandomRule: LibRuleEnum.Once,
    templateConfig: {},
    musicSource: MusicSourceEnum.System,
    musicVolume: MUSIC_VOLUME_DEFAULT,
    speechRate: SPEECH_RATE_DEFAULT,
});

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const loading = ref(false);
const personId = ref<string | number>("");
const isMaterialEmpty = ref(false);
const templatePickerVisible = ref(false);

const innerState = reactive<InnerState>(DEFAULT_INNER_STATE());

const hasDigitalHuman = computed(() => innerState.synthTypes.includes(SynthTypeEnum.DigitalHuman));
const hasNews = computed(() => innerState.synthTypes.includes(SynthTypeEnum.News));
const hasCopyLib = computed(() => innerState.copySource === CopySourceEnum.Lib);

const activeSynthApiTypes = computed(() =>
    innerState.synthTypes.map((t) => SYNTH_TYPE_TO_API[t]).filter((n): n is number => typeof n === "number"),
);

const templateSummary = computed(() =>
    buildTemplateSummary(activeSynthApiTypes.value, innerState.templateConfig),
);

const currentWorkModeNote = computed(() => WORK_MODE_NOTES[innerState.workMode]);
const currentEditorLibRuleNote = computed(() => EDITOR_LIB_RULE_NOTES[innerState.libRandomRule]);
const currentCopyLibRuleNote = computed(() => COPY_LIB_RULE_NOTES[innerState.copyLibRandomRule]);

const canUseNoCopy = computed(
    () =>
        !innerState.synthTypes.includes(SynthTypeEnum.DigitalHuman) &&
        !innerState.synthTypes.includes(SynthTypeEnum.News),
);

const copySourceOptions = computed(() => [
    { val: CopySourceEnum.Rewrite, label: "找爆款仿写", locked: false },
    { val: CopySourceEnum.AIGen, label: "AI直接生成", locked: false },
    { val: CopySourceEnum.Lib, label: "文案库", locked: false },
    // { val: CopySourceEnum.None, label: '无需文案', locked: !canUseNoCopy.value }
]);

const isMaterialConflict = computed(
    () => innerState.materialSource === MaterialSourceEnum.PureLib && isMaterialEmpty.value,
);

const currentMaterialHint = computed(() => MATERIAL_HINTS[innerState.materialSource]);
const currentCopyHint = computed(() => COPY_HINTS[innerState.copySource]);
const currentCoverHint = computed(() => COVER_HINTS[innerState.coverSource]);
const currentMusicSourceHint = computed(
    () => MUSIC_SOURCE_HINTS[innerState.musicSource] || MUSIC_SOURCE_HINTS[MusicSourceEnum.System],
);

const musicVolumePercent = computed(() => Math.round(innerState.musicVolume * 100));
const speechRateLabel = computed(() => `${innerState.speechRate.toFixed(1)}x`);

/** el-slider 整数刻度：音量 0~10 → 0.0~1.0 */
const musicVolumeSlider = computed({
    get: () => Math.round(innerState.musicVolume * 10),
    set: (val: number) => {
        innerState.musicVolume = normalizeMusicVolume(Number(val) / 10);
    },
});

/** el-slider 整数刻度：语速 5~20 → 0.5~2.0 */
const speechRateSlider = computed({
    get: () => Math.round(innerState.speechRate * 10),
    set: (val: number) => {
        innerState.speechRate = normalizeSpeechRate(Number(val) / 10);
    },
});

// 不含数字人 → 强制 materialSource = PureLib；无需文案 lock 时回退
watch(
    () => [...innerState.synthTypes],
    () => {
        if (
            !innerState.synthTypes.includes(SynthTypeEnum.DigitalHuman) &&
            innerState.materialSource !== MaterialSourceEnum.PureLib
        ) {
            innerState.materialSource = MaterialSourceEnum.PureLib;
        }
        if (!canUseNoCopy.value && innerState.copySource === CopySourceEnum.None) {
            innerState.copySource = CopySourceEnum.Rewrite;
        }
        // 原地补默认，取消勾选时保留已选模板
        activeSynthApiTypes.value.forEach((apiType) => {
            const key = String(apiType);
            if (!innerState.templateConfig[key]) {
                innerState.templateConfig[key] = createDefaultTemplateItem(apiType);
            }
        });
    },
    { immediate: true },
);

const toggleSynthType = (id: SynthTypeEnum) => {
    const i = innerState.synthTypes.indexOf(id);
    if (i > -1) {
        if (innerState.synthTypes.length > 1) innerState.synthTypes.splice(i, 1);
        return;
    }
    innerState.synthTypes.push(id);
};

const clampNewsDuration = (value: unknown): number => {
    const num = Math.round(Number(value));
    if (!Number.isFinite(num) || num <= 0) return NEWS_DURATION.default;
    return Math.max(NEWS_DURATION.min, Math.min(NEWS_DURATION.max, num));
};

const handleReupload = () => {
    innerState.coverImage = "";
};

const handleDeleteCover = async () => {
    try {
        await ElMessageBox.confirm("确定删除当前封面图片吗？", "提示", {
            confirmButtonText: "删除",
            cancelButtonText: "取消",
            type: "warning",
        });
        innerState.coverImage = "";
    } catch {
        // canceled
    }
};

const handleOpenTemplatePicker = () => {
    if (!activeSynthApiTypes.value.length) {
        feedback.msgWarning("请先选择生成类型");
        return;
    }
    templatePickerVisible.value = true;
};

const handleTemplateConfirm = (payload: TemplateConfigMap) => {
    if (!payload || typeof payload !== "object") return;
    Object.keys(payload).forEach((key) => {
        const apiType = Number(key);
        innerState.templateConfig[key] = normalizeTemplateItem(apiType, payload[key]);
    });
};

// ─── 数字人检测（无数字人时给降级提示） ─────────────────────────────
const checkDigitalHuman = async (): Promise<boolean> => {
    if (!isAiMode.value || !hasDigitalHuman.value) return true;
    try {
        const res = await getAvatarList({ persona_id: personId.value });
        const lists = res?.lists ?? [];
        if (lists.length) return true;
        try {
            await ElMessageBox.confirm("检测到未配置数字人，实际生成时将自动降级为素材混剪。是否仍然保存？", "提示", {
                confirmButtonText: "继续保存",
                cancelButtonText: "取消",
            });
            return true;
        } catch {
            return false;
        }
    } catch {
        return true;
    }
};

// ─── 校验 / 组装 / 保存 ─────────────────────────────────────────────
const isAiMode = computed(() => innerState.workMode === WorkModeEnum.Ai);

const validate = (): boolean => {
    // 成品库直发模式跳过 AI 合成相关校验
    if (!isAiMode.value) return true;
    if (!innerState.synthTypes.length) {
        feedback.msgWarning("请至少选择一种生成类型");
        return false;
    }
    if (isMaterialConflict.value) {
        feedback.msgWarning("当前素材库为空，请先上传素材或更换画面素材来源");
        return false;
    }
    if (innerState.coverSource === CoverSourceEnum.Manual && !innerState.coverImage) {
        feedback.msgWarning("请上传视频封面");
        return false;
    }
    const emptyType = findEmptyCustomType(activeSynthApiTypes.value, innerState.templateConfig);
    if (emptyType != null) {
        feedback.msgWarning(`「${getSynthApiFullLabel(emptyType)}」请至少选择 1 个模板`);
        return false;
    }
    return true;
};

const buildPayload = () => ({
    id: innerState.id || undefined,
    persona_id: personId.value,
    generation_types: innerState.synthTypes.map((t) => SYNTH_TYPE_TO_API[t]).filter(Boolean),
    visual_material_source: MATERIAL_SOURCE_TO_API[innerState.materialSource],
    copywriting_source: COPY_SOURCE_TO_API[innerState.copySource],
    library_use_mode: LIB_USE_MODE_TO_API[innerState.copyLibUseMode],
    library_reuse_mode: LIB_RULE_TO_API[innerState.copyLibRandomRule],
    video_cover_source: COVER_SOURCE_TO_API[innerState.coverSource],
    news_mixcut_duration: innerState.newsDuration,
    pic: innerState.coverImage,
    work_mode: innerState.workMode === WorkModeEnum.Lib ? 2 : 1,
    product_use_mode: LIB_USE_MODE_TO_API[innerState.libUseMode],
    product_reuse_mode: LIB_RULE_TO_API[innerState.libRandomRule],
    music_source: normalizeMusicSource(innerState.musicSource),
    music_volume: normalizeMusicVolume(innerState.musicVolume),
    speech_rate: normalizeSpeechRate(innerState.speechRate),
    // 提交全部类型配置，避免未勾选类型在后端被重置为自动随机
    template_config: buildTemplateConfigForTypes(ALL_SYNTH_API_TYPES, innerState.templateConfig),
});

const handleSave = async () => {
    if (!validate()) return;
    const ok = await checkDigitalHuman();
    if (!ok) return;
    const payload = buildPayload();
    await (innerState.id ? updateAiSynthesisRule(payload) : addAiSynthesisRule(payload));
    close();
    emit("success");
};

const { isLock, lockFn } = useLockFn(handleSave);
const close = () => {
    templatePickerVisible.value = false;
    emit("close");
};

// ─── 详情回显 ───────────────────────────────────────────────────────
const getDetail = async () => {
    if (!personId.value) return;
    loading.value = true;
    try {
        const res = await getAiSynthesisRuleDetail({ persona_id: personId.value });
        if (!res) return;
        innerState.id = res.id ?? "";
        const isProductDirect = Number(res.work_mode) === 2 || Number(res.publish_mode) === 2;
        innerState.workMode = isProductDirect ? WorkModeEnum.Lib : WorkModeEnum.Ai;
        const types = Array.isArray(res.generation_types) ? res.generation_types : [];
        innerState.synthTypes = (types
            .map((n: number) => API_TO_SYNTH_TYPE[String(n)])
            .filter(Boolean) as SynthTypeEnum[]) || [SynthTypeEnum.DigitalHuman];
        if (!innerState.synthTypes.length) innerState.synthTypes = [SynthTypeEnum.DigitalHuman];
        innerState.materialSource =
            (API_TO_MATERIAL_SOURCE[String(res.visual_material_source)] as MaterialSourceEnum) ??
            MaterialSourceEnum.AILib;
        innerState.copySource =
            (API_TO_COPY_SOURCE[String(res.copywriting_source)] as CopySourceEnum) ?? CopySourceEnum.Rewrite;
        const libraryUseMode =
            (API_TO_LIB_USE_MODE[String(res.library_use_mode)] as LibUseModeEnum) ?? LibUseModeEnum.Random;
        const libraryReuseMode = (API_TO_LIB_RULE[String(res.library_reuse_mode)] as LibRuleEnum) ?? LibRuleEnum.Once;
        const hasProductFields = res.product_use_mode != null || res.product_reuse_mode != null;
        if (hasProductFields) {
            innerState.copyLibUseMode = libraryUseMode;
            innerState.copyLibRandomRule = libraryReuseMode;
            innerState.libUseMode =
                (API_TO_LIB_USE_MODE[String(res.product_use_mode)] as LibUseModeEnum) ?? LibUseModeEnum.Random;
            innerState.libRandomRule =
                (API_TO_LIB_RULE[String(res.product_reuse_mode)] as LibRuleEnum) ?? LibRuleEnum.Once;
        } else if (isProductDirect) {
            // 旧数据：library_* 实际存的是成品库规则
            innerState.libUseMode = libraryUseMode;
            innerState.libRandomRule = libraryReuseMode;
        } else {
            innerState.copyLibUseMode = libraryUseMode;
            innerState.copyLibRandomRule = libraryReuseMode;
        }
        innerState.coverSource =
            (API_TO_COVER_SOURCE[String(res.video_cover_source)] as CoverSourceEnum) ?? CoverSourceEnum.Default;
        innerState.newsDuration = clampNewsDuration(res.news_mixcut_duration);
        innerState.coverImage = res.pic ?? "";
        innerState.musicSource = normalizeMusicSource(res.music_source);
        innerState.musicVolume = normalizeMusicVolume(res.music_volume ?? MUSIC_VOLUME_DEFAULT);
        innerState.speechRate = normalizeSpeechRate(res.speech_rate ?? SPEECH_RATE_DEFAULT);
        // 回显全部类型模板（含当前未启用），以便重新勾选生成类型时恢复
        innerState.templateConfig = buildTemplateConfigForTypes(
            ALL_SYNTH_API_TYPES,
            (res.template_config || {}) as TemplateConfigMap,
        );
    } finally {
        loading.value = false;
    }
};

const open = (id: string | number, opts?: { isMaterialEmpty?: boolean }) => {
    personId.value = id;
    isMaterialEmpty.value = Boolean(opts?.isMaterialEmpty);
    templatePickerVisible.value = false;
    // 重置后再拉详情
    Object.assign(innerState, DEFAULT_INNER_STATE());
    getDetail();
    popupRef.value?.open();
};

defineExpose({ open });
</script>

<style scoped>
.rule-sec {
    @apply mb-5 last:mb-0;
}
.rule-sec-hd {
    @apply mb-3;
}
.rule-sec-title {
    @apply text-sm font-extrabold text-[#1D2129];
}
.rule-sec-hint {
    @apply text-xs text-[#86909C] mt-1;
}

.rule-type-card {
    @apply relative flex flex-col items-center justify-center px-2 py-4 rounded-2xl cursor-pointer transition-all select-none;
    background: #f6f8fc;
    border: 2px solid transparent;
    color: #4b5563;
}
.rule-type-card.sel {
    background: #ebf3ff;
    border-color: #2f73f6;
    color: #2f73f6;
}
.rule-type-check {
    @apply absolute top-2 right-2 w-5 h-5 rounded-full flex items-center justify-center;
    background: #2f73f6;
}

.rule-seg {
    @apply flex gap-2 p-1 rounded-2xl;
    background: #f6f8fc;
}
.rule-seg-btn {
    @apply flex-1 h-9 rounded-xl flex items-center justify-center text-xs font-semibold cursor-pointer select-none transition-all;
    color: #6b7280;
}
.rule-seg-btn.sel {
    background: #ffffff;
    color: #2f73f6;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}
.rule-seg-btn.locked {
    cursor: not-allowed;
    opacity: 0.55;
}

.rule-note {
    @apply mt-3 rounded-xl px-3 py-2 text-xs leading-relaxed;
    background: #f4f7fc;
    color: #4b5563;
}
.rule-note-text {
    color: #4b5563;
}
.rule-note-extra {
    @apply mt-1;
    color: #ff7d00;
}
.rule-note-extra.free {
    color: #10b981;
}
.rule-note-error {
    @apply mt-3 rounded-xl px-3 py-2 text-xs leading-relaxed;
    background: #fff1f0;
    border: 1px solid #ffccc7;
    color: #cf1322;
}

.rule-cover-preview {
    position: relative;
}

.rule-tpl-entry {
    @apply flex items-center gap-3 rounded-2xl px-3.5 py-3 cursor-pointer transition-colors;
    background: #f7f9fc;
    border: 1px solid #eef1f6;
}
.rule-tpl-entry:hover {
    background: #f0f4fa;
}
.rule-tpl-ic {
    @apply w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0;
    background: #ebf3ff;
}

.rule-slider-hd {
    @apply flex items-baseline justify-between gap-3 mb-2;
}
.rule-slider-val {
    @apply text-sm font-bold text-primary flex-shrink-0;
}
.rule-slider-labels {
    @apply flex justify-between mt-1;
}
.rule-slider-label {
    @apply text-[11px] text-[#86909C];
}
</style>
