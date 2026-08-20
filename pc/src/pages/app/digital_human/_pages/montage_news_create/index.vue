<template>
    <div class="flex gap-x-3 h-full min-w-[1000px] overflow-hidden">
        <div class="flex-1 h-full">
            <material-group-panel v-model="formData.materialGroups" />
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
                    <section class="bg-slate-50 rounded-[20px] p-3 border border-br">
                        <montage-character-design-editor
                            v-model:person-name="formData.person_name"
                            v-model:person-introduction="formData.person_introduction" />
                    </section>
                    <section class="bg-slate-50 rounded-[24px] border border-br overflow-hidden flex flex-col">
                        <montage-copywriting-editor
                            ref="copywritingEditorRef"
                            v-model="formData.copywriting"
                            :system-agent-ids="[2, 6]"
                            :show-title="false"
                            :prompt-type="CreateVideoTypeEnum.NEWS" />
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
                                            (formData.extra as any)[row.key] === idx ? 'bg-primary text-white shadow-sm' : 'text-slate-400 hover:text-slate-600',
                                        ]">
                                        {{ btn }}
                                    </button>
                                </div>
                            </div>

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
                                        <template v-if="formData.clip.length > 0"
                                            >已选
                                            <span class="text-primary font-black">{{ formData.clip.length }}</span>
                                            个风格</template
                                        >
                                        <template v-else>点击选择视频风格</template>
                                    </span>
                                    <Icon name="el-icon-ArrowRight" color="var(--slate-300)" :size="14" />
                                </div>
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
                                                : formData.extra.ai_music
                                                  ? "AI音乐库"
                                                  : "无"
                                        }}
                                    </span>
                                    <Icon name="el-icon-ArrowRight" color="var(--slate-300)" :size="14" />
                                </div>
                            </div>
                            <div class="px-4 py-3 border-b border-slate-100">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[13px] font-medium text-slate-700">BGM 音量</span>
                                        <span class="text-[10px] text-slate-400">背景音乐的音量大小</span>
                                    </div>
                                    <span class="text-[13px] font-black text-primary"
                                        >{{ Math.round(formData.extra.volume * 100) }}%</span
                                    >
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

                    <div
                        class="bg-slate-50 rounded-[20px] border border-br px-4 py-3 flex items-center justify-between">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[13px] font-medium text-slate-700">新闻体时长</span>
                            <span class="text-[10px] text-slate-400">单条新闻体视频的时长（5–300 秒）</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="handleNewsDurationStep('minus')"
                                :disabled="formData.extra.videoDuration <= NEWS_DURATION.min"
                                class="w-8 h-8 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:border-primary hover:text-primary transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                <Icon name="el-icon-Minus" :size="14" />
                            </button>
                            <div class="flex items-center gap-1">
                                <input
                                    v-model="formData.extra.videoDuration"
                                    v-number-input="{ min: NEWS_DURATION.min, max: NEWS_DURATION.max, decimal: 0 }"
                                    type="number"
                                    class="w-14 h-8 text-center text-[15px] font-black text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-[#0065fb]/10 transition-all [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                                <span class="text-[12px] font-semibold text-slate-400">秒</span>
                            </div>
                            <button
                                @click="handleNewsDurationStep('add')"
                                :disabled="formData.extra.videoDuration >= NEWS_DURATION.max"
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

    <montage-styles-choose
        v-if="showClipStyleDialog"
        ref="clipStyleDialogRef"
        :type="MontageStylesType.NEWS"
        :selected="formData.clip"
        @confirm="handleClipStyleConfirm"
        @close="showClipStyleDialog = false" />
    <cost-pop
        ref="costPopRef"
        v-if="showTokensCost"
        :type="MontageTypeEnum.NEWS_BODY"
        @close="showTokensCost = false" />
    <choose-music
        v-if="showMusicDialog"
        ref="chooseMusicRef"
        @confirm="handleMusicConfirm"
        @close="showMusicDialog = false" />
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { useUserStore } from "@/stores/user";
import { createShanjianTask, addShanjianPerson } from "@/api/digital_human";
import {
    MontageTypeEnum,
    SidebarTypeEnum,
    CreateVideoTypeEnum,
    MontageStylesType,
} from "@/pages/app/digital_human/_enums";
import MaterialGroupPanel from "@/pages/app/digital_human/_components/material-group-panel.vue";
import MontageCopywritingEditor from "@/pages/app/digital_human/_components/montage-copywriting-editor.vue";
import MontageCharacterDesignEditor from "@/pages/app/digital_human/_components/montage-character-design-editor.vue";
import MontageStylesChoose from "@/pages/app/digital_human/_components/montage-styles-choose.vue";
import ChooseMusic from "@/pages/app/digital_human/_components/choose-music.vue";
import VideoCoverUpload from "@/pages/app/digital_human/_components/video-cover-upload.vue";
import CostPop from "@/pages/app/digital_human/_components/cost-pop.vue";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

