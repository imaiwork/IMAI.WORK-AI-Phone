<template>
    <div class="h-full flex flex-col bg-[#FFFFFF]">
        <div class="grow min-h-[0]">
            <ElScrollbar>
                <div class="px-[30px] py-[24px]">
                    <div class="flex items-center gap-[10px] mb-[24px]">
                        <div class="w-[4px] h-[18px] bg-primary rounded-full"></div>
                        <span class="text-[16px] font-[900] text-tx-primary">能力扩展与技能库</span>
                    </div>

                    <div class="skill-collapse-container">
                        <ElCollapse v-model="activeNames" class="custom-collapse">
                            <ElCollapseItem name="1" disabled>
                                <template #title>
                                    <div class="skill-header">
                                        <div class="flex items-center gap-x-[12px]">
                                            <div
                                                class="collapse-icon-box"
                                                :class="{ 'is-active': activeNames.includes('1') }">
                                                <Icon name="el-icon-ArrowRight" />
                                            </div>
                                            <span class="title">插件系统</span>
                                            <span class="badge-dev shrink-0">功能开发中</span>
                                        </div>
                                        <div class="desc">允许智能体调用外部 API（搜索、绘图、网页分析等）</div>
                                    </div>
                                </template>
                            </ElCollapseItem>

                            <ElCollapseItem name="2">
                                <template #title>
                                    <div class="skill-header">
                                        <div class="flex items-center justify-between w-full pr-[16px]">
                                            <div class="flex items-center gap-x-[12px]">
                                                <div
                                                    class="collapse-icon-box"
                                                    :class="{ 'is-active': activeNames.includes('2') }">
                                                    <Icon name="el-icon-ArrowRight" />
                                                </div>
                                                <span class="title">工作流编排</span>
                                            </div>
                                            <ElButton type="primary" @click.stop="handleWorkflowEdit()">
                                                <Icon name="el-icon-Plus" :size="14" />
                                                <span>配置工作流</span>
                                            </ElButton>
                                        </div>
                                        <div class="desc pl-[32px]">
                                            通过可视化画布编排复杂业务逻辑，实现稳定的流程输出
                                        </div>
                                    </div>
                                </template>

                                <div class="px-[32px] py-[16px]">
                                    <div v-if="formData.flow_status === 1" class="workflow-active-card">
                                        <div class="flex items-center gap-x-[16px]">
                                            <div
                                                class="w-[44px] h-[44px] bg-white rounded-[10px] flex items-center justify-center shadow-sm">
                                                <Icon name="local-icon-flow2" size="28" color="#0065fb"></Icon>
                                            </div>
                                            <div class="flex flex-col gap-[4px]">
                                                <span class="font-[900] text-tx-primary">{{
                                                    formData.flow_config.bot_id
                                                }}</span>
                                                <div class="flex items-center gap-[8px]">
                                                    <span
                                                        class="text-[11px] bg-blue-100 text-primary px-[8px] py-[2px] rounded-[4px] font-medium"
                                                        >Token 鉴权已开启</span
                                                    >
                                                    <span class="text-xs text-tx-secondary opacity-60"
                                                        >ID: {{ formData.flow_config.app_id }}</span
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                        <ElButton link type="danger" @click.stop="handleDeleteWorkflow()">
                                            <Icon name="el-icon-Delete" />
                                            <span class="ml-1">移除</span>
                                        </ElButton>
                                    </div>
                                    <ElEmpty
                                        v-else
                                        :image-size="60"
                                        description="点击右上角按钮添加工作流"
                                        class="py-[20px]" />
                                </div>
                            </ElCollapseItem>

                            <ElCollapseItem name="3">
                                <template #title>
                                    <div class="skill-header">
                                        <div class="flex items-center justify-between w-full pr-[16px]">
                                            <div class="flex items-center gap-x-[12px]">
                                                <div
                                                    class="collapse-icon-box"
                                                    :class="{ 'is-active': activeNames.includes('3') }">
                                                    <Icon name="el-icon-ArrowRight" />
                                                </div>
                                                <span class="title">固定问答话术</span>
                                            </div>
                                            <ElButton type="primary" @click.stop="handleKeywordsAdd()">
                                                <Icon name="el-icon-Plus" :size="14" />
                                                <span>新增话术</span>
                                            </ElButton>
                                        </div>
                                        <div class="desc pl-[32px]">
                                            针对特定关键词提供 100% 准确的官方回复，不消耗 Token
                                        </div>
                                    </div>
                                </template>

                                <div class="px-[32px] py-[20px] bg-white">
                                    <div class="threshold-panel mb-[20px]">
                                        <div class="flex items-center gap-[12px] flex-1">
                                            <span class="text-[13px] font-[900] text-tx-primary shrink-0"
                                                >匹配阈值</span
                                            >
                                            <ElSlider
                                                v-model="formData.threshold"
                                                :min="0"
                                                :max="1"
                                                :step="0.01"
                                                class="flex-1 px-[10px]" />
                                            <ElInputNumber
                                                v-model="formData.threshold"
                                                controls-position="right"
                                                :min="0"
                                                :max="1"
                                                :step="0.01"
                                                class="w-[90px]" />
                                        </div>
                                        <div class="w-[1px] h-[24px] bg-gray-200 mx-[20px]"></div>
                                        <ElButton @click="handleImportKeywords()" class="import-btn">
                                            <Icon name="el-icon-Upload" /><span class="ml-[4px]">批量导入</span>
                                        </ElButton>
                                    </div>

                                    <div class="table-wrapper">
                                        <ElTable
                                            :data="pager.lists"
                                            stripe
                                            v-loading="pager.loading"
                                            :cell-style="{ borderBottom: 'none' }"
                                            max-height="400px">
                                            <ElTableColumn label="模式" width="100">
                                                <template #default="{ row }">
                                                    <span
                                                        :class="
                                                            row.match_type === 0 ? 'text-orange-500' : 'text-primary'
                                                        "
                                                        class="text-xs font-medium">
                                                        {{ row.match_type === 0 ? "模糊" : "精确" }}
                                                    </span>
                                                </template>
                                            </ElTableColumn>
                                            <ElTableColumn label="关键词" prop="keyword" min-width="140" />
                                            <ElTableColumn label="回复预览" min-width="200">
                                                <template #default="{ row }">
                                                    <span
                                                        class="text-tx-secondary truncate block cursor-pointer hover:text-primary"
                                                        @click="handleKeywordsEdit(row)">
                                                        {{ row.content || "点击查看详情..." }}
                                                    </span>
                                                </template>
                                            </ElTableColumn>
                                            <ElTableColumn label="操作" width="110" align="right">
                                                <template #default="{ row }">
                                                    <div class="flex justify-end gap-[10px]">
                                                        <ElButton link type="primary" @click="handleKeywordsEdit(row)"
                                                            >编辑</ElButton
                                                        >
                                                        <ElButton
                                                            link
                                                            type="danger"
                                                            @click="handleKeywordsDelete(row.id)"
                                                            >删除</ElButton
                                                        >
                                                    </div>
                                                </template>
                                            </ElTableColumn>
                                        </ElTable>
                                    </div>
                                    <div class="mt-[16px] flex justify-end">
                                        <pagination v-model="pager" layout="prev, pager, next" @change="getLists" />
                                    </div>
                                </div>
                            </ElCollapseItem>
                        </ElCollapse>
                    </div>
                </div>
            </ElScrollbar>
        </div>

        <div class="h-[80px] border-t border-br flex items-center justify-center bg-white shrink-0">
            <ElButton type="primary" class="global-save-btn" :loading="isLockSubmit" @click="lockSubmit">
                更新技能设置
            </ElButton>
        </div>
    </div>

    <keywords-edit v-if="showKeywords" ref="keywordsEditRef" @close="showKeywords = false" @success="getLists" />
    <workflow-edit
        v-if="showWorkflow"
        ref="workflowEditRef"
        @close="showWorkflow = false"
        @success="getWorkFlowSuccess" />
    <import-data
        v-if="showImportKeywords"
        ref="importKeywordsRef"
        title="批量导入关键词"
        :agent-id="props.agentId"
        @close="showImportKeywords = false"
        @success="getLists" />
