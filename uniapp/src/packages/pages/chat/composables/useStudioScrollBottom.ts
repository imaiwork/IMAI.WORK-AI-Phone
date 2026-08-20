/**
 * 工作台独立面板滚底。
 *
 * scroll-view 会把超出内容高度的 scroll-top 夹到底部，所以直接给一个足够大的值即可，
 * 不必用 getRect 测高——真机上每次选择器查询都是一次渲染层往返，图片异步撑高时
 * 重试几次就会明显卡顿。
 */
const BOTTOM_OVERSHOOT = 1000000;

export function useStudioScrollBottom() {
    const scrollTop = ref(0);
    let timers: ReturnType<typeof setTimeout>[] = [];

    const clearTimers = () => {
        timers.forEach((t) => clearTimeout(t));
        timers = [];
    };

    /** scroll-top 必须变化才会重新触发滚动，两个值夹到底部后位置一致 */
    const jumpToBottom = () => {
        scrollTop.value = scrollTop.value === BOTTOM_OVERSHOOT ? BOTTOM_OVERSHOOT - 1 : BOTTOM_OVERSHOOT;
    };

    /** 重试用于兜住图片/卡片异步撑高，立即滚一次后再补几次 */
    const scrollToBottom = (retries: number[] = [120, 360, 800]) => {
        clearTimers();
        jumpToBottom();
        retries.forEach((delay) => {
            timers.push(setTimeout(jumpToBottom, delay));
        });
    };

    onUnmounted(clearTimers);

    return {
        scrollTop,
        scrollToBottom,
    };
}
