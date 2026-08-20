<template>
    <popup-bottom
        v-model="visible"
        mode="bottom"
        height="88%"
        border-radius="44"
        custom-class="bg-white"
        :clearable="false"
        :z-index="5002"
        :mask-close-able="true"
        :custom-style="{ overflow: 'hidden' }"
        safe-area-inset-bottom
        @close="handleClose">
        <template #header>
            <view class="rule-sheet-handle"></view>
            <view class="rule-sheet-hd">
                <view class="rule-sheet-ic">
                    <image :src="VideoIcon" mode="aspectFit" class="w-[36rpx] h-[36rpx]" />
                </view>
                <view class="rule-sheet-info">
                    <text class="block rule-sheet-title">视频剪辑</text>
                    <text class="block rule-sheet-sub">视频剪辑将按此规则自动生成内容</text>
                </view>
                <view class="rule-sheet-close" @click.stop="handleClose">
                    <u-icon name="close" color="#86909C" size="22"></u-icon>
                </view>
            </view>
        </template>

        <template #content>
            <view class="rule-pop-content">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="rule-pop-inner">
                            <view class="rule-sec">
                                <view class="rule-sec-hd">
                                    <view class="rule-sec-title"><text>工作方式</text></view>
                                </view>
                                <view class="rule-seg">
                                    <view
                                        v-for="opt in WORK_MODE_OPTIONS"
                                        :key="opt.val"
                                        class="rule-seg-btn"
                                        :class="{ sel: innerState.workMode === opt.val }"
                                        @click="innerState.workMode = opt.val">
                                        <text>{{ opt.label }}</text>
                                    </view>
                                </view>
                                <view class="rule-note">
                                    <text class="block rule-note-text">{{ currentWorkModeNote }}</text>
                                </view>
                            </view>

                            <template v-if="innerState.workMode === WorkModeEnum.Lib">
                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>使用方式</text></view>
                                    </view>
                                    <view class="rule-seg">
                                        <view
                                            v-for="opt in LIB_USE_MODE_OPTIONS"
                                            :key="opt.val"
                                            class="rule-seg-btn"
                                            :class="{ sel: innerState.libUseMode === opt.val }"
                                            @click="innerState.libUseMode = opt.val">
                                            <text>{{ opt.label }}</text>
                                        </view>
                                    </view>
                                </view>

                                <view v-if="innerState.libUseMode === LibUseModeEnum.Random" class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>随机规则</text></view>
                                    </view>
                                    <view class="rule-seg">
                                        <view
                                            v-for="opt in EDITOR_LIB_RULE_OPTIONS"
                                            :key="opt.val"
                                            class="rule-seg-btn"
                                            :class="{ sel: innerState.libRandomRule === opt.val }"
                                            @click="innerState.libRandomRule = opt.val">
                                            <text>{{ opt.label }}</text>
                                        </view>
                                    </view>
                                    <view class="rule-note">
                                        <text class="block rule-note-text">{{ currentEditorLibRuleNote }}</text>
                                    </view>
                                </view>
                            </template>

                            <template v-else>
                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>生成类型</text></view>
                                        <text class="rule-sec-hint"> 可多选，将根据每日任务数按顺序轮流生成 </text>
                                    </view>
                                    <view class="rule-type-grid">
                                        <view
                                            v-for="item in SYNTH_TYPES"
                                            :key="item.id"
                                            class="rule-type"
                                            :class="{ sel: innerState.synthTypes.includes(item.id) }"
                                            @click="toggleSynthType(item.id)">
                                            <view
                                                v-if="innerState.synthTypes.includes(item.id)"
                                                class="rule-type-check">
                                                <u-icon name="checkmark" color="#ffffff" size="16"></u-icon>
                                            </view>
                                            <u-icon
                                                :name="item.icon"
                                                :color="innerState.synthTypes.includes(item.id) ? '#2F73F6' : '#9CA3AF'"
                                                size="42"
                                                class="mb-[14rpx]"></u-icon>
                                            <text class="rule-type-name">{{ item.label }}</text>
                                        </view>
                                    </view>
                                </view>

                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>视频模板</text></view>
                                        <text class="rule-sec-hint">每个生成类型可独立配置，支持多选</text>
                                    </view>
                                    <view class="rule-tpl-entry" @click="handleOpenTemplatePicker">
                                        <view class="rule-tpl-ic">
                                            <u-icon name="grid" color="#2F73F6" size="28"></u-icon>
                                        </view>
                                        <view class="rule-tpl-info">
                                            <text class="block rule-tpl-title">模板选择</text>
                                            <text class="block rule-tpl-summary">{{ templateSummary }}</text>
                                        </view>
                                        <u-icon name="arrow-right" color="#C9CDD4" size="22"></u-icon>
                                    </view>
                                </view>

                                <view v-if="hasNews" class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>新闻体时长</text></view>
                                        <text class="rule-sec-hint">范围 5–300 秒</text>
                                    </view>
                                    <view class="rule-dur">
                                        <view
                                            class="rule-dur-btn"
                                            :class="{ disabled: newsDurationMinusDisabled }"
                                            @click="stepNewsDuration(-1)">
                                            <u-icon name="minus" color="#4B5563" size="26"></u-icon>
                                        </view>
                                        <view class="rule-dur-input-wrap">
                                            <input
                                                class="rule-dur-input"
                                                type="number"
                                                :value="String(innerState.newsDuration)"
                                                @input="handleNewsDurationInput"
                                                @blur="handleNewsDurationBlur" />
                                            <text class="rule-dur-unit">秒</text>
                                        </view>
                                        <view
                                            class="rule-dur-btn"
                                            :class="{ disabled: newsDurationPlusDisabled }"
                                            @click="stepNewsDuration(1)">
                                            <u-icon name="plus" color="#4B5563" size="26"></u-icon>
                                        </view>
                                    </view>
                                </view>

                                <view v-if="hasDigitalHuman" class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title">
                                            <text>画面素材</text>
                                            <text class="rule-sec-sub">(数字人专属)</text>
                                        </view>
                                    </view>
                                    <view class="rule-seg">
                                        <view
                                            v-for="opt in MATERIAL_SOURCE_OPTIONS"
                                            :key="opt.val"
                                            class="rule-seg-btn"
                                            :class="{ sel: innerState.materialSource === opt.val }"
                                            @click="innerState.materialSource = opt.val">
                                            <text>{{ opt.label }}</text>
                                        </view>
                                    </view>
                                    <view v-if="isMaterialConflict" class="rule-note rule-note-error">
                                        <text>
                                            当前素材库为空，使用【纯素材库】将导致任务生成失败。请先上传素材。
                                        </text>
                                    </view>
                                    <view v-else class="rule-note">
                                        <text class="block rule-note-text">
                                            {{ currentMaterialHint.effect }}
                                        </text>
                                        <text
                                            class="block rule-note-extra"
                                            :class="{ free: currentMaterialHint.costType === 'free' }">
                                            {{ currentMaterialHint.cost }}
                                        </text>
                                    </view>
                                </view>

                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>文案来源</text></view>
                                    </view>
                                    <view class="rule-seg">
                                        <view
                                            v-for="opt in copySourceOptions"
                                            :key="opt.val"
                                            class="rule-seg-btn"
                                            :class="{
                                                sel: innerState.copySource === opt.val,
                                                locked: opt.locked,
                                            }"
                                            @click="!opt.locked && (innerState.copySource = opt.val)">
                                            <u-icon
                                                v-if="opt.locked"
                                                name="lock"
                                                color="#C0C8D8"
                                                size="20"
                                                class="mr-[6rpx]"></u-icon>
                                            <text>{{ opt.label }}</text>
                                        </view>
                                    </view>
                                    <view class="rule-note">
                                        <text class="block rule-note-text">{{ currentCopyHint }}</text>
                                    </view>

                                    <view v-if="hasCopyLib" class="rule-sub">
                                        <text class="rule-sub-label">使用方式</text>
                                        <view class="rule-seg">
                                            <view
                                                v-for="opt in LIB_USE_MODE_OPTIONS"
                                                :key="opt.val"
                                                class="rule-seg-btn"
                                                :class="{ sel: innerState.copyLibUseMode === opt.val }"
                                                @click="innerState.copyLibUseMode = opt.val">
                                                <text>{{ opt.label }}</text>
                                            </view>
                                        </view>

                                        <view v-if="innerState.copyLibUseMode === LibUseModeEnum.Random">
                                            <text class="rule-sub-label">随机规则</text>
                                            <view class="rule-seg">
                                                <view
                                                    v-for="opt in COPY_LIB_RULE_OPTIONS"
                                                    :key="opt.val"
                                                    class="rule-seg-btn"
                                                    :class="{ sel: innerState.copyLibRandomRule === opt.val }"
                                                    @click="innerState.copyLibRandomRule = opt.val">
                                                    <text>{{ opt.label }}</text>
                                                </view>
                                            </view>
                                            <view class="rule-note">
                                                <text class="block rule-note-text">{{ currentCopyLibRuleNote }}</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>视频封面</text></view>
                                    </view>
                                    <view class="rule-seg">
                                        <view
                                            v-for="opt in COVER_SOURCE_OPTIONS"
                                            :key="opt.val"
                                            class="rule-seg-btn"
                                            :class="{ sel: innerState.coverSource === opt.val }"
                                            @click="innerState.coverSource = opt.val">
                                            <text>{{ opt.label }}</text>
                                        </view>
                                    </view>
                                    <view class="rule-note">
                                        <text class="block rule-note-text">{{ currentCoverHint }}</text>
                                    </view>

                                    <view v-if="innerState.coverSource === CoverSourceEnum.Manual">
                                        <view
                                            v-if="!innerState.coverImage"
                                            class="rule-upload"
                                            @click="handleUploadCover">
                                            <view class="rule-upload-ic">
                                                <u-icon name="plus" color="#9CA3AF" size="32"></u-icon>
                                            </view>
                                            <text class="block rule-upload-title">点击上传封面图片</text>
                                            <text class="block rule-upload-sub">支持 JPG、PNG 格式</text>
                                        </view>

                                        <view v-else class="rule-cover-preview">
                                            <image
                                                :src="innerState.coverImage"
                                                class="w-full h-[300rpx]"
                                                mode="aspectFill"></image>
                                            <view class="rule-cover-mask">
                                                <view class="rule-cover-action" @click="handleUploadCover">
                                                    <view class="rule-cover-action-ic">
                                                        <u-icon name="reload" color="#ffffff" size="34"></u-icon>
                                                    </view>
                                                    <text class="text-[22rpx] text-white">重新上传</text>
                                                </view>
                                                <view class="rule-cover-action" @click="handleDeleteCover">
                                                    <view class="rule-cover-action-ic">
                                                        <u-icon name="trash" color="#ffffff" size="34"></u-icon>
                                                    </view>
                                                    <text class="text-[22rpx] text-white">删除</text>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>剪辑视频音乐来源</text></view>
                                    </view>
                                    <view class="rule-seg">
                                        <view
                                            v-for="opt in MUSIC_SOURCE_OPTIONS"
                                            :key="opt.val"
                                            class="rule-seg-btn"
                                            :class="{ sel: innerState.musicSource === opt.val }"
                                            @click="innerState.musicSource = opt.val">
                                            <text>{{ opt.label }}</text>
                                        </view>
                                    </view>
                                    <view class="rule-note">
                                        <text class="block rule-note-text">{{ currentMusicSourceHint }}</text>
                                    </view>
                                </view>

                                <view v-if="innerState.musicSource !== MusicSourceEnum.None" class="rule-sec">
                                    <view class="rule-sec-hd rule-slider-hd">
                                        <view class="rule-sec-title"><text>音乐音量</text></view>
                                        <text class="rule-slider-val">{{ musicVolumePercent }}%</text>
                                    </view>
                                    <!-- key：切换音乐来源重新显示时强制重挂载，避免沿用错误轨道宽度 -->
                                    <view v-if="visible" :key="`vol-${innerState.musicSource}`" class="rule-slider-body">
                                        <u-slider
                                            v-model="musicVolumeSlider"
                                            :min="0"
                                            :max="10"
                                            :step="1"
                                            active-color="#2F73F6"
                                            inactive-color="#E5E9F0"
                                            block-width="36"></u-slider>
                                        <view class="rule-slider-labels">
                                            <text
                                                v-for="label in MUSIC_VOLUME_LABELS"
                                                :key="label"
                                                class="rule-slider-label">
                                                {{ label }}
                                            </text>
                                        </view>
                                    </view>
                                </view>

                                <view class="rule-sec">
                                    <view class="rule-sec-hd rule-slider-hd">
                                        <view class="rule-sec-title"><text>口播语速</text></view>
                                        <text class="rule-slider-val">{{ speechRateLabel }}</text>
                                    </view>
                                    <view v-if="visible" class="rule-slider-body">
                                        <u-slider
                                            v-model="speechRateSlider"
                                            :min="5"
                                            :max="20"
                                            :step="1"
                                            active-color="#2F73F6"
                                            inactive-color="#E5E9F0"
                                            block-width="36"></u-slider>
                                        <view class="rule-slider-labels">
                                            <text
                                                v-for="label in SPEECH_RATE_LABELS"
                                                :key="label"
                                                class="rule-slider-label">
                                                {{ label }}
                                            </text>
                                        </view>
                                    </view>
                                </view>
                            </template>
                        </view>
                    </scroll-view>
                </view>

                <view class="rule-sheet-foot">
                    <view class="rule-sheet-done" @click="handleConfirm">完成配置</view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { addAiSynthesisRule, updateAiSynthesisRule, getAiSynthesisRuleDetail, getAvatarList } from "@/api/person";
