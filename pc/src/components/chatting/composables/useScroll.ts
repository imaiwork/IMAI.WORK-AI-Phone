/**
 * 模板 ref 一律由组件声明后传进来，不要在 composable 内部声明再 return：
 * <script setup> 的字符串 ref 只认 setup 作用域里的同名变量，
 * 组件一旦忘记解构就会静默失效（TS 和 eslint 都抓不到）。改成入参后，漏传是编译期错误。
 */
export function useScroll(
    scrollContainerRef: Ref<HTMLDivElement | null>,
    emit: (event: string, ...args: any[]) => void,
) {
    const previousScrollTop = ref(0);
    const disabledScroll = ref(false);
    const showBackToBottom = ref(false);

    const BACK_TO_BOTTOM_THRESHOLD = 200;

    const toScrollHeight = () => {
        if (!scrollContainerRef.value) return 0;
        return scrollContainerRef.value.scrollHeight - scrollContainerRef.value.clientHeight;
    };

    const checkNearBottom = (scrollTop: number) => {
        if (!scrollContainerRef.value) return;
        const maxScrollTop = scrollContainerRef.value.scrollHeight - scrollContainerRef.value.clientHeight;
        showBackToBottom.value = maxScrollTop - scrollTop > BACK_TO_BOTTOM_THRESHOLD;
    };

    const scroll = (event: Event) => {
        const target = event.target as HTMLElement;
        const currentScrollTop = target.scrollTop;

        checkNearBottom(currentScrollTop);

        if (currentScrollTop < previousScrollTop.value - 50) {
            disabledScroll.value = true;
        } else if (currentScrollTop >= previousScrollTop.value) {
            disabledScroll.value = false;
        }

        previousScrollTop.value = currentScrollTop;

        if (currentScrollTop === 0) emit("top");
    };

    const resetScroll = () => {
        disabledScroll.value = false;
        previousScrollTop.value = 0;
        showBackToBottom.value = false;
    };

    const scrollToBottom = async (smooth = false) => {
        if (disabledScroll.value || !scrollContainerRef.value) return;
        const scrollH = toScrollHeight();
        await nextTick();
        if (smooth) {
            scrollContainerRef.value.scrollTo({ top: scrollH, behavior: "smooth" });
        } else {
            scrollContainerRef.value.scrollTop = scrollH;
        }
        showBackToBottom.value = false;
    };

    const scrollTo = async (top: number, smooth = true) => {
        if (!scrollContainerRef.value) return;
        await nextTick();
        if (smooth) {
            scrollContainerRef.value.scrollTo({ top, behavior: "smooth" });
        } else {
            scrollContainerRef.value.scrollTop = top;
        }
    };

    const handleBackToBottom = async () => {
        if (!scrollContainerRef.value) return;
        await nextTick();
        await scrollTo(toScrollHeight());
        showBackToBottom.value = false;
        disabledScroll.value = false;
    };

    return {
        disabledScroll,
        showBackToBottom,
        scroll,
        resetScroll,
        scrollToBottom,
        scrollTo,
        handleBackToBottom,
    };
}
