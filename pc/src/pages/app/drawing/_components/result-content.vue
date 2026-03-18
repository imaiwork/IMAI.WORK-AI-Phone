<template>
    <ErrorTemplate.define v-slot="{ msg, status }">
        <div class="flex flex-col gap-2 items-center justify-center py-8">
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mb-2">
                <Icon name="local-icon-error_fill" :size="24" color="#ffffff"></Icon>
            </div>
            <div class="text-[15px] font-black text-[#1E293B] text-center">
                {{ msg || "生成失败" }}
            </div>
            <p class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">Generation Failed</p>
        </div>
    </ErrorTemplate.define>

    <div class="flex flex-col w-full bg-white rounded-[20px] h-full border border-[#F1F5F9] overflow-hidden">
        <div class="shrink-0 h-[72px] flex items-center justify-between px-8 border-b border-[#F1F5F9] bg-white">
            <div class="flex items-center gap-x-3">
                <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary">
                    <Icon name="el-icon-Picture" :size="20" v-if="type === 'image'"></Icon>
                    <Icon name="el-icon-VideoCamera" :size="20" v-else></Icon>
                </div>
                <div>
                    <div class="text-[18px] text-[#1E293B] font-black tracking-tight">生成结果</div>
                    <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">Artworks Gallery</div>
                </div>
            </div>
        </div>

        <div class="grow min-h-0 bg-slate-50">
            <ElScrollbar v-if="resultLists.length > 0">
                <div class="p-6 space-y-10">
                    <div v-for="(item, idx) in resultLists" :key="idx" class="relative">
                        <div class="flex items-center gap-3 mb-5">
                            <span
                                class="text-xs font-black text-[#1E293B] bg-white px-3 py-1 rounded-lg border border-[#F1F5F9]"
                                >{{ item.date }}</span
                            >
                            <div class="h-[1px] grow bg-[#E2E8F0]"></div>
                        </div>

                        <div class="space-y-3 mb-5 px-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <template v-for="tag in item.tags">
                                    <div class="custom-tag" v-if="tag">{{ tag }}</div>
                                </template>
                            </div>
                            <div class="flex items-start gap-3 group/prompt" v-if="item.prompt">
                                <div class="text-[13px] leading-relaxed text-[#64748B] italic grow line-clamp-2">
                                    "{{ item.prompt }}"
                                </div>
                                <div
                                    class="p-1.5 rounded-lg bg-white border border-br opacity-0 group-hover:opacity-100 transition-all cursor-pointer hover:text-primary"
                                    @click="copy(item.prompt)">
                                    <Icon name="el-icon-DocumentCopy" :size="14"></Icon>
                                </div>
                            </div>
                        </div>

                        <div class="gap-4 grid" :class="`grid-cols-${item.images?.length || item.video?.length || 4}`">
                            <template v-if="type == 'image'">
                                <div v-for="(image, index) in item.images" :key="index" class="result-card group">
                                    <div
                                        v-if="image.loading"
                                        class="absolute inset-0 z-30 flex flex-col items-center justify-center bg-[#ffffff]/80 rounded-2xl">
                                        <div class="modern-loader">
                                            <div class="loader-ring"></div>
                                            <div class="loader-dot"></div>
                                        </div>
                                        <div class="mt-4 flex flex-col items-center">
                                            <div class="text-[13px] font-black text-[#1E293B] animate-pulse h-5">
                                                {{ currentLoadingText }}
                                            </div>
                                            <div
                                                class="text-[9px] text-[#94A3B8] font-medium uppercase tracking-[0.2em] mt-1">
                                                Creating Magic
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="absolute inset-0 flex items-center justify-center bg-[#F1F5F9]"
                                        v-else-if="image.status == 1">
                                        <div
                                            class="absolute inset-0 bg-[#000000]/40 opacity-0 group-hover:opacity-100 transition-all z-20 flex items-end p-4">
                                            <div
                                                class="flex gap-2 transform translate-y-2 group-hover:translate-y-0 transition-transform">
                                                <ElTooltip content="下载" placement="bottom">
                                                    <div class="action-btn" @click.stop="downloadFile(image.url)">
                                                        <Icon name="el-icon-Download" :size="16"></Icon>
                                                    </div>
                                                </ElTooltip>

                                                <ElTooltip content="重新生成" placement="bottom">
                                                    <div class="action-btn" @click.stop="emit('retry', item.formData)">
                                                        <Icon name="el-icon-Refresh" :size="16"></Icon>
                                                    </div>
                                                </ElTooltip>
                                            </div>
                                        </div>
                                        <ElImage
                                            fit="contain"
                                            class="w-full h-full transform transition-transform duration-700 group-hover:scale-110"
                                            preview-teleported
                                            :src="image.url"
                                            :preview-src-list="[image.url]" />
                                    </div>

                                    <div
                                        v-else
                                        class="absolute inset-0 bg-white flex items-center justify-center rounded-2xl border border-red-50">
                                        <ErrorTemplate.reuse
                                            :msg="image.msg"
                                            :status="image.status"></ErrorTemplate.reuse>
                                    </div>
                                </div>
                            </template>

                            <template v-if="type == 'video'">
                                <div v-for="video in item.video" :key="video.id" class="result-card h-[420px]">
                                    <div
                                        v-if="video.loading"
                                        class="absolute inset-0 z-30 flex flex-col items-center justify-center bg-[#ffffff0d]">
                                        <div class="modern-loader large">
                                            <div class="loader-ring"></div>
                                        </div>
                                        <div class="mt-6 text-[14px] font-black text-[#1E293B]">
                                            {{ currentLoadingText }}
                                        </div>
                                    </div>
                                    <template v-else-if="video.status == 1">
                                        <video :src="video.url" class="w-full h-full object-cover"></video>
                                        <div
                                            class="absolute inset-0 bg-[#000000]/20 group-hover:bg-[#000000]/40 transition-all flex items-center justify-center">
                                            <div class="play-trigger" @click.stop="playVideo(video.url)">
                                                <Icon name="el-icon-VideoPlay" :size="48" color="white"></Icon>
                                            </div>
                                            <div
                                                class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <div class="action-btn" @click.stop="downloadFile(video.url)">
                                                    <Icon name="el-icon-Download" :size="16"></Icon>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div
                                        v-else
                                        class="absolute inset-0 bg-white flex items-center justify-center rounded-2xl">
                                        <ErrorTemplate.reuse
                                            :msg="video.msg"
                                            :status="video.status"></ErrorTemplate.reuse>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
            <div v-else class="h-full flex flex-col items-center justify-center">
                <div
                    class="w-20 h-20 bg-white rounded-[32px] flex items-center justify-center border border-[#F1F5F9] mb-4">
                    <Icon name="el-icon-Picture" :size="32" color="#CBD5E1"></Icon>
                </div>
                <p class="text-[#94A3B8] font-medium text-sm">暂无生成作品</p>
                <p class="text-[10px] text-[#CBD5E1] uppercase tracking-[0.3em] mt-1">Empty Gallery</p>
            </div>
        </div>
    </div>
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false"></preview-video>
</template>

