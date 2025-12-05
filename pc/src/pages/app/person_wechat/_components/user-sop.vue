<template>
    <DynamicScroller :items="list" :min-item-size="100" key-field="date">
        <template #default="{ item, index, active }">
            <DynamicScrollerItem :item="item" :active="active" :size-dependencies="[item.date, item.log]">
                <div class="flex flex-col gap-4 mb-4 pb-4">
                    <div class="flex items-center gap-2">
                        <img src="@/assets/images/date.png" class="w-6 h-6" />
                        <span class="text-primary font-bold">{{ item.date }}</span>
                    </div>
                    <div
                        v-for="(value, vIndex) in item.log"
                        :key="vIndex"
                        class="border border-solid border-primary-light-9 rounded-xl p-4 min-h-[100px] relative pt-10"
                        :class="{ 'bg-[#F3F3F3]': value.status != 0 }">
                        <div class="absolute top-0 left-0 bg-primary rounded-tl-xl rounded-br-xl">
                            <div class="flex items-center gap-2 px-3 py-1">
                                <Icon name="local-icon-send_plane_fill" color="#fff"></Icon>
                                <span class="text-white text-xs">{{ value.push_real_time }}</span>
                                <span class="text-white text-xs">
                                    ({{ value.status == 0 ? "待推送" : value.status == 1 ? "推送成功" : "推送失败" }})
                                </span>
                            </div>
                        </div>
                        <div class="absolute right-2 top-2 z-20">
                            <ElButton
                                type="danger"
                                size="small"
                                @click="emit('delete', value.id)"
                                v-if="value.status != 0">
                                <Icon name="el-icon-Delete" color="#fff"></Icon>
                                <span class="text-white text-xs">删除</span>
                            </ElButton>
                            <ElButton type="primary" size="small" @click="handleFoldContent(value.id, vIndex)">
                                <Icon name="el-icon-Fold" color="#fff"></Icon>
                                <span class="text-white text-xs">折叠/展开</span>
                            </ElButton>
                        </div>
                        <div class="absolute right-2 bottom-2 text-[#474747]">来源：{{ value.push_name }}</div>
                        <div
                            class="h-full flex flex-col gap-2 overflow-hidden transition-all duration-500 ease-in-out transform origin-top mb-4"
                            :class="[
                                foldIndex.includes(`${value.id}-${vIndex}`)
                                    ? 'opacity-0 max-h-0 scale-y-95'
                                    : 'opacity-100 max-h-[2000px] scale-y-100',
                            ]"
                            v-if="value.content">
                            <div v-for="({ content, type }, cIndex) in value.content" :key="cIndex">
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
                                        class="w-[50%] rounded-xl" />
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
                                        <MiniProgramCard
                                            :title="content.name"
                                            :pic="content.pic"
                                            :link="content.link" />
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
                            class="h-full flex items-center justify-center mt-4 text-gray-500"
                            v-if="foldIndex.includes(`${value.id}-${vIndex}`)">
                            内容被折叠
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
