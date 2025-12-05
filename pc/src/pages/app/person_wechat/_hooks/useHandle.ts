import {
    getWeChatAi,
    saveWeChatAi,
    updateWeChatFriend,
    getWeChatFriend,
    addWeChatFriend,
    deleteWeChatFriend,
    getRobotReplyStrategy,
    tagListsV2,
    friendTagDetail,
    friendTagDelete,
    friendTagUpdate,
    tagFriendAdd,
    getWeChatFriendSopFlow as getWeChatFriendSopFlowApi,
    getWeChatFriendSopPush as getWeChatFriendSopPushApi,
    deleteWeChatFriendSopPush as deleteWeChatFriendSopPushApi,
} from "@/api/person_wechat";
import { HandleEventEnum } from "../_enums";
import { ref, reactive, watch, shallowRef } from "vue";

/**
 * 处理微信消息各类事件
 */

// 类型定义
interface Emoji {
    name: string;
    src: string;
}

interface WechatItem {
    device_code: string;
    wechat_id: string;
    Convers?: ConversationItem[];
    [key: string]: any;
}

interface ConversationItem {
    [key: string]: any;
}

interface FriendItem {
    UserName: string;
    [key: string]: any;
}

interface FriendInfoItem {
    friend_id?: string;
    avatar?: string;
    phone?: string;
    remark?: string;
    nickname?: string;
    source?: string;
    birth_date?: string;
    contact_address?: string;
    [key: string]: any;
}

interface UserInfoForm {
    remark: string;
    phone: string;
    birth_date: string;
    contact_address: string;
    [key: string]: any;
}

interface MessageItem {
    msgId?: string;
    msgSvrId?: string;
    file?: any;
    taskId?: string;
    sttLoading?: boolean;
    showStt?: boolean;
    [key: string]: any;
}

interface TagItem {
    id: number;
    [key: string]: any;
}

type HandleEvent = "action";
type HandleEventCallback<T = any> = (data: T) => void;

interface UseHandleProps {}

interface WechatAiParams {
    [key: string]: any;
}

interface FriendTagParams {
    [key: string]: any;
}

// 当前动作
const actionType = ref<HandleEventEnum | null>(null);
// 微信列表
const wechatLists = ref<WechatItem[]>([]);
// 当前微信信息
const currentWechat = ref<WechatItem>({} as WechatItem);
// 当前好友
const currentFriend = ref<FriendItem>({} as FriendItem);
// 会话列表
const conversationList = ref<ConversationItem[]>([]);
// 好友列表
const friendList = ref<FriendItem[]>([]);
// 好友信息
const friendInfo = ref<FriendInfoItem>({} as FriendInfoItem);
// 历史消息列表
const historyMsgList = ref<MessageItem[]>([]);
// 聊天窗口引用 - 使用 shallowRef 优化性能，因为这是一个 DOM 引用
const chattingRef = shallowRef<any>(null);
// 是否禁用滚动
const disabledScroll = ref<boolean>(false);
// 新消息数量
const newMsg = ref<number>(0);
// 微信 AI 配置 - 使用 Record 类型明确键值对类型
const wechatAiInfo = ref<Record<string, any>>({});
// 用户信息锁定状态
const userInfoIsLocked = ref<boolean>(false);
// 用户信息
const userInfoForm = reactive<UserInfoForm>({
    remark: "", // 备注
    phone: "", // 手机号
    birth_date: "", // 出生日期
    contact_address: "", // 联系地址
});
// 回复策略信息
const replyStrategyInfo = ref<Record<string, any>>({});
// 表情列表
const emojiGlob = import.meta.glob("../_assets/emoji/*.gif", {
    eager: true,
});
const emojis: Emoji[] = Object.entries(emojiGlob).map(([key, module]) => ({
    name: key.split("/").pop()?.split(".")[0] || "",
    src: (module as { default: string }).default,
}));

// 微信标签
const wechatTagLists = ref<TagItem[]>([]);

// 微信好友标签
const friendTagLists = ref<TagItem[]>([]);

// 微信好友sop流程
const friendSopFlow = ref<Record<string, any>>({});

// 微信好友sop推送记录
const friendSopPush = ref<Record<string, any>>({});

// 事件回调存储
const eventHandlers = new Map<HandleEvent, HandleEventCallback>();

/**
 * 微信消息处理逻辑
 * @param props - 可选的配置参数
 * @returns 包含各种处理函数和状态的对象
 */
