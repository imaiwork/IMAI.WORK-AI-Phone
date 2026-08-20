<template>
    <div class="flex gap-x-3 h-full min-w-[1000px] overflow-hidden">
        <div class="flex-1 h-full">
            <material-group-panel ref="materialGroupPanelRef" v-model="formData.materialGroups" is-storyboard />
        </div>

        <div class="basis-[43%] bg-white flex flex-col relative flex-shrink-0 rounded-[20px] p-6 border border-br">
            <header class="mb-5">
                <h2 class="text-[24px] font-medium text-slate-800 tracking-tight">生成设置</h2>
                <div class="h-1 w-12 bg-primary rounded-full mt-2"></div>
            </header>

            <ElScrollbar ref="rightScrollbarRef" class="flex-1 -mr-4 pr-4">
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
                                @click="handleCopywritingMode(item.type)">
                                <Icon :name="item.icon" :size="12" />
                                {{ item.name }}
                            </button>
                        </div>
                    </div>

                    <section class="bg-slate-50 rounded-[24px] border border-br overflow-hidden flex flex-col">
                        <montage-copywriting-editor
                            v-if="copywritingMode === CopywritingTypeEnum.TEXT"
                            ref="copywritingEditorRef"
                            v-model="formData.copywriting"
                            :show-title="false"
                            :prompt-type="CreateVideoTypeEnum.STORYBOARD" />

                        <template v-else-if="copywritingMode === CopywritingTypeEnum.CLIP">
                            <div
                                v-if="formData.materialGroups.length === 0"
                                class="flex flex-col items-center justify-center py-10 gap-3 text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center">
                                    <Icon name="el-icon-Film" :size="24" class="opacity-40" />
                                </div>
                                <p class="text-[12px] font-medium">请先在左侧添加素材分组</p>
                            </div>

                            <div v-else class="flex flex-col">
                                <div
                                    class="flex items-center justify-between px-4 py-2.5 bg-[#ffffff]/60 border-b border-slate-100">
                                    <div class="flex items-center gap-1.5">
                                        <Icon name="el-icon-View" :size="13" color="var(--color-primary)" />
                                        <span class="text-[12px] font-black text-slate-500 uppercase tracking-wider">
                                            镜头匹配文案
                                        </span>
                                        <span
                                            class="ml-1 px-1.5 py-0.5 rounded-full bg-[#0065fb]/10 text-primary text-[10px] font-black">
                                            {{ formData.materialGroups.length }} 组
                                        </span>
                                    </div>
                                    <button
                                        class="text-[11px] text-slate-400 hover:text-red-400 transition-colors font-medium flex items-center gap-1"
                                        @click="handleClearAllClipCopywriting">
                                        <Icon name="el-icon-Delete" :size="11" />
                                        全部清空
                                    </button>
                                </div>

                                <ElScrollbar ref="clipScrollbarRef" max-height="200px">
                                    <div class="divide-y divide-slate-100">
                                        <div
                                            v-for="(group, index) in formData.materialGroups"
                                            :key="group.id"
                                            :data-clip-group-id="group.id"
                                            class="px-4 py-3 flex flex-col gap-2 transition-colors duration-300"
                                            :class="
                                                clipErrorGroupId === group.id
                                                    ? 'bg-[#fef2f2]/70'
                                                    : 'bg-[#ffffff]/40 hover:bg-[#ffffff]/70'
                                            ">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        :class="[
                                                            'w-5 h-5 rounded-full text-white text-[10px] font-black flex items-center justify-center flex-shrink-0 shadow-sm transition-colors',
                                                            clipErrorGroupId === group.id ? 'bg-red-400' : 'bg-primary',
                                                        ]">
                                                        {{ index + 1 }}
                                                    </span>
                                                    <span class="text-[13px] font-black text-slate-700">
                                                        镜头组 {{ index + 1 }}
                                                    </span>
                                                    <span
                                                        v-if="group.name"
                                                        class="text-[11px] text-slate-400 font-medium truncate max-w-[100px]">
                                                        · {{ group.name }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        :class="[
                                                            'text-[11px] font-medium transition-colors',
                                                            (clipCopywritingMap[group.id] || '').length > 450
                                                                ? (clipCopywritingMap[group.id] || '').length >= 500
                                                                    ? 'text-red-400'
                                                                    : 'text-amber-400'
                                                                : 'text-slate-300',
                                                        ]">
                                                        {{ (clipCopywritingMap[group.id] || "").length }}/500
                                                    </span>
                                                    <button
                                                        v-if="(clipCopywritingMap[group.id] || '').length > 0"
                                                        class="w-5 h-5 rounded-full hover:bg-red-50 flex items-center justify-center text-slate-300 hover:text-red-400 transition-all"
                                                        title="清空此条文案"
                                                        @click="handleClearSingleClip(group.id)">
                                                        <Icon name="el-icon-Close" :size="10" />
                                                    </button>
                                                </div>
                                            </div>

                                            <ElInput
                                                v-model="clipCopywritingMap[group.id]"
                                                type="textarea"
                                                :maxlength="500"
                                                :show-word-limit="false"
                                                :autosize="{ minRows: 2, maxRows: 5 }"
                                                :placeholder="`输入镜头组 ${index + 1} 的口播文案…`"
                                                resize="none"
                                                :class="[
                                                    'clip-textarea',
                                                    clipErrorGroupId === group.id ||
                                                    (clipCopywritingMap[group.id] || '').length >= 500
                                                        ? 'is-error'
                                                        : '',
                                                ]"
                                                @input="onClipInput(group.id)" />

                                            <Transition name="fade">
                                                <p
                                                    v-if="clipErrorGroupId === group.id"
                                                    class="text-[11px] text-red-400 flex items-center gap-1">
                                                    <Icon name="el-icon-WarningFilled" :size="11" />
                                                    {{ clipErrorMsg }}
                                                </p>
                                            </Transition>
                                        </div>
                                    </div>
                                </ElScrollbar>
                            </div>
                        </template>
                    </section>

                    <section class="space-y-3">
                        <div class="bg-slate-50 rounded-[20px] border border-br overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 bg-[#f8fafc]/80">
                                <span class="text-[13px] font-black text-slate-500 uppercase tracking-wider">
                                    使用设置
                                </span>
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

                            <div class="px-4 py-3 border-b border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[13px] font-medium text-slate-700">视频顶部标题</span>
                                        <span class="text-[10px] text-slate-400">为视频添加顶部展示标题</span>
                                    </div>
                                </div>
                                <div
                                    class="h-[44px] rounded-xl bg-white border border-slate-200 flex items-center px-3 cursor-pointer hover:border-primary transition-all group"
                                    @click="openTopTitleDialog">
                                    <span
                                        class="text-primary mr-2 group-hover:scale-110 transition-transform leading-[0]">
                                        <Icon name="el-icon-Tickets" :size="16" />
                                    </span>
                                    <span class="text-[12px] font-medium text-slate-600 flex-1 truncate">
                                        {{
                                            formData.topTitleList.length > 0
                                                ? `已添加 ${formData.topTitleList.length} 个标题`
                                                : "点击添加顶部标题"
                                        }}
                                    </span>
                                    <template v-if="formData.topTitleList.length > 0">
                                        <button
                                            class="w-5 h-5 rounded-full hover:bg-red-50 flex items-center justify-center text-slate-300 hover:text-red-400 transition-all mr-1"
                                            @click.stop="formData.topTitleList = []">
                                            <Icon name="el-icon-Close" :size="10" />
                                        </button>
                                    </template>
                                    <Icon name="el-icon-ArrowRight" color="var(--slate-300)" :size="14" />
                                </div>
                                <ElScrollbar v-if="formData.topTitleList.length > 0" max-height="200px">
                                    <div class="mt-2 flex flex-col gap-1.5">
                                        <div
                                            v-for="(title, index) in formData.topTitleList"
                                            :key="index"
                                            class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-slate-100 group">
                                            <span
                                                class="w-4 h-4 rounded-full bg-[#0065fb]/10 text-primary text-[10px] font-black flex items-center justify-center flex-shrink-0">
                                                {{ index + 1 }}
                                            </span>
                                            <span class="flex-1 text-[12px] text-slate-600 truncate">
                                                {{ title.text || title.title || title }}
                                            </span>
                                            <button
                                                class="opacity-0 group-hover:opacity-100 w-4 h-4 rounded-full hover:bg-red-50 flex items-center justify-center text-slate-300 hover:text-red-400 transition-all"
                                                @click.stop="handleRemoveTopTitle(index)">
                                                <Icon name="el-icon-Close" :size="9" />
                                            </button>
                                        </div>
                                    </div>
                                </ElScrollbar>
                            </div>

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
                                                : "AI音乐库"
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
                                            v-if="formData.voice">
                                            {{ `已选 ${formData.voice.name}` }}
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-1.5 rounded-lg bg-[#0065fb]/10 text-primary text-[10px] font-medium">
                                            请选择音色
                                        </span>
                                    </span>
                                    <template v-if="formData.voice">
                                        <button
                                            class="w-5 h-5 rounded-full hover:bg-red-50 flex items-center justify-center text-slate-300 hover:text-red-400 transition-all mr-1"
                                            @click.stop="formData.voice = null">
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
                                :disabled="formData.extra.video_count <= VIDEO_COUNT_MIN"
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
                                :disabled="formData.extra.video_count >= VIDEO_COUNT_MAX"
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
        :type="2"
        :show-user-tone="copywritingMode === CopywritingTypeEnum.TEXT"
        :show-original-tone="false"
        :model-version="`${DigitalHumanModelVersionEnum.MINIMAX_HD},${DigitalHumanModelVersionEnum.MINIMAX_TURBO}`"
        @confirm="handleToneConfirm"
        @close="showToneDialog = false" />

    <choose-music
        v-if="showMusicDialog"
        ref="chooseMusicRef"
        :selected="formData.music"
        @confirm="handleMusicConfirm"
        @close="showMusicDialog = false" />

    <montage-storyboard-title
        v-if="showTopTitleDialog"
        ref="topTitleDialogRef"
        :data="formData.topTitleList"
        @confirm="handleTopTitleConfirm"
        @close="showTopTitleDialog = false" />

    <cost-pop
        ref="costPopRef"
        v-if="showTokensCost"
        :type="MontageTypeEnum.STORYBOARD_MIX"
        @close="showTokensCost = false" />
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { useUserStore } from "@/stores/user";
import { createMontageStoryboard } from "@/api/digital_human";
import {
    DigitalHumanModelVersionEnum,
    MontageTypeEnum,
    SidebarTypeEnum,
    CreateVideoTypeEnum,
} from "@/pages/app/digital_human/_enums";
import MaterialGroupPanel from "@/pages/app/digital_human/_components/material-group-panel.vue";
import MontageCopywritingEditor from "@/pages/app/digital_human/_components/montage-copywriting-editor.vue";
import ChooseMusic from "@/pages/app/digital_human/_components/choose-music.vue";
import ChooseTone from "@/pages/app/digital_human/_components/choose-tone.vue";
import CostPop from "@/pages/app/digital_human/_components/cost-pop.vue";
import MontageStoryboardTitle from "./_components/top-title-pop.vue";

// ──────────────────────────────────────────────────────────────
// 常量
// ──────────────────────────────────────────────────────────────
const VIDEO_COUNT_MIN = 1;
const VIDEO_COUNT_MAX = 99;
const CLIP_TEXT_MAX = 500;

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
    useOriginalAudio?: boolean;
}
interface CopywritingAudio {
    id: number;
    url: string;
    name: string;
}
interface ClipCopywritingItem {
    groupId: string;
    groupIndex: number;
    label: string;
    text: string;
}

