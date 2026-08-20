<template>
    <popup
        ref="popupRef"
        async
        width="520px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-Monitor" :size="20" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight leading-none"
                            >添加AI智能设备</span
                        >
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.1em] mt-1">
                            Device Binding
                        </span>
                    </div>
                </div>
                <div class="w-9 h-9 cursor-pointer" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="p-6">
                <div class="inline-flex bg-[#F1F5F9] rounded-xl p-1 w-full">
                    <button
                        class="tab-btn"
                        :class="{ 'tab-btn--active': activeTab === 'new' }"
                        @click="handleChangeTab('new')">
                        新设备绑定
                    </button>
                    <button
                        class="tab-btn"
                        :class="{ 'tab-btn--active': activeTab === 'old' }"
                        @click="handleChangeTab('old')">
                        二维码绑定
                    </button>
                </div>

                <div v-if="activeTab === 'new'" class="mt-5 flex flex-col items-center">
                    <div class="text-[15px] font-[1000] text-[#1E293B]">设备绑定码</div>
                    <div class="text-[12px] text-[#94A3B8] mt-1">请勿与任何人分享此代码</div>
                    <div
                        class="w-[260px] h-[260px] mt-5 rounded-2xl bg-[#F8FAFC] border border-slate-100 p-3 flex items-center justify-center"
                        v-loading="loading">
                        <img v-if="qrcode" :src="qrcode" class="w-full h-full rounded-xl object-contain" />
                        <div v-else class="flex flex-col items-center text-[#94A3B8]">
                            <Icon name="el-icon-FullScreen" :size="56" />
                            <div class="text-[13px] mt-3">二维码加载中</div>
                        </div>
                    </div>
                    <div class="mt-5 text-[13px] font-medium text-[#64748B]">请前往RPA启用摄像头扫描此二维码</div>
                </div>

                <div v-else class="mt-5 flex flex-col items-center">
                    <div class="text-[15px] font-[1000] text-[#1E293B]">上传设备二维码</div>
                    <div class="text-[12px] text-[#94A3B8] mt-1">上传微信小程序设备二维码图片，解析成功后自动绑定</div>
                    <upload
                        local
                        drag
                        type="image"
                        accept="image/*"
                        :limit="1"
                        :show-file-list="false"
                        class="w-full mt-5"
                        @change="handleLegacyQrcodeChange">
                        <div
                            class="h-[220px] rounded-2xl border-2 border-dashed border-[#DCE6F2] bg-[#F8FAFC] flex flex-col items-center justify-center transition-all hover:border-primary hover:bg-[#F7FAFF]"
                            v-loading="legacyQrcodeLoading">
                            <Icon name="el-icon-UploadFilled" color="#94A3B8" :size="46" />
                            <div class="text-[15px] font-bold text-[#1E293B] mt-3">点击或拖拽二维码图片上传</div>
                            <div class="text-[12px] text-[#94A3B8] mt-1">仅支持包含设备信息的二维码</div>
                        </div>
                    </upload>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getRpaQrcode, getRpaQrcodeStatus } from "@/api/user";
import { addDeviceAuthPhone } from "@/api/device_auth";
import { scanOldDeviceQrcode } from "@/api/device";
import type { UploadRawFile } from "element-plus";
import jsQR from "jsqr";
import Upload from "@/components/upload/index.vue";

const props = defineProps<{
    bindLoading: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "confirm"): void;
    (e: "update:account", value: string): void;
}>();

const loading = ref(false);
const legacyQrcodeLoading = ref(false);
const activeTab = ref<"new" | "old">("new");

const popupRef = ref<any>(null);
const qrcode = ref<string>("");

const { start, end } = usePolling(
    async () => {
        try {
            const data = await getRpaQrcodeStatus();
            if (data.status == 1) {
                end();
                // 扫码绑定成功后调用 addPhone 完成设备绑定
                try {
                    await addDeviceAuthPhone({
                        device_code: data.device_code,
                        device_name: data.device_name ?? "",
                        device_model: data.device_model ?? "",
                        sdk_version: data.sdk_version ?? "",
                    });
                } catch (error: any) {
                    feedback.msgError(error || "设备绑定失败");
                    return;
                }
                feedback.msgSuccess("绑定成功");
                emit("confirm");
                close();
            }
        } catch (error) {
            end();
            feedback.msgError(error);
        }
    },
    {
        time: 4500,
    },
);

