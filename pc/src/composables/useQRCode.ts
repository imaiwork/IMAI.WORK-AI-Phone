// composables/useQRCode.ts
import { ref, readonly, type Ref } from "vue";

// 定义二维码配置选项接口
interface QRCodeOptions {
    size?: number;
    level?: "L" | "M" | "Q" | "H";
    background?: string;
    foreground?: string;
    margin?: number;
    imageSettings?: {
        src: string;
        width: number;
        height: number;
        excavate?: boolean;
    };
    dotsOptions?: {
        type?: "square" | "dots" | "rounded" | "extra-rounded" | "classy" | "classy-rounded";
        color?: string;
        gradient?: {
            type: "linear" | "radial";
            rotation?: number;
            colorStops: Array<{
                offset: number;
                color: string;
            }>;
        };
    };
    cornersSquareOptions?: {
        type?: "square" | "dot" | "extra-rounded";
        color?: string;
    };
    cornersDotOptions?: {
        type?: "square" | "dot";
        color?: string;
    };
}

// 定义下载选项接口
interface DownloadOptions {
    name?: string;
    extension?: "png" | "jpg" | "jpeg" | "webp" | "svg";
    quality?: number;
}

// 定义返回类型接口
interface UseQRCodeReturn {
    isGenerating: Readonly<Ref<boolean>>;
    error: Readonly<Ref<string | null>>;
    qrCodeRef: Ref<any>;
    generateQRCode: (text: string, options?: QRCodeOptions) => Promise<void>;
    downloadQRCode: (options?: DownloadOptions) => Promise<void>;
    getQRCodeDataURL: (extension?: string, quality?: number) => Promise<string>;
    validateText: (text: string) => boolean;
    resetError: () => void;
}

export const useQRCode = (): UseQRCodeReturn => {
    const isGenerating = ref<boolean>(false);
    const error = ref<string | null>(null);
    const qrCodeRef = ref<any>(null);

    // 验证输入文本
    const validateText = (text: string): boolean => {
        error.value = null;

        if (!text || typeof text !== "string") {
            error.value = "文本不能为空";
            return false;
        }

        if (text.trim().length === 0) {
            error.value = "文本不能为空白";
            return false;
        }

        if (text.length > 4296) {
            error.value = "文本长度不能超过 4296 个字符";
            return false;
        }

        return true;
    };

    // 重置错误状态
    const resetError = (): void => {
        error.value = null;
    };

    // 生成二维码
    const generateQRCode = async (text: string, options: QRCodeOptions = {}): Promise<void> => {
        isGenerating.value = true;
        error.value = null;

        try {
            if (!validateText(text)) {
                throw new Error(error.value || "无效的输入文本");
            }

            // qrcode.vue 组件会自动处理生成，这里主要是状态管理
            await nextTick();

            // 等待一小段时间确保组件渲染完成
            await new Promise((resolve) => setTimeout(resolve, 100));
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : "生成二维码失败";
            error.value = errorMessage;
            throw new Error(errorMessage);
        } finally {
            isGenerating.value = false;
        }
    };

    // 获取二维码 Data URL
    const getQRCodeDataURL = async (extension: string = "png", quality: number = 1.0): Promise<string> => {
        try {
            if (!qrCodeRef.value) {
                throw new Error("二维码组件未初始化");
            }

            // 调用 qrcode.vue 组件的下载方法获取 data URL
            const canvas = qrCodeRef.value.$el.querySelector("canvas");
            if (!canvas) {
                throw new Error("未找到 Canvas 元素");
            }

            const mimeType = `image/${extension === "jpg" ? "jpeg" : extension}`;
            return canvas.toDataURL(mimeType, quality);
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : "获取二维码数据失败";
            error.value = errorMessage;
            throw new Error(errorMessage);
        }
    };

    // 下载二维码
    const downloadQRCode = async (options: DownloadOptions = {}): Promise<void> => {
        try {
            const { name = "qrcode", extension = "png", quality = 1.0 } = options;

            if (!qrCodeRef.value) {
                throw new Error("二维码组件未初始化");
            }

            // 如果组件有内置的下载方法，直接使用
            if (typeof qrCodeRef.value.download === "function") {
                await qrCodeRef.value.download({
                    name: `${name}.${extension}`,
                    extension,
                    quality,
                });
            } else {
                // 手动实现下载
                const dataURL = await getQRCodeDataURL(extension, quality);

                const link = document.createElement("a");
                link.download = `${name}.${extension}`;
                link.href = dataURL;
                link.style.display = "none";

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : "下载失败";
            error.value = errorMessage;
            throw new Error(errorMessage);
        }
    };

    return {
        isGenerating: readonly(isGenerating),
        error: readonly(error),
        qrCodeRef,
        generateQRCode,
        downloadQRCode,
        getQRCodeDataURL,
        validateText,
        resetError,
    };
};
