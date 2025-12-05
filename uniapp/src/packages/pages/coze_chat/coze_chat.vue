<template>
    <view class="h-screen flex flex-col relative bg-white">
        <view class="relative z-30">
            <u-navbar
                :border-bottom="false"
                :is-fixed="false"
                :is-custom-back-icon="true"
                :background="{
                    background: 'transparent',
                }">
                <template #custom-back-icon>
                    <view @click="handleBack">
                        <u-icon name="/static/images/icons/back_black.svg" size="32"></u-icon>
                    </view>
                </template>
                <view class="flex items-center gap-2">
                    <view
                        class="leading-[0] w-[64rpx] h-[64rpx] p-[6rpx] rounded-[10rpx] border border-solid border-[#E5E6F3] bg-white flex-shrink-0">
                        <image :src="detail.avatar" class="w-full h-full rounded-[10rpx]"></image>
                    </view>
                    <view>
                        <view class="font-bold text-[28rpx] line-clamp-1">{{ detail.name }}</view>
                        <view class="text-[20rpx] text-[#969EA9] line-clamp-1">{{ detail.introduced }}</view>
                    </view>
                </view>
            </u-navbar>
        </view>
        <view class="grow min-h-0" v-if="agentType == 1">
            <chat-scroll-view
                ref="chattingRef"
                is-coze
                :is-stop="isStopChat"
                :content-list="chatContentList"
                :send-disabled="isReceiving"
                :tokens="getSceneTokens"
                @close="handleChatClose"
                @add-session="addSession"
                @content-post="contentPost">
                <template #content>
                    <view
                        class="flex items-center justify-center flex-col h-full w-full px-4"
                        v-if="chatContentList.length == 0">
                        <view class="mb-3">
                            <image :src="detail.avatar" v-if="detail.avatar" class="w-[96rpx] h-[96rpx] rounded-full" />
                        </view>
                        <view class="text-center text-2xl font-semibold">
                            {{ detail.name }}
                        </view>
                        <view class="text-sm text-center mt-1">
                            {{ detail.description }}
                        </view>
                        <view class="mt-4 mx-4 flex">
                            <view class="flex flex-wrap gap-4">
                                <view class="w-[47%]" v-for="(item, index) in detail.preliminary_ask" :key="index">
                                    <view
                                        v-if="item.value"
                                        class="w-full h-full bg-white p-4 text-sm rounded-2xl"
                                        @click="contentPost(item.value)">
                                        <div class="line-clamp-5 break-all">
                                            {{ item.value }}
                                        </div>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </template>
                <template #sendLeft>
                    <view
                        class="flex-shrink-0 flex items-center bg-[#F2F2F2] justify-center rounded-full w-[90rpx] h-[90rpx]"
                        @click="showHistory = true">
                        <u-icon name="/static/images/icons/history.svg" :size="48"></u-icon>
                    </view>
                </template>
            </chat-scroll-view>
        </view>
        <view v-if="agentType == 2" class="grow min-h-0 flex flex-col">
            <view class="grow min-h-0">
                <scroll-view scroll-y class="h-full">
                    <view class="flex flex-col gap-4 p-4" v-if="!flowResult">
                        <view v-for="(item, index) in getFormItem" :key="index">
                            <view class="mb-4">
                                <text class="text-[#FF3C26]">*</text>
                                <text>{{ item.name }}</text>
                            </view>
                            <view
                                v-if="item.type === FormFieldTypeEnum.INPUT"
                                class="bg-[#F8F9FF] rounded-lg px-4 py-1">
                                <u-input v-model="formFlowData[item.fields]" :placeholder="item.message" />
                            </view>
                            <view
                                v-if="item.type === FormFieldTypeEnum.TEXTAREA"
                                class="bg-[#F8F9FF] rounded-lg px-4 py-1">
                                <u-input
                                    v-model="formFlowData[item.fields]"
                                    height="200"
                                    :maxlength="1000"
                                    :placeholder="item.message"
                                    type="textarea" />
                            </view>
                            <view
                                v-if="item.type === FormFieldTypeEnum.NUMBER"
                                class="bg-[#F8F9FF] rounded-lg px-4 py-1">
                                <u-input
                                    v-model="formFlowData[item.fields]"
                                    :placeholder="item.message"
                                    type="number" />
                            </view>
                            <view
                                v-if="
                                    [FormFieldTypeEnum.VIDEO, FormFieldTypeEnum.IMAGE, FormFieldTypeEnum.FILE].includes(
                                        item.type
                                    )
                                ">
                                <file-upload
                                    file-type="all"
                                    @update:modelValue="handleFileUpload($event, item.fields)" />
                            </view>
                        </view>
                    </view>
                    <view v-else class="flex flex-col gap-4 p-4">
                        <view
                            v-for="(value, key) in flowResult"
                            :key="key"
                            class="border border-solid border-[#0000000d] rounded-xl">
                            <view
                                class="flex items-center justify-between px-[30rpx] h-[104rpx] border-[0] border-b-[1rpx] border-solid border-[#0000000d]">
                                <view class="flex items-center gap-x-3">
                                    <view>{{ getOutputParams[key]?.name || "-" }}</view>
                                    <view class="px-2 py-[2px] bg-[#F2F2F2] rounded">
                                        {{ getOutputParams[key]?.type }}
                                    </view>
                                </view>
                                <view
                                    class="bg-[#000000] text-white text-[22rpx] w-[116rpx] h-[46rpx] rounded-[12rpx] flex items-center justify-center"
                                    @click="copy(value)"
                                    >复制</view
                                >
                            </view>
                            <view class="py-4 px-[14px] flex flex-col gap-2">
                                <view v-for="(item, index) in formatValue(value)" :key="index">
                                    <template
                                        v-if="
                                            [FormFieldTypeEnum.VIDEO, FormFieldTypeEnum.IMAGE].includes(
                                                getOutputParams[key]?.type
                                            )
                                        ">
                                        <video
                                            v-if="getOutputParams[key]?.type == FormFieldTypeEnum.VIDEO"
                                            :src="item"
                                            class="w-full max-h-[200px] rounded-[20rpx]"
                                            controls />
                                        <image
                                            v-if="getOutputParams[key]?.type == FormFieldTypeEnum.IMAGE"
                                            :src="item"
                                            class="w-full rounded-[20rpx]"
                                            mode="widthFix"
                                            show-menu-by-longpress="true"
                                            @click="previewImage(item)" />
                                        <view class="flex justify-end mt-2" v-if="item">
                                            <view
                                                class="bg-[#000000] text-white text-[22rpx] w-[116rpx] h-[46rpx] rounded-[12rpx] flex items-center justify-center"
                                                @click="download(item, getOutputParams[key])"
                                                >下载</view
                                            >
                                        </view>
                                    </template>
                                    <template v-else>
                                        <text class="break-all">{{ item }}</text>
                                    </template>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
            <view class="flex-shrink-0 bg-[#FFFFFFE0] shadow-[0_4rpx_10rpx_2rpx_rgba(0,0,0,0.1)] safe-area">
                <view class="px-4 flex items-center gap-x-5 pt-[20rpx]" v-if="!flowResult">
                    <view class="flex flex-col items-center gap-1 px-2 flex-shrink-0" @click="showHistory = true">
                        <u-icon name="/static/images/icons/history.svg" :size="48"></u-icon>
                        <text class="text-xs">历史记录</text>
                    </view>
                    <view class="flex-1">
                        <view
                            class="bg-black text-white rounded flex items-center justify-center h-[100rpx] text-[28rpx]"
                            @click="handleGenFlowChat"
                            >立即生成</view
                        >
                    </view>
                </view>
                <view class="px-4 flex items-center gap-x-5 pt-[20rpx]" v-else>
                    <view class="flex-1">
                        <view
                            class="bg-black text-white rounded flex items-center justify-center h-[100rpx] text-[28rpx]"
                            @click="openNewFlowChat"
                            >打开新会话</view
                        >
                    </view>
                </view>
            </view>
        </view>
        <popup-bottom
            v-model:show="showHistory"
            title="历史记录"
            show-close-btn
            :is-disabled-touch="true"
            custom-class="bg-[#F9FAFB]">
            <template #content>
                <view class="h-full py-[30rpx]">
                    <z-paging
                        ref="pagingRef"
                        v-model="recordLists"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="queryRecordList">
                        <view class="flex flex-col gap-4 px-[32rpx]">
                            <view
                                v-for="(item, index) in recordLists"
                                class="bg-white rounded-[24rpx] p-[24rpx]"
                                :key="index"
                                @click="handleRecord(item)">
                                <view class="text-[26rpx] break-all">
                                    {{ item.content }}
                                </view>
                                <view class="my-3">
                                    <u-line></u-line>
                                </view>
                                <view class="flex items-center justify-between">
                                    <view
                                        class="text-[#AEAFB0] text-xs bg-[#F9FAFB] rounded-[12rpx] py-[4rpx] px-[8rpx]">
                                        {{ item.create_time }}
                                    </view>
                                    <view @click.stop="handleDeleteRecord(item)">
                                        <u-icon name="/static/images/icons/delete.svg" size="24"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>
            </template>
        </popup-bottom>
        <recharge-popup ref="rechargePopupRef"></recharge-popup>
    </view>
