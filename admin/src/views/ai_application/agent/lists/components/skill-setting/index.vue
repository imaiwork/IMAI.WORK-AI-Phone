<template>
    <div class="h-full flex flex-col py-6">
        <div class="grow min-h-0">
            <el-scrollbar>
                <div class="px-[30px]">
                    <div class="font-bold">技能</div>
                    <div class="mt-3">
                        <el-collapse v-model="activeNames">
                            <!-- 插件技能 (开发中) -->
                            <el-collapse-item name="1" disabled>
                                <template #title>
                                    <div class="leading-5 w-full">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="transition-all duration-300"
                                                :class="{
                                                    'rotate-180': activeNames.includes('1'),
                                                }">
                                                <Icon name="el-icon-ArrowUp" color="#00000080"></Icon>
                                            </span>
                                            <span>插件</span>
                                            <span
                                                class="text-xs text-white bg-[#FF8D1A] px-[6px] py-[2px] rounded inline-block">
                                                功能开发中
                                            </span>
                                        </div>
                                        <div class="text-[#00000080] mt-1 text-left">
                                            插件能够让智能体调用外部
                                            API，例如搜索信息、浏览网页、生成图片等，扩展智能体的能力和使用场景。
                                        </div>
                                    </div>
                                </template>
                                <template #icon>
                                    <div
                                        class="w-5 h-5 bg-[#ECECEC] flex items-center justify-center rounded"
                                        @click.stop>
                                        <Icon name="el-icon-Plus" :size="12"> </Icon>
                                    </div>
                                </template>
                            </el-collapse-item>
                            <!-- 工作流技能 -->
                            <el-collapse-item name="2" v-if="false">
                                <template #title>
                                    <div class="leading-5 w-full">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="transition-all duration-300"
                                                :class="{
                                                    'rotate-180': activeNames.includes('2'),
                                                }">
                                                <Icon name="el-icon-ArrowUp" color="#00000080"></Icon>
                                            </span>
                                            <span>工作流</span>
                                        </div>
                                        <div class="text-[#00000080] mt-1 text-left">
                                            工作流支持通过可视化的方式，对插件、大语言模型、代码块等功能进行组合，从而实现复杂、稳定的业务流程编排。
                                        </div>
                                    </div>
                                </template>
                                <template #icon>
                                    <div
                                        class="w-5 h-5 bg-[#ECECEC] flex items-center justify-center rounded cursor-pointer"
                                        @click.stop="handleWorkflowEdit()">
                                        <Icon name="el-icon-Plus" :size="12"> </Icon>
                                    </div>
                                </template>
                                <div>
                                    <div
                                        class="h-10 rounded-lg bg-[#F5F5F5] mb-3 px-4 flex items-center justify-between gap-x-4"
                                        v-if="formData.flow_status === 1">
                                        <div class="flex items-center gap-x-3">
                                            <Icon name="local-icon-flow2" size="24"></Icon>
                                            <span>{{ formData.flow_config.bot_id }}</span>
                                            <span class="text-xs text-[#666976] bg-[#ECEDF3] px-3 py-1">
                                                Workflow_id：{{ formData.flow_config.workflow_id }}
                                            </span>
                                        </div>
                                        <div>
                                            <div
                                                class="w-4 h-4 rounded-full flex items-center justify-center cursor-pointer bg-primary"
                                                @click.stop="handleDeleteWorkflow()">
                                                <Icon name="el-icon-Close" color="#ffffff" size="10"></Icon>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center text-[#00000080] text-sm" v-else>
                                        <el-empty description="暂未添加工作流" :image-size="80" />
                                    </div>
                                </div>
                            </el-collapse-item>
                            <!-- 固定话术技能 -->
                            <el-collapse-item name="3">
                                <template #title>
                                    <div class="leading-5 w-full">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="transition-all duration-300"
                                                :class="{
                                                    'rotate-180': activeNames.includes('3'),
                                                }">
                                                <Icon name="el-icon-ArrowUp" color="#00000080"></Icon>
                                            </span>
                                            <span>固定话术</span>
                                        </div>
                                        <div class="text-[#00000080] mt-1 text-left">
                                            固定话术是智能体在对话过程中，根据用户输入的特定关键词，自动回复的固定内容。
                                        </div>
                                    </div>
                                </template>
                                <template #icon>
                                    <div
                                        class="w-5 h-5 bg-[#ECECEC] flex items-center justify-center rounded cursor-pointer"
                                        @click.stop="handleKeywordsAdd()">
                                        <Icon name="el-icon-Plus" :size="12"> </Icon>
                                    </div>
                                </template>
                                <div class="flex items-center justify-between gap-x-6 mb-2 px-4">
                                    <div class="flex items-center w-full gap-x-4">
                                        <div>模糊匹配阈值</div>
                                        <div class="flex-1">
                                            <ElSlider v-model="formData.threshold" :min="0" :max="1" :step="0.01" />
                                        </div>
                                        <ElInputNumber
                                            v-model="formData.threshold"
                                            controls-position="right"
                                            :min="0"
                                            :max="1"
                                            :step="0.01">
                                        </ElInputNumber>
                                    </div>
                                </div>
                                <div class="border border-[var(--el-border-color-lighter)] rounded-lg">
                                    <el-table
                                        v-loading="pager.loading"
                                        :data="pager.lists"
                                        stripe
                                        :header-row-style="{ height: '62px' }"
                                        :row-style="{ height: '50px' }"
                                        max-height="300px">
                                        <el-table-column label="匹配模式" width="120">
                                            <template #default="{ row }">
                                                {{ row.match_type === 0 ? "模糊匹配" : "精确匹配" }}
                                            </template>
                                        </el-table-column>
                                        <el-table-column
                                            label="匹配内容"
                                            prop="keyword"
                                            min-width="200"></el-table-column>
                                        <el-table-column label="回复内容" min-width="300">
                                            <template #default="{ row }">
                                                <el-button link type="primary" @click="handleKeywordsEdit(row)">
                                                    点击查看
                                                </el-button>
                                            </template>
                                        </el-table-column>
                                        <el-table-column label="操作" width="120" fixed="right">
                                            <template #default="{ row }">
                                                <el-button link type="primary" @click="handleKeywordsEdit(row)">
                                                    编辑
                                                </el-button>
                                                <el-button link type="danger" @click="handleKeywordsDelete(row.id)">
                                                    删除
                                                </el-button>
                                            </template>
                                        </el-table-column>
                                    </el-table>
                                </div>
                                <div class="h-[64px] flex justify-center items-center">
                                    <pagination
                                        v-model="pager"
                                        layout="prev, pager, next"
                                        @change="getLists"></pagination>
                                </div>
                            </el-collapse-item>
                        </el-collapse>
                    </div>
                </div>
            </el-scrollbar>
        </div>
        <!-- 保存按钮 -->
        <div class="flex items-center justify-center mt-4">
            <el-button
                type="primary"
                class="w-[318px] !rounded-full !h-[50px]"
                :loading="isLockSubmit"
                @click="lockSubmit">
                保存
            </el-button>
        </div>
    </div>
    <!-- 弹窗组件 -->
    <keywords-edit v-if="showKeywords" ref="keywordsEditRef" @close="showKeywords = false" @success="getLists" />
    <workflow-edit
        v-if="showWorkflow"
        ref="workflowEditRef"
        @close="showWorkflow = false"
        @success="getWorkFlowSuccess" />
