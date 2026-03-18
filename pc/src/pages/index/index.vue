<template>
    <div class="h-full flex relative bg-white">
        <div
            class="w-[220px] h-full fixed top-0 left-[var(--aside-width)] z-[888] border-r border-[#e2e8f0]/60 bg-white transition-all"
            :class="[hideSidebar ? 'pt-[70px]' : '']"
            :style="{ left: hideSidebar ? '0' : 'var(--aside-width)' }">
            <div class="flex flex-col h-full">
                <chat-agent ref="chatAgentRef" @select-agent="handleSelectAgent" />
                <ElDivider class="!my-2 !border-t-[#e2e8f0]/60" />
                <div class="grow min-h-0">
                    <chat-history ref="chatHistoryRef" />
                </div>
            </div>
        </div>
        <div class="h-full flex-1 relative" :class="{ 'ml-[220px]': !hideSidebar }">
            <div class="h-full mx-auto">
                <Chatting
                    ref="chattingRef"
                    :is-stop="isStopChat"
                    :content-list="chatContentList"
                    :send-disabled="isReceiving"
                    :tokens="getChatTokens"
                    :is-network="true"
                    :is-new-chat="!!taskId"
                    :is-disabled-humanize="isAgent()"
                    :is-quote="true"
                    :is-share="true"
                    :is-edit="true"
                    @close="stopStream"
                    @content-post="contentPost"
                    @update:file-list="(files) => (fileLists = files)"
                    @update:network="(value) => (isNetwork = value)"
                    @new-chat="startNewChat"
                    @quote="handleQuote"
                    @update:inputContent="handleUpdateInputContent">
                    <template #content>
                        <div class="w-full h-full pt-[100px]">
                            <div class="md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] mx-auto">
                                <div class="font-medium text-[32px]">Hello, 今天心情不错哟?</div>
                                <div class="mt-6 flex flex-col xl:flex-row gap-4">
                                    <div class="flex-1 border border-[#EBEBEB] rounded-2xl px-5 relative">
                                        <div class="flex items-center justify-between py-3">
                                            <div class="text-lg font-medium">AI获客</div>
                                            <div
                                                class="flex items-center text-primary gap-x-1 font-medium cursor-pointer"
                                                @click="toPage('customer')">
                                                更多<Icon name="el-icon-ArrowRight"></Icon>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-6 my-5 mr-[150px]">
                                            <div
                                                class="flex flex-col items-center justify-center cursor-pointer"
                                                v-for="(item, index) in socialPlatformList"
                                                :key="index"
                                                @click="toPage(item.type)">
                                                <img :src="item.icon" class="w-9 h-9" />
                                                <div class="text-[14px] font-medium mt-2 truncate">{{ item.name }}</div>
                                            </div>
                                        </div>
                                        <div class="absolute right-2 bottom-0">
                                            <img src="@/assets/images/chat_img1.png" class="w-[113px] h-[146px]" />
                                        </div>
                                    </div>
                                    <div class="flex-1 border border-[#EBEBEB] rounded-2xl px-5 pb-3 relative">
                                        <div class="flex items-center justify-between py-3">
                                            <div class="text-lg font-medium">矩阵运营</div>
                                            <div
                                                class="flex items-center text-primary gap-x-1 font-medium cursor-pointer"
                                                @click="toPage('matrix')">
                                                更多<Icon name="el-icon-ArrowRight"></Icon>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div
                                                class="h-[190px] bg-[#F9F9FA] rounded-[10px] relative cursor-pointer"
                                                style="grid-row: span 2"
                                                @click="toPage('sales')">
                                                <div class="text-lg font-medium text-center mt-5">AI销售</div>
                                                <div class="text-[#999999] text-xs mt-2 text-center w-[70%] mx-auto">
                                                    AI智能聊天、朋友圈自动点赞评论
                                                </div>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2">
                                                    <img src="@/assets/images/wechat.png" class="w-20" />
                                                </div>
                                            </div>
                                            <div
                                                class="bg-[#F9F9FA] rounded-[10px] px-[17px] py-[10px] h-[92px] cursor-pointer"
                                                @click="toPage('matrix')">
                                                <div class="text-lg font-medium">矩阵运营</div>
                                                <div class="text-[#999999] text-xs">多平台一键自动发布</div>
                                                <div class="mt-2 flex items-center gap-2">
                                                    <img
                                                        :src="item.icon"
                                                        class="w-4 h-4 rounded-full"
                                                        v-for="item in socialPlatformList" />
                                                </div>
                                            </div>
                                            <div
                                                class="bg-[#F9F9FA] rounded-[10px] h-[92px] relative cursor-pointer"
                                                @click="toPage('dh')">
                                                <div class="pl-[17px] pt-[11px] mr-[70px]">
                                                    <div class="text-lg font-medium mb-1">数字人定制</div>
                                                    <div class="text-[#999999] text-xs">形象克隆</div>
                                                    <div class="text-[#999999] text-xs">声音克隆</div>
                                                </div>
                                                <div class="right-0 bottom-0 absolute">
                                                    <img
                                                        src="@/assets/images/chat_img2.png"
                                                        class="w-[50px] h-[68px] rounded-md" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="mt-[14px] px-5 border border-[#EBEBEB] rounded-[10px] h-[65px] flex items-center justify-between">
                                    <div class="flex items-center">
                                        <Icon name="local-icon-phone2" :size="22"></Icon>
                                        <span class="text-lg font-medium ml-2">AI手机管理</span>
                                        <span class="text-[#999999] ml-[28px]">绑定AI手机 / 激活设备码</span>
                                    </div>
                                    <div
                                        class="flex items-center text-primary gap-x-1 font-medium cursor-pointer"
                                        @click="toPage('device')">
                                        更多<Icon name="el-icon-ArrowRight"></Icon>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #input>
                        <div ref="chatAreaRef" class="max-h-[200px] overflow-y-auto dynamic-scroller"></div>
                    </template>
                </Chatting>
            </div>

            <div v-if="loading" class="absolute top-0 left-0 w-full h-full bg-white overflow-hidden z-[8888]">
                <div class="w-full h-full pt-[100px]">
                    <div class="md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] mx-auto animate-pulse">
                        <div class="h-10 bg-gray-200 rounded-lg w-80"></div>
                        <div class="mt-6 flex flex-col xl:flex-row gap-4">
                            <div class="flex-1 border border-[#EBEBEB] rounded-2xl px-5 py-3">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="h-6 bg-gray-200 rounded w-20"></div>
                                    <div class="h-5 bg-gray-200 rounded w-16"></div>
                                </div>
                                <div class="grid grid-cols-2 gap-6 my-5">
                                    <div class="flex flex-col items-center justify-center" v-for="i in 4" :key="i">
                                        <div class="w-9 h-9 bg-gray-200 rounded-full"></div>
                                        <div class="h-4 bg-gray-200 rounded w-20 mt-2"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-1 border border-[#EBEBEB] rounded-2xl px-5 py-3">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="h-6 bg-gray-200 rounded w-20"></div>
                                    <div class="h-5 bg-gray-200 rounded w-16"></div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="h-[190px] bg-gray-200 rounded-[10px]" style="grid-row: span 2"></div>
                                    <div class="h-[92px] bg-gray-200 rounded-[10px]"></div>
                                    <div class="h-[92px] bg-gray-200 rounded-[10px]"></div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-[14px] px-5 border border-[#EBEBEB] rounded-[10px] h-[65px] flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-6 h-6 bg-gray-200 rounded"></div>
                                <div class="h-5 bg-gray-200 rounded w-28"></div>
                                <div class="h-4 bg-gray-200 rounded w-40"></div>
                            </div>
                            <div class="h-5 bg-gray-200 rounded w-16"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useChatAreaManager } from "./_modules/composables/useChatAreaManager";
import { useChatManager } from "./_modules/composables/useChatManager";
import { useChatStore } from "./_modules/stores/chat";
import ChatHistory from "./_components/chat-history.vue";
import ChatAgent from "./_components/chat-agent.vue";
import RedBookIcon from "@/assets/images/redbook_icon.png";
import DouyinIcon from "@/assets/images/douyin_icon.png";
import KuaishouIcon from "@/assets/images/kuaishou_icon.png";
import SphIcon from "@/assets/images/sph_icon.png";
import { storeToRefs } from "pinia";
import { useAgent } from "./_modules/composables/useAgent";
// --- 1. 初始化 ---

const loading = ref(true);

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();

const userStore = useUserStore();
const getChatTokens = userStore.getTokenByScene(TokensSceneEnum.CHAT)?.score;

const hideSidebar = computed(() => appStore.hideSidebar);

const chatStore = useChatStore();

const { chattingRef } = storeToRefs(chatStore);

const chatAgentRef = ref<any>(null);

const {
    isNetwork,
    fileLists,
    taskId,
    chatContentList,
    isReceiving,
    isStopChat,
    initialize,
    sendMessage,
    startNewChat,
    stopStream,
} = useChatManager();

const { chatAreaRef, agent, setup, dispose, clear, isAgent, setAgent, setText, setAgentList } = useChatAreaManager({
    onEnter: (text) => {
        if (isReceiving.value) return;
        contentPost(text);
    },
    onInputChange: (text, isEmpty) => {
        if (chattingRef.value) {
            chattingRef.value.setInput(text);
        }
    },
});

const { agentList, getAgentList } = useAgent();

const socialPlatformList = [
    {
        name: "视频号获客",
        icon: SphIcon,
        type: "customer",
    },
    { name: "小红书获客", icon: RedBookIcon, type: "customer" },
    { name: "抖音获客", icon: DouyinIcon, type: "customer" },
    { name: "快手获客", icon: KuaishouIcon, type: "customer" },
];

const toPage = (type: string) => {
    // 明确定义路由映射表，增强可读性
    const typeUrl: { [key: string]: string } = {
        sales: "/app/person_wechat?type=1",
        matrix: "/app/matrix?type=1",
        dh: "/app/digital_human?type=1",
        customer: "/app/customer?type=1",
        staff: "/staff",
        device: "/device",
    };

    const url = typeUrl[type];

    if (url) {
        router.push(url);
    }
};

const contentPost = (text: string) => {
    chattingRef?.value?.triggerContentPushUp();
    sendMessage(text);
    clear();
    chatStore.setAgent(agent.value);
    chatStore.clearFiles();
    chattingRef.value?.cleanInput();
};

const handleQuote = (text: string) => {
    chatStore.setQuoteText(text);
};

const handleUpdateInputContent = (value: string) => {
    setText(value);
};

const handleSelectAgent = (agent: any) => {
    const { type } = agent;
    if (type === "model_admin") {
        setAgent(null);
        return;
    }
    setAgent(agent);
    chatStore.setAgent(agent);
    chatStore.setAgentId(agent.id);
};

watch(
    () => chatStore.taskId,
    () => {
        chattingRef?.value?.clearQuote();
    }
);

watch(
    () => route.fullPath,
    () => {
        initialize()
            .then(async () => {
                await getAgentList();
                setAgentList(agentList.value);
                await nextTick();
                chatAgentRef.value.init(agentList.value);
                const agentId = route.query.agent_id as string;
                if (Number(agentId) > 0) {
                    setAgent({
                        id: agentId,
                        name: route.query.agent_name as string,
                    });
                }
            })
            .finally(() => {
                loading.value = false;
            });
    },
    { immediate: true }
);

watch(
    () => agent.value,
    (newVal) => {
        if (!newVal) {
            chatAgentRef.value.clearSelectedAgent();
        } else {
            chatAgentRef.value.selectAgent(newVal.id);
        }
    }
);

watch(
    () => chatStore.agentValue,
    (newVal) => {
        if (!newVal || newVal == "0") {
            chatAgentRef.value.clearSelectedAgent();
            return;
        }

        setAgent({
            id: newVal.id,
        });
        chatAgentRef.value.selectAgent(newVal.id);
    }
);

onMounted(() => {
    setup();
});

onUnmounted(() => {
    dispose();
    chatStore.clearChat();
});

definePageMeta({
    key: "home",
});
</script>

<style lang="scss" scoped>
:deep(.chat-area-pc) {
    * {
        font-size: var(--el-font-size-medium);
    }
    svg {
        display: inline;
    }

    .chat-rich-text {
        font-size: var(--el-font-size-medium);
        padding: 8px 0;
        min-height: 80px;
        .chat-grid-input {
            font-size: var(--el-font-size-medium);
        }
        .at-input {
            line-height: 1;
        }
        .at-user,
        .at-tag {
            font-weight: bold;
        }
    }
    .chat-placeholder-wrap {
        padding: 8px 0;
        font-size: var(--el-font-size-medium);
        font-style: inherit;
    }
}
</style>

<style lang="scss">
.chat-dialog {
    .call-user-dialog-header,
    .call-tag-dialog-header {
        display: none;
    }

    .call-user-dialog,
    .call-tag-dialog {
        width: 231px;
        border-radius: 12px;
        border: 1px solid #efefef;
        box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.06);
        backdrop-filter: blur(12px);
        padding: 8px;

        .call-user-dialog-item,
        .call-tag-dialog-item {
            height: 35px;
            padding: 0 12px;
            font-weight: 400;
            border-radius: 6px;
            border: 1px solid transparent;
            margin-bottom: 4px;

            &-name {
                color: #000000 !important;
                font-size: var(--el-font-size-base);
            }

            &-active,
            &:hover {
                background-color: #f6f6f6;
                border-color: #efefef;
                .call-user-dialog-item-name {
                    color: #000000;
                }
            }
        }
    }
}
</style>
