import { useClipboard } from "@vueuse/core";
import { ElMessage } from "element-plus";

interface Options {
    errMsg?: string;
    successMsg?: string;
}

export function useCopy() {
    const copy = async (text: string, options?: Options) => {
        if (!text) {
            ElMessage.error({ message: options?.errMsg || "复制失败" });
            return;
        }
        const { copy } = useClipboard({ source: text });
        try {
            if (navigator.clipboard) {
                setTimeout(async () => {
                    await copy(text);
                }, 0);
            } else {
                const textarea = document.createElement("textarea");
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand("copy");
                document.body.removeChild(textarea);
            }
            ElMessage.success({ message: options?.successMsg || "复制成功" });
        } catch (error) {
            ElMessage.error({ message: options?.errMsg || "复制失败" });
        }
    };
    return {
        copy,
    };
}
