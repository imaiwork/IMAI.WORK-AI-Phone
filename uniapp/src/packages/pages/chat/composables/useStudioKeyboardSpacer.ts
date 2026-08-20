import type { Ref } from "vue";
import useKeyboardHeight from "@/hooks/useKeyboardHeight";
import { useToolbarRevealGuard } from "./useToolbarRevealGuard";

type TabbarVisibleSource = Ref<boolean> | (() => boolean) | boolean;

/**
 * 工作室底栏键盘顶起：与 workbench-chat-scroll / chat-scroll-view 同一套计算。
 * spacerHeight = keyboardRpx - bottomOffset
 * bottomOffset = tabbarVisible ? tabbarHeight + 70 : 70
 *
 * 工具栏显隐 / 点空白收键盘：跟 keyboardOpen（系统键盘高度），不跟 spacerHeight。
 */
export function useStudioKeyboardSpacer(tabbarVisible: TabbarVisibleSource = false) {
    const { dynamicHeight, hideKeyboard: zeroKeyboardHeight } = useKeyboardHeight();
    const { safeAreaInsets, windowWidth, platform } = uni.getSystemInfoSync();

    const isTabbarVisible = computed(() => {
        if (typeof tabbarVisible === "function") return !!tabbarVisible();
        return !!(isRef(tabbarVisible) ? tabbarVisible.value : tabbarVisible);
    });

    const tabbarHeight = computed(() => {
        const fixedHeight = platform === "android" ? 95 : 125;
        return fixedHeight + (safeAreaInsets?.bottom ?? 0);
    });

    const bottomOffset = computed(() => {
        const otherHeight = 70;
        return isTabbarVisible.value ? tabbarHeight.value + otherHeight : otherHeight;
    });

    const keyboardOpen = computed(() => dynamicHeight.value > 0);

    const spacerHeight = computed(() => {
        if (!keyboardOpen.value) return 0;
        return Math.max(0, (dynamicHeight.value * 750) / windowWidth - bottomOffset.value);
    });

    const { showToolbar, guardToolbarAction } = useToolbarRevealGuard(keyboardOpen);

    /** 点空白收键盘：只调系统收起，高度交给 onKeyboardHeightChange，避免 spacer 先塌陷 */
    const dismissKeyboard = () => {
        uni.hideKeyboard();
    };

    /** 切模式等场景：立刻清高度并收起 */
    const hideKeyboard = () => {
        zeroKeyboardHeight();
        uni.hideKeyboard();
    };

    return {
        keyboardOpen,
        spacerHeight,
        showToolbar,
        guardToolbarAction,
        dismissKeyboard,
        hideKeyboard,
    };
}

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
