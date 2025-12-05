<template>
    <div class="p-4 flex gap-4 h-full">
        <Sidebar :sidebar="sidebar" :sidebarIndex="sidebarIndex" @update:sidebarIndex="getSliderIndex" />
        <div class="grow overflow-hidden">
            <component :is="getComponents"></component>
        </div>
    </div>
</template>
<script setup lang="ts">
import { AppKeyEnum, appKeyNameMap } from "@/enums/appEnums";
import Sidebar from "../_components/sidebar.vue";
import Chat from "./_pages/chat/index.vue";
import Account from "./_pages/account/index.vue";
import Record from "./_pages/record/index.vue";
import useSidebar from "../_hooks/useSidebar";

const { sidebar, sidebarIndex, routerParams, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [
    // { name: "智能聊天", icon: "message", components: markRaw(Chat), type: 1 },
    { name: "账号管理", icon: "account_box", components: markRaw(Account), type: 1 },
    { name: "添加记录", icon: "record", components: markRaw(Record), type: 2 },
];

definePageMeta({
    layout: "base",
    title: appKeyNameMap[AppKeyEnum.SERVICE],
    key: AppKeyEnum.SERVICE,
});
</script>

<style scoped></style>
