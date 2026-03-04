<template>
    <div class="flex flex-col gap-6">
        <div class="flex items-center gap-3 px-2">
            <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center">
                <img src="@/assets/images/date.png" class="w-6 h-6" />
            </div>
            <div class="flex flex-col">
                <span class="text-primary font-medium text-lg tracking-tight">第 {{ item.day }} 天</span>
                <span class="text-slate-400 text-[11px] font-medium uppercase tracking-widest">Push Schedule</span>
            </div>
        </div>

        <div v-for="(data, vIndex) in item.list" :key="vIndex" class="push-card group">
            <div
                class="absolute top-0 left-0 right-0 h-10 bg-slate-50 border-b border-slate-100 flex items-center justify-between px-4 rounded-t-[20px]">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
                    <span class="text-slate-600 font-black text-xs tracking-wider">{{ data.push_time }} 推送</span>
                </div>

                <div class="flex items-center gap-1">
                    <button
                        class="icon-btn hover:bg-[#0065fb]/10 hover:text-primary"
                        @click="handleFoldContent(data.push_time_id, vIndex)">
                        <Icon
                            :name="
                                foldIndex.includes(`${data.push_time_id}-${vIndex}`)
                                    ? 'el-icon-ArrowDown'
                                    : 'el-icon-ArrowUp'
                            "
                            :size="14"></Icon>
                    </button>
                    <button
                        v-if="canEdit(data)"
                        class="icon-btn hover:bg-[#0065fb]/10 hover:text-primary"
                        @click="emit('edit', data.content_id)">
                        <Icon name="el-icon-Edit" :size="14"></Icon>
                    </button>
                    <button
                        class="icon-btn hover:bg-red-50 hover:text-red-500"
                        @click="emit('delete', data.content_id)">
                        <Icon name="el-icon-Delete" :size="14"></Icon>
                    </button>
                </div>
            </div>

            <div
                class="mt-10 p-5 transition-all duration-500 ease-in-out overflow-hidden"
                :class="[
                    foldIndex.includes(`${data.push_time_id}-${vIndex}`)
                        ? 'max-h-0 opacity-0 py-0'
                        : 'max-h-[2000px] opacity-100',
                ]"
                v-if="data.content_list.length">
                <div class="flex flex-col gap-4">
                    <div v-for="({ content, type }, cIndex) in data.content_list" :key="cIndex" class="content-row">
                        <div class="type-badge">
                            <span class="relative z-10">{{ getTypeName(type) }}</span>
                            <div class="absolute inset-0 bg-[#0065fb]/5 rounded-md"></div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div
                                v-if="type == MaterialTypeEnum.TEXT"
                                class="text-slate-700 text-[14px] leading-relaxed font-medium">
                                {{ content }}
                            </div>

                            <div v-if="type == MaterialTypeEnum.IMAGE" class="flex">
                                <ElImage
                                    :src="content"
                                    :preview-src-list="[content]"
                                    preview-teleported
                                    class="max-w-[160px] rounded-xl border border-slate-100 transition-shadow"
                                    lazy />
                            </div>

                            <div
                                v-if="type == MaterialTypeEnum.VIDEO"
                                class="max-w-[240px] relative rounded-xl overflow-hidden shadow-sm group">
                                <video :src="content" class="w-full h-full object-cover"></video>
                                <div
                                    class="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer"
                                    @click="handlePlayVideo(content)">
                                    <div
                                        class="w-10 h-10 rounded-full bg-[#ffffff]/20 backdrop-blur-md border border-[#ffffff]/40 flex items-center justify-center">
                                        <play-btn :icon-size="24" />
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
                                class="max-w-md">
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
                class="py-4 text-center text-slate-400 text-xs font-medium tracking-widest flex items-center justify-center gap-2"
                v-if="foldIndex.includes(`${data.push_time_id}-${vIndex}`)">
                <Icon name="el-icon-Loading" class="animate-spin" v-if="false" />
                CONTENT FOLDED
            </div>
        </div>
    </div>
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { MaterialTypeEnum } from "../../_enums";
import MiniProgramCard from "../../_components/mini-program-card.vue";
import LinkCard from "../../_components/link-card.vue";
import FileCard from "../../_components/file-card.vue";
const props = defineProps<{
    item: any;
}>();

const emit = defineEmits<{
    (e: "edit", id: string | number): void;
    (e: "delete", id: string | number): void;
}>();

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

// 根据当前时间判断是否可以编辑, 如果当前时间大于推送时间, 则可以不可以编辑
const canEdit = (data: any) => {
    const { push_real_day, push_time } = data;
    return dayjs(`${push_real_day} ${push_time}`).isAfter(dayjs());
};

const foldIndex = ref<string[]>([]);

const handleFoldContent = (pushTimeId: number, index: number) => {
    if (foldIndex.value.includes(`${pushTimeId}-${index}`)) {
        foldIndex.value = foldIndex.value.filter((item) => item !== `${pushTimeId}-${index}`);
    } else {
        foldIndex.value.push(`${pushTimeId}-${index}`);
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

<style scoped lang="scss">
.push-card {
    @apply border border-slate-100 rounded-[20px] bg-white relative transition-all duration-300;
    &:hover {
        @apply shadow-light shadow-[#0065fb]/5 border-[#0065fb]/20;
    }
}

.content-row {
    @apply flex gap-4 p-3 rounded-2xl bg-slate-50 border border-[transparent] hover:border-slate-200 hover:bg-white transition-all;
}

.type-badge {
    @apply w-16 h-7 flex-shrink-0 flex items-center justify-center text-[11px] font-black text-primary relative;
}

.icon-btn {
    @apply w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 transition-all;
}
</style>