import useUpload from "@/hooks/useUpload";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import VideoIcon from "@/ai_modules/person/static/icons/employee/video-blue.svg";
import {
    SynthTypeEnum,
    MaterialSourceEnum,
    CopySourceEnum,
    CopyStyleEnum,
    CoverSourceEnum,
    TemplateConfigMap,
    VIDEO_TEMPLATE_CONFIRM_EVENT,
    VIDEO_TEMPLATE_DRAFT_KEY,
} from "../enums";
import {
    ALL_SYNTH_API_TYPES,
    buildTemplateConfigForTypes,
    buildTemplateSummary,
    createDefaultTemplateItem,
    findEmptyCustomType,
    getSynthApiFullLabel,
    normalizeTemplateItem,
    synthUiToApi,
} from "../utils/template-config";

// ─── UI 枚举（工作方式 / 成品库为本地态；文案库规则接接口）────────────────────
/** 工作方式：AI 合成视频 / 成品库直发 */
enum WorkModeEnum {
    Ai = "ai",
    Lib = "lib",
}
/** 库使用方式：随机 / 顺序（成品库、文案库共用）*/
enum LibUseModeEnum {
    Random = "random",
    Sequence = "sequence",
}
/** 库随机规则：只用一次 / 可重复 */
enum LibRuleEnum {
    Once = "once",
    Repeat = "repeat",
}
/** 文案来源 · 文案库（UI 专用，未纳入后端提交）*/
const COPY_LIB_VALUE = "lib";

