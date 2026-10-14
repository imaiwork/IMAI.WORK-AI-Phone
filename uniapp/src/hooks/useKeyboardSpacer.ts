import type { Ref } from "vue";
import useKeyboardHeight from "@/hooks/useKeyboardHeight";
import { useToolbarRevealGuard } from "./useToolbarRevealGuard";

/** 常量、ref、getter 都能传，方便调用方按 props 动态给值 */
type Source<T> = T | Ref<T> | (() => T);

/** 设计稿宽度，px → rpx 的换算基准 */
const DESIGN_WIDTH_RPX = 750;
/** 输入区下方固定占位（免责声明那一条） */
const INPUT_BOTTOM_GAP_RPX = 70;
/** tabbar 本体高度：安卓和其它端视觉高度不同 */
const TABBAR_HEIGHT_ANDROID_RPX = 95;
const TABBAR_HEIGHT_DEFAULT_RPX = 125;

interface KeyboardSpacerOptions {
    /** 底部 tabbar 是否可见：可见时键盘顶起要多扣掉一个 tabbar 的高度 */
    tabbarVisible?: Source<boolean>;
    /** 输入区之外还要扣掉的高度（rpx），如客服模式底部多一截 */
    extraOffset?: Source<number>;
}

const readSource = <T>(source: Source<T> | undefined, fallback: T): T => {
    if (source === undefined) return fallback;
    if (typeof source === "function") return (source as () => T)();
    return isRef(source) ? (source.value as T) : (source as T);
};

/**
 * 对话页 / 工作室底栏统一的键盘顶起计算，chat-scroll-view、workbench-chat-scroll
 * 和四个 studio panel 共用这一份，别再各自抄一遍。
 *
 * spacerHeight = 键盘高度(rpx) - bottomOffset
 * bottomOffset = (tabbar 可见 ? tabbar 高度 : 0) + 输入区下方占位 + extraOffset
 *
 * 工具栏显隐、点空白收键盘都跟 keyboardOpen（系统键盘高度），不跟 spacerHeight。
 * 注意 safeAreaInsets.bottom 是 px，历史上就直接加进 rpx 的 tabbar 高度里，
 * 这个偏差是按真机调出来的，合并时原样保留。
 */
export function useKeyboardSpacer(options: KeyboardSpacerOptions = {}) {
    const { dynamicHeight, hideKeyboard } = useKeyboardHeight();
    const { safeAreaInsets, windowWidth, platform } = uni.getSystemInfoSync();

    const tabbarHeight = computed(() => {
        const fixedHeight =
            platform === "android" ? TABBAR_HEIGHT_ANDROID_RPX : TABBAR_HEIGHT_DEFAULT_RPX;
        return fixedHeight + (safeAreaInsets?.bottom ?? 0);
    });

    const bottomOffset = computed(() => {
        const otherHeight = INPUT_BOTTOM_GAP_RPX + readSource(options.extraOffset, 0);
        return readSource(options.tabbarVisible, false)
            ? tabbarHeight.value + otherHeight
            : otherHeight;
    });

    const keyboardOpen = computed(() => dynamicHeight.value > 0);

    const spacerHeight = computed(() => {
        if (!keyboardOpen.value) return 0;
        return Math.max(
            0,
            (dynamicHeight.value * DESIGN_WIDTH_RPX) / windowWidth - bottomOffset.value,
        );
    });

    const { showToolbar, guardToolbarAction } = useToolbarRevealGuard(keyboardOpen);

    /** 点空白收键盘：只调系统收起，高度交给 onKeyboardHeightChange，避免 spacer 先塌陷 */
    const dismissKeyboard = () => {
        uni.hideKeyboard();
    };

    return {
        keyboardOpen,
        spacerHeight,
        showToolbar,
        guardToolbarAction,
        dismissKeyboard,
        /** 切模式等场景：立刻清高度并收起 */
        hideKeyboard,
    };
}
