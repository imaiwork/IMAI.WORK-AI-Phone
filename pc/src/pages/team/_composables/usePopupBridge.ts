/**
 * 把领域 hook 里的 showXxx 布尔状态,桥接到 components/popup 的命令式 open/close。
 * - 保持 showXxx 为唯一可信状态(触发处只需 showXxx = true)
 * - popup 自身关闭(点遮罩/关闭按钮)时通过 onPopupClose 回写 showXxx = false
 */
export function usePopupBridge(visible: Ref<boolean>) {
    const popupRef = ref<any>(null);

    watch(visible, (v) => {
        if (v) popupRef.value?.open();
        else popupRef.value?.close();
    });

    const onPopupClose = () => {
        if (visible.value) visible.value = false;
    };

    return { popupRef, onPopupClose };
}
