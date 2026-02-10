import { chatSendTextStream } from "@/api/chat";
import {
    getCozeAgentDetail as getCozeAgentDetailApi,
    getCopyWritingGenerate,
    cozeAgentChatStream,
    cozeAgentChat,
    cozeAgentChatView,
    cozeAgentChatMsgList,
} from "@/api/agent";

interface Options {
    onmessage?: (value: string) => void;
    onclose?: () => void;
    onstart?: (reader: ReadableStreamDefaultReader<Uint8Array>) => void;
    onfinish?: () => void;
    onerror?: (error: any) => void;
}

// 聊天状态枚举
enum CozeChattingStatus {
    CREATED = "created",
    IN_PROGRESS = "in_progress",
    COMPLETED = "completed",
    FAILED = "failed",
    REQUIRES_ACTION = "requires_action",
    CANCELED = "canceled",
}

export default function useAgent(options: Options) {
    const { onmessage, onclose, onstart, onerror, onfinish } = options;

    const agentId = ref<string | number>();
    const agentDetail = ref<Record<string, any>>({});
    const isStopChat = ref<boolean>(false);
    const pollingEnd = ref<(() => void) | null>(null);
    const result = ref<string>("");

    const systemChat = async ({
        sn,
        keywords,
        number,
        length,
    }: {
        sn: number;
        keywords: string;
        number: number;
        length: number;
    }) => {
        return new Promise<any>((resolve, reject) => {
            getCopyWritingGenerate({
                sn,
                keywords,
                number,
                length,
            })
                .then((res) => {
                    resolve(res);
                })
                .catch((err) => {
                    reject(err);
                });
        });
    };

    const streamAgentChat = async (userInput: string | undefined) => {
        try {
            await chatSendTextStream(
                {
                    message: userInput || "",
                    robot_id: agentId.value,
                    is_network_search: 0,
                    open_reasoning: 0,
                },
                {
                    onstart(reader) {
                        isStopChat.value = true;
                    },
                    onmessage: (value) => {
                        value
                            .trim()
                            .split("data:")
                            .forEach((text, index) => {
                                if (text !== "") {
                                    try {
                                        const dataJson = JSON.parse(text);
                                        const { object, content, task_id, reasoning_content, usage } = dataJson;
                                        if ((content || reasoning_content) && object === "loading") {
                                            result.value += content;
                                            onmessage?.(content);
                                        }
                                        if (object === "finished") {
                                            onfinish?.();
                                            return;
                                        }
                                    } catch (error) {}
                                }
                            });
                    },
                    onclose() {
                        onclose?.();
                    },
                }
            );
        } catch (error: any) {}
    };

    const cozeChat = async (userInput: string | undefined) => {
        try {
            const { conversation_id: newConvId, id: chatId } = await cozeAgentChat({
                id: agentDetail.value.id,
                content: userInput || "",
            });
            const pollParams = {
                id: agentDetail.value.id,
                conversation_id: newConvId,
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
                            data.forEach((item: any) => (result.value += item.content));
                        }
                        onfinish?.();
                    } else if (status === CozeChattingStatus.FAILED) {
                        throw new Error("聊天失败");
                    }
                } catch (error: any) {
                    end();
                    throw error;
                }
            }, {});
            pollingEnd.value = end;
            start();
        } catch (error: any) {
            onerror?.(error);
        }
    };

    const streamCozeChat = async (userInput: string | undefined) => {
        try {
            await cozeAgentChatStream(
                {
                    id: agentDetail.value.id,
                    content: userInput || "",
                },
                {
                    onstart(reader) {
                        isStopChat.value = true;
                    },
                    onmessage: (value) => {
                        value
                            .trim()
                            .split("data:")
                            .forEach((text, index) => {
                                if (text !== "") {
                                    try {
                                        const dataJson = JSON.parse(text);
                                        const { object, content, task_id, reasoning_content, usage } = dataJson;
                                        if ((content || reasoning_content) && object === "loading") {
                                            result.value += content;
                                            onmessage?.(content);
                                        }
                                        if (object === "finished") {
                                            onfinish?.();
                                            return;
                                        }
                                    } catch (error) {}
                                }
                            });
                    },
                    onclose() {
                        onclose?.();
                    },
                }
            );
        } catch (error: any) {
            onerror?.(error);
        }
    };

    // 统一除系统外的处理
    const handleGenerate = async (userInput: string | undefined, agentType: number) => {
        if (agentType == 2) {
            await streamAgentChat(userInput);
        } else if (agentType == 3) {
            if (agentDetail.value.stream == 1) {
                await streamCozeChat(userInput);
            } else {
                await cozeChat(userInput);
            }
        }
    };

    // 获取coze智能体详情
    const getCozeAgentDetail = async (agentId: number): Promise<Record<string, any>> => {
        return new Promise((resolve, reject) => {
            getCozeAgentDetailApi({ id: agentId })
                .then((res) => {
                    resolve(res);
                })
                .catch((err) => {
                    reject(err);
                });
        });
    };

    // 获取机器人详情
    const getDetail = async (id: number, type: number) => {
        agentId.value = id;
        if ([3, 4].includes(type)) {
            agentDetail.value = await getCozeAgentDetail(agentId.value);
        }
    };

    return {
        result,
        pollingEnd,
        getDetail,
        handleGenerate,
        systemChat,
        streamCozeChat,
        streamAgentChat,
    };
}
