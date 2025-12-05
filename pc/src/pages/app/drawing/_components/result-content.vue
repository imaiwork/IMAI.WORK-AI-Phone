<template>
    <DefineTemplate v-slot="{ msg }">
        <div class="flex flex-col gap-2 items-center justify-center">
            <Icon name="local-icon-error_fill" :size="32"></Icon>
            <div class="text-white mt-4 text-lg font-bold">
                {{ msg || "生成失败" }}
            </div>
        </div>
    </DefineTemplate>
    <div class="flex flex-col gap-5 min-w-[700px]">
        <div v-for="item in resultLists">
            <div class="text-white">{{ item.date }}</div>
            <div class="flex flex-wrap items-center gap-2 mt-[11px]">
                <template v-for="tag in item.tags">
                    <div class="tag-item" v-if="tag">
                        {{ tag }}
                    </div>
                </template>
            </div>
            <div class="mt-[11px] flex items-center gap-2" v-if="item.prompt">
                <span class="text-[#ffffffcc]"> {{ item.prompt }} </span>
                <span class="cursor-pointer" @click="copy(item.prompt)">
                    <Icon name="local-icon-copy2"></Icon>
                </span>
            </div>
            <div
                class="mt-[11px] gap-[10px] grid"
                :class="`grid-cols-${item.images?.length || item.video?.length || 4}`">
                <template v-if="type == 'image'">
                    <div
                        v-for="(image, index) in item.images"
                        :key="index"
                        class="img-container group"
                        :class="{ 'pb-[100%] loading': image.loading }">
                        <div
                            class="leading-[0] flex items-center justify-center"
                            v-if="!image.loading && image.status == 1">
                            <div
                                v-if="isHd(item.formData.model)"
                                class="absolute rounded-lg bg-[#000000a3] py-[2px] px-2 top-2 right-2 z-[22] w-fit gap-2 group-hover:visible invisible">
                                <ElTooltip content="下载" placement="top">
                                    <div
                                        class="w-7 h-7 flex items-center justify-center cursor-pointer"
                                        @click.stop="downloadFile(image.url)">
                                        <Icon name="el-icon-Download" :size="16" color="#ffffff"></Icon>
                                    </div>
                                </ElTooltip>
                            </div>
                            <ElImage
                                fit="contain"
                                class="rounded-xl"
                                preview-teleported
                                :src="image.url"
                                :preview-src-list="[image.url]" />
                            <slot name="add-content" :item="item"></slot>
                        </div>
                        <div v-else-if="[3, 4].includes(image.status)" class="w-full pb-[80%]">
                            <div class="absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                <ErrorTemplate></ErrorTemplate>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-if="type == 'video'">
                    <div
                        class="video-container h-[500px] group relative"
                        :class="{ 'pb-[80%] loading': video.loading }"
                        v-for="video in item.video">
                        <template v-if="video.status == 1">
                            <div
                                class="absolute rounded-lg bg-[#000000a3] py-[2px] px-2 top-2 right-2 z-[22] w-fit gap-2 group-hover:visible invisible">
                                <ElTooltip content="下载" placement="top">
                                    <div
                                        class="w-7 h-7 flex items-center justify-center cursor-pointer"
                                        @click.stop="downloadFile(video.url)">
                                        <Icon name="el-icon-Download" :size="16" color="#ffffff"></Icon>
                                    </div>
                                </ElTooltip>
                            </div>
                            <video :src="video.url" class="w-full h-full object-cover"></video>
                            <div class="absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                <div class="cursor-pointer" @click.stop="playVideo(video.url)">
                                    <play-btn />
                                </div>
                            </div>
                        </template>
                        <div v-else-if="video.status == 50430" class="absolute bottom-4 left-4 z-[888]">
                            <div class="text-white">
                                {{ video.msg }}
                            </div>
                        </div>
                        <div
                            class="absolute top-0 left-0 w-full h-full flex items-center justify-center"
                            v-else-if="video.status != 2 && video.status != 0">
                            <ErrorTemplate :msg="video.msg"></ErrorTemplate>
                        </div>
                    </div>
                </template>
            </div>
            <div class="mt-4 flex gap-x-4 h-[20px]" v-if="!isAllTasksCompleted">
                <ElTooltip content="重新生成" placement="bottom">
                    <div class="flex cursor-pointer" @click="emit('retry', item.formData)">
                        <Icon name="el-icon-Refresh" :size="16" color="#8F8F8F"></Icon>
                    </div>
                </ElTooltip>
                <ElTooltip content="下载" placement="bottom">
                    <div class="flex cursor-pointer" @click="handleDownload(item)">
                        <Icon name="el-icon-Download" :size="16" color="#8F8F8F"></Icon>
                    </div>
                </ElTooltip>
            </div>
        </div>
    </div>
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false"></preview-video>
</template>

<script setup lang="ts">
import PreviewVideo from "@/components/preview-video/index.vue";
import { ModelEnum } from "../_enums";

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

const isHd = (modelId: number) => {
    return modelId == ModelEnum.HIDREAMAI;
};

const showPreviewVideo = ref(false);
const previewVideoRef = shallowRef<InstanceType<typeof PreviewVideo>>();

const handleDownload = (data: any) => {
    if (props.type == "image") {
        const { images } = data;
        if (images.length) {
            images.forEach((item) => {
                downloadFile(item.url);
            });
        }
    } else if (props.type == "video") {
    }
};

const playVideo = async (url: string) => {
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value?.setUrl(url);
};

let render;
const DefineTemplate = {
    setup(_, { slots }) {
        return () => {
            render = slots.default;
        };
    },
};

const ErrorTemplate = (props) => {
    return render(props);
};
</script>
<style scoped lang="scss">
.tag-item {
    background-color: var(--app-bg-color-1);
    border: 1px solid var(--app-border-color-1);
    color: #ffffff80;
    font-size: 11px;
    border-radius: 6px;
    padding: 6px 10px;
}
.img-container,
.video-container {
    @apply rounded-xl overflow-hidden bg-[#ffffff14] relative;
    &.loading {
        &::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(270deg, #ffffff0a, #fff0);
            animation: fadeIn 3s infinite ease-in-out;
        }
    }
}
@keyframes fadeIn {
    0% {
        left: -100%;
    }
    100% {
        left: 0;
    }
}
</style>
