<template>
    <div class="flex h-full w-full p-3 gap-3">
        <div
            class="w-[380px] h-full flex-shrink-0 bg-white rounded-[24px] border border-[#F1F5F9] overflow-hidden flex flex-col">
            <div class="p-6 border-b border-[#F1F5F9] flex items-center justify-between">
                <div>
                    <h2 class="text-[18px] font-[900] text-[#1E293B]">AI 导图生成</h2>
                    <p class="text-[11px] text-[#94A3B8] font-medium uppercase tracking-widest">MindMap Generator</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center">
                    <Icon name="el-icon-MagicStick" :size="20"></Icon>
                </div>
            </div>

            <ElScrollbar>
                <div class="p-3 space-y-6">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <label class="text-[13px] font-black text-[#64748B] uppercase tracking-wider"
                                >描述需求</label
                            >
                            <ElButton type="primary" link @click="handleExample()" class="!text-xs font-medium"
                                >查看示例</ElButton
                            >
                        </div>
                        <ElInput
                            v-model="formData.ask"
                            type="textarea"
                            :rows="12"
                            resize="none"
                            placeholder="请描述您想生成的思维导图主题，例如：'自媒体运营全流程'..."
                            class="custom-textarea"></ElInput>

                        <ElButton
                            class="w-full !h-[52px] !rounded-2xl !font-black ! !shadow-[#0065fb]/20 bg-gradient-to-r from-[#0065fb] to-[#7C3AED] !border-none hover:opacity-90 active:scale-[0.98] transition-all"
                            type="primary"
                            :disabled="!formData.ask"
                            :loading="isLock"
                            @click="lockHandleGenerate()">
                            <Icon name="el-icon-Lightning"></Icon>
                            <span class="ml-2">立即智能生成</span>
                        </ElButton>
                    </div>

                    <div class="pt-4 border-t border-[#F8FAFC]">
                        <label class="block text-[13px] font-black text-[#64748B] uppercase tracking-wider mb-3 px-1"
                            >内容大纲 (Markdown)</label
                        >
                        <div class="relative group">
                            <ElInput
                                v-model="formData.reply"
                                type="textarea"
                                readonly
                                :rows="18"
                                resize="none"
                                class="custom-textarea"></ElInput>
                            <div
                                class="absolute inset-0 bg-white/40 backdrop-blur-[1px] rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                <span
                                    class="px-3 py-1 bg-white border border-br rounded-lg text-[11px] text-[#94A3B8] font-medium"
                                    >AI 生成结果展示</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>

        <div class="grow relative flex flex-col overflow-hidden">
            <div class="absolute top-8 right-8 z-[888] flex items-center gap-3">
                <div
                    class="flex items-center bg-white/80 backdrop-blur-xl p-1.5 rounded-2xl border border-br shadow-xl shadow-black/5">
                    <ElButton
                        class="!h-10 !rounded-xl !border-none !bg-transparent hover:!bg-slate-50 !text-[#64748B] font-black"
                        @click="mindMapFit(formData.reply)">
                        <Icon name="el-icon-RefreshLeft"></Icon>
                        <span class="ml-2">重置视图</span>
                    </ElButton>
                    <div class="w-px h-4 bg-[#E2E8F0] mx-1"></div>
                    <ElButton
                        type="primary"
                        class="!h-10 !px-6 !rounded-xl !font-black ! !shadow-[#0065fb]/20 bg-primary !border-none"
                        :disabled="isLock || !formData.reply"
                        @click="handleExport()">
                        <Icon name="el-icon-Download"></Icon>
                        <span class="ml-2">导出高清图片</span>
                    </ElButton>
                </div>
            </div>

            <div
                class="w-full h-full bg-white rounded-[32px] border border-[#F1F5F9] shadow-inner relative overflow-hidden">
                <div
                    class="absolute inset-0 opacity-[0.4] pointer-events-none"
                    style="
                        background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
                        background-size: 24px 24px;
                    "></div>

                <div ref="mindMapContainer" class="w-full h-full relative z-10">
                    <svg ref="mindMapSvg" class="w-full h-full"></svg>
                </div>

                <div ref="toolbarContainer" class="absolute bottom-6 left-1/2 -translate-x-1/2 z-[888]"></div>

                <div
                    class="absolute inset-0 bg-white/60 backdrop-blur-md z-[88888] flex flex-col items-center justify-center transition-all duration-500"
                    v-if="isLock">
                    <div class="modern-loader-ring"></div>
                    <div class="mt-8 text-[16px] font-black text-[#1E293B] tracking-tight">
                        AI 正在深度思考并绘制导图...
                    </div>
                    <div class="mt-2 text-xs text-[#94A3B8] font-medium uppercase tracking-widest animate-pulse">
                        Processing nodes & connections
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { mindMapDetail, mindMapEditChat } from "@/api/mind_map";
import { chatPrompt } from "@/api/chat";
import { useUserStore } from "@/stores/user";
import { useMindMap } from "@/composables/useMindMap";
import { TokensSceneEnum } from "@/enums/appEnums";
import { mindMapExample } from "@/config/common";
import { ScenePromptEnum } from "@/pages/app/_enums/chatEnum";