</template>
<script setup lang="ts">
import { robotKeywordsLists, deleteRobotKeywords } from "@/api/agent";
import KeywordsEdit from "./keywords-edit.vue";
import WorkflowEdit from "./workflow-edit.vue";
import ImportData from "../import-data.vue";
import { Agent } from "../../_enums";

/**
 * @description 智能体技能设置组件
 * @summary 管理插件、工作流和固定话术等技能。
 */

const props = withDefaults(
    defineProps<{
        modelValue: any;
        agentId: string | number;
    }>(),
    {
        agentId: "",
    }
);

const nuxtApp = useNuxtApp();

const formData = defineModel<Agent>("modelValue");

// 固定话术的分页和查询
const queryParams = reactive({
    keyword: "",
    robot_id: props.agentId as string,
});

const { pager, getLists } = usePaging({
    fetchFun: robotKeywordsLists,
    params: queryParams,
});

// 折叠面板的激活状态
const activeNames = ref([]);

// 工作流编辑弹窗
const showWorkflow = ref(false);
const workflowEditRef = shallowRef<InstanceType<typeof WorkflowEdit>>();
const handleWorkflowEdit = async (row?: any) => {
    showWorkflow.value = true;
    await nextTick();
    workflowEditRef.value?.open();
    if (row) {
        workflowEditRef.value?.setFormData(row);
    }
};

