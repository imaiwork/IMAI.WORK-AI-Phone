<template>
    <popup
        ref="popupRef"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        width="1000px"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="rounded-2xl h-[720px] bg-white flex relative overflow-hidden">
            <div class="w-[400px] border-r border-slate-100 flex flex-col bg-[#f8fafc]/50">
                <div class="px-6 h-16 flex items-center border-b border-slate-100 bg-white/50 backdrop-blur-md">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center shadow-lg shadow-primary/20">
                            <Icon name="el-icon-MagicStick" color="white" :size="18" />
                        </div>
                        <h2 class="text-base font-[1000] text-slate-800 tracking-tight">参数配置</h2>
                    </div>
                </div>

                <div class="grow overflow-y-auto p-6 space-y-8">
                    <section>
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-[1000] text-slate-700 flex items-center gap-2">
                                <span class="w-1 h-3 bg-primary rounded-full"></span>
                                文案主题
                            </label>
                        </div>
                        <div class="relative group">
                            <ElInput
                                v-model="contentVal"
                                type="textarea"
                                resize="none"
                                placeholder="输入您想生成的主题..."
                                :rows="6"
                                class="premium-textarea"
                                :maxlength="contentMaxLength" />
                            <div
                                class="absolute bottom-2 right-2 text-[10px] font-black px-2 py-1 bg-white/80 text-slate-300 rounded-md">
                                {{ contentVal.length }}/{{ contentMaxLength }}
                            </div>
                        </div>
                    </section>

                    <section v-if="!isNewsBody">
                        <label class="text-xs font-[1000] text-slate-400 mb-3 block uppercase tracking-widest"
                            >文案长度 / Length</label
                        >
                        <div class="bg-white p-1 rounded-xl border border-br flex gap-1">
                            <button
                                v-for="item in promptList"
                                :key="item.id"
                                @click="currentPrompt = item"
                                class="flex-1 py-2 text-xs font-black rounded-lg transition-all"
                                :class="
                                    currentPrompt?.id === item.id
                                        ? 'bg-primary text-white shadow-md'
                                        : 'text-slate-400 hover:bg-slate-50'
                                ">
                                {{ item.name }}
                            </button>
                        </div>
                    </section>

                    <section>
                        <label class="text-xs font-[1000] text-slate-400 mb-3 block uppercase tracking-widest"
                            >生成条数 / Quantity</label
                        >
                        <div class="grid grid-cols-5 gap-1.5">
                            <button
                                v-for="num in promptNumList"
                                :key="num"
                                @click="currentPromptNum = num"
                                class="py-2 text-[11px] font-black rounded-lg border-2 transition-all"
                                :class="
                                    currentPromptNum === num
                                        ? 'border-primary bg-[#0065fb]/5 text-primary'
                                        : 'border-slate-100 bg-white text-slate-400'
                                ">
                                {{ num }}
                            </button>
                        </div>
                    </section>
                </div>

                <div class="p-6 border-t border-slate-100 bg-[#ffffff]/50">
                    <button
                        :disabled="contentVal.length === 0 || isGenerating"
                        class="w-full h-12 bg-primary rounded-xl text-white font-[1000] shadow-light shadow-[#0065fb]/20 hover:shadow-[#0065fb]/40 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-30 transition-all flex items-center justify-center gap-2"
                        @click="handleGenerate">
                        {{ isGenerating ? "生成中..." : "开始生成" }}
                        <span class="text-[10px] opacity-60 font-medium">(消耗{{ getToken }}算力)</span>
                    </button>
                </div>
            </div>

            <div class="flex-1 flex flex-col bg-[#f8fafc]/50 relative">
                <div
                    class="shrink-0 px-6 h-16 flex items-center justify-between border-b border-slate-100 bg-[#f8fafc]/50 backdrop-blur-md z-10">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-[1000] text-slate-800">生成结果</span>
                        <div
                            v-if="isGenerated"
                            class="px-2 py-0.5 rounded bg-[#22c55e]/10 text-green-600 text-[10px] font-black uppercase">
                            Completed
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="close"
                            class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center transition-colors">
                            <close-btn />
                        </button>
                    </div>
                </div>

                <div class="grow overflow-y-auto p-6 relative z-10 custom-scrollbar">
                    <div
                        v-if="!isGenerated && !isGenerating"
                        class="h-full flex flex-col items-center justify-center text-slate-300">
                        <div
                            class="w-20 h-20 mb-4 bg-slate-100 rounded-3xl flex items-center justify-center text-slate-200">
                            <Icon name="el-icon-ChatDotSquare" :size="40" />
                        </div>
                        <p class="text-xs font-medium tracking-widest uppercase">在左侧输入主题并点击生成</p>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(item, index) in chatContentList"
                            :key="index"
                            class="bg-white rounded-2xl border border-slate-100 overflow-hidden group hover:border-[#0065fb]/30 transition-all animate-in fade-in slide-in-from-right-4">
                            <header
                                class="px-5 py-3 border-b border-slate-50 bg-[#f8fafc]/30 flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-5 h-5 rounded-full bg-primary text-white text-[10px] flex items-center justify-center font-black"
                                        >{{ index + 1 }}</span
                                    >
                                    <span class="text-[11px] font-[1000] text-slate-700">文案草稿</span>
                                </div>
                                <button
                                    v-if="item.status === 'success'"
                                    class="opacity-0 group-hover:opacity-100 w-6 h-6 rounded flex items-center justify-center text-slate-300 hover:bg-red-50 hover:text-red-500 transition-all"
                                    @click="handleDeleteCopywriter(index)">
                                    <Icon name="el-icon-Delete" :size="14" />
                                </button>
                            </header>

                            <div class="p-5">
                                <div v-if="item.status === 'pending'" class="space-y-4 py-2">
                                    <div class="h-3 bg-slate-100 rounded-full animate-pulse w-3/4"></div>
                                    <div class="h-3 bg-slate-100 rounded-full animate-pulse w-full"></div>
                                    <div class="h-3 bg-slate-100 rounded-full animate-pulse w-1/2"></div>
                                </div>
                                <div v-else class="space-y-4">
                                    <template v-if="!isNewsBody">
                                        <input
                                            v-model="item.title"
                                            class="w-full text-sm font-[1000] text-slate-800 border-none focus:ring-0 p-0 mb-1"
                                            placeholder="未命名标题" />
                                        <ElDivider class="!my-1" />
                                        <textarea
                                            v-model="item.content"
                                            class="w-full text-xs font-medium text-slate-500 border-none focus:ring-0 p-0 leading-relaxed bg-[transparent]"
                                            rows="5" />
                                    </template>
                                    <template v-else>
                                        <div v-for="(val, valIndex) in item.content" :key="valIndex" class="mb-2">
                                            <input
                                                v-model="item.content[valIndex]"
                                                class="w-full text-xs font-medium text-slate-600 bg-slate-50 p-3 rounded-xl border-none focus:bg-white focus:ring-2 focus:ring-[#0065fb]/10 transition-all" />
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="isGenerated" class="p-6 border-t border-slate-100 bg-[#f8fafc]/80 backdrop-blur-md z-10">
                    <div class="flex items-center gap-3">
                        <button
                            class="h-12 px-6 rounded-xl border border-slate-100 text-slate-400 font-[1000] text-xs hover:bg-slate-50 transition-all flex items-center gap-2"
                            @click="handleGenerate">
                            <Icon name="el-icon-Refresh" /> 重新生成
                        </button>
                        <button
                            @click="handleUseContent"
                            :disabled="isGenerating"
                            class="grow h-12 bg-primary rounded-xl text-white font-[1000] shadow-light shadow-[#0065fb]/20 hover:shadow-[#0065fb]/40 transition-all flex items-center justify-center gap-2 disabled:opacity-30">
                            <Icon name="el-icon-Select" :size="18" /> 应用选中文案
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { generateShanjianPrompt, generateNewsBodyPrompt } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";

