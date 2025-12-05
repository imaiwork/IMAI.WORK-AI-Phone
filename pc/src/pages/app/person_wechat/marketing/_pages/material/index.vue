<template>
    <div class="w-full h-full flex flex-col gap-4">
        <div class="flex items-center gap-10 bg-white h-[52px] p-4 rounded-xl" v-if="mode == 'page'">
            <div
                class="flex items-center gap-2 cursor-pointer"
                v-for="item in typeTabs"
                :key="item.type"
                :class="{ 'text-primary': currentCate === item.type }"
                @click="handleChangeType(item.type)">
                <Icon :name="`local-icon-${item.icon}`" :size="20" />
                <span class="text-lg">{{ item.name }}</span>
            </div>
        </div>
        <div class="rounded-xl grow min-h-0 flex gap-4">
            <div class="w-[236px] bg-white rounded-xl flex-shrink-0">
                <Sidebar />
            </div>
            <div class="rounded-xl grow min-h-0 bg-white">
                <Container :mode="mode" :limit="limit" @update:select="handleSelect" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
import Sidebar from "./sidebar.vue";
import Container from "./container.vue";
import { useCate, useFile } from "../../_hooks/useMaterial";
const props = withDefaults(
    defineProps<{
        mode?: "page" | "popup";
        type?: MaterialTypeEnum;
        limit?: number;
    }>(),
    {
        mode: "page",
        type: MaterialTypeEnum.IMAGE,
        limit: 9,
    }
);

const emit = defineEmits<{
    (e: "update:select", value: Record<string, any>): void;
}>();

const { currentCate, getCateLists } = useCate();
const { pager, selectItem, queryParams, getLists } = useFile();
const typeTabs = ref<any[]>([
    {
        name: "图片素材",
        icon: "image_ai_fill",
        type: MaterialTypeEnum.IMAGE,
    },
    {
        name: "视频素材",
        icon: "video_ai_fill",
        type: MaterialTypeEnum.VIDEO,
    },
    {
        name: "链接素材",
        icon: "link_fill",
        type: MaterialTypeEnum.LINK,
    },
    {
        name: "小程序素材",
        icon: "mini_program_fill",
        type: MaterialTypeEnum.MINI_PROGRAM,
    },
    {
        name: "文件素材",
        icon: "folder_fill",
        type: MaterialTypeEnum.FILE,
    },
]);

const handleChangeType = (type: (typeof MaterialTypeEnum)[keyof typeof MaterialTypeEnum]) => {
    if (currentCate.value === type) return;
    pager.lists = [];
    selectItem.value = [];
    currentCate.value = type;
    queryParams.file_type = type;
    getCateLists();
    getLists();
};

const handleSelect = (value: any[]) => {
    if (value && value.length) {
        if (props.limit != 1) {
            emit("update:select", value);
        } else {
            emit("update:select", value[0]);
        }
    } else {
        emit("update:select", {});
    }
};

watch(
    () => props.type,
    (value) => {
        handleChangeType(value);
    },
    {
        immediate: true,
    }
);
</script>

<style scoped></style>
