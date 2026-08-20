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

        <Transition name="engine-fade">
            <div v-if="selectedAgent === 1" class="px-4 pb-3">
                <div class="text-[11px] font-bold text-slate-400 mb-2 tracking-wide">生成引擎</div>
                <div class="flex gap-2">
                    <div
                        v-for="engine in engineList"
                        :key="engine.key"
                        :class="[
                            'flex-1 flex items-center gap-2 px-3 py-2.5 rounded-xl border cursor-pointer transition-all',
                            selectedEngine === engine.key
                                ? 'bg-[#0065fb]/5 border-[#0065fb]/20'
                                : 'bg-white border-slate-100 hover:border-slate-300',
                        ]"
                        @click="handleEngineChange(engine.key)">
                        <span class="text-base leading-none">{{ engine.icon }}</span>
                        <div class="flex-1 min-w-0">
                            <div
                                :class="[
                                    'text-[12px] font-black truncate',
                                    selectedEngine === engine.key ? 'text-primary' : 'text-slate-700',
                                ]">
                                {{ engine.label }}
                            </div>
                            <div class="text-[10px] text-slate-400 leading-tight mt-0.5 truncate">
                                {{ engine.desc }}
                            </div>
                        </div>
                        <div
                            :class="[
                                'w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all',
                                selectedEngine === engine.key
                                    ? 'border-primary bg-primary'
                                    : 'border-slate-300 bg-white',
                            ]">
                            <div v-if="selectedEngine === engine.key" class="w-1 h-1 rounded-full bg-white" />
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <div class="flex-1 min-h-0 overflow-hidden">
            <ElScrollbar ref="scrollbarRef" class="h-full" :distance="20" @end-reached="load">
                <div class="p-3 space-y-2">
                    <div
                        v-for="item in computedAgentList"
                        :key="item.id"
                        :class="[
                            'group flex items-center gap-3 p-3 rounded-2xl cursor-pointer transition-all border',
                            isCurrentAgent(item.id)
                                ? 'bg-[#0065fb]/5 border-[#0065fb]/20 '
                                : 'bg-white border-[transparent] hover:bg-slate-50',
                            !canUseCurrentAgent(item) ? 'opacity-60' : '',
                        ]"
                        @click="handleAgentClick(item)">
                        <div class="relative">
                            <img
                                :src="item.logo || item.image || item.avatar"
                                class="w-10 h-10 object-cover rounded-xl" />
                            <div
                                v-if="isCurrentAgent(item.id)"
                                class="absolute -right-1 -top-1 w-4 h-4 bg-primary text-white rounded-full flex items-center justify-center border-2 border-white">
                                <Icon name="el-icon-Check" :size="8" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <div class="text-[13px] font-black text-slate-800 truncate">
                                    {{ item.name }}
                                </div>
                                <span
                                    v-if="shouldShowAgentAccessTag(item)"
                                    class="shrink-0 rounded-full border px-[6px] py-[1px] text-[10px] font-bold leading-none"
                                    :class="
                                        canUseCurrentAgent(item)
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-600'
                                            : 'border-violet-200 bg-violet-50 text-violet-600'
                                    ">
                                    {{ getAgentAccessTagText(item, userInfo) }}
                                </span>
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
import { storeToRefs } from "pinia";
import { getAgentList, getCozeAgentList, getSystemAgentList } from "@/api/agent";
import { useUserStore } from "@/stores/user";
import {
    AGENT_UNAVAILABLE_TIP,
    canUseAgent,
    getAgentAccessTagText,
    shouldShowAgentAccessTag,
} from "@/utils/agentPermission";

const { userInfo } = storeToRefs(useUserStore());

const props = withDefaults(
    defineProps<{
        systemAgentIds?: number[];
    }>(),
    {
        systemAgentIds: () => [0, 1, 3, 4, 5, 6],
    },
);

const emit = defineEmits(["select-agent", "select-agent-type", "select-engine"]);