// ──────────────────────────────────────────────────────────────
// 素材分组类型
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

const formData = reactive<{
    materialGroups: MaterialGroup[];
    copywriting: any[];
    person_name: string;
    person_introduction: string;
    name: string;
    shanjian_type: MontageTypeEnum;
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
        videoDuration: number;
    };
    cover: string;
}>({
    name: dayjs().format("YYYYMMDDHHmm") + "新闻体视频",
    materialGroups: [],
    copywriting: [],
    person_name: "",
    person_introduction: "",
    shanjian_type: MontageTypeEnum.NEWS_BODY,
    music: [],
    clip: [],
    extra: {
        ai_music: true,
        volume: 0.5,
        soundSwitch: false,
        human: 0,
        music: 0,
        clip: 0,
        video_count: 1,
        videoDuration: 10,
    },
    cover: "",
});

const usageToggleRows = [
    { key: "music", label: "背景音乐使用", desc: "多首音乐时的播放顺序", options: ["按顺序", "随机"] },
];

const copywritingEditorRef = shallowRef<InstanceType<typeof MontageCopywritingEditor>>();
const totalMaterialCount = computed(() => formData.materialGroups.reduce((acc, g) => acc + g.materialList.length, 0));

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

const handleMinusVideoCount = (type: "minus" | "add") => {
    if (type === "minus" && formData.extra.video_count > 1) formData.extra.video_count--;
    if (type === "add" && formData.extra.video_count < 99) formData.extra.video_count++;
};

const NEWS_DURATION = { min: 5, max: 300, step: 5 } as const;

const handleNewsDurationStep = (type: "minus" | "add") => {
    const current = Number(formData.extra.videoDuration) || NEWS_DURATION.min;
    if (type === "minus") formData.extra.videoDuration = Math.max(NEWS_DURATION.min, current - NEWS_DURATION.step);
    if (type === "add") formData.extra.videoDuration = Math.min(NEWS_DURATION.max, current + NEWS_DURATION.step);
};

const showTokensCost = ref(false);
const costPopRef = shallowRef<InstanceType<typeof CostPop>>();
const openTokensCostDialog = () => {
    showTokensCost.value = true;
    nextTick(() => costPopRef.value?.open());
};

// ──────────────────────────────────────────────────────────────
// 提交
// ──────────────────────────────────────────────────────────────
const isSubmitting = ref(false);

const validateBeforeCreate = async () => {
    if (!formData.materialGroups.length) {
        feedback.msgWarning("请至少创建一个素材分组");
        return false;
    }
    if (totalMaterialCount.value === 0) {
        feedback.msgWarning("请至少添加一个参考素材");
        return false;
    }
    const emptyGroup = formData.materialGroups.find((g) => g.materialList.length === 0);
    if (emptyGroup) {
        feedback.msgWarning(`分组「${emptyGroup.name}」尚未添加素材`);
        return false;
    }

    const copywritingErrors = await copywritingEditorRef.value?.validate();
    if (!copywritingErrors?.valid) {
        feedback.msgWarning(copywritingErrors.firstErrorMsg);
        return false;
    }
    if (formData.extra.clip === 1 && formData.clip.length === 0) {
        feedback.msgWarning("已选择手动指定风格，请至少选择一个视频风格");
        return false;
    }
    return true;
};

const handleCreateVideo = async () => {
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    if (!(await validateBeforeCreate())) return;

    try {
        isSubmitting.value = true;

        const params = {
            name: formData.name,
            material: formData.materialGroups.map((group: any) =>
                group.materialList.map((item: any) => ({
                    fileUrl: item.url,
                    type: item.type,
                    cover: item.pic,
                    duration: item.duration,
                })),
            ),
            character_design: [
                {
                    name: formData.person_name,
                    introduced: formData.person_introduction,
                },
            ],
            copywriting: formData.copywriting.map(({ content }) => ({ title: [content] })),
            music: formData.music.map((item: any) => item.content),
            clip: formData.clip.map((item: any) => ({ clip_template_id: item })),
            shanjian_type: formData.shanjian_type,
            extra: {
                ...formData.extra,
                volume: formData.extra.volume.toFixed(1),
            },
            pic: formData.cover,
        };
        if (formData.person_name && formData.person_introduction) {
            addShanjianPerson({
                name: formData.person_name,
                introduced: formData.person_introduction,
            });
        }
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
