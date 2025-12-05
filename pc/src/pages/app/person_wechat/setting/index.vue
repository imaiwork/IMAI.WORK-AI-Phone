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
import DeviceLists from "./_pages/device/lists.vue";
import WechatLists from "./_pages/wechat/lists.vue";
import useSidebar from "../../_hooks/useSidebar";

const { sidebar, sidebarIndex, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [
    { name: "设备管理", icon: "device", components: markRaw(DeviceLists), type: 1 },
    { name: "微信管理", icon: "wechat3", components: markRaw(WechatLists), type: 2 },
];

definePageMeta({
    layout: "wechat",
});
</script>

<style scoped></style>
