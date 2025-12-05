<template>
    <div class="p-4 flex gap-[10px] h-full">
        <Sidebar
            :sidebar="sidebar"
            :sidebarIndex="sidebarIndex"
            :theme="ThemeEnum.LIGHT"
            @update:sidebarIndex="getSliderIndex" />
        <div class="grow overflow-hidden">
            <component :is="getComponents"></component>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ThemeEnum } from "@/enums/appEnums";
import Sidebar from "@/pages/app/_components/sidebar.vue";
import useSidebar from "@/pages/app/_hooks/useSidebar";
import { SidebarTypeEnum } from "../_enums";
import Content from "./_pages/content/index.vue";
import HitTest from "./_pages/hit_test/index.vue";
import Setting from "./_pages/setting/index.vue";

const route = useRoute();
const query = searchQueryToObject();

const { sidebar, sidebarIndex, getComponents, residentParams, getSliderIndex } = useSidebar();

sidebar.value = [
    {
        name: "文档内容",
        type: SidebarTypeEnum.CONTENT,
        components: markRaw(Content),
        icon: "menu_content",
    },
    {
        name: "搜索测试",
        type: SidebarTypeEnum.HIT_TEST,
        components: markRaw(HitTest),
        icon: "menu_search",
    },
    {
        name: "设置",
        type: SidebarTypeEnum.SETTING,
        components: markRaw(Setting),
        icon: "menu_setting",
    },
];

watch(
    () => route.query,
    () => {
        residentParams.value = {
            kn_type: query.kn_type,
            kn_name: query.kn_name,
            index_id: query.index_id || undefined,
            category_id: query.category_id || undefined,
        };
    },
    { immediate: true }
);

definePageMeta({
    layout: "base",
});
</script>

<style scoped></style>