const NEWS_DURATION = { min: 5, max: 300, step: 5, default: 60 } as const;

/** 背景音乐来源：1 系统 / 2 人设音乐库 / 3 无 */
enum MusicSourceEnum {
    System = 1,
    Persona = 2,
    None = 3,
}

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

// ─── 类型 ─────────────────────────────────────────────────────────
interface InnerState {
    id: string;
    synthTypes: string[];
    materialSource: string;
    copySource: string;
    copyStyle: string;
    coverSource: string;
    coverImage: string;
    // 工作方式 / 成品库 / 文案库规则（成品库模式下 library_* 取成品库选项）
    workMode: string;
    libUseMode: string;
    libRandomRule: string;
    newsDuration: number;
    copyLibUseMode: string;
    copyLibRandomRule: string;
    /** 视频模板：key 为 generation_types 数字（1/3/4） */
    templateConfig: TemplateConfigMap;
    /** 背景音乐来源：1 系统 / 2 人设 / 3 无 */
    musicSource: MusicSourceEnum;
    /** 音乐音量 0~1，一位小数 */
    musicVolume: number;
    /** 口播语速 0.5~2，一位小数 */
    speechRate: number;
}

// ─── Props / Emits ────────────────────────────────────────────────
const props = defineProps<{
    modelValue: boolean;
    personId: string;
    isMaterialEmpty: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", val: boolean): void;
    (e: "confirm", state: InnerState, summary: string): void;
}>();