const tabList = [
    { key: "generate", label: "文案生成", type: 1 },
    { key: "rewrite", label: "文案改写", type: 2 },
];
const selectedTab = ref(tabList[0].key);

// 生成引擎列表
const engineList = [
    { key: 1, label: "普通版", icon: "⚡", desc: "极速出稿，适合日常铺量" },
    { key: 2, label: "高级版", icon: "✨", desc: "深度思考，爆款网感文案" },
];
const selectedEngine = ref<number>(1);

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

const {
    pager,
    getLists: fetchAgentLists,
    resetPage: resetAgentPage,
} = usePaging({
    fetchFun: (params: any) => {
        const currAgent = agentList.value.find((item) => item.key === selectedAgent.value);
        if (props.systemAgentIds.includes(7) && selectedAgent.value === 1) {
            delete currAgent?.params?.type;
        }
        return currAgent?.api({ ...params, ...currAgent?.params });
    },
    params: agentParams,
    isScroll: true,
});

const scrollbarRef = ref<any>(null);

const computedAgentList = computed(() => {
    if (selectedAgent.value === 1) {
        return pager.lists.filter((item: any) =>
            props.systemAgentIds.length > 0 ? props.systemAgentIds.includes(item.id) : true,
        );
    }
    return pager.lists;
});

// 判断是否为当前选中的智能体
const isCurrentAgent = (itemId: number) => {
    return agentData.agentType === selectedAgent.value && agentData.agentId === itemId;
};

// "智能体" 与 "coze智能体" 适用会员权限校验；系统内置不参与
const canUseCurrentAgent = (item: any) => {
    if (selectedAgent.value === 1) return true;
    return canUseAgent(item, userInfo.value);
};

const canLoadMore = () => pager.isLoad && !pager.loading;

const isScrollbarFilled = () => {
    const wrapEl = scrollbarRef.value?.wrapRef;
    if (!wrapEl) return true;
    return wrapEl.scrollHeight > wrapEl.clientHeight;
};

const loadNextPage = async () => {
    if (!canLoadMore()) return;
    agentParams.page_no++;
    await fetchAgentLists();
    await fillScrollbar();
};

const fillScrollbar = async () => {
    await nextTick();
    if (isScrollbarFilled() || !canLoadMore()) return;
    await loadNextPage();
};

const getLists = async (...args: Parameters<typeof fetchAgentLists>) => {
    await fetchAgentLists(...args);
    await fillScrollbar();
};

const resetPage = async () => {
    await resetAgentPage();
    await fillScrollbar();
};

const load = async (e: string) => {
    if (e !== "bottom") return;
    await loadNextPage();
};

const handleEngineChange = (key: number) => {
    selectedEngine.value = key;
    emit("select-engine", key);
};

const handleTabChange = (key: string) => {
    selectedTab.value = key;
    agentList.value[0].params.type = key === "rewrite" ? 2 : 1;
    agentData.agentId = -1;
    agentData.agentType = 0;
    emit("select-agent", agentData);
    resetPage();
};

const handleAgentChange = (key: number) => {
    selectedAgent.value = key;
    agentData.agentId = -1;
    agentData.agentType = 0;
    emit("select-agent-type", selectedAgent.value);
    emit("select-agent", agentData);
    resetPage();
};

const handleAgentClick = (item: any) => {
    if (!canUseCurrentAgent(item)) {
        feedback.msgWarning(AGENT_UNAVAILABLE_TIP);
        return;
    }
    agentData.agentId = item.id;
    agentData.agentType = selectedAgent.value;
    emit("select-agent", agentData);
};

defineExpose({
    getLists,
});
</script>

<style scoped>
.engine-fade-enter-active,
.engine-fade-leave-active {
    transition: all 0.2s ease;
    overflow: hidden;
}
.engine-fade-enter-from,
.engine-fade-leave-to {
    opacity: 0;
    max-height: 0;
    padding-bottom: 0;
}
.engine-fade-enter-to,
.engine-fade-leave-from {
    opacity: 1;
    max-height: 200px;
}
</style>
