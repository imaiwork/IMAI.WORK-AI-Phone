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
import MeetingMinutes from "./_meeting_minutes/_pages/home/index.vue";
import MeetingMinutesRecord from "./_meeting_minutes/_pages/record/index.vue";
import InterviewJob from "./_interview/_pages/job/index.vue";
import InterviewRecord from "./_interview/_pages/record/index.vue";
import LadderPlayer from "./_ladder_player/_pages/home/index.vue";
import MindMapRecord from "./_mind_map/_pages/record/index.vue";
import UnmannedLive from "./_unmanned_live/index.vue";
import useSidebar from "../_hooks/useSidebar";
import { SidebarTypeEnum } from "./_enums";

const { sidebar, sidebarIndex, getComponents, getSliderIndex } = useSidebar();

sidebar.value = [
    { name: "会议助手", components: markRaw(MeetingMinutes), type: SidebarTypeEnum.MEETING_ASSISTANT },
    { name: "会议记录", components: markRaw(MeetingMinutesRecord), type: SidebarTypeEnum.MEETING_RECORD },
    { name: "岗位列表", components: markRaw(InterviewJob), type: SidebarTypeEnum.JOB_LIST },
    { name: "面试记录", components: markRaw(InterviewRecord), type: SidebarTypeEnum.INTERVIEW_RECORD },
    { name: "员工陪练", components: markRaw(LadderPlayer), type: SidebarTypeEnum.EMPLOYEE_TRAINING },
    { name: "思维导图", components: markRaw(MindMapRecord), type: SidebarTypeEnum.MIND_MAP },
    { name: "无人直播", components: markRaw(UnmannedLive), type: SidebarTypeEnum.UNMANNED_LIVE },
];

enum SidebarGroupEnum {
    // AI会议
    AI_MEETING = "AI会议",
    // AI人事
    AI_PERSONNEL = "AI人事",
    // AI陪练
    AI_TRAINING = "AI陪练",
    // AI工具
    AI_TOOLS = "AI工具",
}

const getSidebar = computed(() => {
    // 1. 定义类型到分组标题的映射关系
    const typeToGroupMap = {
        [SidebarTypeEnum.MEETING_ASSISTANT]: SidebarGroupEnum.AI_MEETING,
        [SidebarTypeEnum.MEETING_RECORD]: SidebarGroupEnum.AI_MEETING,
        [SidebarTypeEnum.JOB_LIST]: SidebarGroupEnum.AI_PERSONNEL,
        [SidebarTypeEnum.INTERVIEW_RECORD]: SidebarGroupEnum.AI_PERSONNEL,
        [SidebarTypeEnum.EMPLOYEE_TRAINING]: SidebarGroupEnum.AI_TRAINING,
        [SidebarTypeEnum.MIND_MAP]: SidebarGroupEnum.AI_TOOLS,
        [SidebarTypeEnum.UNMANNED_LIVE]: SidebarGroupEnum.AI_TOOLS,
    };

    const groupedMap = sidebar.value.reduce((acc, item) => {
        const groupTitle = typeToGroupMap[item.type];

        if (!groupTitle) return acc;

        if (!acc[groupTitle]) {
            acc[groupTitle] = { title: groupTitle, children: [] };
        }
        acc[groupTitle].children.push(item);

        return acc;
    }, {});

    return Object.values(groupedMap);
});
</script>
<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
</style>
