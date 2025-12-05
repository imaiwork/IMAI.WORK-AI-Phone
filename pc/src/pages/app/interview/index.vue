<template>
    <div class="p-4 flex gap-4 h-full">
        <Sidebar :sidebar="sidebar" :sidebarIndex="sidebarIndex" @update:sidebarIndex="getSliderIndex" />
        <div class="grow overflow-hidden">
            <component :is="getComponents" @history="onHistory"></component>
        </div>
    </div>
</template>

<script setup lang="ts">
import Sidebar from "../_components/sidebar.vue";
import Job from "./_pages/job/index.vue";
import Record from "./_pages/record/index.vue";
import useSidebar from "../_hooks/useSidebar";

const { sidebar, sidebarIndex, routerParams, getComponents, pushHistory, getSliderIndex } = useSidebar();

sidebar.value = [
    { name: "岗位列表", icon: "lists", components: markRaw(Job), type: 1 },
    { name: "面试记录", icon: "record", components: markRaw(Record), type: 2 },
];

const onHistory = (id: number) => {
    routerParams.value.id = id;
    sidebarIndex.value = 1;
    pushHistory();
};

definePageMeta({
    layout: "base",
    title: "AI面试",
});
</script>

<style scoped></style>
