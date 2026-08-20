import type { ComputedRef, Ref } from "vue";

const TOOLBAR_REVEAL_DELAY_MS = 480;

/**
 * 键盘收起后延迟恢复工具栏可点区域，避免真机点穿打开挂载/模型等弹层。
 *
 * 注意：不要用全屏 fixed 遮罩吞点击（会挡住输入框）；
 * 也不要用 v-if 卸载工具栏（会丢横向滚动、输入区跳动）。
 * 由调用方用 showToolbar 做高度折叠 + pointer-events。
 */
export function useToolbarRevealGuard(keyboardOpen: Ref<boolean> | ComputedRef<boolean>) {
    const showToolbar = ref(true);
    let revealTimer: ReturnType<typeof setTimeout> | null = null;

    const clearTimers = () => {
        if (revealTimer) {
            clearTimeout(revealTimer);
            revealTimer = null;
        }
    };

    watch(
        keyboardOpen,
        (open, wasOpen) => {
            if (open) {
                clearTimers();
                showToolbar.value = false;
                return;
            }
            if (wasOpen) {
                clearTimers();
                showToolbar.value = false;
                revealTimer = setTimeout(() => {
                    showToolbar.value = true;
                    revealTimer = null;
                }, TOOLBAR_REVEAL_DELAY_MS);
                return;
            }
            showToolbar.value = true;
        },
        { flush: "sync" },
    );

    onUnmounted(clearTimers);

    const guardToolbarAction = (fn: () => void) => {
        if (!showToolbar.value) return;
        fn();
    };

    return {
        showToolbar,
        guardToolbarAction,
    };
}
