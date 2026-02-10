<template>
    <popup
        ref="popupRef"
        width="560px"
        top="10vh"
        style="padding: 0; background-color: #ffffff"
        footer-class="!p-0"
        header-class="!p-0"
        confirm-button-text=""
        cancel-button-text=""
        :show-close="false">
        <div class="flex flex-col overflow-hidden bg-white rounded-2xl">
            <div class="px-6 py-4 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-[#0065fb]/10 flex items-center justify-center">
                        <Icon name="el-icon-Picture" color="#0065fb" :size="16" />
                    </div>
                    <span class="text-[15px] font-[1000] text-slate-900 tracking-tight">生成分享卡片</span>
                </div>
                <div
                    class="w-8 h-8 flex items-center justify-center cursor-pointer hover:bg-slate-50 rounded-full transition-all"
                    @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="p-4 flex justify-center items-center">
                <ElScrollbar max-height="600px">
                    <div class="relative group px-4">
                        <div
                            class="rounded-xl overflow-hidden border border-white transition-transform duration-500 group-hover:scale-[1.02]">
                            <img
                                v-if="shareImage"
                                ref="imageRef"
                                :src="shareImage"
                                alt="share-image"
                                crossorigin="anonymous"
                                class="max-w-full h-auto block" />
                            <div
                                v-else
                                class="w-[300px] h-[450px] bg-white flex flex-col items-center justify-center gap-4">
                                <div class="ai-loading-spinner"></div>
                                <span class="text-xs font-bold text-slate-400 animate-pulse"
                                    >正在渲染高画质图片...</span
                                >
                            </div>
                        </div>

                        <div
                            class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-[80%] h-4 bg-[#0065fb]/10 blur-2xl rounded-full -z-10"></div>
                    </div>
                </ElScrollbar>
            </div>

            <div class="px-8 py-6 bg-white border-t border-slate-50 flex items-center gap-3">
                <button
                    class="flex-1 h-12 rounded-xl border border-slate-200 text-slate-600 text-sm font-[900] hover:bg-slate-50 active:scale-95 transition-all flex items-center justify-center gap-2"
                    :disabled="copyLoading"
                    @click="copyImage()">
                    <Icon v-if="!copyLoading" name="el-icon-DocumentCopy" :size="16" />
                    <div
                        v-else
                        class="w-4 h-4 border-2 border-slate-400 border-t-transparent rounded-full animate-spin"></div>
                    {{ copyLoading ? "复制中..." : "复制图片" }}
                </button>
                <button
                    class="flex-1 h-12 rounded-xl bg-[#0065fb] text-white text-sm font-[1000] shadow-lg shadow-[#0065fb]/20 hover:bg-[#0056d6] hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2"
                    @click="download">
                    <Icon name="el-icon-Download" :size="16" />
                    保存到本地
                </button>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
const popupRef = shallowRef();
const shareImage = ref<string>("");
const imageRef = ref<HTMLImageElement | null>(null);
const copyLoading = ref(false);

// 浏览器能力检测
const capabilities = {
    modernClipboard: !!(navigator.clipboard && window.ClipboardItem),
    legacyExecCommand: !!(document.queryCommandSupported && document.queryCommandSupported("copy")),
    writeText: !!(navigator.clipboard && navigator.clipboard.writeText),
};

const open = () => {
    popupRef.value.open();
};

const close = () => {
    popupRef.value.close();
};

const setContent = (res: string) => {
    shareImage.value = res;
};

// 现代 API 复制图片
const copyImageWithModernAPI = async (): Promise<boolean> => {
    try {
        const response = await fetch(shareImage.value);
        if (!response.ok) throw new Error("获取图片失败");

        const blob = await response.blob();

        await navigator.clipboard.write([
            new ClipboardItem({
                [blob.type]: blob,
            }),
        ]);

        return true;
    } catch (error) {
        console.error("现代API复制失败:", error);
        return false;
    }
};

// 传统方法复制图片
const copyImageWithLegacyMethod = (): boolean => {
    try {
        if (!imageRef.value) return false;

        const img = imageRef.value;
        const range = document.createRange();
        const selection = window.getSelection();

        // 清除现有选区
        selection?.removeAllRanges();

        // 选择图片节点
        range.selectNode(img);
        selection?.addRange(range);

        // 执行复制
        const success = document.execCommand("copy");

        // 清除选区
        selection?.removeAllRanges();

        return success;
    } catch (error) {
        console.error("传统方法复制失败:", error);
        return false;
    }
};

// 复制图片URL作为降级方案
const copyImageUrlFallback = async (): Promise<boolean> => {
    try {
        if (capabilities.writeText) {
            await navigator.clipboard.writeText(shareImage.value);
            return true;
        } else if (capabilities.legacyExecCommand) {
            // 使用传统方法复制URL
            const textArea = document.createElement("textarea");
            textArea.value = shareImage.value;
            textArea.style.position = "absolute";
            textArea.style.left = "-9999px";
            textArea.style.opacity = "0";

            document.body.appendChild(textArea);
            textArea.select();
            textArea.setSelectionRange(0, 99999);

            const success = document.execCommand("copy");
            document.body.removeChild(textArea);

            return success;
        }
        return false;
    } catch (error) {
        console.error("URL复制失败:", error);
        return false;
    }
};

// 通用复制图片方法
const copyImage = async () => {
    if (!shareImage.value || !imageRef.value) {
        ElMessage.warning("图片还未加载完成");
        return;
    }

    copyLoading.value = true;

    try {
        let success = false;
        let method = "";

        // 方法1: 现代 Clipboard API 复制图片数据
        if (capabilities.modernClipboard) {
            try {
                success = await copyImageWithModernAPI();
                method = "现代API";
                if (success) {
                    ElMessage.success("图片已复制到剪贴板");
                    return;
                }
            } catch (error) {
                console.log("现代API失败，尝试传统方法:", error);
            }
        }

        // 方法2: 传统方法复制图片节点
        if (capabilities.legacyExecCommand) {
            try {
                success = copyImageWithLegacyMethod();
                method = "传统方法";
                if (success) {
                    ElMessage.success("图片已复制到剪贴板");
                    return;
                }
            } catch (error) {
                console.log("传统方法失败，尝试URL复制:", error);
            }
        }

        // 方法3: 复制图片URL作为降级方案
        try {
            success = await copyImageUrlFallback();
            method = "URL复制";
            if (success) {
                ElMessage.warning("已复制图片链接（浏览器不支持直接复制图片）");
                return;
            }
        } catch (error) {
            console.log("URL复制失败:", error);
        }

        // 所有方法都失败
        ElMessage.error("复制失败，请尝试右键复制图片或保存到本地");
    } finally {
        copyLoading.value = false;
    }
};

const download = async () => {
    downloadFile(shareImage.value, "share-image.png");
};

defineExpose({
    open,
    close,
    setContent,
});
</script>

<style scoped lang="scss">
/* 加载动画 */
.ai-loading-spinner {
    width: 30px;
    height: 30px;
    border: 3px solid #f1f5f9;
    border-top-color: #0065fb;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* 进场动画由 popup 组件控制，此处保持内容简洁 */
</style>
