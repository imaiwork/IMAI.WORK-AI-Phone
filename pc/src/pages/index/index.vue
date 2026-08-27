<template>
    <div class="h-full flex relative bg-white">
        <div
            class="w-[220px] h-full fixed top-0 left-[var(--aside-width)] z-[888] border-r border-[#e2e8f0]/60 bg-white transition-all"
            :class="[hideSidebar ? 'pt-[70px]' : '']"
            :style="{ left: hideSidebar ? '0' : 'var(--aside-width)' }">
            <div class="flex flex-col h-full">
                <chat-agent
                    ref="chatAgentRef"
                    @select-agent="handleSelectAgent"
                    @change-new-session="handleChangeNewSession" />
                <ElDivider class="!my-2 !border-t-[#e2e8f0]/60" />
                <div class="grow min-h-0">
                    <chat-history ref="chatHistoryRef" />
                </div>
            </div>
        </div>
        <div class="h-full flex-1 relative" :class="{ 'ml-[220px]': !hideSidebar }">
            <div class="h-full mx-auto">
                <welcome-hero
                    v-if="showWelcome"
                    ref="welcomeHeroRef"
                    :agent-list="agentList"
                    @change-mode="handleChangeMode"
                    @send="handleWelcomeSend" />
                <div v-else class="h-full min-h-0">
                    <Chatting
                        ref="chattingRef"
                        class="h-full"
                        :is-stop="isStopChat"
                        :content-list="chatContentList"
                        :agent-list="agentList"
                        :mode-tabs="mainModeTabs"
                        :active-mode="activeMainMode"
                        :disable-mention="!!displayAgent"
                        :send-disabled="isReceiving"
                        :tokens="getChatTokens"
                        :is-network="true"
                        :network="isNetwork"
                        :is-new-chat="!!taskId"
                        :is-disabled-humanize="!!displayAgent"
                        :is-quote="true"
                        :is-share="true"
                        :is-edit="true"
                        :is-agent="!!displayAgent"
                        :placeholder="!displayAgent ? '发送消息，输入 @ 选择智能体' : '在这里输入任何问题 ...'"
                        @close="stopStream"
                        @content-post="contentPost"
                        @update:file-list="(files) => (fileLists = files)"
                        @update:network="(value) => (isNetwork = value)"
                        @quote="handleQuote"
                        @change-mode="handleSelectMainMode">
                        <template #content>
                            <div v-if="displayAgent" class="h-full flex flex-col items-center justify-center py-10">
                                <div class="relative mb-5">
                                    <div
                                        class="w-[110px] h-[110px] rounded-full border-4 border-white overflow-hidden bg-gradient-to-br from-blue-100 to-purple-100"
                                        :class="{ 'opacity-50': isAgentLoading }">
                                        <ElImage
                                            v-if="displayAgent.image"
                                            :src="displayAgent.image"
                                            fit="cover"
                                            class="w-full h-full">
                                            <template #error>
                                                <div
                                                    class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-purple-400 text-white text-3xl font-bold">
                                                    {{ displayAgent.name?.charAt(0) }}
                                                </div>
                                            </template>
                                        </ElImage>
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-purple-400 text-white text-3xl font-bold">
                                            {{ displayAgent.name?.charAt(0) }}
                                        </div>
                                    </div>

                                    <Transition name="agent-fade">
                                        <div
                                            v-if="isAgentLoading"
                                            class="absolute inset-0 rounded-full flex items-center justify-center bg-[#ffffff]/60 backdrop-blur-sm">
                                            <div
                                                class="w-6 h-6 border-2 border-primary border-t-[transparent] rounded-full animate-spin"></div>
                                        </div>
                                    </Transition>
                                </div>

                                <template v-if="isAgentLoading">
                                    <div class="h-7 w-32 bg-gray-200 rounded-lg animate-pulse mb-2"></div>
                                    <div class="h-4 w-48 bg-gray-100 rounded animate-pulse"></div>
                                </template>
                                <template v-else>
                                    <div class="text-[22px] font-semibold text-slate-800 mb-2">
                                        {{ displayAgent.name }}
                                    </div>
                                    <div
                                        class="text-sm text-slate-400 max-w-[260px] text-center line-clamp-2 leading-relaxed">
                                        {{ displayAgent.welcome_introducer || displayAgent.intro || "暂无描述" }}
                                    </div>
                                </template>
                            </div>
                        </template>
                    </Chatting>
                </div>
            </div>

            <div v-if="loading" class="absolute top-0 left-0 w-full h-full bg-white overflow-hidden z-[8888]">
                <div class="w-full h-full flex flex-col items-center justify-center animate-pulse">
                    <div class="h-9 w-72 bg-gray-200 rounded-lg"></div>
                    <div class="flex items-center gap-2 mt-5">
                        <div class="flex">
                            <div
                                v-for="i in 7"
                                :key="i"
                                class="w-[34px] h-[34px] rounded-full bg-gray-200 border-2 border-white"
                                :class="i === 1 ? 'ml-0' : '-ml-2.5'"></div>
                        </div>
                        <div class="h-4 w-48 bg-gray-200 rounded ml-2"></div>
                    </div>
                    <div class="flex items-center gap-2 mt-7">
                        <div v-for="i in 6" :key="i" class="h-9 w-24 bg-gray-200 rounded-full"></div>
                    </div>
                </div>
            </div>

            <!-- 切换最近会话：保持对话壳层，避免闪回欢迎页 -->
            <Transition name="session-fade">
                <div
                    v-if="isSessionSwitching"
                    class="absolute inset-0 z-[80] flex flex-col bg-white/85 backdrop-blur-[2px] pointer-events-none">
                    <div class="flex-1 w-full max-w-[800px] mx-auto px-8 pt-10 space-y-6 animate-pulse">
                        <div class="flex gap-3 justify-end">
                            <div class="h-16 w-[48%] rounded-2xl bg-slate-100"></div>
                        </div>
                        <div class="flex gap-3">
                            <div class="w-9 h-9 rounded-full bg-slate-100 shrink-0"></div>
                            <div class="space-y-2 flex-1">
                                <div class="h-4 w-[62%] rounded-lg bg-slate-100"></div>
                                <div class="h-4 w-[44%] rounded-lg bg-slate-100"></div>
                                <div class="h-4 w-[55%] rounded-lg bg-slate-100"></div>
                            </div>
                        </div>
                        <div class="flex gap-3 justify-end">
                            <div class="h-12 w-[36%] rounded-2xl bg-slate-100"></div>
                        </div>
                        <div class="flex gap-3">
                            <div class="w-9 h-9 rounded-full bg-slate-100 shrink-0"></div>
                            <div class="space-y-2 flex-1">
                                <div class="h-4 w-[70%] rounded-lg bg-slate-100"></div>
                                <div class="h-4 w-[50%] rounded-lg bg-slate-100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { getAgentDetail as getAgentDetailApi } from "@/api/agent";
