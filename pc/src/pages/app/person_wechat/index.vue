<template>
    <div class="px-4 pb-4 flex gap-[10px] h-full w-full">
        <Sidebar :sidebar="getSidebar" :sidebar-index="sidebarIndex" @update:sidebar-index="getSliderIndex" />
        <div class="grow min-h-0 min-w-0">
            <component :is="getComponents"></component>
        </div>
    </div>
</template>

<script setup lang="ts">
import Sidebar from "../_components/sidebar.vue";
import Chat from "./chat/index.vue";
import SopTask from "./sop/_pages/task/index.vue";
import SopAutoTask from "./sop/_pages/auto/index.vue";
import SopFlow from "./sop/_pages/flow/index.vue";
import SopFlowBoard from "./sop/_pages/board/index.vue";
import Greeting from "./sop/_pages/setting/index.vue";
import Circle from "./circle/_pages/index/index.vue";
import CircleTask from "./circle/_pages/task/index.vue";
import CirclePraiseSetting from "./circle/_pages/setting/index.vue";
import TagAuto from "./tag/_pages/auto/index.vue";
import TagManualAuto from "./tag/_pages/manual/index.vue";
import FriendStrategy from "./setting/_pages/friend/index.vue";
import MaterialLibrary from "./marketing/_pages/material/index.vue";
import DeviceList from "./setting/_pages/device/index.vue";
import WechatList from "./setting/_pages/wechat/index.vue";
import useSidebar from "../_hooks/useSidebar";
import { SidebarTypeEnum } from "./_enums";

const { sidebar, sidebarIndex, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [
    { name: "聊天室", components: markRaw(Chat), type: SidebarTypeEnum.CHAT },
    { name: "设备列表", components: markRaw(DeviceList), type: SidebarTypeEnum.DEVICE_MANAGEMENT },
    { name: "微信列表", components: markRaw(WechatList), type: SidebarTypeEnum.WECHAT_MANAGEMENT },
    { name: "SOP任务", components: markRaw(SopTask), type: SidebarTypeEnum.SOP_TASK },
    { name: "自动SOP", components: markRaw(SopAutoTask), type: SidebarTypeEnum.SOP_AUTO_TASK },
    { name: "SOP流程", components: markRaw(SopFlow), type: SidebarTypeEnum.SOP_FLOW },
    { name: "SOP看板", components: markRaw(SopFlowBoard), type: SidebarTypeEnum.SOP_FLOW_BOARD },
    { name: "朋友圈", components: markRaw(Circle), type: SidebarTypeEnum.CIRCLE },
    { name: "任务列表", components: markRaw(CircleTask), type: SidebarTypeEnum.CIRCLE_TASK },
    // { name: "评赞设置", components: markRaw(CirclePraiseSetting), type: SidebarTypeEnum.CIRCLE_PRAISE_SETTING },
    { name: "标签自动化", components: markRaw(TagAuto), type: SidebarTypeEnum.TAG_AUTO },
    { name: "手动标签", components: markRaw(TagManualAuto), type: SidebarTypeEnum.TAG_MANUAL_SETTING },
    { name: "素材库", components: markRaw(MaterialLibrary), type: SidebarTypeEnum.MATERIAL_LIBRARY },
    { name: "好友策略", components: markRaw(FriendStrategy), type: SidebarTypeEnum.FRIEND_STRATEGY },
    { name: "打招呼策略", components: markRaw(Greeting), type: SidebarTypeEnum.GREETING },
];

enum SidebarGroupEnum {
    CHAT = "聊天",
    SOP = "SOP管理",
    CIRCLE = "朋友圈",
    TAG = "标签管理",
    MATERIAL = "素材库",
    DEVICE = "设备管理",
    SETTING = "基础设置",
}

const getSidebar = computed(() => {
    // 1. 定义类型到分组标题的映射关系
    const typeToGroupMap = {
        [SidebarTypeEnum.CHAT]: null,
        [SidebarTypeEnum.SOP_TASK]: SidebarGroupEnum.SOP,
        [SidebarTypeEnum.SOP_AUTO_TASK]: SidebarGroupEnum.SOP,
        [SidebarTypeEnum.SOP_FLOW]: SidebarGroupEnum.SOP,
        [SidebarTypeEnum.SOP_FLOW_BOARD]: SidebarGroupEnum.SOP,
        [SidebarTypeEnum.CIRCLE]: SidebarGroupEnum.CIRCLE,
        [SidebarTypeEnum.CIRCLE_TASK]: SidebarGroupEnum.CIRCLE,
        [SidebarTypeEnum.CIRCLE_PRAISE_SETTING]: SidebarGroupEnum.CIRCLE,
        [SidebarTypeEnum.TAG_AUTO]: SidebarGroupEnum.TAG,
        [SidebarTypeEnum.TAG_MANUAL_SETTING]: SidebarGroupEnum.TAG,
        [SidebarTypeEnum.MATERIAL_LIBRARY]: SidebarGroupEnum.MATERIAL,
        [SidebarTypeEnum.DEVICE_MANAGEMENT]: SidebarGroupEnum.DEVICE,
        [SidebarTypeEnum.WECHAT_MANAGEMENT]: SidebarGroupEnum.DEVICE,
        [SidebarTypeEnum.FRIEND_STRATEGY]: SidebarGroupEnum.SETTING,
        [SidebarTypeEnum.GREETING]: SidebarGroupEnum.SETTING,
    };

    const result = [];
    const groupTracker = new Map();

    sidebar.value.forEach((item) => {
        const groupTitle = typeToGroupMap[item.type];

        if (groupTitle === null || groupTitle === undefined) {
            result.push(item);
        } else {
            let group = groupTracker.get(groupTitle);
            if (!group) {
                group = { title: groupTitle, children: [] };
                result.push(group);
                groupTracker.set(groupTitle, group);
            }
            group.children.push(item);
        }
    });

    return result;
});
</script>
