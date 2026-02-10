<template>
    <popup
        ref="audioPlayerRef"
        width="800px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="rounded-[28px] overflow-hidden bg-white shadow-2xl relative">
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
                            <path d="M12 1v22M17 5v14M2 10v4M22 10v4M7 7v10" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">音频预览播放</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            Audio Stream Player
                        </div>
                    </div>
                </div>

                <div class="w-8 h-8" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="p-10 bg-slate-50">
                <div class="bg-white rounded-[32px] p-8 border border-br flex flex-col items-center">
                    <div
                        class="w-24 h-24 rounded-[24px] bg-[#F1F5F9] flex items-center justify-center mb-6 shadow-inner">
                        <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center">
                            <Icon name="el-icon-Headset" :size="32" class="text-primary"></Icon>
                        </div>
                    </div>

                    <div class="w-full max-w-[500px]">
                        <audio
                            ref="audioPlayer"
                            class="custom-audio-element w-full"
                            :src="audioUrl"
                            controls
                            autoplay></audio>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
/**
 * 逻辑部分保持原样，仅清理了部分不必要的 style
 */
const audioPlayerRef = ref();
const audioUrl = ref("");
const audioPlayer = ref<HTMLAudioElement | null>(null);

const open = () => {
    audioPlayerRef.value?.open();
};

const close = () => {
    if (audioPlayer.value) {
        audioPlayer.value.pause();
        audioPlayer.value.currentTime = 0;
    }
    audioPlayerRef.value?.close();
};

const setUrl = (url: string) => {
    audioUrl.value = url;
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

/* 针对原生 Audio 标签的样式微调 (不同浏览器表现不同，主要是为了对齐整体色调) */
.custom-audio-element {
    height: 54px;
    filter: invert(0.05) hue-rotate(240deg) brightness(1.05); /* 微调原生组件色调至偏品牌紫 */
    &::-webkit-audio-controls-panel {
        background-color: #f1f5f9;
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
</style>
