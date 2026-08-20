const refreshPayload = ref<any | null>(null);
/** 点击侧边「最近会话」时通知首页切回 AI 对话模式 */
const enterChatSessionTick = ref(0);

export function useChatEventBus() {
    const triggerHistoryRefresh = (payload: any) => {
        refreshPayload.value = payload;
    };

    const onHistoryRefresh = (callback: (payload: any) => void) => {
        watch(refreshPayload, (payload) => {
            if (payload) {
                callback(payload);
                refreshPayload.value = null;
            }
        });
    };

    const triggerEnterChatSession = () => {
        enterChatSessionTick.value += 1;
    };

    const onEnterChatSession = (callback: () => void) => {
        watch(enterChatSessionTick, () => {
            callback();
        });
    };

    return {
        triggerHistoryRefresh,
        onHistoryRefresh,
        triggerEnterChatSession,
        onEnterChatSession,
    };
}
