<template>
    <div class="h-full flex flex-col">
        <div class="p-4 border-b border-slate-50">
            <div class="flex bg-[#f1f5f9]/80 p-1 rounded-xl">
                <div
                    v-for="item in tabList"
                    :key="item.key"
                    :class="[
                        'flex-1 py-2 text-center text-xs font-black rounded-lg cursor-pointer transition-all',
                        selectedTab === item.key
                            ? 'bg-white text-primary shadow-light'
                            : 'text-slate-500 hover:text-slate-700',
                    ]"
                    @click="handleTabChange(item.key)">
                    {{ item.label }}
                </div>
            </div>
        </div>

        <div class="px-4 py-3 flex gap-2">
            <div
                v-for="agent in agentList"
                :key="agent.key"
                :class="[
                    'px-3 py-1.5 rounded-full text-[11px] font-bold cursor-pointer border transition-all',
                    selectedAgent === agent.key
                        ? 'bg-[#0065fb]/5 text-primary border-[#0065fb]/20'
                        : 'bg-white text-slate-400 border-slate-100 hover:border-slate-300',
                ]"
                @click="handleAgentChange(agent.key)">
                {{ agent.label }}
            </div>
        </div>

        <div class="flex-1 min-h-0">
            <ElScrollbar @end-reached="load">
                <div class="p-3 space-y-2">
                    <div
                        v-for="item in computedAgentList"
                        :key="item.id"
                        :class="[
                            'group flex items-center gap-3 p-3 rounded-2xl cursor-pointer transition-all border',
                            isCurrentAgent(item.id)
                                ? 'bg-[#0065fb]/5 border-[#0065fb]/20 '
                                : 'bg-white border-[transparent] hover:bg-slate-50',
                        ]"
                        @click="handleAgentClick(item)">
                        <div class="relative">
                            <img :src="item.logo" class="w-10 h-10 object-cover rounded-xl" />
                            <div
                                v-if="isCurrentAgent(item.id)"
                                class="absolute -right-1 -top-1 w-4 h-4 bg-primary text-white rounded-full flex items-center justify-center border-2 border-white">
                                <Icon name="el-icon-Check" :size="8" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-black text-slate-800 truncate">
                                {{ item.name }}
                            </div>
                        </div>
                    </div>
                </div>
                <load-text :is-load="pager.isLoad" />
            </ElScrollbar>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getAgentList, getCozeAgentList, getSystemAgentList } from "@/api/agent";

const props = withDefaults(
    defineProps<{
        systemAgentIds?: number[];
        isSora?: boolean;
    }>(),
    {
        systemAgentIds: () => [0, 1, 3, 4, 5, 6],
        isSora: false,
    }
);

const emit = defineEmits(["select-agent", "select-agent-type"]);

const tabList = [
    { key: "generate", label: "文案生成", type: 1 },
    { key: "rewrite", label: "文案改写", type: 2 },
];
const selectedTab = ref(tabList[0].key);

const agentList = ref([
    { key: 1, label: "系统内置", api: getSystemAgentList, params: { type: tabList[0].type } },
    { key: 2, label: "智能体", api: getAgentList, params: {} },
    { key: 3, label: "coze智能体", api: getCozeAgentList, params: { type: 1 } },
]);
const selectedAgent = ref(agentList.value[0].key);
const agentData = reactive<{
    agentType: number;
    agentId: number;
}>({
    agentType: 0,
    agentId: -1,
});
const agentParams = reactive({
    page_no: 1,
    page_size: 10,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: (params: any) => {
        const currAgent = agentList.value.find((item) => item.key === selectedAgent.value);
        if (props.isSora && selectedAgent.value === 1) {
            delete currAgent?.params?.type;
        }
        return currAgent?.api({ ...params, ...currAgent?.params });
    },
    params: agentParams,
    isScroll: true,
});

const computedAgentList = computed(() => {
    if (selectedAgent.value === 1) {
        return pager.lists.filter((item: any) =>
            props.systemAgentIds.length > 0 ? props.systemAgentIds.includes(item.id) : true
        );
    }
    return pager.lists;
});

// 判断是否为当前选中的智能体
const isCurrentAgent = (itemId: number) => {
    return agentData.agentType === selectedAgent.value && agentData.agentId === itemId;
};

const load = (e: string) => {
    if (e === "bottom") {
        if (!pager.isLoad || pager.loading) return;
        agentParams.page_no++;
        getLists();
    }
};

const handleTabChange = (key: string) => {
    selectedTab.value = key;
    agentList.value[0].params.type = key === "rewrite" ? 2 : 1;
    // 切换tab时也清空选择的智能体数据
    agentData.agentId = -1;
    agentData.agentType = 0;
    emit("select-agent", agentData);

    resetPage();
};

const handleAgentChange = (key: number) => {
    selectedAgent.value = key;
    // 切换智能体类型时清空选择的智能体数据
    agentData.agentId = -1;
    agentData.agentType = 0;
    emit("select-agent-type", selectedAgent.value);
    emit("select-agent", agentData);
    resetPage();
};

const handleAgentClick = (item: any) => {
    agentData.agentId = item.id;
    agentData.agentType = selectedAgent.value;
    emit("select-agent", agentData);
};

defineExpose({
    getLists,
});
</script>

<style scoped></style>
