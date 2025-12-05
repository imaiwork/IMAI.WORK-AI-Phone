const messageList = ref<any[]>([]);

export default function useMessage() {
    const getMessageList = async () => {};

    return {
        messageList,
        getMessageList,
    };
}