</template>

<script lang="ts" setup>
import {
    getCozeAgentDetail,
    cozeAgentChatStream,
    cozeAgentChatRecord,
    cozeAgentChatRecordClear,
    cozeAgentChat,
    cozeAgentChatView,
    cozeAgentChatMsgList,
    cozeWorkflowGenerate,
} from "@/api/agent";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useLockFn } from "@/hooks/useLockFn";
import usePolling from "@/hooks/usePolling";
import { isJson, setFormData } from "@/utils/util";
import { useCopy } from "@/hooks/useCopy";
import requestCancel from "@/utils/request/cancel";
import { RequestCodeEnum } from "@/enums/requestEnums";
import { saveImageToPhotosAlbum, saveVideoToPhotosAlbum } from "@/utils/file";

// 聊天状态枚举
enum CozeChattingStatus {
    CREATED = "created",
    IN_PROGRESS = "in_progress",
    COMPLETED = "completed",
    FAILED = "failed",
    REQUIRES_ACTION = "requires_action",
    CANCELED = "canceled",
}

// 表单字段类型枚举
enum FormFieldTypeEnum {
    INPUT = "input",
    TEXTAREA = "textarea",
    NUMBER = "number",
    VIDEO = "video",
    IMAGE = "image",
    FILE = "file",
}

