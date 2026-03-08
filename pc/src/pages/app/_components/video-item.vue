<template>
    <div class="video-item group transform-gpu">
        <div class="grow min-h-0 relative rounded-[24px] overflow-hidden bg-slate-900 isolation-isolate">
            <div class="w-full h-full">
                <img
                    :src="item.pic"
                    alt=""
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/30 pointer-events-none"></div>
            </div>

            <div class="absolute top-3 left-3 z-[22] pointer-events-none" v-if="item.clip_status != 0">
                <span
                    class="px-2 py-0.5 rounded-md bg-[#0065fb]/20 backdrop-blur-md border border-[#0065fb]/30 text-[9px] font-black text-primary uppercase">
                    AI 剪辑
                </span>
            </div>

            <div
                class="absolute right-2 top-2 z-[1000] w-9 h-9 flex items-center justify-center bg-[#ffffff]/10 rounded-full invisible group-hover:visible transition-all backdrop-blur-md border border-[#ffffff]/10"
                :class="[activeVideo == item.id ? '!visible !bg-primary !border-primary  shadow-[#0065fb]/30' : '']">
                <ElPopover
                    popper-class="!w-[180px] !min-w-[180px] !p-1.5 !rounded-2xl !border-slate-800 !bg-[#020617]/95 !backdrop-blur-xl"
                    :show-arrow="false"
                    :popper-options="{ modifiers: [{ name: 'offset', options: { offset: [0, 12] } }] }"
                    @show="visibleChange(true, item.id)"
                    @hide="visibleChange(false, item.id)">
                    <template #reference>
                        <div class="rotate-90 cursor-pointer w-full h-full flex items-center justify-center">
                            <Icon name="el-icon-MoreFilled" color="#ffffff" :size="16"></Icon>
                        </div>
                    </template>
                    <div class="flex flex-col gap-1 text-white">
                        <DefineTemplate v-slot="{ label, icon }">
                            <div
                                class="h-10 px-3 rounded-xl cursor-pointer flex items-center gap-3 hover:bg-[#ffffff]/10 transition-colors group">
                                <span
                                    class="flex w-6 h-6 rounded-lg bg-[#ffffff]/5 items-center justify-center group-hover:bg-[#0065fb]/20">
                                    <Icon :name="icon" color="#ffffff" :size="14"></Icon>
                                </span>
                                <span class="text-[13px] font-medium text-[#ffffff]/70 group-hover:text-white">{{
                                    label
                                }}</span>
                            </div>
                        </DefineTemplate>
                        <div @click="handleEditName(item.id)">
                            <SelectItemTemplate label="修改名称" icon="local-icon-edit" />
                        </div>
                        <div @click="handleDownLoad(item)">
                            <SelectItemTemplate label="下载视频" icon="local-icon-download" />
                        </div>
                        <div @click="handleDelete(item.id)">
                            <SelectItemTemplate label="删除视频" icon="local-icon-delete" />
                        </div>
                    </div>
                </ElPopover>
            </div>

            <template v-if="getStatus(item) == 1">
                <div
                    class="absolute inset-0 flex items-center justify-center z-[99]"
                    @click="handlePlay(item.clip_video_url || item.video_url)">
                    <div
                        class="w-14 h-14 rounded-full bg-[#ffffff]/20 backdrop-blur-xl border border-[#ffffff]/30 flex items-center justify-center transition-all cursor-pointer group-hover:scale-110 group-hover:bg-primary group-hover:border-primary group-hover:shadow-lg group-hover:shadow-primary/40">
                        <play-btn :icon-size="34"></play-btn>
                    </div>
                </div>
                <div v-if="item.clip_status != 0" class="absolute bottom-5 inset-x-3 z-[51]">
                    <div
                        class="py-1.5 px-3 rounded-full bg-[#020617]/40 backdrop-blur-md border border-[#ffffff]/10 text-center w-fit mx-auto">
                        <span
                            class="text-[10px] font-black tracking-wider"
                            :class="item.clip_status == 4 ? 'text-red-400' : 'text-primary'">
                            <span v-if="item.clip_status < 3" class="mr-1 animate-spin">
                                <Icon name="el-icon-Loading" />
                            </span>
                            <template v-if="item.clip_status == 1 || item.clip_status == 2">
                                AI 智能剪辑中...
                            </template>
                            <template v-if="item.clip_status == 3"> AI 智能剪辑完成 </template>
                            <template v-if="item.clip_status == 4"> AI 智能剪辑失败 </template>
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
                                class="w-10 h-10 flex items-center justify-center rounded-2xl bg-red-500/20 border border-red-500/20">
                                <Icon name="local-icon-video2" color="#f87171" :size="20"></Icon>
                            </div>
                            <div class="flex flex-col items-center">
                                <span class="text-white font-black text-sm">{{ item.remark || "生成失败" }}</span>
                                <span class="text-white/40 text-[10px] mt-1 text-center font-medium">
                                    请检查训练文件
                                </span>
                            </div>
                        </template>
                        <template v-else>
                            <div
                                class="w-10 h-10 rounded-full border-[3px] border-[#0065fb]/20 border-t-primary animate-spin mb-1 shadow-[0_0_15px_rgba(0,101,251,0.2)]"></div>
                            <div class="flex flex-col items-center">
                                <span class="text-white font-black text-sm tracking-widest uppercase opacity-90"
                                    >生成中...</span
                                >
                                <span class="text-primary font-medium text-[10px] mt-1 animate-pulse"
                                    >预计几分钟内完成视频生成</span
                                >
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <div class="w-full mt-3 px-1">
            <div class="flex items-center justify-between gap-2">
                <div class="text-[13px] font-black text-slate-200 line-clamp-1 flex-1">
                    {{ item.name || "未命名视频" }}
                </div>
                <div
                    class="px-2 py-0.5 rounded-md bg-white/5 border border-white/10 text-[10px] text-slate-400 font-medium">
                    {{ getTypeName(item.type) }}
                </div>
            </div>
            <div class="text-[11px] mt-1.5 text-slate-500 font-medium italic opacity-60">
                {{ item.create_time }}
            </div>
        </div>
    </div>
    <preview-video v-if="showVideo" ref="videoPlayerRef" @close="showVideo = false"></preview-video>
