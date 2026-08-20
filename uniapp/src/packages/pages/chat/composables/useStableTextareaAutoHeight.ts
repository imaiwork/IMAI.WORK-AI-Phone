import type { Ref } from "vue";

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