const route = useRoute();
const router = useRouter();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const tokensValue = userStore.getTokenByScene(TokensSceneEnum.MIND_MAP)?.score;

const formData = reactive({
    id: "",
    reply: "",
    ask: "",
    prompt_id: ScenePromptEnum.AI_MIND_MAP,
});

const mindMapContainer = shallowRef<HTMLDivElement>(null);
const mindMapSvg = shallowRef<SVGSVGElement>(null);
const { markmap, toolbarContainer, isFullscreen, mindMapInit, mindMapFit, mindMapExportAsPNG } = useMindMap();

const handleExample = async () => {
    formData.reply = mindMapExample;
    markmap.value && markmap.value.destroy();
    await nextTick();
    initMindMap();
};

const handleExport = () => {
    mindMapExportAsPNG(mindMapContainer.value);
};

const { lockFn: lockHandleGenerate, isLock } = useLockFn(async () => {
    if (userTokens.value <= tokensValue.value) {
        feedback.msgPowerInsufficient();
        return;
    }
    markmap.value && markmap.value.destroy();
    try {
        const data = formData.id
            ? await mindMapEditChat({ id: formData.id, message: formData.ask })
            : await chatPrompt({ message: formData.ask, prompt_id: ScenePromptEnum.AI_MIND_MAP });
        formData.reply = data.reply;
        formData.id = data.id;
        router.replace({ path: route.path, query: { id: data.id } });
        userStore.getUser();
        initMindMap();
    } catch (error) {
        if (error?.message?.indexOf("取消请求") !== -1) return;
        feedback.msgError(error || "发生错误");
    }
});

const getDetail = async () => {
    const data = await mindMapDetail({ id: route.query.id });
    Object.keys(formData).forEach((key) => {
        formData[key] = data[key];
    });
    formData.reply = formData.reply.replace(/```markdown/g, "").replace(/```/g, "");
    initMindMap();
};

const initMindMap = async () => {
    mindMapInit(mindMapSvg.value);
    await nextTick();
    mindMapFit(formData.reply);
};

onMounted(() => {
    if (route.query.id) getDetail();
});
onUnmounted(() => {
    markmap.value && markmap.value.destroy();
    $request.cancelRequest();
});

definePageMeta({ layout: "base", title: "AI思维导图" });
</script>

<style lang="scss" scoped>
/* 现代加载动画 */
.modern-loader-ring {
    width: 60px;
    height: 60px;
    border: 5px solid #f1f5f9;
    border-top: 5px solid #4f46e5;
    border-radius: 50%;
    animation: spin 1s cubic-bezier(0.5, 0.1, 0.4, 0.9) infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* 隐藏 Markmap 原生多余样式 */
:deep(.markmap-control-group) {
    @apply border-none  rounded-xl overflow-hidden;
    button {
        @apply bg-white border-none hover:bg-slate-50 transition-colors p-2;
    }
}

/* 页面 Meta 保持不变 */
</style>
