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
                <!-- 运行模式：这个人设的视频在哪生产、靠什么发布，比生成类型更上位所以放最前；对应人设 run_mode -->
                <div class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">运行模式</div>
                        <div class="rule-sec-hint">决定视频在哪生产、靠什么发布</div>
                    </div>
                    <div class="rule-seg">
                        <div
                            v-for="opt in RUN_MODE_OPTIONS"
                            :key="opt.val"
                            class="rule-seg-btn"
                            :class="{ sel: innerState.runMode === opt.val }"
                            @click="innerState.runMode = opt.val">
                            <span>{{ opt.label }}</span>
                        </div>
                    </div>
                    <div class="rule-note">
                        <div class="rule-note-text">{{ currentRunModeNote.text }}</div>
                        <div class="rule-note-extra" :class="{ free: currentRunModeNote.tipOk }">
                            {{ currentRunModeNote.tip }}
                        </div>
                    </div>
                </div>

                <!-- 素材模式：开关在小程序「人设详情 › 素材库」，这里只读回显，不给第二个入口 -->
                <div class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">素材模式</div>
                        <div class="rule-sec-hint">在小程序「人设详情 › 素材库」里切换</div>
                    </div>
                    <div class="rule-note">
                        <div class="rule-note-text">{{ currentMaterialKindNote }}</div>
                    </div>
                </div>

                <!-- 每日出片条数：只在无手机模式下有意义。手机模式的条数仍由「内容发布」的发布时段数决定 -->
                <div v-if="isCloudRun" class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">每日出片条数</div>
                        <div class="rule-sec-hint">无手机模式不看发布时段，按这里的条数出片</div>
                    </div>
                    <el-input-number
                        v-model="innerState.dailyVideoCount"
                        :min="DAILY_VIDEO_COUNT.min"
                        :max="DAILY_VIDEO_COUNT.max"
                        :step="1"
                        step-strictly
                        controls-position="right" />
                    <div class="rule-note">
                        <div class="rule-note-text">每天云端自动生成这么多条，按下方生成类型顺序轮流出。</div>
                    </div>
                </div>

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
                    <div v-if="innerState.materialSource === MaterialSourceEnum.Square" class="mt-3">
                        <div class="text-xs text-[#86909C] mb-2">从这些广场分组取素材（不选=全部已上架分组）</div>
                        <el-checkbox-group v-model="innerState.squareGroupIds">
                            <el-checkbox v-for="item in squareGroups" :key="item.id" :label="item.id">
                                {{ item.name }}
                            </el-checkbox>
                        </el-checkbox-group>
                        <div v-if="!squareGroups.length" class="text-xs text-[#C0C4CC]">还没有上架的广场分组</div>
                    </div>
                </div>

                <!-- ③ 文案来源 -->
                <div class="rule-sec">
                    <div class="rule-sec-hd">
                        <div class="rule-sec-title">文案来源</div>
                    </div>
                    <div class="rule-seg rule-seg-copy">
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

                    <copy-hotspot-panel v-if="hasCopyHotspot" :persona-id="personId" />

                    <!-- 热点生成 · 取用规则（与 copywriting_source=5 一并提交） -->
                    <div v-if="hasCopyHotspot" class="mt-3">
                        <div class="text-xs font-semibold text-[#4B5563] mb-2">取用规则</div>
                        <div class="rule-seg">
                            <div
                                v-for="opt in HOTSPOT_PICK_MODE_OPTIONS"
                                :key="opt.val"
                                class="rule-seg-btn"
                                :class="{ sel: innerState.hotspotPickMode === opt.val }"
                                @click="innerState.hotspotPickMode = opt.val">
                                <span>{{ opt.label }}</span>
                            </div>
                        </div>
                        <div class="rule-note">
                            <div class="rule-note-text">{{ currentHotspotPickNote }}</div>
                        </div>
                    </div>

                    <!-- AI生成子设置：文案生成类型（预设 + 自定义） -->
                    <div v-if="hasCopyAiGen" class="rule-sub-ai">
                        <div class="text-xs font-semibold text-[#4B5563] mb-0.5">文案生成类型</div>
                        <div class="text-[11px] text-[#9CA3AF] mb-2.5">决定 AI 往哪个方向写口播文案</div>
                        <div class="rule-chips">
                            <div
                                v-for="opt in COPY_AI_TYPE_OPTIONS"
                                :key="opt.val"
                                class="rule-chip"
                                :class="{ sel: innerState.copyAiType === opt.val }"
                                @click="innerState.copyAiType = opt.val">
                                {{ opt.label }}
                            </div>
                        </div>
                        <div v-if="innerState.copyAiType === CopyAiTypeEnum.Custom" class="mt-2.5">
                            <el-input
                                v-model="innerState.copyAiCustom"
                                :maxlength="COPY_AI_CUSTOM_MAX"
                                show-word-limit
                                placeholder="描述你想要的类型，如：反常识冷知识，结尾抛一个互动问题" />
                        </div>
                        <div class="rule-note">
                            <div class="rule-note-text">{{ currentCopyAiTypeNote }}</div>
                        </div>
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
                            @click="pickMusicSource(opt.val)">
                            <span>{{ opt.label }}</span>
                        </div>
                    </div>
                    <div class="rule-note">
                        <div class="rule-note-text">{{ currentMusicSourceHint }}</div>
                    </div>
                    <div v-if="innerState.musicSource === MusicSourceEnum.System" class="mt-3">
                        <div class="rule-seg">
                            <div
                                class="rule-seg-btn"
                                :class="{ sel: !innerState.systemMusicId }"
                                @click="clearSystemMusic">
                                <span>每次随机</span>
                            </div>
                            <div
                                class="rule-seg-btn"
                                :class="{ sel: !!innerState.systemMusicId }"
                                @click="showSystemMusic = true">
                                <span>指定一首</span>
                            </div>
                        </div>
                        <div v-if="innerState.systemMusicId" class="mt-2 flex items-center gap-2">
                            <span class="flex-1 min-w-0 text-xs text-slate-600 truncate">
                                {{ innerState.systemMusicName || "已指定" }}
                            </span>
                            <button class="text-xs font-bold text-primary" @click="showSystemMusic = true">重选</button>
                        </div>
                        <div
                            v-if="innerState.systemMusicId && !innerState.systemMusicEnabled"
                            class="mt-1 text-xs text-amber-500">
                            这首已下架，下次将随机
                        </div>
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

    <el-dialog v-model="showSystemMusic" title="系统音乐" width="420px" append-to-body>
        <div class="music-list">
            <button
                type="button"
                class="music-row"
                :class="{ sel: !innerState.systemMusicId }"
                @click="onSystemMusic('', '')">
                每次随机
            </button>
            <button
                v-for="item in systemMusics"
                :key="item.id"
                type="button"
                class="music-row"
                :class="{ sel: innerState.systemMusicId === item.id }"
                @click="onSystemMusic(item.id, item.name)">
                <span class="music-row-name">{{ item.name }}</span>
            </button>
            <div v-if="!systemMusics.length" class="music-empty">暂无上架曲目</div>
        </div>
    </el-dialog>

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
import { getSquareGroups } from "@/api/ai_application/square";
import Popup from "@/components/popup/index.vue";
import feedback from "@/utils/feedback";
import { useLockFn } from "@/hooks/useLockFn";
import VideoTemplateChoosePop from "./video-template-choose-pop.vue";
import CopyHotspotPanel from "./copy-hotspot-panel.vue";
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
    Square = "Square",
}
enum CopySourceEnum {
    Rewrite = "Rewrite",
    AIGen = "AIGen",
    None = "None",
    Hotspot = "Hotspot",
    Lib = "Lib",
}

