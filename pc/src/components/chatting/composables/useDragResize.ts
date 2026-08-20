import { useEventListener } from "@vueuse/core";

export const INPUT_MIN_HEIGHT = 50;
export const INPUT_MAX_HEIGHT = 400;
export const INPUT_HEIGHT = 150;

/** 输入框底部与视口边缘的安全间距，避免工具栏被裁切 */
const VIEWPORT_BOTTOM_GAP = 24;
/** 拉高时上方对话区至少保留的可见高度 */
const MIN_CONTENT_ABOVE = 100;

export function useDragResize() {
    const inputAreaHeight = ref(INPUT_HEIGHT);
    const isDragging = ref(false);
    const dragHandleRef = ref<HTMLDivElement | null>(null);

    let dragStartY = 0;
    let dragStartHeight = 0;

    /**
     * 按视口动态计算 textarea 最大高度：
     * 允许向上侵占对话区，但保证整块输入区（含模式 Tab / 工具栏）不超出视口。
     */
    const getMaxHeight = () => {
        const handle = dragHandleRef.value;
        if (!handle) return INPUT_MAX_HEIGHT;
        const wrapper = handle.closest(".input-box-wrapper") as HTMLElement | null;
        if (!wrapper) return INPUT_MAX_HEIGHT;

        // 欢迎页模式 Tab 在 wrapper 外的 .input-box 上，需一并计入占用高度
        const outer = (wrapper.closest(".input-box") as HTMLElement | null) || wrapper;
        const outerChrome = Math.max(0, outer.offsetHeight - inputAreaHeight.value);

        const bound =
            (wrapper.closest(".welcome-hero") as HTMLElement | null) ||
            (wrapper.closest(".chatting") as HTMLElement | null);
        const ceiling = (bound?.getBoundingClientRect().top ?? 0) + MIN_CONTENT_ABOVE;
        const available = window.innerHeight - VIEWPORT_BOTTOM_GAP - ceiling - outerChrome;
        return Math.min(INPUT_MAX_HEIGHT, Math.max(INPUT_MIN_HEIGHT, available));
    };

    const clampHeight = (h: number) => Math.min(getMaxHeight(), Math.max(INPUT_MIN_HEIGHT, h));

    const startDrag = (e: MouseEvent) => {
        isDragging.value = true;
        dragStartY = e.clientY;
        dragStartHeight = inputAreaHeight.value;
        e.preventDefault();
    };

    const startTouchDrag = (e: TouchEvent) => {
        isDragging.value = true;
        dragStartY = e.touches[0].clientY;
        dragStartHeight = inputAreaHeight.value;
    };

    useEventListener(document, "mousemove", (e: MouseEvent) => {
        if (!isDragging.value) return;
        inputAreaHeight.value = clampHeight(dragStartHeight + (dragStartY - e.clientY));
    });

    useEventListener(document, "mouseup", () => {
        if (isDragging.value) isDragging.value = false;
    });

    useEventListener(
        document,
        "touchmove",
        (e: TouchEvent) => {
            if (!isDragging.value) return;
            inputAreaHeight.value = clampHeight(dragStartHeight + (dragStartY - e.touches[0].clientY));
        },
        { passive: true }
    );

    useEventListener(document, "touchend", () => {
        if (isDragging.value) isDragging.value = false;
    });

    // 窗口缩放时若当前高度超出可用空间，自动回缩
    useEventListener(window, "resize", () => {
        const max = getMaxHeight();
        if (inputAreaHeight.value > max) {
            inputAreaHeight.value = max;
        }
    });

    const resetHeight = () => {
        inputAreaHeight.value = INPUT_HEIGHT;
    };

    return {
        inputAreaHeight,
        isDragging,
        dragHandleRef,
        startDrag,
        startTouchDrag,
        resetHeight,
    };
}
