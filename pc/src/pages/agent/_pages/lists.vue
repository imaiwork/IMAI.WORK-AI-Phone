<template>
    <div class="h-full flex flex-col min-w-[1000px] px-4 pb-4">
        <div
            class="shrink-0 h-[120px] rounded-[20px] bg-white border border-br px-10 flex items-center justify-between relative overflow-hidden">
            <div class="flex items-center gap-6 relative z-10">
                <img src="@/assets/images/agent.svg" class="w-20 h-20 mt-10" />
                <div>
                    <div class="text-[20px] font-[900] text-[#1E293B] mb-1">{{ ToolEnumMap[ToolEnum.AGENT] }}中心</div>
                    <div class="text-base font-medium text-[#94A3B8]">
                        一键激活模块化智能体，精准执行流程、分析等多类任务，化身您的数字员工。
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 relative z-10">
                <button class="coze-config-btn" @click="handleCozeSetting">
                    <img src="@/assets/images/coze_setting.png" class="w-4 h-4 mr-2" />
                    令牌配置
                </button>

                <ElPopover
                    popper-class="!rounded-2xl !shadow-light !p-0"
                    width="180px"
                    trigger="click"
                    :show-arrow="false">
                    <template #reference>
                        <ElButton type="primary" class="create-main-btn">
                            <Icon name="el-icon-Plus" />
                            <span class="ml-1">立即创建</span>
                        </ElButton>
                    </template>
                    <div class="p-2 space-y-1">
                        <div
                            v-for="(item, index) in tabs"
                            :key="index"
                            class="flex items-center gap-3 p-2.5 rounded-xl cursor-pointer hover:bg-[#0065fb]/5 transition-all group"
                            @click="handleCreate(item.value)">
                            <div
                                class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center transition-colors">
                                <Icon :name="item.icon" :size="16" />
                            </div>
                            <span class="text-[13px] font-black text-[#475569] group-hover:text-primary">{{
                                item.label
                            }}</span>
                        </div>
                    </div>
                </ElPopover>
            </div>
        </div>
        <div class="grow min-h-0 flex flex-col bg-white rounded-[20px] border border-br mt-4 overflow-hidden">
            <div class="px-6 bg-[#f8fafc]/50 border-b border-[#F1F5F9]">
                <ElTabs v-model="currentTab" class="custom-tabs" @tab-click="handleTabClick">
                    <ElTabPane v-for="item in tabs" :key="item.value" :label="item.label" :name="item.value">
                    </ElTabPane>
                </ElTabs>
            </div>

            <div class="grow min-h-0">
                <ElScrollbar :distance="20" @end-reached="load">
                    <template v-if="pager.lists.length">
                        <div class="grid grid-cols-4 2xl:grid-cols-5 gap-6 p-8">
                            <div
                                v-for="(item, index) in pager.lists"
                                :key="index"
                                class="agent-card group"
                                @click="handleAgentChatting(item)">
                                <div
                                    class="card-cover"
                                    :style="{ background: `url(${item.bg_image || AgentBg}) center/cover` }">
                                    <div class="cover-mask"></div>
                                    <div class="avatar-wrapper">
                                        <ElImage :src="item.image || item.avatar" class="w-full h-full" lazy />
                                    </div>
                                </div>

                                <div class="p-5 pt-10 flex flex-col items-center text-center">
                                    <div
                                        class="text-[15px] font-[900] text-[#1E293B] mb-2 group-hover:text-primary transition-colors">
                                        {{ item.name }}
                                    </div>
                                    <div class="text-xs font-medium text-[#94A3B8] leading-relaxed line-clamp-2 h-9">
                                        {{ item.intro || item.introduced || "暂无描述信息" }}
                                    </div>

                                    <div class="w-full h-[1px] bg-[#F1F5F9] my-4"></div>

                                    <div class="w-full flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <div
                                                class="w-5 h-5 rounded-full bg-[#0065fb]/10 flex items-center justify-center">
                                                <Icon name="el-icon-User" :size="10" />
                                            </div>
                                            <span class="text-[11px] font-medium text-[#94A3B8]">{{
                                                item.source_text
                                            }}</span>
                                        </div>

                                        <div
                                            v-if="item.source == 1"
                                            @click.stop
                                            class="hover:scale-110 transition-transform">
                                            <handle-menu :data="item" :menu-list="handleMenuList" :horizontal="true" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <load-text :is-load="pager.isLoad"></load-text>
                    </template>
                    <div v-else class="h-full flex flex-col items-center justify-center grayscale opacity-50">
                        <ElEmpty description="暂无可用智能体" />
                    </div>
                </ElScrollbar>
            </div>
        </div>
        <coze-setting
            ref="cozeSettingRef"
            v-if="showCozeSetting"
            @close="showCozeSetting = false"
            @success="getCozeSettingDetail" />
        <coze-edit ref="cozeEditRef" v-if="showCozeEdit" @close="showCozeEdit = false" @success="resetPage" />
        <coze-flow-edit
            ref="cozeFlowEditRef"
            v-if="showCozeFlowEdit"
            @close="showCozeFlowEdit = false"
            @success="resetPage" />
    </div>
