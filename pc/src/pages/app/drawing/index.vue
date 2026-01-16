<template>
    <div class="px-4 pb-4 flex gap-[10px] h-full">
        <div class="flex-shrink-0">
            <sidebar :sidebar="getSidebar" :sidebar-index="sidebarIndex" @update:sidebar-index="getSliderIndex" />
        </div>
        <div class="grow min-h-0">
            <div class="min-w-[1000px] h-full flex gap-[10px]">
                <create-panel :type="sidebarIndex" />
                <div class="flex-1">
                    <component :is="getComponents"></component>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { SidebarEnum } from "./_enums";
import Sidebar from "../_components/sidebar.vue";
import CreatePanel from "./_components/create-panel.vue";
import GenerationImage from "./_pages/generation-image/index.vue";
import GoodsImage from "./_pages/goods-image/index.vue";
import FashionImage from "./_pages/fashion-image/index.vue";
import PosterImage from "./_pages/poster-image/index.vue";
import VideoGeneration from "./_pages/video-generation/index.vue";
import useSidebar from "../_hooks/useSidebar";

const { sidebar, sidebarIndex, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [
    {
        icon: "menu_picture",
        name: "图片生成",
        type: SidebarEnum.IMAGE_GENERATION,
        components: markRaw(GenerationImage),
    },
    {
        icon: "menu_goods",
        name: "商品图",
        type: SidebarEnum.GOODS_IMAGE,
        components: markRaw(GoodsImage),
    },
    {
        icon: "menu_fashion",
        name: "服饰图",
        type: SidebarEnum.FASHION_IMAGE,
        components: markRaw(FashionImage),
    },
    {
        icon: "menu_poster",
        name: "海报图",
        type: SidebarEnum.POSTER_IMAGE,
        components: markRaw(PosterImage),
    },
    {
        icon: "menu_video",
        name: "视频生成",
        type: SidebarEnum.VIDEO_GENERATION,
        components: markRaw(VideoGeneration),
    },
];

const getSidebar = computed(() => {
    const groupedItems = [];

    sidebar.value.forEach((item) => {
        let group;

        if (item.type === SidebarEnum.IMAGE_GENERATION) {
            group = groupedItems.find((g) => g.title === "智能图片") || {
                title: "智能图片",
                children: [],
            };
            group.children.push(item);
            if (!groupedItems.includes(group)) {
                groupedItems.push(group);
            }
        } else if ([SidebarEnum.GOODS_IMAGE, SidebarEnum.FASHION_IMAGE, SidebarEnum.POSTER_IMAGE].includes(item.type)) {
            group = groupedItems.find((g) => g.title === "智能设计") || {
                title: "智能设计",
                children: [],
            };
            group.children.push(item);
            if (!groupedItems.includes(group)) {
                groupedItems.push(group);
            }
        } else if ([SidebarEnum.VIDEO_GENERATION].includes(item.type)) {
            group = groupedItems.find((g) => g.title === "智能视频") || {
                title: "智能视频",
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
</script>

<style lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
</style>
