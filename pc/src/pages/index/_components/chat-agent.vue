<template>
    <div class="flex flex-col">
        <!-- 搜索栏 -->
        <div class="flex items-center gap-x-2 mb-3 mt-3 px-3">
            <div class="relative flex-1">
                <ElInput
                    v-model="search"
                    placeholder="搜索智能体"
                    :prefix-icon="Search"
                    clearable
                    @input="handleSearchInput"
                    @clear="handleSearchClear" />
            </div>
            <div
                class="shrink-0 w-[38px] h-[38px] flex items-center justify-center rounded-lg bg-[#F5F5F5] cursor-pointer hover:bg-[#E5E6E8] transition-colors"
                @click="handleAddAgentGroup">
                <Icon name="local-icon-add_circle" :size="16" color="#000"></Icon>
            </div>
        </div>

        <div class="px-3 mb-3">
            <ElButton class="modern-new-btn w-full !h-[48px] !rounded-2xl" type="primary" @click="createNewSession()">
                <Icon name="local-icon-history_add" :size="18"></Icon>
                <span class="ml-2 text-[14px] font-[900] tracking-wide">新建智能会话</span>
            </ElButton>
        </div>

        <div v-if="isLoading" class="space-y-4 animate-pulse flex-1 px-3">
            <div v-for="i in 3" :key="i">
                <div class="h-10 bg-gray-200 rounded-xl mb-2"></div>
                <div class="pl-4 space-y-2">
                    <div v-for="j in 2" :key="j" class="flex items-center gap-2">
                        <div class="w-10 h-10 bg-gray-200 rounded-full"></div>
                        <div class="h-4 bg-gray-200 rounded flex-1"></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="flex flex-col">
            <div v-if="isSearchMode" class="flex-1">
                <ElScrollbar max-height="600px">
                    <div class="px-3">
                        <div v-if="searchResults.length === 0" class="text-center py-8">
                            <Icon name="el-icon-Search" :size="36" color="#d1d5db" class="mb-3"></Icon>
                            <div class="text-slate-500 text-sm">未找到相关智能体</div>
                            <div class="text-slate-400 text-xs mt-1">尝试使用其他关键词搜索</div>
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="result in searchResults"
                                :key="`${result.groupId}-${result.agent.id}`"
                                class="search-result-item"
                                :class="{ 'is-active': selectedAgentId == result.agent.id }"
                                @click="handleSelectAgent(result.agent)">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <ElImage
                                        :src="result.agent.image"
                                        :alt="result.agent.name"
                                        class="w-9 h-9 rounded-full flex-shrink-0 border border-slate-100"
                                        fit="cover">
                                        <template #error>
                                            <div
                                                class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-purple-400 text-white text-sm font-bold">
                                                {{ result.agent.name.charAt(0) }}
                                            </div>
                                        </template>
                                    </ElImage>
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="text-[13px] font-medium text-slate-700 truncate"
                                            v-html="highlightText(result.agent.name, search)"></div>
                                    </div>
                                </div>

                                <div
                                    v-if="result.agent.type !== 'model_admin'"
                                    class="opacity-0 group-hover:opacity-100"
                                    :class="{ '!opacity-100': activeAgentPopoverId === result.agent.id }"
                                    @click.stop>
                                    <ElPopover
                                        :show-arrow="false"
                                        placement="left-start"
                                        trigger="click"
                                        popper-class="!p-1.5 !min-w-[120px] !rounded-xl !shadow-xl !border-slate-100"
                                        @show="() => handleAgentPopoverShow(result.agent.id)"
                                        @hide="handleAgentPopoverHide">
                                        <template #reference>
                                            <div
                                                class="more-trigger bg-[#ffffff]/80 backdrop-blur"
                                                :class="{
                                                    '!bg-[#E5E6E8] !text-slate-600':
                                                        activeAgentPopoverId === result.agent.id,
                                                }">
                                                <Icon name="el-icon-MoreFilled" :size="12"></Icon>
                                            </div>
                                        </template>
                                        <div class="flex flex-col gap-1">
                                            <div
                                                class="table-action-item"
                                                @click="handleMoveAgent($event, result.agent)">
                                                <Icon name="el-icon-FolderChecked" :size="14"></Icon>
                                                <span>移动到分组</span>
                                            </div>
                                            <div
                                                v-if="result.groupId !== 0"
                                                class="table-action-item !text-red-500 hover:!bg-red-50"
                                                @click="handleRemoveAgent(result.agent, result.groupId)">
                                                <Icon name="local-icon-delete" :size="14"></Icon>
                                                <span>移除</span>
                                            </div>
                                        </div>
                                    </ElPopover>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>

            <div v-else>
                <ElScrollbar max-height="400px">
                    <div class="px-3">
                        <ElCollapse v-model="activeCollapse" expand-icon-position="left">
                            <ElCollapseItem
                                :name="item.id"
                                v-for="(item, index) in filteredAgentGroupList"
                                :key="index">
                                <template #title>
                                    <div
                                        class="group flex items-center justify-between w-full pr-2 relative"
                                        :class="{ 'is-popover-open': activeGroupPopoverId === item.id }">
                                        <div class="text-[14px] text-[#000000]/50 flex-1 line-clamp-1 font-medium">
                                            {{ item.name }}
                                        </div>

                                        <div class="flex items-center">
                                            <div class="text-xs text-[#000000]/30 group-hover:invisible">
                                                {{ item.agents.length }}
                                            </div>

                                            <div
                                                v-if="item.id !== 0"
                                                class="absolute right-0 opacity-0 group-hover:opacity-100"
                                                :class="{ '!opacity-100': activeGroupPopoverId === item.id }"
                                                @click.stop>
                                                <ElPopover
                                                    :show-arrow="false"
                                                    placement="bottom-end"
                                                    trigger="click"
                                                    popper-class="!p-1.5 !min-w-[120px] !rounded-xl !shadow-xl !border-slate-100"
                                                    @show="() => handleGroupPopoverShow(item.id)"
                                                    @hide="handleGroupPopoverHide">
                                                    <template #reference>
                                                        <div
                                                            class="more-trigger"
                                                            :class="{
                                                                '!bg-[#E5E6E8] !text-slate-600':
                                                                    activeGroupPopoverId === item.id,
                                                            }">
                                                            <Icon name="el-icon-MoreFilled" :size="12"></Icon>
                                                        </div>
                                                    </template>
                                                    <div class="flex flex-col gap-1">
                                                        <div class="table-action-item" @click="handleRenameGroup(item)">
                                                            <Icon name="local-icon-edit" :size="14"></Icon>
                                                            <span>重命名</span>
                                                        </div>
                                                        <div class="table-action-item" @click="handlePinGroup(item)">
                                                            <Icon name="el-icon-Top" :size="14"></Icon>
                                                            <span>{{
                                                                item.is_top === 1 ? "取消置顶" : "置顶分组"
                                                            }}</span>
                                                        </div>
                                                        <div
                                                            class="table-action-item !text-red-500 hover:!bg-red-50"
                                                            @click="handleDeleteGroup(item)">
                                                            <Icon name="local-icon-delete" :size="14"></Icon>
                                                            <span>删除分组</span>
                                                        </div>
                                                    </div>
                                                </ElPopover>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div class="space-y-[2px]">
                                    <div
                                        v-for="(agent, agentIndex) in item.agents"
                                        :key="agentIndex"
                                        class="agent-item group"
                                        :class="{
                                            'is-active': selectedAgentId == agent.id,
                                            'is-popover-open': activeAgentPopoverId == agent.id,
                                        }"
                                        @click="handleSelectAgent(agent)">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <ElImage
                                                :src="agent.image"
                                                :alt="agent.name"
                                                class="w-9 h-9 rounded-full flex-shrink-0 border border-slate-100"
                                                fit="cover">
                                                <template #error>
                                                    <div
                                                        class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-purple-400 text-white text-sm font-bold">
                                                        {{ agent.name.charAt(0) }}
                                                    </div>
                                                </template>
                                            </ElImage>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-[13px] font-medium text-slate-700 truncate">
                                                    {{ agent.name }}
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            v-if="agent.type !== 'model_admin'"
                                            class="absolute right-2 opacity-0 group-hover:opacity-100"
                                            :class="{ '!opacity-100': activeAgentPopoverId === agent.id }"
                                            @click.stop>
                                            <ElPopover
                                                :show-arrow="false"
                                                placement="right-start"
                                                trigger="click"
                                                popper-class="!p-1.5 !min-w-[120px] !rounded-xl !shadow-xl !border-slate-100"
                                                @show="() => handleAgentPopoverShow(agent.id)"
                                                @hide="handleAgentPopoverHide">
                                                <template #reference>
                                                    <div
                                                        class="more-trigger bg-[#ffffff]/80 backdrop-blur"
                                                        :class="{
                                                            '!bg-[#E5E6E8] !text-slate-600':
                                                                activeAgentPopoverId === agent.id,
                                                        }">
                                                        <Icon name="el-icon-MoreFilled" :size="12"></Icon>
                                                    </div>
                                                </template>
                                                <div class="flex flex-col gap-1">
                                                    <div
                                                        class="table-action-item"
                                                        @click="handleMoveAgent($event, agent)">
                                                        <Icon name="el-icon-FolderChecked" :size="14"></Icon>
                                                        <span>移动到分组</span>
                                                    </div>
                                                    <div
                                                        v-if="item.id !== 0"
                                                        class="table-action-item !text-red-500 hover:!bg-red-50"
                                                        @click="handleRemoveAgent(agent, item.id)">
                                                        <Icon name="local-icon-delete" :size="14"></Icon>
                                                        <span>移除</span>
                                                    </div>
                                                </div>
                                            </ElPopover>
                                        </div>
                                    </div>
                                </div>
                            </ElCollapseItem>
                        </ElCollapse>
                    </div>
                </ElScrollbar>
            </div>

            <div
                v-if="false"
                class="flex items-center justify-center py-2 mx-3 cursor-pointer hover:bg-[#F5F5F5] rounded-lg transition-colors"
                @click="toggleExpand">
                <span class="text-xs text-slate-400 font-medium mr-1">
                    {{ isExpanded ? "收起" : "展开全部" }}
                </span>
                <Icon :name="isExpanded ? 'el-icon-ArrowUp' : 'el-icon-ArrowDown'" :size="14" color="#94a3b8"></Icon>
            </div>
        </div>

        <move-agent-popup
            ref="moveAgentPopupRef"
            :current-agent="currentMoveAgent"
            :groups="agentGroupList"
            @close="handleCloseMovePopup"
            @confirm="handleConfirmMove">
        </move-agent-popup>

        <rename-pop
            v-if="showRenamePop"
            ref="renamePopRef"
            :title="renamePopTitle"
            :fetch-fn="renamePopFetchFn"
            @close="showRenamePop = false"
            @success="confirmAgentGroup"></rename-pop>
    </div>