</template>

<script setup lang="ts">
import { getAgentList, deleteAgent, addAgent, getCozeAgentList, cozeAgentDelete, cozeConfigDetail } from "@/api/agent";
import { ToolEnumMap, ToolEnum } from "@/enums/appEnums";
import { KnbTypeEnum } from "@/pages/knowledge_base/_enums";
import { agentExamplePrompt } from "@/config/common";
import { HandleMenuType } from "@/components/handle-menu/typings";
import AgentBg from "@/assets/images/agent_bg.png";
import { AgentTypeEnum } from "../_enums";
import CozeSetting from "../_components/coze-setting.vue";
import CozeEdit from "../_components/coze-edit.vue";
import CozeFlowEdit from "../_components/coze-flow-edit.vue";
import feedback from "@/utils/feedback";

/**
 * @description 智能体列表页面
 * @summary 展示不同类型的智能体，并提供创建、编辑、删除、对话等管理功能。
 */

// 定义智能体列表项接口
interface AgentItem {
    id: number;
    name: string;
    intro?: string;
    introduced?: string;
    image?: string;
    avatar?: string;
    bg_image?: string;
    coze_id?: number;
}

// 定义Tab项接口
interface TabItem {
    label: string;
    icon: string;
    value: AgentTypeEnum;
}

const router = useRouter();
const nuxtApp = useNuxtApp();

// --- Tabs配置 ---
const TABS: TabItem[] = [
    { label: "智能体", icon: "local-icon-agent", value: AgentTypeEnum.AGENT },
    { label: "Coze智能体", icon: "local-icon-coze_agent", value: AgentTypeEnum.COZE_AGENT },
    { label: "Coze工作流", icon: "local-icon-coze_flow", value: AgentTypeEnum.COZE_FLOW },
];
const currentTab = ref<AgentTypeEnum>(AgentTypeEnum.AGENT);
const tabs = computed(() => TABS);

// --- 数据获取与分页 ---
const queryParams = reactive({ page_no: 1 });

// 根据当前Tab动态选择获取列表的API
const getListsAPI = (params: any) => {
    return currentTab.value === AgentTypeEnum.AGENT
        ? getAgentList(params)
        : getCozeAgentList({
              ...params,
              type: currentTab.value === AgentTypeEnum.COZE_AGENT ? 1 : 2,
          });
};

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getListsAPI,
    params: queryParams,
    isScroll: true,
});

// --- 弹窗管理 ---
const showCozeSetting = ref(false);
const showCozeEdit = ref(false);
const showCozeFlowEdit = ref(false);
const cozeSettingRef = shallowRef<InstanceType<typeof CozeSetting>>();
const cozeEditRef = shallowRef<InstanceType<typeof CozeEdit>>();
const cozeFlowEditRef = shallowRef<InstanceType<typeof CozeFlowEdit>>();

// --- Coze配置 ---
const cozeSettingConfig = ref<{ id?: string | number }>();

/**
 * @description 检查是否已配置Coze Token
 */
const checkCozeConfig = () => {
    if (!cozeSettingConfig.value?.id) {
        feedback.msgWarning("请先设置Coze配置Token");
        handleCozeSetting();
        return false;
    }
    return true;
};

// --- 操作处理 ---

// 定义不同类型智能体的编辑和删除处理器
const editHandlers: Record<AgentTypeEnum, (row?: AgentItem) => void> = {
    [AgentTypeEnum.AGENT]: (row) => handleAgentEdit(row),
    [AgentTypeEnum.COZE_AGENT]: (row) => handleCozeEdit(row),
    [AgentTypeEnum.COZE_FLOW]: (row) => handleCozeFlowEdit(row),
};

const deleteApis: Record<AgentTypeEnum, (params: { id: number }) => Promise<any>> = {
    [AgentTypeEnum.AGENT]: deleteAgent,
    [AgentTypeEnum.COZE_AGENT]: cozeAgentDelete,
    [AgentTypeEnum.COZE_FLOW]: cozeAgentDelete,
};

// 处理创建操作
const handleCreate = (type: AgentTypeEnum) => {
    editHandlers[type]?.();
};

// 处理编辑操作
const handleEdit = (row: AgentItem) => {
    editHandlers[currentTab.value]?.(row);
};

