<template>
    <div class="flex h-full w-full flex-col">
        <div class="grow flex min-h-0 gap-4">
            <ElAside width="220px">
                <div class="h-full relative">
                    <SidebarConfig
                        ref="sidebarConfigRef"
                        :form-list="detail.template_info?.form"
                        :detail="{
                            logo: detail.logo,
                            name: detail.name,
                            description: detail.description,
                        }"
                        :is-lock="isReceiving"
                        @change-scene="changeScene"
                        @success="getSliderParams" />
                </div>
            </ElAside>
            <div class="grow h-full relative rounded-xl py-4">
                <div class="pt-5 h-full mr-4 px-4">
                    <Chatting
                        v-if="!loading"
                        ref="chattingRef"
                        :is-stop="isStopChat"
                        :content-list="chatContentList"
                        :send-disabled="isReceiving"
                        :tokens="getSceneTokens"
                        @close="chatClose"
                        @content-post="contentPost"
                        @update:file-lists="getFileLists"
                        @new-chat="handleOpenForm"
                        @confirm-knb="handleConfirmKnb">
                        <template #content v-if="!chatContentList.length">
                            <div
                                v-if="!chatContentList.length"
                                class="flex items-center justify-center flex-col h-full w-full md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] mx-auto text-center">
                                <div class="mb-3">
                                    <img :src="detail.logo" v-if="detail.logo" class="w-12 h-12" />
                                    <div class="w-12 h-12" v-else>
                                        <default-icon :icon-size="28"></default-icon>
                                    </div>
                                </div>
                                <div class="text-center text-2xl font-semibold">
                                    {{ detail.name }}
                                </div>
                                <div class="text-base">
                                    {{ detail.description }}
                                </div>
                                <div>
                                    <div
                                        class="mx-3 mt-12 flex md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] flex-wrap items-stretch justify-center gap-4">
                                        <template v-for="(item, index) in detail.preliminary_ask">
                                            <button
                                                v-if="item.value"
                                                class="relative flex w-40 flex-col gap-2 rounded-2xl border border-token-border-light px-3 pb-4 pt-3 text-start align-top text-[15px] shadow-[0_0_2px_0_rgba(0,0,0,0.05),0_4px_6px_0_rgba(0,0,0,0.02)] transition hover:bg-token-main-surface-secondary"
                                                @click="contentPost(item.value)">
                                                <div class="line-clamp-3 text-balance text-gray-600 break-all">
                                                    {{ item.value }}
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template #input>
                            <div
                                ref="chatAreaRef"
                                class="min-h-[30px] max-h-[200px] overflow-y-auto dynamic-scroller"></div>
                        </template>
                    </Chatting>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import ChatArea from "chatarea";
import "chatarea/lib/ChatArea.css";
import { robotDetail } from "@/api/robot";
import { chatRobotSendTextStream, getChatLog } from "@/api/chat";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { KnTypeEnum } from "@/pages/knowledge_base/_enums";
import SidebarConfig from "./_components/sidebar.vue";

const userStore = useUserStore();
const { userTokens, userInfo, isLogin } = toRefs(userStore);
const getSceneTokens = userStore.getTokenByScene(TokensSceneEnum.SCENE_CHAT)?.score;

const route = useRoute();

const detail = reactive<Record<string, any>>({
    id: "",
    name: "",
    logo: "",
    form_info: "",
    description: "",
    template_info: {},
    preliminary_ask: [],
    assistants_id: "",
});
const sidebarConfigRef = shallowRef<InstanceType<typeof SidebarConfig>>();

const loading = ref(false);

const changeScene = () => {
    chatContentList.value = [];
};

//  聊天内容区 S
const chatContentList = ref<any[]>([]);

let streamReader: ReadableStreamDefaultReader<Uint8Array> | null = null;

const isReceiving = ref(false);
const chattingRef = ref(null);
const isStopChat = ref(false);
const fileLists = ref([]);
const taskId = ref<any>("");

const chatLogParams = reactive<any>({
    page_no: 1,
    page_size: 1500,
});

const chatPostParams = reactive<any>({
    indexid: "",
    rerank_min_score: "",
    kb_id: "",
});

const handleConfirmKnb = (val: any) => {
    const { type, data } = val;
    if (type == KnTypeEnum.RAG) {
        chatPostParams.indexid = data.index_id;
        chatPostParams.rerank_min_score = data.rerank_min_score;
        chatPostParams.kb_id = undefined;
    } else if (type == KnTypeEnum.VECTOR) {
        chatPostParams.kb_id = data.id;
        chatPostParams.indexid = undefined;
        chatPostParams.rerank_min_score = undefined;
    }
};