</template>

<script setup lang="ts">
import { Search } from "@element-plus/icons-vue";
import {
    getAllAgentList,
    getAgentGroupList as getAgentGroupListApi,
    addAgentGroup,
    updateAgentGroup,
    deleteAgentGroup,
    topAgentGroup,
    addAgentToGroup,
    deleteAgentFromGroup,
} from "@/api/agent";
import { useChatHistory } from "../_modules/composables/useChatHistory";
import MoveAgentPopup from "./move-agent-popup.vue";

const { createNewSession } = useChatHistory();

const emit = defineEmits<{
    (e: "select-agent", agent: any): void;
}>();

const search = ref("");
const agentList = ref<{ name: string; id: string; image: string }[]>([]);
const agentGroupList = ref<any[]>([]);
const isLoading = ref(true);
const isExpanded = ref(false);
const selectedAgentId = ref<number | null>(-1);
const activeCollapse = ref<number[]>([0]);

// 搜索相关状态
const searchHistory = ref<string[]>([]);
const searchDebounceTimer = ref<NodeJS.Timeout | null>(null);

// Popover 状态管理
const activeGroupPopoverId = ref<number | null>(null);
const activeAgentPopoverId = ref<number | null>(null);

const showRenamePop = ref(false);
const renamePopRef = ref();
const renamePopTitle = ref("新建智能体分组");
const renamePopFetchFn = ref(addAgentGroup);

