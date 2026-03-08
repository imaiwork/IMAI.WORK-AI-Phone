<template>
    <div class="px-4 pb-4 w-full flex gap-[10px] h-full">
        <Sidebar :sidebar="getSidebar" :sidebar-index="sidebarIndex" @update:sidebar-index="getSliderIndex" />
        <div class="grow min-h-0 w-full">
            <component :is="getComponents"></component>
        </div>
    </div>
</template>

<script setup lang="ts">
import Sidebar from "../_components/sidebar.vue";
import SzrCreate from "./_pages/szr_create/index.vue";
import MontageCreate from "./_pages/montage_create/index.vue";
import Model from "./_pages/model/index.vue";
import Tone from "./_pages/tone/index.vue";
import Audio from "./_pages/audio/index.vue";
import Video from "./_pages/video/index.vue";
import ModelClone from "./_pages/model/clone.vue";
import useSidebar from "../_hooks/useSidebar";
import { SidebarTypeEnum } from "./_enums/index";

const { sidebar, sidebarIndex, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [
    { name: "数字人纯口播视频", components: markRaw(SzrCreate), type: SidebarTypeEnum.DIGITAL_HUMAN_PURE_BOUQUET },
    {
        name: "数字人口播混剪",
        components: markRaw(MontageCreate),
        type: SidebarTypeEnum.BOUQUET_MIXING,
    },
    { name: "真人口播视频混剪", components: markRaw(Tone), type: SidebarTypeEnum.REAL_PERSON_MIXING, disabled: true },
    { name: "素材混剪神器", components: markRaw(Audio), type: SidebarTypeEnum.MATERIAL_MIXING, disabled: true },
    { name: "新闻体视频", components: markRaw(Video), type: SidebarTypeEnum.NEWS_VIDEO, disabled: true },
    { name: "一句话生成视频", components: markRaw(Audio), type: SidebarTypeEnum.ONE_WORD_VIDEO, disabled: true },
    { name: "声音克隆", components: markRaw(Tone), type: SidebarTypeEnum.VOICE_CLONE },
    { name: "形象克隆", components: markRaw(ModelClone), type: SidebarTypeEnum.ANCHOR_CLONE },
    { name: "我的作品", components: markRaw(Video), type: SidebarTypeEnum.MY_WORKS },
    { name: "我的形象", components: markRaw(Model), type: SidebarTypeEnum.MY_ANCHOR },
];

enum SidebarGroupEnum {
    // 视频创作
    VIDEO_CREATION = "视频创作",
    // 克隆管理
    CLONE_MANAGEMENT = "克隆管理",
    // 我的作品
    MY_WORKS = "我的",
}

const getSidebar = computed(() => {
    const typeToGroupMap = {
        [SidebarTypeEnum.DIGITAL_HUMAN_PURE_BOUQUET]: SidebarGroupEnum.VIDEO_CREATION,
        [SidebarTypeEnum.BOUQUET_MIXING]: SidebarGroupEnum.VIDEO_CREATION,
        [SidebarTypeEnum.REAL_PERSON_MIXING]: SidebarGroupEnum.VIDEO_CREATION,
        [SidebarTypeEnum.MATERIAL_MIXING]: SidebarGroupEnum.VIDEO_CREATION,
        [SidebarTypeEnum.NEWS_VIDEO]: SidebarGroupEnum.VIDEO_CREATION,
        [SidebarTypeEnum.ONE_WORD_VIDEO]: SidebarGroupEnum.VIDEO_CREATION,
        [SidebarTypeEnum.ANCHOR_CLONE]: SidebarGroupEnum.CLONE_MANAGEMENT,
        [SidebarTypeEnum.VOICE_CLONE]: SidebarGroupEnum.CLONE_MANAGEMENT,
        [SidebarTypeEnum.MY_WORKS]: SidebarGroupEnum.MY_WORKS,
        [SidebarTypeEnum.MY_ANCHOR]: SidebarGroupEnum.MY_WORKS,
    };

    const groups = sidebar.value.reduce((acc, item) => {
        const title = typeToGroupMap[item.type];
        if (!title) return acc;

        if (!acc[title]) {
            acc[title] = { title, children: [] };
        }
        acc[title].children.push(item);
        return acc;
    }, {});

    return Object.values(groups);
});
</script>
<style lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
</style>
