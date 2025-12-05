<template>
    <div class="h-full flex flex-col p-4">
        <!-- 页面头部 -->
        <div
            class="rounded-[20px] flex items-center justify-between gap-3 px-[30px]"
            style="
                background: linear-gradient(152deg, rgba(0, 101, 251, 0.88) -42.44%, rgba(255, 255, 255, 0) 12.19%)
                    rgb(255, 255, 255);
            ">
            <div class="flex items-center gap-3">
                <img src="@/assets/images/agent.svg" class="w-11 mt-7" />
                <div>
                    <div class="text-[#000000cc]">{{ ToolEnumMap[ToolEnum.AGENT] }}</div>
                    <div class="text-[#00000080]">
                        一键激活模块化智能体，精准执行流程、分析等多类任务，化身全能数字员工。
                    </div>
                </div>
            </div>
            <div>
                <ElButton color="#000000" class="!h-[30px]" size="small" @click="handleCozeSetting">
                    <img src="@/assets/images/coze_setting.png" class="w-[18px] h-[16px] mr-2" />
                    Coze令牌设置
                </ElButton>
                <ElPopover popper-class="!rounded-xl !p-2" trigger="click" :show-arrow="false">
                    <template #reference>
                        <ElButton type="primary" class="!h-[30px]" size="small">立即创建</ElButton>
                    </template>
                    <div>
                        <div
                            class="h-11 flex items-center gap-x-2 cursor-pointer px-3 rounded-lg hover:bg-[#F6F6F6] hover:shadow-[0_0_0_1px_rgba(239,239,239,1)]"
                            v-for="(item, index) in tabs"
                            :key="index"
                            @click="handleCreate(item.value)">
                            <span class="flex w-5 h-5 rounded items-center justify-center bg-primary">
                                <Icon :name="item.icon" size="12"></Icon>
                            </span>
                            {{ item.label }}
                        </div>
                    </div>
                </ElPopover>
            </div>
        </div>
        <!-- 主内容区 -->
        <div class="grow min-h-0 flex flex-col bg-white rounded-[20px] mt-4">
            <!-- Tabs导航 -->
            <ElTabs v-model="currentTab" @tab-click="handleTabClick">
                <ElTabPane v-for="item in tabs" :key="item.value" :label="item.label" :name="item.value"> </ElTabPane>
            </ElTabs>
            <!-- 无限滚动列表 -->
            <div
                class="grow min-h-0 flex flex-col overflow-y-auto"
                v-infinite-scroll="load"
                :infinite-scroll-immediate="false"
                :infinite-scroll-disabled="!pager.isLoad"
                :infinite-scroll-distance="10">
                <template v-if="pager.lists.length">
                    <div
                        class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4 p-5">
                        <!-- 智能体卡片 -->
                        <div
                            v-for="(item, index) in pager.lists"
                            :key="index"
                            class="card-item group"
                            @click="handleAgentChatting(item)">
                            <div
                                class="top"
                                :style="{
                                    background: `url(${item.bg_image || AgentBg}) center center / cover no-repeat`,
                                }">
                                <div
                                    class="w-[72px] h-[72px] bg-white rounded-full p-[5px] absolute left-1/2 -bottom-[20px] -translate-x-1/2">
                                    <ElImage
                                        :src="item.image || item.avatar"
                                        class="w-full h-full rounded-full"
                                        lazy></ElImage>
                                </div>
                            </div>
                            <div class="px-3 mt-10 mb-6 w-full">
                                <div class="text-[14px] text-center line-clamp-1">{{ item.name }}</div>
                                <div class="mt-3 line-clamp-2 text-center text-[#737373] leading-7 h-12">
                                    {{ item.intro || item.introduced }}
                                </div>
                            </div>
                            <div class="text-xs text-[#999999] w-full mb-3 px-4">创建人：{{ item.source_text }}</div>
                            <!-- 悬浮操作菜单 -->
                            <div
                                class="absolute right-4 bottom-2 z-10 invisible group-hover:visible w-6 h-6"
                                v-if="item.source == 1">
                                <handle-menu :data="item" :menu-list="handleMenuList" :horizontal="true" />
                            </div>
                        </div>
                    </div>
                    <div v-if="!pager.isLoad" class="text-tx-secondary text-center text-xs w-full py-4">
                        暂无更多了~
                    </div>
                </template>
                <!-- 空状态 -->
                <div class="h-full flex items-center justify-center" v-else>
                    <ElEmpty />
                </div>
            </div>
        </div>
        <!-- 弹窗组件 -->
        <coze-setting
            ref="cozeSettingRef"
            v-if="showCozeSetting"
            @close="showCozeSetting = false"
            @success="getCozeSettingDetail"></coze-setting>
        <coze-edit ref="cozeEditRef" v-if="showCozeEdit" @close="showCozeEdit = false" @success="resetPage"></coze-edit>
        <coze-flow-edit
            ref="cozeFlowEditRef"
            v-if="showCozeFlowEdit"
            @close="showCozeFlowEdit = false"
            @success="resetPage"></coze-flow-edit>
    </div>
</template>

<script setup lang="ts">
import { getAgentList, deleteAgent, addAgent, getCozeAgentList, cozeAgentDelete, cozeConfigDetail } from "@/api/agent";
import { ToolEnumMap, ToolEnum } from "@/enums/appEnums";
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
:deep(.el-tabs) {
    --el-border-color-light: #0000000d;
    .el-tabs__header {
        margin-bottom: 0;
        .el-tabs__nav {
            height: 62px;
            padding: 0 20px;
            .el-tabs__item {
                height: 62px;
                padding: 0 20px;
                font-weight: bold;
                color: rgba(0, 0, 0, 0.3);
                &.is-active {
                    color: var(--el-color-black);
                }
            }
            .el-tabs__nav-next,
            .el-tabs__nav-prev {
                line-height: 62px;
            }
        }
        .el-tabs__active-bar {
            height: 1px;
        }
        .el-tabs__nav-wrap::after {
            height: 1px;
        }
    }
}
.card-item {
    @apply shadow-light rounded-[20px] bg-white px-0 relative cursor-pointer flex flex-col items-center justify-center hover:scale-[1.02] transition-all duration-300;
    .top {
        @apply h-[120px] w-full rounded-tl-[20px] rounded-tr-[20px] relative;
    }
}
</style>
