<template>
    <div class="w-full h-full relative group overflow-hidden rounded-[20px] bg-[#1a1d24]">
        <div
            class="absolute inset-x-0 top-0 z-[22] p-4 bg-gradient-to-b from-[#000000]/80 via-[#000000]/20 to-[#00000000] pointer-events-none">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1">
                    <div class="text-[14px] font-black text-white line-clamp-1 drop-shadow-md">
                        {{ item.name || "未命名作品" }}
                    </div>
                    <div v-if="item.automatic_clip == 1" class="mt-1 inline-flex">
                        <span
                            class="px-2 py-0.5 rounded-md bg-[#0065fb]/20 backdrop-blur-md border border-[#0065fb]/30 text-[9px] font-black text-primary tracking-wider uppercase">
                            AI剪辑
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full h-full">
            <template v-if="item.status == VideoStatus.VIDEO_COMPOSITION_SUCCESS">
                <video
                    :src="item.video_result_url"
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"></video>

                <div
                    class="absolute inset-0 flex items-center justify-center z-[30] bg-[#000000]/5 group-hover:bg-[#000000]/20 transition-colors cursor-pointer"
                    @click="emit('preview', item.video_result_url)">
                    <div
                        class="w-14 h-14 rounded-full bg-[#ffffff]/20 backdrop-blur-xl flex items-center justify-center border border-[#ffffff]/30 transition-all duration-300 group-hover:scale-110 group-hover:bg-primary group-hover:border-primary">
                        <Icon name="el-icon-CaretRight" :size="32" color="white" />
                    </div>
                </div>

                <div v-if="item.automatic_clip == 1" class="absolute bottom-[80px] inset-x-4 z-[31]">
                    <div
                        class="py-1.5 px-3 rounded-full bg-[#000000]/40 backdrop-blur-xl border border-[#ffffff]/10 text-center w-fit mx-auto">
                        <span class="text-[11px] font-medium" :class="getClipStatusClass(item.clip_status)">
                            <Icon v-if="item.clip_status < 3" name="el-icon-Loading" class="mr-1 animate-spin" />
                            {{ getClipStatusText(item.clip_status) }}
                        </span>
                    </div>
                </div>
            </template>

            <template
                v-else-if="
                    [VideoStatus.VIDEO_COMPOSITION_FAILED, VideoStatus.AUDIO_COMPOSITION_FAILED].includes(item.status)
                ">
                <div class="w-full h-full flex flex-col items-center justify-center gap-4 bg-slate-900">
                    <div
                        class="w-16 h-16 rounded-full bg-[#ef4444]/10 flex items-center justify-center border border-[#ef4444]/20">
                        <Icon name="el-icon-CircleClose" :size="32" color="#f87171" />
                    </div>
                    <div class="flex flex-col items-center">
                        <span class="text-white font-black text-sm">作品合成失败</span>
                        <span class="text-slate-500 text-[10px] mt-1">{{ item.remark }}</span>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="w-full h-full flex flex-col items-center justify-center gap-6 bg-[#1a1d24]">
                    <div class="relative w-16 h-16 flex items-center justify-center">
                        <div class="absolute inset-0 rounded-full border-4 border-[#0065fb]/10"></div>
                        <div
                            class="absolute inset-0 rounded-full border-4 border-primary border-t-[transparent] animate-spin"></div>
                        <span class="animate-bounce">
                            <Icon name="local-icon-import" :size="24" color="var(--color-primary)" />
                        </span>
                    </div>
                    <div class="text-center">
                        <div class="text-primary text-[10px] font-medium mt-1 animate-pulse">正在构建数字人...</div>
                    </div>
                </div>
            </template>
        </div>

        <div
            class="absolute inset-x-0 bottom-0 z-[22] p-4 bg-gradient-to-t from-[#000000]/90 via-[#000000]/40 to-[transparent]">
            <div class="flex flex-col items-center gap-2">
                <div v-if="modelVersionMap[item.model_version]" class="mb-1">
                    <div
                        class="px-4 py-1.5 rounded-lg text-[10px] font-black tracking-wider uppercase border border-[#ffffff]/20"
                        :class="`tag-v-${item.model_version}`"
                        style="background: rgba(255, 255, 255, 0.1); color: #fff; backdrop-filter: blur(8px)">
                        {{ modelVersionMap[item.model_version] }}
                    </div>
                </div>
                <div class="text-slate-400 text-[10px] font-medium italic">
                    {{ item.create_time }}
                </div>
            </div>
        </div>

        <div
            class="w-8 h-8 absolute right-3 top-3 z-[1000] opacity-0 translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
            <handle-menu :data="item" :menu-list="getUtilsMenuList(item)" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ThemeEnum } from "@/enums/appEnums";
