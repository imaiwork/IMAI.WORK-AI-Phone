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
import Robot from "./_pages/robot/index.vue";
import SettingReply from "./_pages/setting/reply.vue";
import SettingFriend from "./_pages/setting/friend.vue";
import Tag from "./_pages/tag/index.vue";
import TagManual from "./_pages/tag/manual.vue";
import useSidebar from "../../_hooks/useSidebar";

const { sidebar, sidebarIndex, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [
    // { name: "机器人管理", icon: "robot", components: markRaw(Robot), type: 1 },
    { name: "自动标签管理", icon: "price_tag", components: markRaw(Tag), type: 1 },
    {
        name: "手动设置标签",
        icon: "draft",
        components: markRaw(TagManual),
        type: 2,
    },
    // { name: "回复策略", icon: "setting", components: markRaw(SettingReply), type: 3 },
    {
        name: "自动通过好友策略",
        icon: "setting",
        components: markRaw(SettingFriend),
        type: 4,
    },
];

definePageMeta({
    layout: "wechat",
});
</script>

<style scoped></style>