//
const getSliderParams = (data: any) => {
    fileLists.value = sidebarConfigRef.value.fileLists || [];
    contentPost("", false, true);
};

// 获取聊天记录
const getChatList = async () => {
    try {
        const data = await getChatLog({
            ...chatLogParams,
            assistant_id: route.query.id,
            task_id: taskId.value,
        });
        const transformData = data?.map((item: any) => {
            if (item.type == 1) {
                return { ...item, form_avatar: userInfo.value.avatar };
            } else {
                return {
                    ...item,
                    consume_tokens: item.tokens_info || {},
                    form_avatar: detail.logo,
                };
            }
        });
        chatContentList.value = transformData;
    } catch (err) {}
};

// 获取上传文件
const getFileLists = (file: any[]) => {
    fileLists.value = file;
};

let chat;
const chatAreaRef = ref(null);
const initChat = () => {
    const { template_info } = detail;
    const selectList = template_info?.form
        ?.filter((item) => item.name == "WidgetSelect")
        .map((item) => ({
            dialogTitle: item.props.title,
            key: item.props.field,
            options: item.props.options.map((option, index) => ({
                id: index + 1,
                name: option,
            })),
        }));
    // 实例chat对象
    chat = new ChatArea({
        elm: chatAreaRef.value,
        placeholder: `请输入你要的内容`,
        needCallEvery: false,
        selectList: selectList,
    });

    chat.addEventListener("enterSend", () => {
        contentPost(chat.getText());
    });
    chat.addEventListener("operate", (e) => {
        isReceiving.value = chat.isEmpty();
        chattingRef.value.setInput(chat.getText());
    });
    chat.addEventListener("defaultAction", (type) => {
        switch (type) {
            case "CUT": // 剪切
                chattingRef.value.setInput("");
                break;
        }
    });
    if (template_info?.form && !taskId.value) {
        processTextAndForm(
            detail.form_info,
            template_info.form,
            (text) => chat.insertText(text),
            (field, title, defaultValue) => chat.setInputTag(field, title, defaultValue)
        );
    }
    chattingRef.value.setInput(chat.getText());
};

const processTextAndForm = (text, form, insertText, setInputTag) => {
    // 获取按出现顺序切割后的结果
    const segments = splitTextByVariables(text, form);

    // 遍历所有片段并插入
    segments.forEach((segment) => {
        if (segment.type === "text") {
            // 插入文本片段
            chat.insertText(segment.content);
        } else if (segment.type === "variable") {
            // 插入变量输入框
            const item = segment.content;
            if (item.name == "WidgetSelect") {
                const options = item.props.options.map((option, index) => ({ id: index + 1, name: option }));

                if (options.length) {
                    chat.setSelectTag({ id: options[0].id, name: options[0].name }, item.props.field);
                }
            } else {
                chat.setInputTag(item.props.field, item.props.title, item.props.default);
            }
        }
    });
};

const splitTextByVariables = (text, variables) => {
    // 创建变量名到变量对象的映射
    const variableMap = {};
    variables.forEach((variable) => {
        variableMap[variable.props.field] = variable;
    });

    // 找出所有变量占位符及其位置
    const placeholders = [];
    const variableRegex = /\$\{(\w+)\}/g;
    let match;

    while ((match = variableRegex.exec(text)) !== null) {
        const variableName = match[1];
        const variable = variableMap[variableName];

        if (variable) {
            placeholders.push({
                name: variableName,
                variable: variable,
                start: match.index,
                end: match.index + match[0].length,
            });
        }
    }

    // 按出现顺序排序
    placeholders.sort((a, b) => a.start - b.start);

    // 切割文本
    const result = [];
    let currentIndex = 0;

    placeholders.forEach((placeholder) => {
        // 添加当前位置到占位符之间的文本
        if (currentIndex < placeholder.start) {
            result.push({
                type: "text",
                content: text.substring(currentIndex, placeholder.start),
            });
        }

        // 添加变量标记
        result.push({
            type: "variable",
            content: placeholder.variable,
        });

        currentIndex = placeholder.end;
    });

    // 添加剩余文本
    if (currentIndex < text.length) {
        result.push({
            type: "text",
            content: text.substring(currentIndex),
        });
    }

    return result;
};

