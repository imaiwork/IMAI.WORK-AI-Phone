<template>
    <DynamicScroller :items="list" :min-item-size="100" key-field="date" class="h-full pr-2">
        <template #default="{ item, index, active }">
            <DynamicScrollerItem :item="item" :active="active" :size-dependencies="[item.date, item.log]">
                <div class="flex flex-col mb-8 relative">
                    <div class="flex items-center gap-3 mb-6 sticky top-0 z-30 bg-white py-2">
                        <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 flex items-center justify-center shadow-sm">
                            <img src="@/assets/images/date.png" class="w-6 h-6" />
                        </div>
                        <span class="text-[18px] font-medium text-slate-800 tracking-tight">{{ item.date }}</span>
                        <div class="flex-1 h-[1px] bg-gradient-to-r from-slate-100 to-transparent ml-2"></div>
                    </div>

                    <div class="flex flex-col gap-6 pl-5 border-l-2 border-slate-50 ml-5">
                        <div
                            v-for="(value, vIndex) in item.log"
                            :key="vIndex"
                            class="log-card group"
                            :class="{ 'is-completed': value.status != 0 }">
                            <div class="flex items-center justify-between mb-4 border-b border-slate-50 pb-3">
                                <div class="flex items-center gap-2">
                                    <div class="status-dot" :class="`is-status-${value.status}`"></div>
                                    <span class="text-[13px] font-black text-slate-700">{{
                                        value.push_real_time
                                    }}</span>
                                    <span class="status-tag" :class="`is-status-${value.status}`">
                                        {{ value.status == 0 ? "待推送" : value.status == 1 ? "发送成功" : "发送失败" }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <ElButton
                                        v-if="value.status != 0"
                                        type="danger"
                                        link
                                        class="!p-1.5"
                                        @click="emit('delete', value.id)">
                                        <Icon name="el-icon-Delete" :size="16" />
                                    </ElButton>
                                    <ElButton
                                        type="primary"
                                        link
                                        class="!p-1.5"
                                        @click="handleFoldContent(value.id, vIndex)">
                                        <Icon
                                            :name="
                                                foldIndex.includes(`${value.id}-${vIndex}`)
                                                    ? 'el-icon-ArrowDown'
                                                    : 'el-icon-ArrowUp'
                                            "
                                            :size="16" />
                                    </ElButton>
                                </div>
                            </div>

                            <div
                                class="content-container"
                                :class="{ 'is-folded': foldIndex.includes(`${value.id}-${vIndex}`) }"
                                v-if="value.content">
                                <div
                                    v-for="({ content, type }, cIndex) in value.content"
                                    :key="cIndex"
                                    class="mb-4 last:mb-0">
                                    <div class="flex gap-3">
                                        <div class="type-indicator" :class="`type-${type}`">
                                            {{ getTypeName(type) }}
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div
                                                v-if="type == MaterialTypeEnum.TEXT"
                                                class="text-slate-600 text-[14px] leading-relaxed">
                                                {{ content }}
                                            </div>
                                            <div v-if="type == MaterialTypeEnum.IMAGE">
                                                <ElImage
                                                    :src="content"
                                                    :preview-src-list="[content]"
                                                    preview-teleported
                                                    class="w-40 rounded-xl border border-slate-100 hover:scale-[1.02] transition-transform" />
                                            </div>
                                            <div
                                                v-if="type == MaterialTypeEnum.VIDEO"
                                                class="relative w-60 group cursor-pointer"
                                                @click="handlePlayVideo(content)">
                                                <video
                                                    :src="content"
                                                    class="w-full rounded-xl bg-slate-900 aspect-video object-cover"></video>
                                                <div
                                                    class="absolute inset-0 flex items-center justify-center bg-[#000000]/20 rounded-xl group-hover:bg-[#000000]/40 transition-all">
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-[#ffffff]/30 backdrop-blur-md flex items-center justify-center text-white">
                                                        <Icon name="el-icon-CaretRight" :size="24" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                v-if="
                                                    [
                                                        MaterialTypeEnum.MINI_PROGRAM,
                                                        MaterialTypeEnum.LINK,
                                                        MaterialTypeEnum.FILE,
                                                    ].includes(type)
                                                "
                                                class="max-w-[320px]">
                                                <MiniProgramCard
                                                    v-if="type == MaterialTypeEnum.MINI_PROGRAM"
                                                    :title="content.name"
                                                    :pic="content.pic"
                                                    :link="content.link" />
                                                <LinkCard
                                                    v-if="type == MaterialTypeEnum.LINK"
                                                    :title="content.name"
                                                    :desc="content.desc"
                                                    :img="content.img" />
                                                <FileCard
                                                    v-if="type == MaterialTypeEnum.FILE"
                                                    :name="content.name"
                                                    :url="content.url"
                                                    :icon-size="32" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="foldIndex.includes(`${value.id}-${vIndex}`)"
                                class="text-center py-2 text-slate-400 text-xs font-medium bg-slate-50 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors"
                                @click="handleFoldContent(value.id, vIndex)">
                                内容已折叠，点击展开查看详情
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-50 flex justify-between items-center">
                                <span class="text-[11px] text-slate-400 font-medium uppercase tracking-wider"
                                    >Source Plan</span
                                >
                                <span class="text-xs text-slate-500 font-black bg-slate-100 px-2 py-0.5 rounded-md">{{
                                    value.push_name
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </DynamicScrollerItem>
        </template>
    </DynamicScroller>
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { DynamicScroller, DynamicScrollerItem } from "vue-virtual-scroller";
import "vue-virtual-scroller/dist/vue-virtual-scroller.css";
import { MaterialTypeEnum } from "../_enums";
import MiniProgramCard from "./mini-program-card.vue";
import LinkCard from "./link-card.vue";
import FileCard from "./file-card.vue";
const props = defineProps<{
    list: any;
}>();

const emit = defineEmits<{
    (e: "edit", id: string | number): void;
    (e: "delete", id: string | number): void;
}>();

const foldIndex = ref<string[]>([]);

const getTypeName = (type: number) => {
    const map: any = {
        [MaterialTypeEnum.TEXT]: "文本",
        [MaterialTypeEnum.IMAGE]: "图片",
        [MaterialTypeEnum.VIDEO]: "视频",
        [MaterialTypeEnum.MINI_PROGRAM]: "小程序",
        [MaterialTypeEnum.LINK]: "链接",
        [MaterialTypeEnum.FILE]: "文件",
    };
    return map[type] || "素材";
};

const handleFoldContent = (id: number, index: number) => {
    if (foldIndex.value.includes(`${id}-${index}`)) {
        foldIndex.value = foldIndex.value.filter((item) => item !== `${id}-${index}`);
    } else {
        foldIndex.value.push(`${id}-${index}`);
    }
};

const showPreviewVideo = ref(false);
const previewVideoRef = shallowRef();

const handlePlayVideo = async (url: string) => {
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value?.setUrl(url);
};
</script>

<style scoped>
.content-wrapper {
    @apply h-full bg-primary-light-9 flex p-2 rounded-lg;
}
</style>

<style scoped lang="scss">
.log-card {
    @apply bg-white border border-slate-100 rounded-[24px] p-6 transition-all duration-300 relative;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);

    &:hover {
        @apply shadow-light shadow-[#0065fb]/5 border-[#0065fb]/10;
    }

    &.is-completed {
        @apply bg-[#FCFDFF];
    }
}

.status-tag {
    @apply px-2 py-0.5 rounded-md text-[11px] font-black;
    &.is-status-0 {
        @apply bg-blue-50 text-blue-500;
    }
    &.is-status-1 {
        @apply bg-emerald-50 text-emerald-500;
    }
    &.is-status-2 {
        @apply bg-red-50 text-red-500;
    }
}

.status-dot {
    @apply w-2 h-2 rounded-full;
    &.is-status-0 {
        @apply bg-blue-500 animate-pulse;
    }
    &.is-status-1 {
        @apply bg-emerald-500;
    }
    &.is-status-2 {
        @apply bg-red-500;
    }
}

.type-indicator {
    @apply shrink-0 w-14 h-6 rounded-md flex items-center justify-center text-[11px] font-black border;
    &.type-1 {
        @apply border-blue-100 text-blue-500 bg-[#eff6ff]/50;
    }
    &.type-2 {
        @apply border-purple-100 text-purple-500 bg-[#faf5ff]/50;
    }
    &.type-3 {
        @apply border-orange-100 text-orange-500 bg-[#fff7ed]/50;
    }
    @apply border-slate-100 text-slate-400 bg-[#f8fafc]/50;
}

.content-container {
    @apply transition-all duration-500 overflow-hidden;
    &.is-folded {
        @apply max-h-0 opacity-0;
    }
    &:not(.is-folded) {
        @apply max-h-[2000px] opacity-100;
    }
}
</style>
