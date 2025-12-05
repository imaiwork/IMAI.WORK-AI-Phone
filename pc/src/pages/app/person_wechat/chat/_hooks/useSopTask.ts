import { sopGreetInfo, messageGreet } from "@/api/person_wechat";
import useHandle from "../../_hooks/useHandle";

/**
 * 处理SOP任务
 *
 */

interface SopTaskParams {
    WeChatId: string;
    FriendId: string;
    NickName: string;
    Memo: string;
}

const { wechatAiInfo } = useHandle();

export default function useSopTask() {
    // 打招呼信息
    const greetInfo = ref<Record<string, any>>({});
    // 打招呼任务
    const greetTask = ref<Record<string, NodeJS.Timeout | null>>({});

    // 1. 获取SOP信息
    const getSopGreetInfo = async () => {
        const data = await sopGreetInfo();
        greetInfo.value = data;
    };

    // 2. 创建一个任务，
    const createStopTask = async (result: SopTaskParams) => {
        const { WeChatId, FriendId, NickName, Memo } = result;
        const key = `${WeChatId}${FriendId}`;

        if (greetTask.value[key]) {
            return;
        }

        // 判断是否需要创建任务, 前置条件是open_ai == 1 && is_enable == 1
        const { open_ai } = wechatAiInfo.value[WeChatId] || {};
        const { is_enable, interval_time, greet_after_ai_enable } = greetInfo.value;

        if (open_ai == 1 && greet_after_ai_enable == 1 && is_enable == 1) {
            greetTask.value[key] = setTimeout(() => {
                messageGreet({
                    wechat_id: WeChatId,
                    friend_id: FriendId,
                    friend_remark: Memo,
                });
            }, interval_time * 1000 * 60);
        }
    };

    // 3. 删除一个任务
    const deleteStopTask = (friendId: string) => {
        const { friend_greet_is_reply } = greetInfo.value;
        if (greetTask.value[friendId] && friend_greet_is_reply != 1) {
            clearTimeout(greetTask.value[friendId]);
        }
    };

    // 4. 删除所有任务
    const deleteAllStopTask = () => {
        Object.values(greetTask.value).forEach((timeout) => {
            clearTimeout(timeout);
        });
    };

    return {
        getSopGreetInfo,
        createStopTask,
        deleteStopTask,
        deleteAllStopTask,
    };
}