</template>

<script setup lang="ts">
import { robotKeywordsLists, deleteRobotKeywords } from "@/api/ai_application/agent/index";
import KeywordsEdit from "./keywords-edit.vue";
import WorkflowEdit from "./workflow-edit.vue";
import type { Agent } from "../enums";
import feedback from "@/utils/feedback";
import { usePaging } from "@/hooks/usePaging";
import { useLockFn } from "@/hooks/useLockFn";

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

const formData = defineModel<Agent>("modelValue", {
    default: () => ({
        menus: [],
        flow_status: 0,
        flow_config: {
            workflow_id: "",
            bot_id: "",
        },
    }),
});

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
const activeNames = ref<string[]>([]);

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
const handleDeleteWorkflow = async () => {
    await feedback.confirm("确定删除该工作流吗？");
    formData.value.flow_status = 0;
    formData.value.flow_config = {
        workflow_id: "",
        bot_id: "",
        app_id: "",
        api_token: "",
    };
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
    await feedback.confirm("确定删除该问答话术吗？");
    await deleteRobotKeywords({ id });
    feedback.msgSuccess("删除成功");
    getLists(); // 刷新列表
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
:deep(.el-collapse) {
    --el-collapse-header-height: 70px; // 自适应高度
    --el-collapse-header-padding: 10px 0; // 调整内边距
    .el-collapse-item__header {
        font-weight: initial;
    }
    .el-collapse-item__arrow {
        display: none; // 隐藏默认箭头
    }
}
:deep(.el-table) {
    border-radius: 8px;
    thead th.el-table__cell.is-leaf {
        border-top: 0;
    }
    &.el-table--fit .el-table__inner-wrapper:before {
        display: none;
    }
}
</style>