enum CopywritingTypeEnum {
    TEXT = "text",
    CLIP = "clip",
}

// ──────────────────────────────────────────────────────────────
// 表单数据
// ──────────────────────────────────────────────────────────────
const formData = reactive<{
    materialGroups: MaterialGroup[];
    copywriting: any[];
    name: string;
    shanjian_type: MontageTypeEnum;
    voice: any;
    music: any[];
    clip: ClipCopywritingItem[];
    topTitleList: any[];
    extra: {
        volume: number;
        soundSwitch: boolean;
        human: number;
        music: number;
        clip: number;
        video_count: number;
    };
    audio: CopywritingAudio[];
}>({
    name: dayjs().format("YYYYMMDDHHmm") + "分镜混剪",
    materialGroups: [],
    copywriting: [],
    shanjian_type: MontageTypeEnum.MATERIAL_MIX,
    voice: null,
    music: [],
    clip: [],
    topTitleList: [],
    extra: { volume: 0.2, soundSwitch: false, human: 0, music: 0, clip: 0, video_count: 1 },
    audio: [],
});

const copywritingMode = ref<CopywritingTypeEnum>(CopywritingTypeEnum.TEXT);

const copywriterTypeIndexList = [
    { name: "按顺序文案", icon: "el-icon-Document", type: CopywritingTypeEnum.TEXT },
    { name: "镜头匹配文案", icon: "el-icon-View", type: CopywritingTypeEnum.CLIP },
];

