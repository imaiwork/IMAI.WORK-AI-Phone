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
import Material from "./_pages/material/index.vue";
import useSidebar from "../../_hooks/useSidebar";

const { sidebar, sidebarIndex, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [{ name: "素材库", icon: "image_circle", components: markRaw(Material), type: 1 }];

definePageMeta({
    layout: "wechat",
});
</script>

<style scoped></style>
