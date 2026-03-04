<template>
    <popup
        ref="videoPlayerRef"
        width="900px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="rounded-[28px] overflow-hidden bg-white shadow-2xl relative border border-br">
            <div class="flex items-center justify-between h-[72px] px-8 border-b border-[#F1F5F9]">
                <div class="flex items-center gap-x-3">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>
                            <line x1="7" y1="2" x2="7" y2="22"></line>
                            <line x1="17" y1="2" x2="17" y2="22"></line>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <line x1="2" y1="7" x2="7" y2="7"></line>
                            <line x1="2" y1="17" x2="7" y2="17"></line>
                            <line x1="17" y1="17" x2="22" y2="17"></line>
                            <line x1="17" y1="7" x2="22" y2="7"></line>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">视频预览播放</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            Video Stream Player
                        </div>
                    </div>
                </div>

                <div class="w-8 h-8" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="p-8 bg-slate-50">
                <div
                    class="bg-black rounded-[20px] overflow-hidden shadow-2xl relative group border-[6px] border-white">
                    <video
                        ref="videoRef"
                        :src="videoUrl"
                        controls
                        class="w-full h-[500px] object-contain transition-transform duration-500"
                        autoplay
                        @loadedmetadata="onVideoLoaded"></video>

                    <div v-if="!videoLoaded" class="absolute inset-0 flex items-center justify-center bg-slate-900">
                        <div class="flex flex-col items-center">
                            <div class="loading-dot"></div>
                            <span class="text-white/40 text-xs mt-4 font-medium tracking-widest uppercase"
                                >Buffering</span
                            >
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between px-2">
                    <div class="flex items-center gap-2 text-[#64748B]">
                        <Icon name="el-icon-Monitor" :size="14" />
                        <span class="text-[13px] font-medium">内容预览</span>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
const emit = defineEmits(["close"]);

const videoUrl = ref("");
const videoRef = ref<HTMLVideoElement | null>(null);
const videoPlayerRef = ref();
const videoLoaded = ref(false);

const open = () => {
    videoPlayerRef.value.open();
};

const setUrl = (url: string) => {
    videoUrl.value = url;
    videoLoaded.value = false;
};

const close = () => {
    if (videoRef.value) {
        videoRef.value.pause();
    }
    emit("close");
    videoPlayerRef.value.close();
};

const onVideoLoaded = () => {
    videoLoaded.value = true;
};

defineExpose({
    open,
    setUrl,
});
</script>

<style scoped lang="scss">
/* 弹窗容器深度覆盖 */
:deep(.el-dialog) {
    background: transparent !important;
    box-shadow: none !important;
    .el-dialog__header {
        display: none;
    }
    .el-dialog__body {
        padding: 0 !important;
    }
}

/* 进场动画 */
:deep(.popup-content) {
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* 简单的加载点动画 */
.loading-dot {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(255, 255, 255, 0.1);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