const showMoveAgentPopup = ref(false);
const currentMoveAgent = ref<any>(null);
const moveAgentPopupRef = ref();

// 搜索模式判断
const isSearchMode = computed(() => search.value.trim().length > 0);

// 搜索结果
const searchResults = computed(() => {
    if (!search.value.trim()) return [];

    const keyword = search.value.toLowerCase();
    const results: Array<{
        agent: any;
        groupId: number;
        groupName: string;
        matchScore: number;
    }> = [];

    agentGroupList.value.forEach((group) => {
        group.agents.forEach((agent: any) => {
            let matchScore = 0;
            const nameMatch = agent.name.toLowerCase().includes(keyword);
            const descMatch = agent.description?.toLowerCase().includes(keyword);

            if (nameMatch || descMatch) {
                // 计算匹配分数，名称匹配权重更高
                if (nameMatch) matchScore += agent.name.toLowerCase().indexOf(keyword) === 0 ? 10 : 5;
                if (descMatch) matchScore += 2;

                results.push({
                    agent,
                    groupId: group.id,
                    groupName: group.name,
                    matchScore,
                });
            }
        });
    });

    // 按匹配分数排序
    return results.sort((a, b) => b.matchScore - a.matchScore);
});

// 搜索过滤（用于非搜索模式下的分组过滤）
const filteredAgentGroupList = computed(() => {
    if (isSearchMode.value) return agentGroupList.value;

    return agentGroupList.value;
});