const getRpaQrcodeData = async () => {
    loading.value = true;
    try {
        const data = await getRpaQrcode();
        qrcode.value = data.url;
        start();
    } catch (error) {
        feedback.msgError(error || "获取二维码失败");
    } finally {
        loading.value = false;
    }
};

const handleChangeTab = (tab: "new" | "old") => {
    if (activeTab.value === tab) return;
    activeTab.value = tab;
    end();
    if (tab === "new") {
        getRpaQrcodeData();
    }
};

const parseOldQrcodePayload = (text: string) => {
    const parseFromObject = (value: any) => ({
        device_code: value?.device_code || "",
        activation_code: value?.activation_code || "",
    });

    try {
        return parseFromObject(JSON.parse(text));
    } catch {
        // 非 JSON 二维码内容继续按 URL/query 解析
    }

    try {
        const url = new URL(text);
        return {
            device_code: url.searchParams.get("device_code") || "",
            activation_code: url.searchParams.get("activation_code") || "",
        };
    } catch {
        // 非 URL 二维码内容继续按 query 解析
    }

    const queryText = text.includes("?") ? text.split("?").pop() || "" : text;
    const params = new URLSearchParams(queryText);
    return {
        device_code: params.get("device_code") || "",
        activation_code: params.get("activation_code") || "",
    };
};

const decodeQrcodeWithBarcodeDetector = async (file: File) => {
    const BarcodeDetector = (window as any).BarcodeDetector;
    if (!BarcodeDetector) {
        return "";
    }
    const detector = new BarcodeDetector({ formats: ["qr_code"] });
    const image = await createImageBitmap(file);
    try {
        const [result] = await detector.detect(image);
        return result?.rawValue || "";
    } finally {
        image.close();
    }
};

const decodeQrcodeWithJsQr = async (file: File) => {
    const image = await createImageBitmap(file);
    try {
        const canvas = document.createElement("canvas");
        canvas.width = image.width;
        canvas.height = image.height;
        const context = canvas.getContext("2d");
        if (!context) return "";
        context.drawImage(image, 0, 0);
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        return jsQR(imageData.data, imageData.width, imageData.height)?.data || "";
    } finally {
        image.close();
    }
};

const decodeQrcodeImage = async (file: File) => {
    try {
        const nativeValue = await decodeQrcodeWithBarcodeDetector(file);
        if (nativeValue) return nativeValue;
    } catch {
        // 原生识别失败时继续使用 jsQR 兜底解析
    }

    const jsQrValue = await decodeQrcodeWithJsQr(file);
    if (jsQrValue) return jsQrValue;
    throw new Error("二维码解析失败，请上传清晰的设备二维码图片");
};

const handleLegacyQrcodeChange = async (rawFile: UploadRawFile) => {
    legacyQrcodeLoading.value = true;
    try {
        const rawValue = await decodeQrcodeImage(rawFile);
        const payload = parseOldQrcodePayload(rawValue);
        if (!payload.device_code) {
            feedback.msgError("二维码信息不完整");
            return;
        }
        await scanOldDeviceQrcode(payload);
        feedback.msgSuccess("绑定成功");
        emit("confirm");
        close();
    } catch (error: any) {
        feedback.msgError(error?.message || error || "二维码解析失败");
    } finally {
        legacyQrcodeLoading.value = false;
    }
};

const open = () => {
    popupRef.value.open();
    activeTab.value = "new";
    getRpaQrcodeData();
};

const close = () => {
    emit("close");
    end();
};

onUnmounted(() => end());

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
.tab-btn {
    @apply flex-1 px-5 py-2 rounded-lg text-[14px] font-medium text-[#64748B] transition-all;
    &--active {
        @apply bg-white text-primary font-bold;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    }
}
:deep(.el-upload-dragger) {
    border: none;
    padding: 0;
}
</style>