/** 热点生成 · 取用规则，对齐 la_ai_persona_synthesis_config.hotspot_pick_mode */
enum HotspotPickModeEnum {
    Latest = 1,
    Once = 2,
}

const normalizeHotspotPickMode = (value: unknown): HotspotPickModeEnum =>
    Number(value) === HotspotPickModeEnum.Once ? HotspotPickModeEnum.Once : HotspotPickModeEnum.Latest;

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
/** 运行模式：手机模式 = 绑定 AI 手机制作发布；无手机模式 = 云端制作、不做发布，用户自己下载发布（与 uniapp RunModeEnum 对齐） */
enum RunModeEnum {
    Phone = "phone",
    Cloud = "cloud",
}
/** 素材模式：自动适配 / 原图（与 uniapp MaterialKindEnum 对齐），只读回显 */
enum MaterialKindEnum {
    Auto = "auto",
    Original = "original",
}

const NEWS_DURATION = { min: 5, max: 300, step: 5, default: 60 } as const;
/** 每日出片条数：仅无手机模式使用，与后端 DAILY_VIDEO_COUNT_MAX 对齐 */
const DAILY_VIDEO_COUNT = { min: 1, max: 20, default: 1 } as const;
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
    { val: MaterialSourceEnum.Square, label: "素材库+广场" },
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