// 类型定义
interface AgentDetail {
    id: string;
    name: string;
    avatar: string;
    introduced: string;
    coze_id: string;
    stream: string | number;
    inputs: string;
    outputs: string;
    description?: string;
    preliminary_ask?: Array<{ value: string }>;
}

interface BaseMessage {
    type: 1 | 2; // 1: 用户消息, 2: AI回复
    message?: string;
    role?: string;
    content?: string;
    token_total?: number;
    loading?: boolean;
    reply?: string;
    create_time?: string;
    conversation_id: string;
    consume_tokens?: any;
}

interface FormField {
    name: string;
    fields: string;
    type: FormFieldTypeEnum;
    required: boolean;
    message: string;
    value?: string;
}

interface ChatLogParams {
    page_no: number;
    page_size: number;
    bot_id?: string;
    type?: number;
    conversation_id?: string;
}

// Store
const userStore = useUserStore();
const { userTokens, isLogin } = toRefs(userStore);
const getSceneTokens = userStore.getTokenByScene(TokensSceneEnum.SCENE_CHAT)?.score;

// 组件引用
const rechargePopupRef = ref();
const pagingRef = shallowRef();

// 页面状态
const agentType = ref<number>();
const detail = reactive<AgentDetail>({
    id: "",
    name: "",
    avatar: "",
    introduced: "",
    coze_id: "",
    stream: "",
    inputs: "",
    outputs: "",
});

// 表单状态
const formFlowData = ref<Record<string, string>>({});
const ruleFlow = ref<Record<string, { required: boolean; message: string }>>({});
const isStream = computed(() => detail.stream == 1);

const isImageError = ref(false);