// ─── 显隐双向绑定 ─────────────────────────────────────────────────
const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});

// ─── 静态常量 ─────────────────────────────────────────────────────
const SYNTH_TYPES = [
    { id: SynthTypeEnum.DigitalHuman, label: "数字人口播", icon: "account" },
    { id: SynthTypeEnum.News, label: "新闻体混剪", icon: "server-fill" },
    { id: SynthTypeEnum.Material, label: "纯素材混剪", icon: "photo" },
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
    rewrite: "智能提取爆款视频框架，大幅提升上热门概率。",
    ai_gen: "根据核心词快速裂变，保证极高原创度和产出效率。",
    none: "纯画面与音乐展示，不生成任何口播或字幕，适合风景、沉浸式空镜。",
    [COPY_LIB_VALUE]: "从该人设的文案库中按规则取用，已审过的内容直接发，更稳。",
};

const COVER_HINTS: Record<string, string> = {
    [CoverSourceEnum.Default]: "截取视频首帧作为封面，简单直接。",
    [CoverSourceEnum.AI]: "AI 根据视频内容自动生成更适合推荐流的封面。",
    [CoverSourceEnum.Manual]: "上传固定封面图，适合统一账号视觉风格。",
};

// ─── 提交 / 回显 映射表 ───────────────────────────────────────────

/** 生成类型：枚举 → 接口数字 */
const SYNTH_TYPE_TO_API: Record<string, number> = {
    [SynthTypeEnum.DigitalHuman]: 1,
    [SynthTypeEnum.Material]: 3,
    [SynthTypeEnum.News]: 4,
};

/** 画面素材来源：枚举 ↔ 接口数字 */
const MATERIAL_SOURCE_TO_API: Record<string, number> = {
    [MaterialSourceEnum.PureAI]: 1,
    [MaterialSourceEnum.AILib]: 2,
    [MaterialSourceEnum.PureLib]: 3,
};

/** 文案来源：枚举 ↔ 接口数字（4 = 文案库）*/
const COPY_SOURCE_TO_API: Record<string, number> = {
    [CopySourceEnum.Rewrite]: 1,
    [CopySourceEnum.AIGen]: 2,
    [CopySourceEnum.None]: 3,
    [COPY_LIB_VALUE]: 4,
};

/** 文案库 / 成品库使用方式：枚举 ↔ 接口数字 */
const LIB_USE_MODE_TO_API: Record<string, number> = {
    [LibUseModeEnum.Random]: 1,
    [LibUseModeEnum.Sequence]: 2,
};

/** 文案库 / 成品库重复规则：枚举 ↔ 接口数字 */
const LIB_RULE_TO_API: Record<string, number> = {
    [LibRuleEnum.Once]: 1,
    [LibRuleEnum.Repeat]: 2,
};

/** 视频封面：枚举 ↔ 接口数字 */
const COVER_SOURCE_TO_API: Record<string, number> = {
    [CoverSourceEnum.Default]: 1,
    [CoverSourceEnum.AI]: 2,
    [CoverSourceEnum.Manual]: 3,
};

/** 工具函数：将映射表反转，用于回显 */
const invertMap = <K extends string, V extends string | number>(map: Record<K, V>) =>
    Object.fromEntries(Object.entries(map).map(([k, v]) => [v, k])) as Record<string, K>;

const API_TO_MATERIAL_SOURCE = invertMap(MATERIAL_SOURCE_TO_API);
const API_TO_COPY_SOURCE = invertMap(COPY_SOURCE_TO_API);
const API_TO_COVER_SOURCE = invertMap(COVER_SOURCE_TO_API);
const API_TO_SYNTH_TYPE = invertMap(SYNTH_TYPE_TO_API);
const API_TO_LIB_USE_MODE = invertMap(LIB_USE_MODE_TO_API);
const API_TO_LIB_RULE = invertMap(LIB_RULE_TO_API);

