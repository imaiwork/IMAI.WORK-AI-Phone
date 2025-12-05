<template>
    <div class="h-screen">
        <ElWatermark
            class="h-full"
            :content="appStore.getWebsiteConfig.shop_name"
            :z-index="-1"
            :font="{
                color: 'rgba(0,0,0,0.06)',
                fontSize: 12,
            }">
            <div class="px-4 h-full">
                <Chatting
                    ref="chattingRef"
                    :is-stop="isStopChat"
                    :content-list="chatContentList"
                    :send-disabled="isReceiving"
                    :is-disabled-humanize="true"
                    :is-new-chat="false"
                    :is-auth-login="false"
                    @close="stopChat"
                    @content-post="contentPost">
                    <template #content>
                        <div class="h-full flex flex-col items-center justify-center">
                            <img :src="robot?.image" class="w-[112px] h-[112px] rounded-full" />
                            <div class="text-[32px] font-bold mt-4">{{ robot?.name }}</div>
                            <div class="text-[#7b7b7b] mt-[10px] text-xs w-[383px] mx-auto text-center">
                                {{ robot?.welcome_introducer }}
                            </div>
                        </div>
                    </template>
                    <template #chat-area-top>
                        <div class="flex items-center gap-3">
                            <ElButton round :icon="Delete" @click="clearChat" class="mb-3">清空</ElButton>
                            <div v-if="menus.length > 0">
                                <ElScrollbar>
                                    <div class="flex pb-3 gap-3">
                                        <div
                                            v-for="(item, index) in menus"
                                            class="whitespace-nowrap rounded-[24px] py-2 px-4 bg-white border border-[#F1F1F2] text-xs cursor-pointer hover:bg-[#F1F1F2]"
                                            :key="index"
                                            @click="contentPost(item)">
                                            {{ item }}
                                        </div>
                                    </div>
                                </ElScrollbar>
                            </div>
                        </div>
                    </template>
                    <template #chat-area-bottom v-if="robot.copyright">
                        <div class="text-xs text-[#7b7b7b] text-center mt-2 line-clamp-1">
                            {{ robot.copyright }}
                        </div>
                    </template>
                </Chatting>
            </div>
        </ElWatermark>
    </div>
    <pwd-check v-model:show="showPwdCheck" ref="pwdCheckRef" @close="showPwdCheck = false" @confirm="login" />
</template>

<script setup lang="ts">
import { getPublishDetail, getPublishAgentChatRecord, getAgentChatUniqueId, clearChatRecord } from "@/api/agent";
import { publishChatSendTextStream } from "@/api/chat";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { AUTHORIZATION_KEY } from "@/enums/cacheEnums";
import { useLocalStorage } from "@vueuse/core";
import { Sse } from "@/utils/http/sse";
import { Delete } from "@element-plus/icons-vue";
import PwdCheck from "./_components/pwd-check.vue";

const appStore = useAppStore();
const userStore = useUserStore();

const route = useRoute();
const apikey = route.params.key as string;

const chatContentList = ref<any[]>([]);
const isReceiving = ref(false);
const isStopChat = ref(false);
const chattingRef = shallowRef();

const getChatRecord = async () => {
    try {
        const { lists } = await getPublishAgentChatRecord(
            { share_apikey: apikey, identity: userStore.visitorId, page_size: 25000 },
            {
                password: password.value,
                authorization: apikey,
                identity: userStore.visitorId,
            }
        );
        chatContentList.value = lists.map((item) =>
            item.type == 1
                ? { ...item, message: item.content }
                : { ...item, reply: item.content, form_avatar: robot.value.image }
        );
        chatScrollToBottom();
    } catch (error) {
        if (error == "访问密码错误!") {
            logout();
            await checkLogin();
        }
        return Promise.reject();
    }
};