const RUN_MODE_OPTIONS = [
    { val: RunModeEnum.Phone, label: "手机模式" },
    { val: RunModeEnum.Cloud, label: "无手机模式" },
];

/** 运行模式说明：text 讲在哪生产、靠什么发布；tip 讲前置条件（tipOk 绿字，否则橙字提醒） */
const RUN_MODE_NOTES: Record<string, { text: string; tip: string; tipOk: boolean }> = {
    [RunModeEnum.Phone]: {
        text: "交给绑定的 AI 手机制作并发布，自动化最全。",
        tip: "没绑手机 / 手机掉线时不会自动开工",
        tipOk: false,
    },
    [RunModeEnum.Cloud]: {
        text: "不用绑手机，云端自动制作，成片进内容记录，自己下载发布。",
        tip: "无需准备设备",
        tipOk: true,
    },
};

/** 素材模式只读回显文案：讲合成时怎么取用素材 */
const MATERIAL_KIND_NOTES: Record<string, string> = {
    [MaterialKindEnum.Auto]: "自动适配模式：什么规格都能传，合成时统一缩放到模板分辨率，只取自动适配下传的素材。",
    [MaterialKindEnum.Original]: "原图模式：合成时不缩放，只取原图模式下传的素材。",
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
    [MaterialSourceEnum.Square]: {
        effect: "自有素材与广场素材对半使用，一边不够时用另一边补缺口。",
        cost: "无额外费用",
        costType: "free",
    },
};

const COPY_HINTS: Record<string, string> = {
    [CopySourceEnum.Rewrite]: "智能提取爆款视频框架，大幅提升上热门概率。",
    [CopySourceEnum.AIGen]: "根据核心词快速裂变，保证极高原创度和产出效率。",
    [CopySourceEnum.None]: "纯画面与音乐展示，不生成任何口播或字幕，适合风景、沉浸式空镜。",
    [CopySourceEnum.Hotspot]: "从热点库取用已入库的热点，结合人设生成追热点文案，时效性最强。",
    [CopySourceEnum.Lib]: "从该人设的文案库中按规则取用，已审过的内容直接发，更稳。",
};

const HOTSPOT_PICK_MODE_OPTIONS = [
    {
        val: HotspotPickModeEnum.Latest,
        label: "最新入库优先",
        note: "每次生成取最新入库的热点，追热点时效最好。",
    },
    {
        val: HotspotPickModeEnum.Once,
        label: "每条只用一次",
        note: "按入库顺序取用，每条用完即标记，不重复消费同一热点。",
    },
] as const;

