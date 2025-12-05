<template>
    <div class="p-4 flex gap-4 h-full">
        <Sidebar :sidebar="sidebar" :sidebarIndex="sidebarIndex" @update:sidebarIndex="getSliderIndex" />
        <div class="grow overflow-hidden">
            <component :is="getComponents"></component>
        </div>
    </div>
</template>

<script setup lang="ts">
import Sidebar from "../../_components/sidebar.vue";
import useSidebar from "../../_hooks/useSidebar";
import Index from "./_pages/index/index.vue";
import Task from "./_pages/task/index.vue";
import Setting from "./_pages/setting/index.vue";

const { sidebar, sidebarIndex, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [
    { name: "朋友圈", icon: "camera_lens_fill", components: markRaw(Index), type: 1 },
    { name: "任务列表", icon: "task", components: markRaw(Task), type: 2 },
    { name: "自动评赞设置", icon: "setting", components: markRaw(Setting), type: 3 },
];

definePageMeta({
    layout: "wechat",
});
</script>

<style scoped></style>