import { HandleMenuType } from "@/components/handle-menu/typings";
import { useAppStore } from "@/stores/app";

const props = withDefaults(
    defineProps<{
        item: any;
    }>(),
    {
        item: {},
    }
);

const emit = defineEmits(["edit", "delete", "preview"]);

enum VideoStatus {
    WAITING = 0,
    AUDIO_RESULT_QUERY = 1,
    AUDIO_COMPOSITION_FAILED = 2,
    AUDIO_COMPOSITION_SUCCESS = 3,
    VIDEO_RESULT_QUERY = 4,
    VIDEO_COMPOSITION_FAILED = 5,
    VIDEO_COMPOSITION_SUCCESS = 6,
}

const appStore = useAppStore();
const modelChannel = computed(() => appStore.getDigitalHumanConfig?.channel);

const modelVersionMap = computed(() => {
    return modelChannel.value.reduce((acc: Record<string, string>, item: any) => {
        acc[item.id] = item.name;
        return acc;
    }, {});
});

const getClipStatusText = (status: number) => {
    const map = { 1: "AI智能剪辑中", 2: "AI智能剪辑中", 3: "AI剪辑已完成", 4: "AI剪辑失败" };
    return map[status] || "等待剪辑";
};

const getClipStatusClass = (status: number) => {
    if (status === 3) return "text-emerald-400";
    if (status === 4) return "text-red-400";
    return "text-primary animate-pulse";
};

const getUtilsMenuList = (item) => {
    const { automatic_clip, clip_status, clip_result_url, video_result_url } = item;
    const utilsMenuList: HandleMenuType[] = [
        {
            label: "重命名",
            icon: "local-icon-edit3",
            click: async (data) => {
                emit("edit", props.item);
            },
        },
        {
            label: "下载视频",
            icon: "local-icon-download",
            click: (data: any) => {
                handleDownLoad(video_result_url);
            },
        },
        {
            label: "删除视频",
            icon: "local-icon-delete",
            click: ({ id }) => {
                useNuxtApp().$confirm({
                    message: "确定删除该视频吗？",
                    onConfirm: async () => {
                        emit("delete", props.item);
                    },
                });
            },
        },
    ];
    if (automatic_clip == 1 && clip_status == 3 && clip_result_url) {
        utilsMenuList.push(
            ...[
                {
                    label: "播放剪辑视频",
                    icon: "local-icon-play",
                    click: (data: any) => {
                        emit("preview", clip_result_url);
                    },
                },
                {
                    label: "下载剪辑视频",
                    icon: "local-icon-download",
                    click: (data: any) => {
                        handleDownLoad(clip_result_url);
                    },
                },
            ]
        );
    }
    return utilsMenuList;
};

const handleDownLoad = (url: string) => {
    feedback.loading("保存中");
    downloadFile(url)
        .then(() => {
            feedback.closeLoading();
            feedback.msgSuccess("下载成功");
        })
        .catch(() => {
            feedback.closeLoading();
            feedback.msgError("下载失败");
        });
};
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";

.tag-v-1 {
    border-color: #0065fb !important;
    color: #0065fb !important;
}
.tag-v-2 {
    border-color: #10b981 !important;
    color: #10b981 !important;
}

.loading {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: inline-block;
    position: relative;
    border: 10px solid;
    -webkit-animation: animloader51 1s linear infinite alternate;
    animation: animloader51 1s linear infinite alternate;
}
@keyframes animloader51 {
    0% {
        border-color: white rgba(255, 255, 255, 0) rgba(255, 255, 255, 0) rgba(255, 255, 255, 0);
    }
    33% {
        border-color: white rgba(255, 255, 255, 0) rgba(255, 255, 255, 0);
    }
    66% {
        border-color: white white white rgba(255, 255, 255, 0);
    }
    100% {
        border-color: white white white white;
    }
}
</style>
