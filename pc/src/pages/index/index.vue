<template>
    <div class="h-full flex relative bg-white">
        <div class="w-[220px] h-full fixed top-0 left-[var(--aside-width)] z-[888]" v-show="!hideSidebar">
            <chat-history ref="chatHistoryRef" />
        </div>
        <div class="h-full flex-1" v-show="!isLoading" :class="{ 'ml-[220px]': !hideSidebar }">
            <div class="h-full px-4 mx-auto">
                <Chatting
                    ref="chattingRef"
                    :is-stop="isStopChat"
                    :content-list="chatContentList"
                    :send-disabled="isReceiving"
                    :tokens="getChatTokens"
                    :is-network="true"
                    :is-new-chat="!!taskId"
                    :is-disabled-humanize="isAgent()"
                    @close="stopStream"
                    @content-post="contentPost"
                    @update:file-list="(files) => (fileLists = files)"
                    @update:network="(value) => (isNetwork = value)"
                    @new-chat="startNewChat">
                    <template #content>
                        <div class="w-full h-full pt-[100px]">
                            <div class="md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] mx-auto">
                                <div class="font-bold text-[32px]">Halo, 今天心情不错哟?</div>
                                <div class="mt-6 flex flex-col xl:flex-row gap-4">
                                    <div class="flex-1 border border-[#EBEBEB] rounded-2xl px-5 relative">
                                        <div class="flex items-center justify-between py-3">
                                            <div class="text-lg font-bold">AI获客</div>
                                            <div
                                                class="flex items-center text-primary gap-x-1 font-bold cursor-pointer"
                                                @click="toPage('staff')">
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
                                                <div class="text-[14px] font-bold mt-2 truncate">{{ item.name }}</div>
                                            </div>
                                        </div>
                                        <div class="absolute right-2 bottom-0">
                                            <img src="@/assets/images/chat_img1.png" class="w-[113px] h-[146px]" />
                                        </div>
                                    </div>
                                    <div class="flex-1 border border-[#EBEBEB] rounded-2xl px-5 pb-3 relative">
                                        <div class="flex items-center justify-between py-3">
                                            <div class="text-lg font-bold">矩阵运营</div>
                                            <div
                                                class="flex items-center text-primary gap-x-1 font-bold cursor-pointer"
                                                @click="toPage('staff')">
                                                更多<Icon name="el-icon-ArrowRight"></Icon>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div
                                                class="h-[190px] bg-[#F9F9FA] rounded-[10px] relative cursor-pointer"
                                                style="grid-row: span 2"
                                                @click="toPage('sales')">
                                                <div class="text-lg font-bold text-center mt-5">AI销售</div>
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
                                                <div class="text-lg font-bold">矩阵运营</div>
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
                                                    <div class="text-lg font-bold mb-1">数字人定制</div>
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
                                        <span class="text-lg font-bold ml-2">AI手机管理</span>
                                        <span class="text-[#999999] ml-[28px]">绑定AI手机 / 激活设备码</span>
                                    </div>
                                    <div
                                        class="flex items-center text-primary gap-x-1 font-bold cursor-pointer"
                                        @click="toPage('device')">
                                        更多<Icon name="el-icon-ArrowRight"></Icon>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #input>
                        <div ref="chatAreaRef" class="max-h-[400px] overflow-y-auto dynamic-scroller"></div>
                    </template>
                </Chatting>
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
import RedBookIcon from "@/assets/images/redbook_icon.png";
import DouyinIcon from "@/assets/images/douyin_icon.png";
import KuaishouIcon from "@/assets/images/kuaishou_icon.png";
import SphIcon from "@/assets/images/sph_icon.png";
import { useChatHistory } from "./_modules/composables/useChatHistory";

// --- 1. 初始化 ---

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();

const userStore = useUserStore();
const getChatTokens = userStore.getTokenByScene(TokensSceneEnum.CHAT)?.score;

const hideSidebar = computed(() => appStore.hideSidebar);

const chatStore = useChatStore();

const {
    chattingRef,
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

const { isLoading } = useChatHistory();

const { chatAreaRef, agent, setup, dispose, clear, getAgentList, isAgent, setAgent } = useChatAreaManager({
    onEnter: (text) => {
        contentPost(text);
    },
    onInputChange: (text, isEmpty) => {
        if (chattingRef.value) {
            chattingRef.value.setInput(text);
        }
    },
});

const socialPlatformList = [
    {
        name: "视频号获客",
        icon: SphIcon,
        type: "sph",
    },
    { name: "小红书获客", icon: RedBookIcon, type: "sph" },
    { name: "抖音获客", icon: DouyinIcon, type: "sph" },
    { name: "快手获客", icon: KuaishouIcon, type: "sph" },
];

const toPage = (type: string) => {
    const typeUrl = {
        sales: "/app/person_wechat/chat",
        matrix: "/app/matrix?type=1",
        dh: "/app/digital_human?type=1",
        sph: "/app/sph?type=1",
        staff: "/staff",
        device: "/device",
    };
    router.push(typeUrl[type]);
};

const contentPost = (text: string) => {
    chatStore.setAgent(agent.value);
    sendMessage(text);
    clear();
    chatStore.clearFiles();
    chattingRef.value?.cleanInput();
};

watch(
    () => route.query,
    () => {
        initialize().then(async () => {
            await getAgentList();
            await setup();
            if (route.query.agent_id) {
                setAgent({
                    id: route.query.agent_id as string,
                    name: route.query.agent_name as string,
                });
            }
        });
    },
    { immediate: true, deep: true }
);

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
        font-size: var(--el-font-size-base);
    }
    svg {
        display: inline;
    }

    .chat-rich-text {
        font-size: var(--el-font-size-base);
        padding: 8px 0;
        min-height: 80px;
        .chat-grid-input {
            font-size: var(--el-font-size-base);
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
        font-size: var(--el-font-size-base);
        font-style: inherit;
    }
}
</style>

<style lang="scss">
/* Global styles for chat dialogs */
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
