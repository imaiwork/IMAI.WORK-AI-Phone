<template>
    <popup-bottom
        v-model="showPopup"
        height="62%"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        @close="handleClosePopup">
        <template #header>
            <view class="px-[32rpx] pt-[24rpx]">
                <view class="w-[72rpx] h-[8rpx] bg-[#E5EAF3] rounded-full mx-auto mb-[32rpx]"></view>
                <view class="flex items-center justify-between mb-[36rpx]">
                    <text class="text-[34rpx] font-bold text-[#1A1A1A]">添加手机</text>
                    <view
                        class="w-[56rpx] h-[56rpx] bg-[#F4F6FA] rounded-full flex items-center justify-center active:opacity-80"
                        @click="showPopup = false">
                        <u-icon name="close" color="#555555" size="24" />
                    </view>
                </view>
            </view>
        </template>

        <template #content>
            <view class="h-full flex flex-col items-center px-[32rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                <view
                    class="w-[360rpx] h-[360rpx] rounded-[28rpx] bg-[#F8FAFF] border border-[#E2E8F0] flex items-center justify-center overflow-hidden p-[20rpx]">
                    <image
                        v-if="addPhoneQrcode"
                        :src="addPhoneQrcode"
                        mode="aspectFit"
                        class="w-full h-full rounded-[20rpx]" />
                    <view v-else class="flex flex-col items-center">
                        <image
                            src="@/packages/static/icons/qr_code.svg"
                            mode="aspectFit"
                            class="w-[120rpx] h-[120rpx]" />
                        <text class="text-[22rpx] text-[#94A3B8] mt-[16rpx]">
                            {{ isLoadingQrcode ? "二维码加载中" : "二维码加载失败" }}
                        </text>
                    </view>
                </view>
                <view class="text-center mt-[28rpx]">
                    <text class="block text-[28rpx] font-semibold text-[#1A1A1A]">使用手机扫描二维码</text>
                    <text class="block text-[24rpx] text-[#888888] leading-[40rpx] mt-[12rpx]">
                        绑定设备后，需用CDK完成 AI 手机激活
                    </text>
                    <text class="block text-[24rpx] text-[#888888] leading-[40rpx]">才能解锁完整功能</text>
                </view>
                <view
                    class="mt-4 flex w-full items-center justify-center gap-[12rpx] rounded-[28rpx] border-[2rpx] border-solid border-[#E2E8F0] bg-[#F1F5F9] py-[24rpx] text-sm font-semibold text-[#475569]"
                    @click="handleAddLegacyDevice">
                    <image src="@/packages/static/icons/qr_code.svg" mode="aspectFit" class="h-[28rpx] w-[28rpx]" />
                    <text>扫码添加设备</text>
                </view>
                <view
                    class="mt-4 w-full h-[96rpx] bg-primary rounded-[28rpx] flex items-center justify-center shadow-[0_8rpx_32rpx_rgba(43,110,255,0.28)] active:opacity-85"
                    @click="showPopup = false">
                    <text class="text-white text-[30rpx] font-semibold">完成</text>
                </view>
            </view>
        </template>
    </popup-bottom>

    <confirm-dialog
        v-if="showBindSuccessDialog"
        v-model="showBindSuccessDialog"
        center
        cancel-text="稍后配置"
        confirm-text="前往配置"
        content="您已添加新的设备，是否立即为该设备配置“IP人设”？"
        @confirm="handleBindSuccessConfirm" />
</template>

<script setup lang="ts">
import { getRpaQrcode, getRpaQrcodeStatus } from "@/api/user";
import { addDeviceAuthPhone } from "@/api/device_auth";
import { scanOldDeviceQrcode } from "@/api/device";
import usePolling from "@/hooks/usePolling";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        showConfigConfirm?: boolean;
        beforeLoadQrcode?: () => Promise<void> | void;
    }>(),
    {
        modelValue: false,
        showConfigConfirm: true,
    },
);

const emit = defineEmits<{
    (event: "update:modelValue", value: boolean): void;
    (event: "bound"): void;
    (event: "legacy-bound"): void;
}>();

const showPopup = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const addPhoneQrcode = ref("");
const newDeviceCode = ref("");
const isLoadingQrcode = ref(false);
const showBindSuccessDialog = ref(false);

const finishBind = () => {
    showPopup.value = false;
    emit("bound");
};

const decodeQrcodeText = (text: string) => {
    try {
        return decodeURIComponent(text);
    } catch {
        return text;
    }
};

const safeHideLoading = () => {
    try {
        uni.hideLoading();
    } catch {
        // 未展示 loading 时，真机会抛 hideLoading:fail，这里直接吞掉
    }
};

const getRecordValues = (record: Record<string, any>) => Object.keys(record).map((key) => record[key]);

