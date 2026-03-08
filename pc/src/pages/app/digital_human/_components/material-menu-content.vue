<template>
    <DefineTemplate v-slot="{ type }">
        <upload
            class="w-full"
            show-progress
            :type="type"
            :accept="
                type === 'video' ? montageUploadConfig.videoAccept.join(',') : montageUploadConfig.imageAccept.join(',')
            "
            :data="{ ffmpeg: 1 }"
            :show-file-list="false"
            :max-size="type === 'video' ? montageUploadConfig.videoSize : montageUploadConfig.imageSize"
            :min-duration="montageUploadConfig.videoDuration[0]"
            :max-duration="montageUploadConfig.videoDuration[1]"
            @change="$emit('action', { type: `upload-${type}`, event: $event })">
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

    <UseTemplate type="image" />
    <UseTemplate type="video" />

    <div class="flex items-center gap-2 px-3 py-1.5">
        <div class="flex-1 h-px bg-slate-100"></div>
        <span class="text-[10px] font-black text-slate-300 uppercase tracking-wider">素材库</span>
        <div class="flex-1 h-px bg-slate-100"></div>
    </div>

    <div
        v-for="item in libraryItems"
        :key="item.action"
        class="w-full flex items-center gap-3 p-3 rounded-xl cursor-pointer group/item transition-all"
        :class="item.hoverBg"
        @click="$emit('action', { type: item.action })">
        <div
            class="w-10 h-10 rounded-lg flex items-center justify-center group-hover/item:scale-110 transition-transform shrink-0"
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
import { createReusableTemplate } from "@vueuse/core";
import { montageUploadConfig } from "@/pages/app/digital_human/_config";

defineEmits<{ action: [payload: { type: string; event?: any }] }>();

const [DefineTemplate, UseTemplate] = createReusableTemplate<{ type: "image" | "video" }>();

const libraryItems = [
    {
        action: "library-image",
        label: "从图片素材库选择",
        subLabel: "Image Library",
        icon: "el-icon-Picture",
        hoverBg: "hover:bg-orange-50",
        iconBg: "bg-orange-50 text-orange-500",
    },
    {
        action: "library-video",
        label: "从视频素材库选择",
        subLabel: "Video Library",
        icon: "el-icon-VideoCamera",
        hoverBg: "hover:bg-blue-50",
        iconBg: "bg-blue-50 text-primary",
    },
    {
        action: "history",
        label: "从创作库选择",
        subLabel: "Creation History",
        icon: "el-icon-Clock",
        hoverBg: "hover:bg-purple-50",
        iconBg: "bg-purple-50 text-purple-500",
    },
];
</script>