const getWorkFlowSuccess = (data: any) => {
    props.modelValue.flow_status = 1;
    props.modelValue.flow_config = data;
};

/**
 * @description 删除工作流
 */
const handleDeleteWorkflow = () => {
    nuxtApp.$confirm({
        message: "确定删除该工作流吗？",
        onConfirm: async () => {
            try {
                formData.value.flow_status = 0;
                formData.value.flow_config = {
                    workflow_id: "",
                    bot_id: "",
                    app_id: "",
                    api_token: "",
                };
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError("删除失败");
            }
        },
    });
};

// 关键词编辑弹窗
const showKeywords = ref(false);
const keywordsEditRef = shallowRef<InstanceType<typeof KeywordsEdit>>();
const handleKeywordsAdd = async () => {
    showKeywords.value = true;
    await nextTick();
    keywordsEditRef.value?.open();
};
const handleKeywordsEdit = async (row: any) => {
    showKeywords.value = true;
    await nextTick();
    keywordsEditRef.value?.open();
    keywordsEditRef.value?.setFormData(row);
};

/**
 * @description 删除关键词
 */
const handleKeywordsDelete = async (id: number) => {
    await nuxtApp.$confirm({
        message: "确定删除该问答话术吗？",
        onConfirm: async () => {
            try {
                await deleteRobotKeywords({ id });
                feedback.msgSuccess("删除成功");
                getLists(); // 刷新列表
            } catch (error) {
                feedback.msgError("删除失败");
            }
        },
    });
};

/**
 * @description 批量导入关键词
 */
const showImportKeywords = ref(false);
const importKeywordsRef = shallowRef<InstanceType<typeof ImportData>>();
const handleImportKeywords = async () => {
    showImportKeywords.value = true;
    await nextTick();
    importKeywordsRef.value?.open();
};

/**
 * @description 提交保存（占位）
 * @summary 当前组件的子项（如关键词、工作流）在各自的弹窗中独立保存，此处的保存按钮可能用于后续整体保存的逻辑。
 */
const handleSubmit = async () => {
    try {
        // TODO: 实现技能设置的整体保存逻辑
        feedback.msgSuccess("保存成功");
    } catch (error) {
        feedback.msgError((error as string) || "提交失败");
    }
};

const { lockFn: lockSubmit, isLock: isLockSubmit } = useLockFn(handleSubmit);

// 组件挂载时获取固定话术列表
onMounted(() => {
    getLists();
});
</script>
<style scoped lang="scss">
.skill-collapse-container {
    --el-collapse-border-color: transparent;
    --el-collapse-header-bg-color: transparent;
}

.skill-header {
    @apply flex flex-col justify-center w-full py-[16px] transition-all;

    .title {
        @apply text-[15px] font-[900] text-tx-primary;
    }
    .desc {
        @apply text-xs text-tx-secondary mt-[4px] font-normal;
    }

    .collapse-icon-box {
        @apply w-[24px] h-[24px] flex items-center justify-center rounded-[6px] bg-gray-50 text-gray-400 transition-all;
        &.is-active {
            @apply rotate-90 bg-blue-50 text-primary;
        }
    }
}

.badge-dev {
    @apply text-[11px] text-white bg-orange-400 px-[8px] py-[2px] rounded-[6px] font-medium h-fit;
}

.workflow-active-card {
    @apply flex items-center justify-between p-[16px] bg-blue-50 border border-primary-light-8 rounded-lg;
}

.threshold-panel {
    @apply flex items-center p-[16px] bg-gray-50 rounded-[12px] border border-[transparent];
}

.import-btn {
    @apply h-[36px] rounded-[8px] border-br font-medium text-tx-primary hover:text-primary;
}

.table-wrapper {
    @apply border border-br rounded-2xl overflow-hidden;
}

:deep(.el-table) {
    thead th.el-table__cell.is-leaf {
        border-top: none;
    }
    .el-table--border .el-table__inner-wrapper:after,
    .el-table--border:after,
    .el-table--border:before,
    .el-table__inner-wrapper:before {
        background-color: transparent;
    }
}

.global-save-btn {
    @apply w-[360px] h-[48px] rounded-xl font-[900] shadow-[0_10px_20px_-5px_rgba(0,101,251,0.2)];
}

:deep(.el-collapse-item__header) {
    @apply h-auto border-b border-br-extra-light mb-[8px] leading-[24px];
}
:deep(.el-collapse-item__wrap) {
    @apply border-none bg-[transparent];
}
:deep(.el-table) {
    --el-table-header-bg-color: var(--gray-50);
    th {
        @apply font-[900] text-gray-500 text-xs;
    }
}
</style>
