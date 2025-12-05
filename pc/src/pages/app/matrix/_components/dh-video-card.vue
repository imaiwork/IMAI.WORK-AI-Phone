<template>
    <div class="w-full h-full card-gradient">
        <div class="w-full px-3 absolute z-[22] top-2">
            <div class="line-clamp-1 text-white">
                {{ item.name }}
            </div>
            <div class="text-[10px] text-white" v-if="item.automatic_clip == 1">AI剪辑</div>
        </div>
        <template v-if="item.status == VideoStatus.VIDEO_COMPOSITION_SUCCESS">
            <video :src="item.video_result_url" class="w-full h-full object-cover"></video>
            <div
                class="absolute top-[50%] left-[50%] translate-x-[-50%] translate-y-[-50%] z-[99]"
                @click="emit('preview', item.video_result_url)">
                <div class="w-12 h-12">
                    <play-btn :icon-size="38"></play-btn>
                </div>
            </div>
            <div
                v-if="item.automatic_clip == 1"
                class="absolute bottom-[80px] left-0 w-full z-[51] text-[#ffffff80] text-center">
                <template v-if="item.clip_status == 1 || item.clip_status == 2"> AI智能剪辑中... </template>
                <template v-if="item.clip_status == 3">AI智能剪辑完成</template>
                <template v-if="item.clip_status == 4">AI智能剪辑失败</template>
            </div>
        </template>
        <template
            v-else-if="
                [VideoStatus.VIDEO_COMPOSITION_FAILED, VideoStatus.AUDIO_COMPOSITION_FAILED].includes(item.status)
            ">
            <div class="w-full h-full flex flex-col items-center justify-center gap-2">
                <img src="@/assets/images/image_error.png" class="w-10 h-10" />
                <div class="text-white font-bold text-xs">生成失败</div>
            </div>
        </template>
        <template v-else>
            <div class="w-full h-full flex flex-col items-center justify-center gap-2">
                <div class="loading"></div>
                <div class="text-white text-xs font-bold">正在生成...</div>
            </div>
        </template>
        <div class="absolute bottom-2 w-full text-center px-3 z-[22]">
            <div class="flex justify-center mb-2" v-if="modelVersionMap[item.model_version]">
                <div class="digital-human-tag !py-1.5 !px-5" :class="`digital-human-tag-${item.model_version}`">
                    {{ modelVersionMap[item.model_version] }}
                </div>
            </div>
            <div class="text-[#ffffff80] line-clamp-1">
                {{ item.create_time }}
            </div>
        </div>
        <div class="absolute right-2 top-2 z-[1000] w-9 h-9 invisible group-hover:visible">
            <handle-menu :theme="ThemeEnum.DARK" :data="item" :menu-list="getUtilsMenuList(item)" />
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

const getUtilsMenuList = (item) => {
    const { automatic_clip, clip_status, clip_result_url, video_result_url } = item;
    const utilsMenuList: HandleMenuType[] = [
        {
            label: "重命名",
            icon: "local-icon-edit3",
            click: async (data) => {
                console.log(data);
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
                    theme: "dark",
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