// ─── 组件内部状态 ─────────────────────────────────────────────────
const innerState = reactive<InnerState>({
    id: "",
    synthTypes: [SynthTypeEnum.DigitalHuman],
    materialSource: MaterialSourceEnum.AILib,
    copySource: CopySourceEnum.Rewrite,
    copyStyle: CopyStyleEnum.Spoken,
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

const { on: onEventBus } = useEventBusManager();

// ─── 计算属性 ─────────────────────────────────────────────────────
const hasDigitalHuman = computed(() => innerState.synthTypes.includes(SynthTypeEnum.DigitalHuman));
const hasNews = computed(() => innerState.synthTypes.includes(SynthTypeEnum.News));
const hasCopyLib = computed(() => innerState.copySource === COPY_LIB_VALUE);

const activeSynthApiTypes = computed(() =>
    innerState.synthTypes.map((t) => synthUiToApi(t)).filter((n): n is number => typeof n === "number"),
);

const templateSummary = computed(() =>
    buildTemplateSummary(activeSynthApiTypes.value, innerState.templateConfig),
);

const currentWorkModeNote = computed(() => WORK_MODE_NOTES[innerState.workMode]);
const currentEditorLibRuleNote = computed(() => EDITOR_LIB_RULE_NOTES[innerState.libRandomRule]);
const currentCopyLibRuleNote = computed(() => COPY_LIB_RULE_NOTES[innerState.copyLibRandomRule]);

const newsDurationMinusDisabled = computed(() => innerState.newsDuration <= NEWS_DURATION.min);
const newsDurationPlusDisabled = computed(() => innerState.newsDuration >= NEWS_DURATION.max);

const canUseNoCopy = computed(
    () =>
        !innerState.synthTypes.includes(SynthTypeEnum.DigitalHuman) &&
        !innerState.synthTypes.includes(SynthTypeEnum.News),
);

const copySourceOptions = computed(() => [
    { val: CopySourceEnum.Rewrite as string, label: "找爆款仿写", locked: false },
    { val: CopySourceEnum.AIGen as string, label: "AI直接生成", locked: false },
    { val: COPY_LIB_VALUE, label: "文案库", locked: false },
    // { val: CopySourceEnum.None, label: "无需文案", locked: !canUseNoCopy.value },
]);

const isMaterialConflict = computed(
    () => innerState.materialSource === MaterialSourceEnum.PureLib && props.isMaterialEmpty,
);

const currentMaterialHint = computed(() => MATERIAL_HINTS[innerState.materialSource]);
const currentCopyHint = computed(() => COPY_HINTS[innerState.copySource]);
const currentCoverHint = computed(() => COVER_HINTS[innerState.coverSource]);
const currentMusicSourceHint = computed(
    () => MUSIC_SOURCE_HINTS[innerState.musicSource] || MUSIC_SOURCE_HINTS[MusicSourceEnum.System],
);

const musicVolumePercent = computed(() => Math.round(innerState.musicVolume * 100));
const speechRateLabel = computed(() => `${innerState.speechRate.toFixed(1)}x`);

/** u-slider 用整数刻度：音量 0~10 → 0.0~1.0 */
const musicVolumeSlider = computed({
    get: () => Math.round(innerState.musicVolume * 10),
    set: (val: number) => {
        innerState.musicVolume = normalizeMusicVolume(Number(val) / 10);
    },
});

/** u-slider 用整数刻度：语速 5~20 → 0.5~2.0 */
const speechRateSlider = computed({
    get: () => Math.round(innerState.speechRate * 10),
    set: (val: number) => {
        innerState.speechRate = normalizeSpeechRate(Number(val) / 10);
    },
});

// ─── 联动纠错 watch ───────────────────────────────────────────────
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
        // 原地补默认，不重建整个 map，避免取消勾选时丢掉已选模板
        activeSynthApiTypes.value.forEach((apiType) => {
            const key = String(apiType);
            if (!innerState.templateConfig[key]) {
                innerState.templateConfig[key] = createDefaultTemplateItem(apiType);
            }
        });
    },
    { immediate: true },
);

onEventBus(VIDEO_TEMPLATE_CONFIRM_EVENT, (payload: TemplateConfigMap) => {
    if (!payload || typeof payload !== "object") return;
    Object.keys(payload).forEach((key) => {
        const apiType = Number(key);
        innerState.templateConfig[key] = normalizeTemplateItem(apiType, payload[key]);
    });
});

// ─── 方法 ─────────────────────────────────────────────────────────
const toggleSynthType = (id: string): void => {
    const idx = innerState.synthTypes.indexOf(id as SynthTypeEnum);
    if (idx > -1) {
        if (innerState.synthTypes.length > 1) innerState.synthTypes.splice(idx, 1);
    } else {
        innerState.synthTypes.push(id as SynthTypeEnum);
    }
};

// ─── 新闻体时长 stepper ────────────────────────────────────────────
const clampNewsDuration = (value: unknown): number => {
    const num = Math.round(Number(value));
    if (!Number.isFinite(num)) return NEWS_DURATION.default;
    return Math.max(NEWS_DURATION.min, Math.min(NEWS_DURATION.max, num));
};

const stepNewsDuration = (dir: 1 | -1): void => {
    innerState.newsDuration = clampNewsDuration(innerState.newsDuration + dir * NEWS_DURATION.step);
};

const handleNewsDurationInput = (e: any): void => {
    const raw = Number(e?.detail?.value);
    innerState.newsDuration = Number.isFinite(raw) ? raw : innerState.newsDuration;
};

const handleNewsDurationBlur = (): void => {
    innerState.newsDuration = clampNewsDuration(innerState.newsDuration);
};

const { uploadAndProcessFiles } = useUpload({
    imageResolution: [99999, 99999],
    onSuccess: (res) => {
        innerState.coverImage = res[0]?.url;
    },
});

const handleUploadCover = async (): Promise<void> => {
    uni.showLoading({ title: "上传中...", mask: true });
    try {
        await uploadAndProcessFiles("image");
    } finally {
        uni.hideLoading();
    }
};

const handleDeleteCover = (): void => {
    uni.showModal({
        title: "提示",
        content: "确定删除当前封面图片吗？",
        success: ({ confirm }) => {
            if (confirm) innerState.coverImage = "";
        },
    });
};

const handleClose = (): void => {
    visible.value = false;
};