// 枚举定义
enum MontageTypeEnum {
    NEWS_BODY = 2,
    COMMON = 1,
}

const props = withDefaults(
    defineProps<{
        type: MontageTypeEnum;
    }>(),
    {
        type: MontageTypeEnum.NEWS_BODY,
    }
);

const emit = defineEmits(["close", "confirm"]);

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const popupRef = shallowRef();

// 响应式数据
const contentVal = ref<string>("");
const contentMaxLength = 500;
const chatContentList = ref<any[]>([]);

const promptList = [
    { id: 1, name: "长", length: 500 },
    { id: 2, name: "中", length: 300 },
    { id: 3, name: "短", length: 150 },
];

const promptNumList = [1, 3, 5, 10, 20];
const currentPromptNum = ref<number>(1);
const currentPrompt = ref<any>(promptList[0]); // 默认选择"中"

const isGenerating = ref<boolean>(false);

// 计算属性
const isNewsBody = computed(() => {
    return props.type === MontageTypeEnum.NEWS_BODY;
});

const isGenerated = computed(() => {
    return chatContentList.value.length > 0 && chatContentList.value.every((item) => item.status === "success");
});

const getToken = computed(() => {
    const token = userStore.getTokenByScene(
        isNewsBody.value ? TokensSceneEnum.NEWS_MIX_CUT_TITLE : TokensSceneEnum.SHANJIAN_COPYWRITING_CREATE
    )?.score;
    return parseFloat(token) * currentPromptNum.value;
});

