<template>
    <div class="h-full">
        <ElScrollbar>
            <!-- 统计数据网格 -->
            <div class="p-5 grid gap-4 grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                <div
                    v-for="(item, index) in stats"
                    :key="index"
                    class="flex gap-4 border border-[#0000001a] p-4 rounded-[20px] hover:bg-[#00000005]">
                    <!-- 图标 -->
                    <div
                        class="rounded-[10px] w-[50px] h-[50px] border border-[#0000001a] flex items-center justify-center">
                        <Icon name="local-icon-user" size="30"></Icon>
                    </div>
                    <!-- 标题和数值 -->
                    <div>
                        <div class="text-[#000000cc]">{{ item.title }}</div>
                        <div class="mt-1 text-[#00000080]">{{ get(statsData, item.key) }}</div>
                    </div>
                </div>
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
import { get } from "lodash-es";
import { getAgentChatStat } from "@/api/agent";

const props = defineProps<{
    agentId: string | number;
}>();

// 定义统计项的接口
interface StatItem {
    title: string;
    value: string | number;
    key: string;
}

// 统计数据，key用于后续从API获取数据时匹配
const stats = ref<StatItem[]>([
    {
        title: "今日对话次数",
        value: 0,
        key: "robot.todayChatCount",
    },
    {
        title: "昨日对话次数",
        value: 0,
        key: "robot.yesterdayChatCount",
    },
    {
        title: "本周对话次数",
        value: 0,
        key: "robot.weekChatCount",
    },
    {
        title: "全部对话次数",
        value: 0,
        key: "robot.wholeChatCount",
    },
    {
        title: "今日访问用户/人",
        value: 0,
        key: "visitor.todayVisitorCount",
    },
    {
        title: "昨日访问用户/人",
        value: 0,
        key: "visitor.yesterdayVisitorCount",
    },
    {
        title: "本周访问用户/人",
        value: 0,
        key: "visitor.weekVisitorCount",
    },
    {
        title: "全部访问用户/人",
        value: 0,
        key: "visitor.wholeVisitorCount",
    },
]);

/**
 * @description 从API获取统计数据
 */
const statsData = reactive({
    robot: {},
    visitor: {},
});
const fetchStats = async () => {
    const apiData = await getAgentChatStat({ robot_id: props.agentId });
    statsData.robot = apiData.robot;
    statsData.visitor = apiData.visitor;
};

// 组件挂载后获取数据
onMounted(() => {
    fetchStats();
});
</script>

<style scoped></style>
