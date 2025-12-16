const refreshPayload = ref<any | null>(null);

export function useChatEventBus() {
    const triggerHistoryRefresh = (payload: any) => {
        refreshPayload.value = payload;
    };

    const onHistoryRefresh = (callback: (payload: any) => void) => {
        watch(refreshPayload, (payload) => {
            if (payload) {
                callback(payload);
                refreshPayload.value = null; // Reset after triggering
            }
        });
    };

    return {
        triggerHistoryRefresh,
        onHistoryRefresh,
    };
}
