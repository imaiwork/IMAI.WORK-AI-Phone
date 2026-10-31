<template>
    <ElDrawer
        v-model="visible"
        direction="rtl"
        size="640px"
        header-class="!p-0 !mb-0"
        body-class="!p-0"
        :show-close="false"
        :close-on-click-modal="true"
        :close-on-press-escape="true"
        class="prompt-guide-drawer"
        @close="handleDrawerClose">
        <template #header>
            <div class="flex items-center justify-between w-full px-6 py-4 border-b border-br-extra-light">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-[#0065fb]/10 flex items-center justify-center shrink-0">
                        <Icon name="el-icon-MagicStick" color="#0065fb" :size="18" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[15px] font-black text-slate-900 leading-tight">提示词向导</p>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5 truncate">
                            按所选时长 / 比例 / 素材，生成可直接投喂视频模型的镜头级提示词
                        </p>
                    </div>
                </div>
                <div
                    class="w-9 h-9 shrink-0 flex items-center justify-center cursor-pointer hover:bg-gray-100 rounded-full transition-colors"
                    @click="close">
                    <close-btn />
                </div>
            </div>
        </template>

        <div class="flex flex-col h-full bg-white">
            <ElScrollbar class="flex-1">
                <div class="px-6 py-5 flex flex-col gap-5">
                    <!-- 参数信息条：生成后用后端 meta 回填，未生成前按同一套公式预览 -->
                    <div
                        class="flex items-start gap-2 rounded-xl border border-[#0065fb]/15 bg-[#0065fb]/5 px-3.5 py-2.5">
                        <span class="mt-0.5 shrink-0 leading-[0]">
                            <Icon name="el-icon-InfoFilled" color="#0065fb" :size="13" />
                        </span>
                        <span class="text-[12px] leading-relaxed text-slate-600">{{ paramSummary }}</span>
                    </div>

                    <!-- 你的想法 -->
                    <section>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[13px] font-black text-slate-800">你的想法</span>
                            <span
                                class="text-[11px] font-bold"
                                :class="idea.length > maxSize ? 'text-red-500' : 'text-slate-400'">
                                {{ idea.length }} / {{ maxSize }}
                            </span>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-[#f9fafb] px-3.5 py-3">
                            <ElInput
                                v-model="idea"
                                type="textarea"
                                :rows="4"
                                resize="none"
                                :maxlength="maxSize"
                                class="idea-textarea"
                                placeholder="一个女生在海边喝咖啡，杯子就是我上传的那只…" />
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-400 leading-relaxed">
                            引用素材写 <span class="font-bold text-slate-500">@图片1</span> /
                            <span class="font-bold text-slate-500">@视频1</span>（前后各一个空格，编号按上传顺序）
                        </p>
                    </section>

                    <!-- 绑定人设 -->
                    <section>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[13px] font-black text-slate-800">
                                绑定人设
                                <span class="text-[11px] font-medium text-slate-400">（可选）</span>
                            </span>
                            <button
                                v-if="!selectedPersona"
                                class="flex items-center gap-1 h-7 px-2.5 rounded-lg bg-[#0065fb]/5 text-primary hover:bg-[#0065fb]/10 transition-all text-[11px] font-bold"
                                @click="openPersonaSelect">
                                <Icon name="el-icon-Plus" :size="12" />
                                <span>选择人设</span>
                            </button>
                            <div v-else class="flex items-center gap-1.5">
                                <button
                                    class="flex items-center gap-1 h-7 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 transition-all text-[11px] font-bold text-slate-500"
                                    @click="openPersonaSelect">
                                    <Icon name="el-icon-Switch" :size="12" />
                                    更换
                                </button>
                                <button
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-400 transition-all"
                                    @click="clearPersona">
                                    <Icon name="el-icon-Close" :size="12" />
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="!selectedPersona"
                            class="flex items-center gap-3 px-3.5 py-3 rounded-xl border border-dashed border-slate-200 bg-[#f9fafb] cursor-pointer hover:border-[#0065fb]/40 hover:bg-[#0065fb]/5 transition-all group"
                            @click="openPersonaSelect">
                            <div
                                class="w-8 h-8 rounded-full bg-slate-200 group-hover:bg-[#0065fb]/10 flex items-center justify-center transition-colors shrink-0">
                                <Icon name="el-icon-UserFilled" color="#94a3b8" :size="15" />
                            </div>
                            <span class="text-[12px] text-slate-400 font-medium">未绑定：AI 用中性口吻写台词</span>
                        </div>

                        <div
                            v-else
                            class="flex items-center gap-3 px-3.5 py-3 rounded-xl border border-[#0065fb]/20 bg-[#0065fb]/5">
                            <div class="w-9 h-9 rounded-full overflow-hidden border-2 border-white shadow-sm shrink-0">
                                <el-image
                                    v-if="selectedPersona.avatar_url"
                                    :src="selectedPersona.avatar_url"
                                    fit="cover"
                                    class="w-full h-full" />
                                <div v-else class="w-full h-full flex items-center justify-center bg-[#E8EDF2]">
                                    <Icon name="el-icon-UserFilled" color="#B0BEC5" :size="18" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-bold text-slate-900 truncate">
                                    {{ selectedPersona.persona_name }}
                                </p>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    {{ PersonTypeMap[selectedPersona.persona_type as keyof typeof PersonTypeMap] }}
                                </p>
                            </div>
                            <div
                                class="shrink-0 flex items-center gap-1 bg-[#ECFDF5] border border-[#A7F3D0] px-2 py-0.5 rounded-full">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] inline-block"></span>
                                <span class="text-[10px] text-[#059669] font-medium">已绑定</span>
                            </div>
                        </div>
                    </section>

                    <!-- 模式 / 生成数量 -->
                    <section class="flex items-center justify-between gap-4">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-[13px] font-black text-slate-800">模式</span>
                            <div class="flex bg-slate-100 p-1 rounded-xl gap-1">
                                <button
                                    v-for="item in MODE_LIST"
                                    :key="item.value"
                                    class="px-4 h-8 rounded-lg text-[12px] font-black transition-all"
                                    :class="
                                        mode === item.value
                                            ? 'bg-white text-primary shadow-sm'
                                            : 'text-slate-400 hover:text-slate-600'
                                    "
                                    @click="mode = item.value">
                                    {{ item.label }}
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1.5 items-end">
                            <span class="text-[13px] font-black text-slate-800">
                                生成数量
                                <span class="text-[11px] font-medium text-slate-400">（1~3 条）</span>
                            </span>
                            <div class="flex bg-slate-100 p-1 rounded-xl gap-1">
                                <button
                                    v-for="n in COUNT_LIST"
                                    :key="n"
                                    class="w-9 h-8 rounded-lg text-[12px] font-black transition-all"
                                    :class="
                                        count === n
                                            ? 'bg-white text-primary shadow-sm'
                                            : 'text-slate-400 hover:text-slate-600'
                                    "
                                    @click="count = n">
                                    {{ n }}
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- 结果 -->
                    <section v-if="results.length > 0" class="flex flex-col gap-3">
                        <div class="flex items-center gap-2 px-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                            <span class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">生成结果</span>
                        </div>

                        <div
                            v-for="(item, index) in results"
                            :key="item.id"
                            class="rounded-xl border border-slate-100 bg-[#f9fafb] px-3.5 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-black text-slate-400">
                                    结果 {{ index + 1 }}/{{ results.length }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-[10px] font-bold"
                                        :class="item.content.length > maxSize ? 'text-amber-500' : 'text-slate-400'">
                                        {{ item.content.length }} / {{ maxSize }}
                                    </span>
                                    <button
                                        class="flex items-center gap-1 text-[11px] font-bold text-slate-400 hover:text-primary transition-colors disabled:opacity-40"
                                        :disabled="isBusy"
                                        @click="rewrite(index)">
                                        <Icon name="el-icon-Refresh" :size="11" />
                                        重写
                                    </button>
                                    <button
                                        class="flex items-center text-slate-400 hover:text-red-400 transition-colors disabled:opacity-40"
                                        :disabled="isBusy"
                                        title="删除这条结果"
                                        @click="removeResult(index)">
                                        <Icon name="el-icon-Close" :size="12" />
                                    </button>
                                </div>
                            </div>

                            <div v-if="item.loading" class="flex items-center gap-2 py-6 justify-center">
                                <div class="ai-loading-icon !border-[#0065fb]/30 !border-t-primary"></div>
                                <span class="text-[11px] font-bold text-primary">重新生成中…</span>
                            </div>
                            <template v-else>
                                <p
                                    v-if="item.title"
                                    class="mb-1.5 flex items-center gap-1.5 text-[11px] font-black text-slate-500">
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[#64748B]">记录标题</span>
                                    <span class="truncate">{{ item.title }}</span>
                                </p>
                                <p
                                    class="text-[13px] leading-relaxed text-slate-700 whitespace-pre-wrap max-h-[320px] overflow-y-auto">
                                    {{ item.content }}
                                </p>
                                <p class="mt-2 flex items-start gap-1.5 text-[11px] text-slate-400 leading-relaxed">
                                    <span class="mt-0.5 shrink-0 leading-[0]">
                                        <Icon name="el-icon-InfoFilled" :size="11" />
                                    </span>
                                    @图片N / @视频N 是素材引用，改写会导致参考图失效
                                </p>
                                <div class="mt-2.5 flex items-center justify-end gap-2">
                                    <button
                                        class="h-8 px-3 rounded-lg bg-white border border-slate-200 text-slate-500 hover:border-primary hover:text-primary transition-all text-[12px] font-medium"
                                        @click="copyText(item.content)">
                                        复制
                                    </button>
                                    <button
                                        class="h-8 px-4 rounded-lg bg-slate-950 text-white hover:bg-primary transition-all text-[12px] font-black"
                                        @click="useContent(item)">
                                        使用此提示词
                                    </button>
                                </div>
                            </template>
                        </div>
                    </section>
                </div>
            </ElScrollbar>

            <div class="shrink-0 px-6 py-4 border-t border-br-extra-light bg-white">
                <ElButton
                    type="primary"
                    class="!w-full !h-12 !rounded-xl !text-[14px] !font-black"
                    :loading="isGenerating"
                    :disabled="isBusy || !canSubmit"
                    @click="handleGenerate">
                    <template #loading>
                        <div class="flex items-center gap-2">
                            <div class="ai-loading-icon"></div>
                            <span>正在生成 {{ count }} 条…</span>
                        </div>
                    </template>
                    <div v-if="!isGenerating" class="flex items-center gap-2">
                        <Icon name="el-icon-MagicStick" color="#fff" :size="15" />
                        <span>{{ results.length > 0 ? "重新生成提示词" : "生成提示词" }}</span>
                        <span v-if="totalCost > 0" class="opacity-80 font-bold">（消耗 {{ totalCost }} 算力）</span>
                    </div>
                </ElButton>
                <p v-if="!canSubmit" class="mt-2 text-center text-[11px] font-bold text-amber-500">
                    请先填写「你的想法」
                </p>
            </div>
        </div>
    </ElDrawer>

    <persona-select
        v-if="showPersonaSelect"
        ref="personaSelectRef"
        :limit="1"
        :skip-un-config="true"
        @select="handlePersonaSelect"
        @close="showPersonaSelect = false" />
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum, PersonTypeMap } from "@/enums/appEnums";
import { createSoraPrompt } from "@/api/digital_human";
import { useCopy } from "@/composables/useCopy";
import feedback from "@/utils/feedback";

/**
 * 一句话生成视频 · 提示词向导（PC）
 *
 * 与共享的 generate-prompt.vue 刻意分离：那个抽屉是「智能体 + 散文文案」的旧链路，
 * 还挂在 szr_create / montage-copywriting-editor / 作图页上，本页只做镜头级提示词，
 * 参数（时长/比例/素材数）由页面传进来，不让用户在抽屉里重复选一遍。
 */
const props = withDefaults(
    defineProps<{
        /** 当前所选生成时长（秒） */
        duration?: number;
        /** 当前所选画面比例 */
        aspectRatio?: string;
        /** 已上传参考图数量 */
        imageCount?: number;
        /** 已上传参考视频数量 */
        videoCount?: number;
        /** 是否首尾帧模式（该模式没有参考图） */
        firstLastFrame?: boolean;
        /** 提示词字数上限 */
        maxSize?: number;
    }>(),
    {
        duration: 7,
        aspectRatio: "9:16",
        imageCount: 0,
        videoCount: 0,
        firstLastFrame: false,
        maxSize: 1000,
    },
);

const emit = defineEmits<{
    (e: "use-content", value: string, title?: string): void;
    (e: "close"): void;
}>();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const { copy } = useCopy();

const MODE_LIST = [
    { label: "带货", value: "commerce" },
    { label: "创作", value: "creative" },
] as const;
const COUNT_LIST = [1, 2, 3] as const;

const visible = ref(false);
const idea = ref("");
const mode = ref<"commerce" | "creative">("commerce");
const count = ref(1);
const isGenerating = ref(false);
const results = ref<{ id: number; content: string; title: string; loading: boolean }[]>([]);
let resultSeq = 0;

/** 生成成功后的 meta，用来把信息条换成后端真实口径（镜头数 / 口播上限） */
const meta = ref<any>(null);

// ── 人设 ───────────────────────────────────────────────
const personaSelectRef = shallowRef();
const selectedPersona = ref<any>(null);
const showPersonaSelect = ref(false);

const openPersonaSelect = async () => {
    showPersonaSelect.value = true;
    await nextTick();
    personaSelectRef.value?.open();
    if (selectedPersona.value) {
        personaSelectRef.value?.setChooseLists([selectedPersona.value]);
    }
};

const handlePersonaSelect = (item: any) => {
    selectedPersona.value = item;
};

const clearPersona = () => {
    selectedPersona.value = null;
};

// ── 信息条 ─────────────────────────────────────────────
/** 镜头数口径与 VideoPromptService::recommendedShots 一致：≈4 秒一镜，1~4 镜 */
const previewShots = computed(() => Math.min(Math.max(Math.round(props.duration / 4), 1), 4));
/** 口播上限口径与 VideoPromptService::maxDialogueChars 一致：中文 4.5 字/秒 */
const previewDialogue = computed(() => Math.floor(Math.max(1, props.duration) * 4.5));

const paramSummary = computed(() => {
    const m = meta.value ?? {};
    const duration = Number(m.duration ?? props.duration) || props.duration;
    const shots = Number(m.shots ?? previewShots.value) || previewShots.value;
    const dialogue = Number(m.max_dialogue_chars ?? previewDialogue.value) || previewDialogue.value;
    const imageCount = Number(m.image_count ?? props.imageCount) || 0;
    const videoCount = Number(m.video_count ?? props.videoCount) || 0;

    const material = props.firstLastFrame
        ? "首尾帧"
        : [imageCount > 0 ? `参考图 ${imageCount}` : "", videoCount > 0 ? `参考视频 ${videoCount}` : ""]
              .filter(Boolean)
              .join(" · ") || "无素材";

    return `${duration} 秒 · ${shots} 镜 · 口播 ≤ ${dialogue} 字 · ${props.aspectRatio} · ${material}`;
});

// ── 计费 ───────────────────────────────────────────────
const unitCost = computed(() => {
    const score = userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING)?.score;
    return Number(score) || 0;
});
const totalCost = computed(() => Math.round(unitCost.value * count.value * 100) / 100);

const canSubmit = computed(() => idea.value.trim().length > 0);

/** 任意一条请求在途就锁住全部入口，避免连点造成多扣一次算力 */
const isBusy = computed(() => isGenerating.value || results.value.some((item) => item.loading));

// ── 生成 ───────────────────────────────────────────────
const buildParams = () => ({
    content: idea.value.trim(),
    duration: props.duration,
    aspect_ratio: props.aspectRatio,
    image_count: props.firstLastFrame ? 0 : props.imageCount,
    video_count: props.videoCount,
    first_last_frame: props.firstLastFrame ? 1 : 0,
    mode: mode.value,
    persona_id: selectedPersona.value?.id ?? 0,
});

/** 把接口返回整理成卡片列表；多条时读 list，单条时回落到 message（兼容旧口径）
 *  titles 与 list 一一对应，是后端从模型输出里剥出来的记录标题（老版本接口没有这个字段） */
const normalizeResult = (res: any): { content: string; title: string }[] => {
    const rows = Array.isArray(res?.list) ? res.list : [];
    const titles = Array.isArray(res?.titles) ? res.titles : [];
    const list = rows
        .map((content: any, index: number) => ({
            content: typeof content === "string" ? content : "",
            title: String(titles[index] ?? "").trim(),
        }))
        .filter((item: { content: string }) => item.content);
    if (list.length > 0) return list;
    const single = typeof res?.message === "string" ? res.message.trim() : "";
    return single ? [{ content: single, title: String(res?.meta?.title ?? "").trim() }] : [];
};

const handleGenerate = async () => {
    if (isBusy.value) return;
    if (!canSubmit.value) return feedback.msgWarning("请输入您的想法");
    if (unitCost.value > 0 && userTokens.value < totalCost.value) return feedback.msgPowerInsufficient();

    isGenerating.value = true;
    try {
        const res: any = await createSoraPrompt({ ...buildParams(), number: count.value });
        const list = normalizeResult(res);
        if (list.length === 0) throw new Error("生成结果为空，请重试");
        // 保留历史结果便于对比，新一轮排在最前
        results.value = list
            .map((item) => ({ id: ++resultSeq, content: item.content, title: item.title, loading: false }))
            .concat(results.value);
        meta.value = res?.meta ?? null;
        userStore.getUser();
    } catch (error: any) {
        feedback.msgError(typeof error === "string" ? error : error?.message || "生成失败，请重试");
    } finally {
        isGenerating.value = false;
    }
};

/** 单条重写：只调一次，替换当前卡片，不打断其它结果 */
const rewrite = async (index: number) => {
    const target = results.value[index];
    if (!target || isBusy.value) return;
    if (unitCost.value > 0 && userTokens.value < unitCost.value) return feedback.msgPowerInsufficient();

    target.loading = true;
    try {
        const res: any = await createSoraPrompt({ ...buildParams(), number: 1 });
        const list = normalizeResult(res);
        if (list.length === 0) throw new Error("生成结果为空，请重试");
        results.value[index] = { id: target.id, content: list[0].content, title: list[0].title, loading: false };
        userStore.getUser();
    } catch (error: any) {
        target.loading = false;
        feedback.msgError(typeof error === "string" ? error : error?.message || "生成失败，请重试");
    }
};

const removeResult = (index: number) => {
    if (isBusy.value) return;
    results.value.splice(index, 1);
};

const copyText = (text: string) => copy(text);

const useContent = (item: { content: string; title?: string }) => {
    emit("use-content", item.content, item.title ?? "");
    close();
};

// ── 抽屉控制 ───────────────────────────────────────────
const open = () => {
    visible.value = true;
};

const close = () => {
    visible.value = false;
};

const handleDrawerClose = () => {
    emit("close");
};

defineExpose({ open, close });
</script>

<style scoped lang="scss">
:deep(.prompt-guide-drawer) {
    .el-drawer__header {
        @apply mb-0 px-0 py-0 border-b border-br-extra-light;
    }
    .el-drawer__body {
        @apply p-0 overflow-hidden;
    }
}

:deep(.idea-textarea .el-textarea__inner) {
    @apply bg-transparent p-0 border-none text-[13px] leading-relaxed text-slate-700;
    // boxShadow 在 tailwind.config.js 里是整体覆盖，没有 shadow-none / shadow-sm 这类档位，
    // 这里直接写原生属性，别再用 @apply shadow-none（会 postcss 报错）
    box-shadow: none;
    resize: none;
}
:deep(.idea-textarea .el-textarea__inner::placeholder) {
    @apply text-slate-300 text-[12px];
}

.ai-loading-icon {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: prompt-guide-spin 0.8s linear infinite;
}

@keyframes prompt-guide-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
