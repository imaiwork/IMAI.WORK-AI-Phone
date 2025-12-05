<template>
    <div class="h-full p-4">
        <div class="flex flex-col h-full rounded-[20px] bg-white">
            <!-- 头部导航 -->
            <div
                class="px-[14px] h-[72px] flex items-center justify-between flex-shrink-0 border-b-[1px] border-[#0000000d]">
                <div class="flex items-center gap-2 cursor-pointer" @click="router.back()">
                    <Icon name="el-icon-ArrowLeft"></Icon>
                    <div>返回</div>
                </div>
                <!-- Tab切换 -->
                <div>
                    <ElSegmented v-model="tab" :options="tabs.map((item) => item.name)"></ElSegmented>
                </div>
            </div>
            <!-- 主内容区：动态渲染组件 -->
            <div class="grow min-h-0">
                <component :is="getComponent" :agent-id="agentId" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import ChattingLogRecord from "./_components/chatting-log/record.vue";
import ChattingLogStat from "./_components/chatting-log/stat.vue";

/**
 * @description 聊天日志和统计页面容器
 * @summary 通过Tab切换显示对话记录或对话统计。
 */

// Tab定义
enum TabEnum {
    RECORD = "对话记录",
    STAT = "对话统计",
}

const router = useRouter();
const route = useRoute();

const agentId = route.query.agent_id as string;

// 当前激活的Tab
const tab = ref(TabEnum.RECORD);

// Tab及其对应组件的配置
const tabs = [
    { name: TabEnum.RECORD, component: markRaw(ChattingLogRecord) },
    { name: TabEnum.STAT, component: markRaw(ChattingLogStat) },
];

// 根据当前tab动态获取要渲染的组件
const getComponent = computed(() => tabs.find((item) => item.name === tab.value)?.component);
</script>

<style scoped lang="scss">
/* 深度选择器，自定义分段控制器样式 */
:deep(.el-segmented) {
    min-height: 44px;
    border-radius: 100px;
    .el-segmented__item-selected,
    .el-segmented__item {
        border-radius: 100px;
    }
    .el-segmented__item {
        padding: 0 40px;
    }
}
</style>