const usageToggleRows = [
    { key: "music", label: "背景音乐使用", desc: "多首音乐时的播放顺序", options: ["按顺序", "随机"] },
];

const copywritingEditorRef = shallowRef<InstanceType<typeof MontageCopywritingEditor>>();

const materialGroupPanelRef = shallowRef<InstanceType<typeof MaterialGroupPanel>>();
const clipScrollbarRef = shallowRef();

// ──────────────────────────────────────────────────────────────
// 右侧：滚动到指定镜头组文案行（在 clipScrollbar 内部定位）
// ──────────────────────────────────────────────────────────────
const scrollToClipGroup = async (groupId: string) => {
    await nextTick();
    const wrapEl = clipScrollbarRef.value?.wrapRef;
    if (!wrapEl) return;
    const target = wrapEl.querySelector(`[data-clip-group-id="${groupId}"]`) as HTMLElement | null;
    if (!target) return;
    clipScrollbarRef.value?.setScrollTop(target.offsetTop - 8);
};

// ──────────────────────────────────────────────────────────────
// 镜头匹配文案：草稿 Map + 错误状态
// ──────────────────────────────────────────────────────────────
const clipCopywritingMap = reactive<Record<string, string>>({});
const clipErrorGroupId = ref<string | null>(null);
const clipErrorMsg = ref("");
let clipErrorClearTimer: ReturnType<typeof setTimeout> | null = null;