// 方法
const handleGenerate = async () => {
    if (!contentVal.value.trim()) {
        feedback.msgWarning("请输入文案主题");
        return;
    }
    if (isGenerating.value) return;
    if (userTokens.value < getToken.value) {
        feedback.msgPowerInsufficient();
        return;
    }
    isGenerating.value = true;

    // 创建待生成的文案项
    chatContentList.value = Array.from({ length: currentPromptNum.value }, (_, index) => ({
        id: index,
        title: "",
        content: isNewsBody.value ? [] : "",
        status: "pending",
    }));

    try {
        if (isNewsBody.value) {
            const result = await generateNewsBodyPrompt({
                keywords: contentVal.value,
                number: currentPromptNum.value,
            });
            if (result.content && result.content.length > 0) {
                chatContentList.value = result.content.map((item: any) => ({
                    content: JSON.parse(item),
                    status: "success",
                }));
                isGenerating.value = false;
            } else {
                feedback.msgWarning("文案生成失败，请重新输入");
                isGenerating.value = false;
                chatContentList.value = [];
            }
            return;
        }

        // 跟踪完成状态
        let completedCount = 0;
        const totalCount = currentPromptNum.value;

        for (let i = 0; i < currentPromptNum.value; i++) {
            generateShanjianPrompt({
                keywords: contentVal.value,
                number: currentPrompt.value.length,
            })
                .then((result) => {
                    chatContentList.value[i] = {
                        title: result.title,
                        content: result.content,
                        status: "success",
                    };

                    completedCount++;
                    if (completedCount === totalCount) {
                        userStore.getUser();
                        isGenerating.value = false;
                    }
                })
                .catch((error) => {
                    chatContentList.value[i] = {
                        ...chatContentList.value[i],
                        status: "error",
                    };
                    completedCount++;
                    if (completedCount === totalCount) {
                        userStore.getUser();
                        isGenerating.value = false;
                    }
                });
        }
    } catch (error) {
        feedback.msgWarning("生成失败，请重试");
        isGenerating.value = false;
        chatContentList.value = [];
    }
};

const handleDeleteCopywriter = (index: number) => {
    chatContentList.value.splice(index, 1);
    if (chatContentList.value.length === 0) {
        isGenerating.value = false;
    }
};

const handleUseContent = () => {
    if (isGenerating.value) {
        feedback.msgWarning("文案生成中，请稍候...");
        return;
    }

    emit("confirm", {
        type: props.type,
        content: isNewsBody.value
            ? chatContentList.value
                  .filter((item) => item.content.some((content: string) => content.trim() !== ""))
                  .map((item) => item.content)
            : chatContentList.value
                  .filter((item) => item.title)
                  .map((item) => ({ title: item.title, content: item.content }))
                  .filter((item: { title: string; content: string[] }) => item.title.trim() !== ""),
    });
    chatContentList.value = [];
    isGenerating.value = false;
    contentVal.value = "";
};

const open = () => {
    popupRef.value.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
.animate-in {
    animation-fill-mode: forwards;
}
</style>