</template>
<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { updateVideoCreationRecord } from "@/api/app";
import { CreateVideoTypeEnum } from "@/pages/app/digital_human/_enums";
import { createReusableTemplate } from "@vueuse/core";

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
const appStore = useAppStore();
const modelChannel = computed(() => appStore.getDigitalHumanConfig?.channel);

const modelVersionMap = computed(() => {
    return modelChannel.value.reduce((acc: Record<string, string>, item: any) => {
        acc[item.id] = item.name;
        return acc;
    }, {});
});

const activeVideo = ref<number | undefined>();

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

const visibleChange = (flag: boolean, id: number) => {
    if (!flag) {
        activeVideo.value = undefined;
    } else {
        activeVideo.value = id;
    }
};

const handleEditName = (id: number) => {
    ElMessageBox.prompt("请输入视频名称", "提示", {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        inputPattern: /\S+/,
        inputPlaceholder: "请输入视频名称",
        inputErrorMessage: "视频名称不能为空",
        inputValue: props.item.name,
        customClass: "!rounded-2xl",
    }).then(async ({ value }) => {
        try {
            await updateVideoCreationRecord({
                id,
                name: value,
                task_id: props.item.task_id,
                type: props.item.type,
            });
            props.item.name = value;
            feedback.msgSuccess("修改名称成功");
        } catch (error) {
            feedback.msgError(error);
        }
    });
};

const handleDownLoad = (item: any) => {
    const { video_result_url, clip_result_url, clip_status } = item;
    if (!video_result_url && !clip_result_url) {
        feedback.msgWarning("视频未生成");
        return;
    }
    useNuxtApp().$confirm({
        title: "提示",
        message: "确定下载视频吗？",
        cancelButtonText: clip_status != 3 ? "取消" : "下载生成视频",
        confirmButtonText: clip_status == 3 ? "下载剪辑视频" : "下载生成视频",
        onConfirm: () => {
            downloadFile(clip_status == 3 ? clip_result_url : video_result_url);
        },
        onCancel: () => {
            if (clip_status != 3) return;
            downloadFile(video_result_url);
        },
    });
};

const handleDelete = async (id: number) => {
    emit("delete", id);
};

const videoPlayerRef = shallowRef();
const showVideo = ref(false);
const handlePlay = async (url: string) => {
    showVideo.value = true;
    await nextTick();
    videoPlayerRef.value.open();
    videoPlayerRef.value.setUrl(url);
};

const [DefineTemplate, SelectItemTemplate] = createReusableTemplate<{
    label: string;
    icon: string;
}>();
</script>
<style lang="scss" scoped>
.video-item {
    @apply flex flex-col h-[320px] relative transition-all duration-300;
}

.isolation-isolate {
    isolation: isolate;
    -webkit-mask-image: -webkit-radial-gradient(white, black);
}

.rotation {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(0, 101, 251, 0.1);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: rotation 1s linear infinite;
}

@keyframes rotation {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}
</style>