import { canUseAgent, AGENT_UNAVAILABLE_TIP } from "@/utils/agentPermission";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { TokensSceneEnum } from "@/enums/appEnums";
import KuaishouIcon from "@/assets/images/kuaishou_icon.png";
import SphIcon from "@/assets/images/sph_icon.png";
import RedBookIcon from "@/assets/images/redbook_icon.png";
import DouyinIcon from "@/assets/images/douyin_icon.png";
import { useChatManager } from "./_modules/composables/useChatManager";
import { useChatStore } from "./_modules/stores/chat";
import { useChatEventBus } from "./_modules/composables/useChatEventBus";
import { useSelectedChatModel } from "@/composables/useSelectedChatModel";
import ChatHistory from "./_components/chat-history.vue";
import ChatAgent from "./_components/chat-agent.vue";
import WelcomeHero from "./_components/welcome-hero.vue";

import { useAgent } from "./_modules/composables/useAgent";

// --- 1. 初始化 ---
const loading = ref(true);

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();

const userStore = useUserStore();
const { userInfo } = storeToRefs(userStore);
const getChatTokens = userStore.getTokenByScene(TokensSceneEnum.CHAT)?.score;

const hideSidebar = computed(() => appStore.hideSidebar);

const chatStore = useChatStore();
const { chattingRef, agentValue, isLoading: isChatLoading } = storeToRefs(chatStore);
const { onEnterChatSession } = useChatEventBus();
const { setSelectedChatModel } = useSelectedChatModel();

const chatAgentRef = ref<any>(null);

const { isNetwork, fileLists, taskId, chatContentList, isReceiving, isStopChat, initialize, sendMessage, stopStream } =
    useChatManager();

const { agentList, getAgentList } = useAgent();

type MainMode = "chat" | "image" | "ppt" | "map" | "video" | "digital";

const mainModeTabs: Array<{
    key: MainMode;
    label: string;
    icon: string;
    badge?: { text: string; type: "new" | "hot" };
}> = [
    { key: "chat", label: "AI对话", icon: "el-icon-ChatLineRound" },
    { key: "image", label: "图片创作", icon: "el-icon-Picture" },
    {
        key: "ppt",
        label: "PPT生成",
        icon: "el-icon-Monitor",
        badge: { text: "NEW", type: "new" },
    },
    { key: "map", label: "地图获客", icon: "el-icon-LocationFilled" },
    {
        key: "video",
        label: "视频生成",
        icon: "el-icon-VideoCamera",
        badge: { text: "HOT", type: "hot" },
    },
    // 数字人入口暂隐藏，恢复时取消注释即可
    // { key: "digital", label: "数字人", icon: "el-icon-User" },
];

