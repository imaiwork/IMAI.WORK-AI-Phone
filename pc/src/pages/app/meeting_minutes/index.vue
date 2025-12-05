<template>
    <div class="flex h-full">
        <Sidebar :sidebar="sidebar" :sidebarIndex="sidebarIndex" @update:sidebarIndex="getSliderIndex" />
        <div class="grow overflow-hidden">
            <component :is="getComponents"></component>
        </div>
    </div>
</template>

<script setup lang="ts">
import { AppKeyEnum, appKeyNameMap } from "@/enums/appEnums";
import Sidebar from "../_components/sidebar.vue";
import Home from "./_pages/home/index.vue";
import Record from "./_pages/record/index.vue";
import useSidebar from "../_hooks/useSidebar";

const { sidebar, sidebarIndex, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [
    { name: "首页", icon: "home", components: markRaw(Home), type: 1 },
    {
        name: "会议记录",
        icon: "history",
        components: markRaw(Record),
        type: 2,
    },
];

definePageMeta({
    layout: "base",
    title: appKeyNameMap[AppKeyEnum.MEETING_MINUTES],
    key: AppKeyEnum.MEETING_MINUTES,
});
</script>

<style scoped lang="scss">
.search {
    :deep(.el-input__wrapper) {
        @apply pl-10 py-2;
    }
}
:deep(.el-card__body) {
    height: 100%;
}
</style>
