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
import Task from "./_pages/task/index.vue";
import Auto from "./_pages/auto/index.vue";
import Flow from "./_pages/flow/index.vue";
import Board from "./_pages/board/index.vue";
import Setting from "./_pages/setting/index.vue";
import useSidebar from "../../_hooks/useSidebar";
import { SidebarTypeEnum } from "./_enums";
const { sidebar, sidebarIndex, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [
    { name: "任务列表", icon: "lists", components: markRaw(Task), type: SidebarTypeEnum.TASK },
    { name: "自动SOP", icon: "auto", components: markRaw(Auto), type: SidebarTypeEnum.AUTO_SOP },
    { name: "流程管理", icon: "flow", components: markRaw(Flow), type: SidebarTypeEnum.FLOW },
    { name: "流程看板", icon: "board", components: markRaw(Board), type: SidebarTypeEnum.BOARD },
    { name: "打招呼", icon: "setting", components: markRaw(Setting), type: SidebarTypeEnum.SETTING },
];

definePageMeta({
    layout: "wechat",
});
</script>

<style scoped></style>