// --- 2. 过渡态：解决切换智能体闪烁问题 ---

const isAgentLoading = ref(false);
const displayAgent = ref<any>(null);
const isAgentUnavailable = ref(false);
const agentDetailRequestId = ref(0);

watch(
    () => agentValue.value,
    (newVal) => {
        if (!isAgentLoading.value) {
            displayAgent.value = newVal ?? null;
        }
    },
    { immediate: true },
);

// --- 3. 欢迎页（welcome-hero）---
// URL 带 conversation_id → 地图；带 draw_conversation_id → 图片/视频（刷新恢复会话）
function resolveInitialMainMode(): MainMode {
    if (route.query.conversation_id) return "map";
    const drawMode = route.query.draw_mode;
    const mode = Array.isArray(drawMode) ? drawMode[0] : drawMode;
    if (route.query.draw_conversation_id && (mode === "image" || mode === "video")) {
        return mode;
    }
    return "chat";
}
const activeMainMode = ref<MainMode>(resolveInitialMainMode());

// AI 对话走 Chatting；其它工作流走 welcome-hero。
// 没有真实聊天上下文时，AI 对话也展示 welcome-hero 的初始输入区。
// 有 taskId / 正在拉历史时保持对话壳层，避免切换最近会话闪回欢迎页。
const showWelcome = computed(
    () =>
        activeMainMode.value !== "chat" ||
        (!displayAgent.value &&
            !taskId.value &&
            !isChatLoading.value &&
            (!chatContentList.value || chatContentList.value.length === 0)),
);

const isSessionSwitching = computed(
    () => !loading.value && activeMainMode.value === "chat" && !!taskId.value && isChatLoading.value,
);

onEnterChatSession(() => {
    agentDetailRequestId.value += 1;
    activeMainMode.value = "chat";
    displayAgent.value = null;
    isAgentLoading.value = false;
    isAgentUnavailable.value = false;
    chatStore.setAgent(null);
    chatStore.replaceAgentId("");
});

const handleChangeMode = (mode: MainMode) => {
    activeMainMode.value = mode;
};

const leaveChatContext = async () => {
    agentDetailRequestId.value += 1;
    isAgentLoading.value = false;
    isAgentUnavailable.value = false;
    await stopStream();
    chattingRef.value?.setSelectedAgent(0);
    chatAgentRef.value?.clearSelectedAgent?.();
    chatStore.clearChat();
    chatStore.replaceTaskId("");
    displayAgent.value = null;
    chatStore.setAgent(null);
    chatStore.replaceAgentId("");
};

const handleSelectMainMode = async (mode: MainMode) => {
    activeMainMode.value = mode;
    if (mode === "chat") return;
    await leaveChatContext();
    await nextTick();
    await nextTick();
    welcomeHeroRef.value?.switchMode?.(mode);
};

const handleWelcomeSend = (payload: {
    text: string;
    files: File[];
    fileList?: Array<{
        uid: number;
        id?: string | number;
        file_id?: string | number;
        url: string;
        name: string;
        type?: string;
        size?: number;
    }>;
    mode: string;
    webSearch: boolean;
    agent?: any;
}) => {
    // 当前仅 AI对话 走真实链路；图片/PPT/地图/视频/数字人由 welcome-hero 内部跳转或占位
    if (payload.mode !== "chat") return;
    agentDetailRequestId.value += 1;
    activeMainMode.value = "chat";
    isNetwork.value = payload.webSearch;
    if (payload.agent) {
        const agent = {
            id: payload.agent.id,
            name: payload.agent.name,
            image: payload.agent.image,
            source: payload.agent.source,
            permissions: payload.agent.permissions,
            member_level_ids: payload.agent.member_level_ids,
        };
        displayAgent.value = agent;
        isAgentUnavailable.value = false;
        chatStore.setAgent(agent);
        chatStore.replaceAgentId(String(agent.id));
    }
    // 优先用事件里的附件；若为空再读 store（welcome 发送前也会 setFiles）
    const files =
        payload.fileList?.length
            ? payload.fileList
            : chatStore.fileLists?.length
              ? [...chatStore.fileLists]
              : undefined;
    const chatConfig = welcomeHeroRef.value?.getChatConfig?.() || {};
    // 欢迎页销毁前记住当前模型，正式对话挂载后继续选中
    if (chatConfig.model_id) {
        setSelectedChatModel({
            model_id: chatConfig.model_id,
            model_sub_id: chatConfig.model_sub_id,
        });
    }
    sendMessage(payload.text, false, undefined, chatConfig, files);
};

