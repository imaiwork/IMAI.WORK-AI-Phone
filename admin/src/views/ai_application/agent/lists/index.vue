<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="创作用户">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.nickname"
                        placeholder="请输入创作用户"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <!-- 创建类型 -->
                <el-form-item label="创建类型">
                    <el-select
                        v-model="queryParams.source"
                        class="!w-[160px]"
                        placeholder="请选择创建类型"
                        @change="resetPage">
                        <el-option label="后台" :value="0"></el-option>
                        <el-option label="用户" :value="1"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4">
                <el-tabs v-model="activeTab" @tab-click="handleTabClick">
                    <el-tab-pane label="智能体" :name="AgentType.AGENT"></el-tab-pane>
                    <el-tab-pane label="Coze智能体" :name="AgentType.COZE_AGENT"></el-tab-pane>
                    <el-tab-pane label="Coze工作流" :name="AgentType.COZE_FLOW"></el-tab-pane>
                </el-tabs>
            </div>
            <div class="mb-4 flex justify-between">
                <el-dropdown v-perms="['ai_assistant.agent/add']">
                    <el-button type="primary">
                        <template #icon>
                            <icon name="el-icon-Plus" />
                        </template>
                        创建
                    </el-button>
                    <template #dropdown>
                        <el-dropdown-menu>
                            <el-dropdown-item @click="handleEdit()"> 创建智能体 </el-dropdown-item>
                            <el-dropdown-item @click="handleCozeEdit()"> 创建Coze智能体 </el-dropdown-item>
                            <el-dropdown-item @click="handleCozeFlowEdit()"> 创建Coze工作流 </el-dropdown-item>
                        </el-dropdown-menu>
                    </template>
                </el-dropdown>
                <el-button
                    v-perms="['ai_application.agent/delete']"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
            </div>
            <el-table
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed="left" />
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="图标" min-width="100">
                    <template #default="{ row }">
                        <div class="flex items-center">
                            <image-contain
                                radius="50%"
                                class="flex-none"
                                v-if="row.image || row.avatar"
                                :src="row.image || row.avatar"
                                :width="48"
                                :height="48"
                                :preview-src-list="[row.image || row.avatar]"
                                preview-teleported
                                fit="cover" />
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="名称" prop="name" min-width="140" show-overflow-tooltip />
                <el-table-column label="分类" min-width="140" prop="agent_cate_name">
                    <template #default="{ row }">
                        {{ row.agent_cate_name || row.cate_name }}
                    </template>
                </el-table-column>
                <el-table-column label="创建用户" prop="nickname" min-width="140">
                    <template #default="{ row }">
                        {{ row.nickname || row.source_text }}
                    </template>
                </el-table-column>
                <el-table-column label="创建类型" min-width="140">
                    <template #default="{ row }">
                        {{ row.source == 1 ? "用户" : "后台" }}
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" v-perms="['ai_application.agent/edit']" link @click="handleEdit(row)">
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['ai_application.agent/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
    <coze-edit ref="cozeEditRef" v-if="showCozeEdit" @close="showCozeEdit = false" @success="resetPage"></coze-edit>
    <coze-flow-edit
        ref="cozeFlowEditRef"
        v-if="showCozeFlowEdit"
        @close="showCozeFlowEdit = false"
        @success="resetPage"></coze-flow-edit>
</template>
<script lang="ts" setup>
import { addAgent } from "@/api/ai_application/agent";
import { usePaging } from "@/hooks/usePaging";
import { getRoutePath } from "@/router";
import { getAgentLists, deleteAgent } from "@/api/ai_application/agent";
import { getCozeAgentList, cozeAgentDelete } from "@/api/ai_application/agent/coze";
import feedback from "@/utils/feedback";
import CozeEdit from "./components/coze-edit.vue";
import CozeFlowEdit from "./components/coze-flow-edit.vue";

enum AgentType {
    AGENT = 0,
    COZE_AGENT = 1,
    COZE_FLOW = 2,
}

const router = useRouter();
const activeTab = ref(AgentType.AGENT);
const queryParams = reactive({
    name: "",
    nickname: "",
    source: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: (params) => {
        if (activeTab.value === AgentType.AGENT) {
            return getAgentLists(params);
        } else {
            return getCozeAgentList({
                ...params,
                type: activeTab.value,
            });
        }
    },
    params: queryParams,
});

const handleTabClick = (tab: any) => {
    activeTab.value = tab.paneName;
    getLists();
};

const cozeEditRef = ref<InstanceType<typeof CozeEdit>>();
const cozeFlowEditRef = ref<InstanceType<typeof CozeFlowEdit>>();

const showCozeEdit = ref(false);
const showCozeFlowEdit = ref(false);

const handleCozeEdit = async (row?: any) => {
    showCozeEdit.value = true;
    await nextTick();
    await cozeEditRef.value?.open();
    row && cozeEditRef.value?.getDetail(row.id);
};

const handleCozeFlowEdit = async (row?: any) => {
    showCozeFlowEdit.value = true;
    await nextTick();
    await cozeFlowEditRef.value?.open();
    row && cozeFlowEditRef.value?.getDetail(row.id);
};

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    if (activeTab.value === AgentType.AGENT) {
        await deleteAgent({ id });
    } else {
        await cozeAgentDelete({ id });
    }
    getLists();
};

const handleEdit = async (row?: any) => {
    if (activeTab.value === AgentType.AGENT) {
        let id = row?.id;
        if (!id) {
            const res = await addAgent({});
            id = res.id;
        }
        router.push({
            path: getRoutePath("ai_application.agent/add:edit"),
            query: {
                id,
            },
        });
    } else if (activeTab.value === AgentType.COZE_AGENT) {
        handleCozeEdit(row);
    } else if (activeTab.value === AgentType.COZE_FLOW) {
        handleCozeFlowEdit(row);
    }
};

getLists();
</script>