export default function useHandle(props?: UseHandleProps) {
    /**
     * 处理图片预览
     * @param item - 图片消息项
     */
    const previewImage = (item: MessageItem) => {
        actionType.value = HandleEventEnum.PreviewImage;
        triggerHandleEvent("action", {
            type: HandleEventEnum.PreviewImage,
            data: item,
        });
    };

    /**
     * 处理视频预览
     * @param item - 视频消息项
     */
    const previewVideo = (item: MessageItem) => {
        if (!item) return;

        actionType.value = HandleEventEnum.PreviewVideo;
        triggerHandleEvent("action", {
            ...item,
            type: HandleEventEnum.PreviewVideo,
        });
    };

    /**
     * 选择表情
     * @param emoji - 表情内容
     * @param key - 表情键值
     */
    const chooseEmoji = (emoji: string, key: string) => {
        if (!emoji || !key) return;

        actionType.value = HandleEventEnum.ChooseEmoji;
        triggerHandleEvent("action", {
            type: HandleEventEnum.ChooseEmoji,
            emoji,
            key,
        });
    };

    /**
     * 下载文件
     * @param file - 文件消息项
     */
    const downloadFile = (file: MessageItem) => {
        if (!file || !file.msgId || !file.msgSvrId) return;

        actionType.value = HandleEventEnum.DownloadFile;
        triggerHandleEvent("action", {
            type: HandleEventEnum.DownloadFile,
            msgId: file.msgId,
            msgSvrId: file.msgSvrId,
        });
    };

    /**
     * 语音转文字
     * @param data - 语音消息项
     */
    const voiceToText = (data: MessageItem) => {
        if (!data || !data.msgSvrId) return;

        actionType.value = HandleEventEnum.VoiceToText;
        const taskId = `${Date.now()}`;

        // 更新消息列表中对应的语音消息状态
        historyMsgList.value.forEach((item) => {
            if (item.msgSvrId === data.msgSvrId) {
                item.taskId = taskId;
                item.sttLoading = true;
                item.showStt = true;
            }
        });

        triggerHandleEvent("action", {
            type: HandleEventEnum.VoiceToText,
            messageId: data.msgSvrId,
            taskId,
        });
    };
    /**
     * 获取微信 AI 信息
     * @param wechat_id - 微信ID
     */
    const getWeChatAiInfo = async (wechat_id: string): Promise<void> => {
        if (!wechat_id) return;

        try {
            const data = await getWeChatAi({ wechat_id });
            wechatAiInfo.value[wechat_id] = data;
        } catch (error) {
            console.error("获取微信AI信息失败:", error);
            // 可以在这里添加错误处理逻辑，如显示错误提示等
        }
    };

    /**
     * 更新微信 AI 信息
     * @param params - 更新参数
     */
    const updateWeChatAiInfo = async (params: WechatAiParams): Promise<void> => {
        if (!currentWechat.value?.wechat_id) return;

        try {
            await saveWeChatAi({
                wechat_id: currentWechat.value.wechat_id,
                ...params,
            });
            await getWeChatAiInfo(currentWechat.value.wechat_id);
        } catch (error) {
            console.error("更新微信AI信息失败:", error);
            // 可以在这里添加错误处理逻辑，如显示错误提示等
        }
    };

    /**
     * 获取好友信息
     */
    const getFriendInfo = async (): Promise<void> => {
        if (!currentWechat.value?.wechat_id || !currentFriend.value?.UserName) return;

        try {
            const data = await getWeChatFriend({
                wechat_id: currentWechat.value.wechat_id,
                friend_id: currentFriend.value.UserName,
            });

            // 合并数据，保留现有值或使用新获取的值
            friendInfo.value = {
                ...friendInfo.value,
                ...data,
                friend_id: friendInfo.value.friend_id || data.friend_id,
                avatar: friendInfo.value.avatar || data.avatar,
                phone: friendInfo.value.phone || data.phone,
                remark: friendInfo.value.remark || data.remark,
                nickname: friendInfo.value.nickname || data.nickname,
                source: friendInfo.value.source || data.source,
                birth_date: friendInfo.value.birth_date || data.birth_date,
                contact_address: friendInfo.value.contact_address || data.contact_address,
            };
        } catch (error) {
            console.error("获取好友信息失败:", error);
            // 可以在这里添加错误处理逻辑，如显示错误提示等
        }
    };

    /**
     * 更新用户信息
     * @param data - 更新的用户信息
     */
    const updateUserInfo = async (data?: Record<string, any>): Promise<void> => {
        if (!currentWechat.value?.wechat_id || !currentFriend.value?.UserName) return;

        userInfoIsLocked.value = true;
        try {
            await updateWeChatFriend({
                ...data,
                wechat_id: currentWechat.value.wechat_id,
                friend_id: currentFriend.value.UserName,
            });
            await getFriendInfo();
        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : "修改失败，请稍后再试";
            if (typeof feedback !== "undefined" && feedback.msgError) {
                feedback.msgError(errorMessage);
            } else {
                console.error("更新用户信息失败:", errorMessage);
            }
        } finally {
            userInfoIsLocked.value = false;
        }
    };

    /**
     * 添加好友
     * @param params - 添加好友的参数
     * @returns 添加结果的Promise
     */
    const addFriend = <T = any>(params: Record<string, any>): Promise<T> => {
        return new Promise<T>((resolve, reject) => {
            addWeChatFriend(params)
                .then((res: T) => {
                    resolve(res);
                })
                .catch((err: Error) => {
                    console.error("添加好友失败:", err);
                    reject(err);
                });
        });
    };

    /**
     * 删除好友
     * @param data - 删除好友的参数
     */
    const deleteFriend = async (data: Record<string, any>): Promise<void> => {
        if (!data) return;

        try {
            await deleteWeChatFriend(data);
        } catch (error) {
            console.error("删除好友失败:", error);
            // 可以在这里添加错误处理逻辑
        }
    };

    /**
     * 获取回复策略信息
     */
    const getReplyStrategyInfo = async (): Promise<void> => {
        try {
            const data = await getRobotReplyStrategy();
            replyStrategyInfo.value = data;
        } catch (error) {
            console.error("获取回复策略信息失败:", error);
            // 可以在这里添加错误处理逻辑
        }
    };

    /**
     * 获取微信好友标签
     */
    const getFriendTagDetail = async (): Promise<void> => {
        if (!currentWechat.value?.wechat_id || !currentFriend.value?.UserName) return;

        try {
            const data = await friendTagDetail({
                wechat_id: currentWechat.value.wechat_id,
                friend_id: currentFriend.value.UserName,
            });
            friendTagLists.value = data;
        } catch (error) {
            console.error("获取微信好友标签失败:", error);
            // 可以在这里添加错误处理逻辑
        }
    };

    /**
     * 删除微信好友标签
     * @param id - 标签ID
     */
    const deleteFriendTag = async (id: number): Promise<void> => {
        if (!id || !currentWechat.value?.wechat_id || !currentFriend.value?.UserName) return;

        try {
            await friendTagDelete({
                tag_id: id,
                wechat_id: currentWechat.value.wechat_id,
                friend_id: currentFriend.value.UserName,
            });
            await getFriendTagDetail();
        } catch (error) {
            console.error("删除微信好友标签失败:", error);
            // 可以在这里添加错误处理逻辑
        }
    };

    /**
     * 更新微信好友标签
     * @param params - 更新标签的参数
     */
    const updateFriendTag = async (params: FriendTagParams): Promise<void> => {
        if (!params || !currentWechat.value?.wechat_id || !currentFriend.value?.UserName) return;

        try {
            await friendTagUpdate({
                wechat_id: currentWechat.value.wechat_id,
                friend_id: currentFriend.value.UserName,
                ...params,
            });
            await getFriendTagDetail();
        } catch (error) {
            console.error("更新微信好友标签失败:", error);
            // 可以在这里添加错误处理逻辑
        }
    };

    /**
     * 获取微信标签列表
     */
    const getWechatTagLists = async (): Promise<void> => {
        if (!currentWechat.value?.wechat_id) return;

        try {
            const { lists } = await tagListsV2({
                wechat_id: currentWechat.value.wechat_id,
            });
            wechatTagLists.value = lists.filter((item) => item.id !== 0);
        } catch (error) {
            console.error("获取微信标签列表失败:", error);
            // 可以在这里添加错误处理逻辑
        }
    };

    /**
     * 添加微信好友标签
     * @param params - 添加标签的参数
     */
    const addFriendTag = async (params: FriendTagParams): Promise<void> => {
        if (!params || !currentWechat.value?.wechat_id || !currentFriend.value?.UserName) return;

        try {
            await tagFriendAdd({
                wechat_id: currentWechat.value.wechat_id,
                friend_ids: [currentFriend.value.UserName],
                ...params,
            });
            await getFriendTagDetail();
        } catch (error) {
            console.error("添加微信好友标签失败:", error);
            // 可以在这里添加错误处理逻辑
        }
    };

    /**
     * 获取微信好友SOP流程
     */
    const getWeChatFriendSopFlow = async (): Promise<void> => {
        if (!currentWechat.value?.wechat_id || !currentFriend.value?.UserName) return;

        try {
            const data = await getWeChatFriendSopFlowApi({
                wechat_id: currentWechat.value.wechat_id,
                friend_id: currentFriend.value.UserName,
            });
            friendSopFlow.value = data;
        } catch (error) {
            console.error("获取微信好友SOP流程失败:", error);
            // 可以在这里添加错误处理逻辑
        }
    };

    /**
     * 获取好友sop推送记录
     */
    const getWeChatFriendSopPush = async (params: any): Promise<void> => {
        if (!currentWechat.value?.wechat_id || !currentFriend.value?.UserName) return;

        try {
            const data = await getWeChatFriendSopPushApi({
                wechat_id: currentWechat.value.wechat_id,
                friend_id: currentFriend.value.UserName,
                ...params,
            });
            friendSopPush.value = data;
        } catch (error) {
            console.error("获取好友sop推送记录失败:", error);
        }
    };

    /**
     * 删除好友sop推送记录
     */
    const deleteWeChatFriendSopPush = async (params: any): Promise<void> => {
        if (!params) return;
        return new Promise<void>((resolve, reject) => {
            deleteWeChatFriendSopPushApi(params)
                .then(() => {
                    resolve();
                })
                .catch((error) => {
                    reject(error);
                });
        });
    };

    /**
     * 事件触发器 - 触发已注册的事件回调
     * @param event - 事件类型
     * @param data - 事件数据
     */
    const triggerHandleEvent = <D = unknown>(event: HandleEvent, data?: D): void => {
        const handler = eventHandlers.get(event);
        if (handler) handler(data!);
    };

    /**
     * 注册事件处理回调
     * @param event - 事件类型
     * @param callback - 回调函数
     */
    const onHandleEvent = <D = unknown>(event: HandleEvent, callback: HandleEventCallback<D>): void => {
        if (!event || !callback) return;
        eventHandlers.set(event, callback as HandleEventCallback);
    };

    // 监听微信列表和当前微信变化，更新会话列表
    watch(
        [wechatLists, currentWechat],
        () => {
            if (wechatLists.value.length === 0) return;

            // 根据当前微信的 device_code 查找对应的会话列表
            const currentWechatItem = wechatLists.value.find(
                (item) => item.device_code === currentWechat.value?.device_code
            );

            conversationList.value = currentWechatItem?.Convers || [];
        },
        {
            deep: true,
        }
    );

    // 返回包含状态和方法的对象
    return {
        // 状态
        actionType,
        wechatLists,
        conversationList,
        currentWechat,
        currentFriend,
        friendList,
        friendInfo,
        emojis,
        historyMsgList,
        chattingRef,
        disabledScroll,
        newMsg,
        wechatAiInfo,
        userInfoForm,
        userInfoIsLocked,
        replyStrategyInfo,
        friendTagLists,
        wechatTagLists,
        friendSopFlow,
        friendSopPush,
        // 消息处理方法
        previewVideo,
        previewImage,
        chooseEmoji,
        downloadFile,
        voiceToText,

        // 微信 AI 相关方法
        getWeChatAiInfo,
        updateWeChatAiInfo,

        // 用户信息相关方法
        updateUserInfo,
        getFriendInfo,

        // 好友管理方法
        addFriend,
        deleteFriend,

        // 回复策略方法
        getReplyStrategyInfo,

        // 标签相关方法
        getFriendTagDetail,
        deleteFriendTag,
        updateFriendTag,
        getWechatTagLists,
        addFriendTag,

        // 事件处理方法
        onHandleEvent,
        triggerHandleEvent,

        // SOP 流程方法
        getWeChatFriendSopFlow,

        // 好友sop推送记录方法
        getWeChatFriendSopPush,
        deleteWeChatFriendSopPush,
    };
}