// --- 4. 聊天操作 ---
const contentPost = (text: string) => {
    if (isAgentUnavailable.value) {
        feedback.msgWarning(AGENT_UNAVAILABLE_TIP);
        return;
    }
    // 上推由 chatting 组件内部的 contentPost 统一触发，这里不再重复调用
    sendMessage(text);
    chatStore.clearFiles();
    chattingRef.value?.cleanInput();
};

const handleQuote = (text: string) => {
    chatStore.setQuoteText(text);
};


const welcomeHeroRef = ref<any>(null);

const handleChangeNewSession = () => {
    agentDetailRequestId.value += 1;
    activeMainMode.value = "chat";
    // 1) 清除选中智能体(原行为)
    chattingRef.value?.setSelectedAgent(0);
    // 2) 停止聊天流 + 清空对话内容,让 showWelcome 重新为 true
    stopStream();
    chatStore.clearChat();
    chatStore.replaceTaskId("");
    displayAgent.value = null;
    chatStore.setAgent(null);
    chatStore.replaceAgentId("");
    // 3) 等下一个 tick,welcome-hero 已挂载,调用其 reset()
    nextTick(() => {
        welcomeHeroRef.value?.reset?.();
    });
};

// --- 5. 智能体切换（核心修改） ---

const handleSelectAgent = (agent: any) => {
    agentDetailRequestId.value += 1;
    activeMainMode.value = "chat";
    if (agent.type === "model_admin") {
        stopStream();
        chatStore.setAgent(null);
        chatStore.replaceAgentId("");
        chatStore.clearChat();
        // model_admin 类型：直接清空，显示默认欢迎页
        displayAgent.value = null;
        isAgentUnavailable.value = false;
        return;
    }

    displayAgent.value = {
        id: agent.id,
        name: agent.name,
        image: agent.image,
        description: null, // 详情待请求，先置 null 显示骨架屏
    };
    isAgentLoading.value = true;
    isAgentUnavailable.value = false;

    // 停止流 & 清空会话
    // 此时 store 的 agentValue 被清空，但 displayAgent 已有占位，不会闪烁
    stopStream();
    chatStore.clearChat();
    chatStore.replaceTaskId("");
    chattingRef.value?.setSelectedAgent(0);

    getAgentDetail(agent.id, agentDetailRequestId.value);
};

const getAgentDetail = async (agentId: number, requestId = ++agentDetailRequestId.value) => {
    try {
        const data = await getAgentDetailApi({ id: agentId });
        if (requestId !== agentDetailRequestId.value || activeMainMode.value !== "chat") {
            return;
        }
        const fullAgent = {
            id: data.id,
            name: data.name,
            image: data.image,
            intro: data.intro,
            welcome_introducer: data.welcome_introducer,
            source: data.source,
            permissions: data.permissions,
            member_level_ids: data.member_level_ids,
        };
        displayAgent.value = fullAgent;
        isAgentUnavailable.value = !canUseAgent(data, userInfo.value);
        chatStore.setAgent(fullAgent);
        chatStore.replaceAgentId(data.id);
    } catch (e) {
        if (requestId !== agentDetailRequestId.value) return;
        displayAgent.value = null;
        isAgentUnavailable.value = false;
        chatStore.setAgent(null);
    } finally {
        if (requestId === agentDetailRequestId.value) {
            isAgentLoading.value = false;
        }
    }
};

// --- 6. 监听 ---
watch(
    () => chatStore.taskId,
    () => {
        chattingRef?.value?.clearQuote();
    },
);

watch(
    () => route.fullPath,
    () => {
        initialize()
            .then(async () => {
                try {
                    await getAgentList();
                } catch (e) {
                    // 未登录或请求失败时,仍需渲染智能体列表(空状态),避免骨架屏一直显示
                }
                await nextTick();
                chatAgentRef.value?.init(agentList.value);
                const agentId = Number(route.query.agent_id);
                if (activeMainMode.value === "chat" && agentId > 0) {
                    chatAgentRef.value.selectAgent(agentId);
                    getAgentDetail(agentId);
                }
            })
            .finally(() => {
                loading.value = false;
            });
    },
    { immediate: true },
);

onUnmounted(() => {
    // 先停流：否则后端继续生成继续扣 tokens，晚到的分片还会用 replaceState
    // 把 ?task_id=... 写进用户此刻所在的其它页面 URL
    stopStream();
    chatStore.clearChat();
});

definePageMeta({
    key: "home",
});
</script>

<style lang="scss" scoped>
/* loading 蒙层淡入淡出 */
.agent-fade-enter-active,
.agent-fade-leave-active {
    transition: opacity 0.2s ease;
}
.agent-fade-enter-from,
.agent-fade-leave-to {
    opacity: 0;
}

.session-fade-enter-active,
.session-fade-leave-active {
    transition: opacity 0.18s ease;
}
.session-fade-enter-from,
.session-fade-leave-to {
    opacity: 0;
}
</style>