const handleOpenTemplatePicker = (): void => {
    const types = activeSynthApiTypes.value;
    if (!types.length) {
        uni.showToast({ title: "请先选择生成类型", icon: "none" });
        return;
    }

    uni.setStorageSync(VIDEO_TEMPLATE_DRAFT_KEY, {
        types,
        config: buildTemplateConfigForTypes(types, innerState.templateConfig),
    });
    uni.navigateTo({
        url: "/ai_modules/person/pages/video_template_choose/video_template_choose",
    });
};

/** 生成规则摘要文字 */
const buildSummary = (): string => {
    if (innerState.workMode === WorkModeEnum.Lib) {
        const useLabel = LIB_USE_MODE_OPTIONS.find((o) => o.val === innerState.libUseMode)!.label;
        return `成品库直发 · ${useLabel}`;
    }

    const typeLabels = innerState.synthTypes.map((id) => SYNTH_TYPES.find((t) => t.id === id)!.label);
    const typeStr = typeLabels.length > 1 ? `${typeLabels[0]}等` : typeLabels[0];

    const mLabel = hasDigitalHuman.value
        ? ` · ${MATERIAL_SOURCE_OPTIONS.find((o) => o.val === innerState.materialSource)!.label}`
        : "";

    const cLabel = copySourceOptions.value.find((o) => o.val === innerState.copySource)!.label;

    return `${typeStr}${mLabel} · ${cLabel}`;
};

const validateParams = (): boolean => {
    if (
        innerState.workMode === WorkModeEnum.Ai &&
        innerState.coverSource === CoverSourceEnum.Manual &&
        !innerState.coverImage
    ) {
        uni.showToast({ title: "请上传视频封面", icon: "none" });
        return false;
    }

    if (innerState.workMode === WorkModeEnum.Ai) {
        const emptyType = findEmptyCustomType(activeSynthApiTypes.value, innerState.templateConfig);
        if (emptyType != null) {
            uni.showToast({
                title: `「${getSynthApiFullLabel(emptyType)}」请至少选择 1 个模板`,
                icon: "none",
            });
            return false;
        }
    }
    return true;
};

