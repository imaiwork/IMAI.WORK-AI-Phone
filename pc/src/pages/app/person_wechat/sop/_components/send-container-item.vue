<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center gap-2">
            <img src="@/assets/images/date.png" class="w-6 h-6" />
            <span class="text-primary font-bold">第{{ item.day }}天</span>
        </div>
        <div
            v-for="(data, vIndex) in item.list"
            :key="vIndex"
            class="border border-solid border-primary-light-9 rounded-xl p-4 min-h-[100px] relative pt-10">
            <div class="absolute top-0 left-0 bg-primary rounded-tl-xl rounded-br-xl">
                <div class="flex items-center gap-2 px-3 py-1">
                    <Icon name="local-icon-send_plane_fill" color="#fff"></Icon>
                    <span class="text-white text-xs">{{ data.push_time }}</span>
                </div>
            </div>
            <div class="absolute right-2 top-2 z-20">
                <ElButton type="danger" size="small" @click="emit('delete', data.content_id)">
                    <Icon name="el-icon-Delete" color="#fff"></Icon>
                    <span class="text-white text-xs">删除</span>
                </ElButton>
                <ElButton v-if="canEdit(data)" type="primary" size="small" @click="emit('edit', data.content_id)">
                    <Icon name="el-icon-Edit" color="#fff"></Icon>
                    <span class="text-white text-xs">编辑</span>
                </ElButton>
                <ElButton type="primary" size="small" @click="handleFoldContent(data.push_time_id, vIndex)">
                    <Icon name="el-icon-Fold" color="#fff"></Icon>
                    <span class="text-white text-xs">折叠/展开</span>
                </ElButton>
            </div>
            <div
                class="h-full flex flex-col gap-2 transition-all duration-500 ease-in-out transform origin-top"
                :class="[
                    foldIndex.includes(`${data.push_time_id}-${vIndex}`)
                        ? 'opacity-0 max-h-0 scale-y-95'
                        : 'opacity-100 max-h-[2000px] scale-y-100',
                ]"
                v-if="data.content_list.length">
                <div v-for="({ content, type }, cIndex) in data.content_list" :key="cIndex">
                    <div
                        class="text-xs text-[#8A8C99] line-clamp-2 content-wrapper"
                        v-if="type == MaterialTypeEnum.TEXT">
                        <div class="w-[80px] flex-shrink-0">
                            <ElTag>【文本】</ElTag>
                        </div>
                        <span class="mt-[3px]">{{ content }}</span>
                    </div>
                    <div v-if="type == MaterialTypeEnum.IMAGE" class="content-wrapper">
                        <div class="w-[80px] flex-shrink-0">
                            <ElTag>【图片】</ElTag>
                        </div>
                        <ElImage
                            :src="content"
                            :preview-src-list="[content]"
                            preview-teleported
                            class="max-w-[128px] rounded-xl"
                            lazy />
                    </div>
                    <div v-if="type == MaterialTypeEnum.VIDEO" class="content-wrapper">
                        <div class="w-[80px] flex-shrink-0">
                            <ElTag>【视频】</ElTag>
                        </div>
                        <div class="flex-1 relative">
                            <video :src="content" class="w-full rounded-lg"></video>
                            <div
                                class="absolute top-[50%] left-[50%] translate-x-[-50%] translate-y-[-50%]"
                                @click="handlePlayVideo(content)">
                                <play-btn />
                            </div>
                        </div>
                    </div>
                    <div v-if="type == MaterialTypeEnum.MINI_PROGRAM" class="content-wrapper">
                        <div class="w-[80px] flex-shrink-0">
                            <ElTag>【小程序】</ElTag>
                        </div>
                        <div class="w-[50%] h-[300px]">
                            <MiniProgramCard :title="content.name" :pic="content.pic" :link="content.link" />
                        </div>
                    </div>
                    <div v-if="type == MaterialTypeEnum.LINK" class="content-wrapper">
                        <div class="w-[80px] flex-shrink-0">
                            <ElTag>【链接】</ElTag>
                        </div>
                        <div class="w-[50%]">
                            <LinkCard :title="content.name" :desc="content.desc" :img="content.img" />
                        </div>
                    </div>
                    <div v-if="type == MaterialTypeEnum.FILE" class="content-wrapper">
                        <div class="w-[80px] flex-shrink-0">
                            <ElTag>【文件】</ElTag>
                        </div>
                        <FileCard :name="content.name" :url="content.url" :icon-size="32" />
                    </div>
                </div>
            </div>
            <div
                class="h-full flex items-center justify-center mt-8 text-gray-500"
                v-if="foldIndex.includes(`${data.push_time_id}-${vIndex}`)">
                内容被折叠
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

<style scoped>
.content-wrapper {
    @apply h-full bg-primary-light-9 flex p-2 rounded-lg;
}
</style>