// 高亮搜索文本
const highlightText = (text: string, keyword: string) => {
    if (!keyword.trim()) return text;

    const regex = new RegExp(`(${keyword.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")})`, "gi");
    return text.replace(regex, '<mark class="bg-yellow-200 text-yellow-800 px-0.5 rounded">$1</mark>');
};

// 处理搜索输入
const handleSearchInput = (value: string) => {
    // 防抖处理
    if (searchDebounceTimer.value) {
        clearTimeout(searchDebounceTimer.value);
    }

    searchDebounceTimer.value = setTimeout(() => {
        if (value.trim() && !searchHistory.value.includes(value.trim())) {
            searchHistory.value.unshift(value.trim());
            // 保持搜索历史不超过10条
            if (searchHistory.value.length > 10) {
                searchHistory.value = searchHistory.value.slice(0, 10);
            }
            // 保存到本地存储
            localStorage.setItem("agent-search-history", JSON.stringify(searchHistory.value));
        }
    }, 1000);
};

// 清除搜索
const handleSearchClear = () => {
    search.value = "";
    if (searchDebounceTimer.value) {
        clearTimeout(searchDebounceTimer.value);
    }
};

// Popover 显示/隐藏处理
const handleGroupPopoverShow = (groupId: number) => {
    activeGroupPopoverId.value = groupId;
};

const handleGroupPopoverHide = () => {
    activeGroupPopoverId.value = null;
};

const handleAgentPopoverShow = (agentId: number) => {
    activeAgentPopoverId.value = agentId;
};

const handleAgentPopoverHide = () => {
    activeAgentPopoverId.value = null;
};

// 展开/收起
const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};

// 选择智能体
const handleSelectAgent = (agent: any) => {
    selectedAgentId.value = agent.id;
    emit("select-agent", agent);
};

// 添加分组
const handleAddAgentGroup = async () => {
    renamePopTitle.value = "新建智能体分组";
    renamePopFetchFn.value = addAgentGroup;
    showRenamePop.value = true;
    await nextTick();
    renamePopRef.value?.open();
};

// 分组操作
const handleRenameGroup = async (group: any) => {
    renamePopTitle.value = "重命名分组";
    currentMoveAgent.value = group;
    showRenamePop.value = true;
    renamePopFetchFn.value = updateAgentGroup;
    await nextTick();
    renamePopRef.value?.open();
    renamePopRef.value?.setFormData({ id: group.id, name: group.name, sort: group.sort });
};

const handlePinGroup = async (group: any) => {
    try {
        await topAgentGroup({ id: group.id, type: group.is_top === 1 ? 2 : 1 });
        await getAgentGroupList();
        feedback.msgSuccess("操作成功");
    } catch (error) {
        feedback.msgError(error);
    }
};