/** AI生成 · 文案生成类型（预设 + 自定义），对应 copywriting_generation_type / copywriting_generation_custom */
enum CopyAiTypeEnum {
    Ganhuo = "ganhuo",
    Zhongcao = "zhongcao",
    Guandian = "guandian",
    Juqing = "juqing",
    Qinggan = "qinggan",
    Jiemi = "jiemi",
    Custom = "custom",
}
const COPY_AI_CUSTOM_MAX = 60;
const COPY_AI_TYPE_OPTIONS: { val: CopyAiTypeEnum; label: string; note: string }[] = [
    { val: CopyAiTypeEnum.Ganhuo, label: "干货科普", note: "把卖点讲成知识点，先给结论再讲原因，适合建立专业信任。" },
    { val: CopyAiTypeEnum.Zhongcao, label: "带货种草", note: "突出使用场景与前后对比，结尾直接引导下单或进店。" },
    { val: CopyAiTypeEnum.Guandian, label: "观点评论", note: "对行业现象亮明态度，争议感强，评论区互动率高。" },
    { val: CopyAiTypeEnum.Juqing, label: "剧情段子", note: "小剧情加反转带出产品，完播率高，适合泛流量涨粉。" },
    { val: CopyAiTypeEnum.Qinggan, label: "情感共鸣", note: "讲人和故事，唤起共鸣后自然带出品牌，转化路径更软。" },
    { val: CopyAiTypeEnum.Jiemi, label: "揭秘避坑", note: "用「内行人才知道」的口吻拆行业内幕，信任感与收藏率高。" },
    { val: CopyAiTypeEnum.Custom, label: "✏️ 自定义", note: "用一句话描述想要的类型，AI 按你的描述生成。" },
];
/** 文案生成类型：枚举 ↔ 接口数字（copywriting_generation_type 1~7） */
const COPY_AI_TYPE_TO_API: Record<string, number> = {
    [CopyAiTypeEnum.Ganhuo]: 1,
    [CopyAiTypeEnum.Zhongcao]: 2,
    [CopyAiTypeEnum.Guandian]: 3,
    [CopyAiTypeEnum.Juqing]: 4,
    [CopyAiTypeEnum.Qinggan]: 5,
    [CopyAiTypeEnum.Jiemi]: 6,
    [CopyAiTypeEnum.Custom]: 7,
};
const API_TO_COPY_AI_TYPE: Record<string, CopyAiTypeEnum> = Object.fromEntries(
    Object.entries(COPY_AI_TYPE_TO_API).map(([k, v]) => [String(v), k as CopyAiTypeEnum]),
);

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
    [MusicSourceEnum.System]:
        "默认每次随机。指定一首后，人设自动出片、设备发布、热点已选人设、爆款人设模式都会用这一首。闪剪手动创建仍当次另选。",
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
    [MaterialSourceEnum.Square]: 4,
};
const COPY_SOURCE_TO_API: Record<string, number> = {
    [CopySourceEnum.Rewrite]: 1,
    [CopySourceEnum.AIGen]: 2,
    [CopySourceEnum.None]: 3,
    [CopySourceEnum.Lib]: 4,
    [CopySourceEnum.Hotspot]: 5,
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

/** 素材模式：枚举 ↔ 人设接口 material_use_mode（1 自动适配 / 2 原图） */
const MATERIAL_KIND_TO_API: Record<string, number> = {
    [MaterialKindEnum.Auto]: 1,
    [MaterialKindEnum.Original]: 2,
};

/** 运行模式：枚举 ↔ 人设接口 run_mode（1 手机模式 / 2 无手机模式） */
const RUN_MODE_TO_API: Record<string, number> = {
    [RunModeEnum.Phone]: 1,
    [RunModeEnum.Cloud]: 2,
};

const invertMap = <K extends string, V extends string | number>(map: Record<K, V>) =>
    Object.fromEntries(Object.entries(map).map(([k, v]) => [String(v), k])) as Record<string, K>;

const API_TO_SYNTH_TYPE = invertMap(SYNTH_TYPE_TO_API);
const API_TO_MATERIAL_SOURCE = invertMap(MATERIAL_SOURCE_TO_API);
const API_TO_COPY_SOURCE = invertMap(COPY_SOURCE_TO_API);
const API_TO_COVER_SOURCE = invertMap(COVER_SOURCE_TO_API);
const API_TO_LIB_USE_MODE = invertMap(LIB_USE_MODE_TO_API);
const API_TO_LIB_RULE = invertMap(LIB_RULE_TO_API);
const API_TO_MATERIAL_KIND = invertMap(MATERIAL_KIND_TO_API);
const API_TO_RUN_MODE = invertMap(RUN_MODE_TO_API);

// ─── State ─────────────────────────────────────────────────────────
interface InnerState {
    id: string | number;
    synthTypes: SynthTypeEnum[];
    materialSource: MaterialSourceEnum;
    copySource: CopySourceEnum;
    /** AI生成 · 文案生成类型 */
    copyAiType: CopyAiTypeEnum;
    /** AI生成 · 自定义类型描述 */
    copyAiCustom: string;
    coverSource: CoverSourceEnum;
    coverImage: string;
    // 工作方式 / 成品库为本地 UI 态；文案库规则走 library_* 接口字段
    workMode: WorkModeEnum;
    /** 运行模式：人设接口 run_mode（1 手机 / 2 无手机），随规则一起提交 */
    runMode: RunModeEnum;
    /** 素材模式：只读回显人设接口 material_use_mode（合成配置详情双写同一份），不随规则提交 */
    materialKind: MaterialKindEnum;
    /** 每日出片条数：仅无手机模式提交与生效 */
    dailyVideoCount: number;
    libUseMode: LibUseModeEnum;
    libRandomRule: LibRuleEnum;
    newsDuration: number;
    copyLibUseMode: LibUseModeEnum;
    copyLibRandomRule: LibRuleEnum;
    /** 热点生成 · 取用规则，提交 hotspot_pick_mode：1 最新入库优先 / 2 每条只用一次 */
    hotspotPickMode: HotspotPickModeEnum;
    /** 视频模板：key 为 generation_types 数字（1/3/4） */
    templateConfig: TemplateConfigMap;
    /** 背景音乐来源：1 系统 / 2 人设 / 3 无 */
    musicSource: MusicSourceEnum;
    systemMusicId: string;
    systemMusicName: string;
    systemMusicEnabled: boolean;
    /** 音乐音量 0~1，一位小数 */
    musicVolume: number;
    /** 口播语速 0.5~2，一位小数 */
    speechRate: number;
    /** 素材库+广场时限定的分组 */
    squareGroupIds: number[];
}

const DEFAULT_INNER_STATE = (): InnerState => ({
    id: "",
    synthTypes: [SynthTypeEnum.DigitalHuman],
    materialSource: MaterialSourceEnum.AILib,
    copySource: CopySourceEnum.Rewrite,
    copyAiType: CopyAiTypeEnum.Ganhuo,
    copyAiCustom: "",
    coverSource: CoverSourceEnum.AI,
    coverImage: "",
    workMode: WorkModeEnum.Ai,
    runMode: RunModeEnum.Phone,
    materialKind: MaterialKindEnum.Auto,
    dailyVideoCount: DAILY_VIDEO_COUNT.default,
    libUseMode: LibUseModeEnum.Random,
    libRandomRule: LibRuleEnum.Once,
    newsDuration: NEWS_DURATION.default,
    copyLibUseMode: LibUseModeEnum.Random,
    copyLibRandomRule: LibRuleEnum.Once,
    hotspotPickMode: HotspotPickModeEnum.Latest,
    templateConfig: {},
    musicSource: MusicSourceEnum.System,
    systemMusicId: "",
    systemMusicName: "",
    systemMusicEnabled: true,
    musicVolume: MUSIC_VOLUME_DEFAULT,
    speechRate: SPEECH_RATE_DEFAULT,
    squareGroupIds: [],
});

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const loading = ref(false);
const personId = ref<string | number>("");
const isMaterialEmpty = ref(false);
const squareGroups = ref<{ id: number; name: string; enabled: number }[]>([]);
const templatePickerVisible = ref(false);

const innerState = reactive<InnerState>(DEFAULT_INNER_STATE());

const hasDigitalHuman = computed(() => innerState.synthTypes.includes(SynthTypeEnum.DigitalHuman));
const hasNews = computed(() => innerState.synthTypes.includes(SynthTypeEnum.News));
const hasCopyLib = computed(() => innerState.copySource === CopySourceEnum.Lib);
const hasCopyAiGen = computed(() => innerState.copySource === CopySourceEnum.AIGen);
const hasCopyHotspot = computed(() => innerState.copySource === CopySourceEnum.Hotspot);
const currentHotspotPickNote = computed(
    () => HOTSPOT_PICK_MODE_OPTIONS.find((o) => o.val === innerState.hotspotPickMode)?.note ?? "",
);
const currentCopyAiTypeNote = computed(() => {
    const custom = innerState.copyAiCustom.trim();
    if (innerState.copyAiType === CopyAiTypeEnum.Custom && custom) return `将按「${custom}」的方向生成文案。`;
    return COPY_AI_TYPE_OPTIONS.find((o) => o.val === innerState.copyAiType)?.note ?? "";
});

const activeSynthApiTypes = computed(() =>
    innerState.synthTypes.map((t) => SYNTH_TYPE_TO_API[t]).filter((n): n is number => typeof n === "number"),
);

const templateSummary = computed(() =>
    buildTemplateSummary(activeSynthApiTypes.value, innerState.templateConfig),
);

const currentWorkModeNote = computed(() => WORK_MODE_NOTES[innerState.workMode]);
const currentRunModeNote = computed(() => RUN_MODE_NOTES[innerState.runMode]);
const isCloudRun = computed(() => innerState.runMode === RunModeEnum.Cloud);
const currentMaterialKindNote = computed(() => MATERIAL_KIND_NOTES[innerState.materialKind]);
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
    { val: CopySourceEnum.Hotspot, label: "热点生成", locked: false },
    { val: CopySourceEnum.Lib, label: "文案库", locked: false },
    // { val: CopySourceEnum.None, label: '无需文案', locked: !canUseNoCopy.value }
]);

