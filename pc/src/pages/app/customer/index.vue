<template>
    <div class="px-4 pb-4 flex gap-[10px] h-full">
        <Sidebar :sidebar="getSidebar" :sidebar-index="sidebarIndex" @update:sidebar-index="getSliderIndex" />
        <div class="min-h-0 flex-1 min-w-0">
            <component :is="getComponents"></component>
        </div>
    </div>
</template>

<script setup lang="ts">
import Sidebar from "../_components/sidebar.vue";
import CreateTask from "./_pages/create_task/index.vue";
import AutoAddWechat from "./_pages/auto_add_wechat/index.vue";
import ManualAddWechat from "./_pages/manual_add_wechat/index.vue";
import useSidebar from "../_hooks/useSidebar";
import { SidebarTypeEnum } from "./_enums";

const { sidebar, sidebarIndex, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [
    { name: "采集任务", components: markRaw(CreateTask), type: SidebarTypeEnum.AUTO_GET_CUSTOMER },
    { name: "评论获客", components: "", type: SidebarTypeEnum.COMMENT_GET_CUSTOMER, disabled: true },
    { name: "私信获客", components: "", type: SidebarTypeEnum.MESSAGE_GET_CUSTOMER, disabled: true },
    { name: "采集加微", components: markRaw(AutoAddWechat), type: SidebarTypeEnum.AUTO_ADD_WECHAT },
    { name: "批量加微", components: markRaw(ManualAddWechat), type: SidebarTypeEnum.MANUAL_ADD_WECHAT },
];

enum SidebarGroupEnum {
    // 主动获客
    ACTIVE_GET_CUSTOMER = "主动获客",
    // 线索加微
    LEAD_ADD_WECHAT = "线索加微",
}

const getSidebar = computed(() => {
    const typeToGroupMap = {
        [SidebarTypeEnum.AUTO_GET_CUSTOMER]: SidebarGroupEnum.ACTIVE_GET_CUSTOMER,
        [SidebarTypeEnum.COMMENT_GET_CUSTOMER]: SidebarGroupEnum.ACTIVE_GET_CUSTOMER,
        [SidebarTypeEnum.MESSAGE_GET_CUSTOMER]: SidebarGroupEnum.ACTIVE_GET_CUSTOMER,
        [SidebarTypeEnum.AUTO_ADD_WECHAT]: SidebarGroupEnum.LEAD_ADD_WECHAT,
        [SidebarTypeEnum.MANUAL_ADD_WECHAT]: SidebarGroupEnum.LEAD_ADD_WECHAT,
    };

    const groups = sidebar.value.reduce((acc, item) => {
        const title = typeToGroupMap[item.type];
        if (!title) return acc;

        if (!acc[title]) {
            acc[title] = { title, children: [] };
        }
        acc[title].children.push(item);
        return acc;
    }, {});

    return Object.values(groups);
});
</script>
<style lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
</style>
