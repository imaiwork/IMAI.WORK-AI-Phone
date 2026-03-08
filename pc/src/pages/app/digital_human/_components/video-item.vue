<template>
    <div class="video-item">
        <div
            class="h-[250px] relative rounded-[24px] overflow-hidden bg-slate-900 isolation-isolate transform-gpu group">
            <div class="w-full h-full">
                <img
                    :src="item.pic"
                    alt=""
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-[#000000]/80 via-transparent to-[#000000]/20 pointer-events-none"></div>
            </div>

            <div class="absolute top-3 left-3 z-[22]" v-if="item.clip_status != 0">
                <span
                    class="px-2 py-0.5 rounded-md bg-[#0065fb]/20 backdrop-blur-md border border-[#0065fb]/30 text-[9px] font-medium text-primary uppercase">
                    AI 剪辑
                </span>
            </div>
            <template v-if="getStatus(item) == 1">
                <div
                    class="absolute inset-0 flex items-center justify-center z-[30] cursor-pointer"
                    @click="handlePlayCheck">
                    <div
                        class="w-12 h-12 rounded-full bg-[#ffffff]/20 backdrop-blur-xl border border-[#ffffff]/30 flex items-center justify-center transition-all group-hover:scale-110 group-hover:bg-primary group-hover:border-primary group-hover:shadow-light group-hover:shadow-[#0065fb]/40">
                        <play-btn :icon-size="38"></play-btn>
                    </div>
                </div>

                <div v-if="item.automatic_clip == '1'" class="absolute bottom-5 inset-x-3 z-[31]">
                    <div
                        class="py-1.5 px-3 rounded-full bg-[#000000]/40 backdrop-blur-md border border-[#ffffff]/10 text-center w-fit mx-auto">
                        <span
                            class="text-[10px] font-black tracking-wider"
                            :class="item.clip_status == 4 ? 'text-red-400' : 'text-primary'">
                            <template v-if="item.clip_status == 1 || item.clip_status == 2"> AI智能剪辑中... </template>
                            <template v-if="item.clip_status == 3"> AI智能剪辑完成 </template>
                            <template v-if="item.clip_status == 4"> AI智能剪辑失败 </template>
                        </span>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    class="absolute inset-0 z-[88] flex flex-col items-center justify-center bg-[#020617]/60 rounded-[24px]">
                    <div class="absolute inset-0 backdrop-blur-[6px] rounded-[24px] -z-10"></div>

                    <div class="flex justify-center items-center flex-col gap-3 relative z-10 px-4">
                        <template v-if="getStatus(item) == 2">
                            <div
                                class="w-10 h-10 flex items-center justify-center rounded-2xl bg-[#ef4444]/20 border border-[#ef4444]/20">
                                <Icon name="local-icon-video2" color="#f87171" :size="20"></Icon>
                            </div>
                            <span class="text-white font-black text-sm text-center">{{
                                item.remark || "生成失败"
                            }}</span>
                            <span class="text-[#ffffff]/40 text-[10px] font-medium">请检查视频素材</span>
                        </template>
                        <template v-else>
                            <div
                                class="w-9 h-9 rounded-full border-[3px] border-[#0065fb]/20 border-t-primary animate-spin mb-1"></div>
                            <span class="text-white font-black text-xs uppercase tracking-widest opacity-80"
                                >生成中...</span
                            >
                            <span class="text-primary font-medium text-[10px] animate-pulse">预计几分钟内完成</span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
        <div class="w-full mt-3 px-1">
            <div class="flex justify-between items-center gap-2">
                <div class="text-[14px] font-black text-slate-800 break-all line-clamp-1 flex-1">
                    {{ item.name || "未命名作品" }}
                </div>
                <div class="flex-shrink-0">
                    <handle-menu
                        horizontal
                        :menu-list="getMenuList"
                        :data="item"
                        class="!text-slate-400 hover:!text-primary transition-colors" />
                </div>
            </div>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-[#0000004d] text-[11px] font-medium italic">{{ item.create_time }}</span>
                <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                <span class="text-[#0065fb]/70 text-[11px] font-black uppercase tracking-tight">{{
                    getTypeName(item.type)
                }}</span>
            </div>
        </div>
    </div>
    <preview-video v-if="showVideo" ref="videoPlayerRef" @close="showVideo = false"></preview-video>
    <rename-pop
        ref="renamePopRef"
        v-if="showRenamePop"
        :fetch-fn="updateVideoCreationRecord"
        @close="showRenamePop = false"
        @success="getUpdateNameResult"></rename-pop>
    <div v-if="showDownload" class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
        <div
            class="absolute inset-0 bg-[#0f172a]/40 backdrop-blur-md transition-opacity"
            @click="showDownload = false"></div>
        <div
            class="relative w-full max-w-[420px] bg-white rounded-[32px] shadow-[0_24px_60px_rgba(0,101,251,0.18)] overflow-hidden animate-in">
            <div class="h-1.5 w-full bg-primary"></div>

            <div class="p-8">
                <div class="flex flex-col items-center text-center mb-8">
                    <div
                        class="w-16 h-16 bg-[#0065fb]/5 rounded-2xl flex items-center justify-center mb-4 text-primary">
                        <Icon name="el-icon-Download" :size="28" />
                    </div>
                    <h3 class="text-slate-800 font-medium text-[20px] tracking-tight">资源打包完成</h3>
                    <p class="text-slate-400 text-[13px] font-medium mt-1">请选择您需要下载的视频版本</p>
                </div>

                <div class="flex flex-col gap-4">
                    <div
                        class="download-item-card group"
                        v-if="hasClipVideo"
                        @click="downloadFile(item.clip_result_url)">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-[#0065fb]/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all duration-300">
                                <Icon name="local-icon-auto" :size="20" />
                            </div>
                            <div class="flex flex-col flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[15px] font-black text-slate-700">下载 AI 剪辑版</span>
                                    <span
                                        class="px-1.5 py-0.5 bg-primary/10 text-primary text-[10px] rounded font-medium"
                                        >推荐</span
                                    >
                                </div>
                                <span class="text-[11px] text-slate-400 font-medium mt-1">包含智能字幕、转场与BGM</span>
                            </div>
                            <div class="text-slate-300 group-hover:text-primary transition-colors leading-[0]">
                                <Icon name="el-icon-Download" :size="18" />
                            </div>
                        </div>
                    </div>

                    <div class="download-item-card group" @click="downloadFile(item.video_result_url)">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-slate-200 group-hover:text-slate-600 transition-all duration-300">
                                <Icon name="local-icon-video" :size="20" />
                            </div>
                            <div class="flex flex-col flex-1">
                                <span class="text-[15px] font-black text-slate-700">下载视频原片</span>
                                <span class="text-[11px] text-slate-400 font-medium mt-1"
                                    >原始纯净画面，无后期处理</span
                                >
                            </div>

                            <div class="text-slate-300 group-hover:text-slate-500 transition-colors">
                                <Icon name="el-icon-Download" :size="18" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-center">
                    <button
                        @click="showDownload = false"
                        class="text-[13px] text-slate-400 font-medium hover:text-slate-600 transition-colors">
                        取消下载
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div v-if="showPlaySelection" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
        <div
            class="absolute inset-0 bg-[#0f172a]/40 backdrop-blur-md transition-opacity"
            @click="showPlaySelection = false"></div>

        <div
            class="relative w-full max-w-[360px] bg-white rounded-[32px] border border-white shadow-[0_20px_50px_rgba(0,101,251,0.15)] overflow-hidden animate-in fade-in zoom-in-95 duration-300">
            <div class="h-1.5 w-full bg-primary"></div>

            <div class="p-8 flex flex-col items-center text-center gap-6">
                <div class="space-y-2">
                    <h3 class="text-slate-800 font-medium text-[20px] tracking-tight">预览版本选择</h3>
                    <p class="text-slate-400 text-[13px] font-medium">请选择您想要查看的视频版本</p>
                </div>

                <div class="flex flex-col gap-4 w-full">
                    <button
                        @click="selectPlay(item.clip_result_url)"
                        class="group relative w-full p-4 rounded-2xl flex items-center justify-between bg-primary hover:bg-[#0055d6] transition-all shadow-lg shadow-[#0065fb]/20 hover:-translate-y-1">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-[#ffffff]/20 flex items-center justify-center shadow-inner">
                                <Icon name="local-icon-auto" color="#ffffff" :size="18" />
                            </div>
                            <div class="flex flex-col items-start text-left">
                                <span class="text-white font-black text-[15px]">AI 剪辑版本</span>
                                <span class="text-[#ffffff]/70 text-[11px] font-medium">智能处理 · 效果更佳</span>
                            </div>
                        </div>
                        <div
                            class="w-8 h-8 rounded-full bg-[#ffffff]/10 flex items-center justify-center group-hover:bg-[#ffffff]/20 transition-colors">
                            <Icon name="el-icon-CaretRight" color="#ffffff" :size="20" />
                        </div>
                    </button>

                    <button
                        @click="selectPlay(item.video_result_url)"
                        class="group w-full p-4 rounded-2xl flex items-center justify-between bg-slate-50 hover:bg-slate-100 border border-slate-100 transition-all hover:border-[#0065fb]/20">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center shadow-sm">
                                <span class="text-slate-400 group-hover:text-primary transition-colors">
                                    <Icon name="local-icon-video" :size="18" />
                                </span>
                            </div>
                            <div class="flex flex-col items-start text-left">
                                <span class="text-slate-700 font-black text-[15px]">数字人原片</span>
                                <span class="text-slate-400 text-[11px] font-medium">原始生成 · 未经剪辑</span>
                            </div>
                        </div>
                        <Icon
                            name="el-icon-ArrowRight"
                            class="text-slate-300 group-hover:text-primary group-hover:translate-x-1 transition-all"
                            :size="16" />
                    </button>
                </div>

                <div
                    class="text-[13px] text-slate-400 font-medium cursor-pointer hover:text-slate-600 transition-colors"
                    @click="showPlaySelection = false">
                    暂不播放
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { HandleMenuType } from "@/components/handle-menu/typings";
import { updateVideoCreationRecord } from "@/api/app";
import { CreateVideoTypeEnum } from "@/pages/app/digital_human/_enums";

const props = withDefaults(
    defineProps<{
        item: Record<string, any>;
        showVersion?: boolean;
        isCreate?: boolean;
    }>(),
    {
        item: () => ({}),
        showVersion: true,
        isCreate: false,
    }
);

const emit = defineEmits(["edit", "delete", "retry"]);

const renamePopRef = ref();
const showRenamePop = ref(false);
const showDownload = ref(false);
const showVideo = ref(false);
const showPlaySelection = ref(false);

const videoPlayerRef = shallowRef();

const menuList = ref<HandleMenuType[]>([
    {
        label: "修改名称",
        icon: "local-icon-edit",
        click: (data: any) => handleEditName(data.id),
    },
    {
        label: "下载视频",
        icon: "local-icon-download",
        click: (data: any) => (showDownload.value = true),
    },
    {
        label: "删除视频",
        icon: "local-icon-delete",
        click: (data: any) => handleDelete(data),
    },
]);

const getMenuList = computed(() => {
    if (props.item.type == 6 && [1, 2].includes(getStatus(props.item))) {
        menuList.value.push({
            label: "重试",
            icon: "el-icon-Refresh",
            click: (data: any) => handleRetry(data),
        });
    }
    return menuList.value;
});

// 根据不同的类型获取不同的status值
const getStatus = (item: any) => {
    const { type, status } = item || {};

    if (type === 1) {
        if (status === 0 || status === 1 || status === 2) {
            return status;
        }
        return 3;
    } else {
        if (status === 0) {
            return 0;
        }
        if (status === 3) {
            return 1;
        }
        if (status === 2) {
            return 2;
        }
        return 3;
    }
};

const getTypeName = (type: number) => {
    return [
        { name: "数字人口播", key: CreateVideoTypeEnum.DIGITAL_HUMAN },
        { name: "口播混剪", key: CreateVideoTypeEnum.ORAL_MIX },
        { name: "真人口播", key: CreateVideoTypeEnum.REAL_PERSON_MIXING },
        { name: "素材混剪", key: CreateVideoTypeEnum.MATERIAL_MIX },
        { name: "新闻体", key: CreateVideoTypeEnum.NEWS },
        { name: "一句话生成", key: CreateVideoTypeEnum.SENTENCE },
    ].find((item: any) => item.key === type)?.name;
};

const getUpdateNameResult = async (res: any) => {
    props.item.name = res.name;
};

const handleEditName = async (value: any) => {
    showRenamePop.value = true;
    await nextTick();
    renamePopRef.value.open();
    renamePopRef.value.setFormData({
        id: props.item.id,
        name: props.item.name,
        task_id: props.item.task_id,
        type: props.item.type,
    });
};

const hasClipVideo = computed(() => {
    const { automatic_clip, clip_status, clip_result_url } = props.item;
    return automatic_clip == "1" && clip_status == 3 && clip_result_url;
});

const handleDelete = async (item: any) => {
    emit("delete", item);
};

const handleRetry = async (item: any) => {
    emit("retry", item);
};

const handlePlayCheck = () => {
    const { video_result_url } = props.item;

    if (hasClipVideo.value) {
        showPlaySelection.value = true;
    } else {
        triggerPlay(video_result_url);
    }
};
const selectPlay = (url: string) => {
    showPlaySelection.value = false;
    if (url) {
        triggerPlay(url);
    } else {
        // 理论上不会走到这里，但为了健壮性
        feedback.msgWarning("视频地址无效");
    }
};
const triggerPlay = async (url: string) => {
    showVideo.value = true;
    await nextTick();
    videoPlayerRef.value.open();
    videoPlayerRef.value.setUrl(url);
};
</script>

<style lang="scss" scoped>
.video-item {
    @apply flex flex-col relative transition-all duration-300;
}

.isolation-isolate {
    isolation: isolate;
    -webkit-mask-image: -webkit-radial-gradient(white, black);
    mask-image: radial-gradient(white, black);
}

.download-item-card {
    @apply p-4 rounded-2xl bg-white border border-slate-100 cursor-pointer transition-all duration-300;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);

    &:hover {
        @apply border-[#0065fb]/30 -translate-y-1;
        box-shadow: 0 12px 24px rgba(0, 101, 251, 0.08);
        background-color: #fcfdff;
    }

    &:active {
        @apply translate-y-0 scale-[0.98];
    }
}

/* 进场动画 */
.animate-in {
    animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.animate-bounce-slow {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
}

.animate-in {
    animation: zoomIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>
