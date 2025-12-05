import useWebSocket, { WebSocketOptions } from "@/composables/useWebSocket";
import { MsgTypeEnum, MsgErrorCodeEnum } from "../_enums";
import { addDevice, addWeChat, updateDevice } from "@/api/person_wechat";

// WebSocket 事件类型
type WeChatWsEvent = "open" | "message" | "close" | "success" | "error" | "heartbeat" | "action";
type WeChatWsEventCallback<T = unknown> = (data: T) => void;

// WebSocket 消息内容类型
interface WeChatWsMessage {
    Code: number;
    Message: string;
    Data: {
        MsgType: MsgTypeEnum;
        Content: {
            DeviceModel: string;
            IsOnline: boolean;
            WeChatNo: string;
            AccessToken: string;
            DeviceId: string;
            WeChatId: string;
            SdkVersion: string;
            Avatar?: string;
            WeChatNick?: string;
            Status?: string;
        };
    };
}

// 事件回调存储
const eventHandlers = new Map<WeChatWsEvent, WeChatWsEventCallback>();

export default function useWeChatWs(options: WebSocketOptions = {}) {
    const wssUrl = `wss://${location.host}/wechat`;
    const {
        on: wssOn,
        send,
        reconnect,
        isConnected,
    } = useWebSocket(wssUrl, {
        manualHeartbeat: true,
        ...options,
    });

    // 添加设备 loading 状态
    const addDeviceLoading = ref(false);

    // 当前操作类型
    const actionType = ref<MsgTypeEnum>();

    // 事件触发器
    const triggerEvent = <D = unknown>(event: WeChatWsEvent, data?: D) => {
        const handler = eventHandlers.get(event);
        if (handler) handler(data!);
    };

    // 监听 WebSocket 事件
    wssOn("open", () => triggerEvent("open"));
    wssOn("error", (error: any) => triggerEvent("error", error));
    wssOn("close", () => triggerEvent("close"));

    // 处理 WebSocket 消息
    wssOn("message", async (data: WeChatWsMessage) => {
        const { Code, Message, Data } = data || {};
        if (Code === MsgErrorCodeEnum.Success) {
            const { MsgType, Content } = Data || {};
            triggerEvent("message", Data);
            triggerEvent("action", {
                type: actionType.value,
                accessToken: Content.AccessToken,
                deviceId: Content.DeviceId,
                wechatId: Content.WeChatId,
                content: Data.Content,
            });
            // 处理添加设备逻辑
            if (actionType.value === MsgTypeEnum.AddDevice) {
                await handleAddDevice(Content);
                return;
            }

            // 处理设备授权逻辑
            if (MsgType === MsgTypeEnum.Auth && actionType.value === MsgTypeEnum.Auth) {
                await handleAuth(Content);

                return;
            }

            // 处理微信信息逻辑
            if (Content.WeChatId && actionType.value === MsgTypeEnum.WxInfo) {
                await handleWeChatInfo(Content);
                return;
            }
        } else {
            handleError(Message, Code, Data);
        }
    });

    // 处理心跳
    wssOn("heartbeat", () => {
        actionType.value = MsgTypeEnum.Heartbeat;
        send({ MsgType: MsgTypeEnum.Heartbeat, Content: {} });
        triggerEvent("heartbeat");
    });

    // 添加设备逻辑
    const handleAddDevice = async (content: WeChatWsMessage["Data"]["Content"]) => {
        try {
            await addDevice({
                device_code: content.DeviceId,
                device_model: content.DeviceModel,
                sdk_version: content.SdkVersion,
                device_status: content.IsOnline ? 1 : 0,
            });
            send({
                MsgType: MsgTypeEnum.Auth,
                Content: {
                    DeviceId: content.DeviceId,
                },
            });
            actionType.value = MsgTypeEnum.Auth;
        } catch (error) {
            feedback.msgError(`${error}`);
            triggerEvent("error");
        } finally {
            addDeviceLoading.value = false;
        }
    };

    // 设备授权逻辑
    const handleAuth = async (content: WeChatWsMessage["Data"]["Content"]) => {
        send({
            MsgType: MsgTypeEnum.WxInfo,
            Content: {
                DeviceId: content.DeviceId,
                AccessToken: content.AccessToken,
                WeChatId: content.WeChatId,
            },
        });
        actionType.value = MsgTypeEnum.WxInfo;
    };

    // 处理微信信息逻辑
    const handleWeChatInfo = async (content: WeChatWsMessage["Data"]["Content"]) => {
        try {
            await addWeChat({
                device_code: content.DeviceId,
                wechat_id: content.WeChatId,
                wechat_nickname: content.WeChatNick || "",
                wechat_avatar: content.Avatar || "",
                wechat_status: content.Status === "online",
                wechat_no: content.WeChatNo,
            });
            updateDevice({
                device_code: content.DeviceId,
                is_used: true,
            });
            feedback.msgSuccess("设备添加成功");
            triggerEvent("success", { type: "add-device" });
            actionType.value = undefined;
        } catch (error) {
            actionType.value = undefined;
            feedback.msgError(`${error}，请联系站长`);
        }
    };

    // 处理错误逻辑
    const handleError = (message: string, code: number, data: any) => {
        triggerEvent("error", {
            Message: message,
            Code: code,
            MsgType: data.MsgType,
            Content: data.Content,
        });
        addDeviceLoading.value = false;
        actionType.value = undefined;
        if (
            [
                MsgErrorCodeEnum.DataNotFound,
                MsgErrorCodeEnum.DeviceNotFound,
                MsgErrorCodeEnum.DeviceOffline,
                MsgErrorCodeEnum.DeviceExist,
                MsgErrorCodeEnum.DeviceWechatNotFound,
                MsgErrorCodeEnum.SystemError,
            ].includes(code)
        ) {
            feedback.msgError(message);
        }
        // if (code === MsgErrorCodeEnum.DeviceOffline) {
        // 	feedback.msgError("设备离线，请重新登录");
        // } else {
        // 	feedback.msgError(message);
        // }
    };

    // 暴露的方法
    return {
        isConnected,
        addDeviceLoading,
        actionType,
        on: <D = unknown>(event: WeChatWsEvent, callback: WeChatWsEventCallback<D>) => {
            eventHandlers.set(event, callback);
        },
        send,
        reconnect,
    };
}