// 发送问题
const contentPost = async (userInput?: any, isNewChat: boolean = false, isSlider: boolean = false) => {
    if (!isLogin.value) {
        userStore.toggleShowLogin();
        return;
    }
    await sidebarConfigRef.value?.formValidate();
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    if (isReceiving.value) return;
    isReceiving.value = true;
    if (!isNewChat && !isSlider) {
        chatContentList.value.push({
            type: 1,
            message: chat.getText() || userInput,
            from_avatar: userInfo.value.avatar,
            fileLists: fileLists.value,
        });
    }

    const result = reactive({
        type: 2,
        loading: true,
        reply: "",
        is_reasoning_finished: false,
        reasoning_content: "",
        error: "",
        from_avatar: detail.logo,
        consume_tokens: {},
    });
    chatContentList.value.push(result);

    // 清空输入框
    chat.clear();
    chattingRef.value.setInput("");

    try {
        await chatRobotSendTextStream(
            {
                message: chat.getText() || userInput || "",
                message_ext: JSON.stringify(sidebarConfigRef.value?.formData),
                assistant_id: route.query.id,
                task_id: taskId.value,
                ...chatPostParams,
            },
            {
                onstart(reader) {
                    streamReader = reader;
                    isStopChat.value = true;
                },
                onmessage(value) {
                    value
                        .trim()
                        .split("data:")
                        .forEach((text, index) => {
                            if (text !== "") {
                                try {
                                    const dataJson = JSON.parse(text);
                                    const { object, content, task_id, reasoning_content, usage } = dataJson;
                                    if ((content || reasoning_content) && object === "loading") {
                                        if (reasoning_content) {
                                            result.is_reasoning_finished = false;
                                            result.reasoning_content += reasoning_content;
                                        } else {
                                            result.is_reasoning_finished = true;
                                            result.reply += content;
                                        }
                                    }
                                    if (object === "finished") {
                                        result.loading = false;
                                        result.consume_tokens = {
                                            ...usage,
                                        };
                                        if (!taskId.value) {
                                            taskId.value = task_id;
                                        }
                                        replaceState({
                                            task_id: task_id || "",
                                        });
                                        return;
                                    }
                                } catch (error) {}
                            }
                        });
                },
                async onclose() {
                    result.loading = false;
                    resetChat();

                    setTimeout(async () => {
                        await nextTick();
                        chattingRef.value.scrollToBottom();
                    }, 600);
                    userStore.getUser();
                },
            }
        );
    } catch (error) {
        if (error && error.type == "cancel") return;
        result.loading = false;
        result.error = error || "发生错误";
        resetChat();
        feedback.msgError(error || "发生错误");
    }
    nextTick(() => {
        chattingRef?.value?.scrollToBottom();
    });
};

const handleOpenForm = () => {
    if (!taskId.value) {
        feedback.msgError("当前会话已经是最新的了");
        return;
    }
    taskId.value = "";
    chatContentList.value = [];
    resetChat();
    replaceState({
        ppid: route.query.ppid,
        pid: route.query.pid,
        id: route.query.id,
        task_id: "",
    });
    contentPost("你好", true);
};

const resetChat = () => {
    fileLists.value = [];
    isReceiving.value = false;
    isStopChat.value = false;
};

const chatClose = (index?: number) => {
    streamReader.cancel();
    chatContentList.value[chatContentList.value.length - 1].loading = false;
    isReceiving.value = false;
    isStopChat.value = false;
    if (chatContentList.value[index] && !chatContentList.value[index]?.reply) {
        chatContentList.value[index].reply = "用户已停止内容生成";
    }
    // $request.cancelRequest();
};

// 聊天内容区 E

const getDetail = async () => {
    const data = await robotDetail({
        assistant_id: route.query.id,
    });
    Object.keys(detail).forEach((key) => {
        //@ts-ignore
        detail[key] = data[key];
    });
};

const init = async () => {
    try {
        taskId.value = route.query.task_id;
        if (!isReceiving.value) {
            loading.value = true;
            await getDetail();
            loading.value = false;
            if (taskId.value && taskId.value !== "undefined") {
                await getChatList();
                await nextTick();
                chattingRef.value.scrollToBottom();
            }
            await nextTick();
            initChat();
        }
    } catch (error) {
        loading.value = false;
    }
};

watch(
    () => route.params,
    () => {
        $request.cancelRequest();
        isReceiving.value = false;
        init();
    }
);

onMounted(() => {
    init();
});

onBeforeUnmount(() => {
    // 释放实例
    if (chat) {
        chat.dispose();
        chat = null;
    }
});

definePageMeta({
    layout: "base",
    title: "AI对话",
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
    }
    .chat-placeholder-wrap {
        padding: 8px 0;
        font-size: var(--el-font-size-base);
        font-style: inherit;
    }
}
</style>
