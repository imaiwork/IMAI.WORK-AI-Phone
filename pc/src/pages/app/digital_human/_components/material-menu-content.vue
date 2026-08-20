<template>
    <DefineTemplate v-slot="{ type, accept }">
        <upload
            class="w-full"
            show-progress
            :type="type"
            :accept="accept || getAccept"
            :data="{
                ffmpeg: ffmpeg ? 1 : 0,
                generate_thumbnail: generateThumbnail ? 1 : 0,
                fetch_video_info: fetchVideoInfo ? 1 : 0,
            }"
            :limit="getLimit"
            :show-file-list="false"
            :max-size="getUploadSize"
            :min-duration="montageUploadConfig.videoDuration[0]"
            :max-duration="montageUploadConfig.videoDuration[1]"
            :image-resolution="getImageResolution"
            :video-resolution="getVideoResolution"
            @change="handleUploadChange(type, $event)">
            <div
                class="w-full flex items-center gap-3 p-3 rounded-xl cursor-pointer group transition-all"
                :class="type === 'video' ? 'hover:bg-blue-50' : 'hover:bg-orange-50'">
                <div
                    class="w-10 h-10 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform"
                    :class="type === 'video' ? 'bg-blue-50 text-primary' : 'bg-orange-50 text-orange-500'">
                    <Icon :name="type === 'video' ? 'el-icon-VideoCamera' : 'el-icon-Picture'" :size="20" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-black text-slate-700"
                        >上传{{ type === "video" ? "视频" : "图片" }}素材</span
                    >
                    <span class="text-[10px] text-slate-400 font-medium uppercase"
                        >Dynamic {{ type === "video" ? "Video" : "Image" }}</span
                    >
                </div>
            </div>
        </upload>
    </DefineTemplate>

    <UseTemplate v-if="showImage" type="image" :accept="imageAccept" />
    <UseTemplate v-if="showVideo" type="video" :accept="videoAccept" />

    <div class="flex items-center gap-2 px-3 py-1.5">
        <div class="flex-1 h-px bg-slate-100"></div>
        <span class="text-[10px] font-black text-slate-300 uppercase tracking-wider">素材库</span>
        <div class="flex-1 h-px bg-slate-100"></div>
    </div>

    <div
        v-for="item in filteredLibraryItems"
        :key="item.action"
        class="w-full flex items-center gap-3 p-3 rounded-xl cursor-pointer group transition-all"
        :class="item.hoverBg"
        @click="$emit('action', { type: item.action })">
        <div
            class="w-10 h-10 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform shrink-0"
            :class="item.iconBg">
            <Icon :name="item.icon" :size="20" />
        </div>
        <div class="flex flex-col">
            <span class="text-sm font-black text-slate-700">{{ item.label }}</span>
            <span class="text-[10px] text-slate-400 font-medium uppercase">{{ item.subLabel }}</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { createReusableTemplate } from "@vueuse/core";
import { montageUploadConfig } from "@/pages/app/digital_human/_config";
import { getValidUploadFileData } from "@/pages/app/digital_human/_hooks/useUpload";

// ─── Types ───────────────────────────────────────────────────────────────────

type PanelType = "image" | "video" | "all";
type MediaType = "image" | "video";
type Resolution = [number, number];
type ResolutionRange = [Resolution, Resolution];

type LibraryItem = {
    action: string;
    label: string;
    subLabel: string;
    icon: string;
    hoverBg: string;
    iconBg: string;
    belongs: PanelType;
};

// ─── Props & Emits ───────────────────────────────────────────────────────────

const props = withDefaults(
    defineProps<{
        type?: PanelType;
        imageAccept?: string;
        videoAccept?: string;
        imageLimit?: number;
        videoLimit?: number;
        imageSize?: number;
        videoSize?: number;
        imageResolution?: ResolutionRange;
        videoResolution?: ResolutionRange;
        ffmpeg?: boolean;
        generateThumbnail?: boolean;
        fetchVideoInfo?: boolean;
    }>(),
    {
        type: "all",
        imageAccept: "",
        videoAccept: "",
        imageSize: 30,
        videoSize: 100,
        ffmpeg: true,
        generateThumbnail: true,
        fetchVideoInfo: true,
    },
);

const emit = defineEmits<{
    action: [payload: { type: string; event?: any }];
}>();

const handleUploadChange = (type: MediaType, event: any) => {
    if (!getValidUploadFileData(event)) return;
    emit("action", { type: `upload-${type}`, event });
};

// ─── Reusable Template ───────────────────────────────────────────────────────

const [DefineTemplate, UseTemplate] = createReusableTemplate<{ type: MediaType; accept?: string }>();

// ─── Visibility ──────────────────────────────────────────────────────────────

const showImage = computed(() => props.type !== "video");
const showVideo = computed(() => props.type !== "image");

// ─── Library Items ───────────────────────────────────────────────────────────

const LIBRARY_ITEMS: LibraryItem[] = [
    {
        action: "library-image",
        label: "从图片素材库选择",
        subLabel: "Image Library",
        icon: "el-icon-Picture",
        hoverBg: "hover:bg-orange-50",
        iconBg: "bg-orange-50 text-orange-500",
        belongs: "image",
    },
    {
        action: "library-video",
        label: "从视频素材库选择",
        subLabel: "Video Library",
        icon: "el-icon-VideoCamera",
        hoverBg: "hover:bg-blue-50",
        iconBg: "bg-blue-50 text-primary",
        belongs: "video",
    },
    {
        action: "history",
        label: "从创作库选择",
        subLabel: "Creation History",
        icon: "el-icon-Clock",
        hoverBg: "hover:bg-purple-50",
        iconBg: "bg-purple-50 text-purple-500",
        belongs: "all",
    },
];

const filteredLibraryItems = computed<LibraryItem[]>(() => {
    if (props.type === "all") return LIBRARY_ITEMS;
    return LIBRARY_ITEMS.filter((item) => item.belongs === props.type || item.belongs === "all");
});

// ─── Upload Config ───────────────────────────────────────────────────────────

const getLimit = computed<number>(() => {
    const { imageLimit, videoLimit, type } = props;
    if (type === "image" && imageLimit != null) return imageLimit;
    if (type === "video" && videoLimit != null) return videoLimit;
    return montageUploadConfig.count;
});

const getUploadSize = computed<number>(() => {
    const { type, imageSize, videoSize } = props;
    const fallback = montageUploadConfig.fileSize;
    if (type === "image") return imageSize ?? fallback;
    if (type === "video") return videoSize ?? fallback;
    return fallback;
});

const getAccept = computed<string>(() => {
    const { type, imageAccept, videoAccept } = props;
    if (type === "image") {
        return imageAccept || montageUploadConfig.imageAccept.join(",");
    }
    if (type === "video") {
        return videoAccept || montageUploadConfig.videoAccept.join(",");
    }
    return montageUploadConfig.fileAccept.join(",");
});

const getImageResolution = computed(() => {
    if (props.ffmpeg) return undefined;
    return (props.imageResolution ?? montageUploadConfig.imageResolution) as ResolutionRange;
});

const getVideoResolution = computed(() => {
    if (props.ffmpeg) return undefined;
    return (props.videoResolution ?? montageUploadConfig.videoResolution) as ResolutionRange;
});
</script>
