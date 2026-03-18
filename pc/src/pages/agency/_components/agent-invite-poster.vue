<template>
    <popup
        ref="popupRef"
        width="520px"
        top="8vh"
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
                        <Icon name="el-icon-Picture" :size="20" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight leading-none">邀请海报</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.1em] mt-1">
                            Invitation Poster
                        </span>
                    </div>
                </div>
                <div class="w-9 h-9 cursor-pointer" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="flex flex-col p-6 gap-6">
                <div v-if="loading" class="flex flex-col items-center justify-center py-16 gap-4">
                    <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-sm text-slate-400 font-bold">海报生成中...</span>
                </div>

                <div v-else-if="hasError" class="flex flex-col items-center justify-center py-10 gap-4">
                    <div
                        class="w-16 h-16 rounded-full flex items-center justify-center"
                        style="background: linear-gradient(135deg, #eef4ff, #ddeaff)">
                        <Icon name="el-icon-Picture" :size="28" color="#90b0e8" />
                    </div>
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-base font-bold text-[#1a2b4a]">海报生成失败</span>
                        <span class="text-xs text-slate-400 text-center leading-relaxed">
                            网络异常或服务繁忙，请稍后重试
                        </span>
                    </div>
                    <button
                        class="mt-2 px-8 h-10 rounded-xl flex items-center gap-2 text-white text-sm font-bold border-none cursor-pointer"
                        style="background: linear-gradient(90deg, #0055d4, #4d9aff)"
                        @click="handleRetry">
                        <Icon name="el-icon-Refresh" :size="14" />
                        重新加载
                    </button>
                </div>

                <div v-else class="flex flex-col items-center gap-5">
                    <div class="w-full p-4 bg-slate-50 rounded-xl border border-slate-100 flex gap-3 items-start">
                        <span class="text-slate-400 mt-0.5 shrink-0">
                            <Icon name="el-icon-InfoFilled" :size="16" />
                        </span>
                        <p class="text-xs font-bold text-slate-500 leading-relaxed">
                            将海报发送给好友，好友通过海报中的二维码扫码注册后，将成为您的下级代理用户。
                        </p>
                    </div>

                    <div
                        ref="posterRef"
                        class="relative overflow-hidden rounded-2xl shadow-light shadow-[#e2e8f0]/80 shrink-0"
                        :style="{
                            width: '240px',
                            height: '360px',
                            backgroundImage: `url(${posterBgUrl})`,
                            backgroundSize: 'cover',
                            backgroundPosition: 'center',
                        }">
                        <div
                            class="absolute rounded-2xl overflow-visible"
                            :style="{
                                top: '64px',
                                left: '26px',
                                width: '188px',
                                height: '263px',
                                backgroundImage: `url(${posterWhiteBg})`,
                                backgroundSize: 'cover',
                                backgroundPosition: 'center',
                            }">
                            <div class="absolute -top-[38px] left-0 w-full flex flex-col items-center">
                                <div
                                    class="w-[76px] h-[76px] rounded-full border-[6px] border-white overflow-hidden bg-white">
                                    <img
                                        :src="avatarUrl"
                                        class="w-full h-full object-cover rounded-full"
                                        crossorigin="anonymous" />
                                </div>

                                <span class="mt-1.5 text-[13px] font-bold text-[#333] leading-none">
                                    {{ userInfo.nickname }}
                                </span>

                                <span class="mt-1 text-[9px] text-[#B2B2B2]"> 邀你体验全新 AI 智能体 </span>

                                <div class="mt-[22px] w-[120px] h-[120px] rounded-xl overflow-hidden">
                                    <img
                                        :src="shareQrcode"
                                        class="w-full h-full object-contain"
                                        crossorigin="anonymous" />
                                </div>

                                <span class="mt-2 text-[12px] font-bold text-[#333]">
                                    {{ shareSn }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="!loading && !hasError"
                class="px-8 py-6 border-t border-slate-50 flex items-center justify-end shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.01)]">
                <div class="flex items-center gap-3">
                    <button
                        @click="close"
                        class="px-6 h-11 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all active:scale-95">
                        关闭
                    </button>
                    <ElButton
                        type="primary"
                        :loading="isSaving"
                        class="!px-8 !h-11 !rounded-xl !text-sm !font-[1000] hover:scale-[1.02] active:scale-95 transition-all"
                        @click="savePoster">
                        <Icon name="el-icon-Download" :size="15" class="mr-1" />
                        {{ isSaving ? "保存中..." : "下载保存" }}
                    </ElButton>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import html2canvas from "html2canvas";
import { getAgentUserShareQrcode } from "@/api/user";
import { useUserStore } from "@/stores/user";

const emit = defineEmits<{
    (e: "close"): void;
}>();

const userStore = useUserStore();
const { userInfo } = toRefs(userStore);

const popupRef = ref();
const posterRef = ref<HTMLElement>();

const baseUrl = location.origin;

const avatarUrl = ref(userInfo.value.avatar);
const posterBgUrl = ref(baseUrl + "/static/images/mp/agent_poster_bg.png");
const posterWhiteBg = ref(baseUrl + "/static/images/mp/agent_poster_white_bg.png");

const shareQrcode = ref("");
const shareSn = ref("");
const loading = ref(false);
const hasError = ref(false);
const isSaving = ref(false);

const getShareQrcode = async () => {
    return await getAgentUserShareQrcode({ path: "pages/index/index" });
};

const init = async () => {
    loading.value = true;
    hasError.value = false;
    try {
        const res = await getShareQrcode();
        if (!res.url) {
            hasError.value = true;
            return;
        }
        shareQrcode.value = res.url;
        shareSn.value = res.sn;
    } catch (e) {
        hasError.value = true;
    } finally {
        loading.value = false;
    }
};

const handleRetry = () => init();

const savePoster = async () => {
    if (!posterRef.value) return;
    isSaving.value = true;
    try {
        const images = posterRef.value.querySelectorAll("img");
        await Promise.all(
            Array.from(images).map(
                (img) =>
                    new Promise<void>((resolve) => {
                        if (img.complete) {
                            resolve();
                        } else {
                            img.onload = () => resolve();
                            img.onerror = () => resolve();
                        }
                    })
            )
        );

        const canvas = await html2canvas(posterRef.value, {
            useCORS: true,
            allowTaint: false,
            scale: window.devicePixelRatio * 3, // 根据屏幕像素比 * 3 倍
            backgroundColor: null,
            imageTimeout: 0, // 不限制图片加载超时
            logging: false,
        });

        const url = canvas.toDataURL("image/png", 1.0); // 1.0 = 最高质量
        const a = document.createElement("a");
        a.href = url;
        a.download = "invite_poster.png";
        a.click();
        feedback.msgSuccess("保存成功");
    } catch {
        feedback.msgError("下载失败，请重试");
    } finally {
        isSaving.value = false;
    }
};

const open = () => {
    popupRef.value?.open();
    init();
};

const close = () => {
    popupRef.value?.close();
    emit("close");
};

defineExpose({ open, close });
</script>
