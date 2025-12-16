<template>
    <div class="flex flex-col h-full">
        <div class="grow min-h-0 px-4">
            <Chatting
                ref="chattingRef"
                :is-new-chat="false"
                :is-stop="isStopChat"
                :is-disabled-humanize="true"
                :content-list="chatContentList"
                :send-disabled="isReceiving"
                :is-upload-file="false"
                :is-network="false"
                @content-post="onContentPost"
                @close="stopChat">
                <template #content>
                    <div class="h-full w-full">
                        <div class="md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] mx-auto">
                            <img :src="detail.avatar" class="w-[70px] h-[70px] rounded-[10px] mt-[50px]" />
                            <div v-html="config?.onboarding_info?.prologue" class="mt-5 text-[14px]"></div>
                            <div class="mt-5 flex flex-col gap-2">
                                <div
                                    v-for="(item, index) in config?.onboarding_info?.suggested_questions"
                                    :key="index"
                                    class="cursor-pointer"
                                    @click="onContentPost(item)">
                                    <div
                                        class="bg-[#F6F6F6] rounded-[8px] px-4 py-2 w-fit cursor-pointer hover:bg-[#E5E5E5]">
                                        {{ item }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template #chat-area-top>
                    <div>
                        <ElScrollbar>
                            <div class="flex gap-2 mb-2 whitespace-nowrap">
                                <div v-for="(item, index) in config?.shortcut_commands" @click="openCommand(index)">
                                    <ElButton>{{ item.name }}</ElButton>
                                </div>
                            </div>
                        </ElScrollbar>
                    </div>
                </template>
                <template #customSendArea v-if="currCommandIndex >= 0">
                    <div class="w-full rounded-[20px] border border-primary overflow-hidden">
                        <div class="h-[50px] px-2 bg-[#F2F7FF] flex items-center justify-between">
                            <div class="font-bold text-lg line-clamp-1 break-all ml-3">
                                {{ getShortcutCommands?.name }}
                            </div>
                            <div class="w-6 h-6" @click="closeCommand">
                                <close-btn :icon-size="14" />
                            </div>
                        </div>
                        <ElScrollbar :max-height="300">
                            <div class="p-5 w-full">
                                <ElForm class="command-form" inline label-position="top">
                                    <div class="grid grid-cols-2 gap-x-2 w-full">
                                        <div
                                            v-for="(item, index) in getShortcutCommands?.components"
                                            :key="index"
                                            :class="[item.type == 'file' ? 'col-span-2' : 'col-span-1']">
                                            <ElFormItem :label="item.name" prop="name">
                                                <div v-if="item.type == 'file'" class="w-full">
                                                    <upload
                                                        class="w-full"
                                                        type="file"
                                                        list-type="text"
                                                        drag
                                                        :multiple="false"
                                                        :max-size="200"
                                                        :limit="1"
                                                        :accept="getAccept(item.options)"
                                                        @remove="handleUploadRemove($event, item)"
                                                        @success="handleUploadSuccess($event, item)">
                                                        <div
                                                            class="w-full h-[52px] flex items-center justify-center gap-2 text-primary">
                                                            <Icon name="el-icon-Plus" :size="14"></Icon>
                                                            <span class="font-bold">点击或拖放</span>
                                                        </div>
                                                    </upload>
                                                </div>
                                                <div v-else-if="item.type == 'text'" class="w-full">
                                                    <ElInput
                                                        class="!w-full"
                                                        v-model="item.default_value"
                                                        :placeholder="item.description"
                                                        clearable />
                                                </div>
                                                <div v-else-if="item.type == 'select'" class="w-full">
                                                    <ElSelect
                                                        class="!w-full"
                                                        v-model="item.default_value"
                                                        :placeholder="item.description">
                                                        <ElOption
                                                            v-for="option in item.default_value"
                                                            :key="option"
                                                            :label="option"
                                                            :value="option"></ElOption>
                                                    </ElSelect>
                                                </div>
                                            </ElFormItem>
                                        </div>
                                    </div>
                                </ElForm>
                            </div>
                        </ElScrollbar>
                        <div class="flex justify-end mt-2 mb-[6px] mr-[6px]">
                            <div class="w-8 h-8">
                                <button
                                    v-if="isReceiving"
                                    @click="stopChat"
                                    class="flex w-full h-full items-center justify-center rounded-full bg-primary-light-9">
                                    <Icon name="local-icon-chat_stop" :size="18"></Icon>
                                </button>
                                <button
                                    v-else
                                    :disabled="isSendDisabled"
                                    class="flex w-full h-full items-center justify-center rounded-full bg-primary-light-9 text-white disabled:bg-[#F6F6F6] disabled:text-[#f4f4f4] disabled:hover:opacity-100"
                                    @click="handleCommandSend">
                                    <Icon
                                        name="local-icon-arrow_up"
                                        :color="isSendDisabled ? '#a9a9a9' : 'var(--color-primary)'"
                                        :size="18"></Icon>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </Chatting>
        </div>
    </div>
</template>

<script setup lang="ts">
import { cozeAgentChatRecord, cozeAgentGetConfig } from "@/api/agent";
import { useUserStore } from "@/stores/user";
import { useCozeChat } from "../../_composables/useCozeChat";

/**
 * @description Coze智能体聊天组件
 */
const props = defineProps<{
    agentId: string;
    cozeId: string;
    detail: any;
    conversationId: string | null;
}>();

const emit = defineEmits(["new-conversation", "conversation-id-change", "update:isReceiving"]);

const acceptMap: Record<string, string[]> = {
    image: [".jpg", ".jpeg", ".png", ".gif", ".bmp", ".webp", ".svg"],
    audio: [".mp3", ".wav", ".wma", ".aac", ".m4a", ".ogg", ".flac"],
    doc: [".doc", ".docx", ".pdf", ".txt", ".md"],
    table: [".xls", ".xlsx", ".csv", ".tsv"],
    code: [".py", ".java", ".html", ".css", ".js", ".php", ".c", ".cpp", ".sh", ".json", ".xml"],
    zip: [".zip", ".rar", ".7z", ".tar", ".gz", ".bz2", ".iso", ".dmg", ".pkg", ".deb", ".rpm", ".msi", ".exe"],
    ppt: [".ppt", ".pptx", ".pptm", ".pptm", ".pptx"],
    video: [".mp4", ".mov", ".avi", ".mkv", ".wmv", ".flv", ".webm"],
    txt: [".txt", ".md", ".log", ".error", ".warning", ".info", ".debug"],
};

const userStore = useUserStore();
const { userInfo, userTokens } = toRefs(userStore);
const chattingRef = ref();
const chatContentLoading = ref(false);

const config = ref<any>({});
const currCommandIndex = ref(-1);
const commandFile = ref<any>({});
const extraData = ref<any>({});

// 获取快捷指令数据
const getShortcutCommands = computed(() => {
    return currCommandIndex.value >= 0 ? config.value?.shortcut_commands?.[currCommandIndex.value] || {} : {};
});

const isSendDisabled = computed(() => {
    const { components } = getShortcutCommands.value;
    if (components && components.length > 0) {
        const isDisabled = components.some((item: any) => {
            if (item.type === "file") {
                return !commandFile.value?.[item.name];
            } else {
                return !item.default_value;
            }
        });
        return isDisabled;
    }
    return true;
});

// 获取文件接受类型
const getAccept = (options: any[]) => {
    if (options.length) {
        let accept: string = "";
        options.forEach((item: any) => {
            accept += acceptMap[item].join(",");
        });
        return accept;
    }
    return "*";
};

const openCommand = (index: number) => {
    currCommandIndex.value = index;
};

const closeCommand = () => {
    currCommandIndex.value = -1;
    extraData.value = {};
    commandFile.value = {};
};

const handleUploadRemove = (file: any, item: any) => {
    commandFile.value[item.name] = null;
};

const handleUploadSuccess = (file: any, item: any) => {
    commandFile.value[item.name] = file.data;
};

const handleCommandSend = () => {
    let { id, components, query_template } = getShortcutCommands.value;
    let parameters = {};
    components.forEach((item: any) => {
        if (item.type === "file") {
            query_template = query_template.replace(`{{${item.name}}}`, commandFile.value[item.name].uri);
            parameters[item.name] = commandFile.value[item.name].uri;
        } else {
            query_template = query_template.replace(`{{${item.name}}}`, item.default_value);
            parameters[item.name] = item.default_value;
        }
    });
    extraData.value = {
        command_id: id,
        parameters,
        text: query_template,
    };
    onContentPost(query_template);
    closeCommand();
};

// 使用Coze聊天逻辑
const { chatContentList, isReceiving, isStopChat, sendMessage, stopChat, setConversationId } = useCozeChat(
    toRef(props, "detail"),
    props.agentId
);

// 监听isReceiving状态并通知父组件
watch(isReceiving, (val) => emit("update:isReceiving", val));

// 发送消息
const onContentPost = async (content: string) => {
    if (userTokens.value <= 1) {
        feedback.msgPowerInsufficient();
        return;
    }
    await sendMessage(
        content,
        extraData.value,
        (newId) => {
            emit("conversation-id-change", newId);
        },
        (conv) => {
            emit("new-conversation", conv);
        }
    );
};

// 获取聊天记录
const getChatList = async (convId: string) => {
    if (!convId) {
        chatContentList.value = [];
        return;
    }
    chatContentLoading.value = true;
    try {
        const { lists } = await cozeAgentChatRecord({
            bot_id: props.cozeId,
            type: 1, // Coze智能体类型
            conversation_id: convId,
            page_size: 9999,
        });
        const historyMessages = lists.map((item: any) =>
            item.role === "user"
                ? { ...item, type: 1, message: item.content, form_avatar: userInfo.value.avatar }
                : {
                      ...item,
                      type: 2,
                      reply: item.content,
                      form_avatar: props.detail?.avatar,
                      consume_tokens: {
                          total_tokens: item.token_total,
                      },
                  }
        );
        chatContentList.value = historyMessages;
        await nextTick();
        chattingRef.value?.scrollToBottom();
    } catch (error) {
        feedback.msgError((error as string) || "发生错误");
    } finally {
        chatContentLoading.value = false;
    }
};

const getConfig = async () => {
    const data = await cozeAgentGetConfig({
        id: props.agentId,
    });
    config.value = data;
};

// 监听对话ID变化
watch(
    () => props.conversationId,
    (newId) => {
        setConversationId(newId || "");
        if (!isReceiving.value) {
            getChatList(newId || "");
            getConfig();
        }
        chattingRef.value?.resetScroll();
    },
    { immediate: true }
);

// 监听聊天内容变化，自动滚动到底部
watch(
    () => chatContentList.value,
    async () => {
        await nextTick();
        chattingRef.value?.scrollToBottom();
    },
    { deep: true }
);
</script>
<style lang="scss" scoped>
:deep(.upload) {
    .el-upload {
        .el-upload-dragger {
            @apply p-0 border-solid;
        }
    }
    .el-upload-list {
        .el-upload-list__item {
            @apply h-11 flex items-center shadow-[0_0_0_1px_#EFEFEF];
        }
        .el-progress {
            @apply top-[34px] left-0;
        }
    }
}
:deep(.command-form) {
    .el-form-item {
        @apply mr-0;
    }
}
</style>