let streamReader: any = null;
const contentPost = async (content: string) => {
    await userStore.getFingerprint();
    await checkLogin();
    const chatKey = Date.now();
    chatContentList.value.push({
        type: 1,
        message: content,
        reasoning: "",
    });
    chatContentList.value.push({
        type: 2,
        reply: "",
        reasoning: "",
        loading: true,
        key: chatKey,
        form_avatar: robot.value.image,
    });
    const currentChat = chatContentList.value.find((item: any) => item.key === chatKey);
    isReceiving.value = true;
    chatScrollToBottom();
    try {
        await publishChatSendTextStream(
            {
                question: content,
                unique_id: uniqueId.value,
                stream: true,
            },
            {
                password: password.value,
                authorization: apikey,
                identity: userStore.visitorId,
            },
            {
                onstart: (reader) => {
                    streamReader = reader;
                    isStopChat.value = true;
                },
                onmessage: (value) => {
                    value
                        .trim()
                        .split("data:")
                        .forEach((text) => {
                            if (!text) return;

                            try {
                                const { object, content, task_id: newTaskId, usage } = JSON.parse(text);

                                if (object === "loading") {
                                    currentChat.reply += content;
                                } else if (object === "finished") {
                                    currentChat.loading = false;
                                    currentChat.consume_tokens = usage;
                                }
                                chatScrollToBottom();
                            } catch (error) {
                                console.error("解析流式消息失败:", error, "原始文本:", text);
                            }
                        });
                },
                onclose: () => {
                    currentChat.loading = false;
                    isReceiving.value = false;
                    isStopChat.value = false;
                    chatScrollToBottom();
                },
            }
        );
        // streamReader.addEventListener("reasoning", ({ data: dataJson }: any) => {
        //     const { data, index } = dataJson;
        //     if (!currentChat.reasoning) {
        //         currentChat.reasoning = "";
        //     }
        //     currentChat.reasoning += data;
        // });
        // streamReader.addEventListener("chat", ({ data: dataJson }: any) => {
        //     const { data, index } = dataJson;
        //     if (!currentChat.reply) {
        //         currentChat.reply = "";
        //     }
        //     currentChat.reply += data;
        //     console.log(currentChat);
        // });
        // streamReader.addEventListener("close", () => {
        //     currentChat.loading = false;
        //     isReceiving.value = false;
        //     setTimeout(async () => {
        //         await getChatRecord();
        //         chattingRef.value.scrollToBottom();
        //     }, 1000);
        // });
        // streamReader.addEventListener("error", (ev) => {
        //     if (ev.errorType === "connectError") {
        //         feedback.msgError("请求失败，请重试");
        //     }
        //     // 登录失效
        //     if (ev.data?.code === 1200) {
        //         feedback.msgError(ev.data?.message);
        //         logout();
        //         setTimeout(() => {
        //             checkLogin();
        //         }, 10);
        //     }
        //     if (["connectError", "responseError"].includes(ev.errorType!)) {
        //         chatContentList.value.splice(chatContentList.value.length - 2, 2);
        //     }
        //     currentChat.loading = false;
        //     setTimeout(() => {
        //         isReceiving.value = false;
        //     }, 200);
        // });
    } catch (error) {
        currentChat.loading = false;
        isReceiving.value = false;
        currentChat.reply = "请求失败，请重试";
    }
};

const chatScrollToBottom = () => {
    setTimeout(() => {
        chattingRef.value.scrollToBottom();
    }, 100);
};

const clearChat = () => {
    useNuxtApp().$confirm({
        title: "提示",
        message: "确定要清空聊天记录吗？",
        onConfirm: async () => {
            if (isReceiving.value) {
                stopChat();
            }
            if (chatContentList.value.length == 0) {
                feedback.msgError("没有聊天记录");
                return;
            }
            try {
                await clearChatRecord(
                    {},
                    {
                        password: password.value,
                        authorization: apikey,
                        identity: userStore.visitorId,
                    }
                );
                await getChatRecord();
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const stopChat = () => {
    streamReader?.cancel();
    if (isReceiving.value) {
        const lastMessage = chatContentList.value[chatContentList.value.length - 1];
        lastMessage.loading = false;
        isReceiving.value = false;
        isStopChat.value = false;
    }
};

const detail = ref<any>({});
const robot = ref<any>({});
const menus = ref<any[]>([]);

const showPwdCheck = ref(false);
const loginRef = ref<InstanceType<typeof PwdCheck>>();
const password = ref<string>("");
const checkLogin = () => {
    const pwd = useLocalStorage<Record<string, string>>(AUTHORIZATION_KEY, {});
    password.value = pwd.value[apikey] || "";
    if (detail.value.pwd && !password.value) {
        showPwdCheck.value = true;
        return Promise.reject();
    }
};

const login = async (data: any) => {
    const pwd = useLocalStorage<Record<string, string>>(AUTHORIZATION_KEY, {});
    password.value = data.password;
    pwd.value = Object.assign(pwd.value, {
        [apikey]: data.password,
    });
    showPwdCheck.value = false;
    await getChatRecord();
};

const logout = () => {
    const pwd = useLocalStorage<Record<string, string>>(AUTHORIZATION_KEY, {});
    pwd.value = Object.assign(pwd.value, {
        [apikey]: "",
    });
};
const uniqueId = useLocalStorage("SHARE_CHAT_UNIQUE_ID", "");

const getUniqueId = async () => {
    if (uniqueId.value) {
        return;
    }
    const res = await getAgentChatUniqueId(
        { robot_id: detail.value.robot.id },
        {
            password: password.value,
            authorization: apikey,
            identity: userStore.visitorId,
        }
    );
    uniqueId.value = res[0];
};

const getDetail = async () => {
    const res = await getPublishDetail({ apikey });
    detail.value = res;
    robot.value = res.robot;
    menus.value = res.menus;
    checkLogin();
    getUniqueId();
};

const init = async () => {
    await getDetail();
    await getChatRecord();
};

onMounted(async () => {
    await userStore.getFingerprint();
    init();
});

definePageMeta({
    layout: false,
});
</script>

<style scoped></style>