const handleDeleteGroup = (group: any) => {
    useNuxtApp().$confirm({
        title: "确定删除分组吗？",
        message: "删除后将无法恢复",
        onConfirm: async () => {
            try {
                await deleteAgentGroup({ id: group.id });
                await getAgentGroupList();
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

// 智能体操作
const handleMoveAgent = (event: Event, agent: any) => {
    currentMoveAgent.value = agent;
    moveAgentPopupRef.value?.open({
        agent: agent,
    });
};

// 关闭移动弹窗
const handleCloseMovePopup = () => {
    showMoveAgentPopup.value = false;
    currentMoveAgent.value = null;
};

// 确认移动智能体
const handleConfirmMove = async ({ agent, targetGroupId }: { agent: any; targetGroupId: number }) => {
    try {
        await addAgentToGroup({ group_id: targetGroupId, robot_id: agent.id, type: agent.type });
        await getAgentGroupList();
        handleCloseMovePopup();
        handleAgentPopoverHide();
        feedback.msgSuccess("移动成功");
    } catch (error) {
        feedback.msgError(error);
    }
};

const handleRemoveAgent = async (agent: any, groupId: number) => {
    try {
        await deleteAgentFromGroup({ robot_id: agent.id, type: agent.type });
        await getAgentGroupList();
        handleAgentPopoverHide();
        feedback.msgSuccess("移除成功");
    } catch (error) {
        feedback.msgError(error);
    }
};

const confirmAgentGroup = () => {
    getAgentGroupList();
};

// 获取智能体分组列表
const getAgentGroupList = async () => {
    const { lists } = await getAgentGroupListApi({ page_no: 1, page_size: 1000 });

    const groupMap = new Map();

    // 添加默认分组
    groupMap.set(0, { name: "智能体", id: 0, agents: [{ name: "模型大管家", id: 0, type: "model_admin" }] });

    // 添加其他分组
    lists.forEach((group) => {
        groupMap.set(group.id, { ...group, agents: [] });
    });

    // 分配智能体到分组
    agentList.value.forEach((agent: { name: string; id: string; image: string; group_id: number }) => {
        const groupId = agent.group_id || 0;

        if (groupMap.has(groupId)) {
            groupMap.get(groupId).agents.push(agent);
        } else {
            groupMap.get(0).agents.push(agent);
        }
    });

    // 转换为数组
    agentGroupList.value = Array.from(groupMap.values());
};

const init = async () => {
    try {
        isLoading.value = true;
        await getAgentGroupList();

        // 加载搜索历史
        const savedHistory = localStorage.getItem("agent-search-history");
        if (savedHistory) {
            try {
                searchHistory.value = JSON.parse(savedHistory);
            } catch (e) {
                console.warn("Failed to parse search history:", e);
            }
        }
    } finally {
        isLoading.value = false;
    }
};

onUnmounted(() => {
    if (searchDebounceTimer.value) {
        clearTimeout(searchDebounceTimer.value);
    }
});

defineExpose({
    clearSelectedAgent: () => {
        selectedAgentId.value = -1;
    },
    selectAgent: (agentId: number) => {
        selectedAgentId.value = agentId;
    },
    init: (list: any[]) => {
        agentList.value = list;

        init();
    },
});
</script>

<style scoped lang="scss">
:deep(.el-input__wrapper) {
    background-color: #f5f5f5;
    border-radius: 12px;
    padding: 0 12px;
    &:not(.is-focus) {
        box-shadow: none;
    }
    .el-input__icon {
        color: #000;
    }
}

:deep(.el-collapse) {
    border: none;
    .el-collapse-item {
        margin-bottom: 8px;
    }
    .el-collapse-item__header {
        border-bottom: none;
        min-height: 34px;
        line-height: 34px;
        padding: 0 8px;
        border-radius: 10px;
        transition: all 0.2s;
        &:hover {
            background-color: #f5f5f5;
        }
    }
    .el-collapse-item__wrap {
        border-bottom: none;
    }
    .el-collapse-item__content {
        padding-bottom: 0;
    }
    .el-collapse-item__arrow {
        color: #94a3b8;
    }
}

.agent-item {
    @apply flex items-center gap-2 px-3 py-1.5 cursor-pointer rounded-xl relative overflow-hidden;

    &:hover,
    &.is-popover-open {
        @apply bg-[#F8F9FA] border-[#E5E7EB];
    }

    &.is-active {
        @apply bg-[#F1F2F3];
    }
}

.search-result-item {
    @apply flex items-center gap-3 px-3 py-2 cursor-pointer rounded-xl relative overflow-hidden transition-all;
    @apply border border-[transparent];

    &:hover {
        @apply bg-[#F8F9FA] border-[#E5E7EB];
    }

    &.is-active {
        @apply bg-gradient-to-r from-blue-50 to-purple-50 border-blue-200;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
    }
}

.modern-new-btn {
    @apply border-[none] shadow-[#0065fb]/20 transition-all transform;
    background: linear-gradient(135deg, #0065fb 0%, #2581ff 100%);
    &:hover {
        @apply -translate-y-0.5 shadow-[#0065fb]/30;
        filter: brightness(1.1);
    }
    &:active {
        @apply translate-y-0 scale-[0.98];
    }
}

.more-trigger {
    @apply w-6 h-6 rounded-lg flex items-center justify-center text-slate-400 cursor-pointer transition-all;

    &:hover {
        @apply bg-[#E5E6E8] text-slate-600;
    }
}

:deep(mark) {
    @apply bg-yellow-200 text-yellow-800 px-0.5 rounded;
}
</style>