const isMaterialConflict = computed(
    () => innerState.materialSource === MaterialSourceEnum.PureLib && isMaterialEmpty.value,
);

const currentMaterialHint = computed(() => MATERIAL_HINTS[innerState.materialSource]);
const currentCopyHint = computed(() => {
    const hint = COPY_HINTS[innerState.copySource] ?? "";
    // 无手机模式下仿写照常可用，但爆款只能手动导入：自动抓取要靠绑定手机去平台搜
    if (isCloudRun.value && innerState.copySource === CopySourceEnum.Rewrite) {
        return hint + "无手机模式下爆款需要在爆款库手动导入分享链接（热点追踪的自动抓取要绑手机）。";
    }
    return hint;
});
const currentCoverHint = computed(() => COVER_HINTS[innerState.coverSource]);
const currentMusicSourceHint = computed(
    () => MUSIC_SOURCE_HINTS[innerState.musicSource] || MUSIC_SOURCE_HINTS[MusicSourceEnum.System],
);
const showSystemMusic = ref(false);
const systemMusics = ref<any[]>([]);
const pickMusicSource = (val: MusicSourceEnum) => {
    innerState.musicSource = val;
    if (val !== MusicSourceEnum.System) clearSystemMusic();
};
const clearSystemMusic = () => {
    innerState.systemMusicId = "";
    innerState.systemMusicName = "";
    innerState.systemMusicEnabled = true;
};
const onSystemMusic = (id: string, name: string) => {
    innerState.systemMusicId = id;
    innerState.systemMusicName = name;
    innerState.systemMusicEnabled = id !== "";
    showSystemMusic.value = false;
};

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
    square_group_ids:
        innerState.materialSource === MaterialSourceEnum.Square ? innerState.squareGroupIds : [],
    copywriting_source: COPY_SOURCE_TO_API[innerState.copySource],
    hotspot_pick_mode: normalizeHotspotPickMode(innerState.hotspotPickMode),
    copywriting_generation_type: COPY_AI_TYPE_TO_API[innerState.copyAiType],
    copywriting_generation_custom:
        innerState.copyAiType === CopyAiTypeEnum.Custom ? innerState.copyAiCustom.trim() : "",
    library_use_mode: LIB_USE_MODE_TO_API[innerState.copyLibUseMode],
    library_reuse_mode: LIB_RULE_TO_API[innerState.copyLibRandomRule],
    video_cover_source: COVER_SOURCE_TO_API[innerState.coverSource],
    news_mixcut_duration: innerState.newsDuration,
    pic: innerState.coverImage,
    work_mode: innerState.workMode === WorkModeEnum.Lib ? 2 : 1,
    run_mode: RUN_MODE_TO_API[innerState.runMode],
    // 手机模式不提交条数，避免把无手机模式的配置带进按发布时段出片的链路
    daily_video_count: isCloudRun.value ? innerState.dailyVideoCount : 0,
    product_use_mode: LIB_USE_MODE_TO_API[innerState.libUseMode],
    product_reuse_mode: LIB_RULE_TO_API[innerState.libRandomRule],
    music_source: normalizeMusicSource(innerState.musicSource),
    system_music_id:
        normalizeMusicSource(innerState.musicSource) === MusicSourceEnum.System ? innerState.systemMusicId : "",
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
        innerState.squareGroupIds = Array.isArray(res.square_group_ids)
            ? res.square_group_ids.map((id: any) => Number(id)).filter(Boolean)
            : [];
        innerState.copySource =
            (API_TO_COPY_SOURCE[String(res.copywriting_source)] as CopySourceEnum) ?? CopySourceEnum.Rewrite;
        innerState.hotspotPickMode = normalizeHotspotPickMode(res.hotspot_pick_mode);
        innerState.copyAiType =
            API_TO_COPY_AI_TYPE[String(res.copywriting_generation_type)] ?? CopyAiTypeEnum.Ganhuo;
        innerState.copyAiCustom = String(res.copywriting_generation_custom ?? "").slice(0, COPY_AI_CUSTOM_MAX);
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
        innerState.systemMusicId = String(res.system_music_id || "");
        innerState.systemMusicName = String(res.system_music_name || "");
        innerState.systemMusicEnabled = res.system_music_enabled !== false;
        systemMusics.value = Array.isArray(res.system_musics) ? res.system_musics : [];
        innerState.musicVolume = normalizeMusicVolume(res.music_volume ?? MUSIC_VOLUME_DEFAULT);
        innerState.speechRate = normalizeSpeechRate(res.speech_rate ?? SPEECH_RATE_DEFAULT);
        innerState.materialKind =
            (API_TO_MATERIAL_KIND[String(res.material_use_mode)] as MaterialKindEnum) ?? MaterialKindEnum.Auto;
        innerState.runMode = (API_TO_RUN_MODE[String(res.run_mode)] as RunModeEnum) ?? RunModeEnum.Phone;
        const dailyCount = Number(res.daily_video_count);
        innerState.dailyVideoCount =
            Number.isFinite(dailyCount) && dailyCount >= DAILY_VIDEO_COUNT.min && dailyCount <= DAILY_VIDEO_COUNT.max
                ? dailyCount
                : DAILY_VIDEO_COUNT.default;
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
    getSquareGroups()
        .then((res: any) => {
            squareGroups.value = (res?.groups || []).filter((item: any) => Number(item.enabled) === 1);
        })
        .catch(() => {
            squareGroups.value = [];
        });
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
.rule-seg-copy .rule-seg-btn {
    @apply whitespace-nowrap px-1 text-[11px];
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

/* AI生成 · 文案生成类型 */
.rule-sub-ai {
    @apply mt-3 pl-3;
    border-left: 2px solid #e3e9f2;
}
.rule-chips {
    @apply flex flex-wrap gap-2;
}
.rule-chip {
    @apply px-3 py-1.5 rounded-full text-xs cursor-pointer select-none transition-all;
    border: 1px solid #e3e9f2;
    background: #ffffff;
    color: #4b5563;
}
.rule-chip:hover {
    border-color: #2f73f6;
    color: #2f73f6;
}
.rule-chip.sel {
    @apply font-semibold;
    border-color: #2f73f6;
    background: #2f73f6;
    color: #ffffff;
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

.music-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 420px;
    overflow-y: auto;
}
.music-row {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    height: 40px;
    padding: 0 12px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #1d2129;
    font-size: 13px;
    text-align: left;
    cursor: pointer;
}
.music-row.sel {
    border-color: var(--el-color-primary);
    color: var(--el-color-primary);
}
.music-row-name {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.music-empty {
    padding: 24px 0;
    text-align: center;
    font-size: 12px;
    color: #86909c;
}
</style>