/**
 * 从详情生成表单项
 */
const getFormItem = computed(() => {
    if (!detail.inputs) return [];

    try {
        const inputs: FormField[] = JSON.parse(detail.inputs);

        // 初始化表单数据和验证规则
        inputs.forEach((item) => {
            formFlowData.value[item.fields] = item.value || "";
            ruleFlow.value[item.fields] = {
                required: Boolean(item.required),
                message: item.type === FormFieldTypeEnum.FILE ? "请上传文件" : "请输入",
            };
        });

        return inputs;
    } catch (error) {
        console.error("解析表单配置失败:", error);
        return [];
    }
});

/**
 * 获取输出参数配置
 */
const getOutputParams = computed(() => {
    if (!detail.outputs) return {};

    try {
        const outputs: FormField[] = isJson(detail.outputs) ? JSON.parse(detail.outputs) : [];

        return outputs.reduce((acc, item) => {
            acc[item.fields] = { ...item };
            return acc;
        }, {} as Record<string, FormField>);
    } catch (error) {
        console.error("解析输出参数失败:", error);
        return {};
    }
});

// 历史记录状态
const recordLists = ref<BaseMessage[]>([]);
const showHistory = ref(false);

// 工具函数
const { copy } = useCopy();

// 聊天状态
const chattingRef = shallowRef();
const isReceiving = ref(false);
const isStopChat = ref(false);
const chatContentList = ref<BaseMessage[]>([]);
const conversationId = ref<string>("");
let streamReader: any = null;

// 聊天参数
const chatLogParams = reactive<ChatLogParams>({
    page_no: 1,
    page_size: 25000,
});

/**
 * 查询历史记录列表
 */
const queryRecordList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await cozeAgentChatRecord({
            page_no,
            page_size,
            bot_id: detail.coze_id,
            type: agentType.value,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

/**
 * 处理历史记录选择
 */
const handleRecord = async (item: BaseMessage) => {
    if (agentType.value == 1) {
        conversationId.value = item.conversation_id;
        await getChatList();
    } else if (agentType.value == 2) {
        const { lists } = await cozeAgentChatRecord({
            bot_id: detail.coze_id,
            type: 2, // Coze工作流类型
            conversation_id: item.conversation_id,
        });
        if (lists.length) {
            flowResult.value = JSON.parse(lists[0].content);
        }
    }
    showHistory.value = false;
};

/**
 * 处理历史记录删除
 */
const handleDeleteRecord = async (item: BaseMessage) => {
    const { confirm } = await new Promise<UniApp.ShowModalRes>((resolve) => {
        uni.showModal({
            title: "提示",
            content: "确定要删除该聊天记录吗？",
            success: resolve,
        });
    });

    if (!confirm) return;

    uni.showLoading({ title: "删除中", mask: true });

    try {
        await cozeAgentChatRecordClear({
            bot_id: detail.coze_id,
            conversation_id: item.conversation_id || "0",
        });
        uni.hideLoading();
        uni.showToast({
            title: "删除成功",
            icon: "none",
            duration: 3000,
        });

        pagingRef.value?.reload();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "删除失败",
            icon: "none",
            duration: 3000,
        });
    }
};

/**
 * 处理文件上传
 */
const handleFileUpload = (event: { url: string }[] | null, fields: string) => {
    formFlowData.value[fields] = event?.[0]?.url || "";
};

/**
 * 返回上一页
 */
const handleBack = () => {
    uni.navigateBack();
};

/**
 * 获取聊天记录
 */
const getChatList = async () => {
    try {
        const { lists } = await cozeAgentChatRecord({
            ...chatLogParams,
            bot_id: detail.coze_id,
            type: agentType.value,
            conversation_id: conversationId.value,
        });

        chatContentList.value = lists.map((item: any) =>
            item.role === "user"
                ? { ...item, type: 1, message: item.content }
                : {
                      ...item,
                      type: 2,
                      reply: item.content,
                      consume_tokens: {
                          total_tokens: item.token_total,
                      },
                  }
        );

        await scrollToBottom();
    } catch (error) {
        console.error("获取聊天记录失败:", error);
        uni.showToast({
            title: "获取聊天记录失败",
            icon: "none",
        });
    }
};

