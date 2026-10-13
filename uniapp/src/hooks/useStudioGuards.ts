/** 对话 / 工作室底栏的点击误触保护，键盘顶起计算见 useKeyboardSpacer */

/** 发送那次点击会被小程序补派发到上传入口，且要等工具栏结束折叠（480ms）才生效 */
const UPLOAD_MISFIRE_WINDOW_MS = 800;

/** 发送后短时忽略上传入口的点击，避免同一次点击被补派发成选图/选文件 */
export function useUploadMisfireGuard() {
    let lastSendAt = 0;

    const markSent = () => {
        lastSendAt = Date.now();
    };

    const isUploadMisfire = () => Date.now() - lastSendAt < UPLOAD_MISFIRE_WINDOW_MS;

    return { markSent, isUploadMisfire };
}

/** 切换进工作室后短时锁定发送，避免点穿/残留点击误触发 */
export function useStudioSendGuard(lockMs = 420) {
    const locked = ref(true);
    let timer: ReturnType<typeof setTimeout> | null = null;

    onMounted(() => {
        timer = setTimeout(() => {
            locked.value = false;
            timer = null;
        }, lockMs);
    });

    onUnmounted(() => {
        if (timer) clearTimeout(timer);
    });

    const guardSend = (fn: () => void) => {
        if (locked.value) return;
        fn();
    };

    const { markSent, isUploadMisfire } = useUploadMisfireGuard();

    return { sendLocked: locked, guardSend, markSent, isUploadMisfire };
}