/** 构建提交参数：文案库 / 成品库字段分拆提交，始终携带 work_mode */
const buildApiParams = () => ({
    id: innerState.id,
    persona_id: props.personId,
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

/** 数字人检测 */
const checkDigitalHuman = async (): Promise<boolean> => {
    const { lists } = await getAvatarList({ persona_id: props.personId });
    if (lists.length === 0) {
        return new Promise((resolve) => {
            uni.showModal({
                title: "提示",
                content: "检测到未配置数字人，实际生成时将自动降级为素材混剪",
                confirmText: "去配置",
                cancelText: "继续保存",
                success: ({ confirm }) => {
                    if (confirm) {
                        uni.navigateTo({
                            url: `/ai_modules/person/pages/digital_human_config/digital_human_config?id=${props.personId}`,
                        });
                        resolve(false);
                    } else {
                        resolve(true);
                    }
                },
            });
        });
    }
    return true;
};

const handleConfirm = async (): Promise<void> => {
    if (!validateParams()) return;

    if (innerState.workMode === WorkModeEnum.Ai && innerState.synthTypes.includes(SynthTypeEnum.DigitalHuman)) {
        const canContinue = await checkDigitalHuman();
        if (!canContinue) return;
    }

    uni.showLoading({ title: "保存中...", mask: true });
    try {
        const params = buildApiParams();
        await (innerState.id ? updateAiSynthesisRule(params) : addAiSynthesisRule(params));

        visible.value = false;
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        emit(
            "confirm",
            {
                ...innerState,
                synthTypes: [...innerState.synthTypes],
                templateConfig: buildTemplateConfigForTypes(ALL_SYNTH_API_TYPES, innerState.templateConfig),
            },
            buildSummary(),
        );
    } catch {
        uni.showToast({ title: "保存失败", icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
    }
};

/** 回显详情数据 */
const getDetail = async (): Promise<void> => {
    const res = await getAiSynthesisRuleDetail({ persona_id: props.personId });
    if (!res) return;

    innerState.id = res.id ?? "";
    const isProductDirect = Number(res.work_mode) === 2 || Number(res.publish_mode) === 2;
    innerState.workMode = isProductDirect ? WorkModeEnum.Lib : WorkModeEnum.Ai;
    innerState.synthTypes = (res.generation_types as number[])?.map((n) => API_TO_SYNTH_TYPE[n]).filter(Boolean) ?? [
        SynthTypeEnum.DigitalHuman,
    ];
    innerState.materialSource = API_TO_MATERIAL_SOURCE[res.visual_material_source] ?? MaterialSourceEnum.AILib;
    innerState.copySource = API_TO_COPY_SOURCE[res.copywriting_source] ?? CopySourceEnum.Rewrite;
    const libraryUseMode = API_TO_LIB_USE_MODE[res.library_use_mode] ?? LibUseModeEnum.Random;
    const libraryReuseMode = API_TO_LIB_RULE[res.library_reuse_mode] ?? LibRuleEnum.Once;
    const hasProductFields = res.product_use_mode != null || res.product_reuse_mode != null;
    if (hasProductFields) {
        innerState.copyLibUseMode = libraryUseMode;
        innerState.copyLibRandomRule = libraryReuseMode;
        innerState.libUseMode = API_TO_LIB_USE_MODE[res.product_use_mode] ?? LibUseModeEnum.Random;
        innerState.libRandomRule = API_TO_LIB_RULE[res.product_reuse_mode] ?? LibRuleEnum.Once;
    } else if (isProductDirect) {
        // 旧数据：library_* 实际存的是成品库规则
        innerState.libUseMode = libraryUseMode;
        innerState.libRandomRule = libraryReuseMode;
    } else {
        innerState.copyLibUseMode = libraryUseMode;
        innerState.copyLibRandomRule = libraryReuseMode;
    }
    innerState.coverSource = API_TO_COVER_SOURCE[res.video_cover_source] ?? CoverSourceEnum.Default;
    innerState.newsDuration = res.news_mixcut_duration
        ? clampNewsDuration(res.news_mixcut_duration)
        : NEWS_DURATION.default;
    innerState.coverImage = res.pic ?? "";
    innerState.musicSource = normalizeMusicSource(res.music_source);
    innerState.musicVolume = normalizeMusicVolume(
        res.music_volume ?? MUSIC_VOLUME_DEFAULT,
    );
    innerState.speechRate = normalizeSpeechRate(res.speech_rate ?? SPEECH_RATE_DEFAULT);

    // 回显全部类型模板（含当前未启用），以便重新勾选生成类型时恢复
    innerState.templateConfig = buildTemplateConfigForTypes(
        ALL_SYNTH_API_TYPES,
        (res.template_config || {}) as TemplateConfigMap,
    );
};

watch(visible, (newVal) => {
    if (newVal) getDetail();
});

defineExpose({
    getDetail,
    buildSummary,
});
</script>

<style lang="scss" scoped>
.rule-sheet-handle {
    width: 76rpx;
    height: 8rpx;
    border-radius: 999rpx;
    background: #e5e9f0;
    margin: 18rpx auto 0;
    flex-shrink: 0;
}

.rule-sheet-hd {
    display: flex;
    align-items: center;
    gap: 24rpx;
    padding: 24rpx 36rpx 28rpx;
    border-bottom: 2rpx solid #f2f5fa;
    flex-shrink: 0;
    background: #ffffff;
}

.rule-sheet-ic {
    width: 80rpx;
    height: 80rpx;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #eff6ff;
}

.rule-sheet-info {
    flex: 1;
    min-width: 0;
}

.rule-sheet-title {
    font-size: 33rpx;
    font-weight: 800;
    color: #1d2129;
    line-height: 1.2;
}

.rule-sheet-sub {
    font-size: 25rpx;
    color: #9ca3af;
    margin-top: 6rpx;
    line-height: 1.35;
}

.rule-sheet-close {
    width: 60rpx;
    height: 60rpx;
    border-radius: 50%;
    background: #f3f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rule-pop-content {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #ffffff;
}

.rule-pop-inner {
    padding: 8rpx 32rpx 28rpx;
}

.rule-sec {
    margin-top: 44rpx;
}

.rule-sec:first-child {
    margin-top: 8rpx;
}

.rule-sec-hd {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 20rpx;
    margin-bottom: 24rpx;
}

/* 分块标题：与 CS / Operator 的 cs-sec-title / op-row-label 风格对齐
   30rpx / 800 / #1D2129 + 左侧 6rpx 蓝色短条；用 view 承载以兼容小程序的 ::before */
.rule-sec-title {
    display: flex;
    align-items: center;
    font-size: 30rpx;
    font-weight: 800;
    color: #1d2129;
    line-height: 1.2;
}

.rule-sec-title::before {
    content: "";
    width: 6rpx;
    height: 28rpx;
    border-radius: 999rpx;
    background: #2f73f6;
    margin-right: 16rpx;
    flex-shrink: 0;
}

.rule-sec-sub {
    font-size: 22rpx;
    color: #9ca3af;
    font-weight: 400;
    margin-left: 8rpx;
}

.rule-sec-hint {
    flex: 1;
    min-width: 0;
    text-align: right;
    font-size: 22rpx;
    color: #9ca3af;
    line-height: 1.35;
}

.rule-type-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20rpx;
}

.rule-type {
    position: relative;
    min-height: 174rpx;
    border-radius: 28rpx;
    border: 3rpx solid #e9edf4;
    background: #f5f6f8;
    padding: 36rpx 12rpx 28rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}

.rule-type:active {
    transform: scale(0.97);
}

.rule-type.sel {
    border-color: #2f73f6;
    background: #eff5ff;
}

.rule-type-check {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
    width: 36rpx;
    height: 36rpx;
    border-radius: 50%;
    background: #2f73f6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rule-type-name {
    font-size: 26rpx;
    font-weight: 700;
    color: #6b7280;
    line-height: 1.25;
    text-align: center;
}

.rule-type.sel .rule-type-name {
    color: #2f73f6;
}

/* 分段控件：与 CS 的 cs-seg / cs-seg-btn 对齐
   轨道 #f3f4f6 / r-20rpx / p-6rpx；激活态白底 + primary 蓝字 */
.rule-seg {
    display: flex;
    background: #f3f4f6;
    border-radius: 20rpx;
    padding: 6rpx;
    gap: 6rpx;
}

.rule-seg-btn {
    flex: 1;
    min-width: 0;
    min-height: 64rpx;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    font-weight: 700;
    color: #9ca3af;
    line-height: 1.2;
    transition: all 0.15s;
}

.rule-seg-btn.sel {
    background: #ffffff;
    color: #2f73f6;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
}

.rule-seg-btn.locked {
    background: #ebeef4;
    color: #c0c8d8;
}

.rule-note {
    margin-top: 20rpx;
    background: #f7f9fc;
    border: 2rpx solid #eef2f8;
    border-radius: 24rpx;
    padding: 24rpx 28rpx;
}

.rule-note-error {
    background: #fef2f2;
    border-color: rgba(252, 165, 165, 0.5);
    color: #dc2626;
    font-weight: 600;
    font-size: 24rpx;
    line-height: 1.65;
}

.rule-note-text {
    font-size: 22rpx;
    color: #6b7280;
    line-height: 1.7;
}

.rule-note-extra {
    color: #f97316;
    font-size: 22rpx;
    font-weight: 700;
    line-height: 1.5;
    margin-top: 8rpx;
}

.rule-note-extra.free {
    color: #16a34a;
}

.rule-slider-hd {
    margin-bottom: 8rpx;
}

.rule-slider-val {
    flex-shrink: 0;
    font-size: 28rpx;
    font-weight: 800;
    color: #2f73f6;
    line-height: 1.2;
}

/* 左右各留半个滑钮宽度，避免最大值时滑钮溢出被 overflow:hidden 裁切导致无法回拖 */
.rule-slider-body {
    padding: 0 18rpx;
}

.rule-slider-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 12rpx;
    padding: 0 4rpx;
}

.rule-slider-label {
    font-size: 20rpx;
    color: #9ca3af;
    line-height: 1.2;
}

/* 新闻体时长 stepper */
.rule-dur {
    display: inline-flex;
    align-items: stretch;
    height: 76rpx;
    background: #f7f9fc;
    border: 2rpx solid #e5e9f0;
    border-radius: 20rpx;
    overflow: hidden;
}

.rule-dur-btn {
    width: 72rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.15s, opacity 0.15s;
}

.rule-dur-btn:active {
    background: #ebeff6;
}

.rule-dur-btn.disabled {
    opacity: 0.3;
    pointer-events: none;
}

.rule-dur-input-wrap {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 0 18rpx;
    background: #ffffff;
    border-left: 2rpx solid #e5e9f0;
    border-right: 2rpx solid #e5e9f0;
    min-width: 150rpx;
}

.rule-dur-input {
    width: 96rpx;
    min-width: 0;
    background: transparent;
    font-size: 30rpx;
    font-weight: 700;
    color: #1d2129;
    text-align: center;
}

.rule-dur-unit {
    font-size: 24rpx;
    color: #9ca3af;
    flex-shrink: 0;
}

/* 文案库子设置 */
.rule-sub {
    margin-top: 24rpx;
}

.rule-sub-label {
    display: block;
    font-size: 24rpx;
    font-weight: 600;
    color: #4b5563;
    margin: 0 0 16rpx;
}

.rule-sub .rule-seg + .rule-sub-label {
    margin-top: 24rpx;
}

.rule-upload {
    margin-top: 20rpx;
    border: 3rpx dashed #d4dbe6;
    border-radius: 28rpx;
    padding: 52rpx 32rpx;
    text-align: center;
    background: #ffffff;
    transition: all 0.15s;
}

.rule-upload:active {
    background: #f7f9fc;
}

.rule-upload-ic {
    width: 68rpx;
    height: 68rpx;
    border-radius: 50%;
    background: #f2f5fa;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.rule-upload-title {
    font-size: 26rpx;
    font-weight: 700;
    color: #6b7280;
    margin-top: 20rpx;
}

.rule-upload-sub {
    font-size: 22rpx;
    color: #c0c8d8;
    margin-top: 4rpx;
}

.rule-cover-preview {
    position: relative;
    border-radius: 28rpx;
    overflow: hidden;
    margin-top: 20rpx;
}

.rule-cover-mask {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 48rpx;
}

.rule-cover-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8rpx;
}

.rule-cover-action:active {
    transform: scale(0.96);
}

.rule-cover-action-ic {
    width: 80rpx;
    height: 80rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: 2rpx solid rgba(255, 255, 255, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
}

.rule-tpl-entry {
    display: flex;
    align-items: center;
    gap: 24rpx;
    background: #f7f9fc;
    border: 2rpx solid #eef1f6;
    border-radius: 28rpx;
    padding: 24rpx 28rpx;
}

.rule-tpl-entry:active {
    background: #f0f4fa;
}

.rule-tpl-ic {
    width: 72rpx;
    height: 72rpx;
    border-radius: 24rpx;
    background: #ebf3ff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rule-tpl-info {
    flex: 1;
    min-width: 0;
}

.rule-tpl-title {
    font-size: 26rpx;
    font-weight: 700;
    color: #1d2129;
    line-height: 1.2;
}

.rule-tpl-summary {
    font-size: 22rpx;
    color: #9ca3af;
    margin-top: 6rpx;
    line-height: 1.35;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rule-sheet-foot {
    flex-shrink: 0;
    padding: 24rpx 32rpx calc(24rpx + env(safe-area-inset-bottom));
    border-top: 2rpx solid #f2f5fa;
    background: #ffffff;
}

.rule-sheet-done {
    width: 100%;
    height: 92rpx;
    border-radius: 28rpx;
    background: #2f73f6;
    color: #ffffff;
    font-size: 31rpx;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12rpx 30rpx rgba(47, 115, 246, 0.24);
}

.rule-sheet-done:active {
    background: #2862d8;
    transform: scale(0.98);
}
</style>