/**
 * 处理流式消息
 */
const handleStreamMessage = (value: string, result: BaseMessage) => {
    value
        .trim()
        .split("data:")
        .forEach((text) => {
            if (!text) return;
            try {
                const { object, content, conversation_id, usage } = JSON.parse(text);
                if (content && object === "loading") {
                    result.reply += content;
                }
                if (object === "finished") {
                    result.loading = false;
                    result.consume_tokens = {
                        total_tokens: usage.token_count,
                    };
                    if (!conversationId.value) {
                        conversationId.value = conversation_id;
                    }
                    return;
                }
                scrollToBottom();
            } catch (error) {
                console.error("解析流式消息失败:", error);
            }
        });
};

/**
 * 发送消息
 */
const contentPost = async (userInput?: string) => {
    // 权限检查
    if (!isLogin.value) {
        uni.$u.route({ url: "/pages/login/login" });
        return;
    }
    if (userTokens.value <= 1) {
        uni.$u.toast("算力不足，请充值！");
        rechargePopupRef.value?.open();
        return;
    }
    if (isReceiving.value) return;

    // 添加用户消息
    chatContentList.value.push({
        type: 1,
        message: userInput,
        conversation_id: conversationId.value || "",
    });

    // 准备AI回复消息
    const result: BaseMessage = reactive({
        type: 2,
        loading: true,
        reply: "",
        conversation_id: conversationId.value || "",
        consume_tokens: {},
    });
    chatContentList.value.push(result);
    isReceiving.value = true;

    await scrollToBottom();

    // 处理流式响应
    if (isStream.value) {
        await handleStreamResponse(userInput, result);
    } else {
        await handlePollingResponse(userInput, result);
    }
};

/**
 * 处理流式响应
 */
const handleStreamResponse = async (userInput: string | undefined, result: BaseMessage) => {
    try {
        await cozeAgentChatStream(
            {
                id: detail.id,
                content: userInput || "",
                conversation_id: conversationId.value,
            },
            {
                onstart(reader) {
                    streamReader = reader;
                    isStopChat.value = true;
                },
                onmessage: (value) => handleStreamMessage(value, result),
                onclose() {
                    result.loading = false;
                    userStore.getUser();
                    resetChat();
                    scrollToBottom();
                },
            }
        );
    } catch (error: any) {
        handleChatError(error, result);
    }
};

/**
 * 处理轮询响应
 */
let pollingEnd: any = null;
const handlePollingResponse = async (userInput: string | undefined, result: BaseMessage) => {
    try {
        isStopChat.value = true;
        const cozeParams = {
            id: detail.id,
            content: userInput || "",
            conversation_id: conversationId.value,
        };

        const { conversation_id: newConvId, id: chatId } = await cozeAgentChat(cozeParams);
        if (newConvId && conversationId.value !== newConvId) {
            conversationId.value = newConvId;
        }

        const pollParams = {
            id: detail.id,
            conversation_id: conversationId.value,
        };

        const { start, end } = usePolling(async () => {
            try {
                const { status, id: chatDetailId } = await cozeAgentChatView({
                    chat_id: chatId,
                    ...pollParams,
                });

                if (status === CozeChattingStatus.COMPLETED) {
                    end();
                    const data = await cozeAgentChatMsgList({
                        chat_id: chatDetailId,
                        ...pollParams,
                    });

                    if (data?.length) {
                        data.forEach((item: any) => (result.reply += item.content));
                    }

                    result.loading = false;
                    resetChat();
                    scrollToBottom();
                } else if (status === CozeChattingStatus.FAILED) {
                    throw new Error("聊天失败");
                }
            } catch (error: any) {
                end();
                handleChatError(error, result);
                scrollToBottom();
                throw error;
            }
        }, {});

        pollingEnd = end;

        await start();
    } catch (error) {
        handleChatError(error, result);
    }
};

/**
 * 处理聊天错误
 */