const parseQrcodeQuery = (source: string): Record<string, string> => {
    const queryText = source.includes("?") ? source.split("?").pop() || "" : source;
    const cleanQuery = queryText.split("#")[0] || "";
    const record: Record<string, string> = {};
    cleanQuery.split("&").forEach((pair) => {
        if (!pair || !pair.includes("=")) return;
        const separatorIndex = pair.indexOf("=");
        const rawKey = pair.slice(0, separatorIndex).replace(/\+/g, " ");
        const rawValue = pair.slice(separatorIndex + 1).replace(/\+/g, " ");
        const key = decodeQrcodeText(rawKey).trim();
        if (!key) return;
        record[key] = decodeQrcodeText(rawValue).trim();
    });
    return record;
};

const collectNestedQrcodeRecords = (value: any, depth: number): Record<string, any>[] => {
    if (depth >= 3 || typeof value !== "string") return [];
    const source = decodeQrcodeText(value.trim());
    if (!source) return [];
    return collectQrcodeRecords(source, depth + 1);
};

const collectQrcodeRecords = (source: string, depth = 0): Record<string, any>[] => {
    const records: Record<string, any>[] = [];
    try {
        const parsed = JSON.parse(source);
        if (parsed && typeof parsed === "object") {
            records.push(parsed);
            getRecordValues(parsed).forEach((value) => {
                records.push(...collectNestedQrcodeRecords(value, depth));
            });
        }
    } catch {
        // 非 JSON 二维码内容继续按 URL/query 解析
    }

    const queryParams = parseQrcodeQuery(source);
    if (Object.keys(queryParams).length > 0) {
        records.push(queryParams);
        getRecordValues(queryParams).forEach((value) => {
            records.push(...collectNestedQrcodeRecords(value, depth));
        });
    }
    return records;
};

const parseLegacyDeviceQrcode = (result: string) => {
    const source = decodeQrcodeText(`${result || ""}`.trim());
    for (const record of collectQrcodeRecords(source)) {
        const deviceCode = `${record.device_code ?? ""}`.trim();
        const activationCode = `${record.activation_code ?? ""}`.trim();
        if (deviceCode) {
            return {
                device_code: deviceCode,
                activation_code: activationCode,
            };
        }
    }
    return null;
};

const scanLegacyDeviceQrcode = (): Promise<string> => {
    if (typeof uni.scanCode !== "function") {
        return Promise.reject("请在微信小程序中扫码添加设备");
    }
    return new Promise((resolve, reject) => {
        uni.scanCode({
            onlyFromCamera: true,
            scanType: ["qrCode"],
            success: (res) => resolve(res.result || ""),
            fail: reject,
        });
    });
};

const handleAddLegacyDevice = async () => {
    showPopup.value = false;
    endBindPolling();
    let bindingLoading = false;
    try {
        const result = await scanLegacyDeviceQrcode();
        const payload = parseLegacyDeviceQrcode(result);
        if (!payload) {
            uni.showToast({ title: "二维码信息不完整", icon: "none" });
            return;
        }
        uni.showLoading({ title: "绑定中...", mask: true });
        bindingLoading = true;
        await scanOldDeviceQrcode(payload);
        safeHideLoading();
        bindingLoading = false;
        uni.showToast({ title: "设备添加成功", icon: "none" });
        finishBind();
        emit("legacy-bound");
    } catch (error: any) {
        if (bindingLoading) {
            safeHideLoading();
        }
        const message = typeof error === "string" ? error : error?.errMsg || "";
        if (message.includes("cancel")) return;
        uni.showToast({ title: message || "扫码添加失败", icon: "none" });
    }
};

const { start: startBindPolling, end: endBindPolling } = usePolling(
    async () => {
        const data = await getRpaQrcodeStatus();
        if (data.status == 1) {
            endBindPolling();
            newDeviceCode.value = data.device_code;
            try {
                await addDeviceAuthPhone({
                    device_code: data.device_code,
                    device_name: data.device_name ?? "",
                    device_model: data.device_model ?? "",
                    sdk_version: data.sdk_version ?? "",
                });
            } catch (error: any) {
                uni.showToast({ title: error || "设备绑定失败", icon: "none" });
                return;
            }
            finishBind();
            if (props.showConfigConfirm) {
                showBindSuccessDialog.value = true;
            }
            uni.showToast({
                title: "绑定成功",
                icon: "none",
                duration: 3000,
            });
        }
    },
    {
        time: 4500,
    },
);

const getAddPhoneQrcode = async () => {
    isLoadingQrcode.value = true;
    addPhoneQrcode.value = "";
    try {
        await props.beforeLoadQrcode?.();
        const data = await getRpaQrcode();
        addPhoneQrcode.value = data?.url || "";
        startBindPolling();
    } catch (error: any) {
        uni.showToast({ title: error || "二维码加载失败", icon: "none" });
    } finally {
        isLoadingQrcode.value = false;
    }
};

const handleClosePopup = () => {
    endBindPolling();
};

const handleBindSuccessConfirm = () => {
    uni.$u.route({
        url: "/ai_modules/device/pages/setting_person/setting_person",
        type: "reLaunch",
        params: {
            device_code: newDeviceCode.value,
        },
    });
};

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) {
            getAddPhoneQrcode();
            return;
        }
        endBindPolling();
    },
);

onUnmounted(() => {
    endBindPolling();
});
</script>
