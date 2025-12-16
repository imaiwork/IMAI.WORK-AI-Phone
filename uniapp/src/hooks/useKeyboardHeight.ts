import { ref, onMounted, onBeforeUnmount } from "vue";

const dynamicHeight = ref(0); // 默认高度

export default function useKeyboardHeight() {
    const isH5 = uni.getSystemInfoSync().uniPlatform === "web"; // 判断是否为 H5
    // 监听键盘高度变化并更新元素高度
    const updateHeight = (res: { height: number }) => {
        dynamicHeight.value = res.height;
    };

    const onFocus = () => {
        if (!isH5) {
            // 小程序环境使用 uni.onKeyboardHeightChange
            uni.onKeyboardHeightChange(updateHeight);
        } else {
            // H5 环境使用 window.resize 监听
            window.addEventListener("resize", handleResize);
        }
    };

    const onBlur = () => {
        dynamicHeight.value = 0; // 恢复默认高度
        if (!isH5) {
            uni.offKeyboardHeightChange(updateHeight); // 停止监听键盘高度
        } else {
            window.removeEventListener("resize", handleResize); // H5 解除 resize 事件监听
        }
    };

    const handleResize = () => {
        // 获取 H5 页面可视区高度变化时的变化情况
        const newHeight = document.documentElement.clientHeight || window.innerHeight;
        dynamicHeight.value = newHeight + 50; // 设置动态高度
    };

    // 隐藏键盘
    const hideKeyboard = () => {
        dynamicHeight.value = 0;
        if (!isH5) {
            console.log("隐藏键盘");
            uni.hideKeyboard();
        }
    };

    // 移除键盘监听
    const removeKeyboardListener = () => {
        dynamicHeight.value = 0;
        if (!isH5) {
            console.log("移除键盘监听");
            // 隐藏键盘
            uni.hideKeyboard();
            uni.offKeyboardHeightChange(updateHeight); // 移除小程序键盘监听
        } else {
            window.removeEventListener("resize", handleResize); // 移除 H5 的 resize 监听
        }
    };

    // 销毁前清理监听器
    onMounted(() => {
        if (!isH5) {
            uni.onKeyboardHeightChange(updateHeight); // 注册小程序键盘监听
        }
    });

    onBeforeUnmount(() => {
        removeKeyboardListener();
    });

    onUnmounted(() => {
        removeKeyboardListener();
    });

    onUnload(() => {
        removeKeyboardListener();
    });

    return {
        dynamicHeight,
        onFocus,
        onBlur,
        hideKeyboard,
        removeKeyboardListener,
    };
}
