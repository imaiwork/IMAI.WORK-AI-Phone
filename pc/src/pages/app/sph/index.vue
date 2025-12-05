<template>
    <div class="p-4 flex gap-[10px] h-full bg-app-bg-1">
        <Sidebar
            :sidebar="getSidebar"
            :sidebarIndex="sidebarIndex"
            :theme="ThemeEnum.DARK"
            @update:sidebarIndex="getSliderIndex" />
        <div class="grow overflow-hidden">
            <component :is="getComponents" @update:sidebarIndex="updateSliderIndex"></component>
        </div>
    </div>
</template>
<script setup lang="ts">
import { AppKeyEnum, appKeyNameMap, ThemeEnum } from "@/enums/appEnums";
import Sidebar from "../_components/sidebar.vue";
import { SidebarTypeEnum } from "./_enums/index";
import useSidebar from "../_hooks/useSidebar";
import AutoCustomer from "./_pages/auto_customer/index.vue";
import AutoAddWechat from "./_pages/auto_add_wechat/index.vue";
import ManualAddWechat from "./_pages/manual_add_wechat/index.vue";
import MsgManagement from "./_pages/msg_manage/index.vue";

const { sidebar, sidebarIndex, getComponents, getSliderIndex, updateSliderIndex } = useSidebar();

sidebar.value = [
    {
        name: "采集任务",
        icon: "menu_gather_task",
        components: markRaw(AutoCustomer),
        type: SidebarTypeEnum.AUTO_GET_CUSTOMER,
    },
    {
        name: "评论获客",
        icon: "menu_auto_wechat",
        components: "",
        type: SidebarTypeEnum.COMMENT_GET_CUSTOMER,
        disabled: true,
    },
    {
        name: "私信获客",
        icon: "menu_auto_customer",
        components: "",
        type: SidebarTypeEnum.MESSAGE_GET_CUSTOMER,
        disabled: true,
    },
    {
        name: "采集加微",
        icon: "menu_manual_wechat",
        components: markRaw(AutoAddWechat),
        type: SidebarTypeEnum.AUTO_ADD_WECHAT,
    },
    {
        name: "批量加微",
        icon: "menu_msg_manage",
        components: markRaw(ManualAddWechat),
        type: SidebarTypeEnum.MANUAL_ADD_WECHAT,
    },
];

enum SidebarGroupEnum {
    // 获客管理
    GET_CUSTOMER_MANAGEMENT = "主动获客",
    // 私信管理
    MESSAGE_MANAGEMENT = "线索加微",
}

const getSidebar = computed(() => {
    const groupedItems = [];

    sidebar.value.forEach((item) => {
        let group;

        if (
            [
                SidebarTypeEnum.AUTO_GET_CUSTOMER,
                SidebarTypeEnum.COMMENT_GET_CUSTOMER,
                SidebarTypeEnum.MESSAGE_GET_CUSTOMER,
            ].includes(item.type)
        ) {
            group = groupedItems.find((g) => g.title === SidebarGroupEnum.GET_CUSTOMER_MANAGEMENT) || {
                title: SidebarGroupEnum.GET_CUSTOMER_MANAGEMENT,
                children: [],
            };
            group.children.push(item);
            if (!groupedItems.includes(group)) {
                groupedItems.push(group);
            }
        } else if ([SidebarTypeEnum.AUTO_ADD_WECHAT, SidebarTypeEnum.MANUAL_ADD_WECHAT].includes(item.type)) {
            group = groupedItems.find((g) => g.title === SidebarGroupEnum.MESSAGE_MANAGEMENT) || {
                title: SidebarGroupEnum.MESSAGE_MANAGEMENT,
                children: [],
            };
            group.children.push(item);
            if (!groupedItems.includes(group)) {
                groupedItems.push(group);
            }
        }
    });
    return groupedItems;
});

definePageMeta({
    layout: "base",
    title: appKeyNameMap[AppKeyEnum.SPH],
    key: AppKeyEnum.SPH,
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
</style>