// 处理删除操作
const handleDelete = async (item: AgentItem) => {
    await nuxtApp.$confirm({
        message: "确定删除吗？",
        onConfirm: async () => {
            try {
                const deleteAPI = deleteApis[currentTab.value];
                await deleteAPI({ id: item.id });
                pager.lists = pager.lists.filter((listItem) => listItem.id !== item.id);
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgWarning("删除失败");
            }
        },
    });
};

// 查看对话数据 (仅限标准智能体)
const handleViewChattingData = (row: AgentItem) => {
    router.push({
        path: "/agent/chatting_log",
        query: { agent_id: row.id, coze_id: row.coze_id },
    });
};

// 动态生成操作菜单
const handleMenuList = computed<HandleMenuType[]>(() => {
    const baseMenu: HandleMenuType[] = [
        { label: "编辑", icon: "local-icon-edit3", click: handleEdit },
        { label: "删除", icon: "local-icon-delete", click: handleDelete },
    ];
    if (currentTab.value === AgentTypeEnum.AGENT) {
        baseMenu.splice(1, 0, {
            label: "对话数据",
            icon: "local-icon-upload2",
            click: handleViewChattingData,
        });
    }
    return baseMenu;
});

// --- 具体类型的编辑实现 ---

// 编辑标准智能体
const handleAgentEdit = async (row?: AgentItem) => {
    if (row) {
        router.push({ query: { type: "edit", id: String(row.id) } });
    } else {
        try {
            const data = await addAgent({
                context_num: 3,
                kb_type: KnbTypeEnum.VECTOR,
                roles_prompt: agentExamplePrompt,
            });
            router.push({ query: { type: "edit", id: String(data.id) } });
        } catch (error: any) {
            feedback.msgError(error || "创建智能体失败");
        }
    }
};

// 编辑Coze智能体
const handleCozeEdit = async (row?: AgentItem) => {
    if (!checkCozeConfig()) return;
    showCozeEdit.value = true;
    await nextTick();
    cozeEditRef.value?.open();
    if (row) {
        cozeEditRef.value?.setFormData(row);
    }
};

// 编辑Coze工作流
const handleCozeFlowEdit = async (row?: AgentItem) => {
    if (!checkCozeConfig()) return;
    showCozeFlowEdit.value = true;
    await nextTick();
    cozeFlowEditRef.value?.open();
    if (row?.id) {
        cozeFlowEditRef.value?.getDetail(row.id);
    }
};

// --- 其他逻辑 ---

// 打开Coze令牌设置弹窗
const handleCozeSetting = async () => {
    showCozeSetting.value = true;
    await nextTick();
    cozeSettingRef.value?.open();
    cozeSettingRef.value?.setFormData(cozeSettingConfig.value);
};

// 切换Tab
const handleTabClick = (tab: any) => {
    const newTabValue = tab.paneName as AgentTypeEnum;
    if (currentTab.value === newTabValue) return;
    currentTab.value = newTabValue;
    resetPage();
};

// 进入聊天页面
const handleAgentChatting = (row: AgentItem) => {
    router.push({
        path: "/agent/chatting",
        query: {
            agent_id: String(row.id),
            coze_id: row.coze_id ? String(row.coze_id) : undefined,
            type: currentTab.value,
        },
    });
};

// 无限滚动加载更多
const load = async () => {
    queryParams.page_no += 1;
    await getLists();
};

// 获取Coze配置详情
const getCozeSettingDetail = async () => {
    try {
        cozeSettingConfig.value = await cozeConfigDetail();
    } catch (error: any) {
        console.warn("获取Coze配置失败，部分功能可能受限:", error);
    }
};

// 初始化
onMounted(() => {
    getLists();
    getCozeSettingDetail();
});
</script>

<style scoped lang="scss">
.coze-config-btn {
    @apply flex items-center h-10 px-4 rounded-xl bg-black border border-br text-[13px] font-black text-white;
}

.create-main-btn {
    @apply h-10 rounded-xl px-6 font-black text-[14px] border-none;
}

.agent-card {
    @apply bg-white border border-[#F1F5F9] rounded-[20px] overflow-hidden transition-all duration-300 cursor-pointer;

    &:hover {
        @apply border-primary transform shadow-[0_20px_40px_-12px_rgba(0,0,0,0.08)];
        transform: translateY(-4px);
    }

    .card-cover {
        @apply h-28 w-full relative;
        .cover-mask {
            @apply absolute inset-0 bg-gradient-to-b from-[#000000]/0 to-[#000000]/20;
        }
    }

    .avatar-wrapper {
        @apply w-20 h-20 rounded-[28px] border-4 border-white bg-white overflow-hidden absolute left-1/2 -bottom-8 -translate-x-1/2;
        box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.1);
    }
}
</style>
