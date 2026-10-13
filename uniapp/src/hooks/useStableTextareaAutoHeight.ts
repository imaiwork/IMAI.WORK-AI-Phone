import type { Ref } from "vue";
import { cancelKeyboardClosing } from "@/hooks/useKeyboardHeight";

/**
 * 稳定小程序 textarea 首屏高度：
 * 空态关闭 auto-height 并固定单行高，避免长 placeholder 在宽度未稳定时先撑高再回落。
 */
export function useStableTextareaAutoHeight(model: Ref<string>, lineHeightRpx = 40) {
    const focused = ref(false);

    const autoHeight = computed(() => focused.value || !!String(model.value || "").length);

    const textareaStyle = computed(() =>
        autoHeight.value
            ? {}
            : {
                  height: `${lineHeightRpx}rpx`,
              },
    );

    const onTextareaFocus = () => {
        // 收起键盘后 400ms 内再点输入框：先解除保护窗口，否则这次弹起的高度回调会被丢掉
        cancelKeyboardClosing();
        focused.value = true;
    };

    const onTextareaBlur = () => {
        focused.value = false;
    };

    return {
        autoHeight,
        textareaStyle,
        onTextareaFocus,
        onTextareaBlur,
    };
}
