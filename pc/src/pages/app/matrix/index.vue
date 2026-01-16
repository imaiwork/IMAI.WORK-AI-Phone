<template>
    <div class="px-4 pb-4 flex gap-[10px] h-full">
        <Sidebar :sidebar="getSidebar" :sidebarIndex="sidebarIndex" @update:sidebarIndex="getSliderIndex" />
        <div class="grow min-h-0 min-w-0">
            <component :is="getComponents" @update:sidebarIndex="updateSliderIndex"></component>
        </div>
    </div>
</template>
<script setup lang="ts">
import Sidebar from "../_components/sidebar.vue";
import { SidebarTypeEnum } from "./_enums";
import useSidebar from "../_hooks/useSidebar";
import Home from "./_pages/home/index.vue";
import Create from "./_pages/create/index.vue";
import Publish from "./_pages/publish/index.vue";
import ImageTask from "./_pages/image_task/index.vue";
import DhCreation from "./_pages/dh_creation/index.vue";
import MaterialLibrary from "./_pages/material_library/index.vue";
import VideoWorks from "./_pages/video_works/index.vue";
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
        name: "视频作品",
        icon: "menu_generate_video",
        components: markRaw(VideoWorks),
        type: SidebarTypeEnum.VIDEO_WORKS,
    },
    {
        name: "文案库",
        icon: "menu_copywriting_library",
        components: markRaw(CopywritingLibrary),
        type: SidebarTypeEnum.COPYWRITING_LIBRARY,
    },
];

enum SidebarGroupEnum {
    PUBLISH_TASK = "社媒平台运营",
    MATRIX_TASK = "矩阵创作",
    MATERIAL_LIBRARY = "内容管理",
}

const getSidebar = computed(() => {
    const typeToGroupMap = {
        [SidebarTypeEnum.QUICK_START]: null,
        [SidebarTypeEnum.CREATE]: SidebarGroupEnum.PUBLISH_TASK,
        [SidebarTypeEnum.PUBLISH_IMAGE_TASK]: SidebarGroupEnum.PUBLISH_TASK,
        [SidebarTypeEnum.PUBLISH_MIX_TASK]: SidebarGroupEnum.PUBLISH_TASK,
        [SidebarTypeEnum.DIGITAL_HUMAN_CREATION]: SidebarGroupEnum.MATRIX_TASK,
        [SidebarTypeEnum.IMAGE_CREATION]: SidebarGroupEnum.MATERIAL_LIBRARY,
        [SidebarTypeEnum.MIX_TASK_CREATION]: SidebarGroupEnum.MATERIAL_LIBRARY,
    };

    const result = [];
    const groupTracker = new Map();

    sidebar.value.forEach((item) => {
        const groupTitle = typeToGroupMap[item.type];

        if (groupTitle === null || groupTitle === undefined) {
            result.push(item);
        } else {
            let group = groupTracker.get(groupTitle);
            if (!group) {
                group = { title: groupTitle, children: [] };
                result.push(group);
                groupTracker.set(groupTitle, group);
            }
            group.children.push(item);
        }
    });

    return result;
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
</style>
