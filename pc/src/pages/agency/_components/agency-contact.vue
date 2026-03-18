<template>
    <popup
        ref="popupRef"
        width="520px"
        top="15vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-Connection" :size="20" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight leading-none">代理联系方式</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.1em] mt-1"
                            >Agent Contact Info</span
                        >
                    </div>
                </div>
                <div class="w-9 h-9 cursor-pointer" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="flex flex-col p-6 gap-6">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex gap-3 items-start">
                    <span class="text-slate-400 mt-0.5 shrink-0">
                        <Icon name="el-icon-InfoFilled" :size="16" />
                    </span>
                    <p class="text-xs font-bold text-slate-500 leading-relaxed">
                        设置您的联系方式后，通过您的链接注册的用户可以看到这些联系信息，方便下级代理或用户与您取得联系。
                    </p>
                </div>

                <div class="flex flex-col gap-3 text-left">
                    <label class="text-sm font-[1000] text-slate-800">联系二维码</label>
                    <upload
                        type="image"
                        show-progress
                        :show-file-list="false"
                        :max-size="20"
                        @success="handleUploadSuccess">
                        <div
                            class="relative w-32 h-32 shrink-0 rounded-2xl border-2 border-dashed border-slate-200 bg-[#f8fafc]/50 hover:border-[#0065fb]/30 hover:bg-[#0065fb]/5 transition-all cursor-pointer group flex flex-col items-center justify-center overflow-hidden">
                            <img v-if="formData.qrCode" :src="formData.qrCode" class="w-full h-full object-cover" />

                            <template v-else>
                                <div
                                    class="w-10 h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center group-hover:border-[#0065fb]/20 group-hover:text-primary transition-all text-slate-400 mb-2">
                                    <Icon name="el-icon-Plus" :size="20" />
                                </div>
                                <span
                                    class="text-xs font-bold text-slate-400 group-hover:text-primary transition-colors"
                                    >上传图片</span
                                >
                            </template>

                            <div
                                v-if="formData.qrCode"
                                class="absolute inset-0 bg-[#000000]/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <span class="text-white text-xs font-bold flex items-center gap-1">
                                    <Icon name="el-icon-Refresh" /> 更换
                                </span>
                            </div>
                        </div>
                    </upload>
                </div>
            </div>

            <div
                class="px-8 py-6 border-t border-slate-50 flex items-center justify-end shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.01)]">
                <div class="flex items-center gap-3">
                    <button
                        @click="close"
                        class="px-6 h-11 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all active:scale-95">
                        取消
                    </button>
                    <ElButton
                        type="primary"
                        :disabled="isLock"
                        :loading="isLock"
                        class="!px-10 !h-11 !rounded-xl !text-sm !font-[1000] hover:scale-[1.02] active:scale-95 transition-all"
                        @click="lockFn">
                        {{ isLock ? "保存中..." : "确认保存" }}
                    </ElButton>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getAgentUserInfo, setAgentUserContactQrcode } from "@/api/user";

const emit = defineEmits<{
    (e: "success"): void;
    (e: "close"): void;
}>();

const popupRef = ref();

const formData = reactive({
    qrCode: "",
});

const handleUploadSuccess = (res: any) => {
    formData.qrCode = res.data.uri;
};

const open = () => {
    popupRef.value?.open();
    getInfo();
};

const close = () => {
    popupRef.value?.close();
    emit("close");
};

const handleConfirm = async () => {
    if (!formData.qrCode) {
        feedback.msgWarning("请上传微信二维码");
        return;
    }
    try {
        await setAgentUserContactQrcode({ qr_code: formData.qrCode });
        feedback.msgSuccess("保存成功");
        emit("success");
        close();
    } catch (error) {
        feedback.msgError(error);
    }
};

const { lockFn, isLock } = useLockFn(handleConfirm);

const getInfo = async () => {
    const res = await getAgentUserInfo();
    formData.qrCode = res.qr_code;
};

defineExpose({
    open,
    close,
});
</script>