const handleChatError = (error: any, result: BaseMessage) => {
    result.loading = false;
    result.reply = error.errno === RequestCodeEnum.ABORT ? "用户已停止内容生成" : error || "发生错误";
    if (error.errno !== RequestCodeEnum.ABORT) {
        uni.$u.toast(error?.message || "发生错误");
    }

    result.loading = false;
    resetChat();
};

// 工作流状态
interface WorkflowResult {
    [key: string]: string;
}

const flowResult = ref<WorkflowResult | null>(null);

/**
 * 生成工作流内容
 */
const genFlowChat = async () => {
    if (isReceiving.value) return;

    // 表单验证
    if (!validateForm()) return;

    uni.showLoading({ title: "生成中", mask: true });

    try {
        const data = await cozeWorkflowGenerate({
            id: detail.id,
            ...formFlowData.value,
        });

        flowResult.value = data;
        uni.hideLoading();
        uni.showToast({
            title: "生成成功",
            icon: "none",
            duration: 3000,
        });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "生成失败",
            icon: "none",
            duration: 3000,
        });
    }
};

/**
 * 验证表单
 */
const validateForm = (): boolean => {
    return getFormItem.value.every((item: FormField) => {
        if (item.required) {
            const value = formFlowData.value[item.fields];
            if (!value) {
                uni.$u.toast(`请填写${item.name}`);
                return false;
            }
        }
        return true;
    });
};

/**
 * 打开新的工作流会话
 */
const openNewFlowChat = () => {
    flowResult.value = null;
    Object.keys(formFlowData.value).forEach((key) => {
        formFlowData.value[key] = "";
    });
};

/**
 * 使用防抖函数处理工作流生成
 */
const { lockFn: handleGenFlowChat, isLock } = useLockFn(genFlowChat);

/**
 * 滚动到底部
 */
const scrollToBottom = async () => {
    await nextTick();
    chattingRef.value?.scrollToBottom();
};

/**
 * 添加新会话
 */
const addSession = () => {
    conversationId.value = "";
    chatContentList.value = [];
    resetChat();
};

/**
 * 重置聊天状态
 */
const resetChat = () => {
    isReceiving.value = false;
    isStopChat.value = false;
};

/**
 * 关闭聊天
 */
const handleChatClose = () => {
    //#ifdef H5
    streamReader?.cancel();
    //#endif
    //#ifdef MP-WEIXIN
    streamReader?.abort();
    //#endif
    resetChat();
    // 这里还加加上如果是非流式的话，需要清空定时器
    if (pollingEnd) {
        pollingEnd();
        pollingEnd = null;
    }
    requestCancel.remove("/coze.cozeChat/chat");
    requestCancel.remove("/coze.cozeChat/retrieve");
    if (chatContentList.value.length > 0 && isStopChat.value) {
        chatContentList.value[chatContentList.value.length - 1].loading = false;
    }
};

/**
 * 预览图片
 */
const previewImage = (url: string) => {
    uni.previewImage({
        urls: [url],
    });
};

/**
 * 下载文件
 */

const download = (url: string, params: any) => {
    if (params.type == FormFieldTypeEnum.VIDEO) {
        saveVideoToPhotosAlbum(url);
    } else {
        saveImageToPhotosAlbum(url);
    }
};

/**
 * 格式化值
 */
const formatValue = (value: any) => {
    if (!value) return [];
    return Array.isArray(value) ? value : [value];
};

/**
 * 获取机器人详情
 */
const getDetail = async () => {
    const data = await getCozeAgentDetail({ id: detail.id });
    setFormData(data, detail);
};

/**
 * 初始化页面
 */
const init = async () => {
    uni.showLoading({ title: "加载中" });

    try {
        await getDetail();

        if (conversationId.value) {
            await getChatList();
            setTimeout(() => scrollToBottom(), 500);
        }
    } catch (error) {
        console.error("初始化页面失败:", error);
    } finally {
        uni.hideLoading();
    }
};

/**
 * 页面加载
 */
onLoad((query?: Record<string, any>) => {
    if (!query) return;
    const { id, task_id, type } = query;
    detail.id = id;
    conversationId.value = task_id || "";
    agentType.value = type;
    init();
});
</script>