<script setup lang="ts">
import { createReusableTemplate } from "@vueuse/core";
import PreviewVideo from "@/components/preview-video/index.vue";

const props = withDefaults(
    defineProps<{
        type?: "image" | "video";
        resultLists: any[];
        isAllTasksCompleted: boolean;
    }>(),
    {
        type: "image",
    }
);

const emit = defineEmits<{
    (event: "retry", formData: any): void;
}>();

const { copy } = useCopy();

const showPreviewVideo = ref(false);
const previewVideoRef = shallowRef<InstanceType<typeof PreviewVideo>>();

const playVideo = async (url: string) => {
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value?.setUrl(url);
};

const ErrorTemplate = createReusableTemplate<{
    msg?: string;
    status?: number;
}>();

// --- 动态加载文字逻辑 ---
const loadingTexts = ["AI 正在构思画面", "正在精雕细琢像素", "优化光影细节", "注入艺术灵感", "即将呈现作品"];
const currentLoadingText = ref(loadingTexts[0]);
let textTimer: any = null;

onMounted(() => {
    textTimer = setInterval(() => {
        const index = loadingTexts.indexOf(currentLoadingText.value);
        currentLoadingText.value = loadingTexts[(index + 1) % loadingTexts.length];
    }, 2500);
});

onUnmounted(() => {
    clearInterval(textTimer);
});
</script>
<style scoped lang="scss">
/* 基础卡片样式 */
.result-card {
    @apply relative aspect-square rounded-[20px] bg-white border border-br shadow-light overflow-hidden transition-all duration-500;
    &:hover {
        @apply shadow-light shadow-[#0065fb]/15 -translate-y-1 border-[#0065fb]/30;
    }
}

/* 标签样式 */
.custom-tag {
    @apply bg-white border border-br text-[#64748B] text-[10px] font-black px-2.5 py-1 rounded-lg shadow-light tracking-wider uppercase;
}

/* 操作按钮 */
.action-btn {
    @apply w-9 h-9 flex items-center justify-center rounded-xl bg-[#fffffff2] backdrop-blur text-[#1E293B] hover:bg-primary hover:text-white transition-all shadow-light cursor-pointer;
}

/* 现代加载器动画 */
.modern-loader {
    @apply relative w-12 h-12 flex items-center justify-center;
    &.large {
        @apply w-16 h-16;
    }

    .loader-ring {
        @apply absolute inset-0 border-[3px] border-[#F1F5F9] border-t-[#0065fb] rounded-full;
        animation: spin 1s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }
    .loader-dot {
        @apply w-2 h-2 bg-primary rounded-full;
        animation: pulse-dot 1.5s ease-in-out infinite;
    }
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
@keyframes pulse-dot {
    0%,
    100% {
        opacity: 0.4;
        transform: scale(1);
    }
    50% {
        opacity: 1;
        transform: scale(1.4);
    }
}

/* 视频播放触发器 */
.play-trigger {
    @apply opacity-80 hover:opacity-100 transition-all cursor-pointer transform hover:scale-110 active:scale-95;
}
</style>