const setClipError = (groupId: string, msg: string) => {
    if (clipErrorClearTimer) clearTimeout(clipErrorClearTimer);
    clipErrorGroupId.value = groupId;
    clipErrorMsg.value = msg;
    clipErrorClearTimer = setTimeout(() => {
        clipErrorGroupId.value = null;
        clipErrorMsg.value = "";
    }, 3000);
};

const syncClipToFormData = () => {
    formData.clip = formData.materialGroups.map((group, index) => ({
        groupId: group.id,
        groupIndex: index,
        label: `镜头组${index + 1}`,
        text: clipCopywritingMap[group.id] ?? "",
    }));
};

const onClipInput = (groupId: string) => {
    if (clipErrorGroupId.value === groupId) {
        clipErrorGroupId.value = null;
        clipErrorMsg.value = "";
        if (clipErrorClearTimer) clearTimeout(clipErrorClearTimer);
    }
    syncClipToFormData();
};

const handleClearSingleClip = (groupId: string) => {
    clipCopywritingMap[groupId] = "";
    if (clipErrorGroupId.value === groupId) {
        clipErrorGroupId.value = null;
        clipErrorMsg.value = "";
        if (clipErrorClearTimer) clearTimeout(clipErrorClearTimer);
    }
    syncClipToFormData();
};

const handleClearAllClipCopywriting = () => {
    for (const key in clipCopywritingMap) clipCopywritingMap[key] = "";
    clipErrorGroupId.value = null;
    clipErrorMsg.value = "";
    if (clipErrorClearTimer) clearTimeout(clipErrorClearTimer);
    syncClipToFormData();
};

/** 监听素材分组增删，联动维护草稿 Map */
watch(
    () => formData.materialGroups,
    (newGroups, oldGroups) => {
        const newIds = new Set(newGroups.map((g) => g.id));
        const oldIds = new Set((oldGroups ?? []).map((g) => g.id));
        for (const id of oldIds) {
            if (!newIds.has(id)) {
                delete clipCopywritingMap[id];
                if (clipErrorGroupId.value === id) {
                    clipErrorGroupId.value = null;
                    clipErrorMsg.value = "";
                }
            }
        }
        for (const id of newIds) {
            if (!(id in clipCopywritingMap)) clipCopywritingMap[id] = "";
        }
        syncClipToFormData();
    },
    { deep: true },
);

// ──────────────────────────────────────────────────────────────
// 口播来源切换
// ──────────────────────────────────────────────────────────────
const handleCopywritingMode = (type: CopywritingTypeEnum) => {
    copywritingMode.value = type;
    if (type === CopywritingTypeEnum.CLIP && formData.voice?.id) formData.voice = null;
    clipErrorGroupId.value = null;
    clipErrorMsg.value = "";
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
};
const handleMusicConfirm = (data: { music: any[] }) => {
    formData.music = data.music;
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
    formData.voice = tone ?? null;
};

// ──────────────────────────────────────────────────────────────
// 视频顶部标题
// ──────────────────────────────────────────────────────────────
const showTopTitleDialog = ref(false);
const topTitleDialogRef = shallowRef<InstanceType<typeof MontageStoryboardTitle>>();
const openTopTitleDialog = async () => {
    showTopTitleDialog.value = true;
    await nextTick();
    topTitleDialogRef.value?.open();
};
const handleTopTitleConfirm = (data: any[]) => {
    formData.topTitleList = data;
    showTopTitleDialog.value = false;
};
const handleRemoveTopTitle = (index: number) => {
    formData.topTitleList.splice(index, 1);
};

