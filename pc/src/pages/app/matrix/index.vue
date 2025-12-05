<template>
    <div class="p-4 flex gap-[10px] h-full bg-app-bg-1">
        <Sidebar
            :sidebar="getSidebar"
            :sidebarIndex="sidebarIndex"
            :theme="ThemeEnum.DARK"
            @update:sidebarIndex="getSliderIndex" />
        <div class="grow overflow-hidden">
            <component :is="getComponents" @update:sidebarIndex="updateSliderIndex"></component>
        </div>
    </div>
</template>
<script setup lang="ts">
import { AppKeyEnum, ThemeEnum, appKeyNameMap } from "@/enums/appEnums";
import Sidebar from "../_components/sidebar.vue";
import { SidebarTypeEnum } from "./_enums";
import useSidebar from "../_hooks/useSidebar";
import Home from "./_pages/home/index.vue";
import Create from "./_pages/create/index.vue";
import Publish from "./_pages/publish/index.vue";
import ImageTask from "./_pages/image_task/index.vue";
import DhCreation from "./_pages/dh_creation/index.vue";
import MaterialLibrary from "./_pages/material_library/index.vue";
import GenerateVideo from "./_pages/generate_video/index.vue";
import CopywritingLibrary from "./_pages/copywriting_library/index.vue";

const { sidebar, sidebarIndex, getComponents, getSliderIndex, updateSliderIndex } = useSidebar();

sidebar.value = [
    { name: "快速开始", icon: "menu_create", components: markRaw(Home), type: SidebarTypeEnum.QUICK_START },
    {
        name: "去发布",
        icon: "menu_video_task",
        components: markRaw(Create),
        type: SidebarTypeEnum.CREATE,
    },
    {
        name: "我的发布",
        icon: "menu_image_task",
        components: markRaw(Publish),
        type: SidebarTypeEnum.ME_PUBLISH,
    },
    {
        name: "发布混剪任务",
        icon: "menu_mix_task",
        components: "",
        type: SidebarTypeEnum.PUBLISH_MIX_TASK,
        disabled: true,
    },
    {
        name: "数字人创作",
        icon: "menu_digital_human",
        components: markRaw(DhCreation),
        type: SidebarTypeEnum.DIGITAL_HUMAN_CREATION,
    },
    {
        name: "图文创作",
        icon: "menu_image_creation",
        components: "",
        type: SidebarTypeEnum.IMAGE_CREATION,
        disabled: true,
    },
    {
        name: "混剪任务创作",
        icon: "menu_mix_creation",
        components: "",
        type: SidebarTypeEnum.MIX_TASK_CREATION,
        disabled: true,
    },
    {
        name: "素材库",
        icon: "menu_material_library",
        components: markRaw(MaterialLibrary),
        type: SidebarTypeEnum.MATERIAL_LIBRARY,
    },
    {
        name: "生成视频",
        icon: "menu_generate_video",
        components: markRaw(GenerateVideo),
        type: SidebarTypeEnum.GENERATE_VIDEO,
    },
    {
        name: "文案库",
        icon: "menu_copywriting_library",
        components: markRaw(CopywritingLibrary),
        type: SidebarTypeEnum.COPYWRITING_LIBRARY,
    },
];

enum SidebarGroupEnum {
    QUICK_START = "快速开始",
    PUBLISH_TASK = "社媒平台运营",
    MATRIX_TASK = "矩阵创作",
    MATERIAL_LIBRARY = "内容管理",
}

const getSidebar = computed(() => {
    const groupedItems = [];

    sidebar.value.forEach((item) => {
        let group;

        if (item.type === SidebarTypeEnum.QUICK_START) {
            group = groupedItems.find((g) => g.type === SidebarGroupEnum.QUICK_START) || {
                ...item,
            };
            groupedItems.push(group);
        } else if (
            [SidebarTypeEnum.CREATE, SidebarTypeEnum.PUBLISH_IMAGE_TASK, SidebarTypeEnum.PUBLISH_MIX_TASK].includes(
                item.type
            )
        ) {
            group = groupedItems.find((g) => g.title === SidebarGroupEnum.PUBLISH_TASK) || {
                title: SidebarGroupEnum.PUBLISH_TASK,
                children: [],
            };
            group.children.push(item);
            if (!groupedItems.includes(group)) {
                groupedItems.push(group);
            }
        } else if (
            [
                SidebarTypeEnum.DIGITAL_HUMAN_CREATION,
                SidebarTypeEnum.IMAGE_CREATION,
                SidebarTypeEnum.MIX_TASK_CREATION,
            ].includes(item.type)
        ) {
            group = groupedItems.find((g) => g.title === SidebarGroupEnum.MATRIX_TASK) || {
                title: SidebarGroupEnum.MATRIX_TASK,
                children: [],
            };
            group.children.push(item);
            if (!groupedItems.includes(group)) {
                groupedItems.push(group);
            }
        } else if (
            [
                SidebarTypeEnum.MATERIAL_LIBRARY,
                SidebarTypeEnum.GENERATE_VIDEO,
                SidebarTypeEnum.COPYWRITING_LIBRARY,
            ].includes(item.type)
        ) {
            group = groupedItems.find((g) => g.title === SidebarGroupEnum.MATERIAL_LIBRARY) || {
                title: SidebarGroupEnum.MATERIAL_LIBRARY,
                children: [],
            };
            group.children.push(item);
            if (!groupedItems.includes(group)) {
                groupedItems.push(group);
            }
        }
    });
    return groupedItems;
});

definePageMeta({
    layout: "base",
    title: appKeyNameMap[AppKeyEnum.MATRIX],
    key: AppKeyEnum.MATRIX,
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
</style>