// ──────────────────────────────────────────────────────────────
// 生成数量
// ──────────────────────────────────────────────────────────────
const handleMinusVideoCount = (type: "minus" | "add") => {
    if (type === "minus" && formData.extra.video_count > VIDEO_COUNT_MIN) formData.extra.video_count--;
    if (type === "add" && formData.extra.video_count < VIDEO_COUNT_MAX) formData.extra.video_count++;
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
// 提交校验
// ──────────────────────────────────────────────────────────────
const validateBeforeCreate = async (): Promise<boolean> => {
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return false;
    }

    if (!formData.name.trim()) {
        feedback.msgWarning("请输入视频名称");
        return false;
    }

    if (formData.extra.video_count < VIDEO_COUNT_MIN) {
        feedback.msgWarning(`视频数量最少为 ${VIDEO_COUNT_MIN}`);
        return false;
    }
    if (formData.extra.video_count > VIDEO_COUNT_MAX) {
        feedback.msgWarning(`视频数量最多为 ${VIDEO_COUNT_MAX}`);
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

    if (copywritingMode.value === CopywritingTypeEnum.CLIP) {
        syncClipToFormData();

        for (const item of formData.clip) {
            if (!item.text.trim()) {
                const msg = "文案不能为空";
                setClipError(item.groupId, msg);
                feedback.msgWarning(`「${item.label}」的${msg}`);
                await scrollToClipGroup(item.groupId);
                return false;
            }
            if (item.text.length > CLIP_TEXT_MAX) {
                const msg = `文案不能超过 ${CLIP_TEXT_MAX} 字符`;
                setClipError(item.groupId, msg);
                feedback.msgWarning(`「${item.label}」${msg}`);
                await scrollToClipGroup(item.groupId);
                return false;
            }
        }

        clipErrorGroupId.value = null;
        clipErrorMsg.value = "";
    }

    return true;
};

// ──────────────────────────────────────────────────────────────
// 提交参数构建
// ──────────────────────────────────────────────────────────────
const buildCreateParams = (): Record<string, any> => {
    const mediaGroupArray = formData.materialGroups.map((group) => ({
        GroupName: group.name,
        MediaArray: group.materialList.map((m) => m.url),
        Volume: group.useOriginalAudio ? 1 : 0,
    }));

    const totalDuration = formData.materialGroups.reduce((groupAcc, group) => {
        return (
            groupAcc +
            group.materialList.reduce((matAcc, mat) => {
                return matAcc + (mat.type === "video" ? Number(mat.duration) : 5);
            }, 0)
        );
    }, 0);

    const params: Record<string, any> = {
        name: formData.name,
        number: formData.extra.video_count,
        duration: Number(totalDuration.toFixed(2)),
        minimax_voice_id: formData.voice?.id || undefined,
        system_voice_code: formData.voice?.code || undefined,
        TitleArray: formData.topTitleList.map((item: any) => item.text),
        MediaGroupArray: mediaGroupArray,
        BackgroundMusicArray: formData.music.map((item: any) => item.content),
        BackgroundMusicVolume: formData.extra.volume,
    };

    if (copywritingMode.value === CopywritingTypeEnum.TEXT) {
        params.SpeechTextArray = formData.copywriting.map((item: any) => item.content);
    } else {
        syncClipToFormData();
        for (let i = 0; i < mediaGroupArray.length; i++) {
            const clipItem = formData.clip[i];
            params.MediaGroupArray[i].SpeechTextArray = clipItem?.text ? [clipItem.text] : [];
        }
    }

    return params;
};

// ──────────────────────────────────────────────────────────────
// 生成视频
// ──────────────────────────────────────────────────────────────
const isSubmitting = ref(false);

const handleCreateVideo = async () => {
    if (!(await validateBeforeCreate())) return;
    try {
        isSubmitting.value = true;

        await createMontageStoryboard(buildCreateParams());
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

.clip-textarea {
    :deep(.el-textarea__inner) {
        background: #fff;
        border-radius: 12px;
        border-color: #e2e8f0;
        font-size: 13px;
        color: #334155;
        resize: none;
        transition: border-color 0.2s, box-shadow 0.2s;

        &:focus {
            border-color: var(--el-color-primary);
            box-shadow: 0 0 0 2px rgba(0, 101, 251, 0.08);
        }
    }

    &.is-error :deep(.el-textarea__inner) {
        border-color: #f87171 !important;
        box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.12) !important;
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
</style>
